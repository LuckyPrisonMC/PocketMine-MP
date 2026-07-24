<?php

declare(strict_types=1);

/*
 * Separate ItemTypeDictionary per legacy protocol (1.26.0 / 1.26.10 / 1.26.20).
 * Item IDs are assigned by name rather than list position, so most stay
 * stable across versions, but a handful (shulker_box, dye bundles,
 * stone_spear, planks, trial_key, etc.) do shift - hence still needing
 * per-protocol translation instead of assuming they're all constant.
 */

namespace pocketmine\multiversion\legacy;

use pocketmine\network\mcpe\convert\ItemTypeDictionaryFromDataHelper;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\utils\Filesystem;

final class LegacyItemTranslatorFactory{

	private const RESOURCE_DIR = __DIR__ . "/resources";

	/**
	 * Maps legacy protocol ID => resource file suffix, e.g. 924 => "1.26.0".
	 *
	 * @var array<int, string>
	 */
	private const PROTOCOL_RESOURCE_SUFFIX = [
		898 => "1.21.130",
		924 => "1.26.0",
		944 => "1.26.10",
		975 => "1.26.20",
	];

	/** @var array<int, ItemTypeDictionary> */
	private static array $instances = [];

	private function __construct(){
		//NOOP
	}

	public static function getInstance(int $protocolVersion) : ItemTypeDictionary{
		if(!isset(self::$instances[$protocolVersion])){
			$suffix = self::PROTOCOL_RESOURCE_SUFFIX[$protocolVersion]
				?? throw new \InvalidArgumentException("No legacy item list registered for protocol $protocolVersion");

			$requiredItemListRaw = Filesystem::fileGetContents(self::RESOURCE_DIR . "/required_item_list-$suffix.json");
			self::$instances[$protocolVersion] = ItemTypeDictionaryFromDataHelper::loadFromString($requiredItemListRaw);
		}
		return self::$instances[$protocolVersion];
	}

	/**
	 * Translates a MODERN (1.26.30, used by this server) item network numeric ID
	 * into the numeric ID for the given legacy protocol, for sending to that client.
	 *
	 * @var array<int, array<int, int>> protocolVersion => [modernId => legacyId]
	 */
	private static array $newToOldCache = [];

	public static function translateModernIdToLegacy(int $modernId, ItemTypeDictionary $modernDictionary, int $legacyProtocolVersion) : int{
		if($modernId === 0){
			//0 is the "no item" sentinel, not a real item - never translate it.
			return 0;
		}
		if(isset(self::$newToOldCache[$legacyProtocolVersion][$modernId])){
			return self::$newToOldCache[$legacyProtocolVersion][$modernId];
		}

		try{
			$name = $modernDictionary->fromIntId($modernId);
			$legacyId = self::getInstance($legacyProtocolVersion)->fromStringId($name);
		}catch(\InvalidArgumentException){
			//Not present in this legacy protocol - leave the id as-is rather than
			//guessing; worst case the item looks wrong, the rest of the packet stays intact.
			$legacyId = $modernId;
		}

		return self::$newToOldCache[$legacyProtocolVersion][$modernId] = $legacyId;
	}

	/**
	 * Translates an item network numeric ID from the given LEGACY protocol into the
	 * MODERN numeric ID (1.26.30, used internally by this server). Needed when a legacy
	 * client sends an item stack to the server.
	 *
	 * @var array<int, array<int, int>> protocolVersion => [legacyId => modernId]
	 */
	private static array $oldToNewCache = [];

	public static function translateLegacyIdToModern(int $legacyId, ItemTypeDictionary $modernDictionary, int $legacyProtocolVersion) : int{
		if($legacyId === 0){
			return 0;
		}
		if(isset(self::$oldToNewCache[$legacyProtocolVersion][$legacyId])){
			return self::$oldToNewCache[$legacyProtocolVersion][$legacyId];
		}

		try{
			$name = self::getInstance($legacyProtocolVersion)->fromIntId($legacyId);
			$modernId = $modernDictionary->fromStringId($name);
		}catch(\InvalidArgumentException){
			//Should essentially never happen (every legacy item still exists in 1.26.30),
			//but fall back to the id as-is rather than crashing.
			$modernId = $legacyId;
		}

		return self::$oldToNewCache[$legacyProtocolVersion][$legacyId] = $modernId;
	}
}
