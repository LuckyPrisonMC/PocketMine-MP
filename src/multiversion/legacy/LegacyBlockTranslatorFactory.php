<?php

declare(strict_types=1);

/*
 * Separate BlockTranslator per legacy protocol (1.26.0 / 1.26.10 / 1.26.20).
 * The block state list is almost fully reordered between each of these
 * releases (not just appended to), so each protocol needs its own dictionary
 * rather than sharing one - otherwise block runtime IDs render wrong.
 *
 * Lazy per-process singleton, safe to use inside AsyncTask workers too.
 */

namespace pocketmine\multiversion\legacy;

use pocketmine\network\mcpe\convert\BlockStateDictionary;
use pocketmine\network\mcpe\convert\BlockTranslator;
use pocketmine\utils\Filesystem;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

final class LegacyBlockTranslatorFactory{

	private const RESOURCE_DIR = __DIR__ . "/resources";

	/**
	 * Maps legacy protocol ID => resource file suffix, e.g. 924 => "1.26.0".
	 *
	 * @var array<int, string>
	 */
	private const PROTOCOL_RESOURCE_SUFFIX = [
		//898 (1.21.130) reuses the 1.26.0 block palette files - confirmed byte-identical,
		//no new block was added/reordered between these two releases.
		898 => "1.26.0",
		924 => "1.26.0",
		944 => "1.26.10",
		975 => "1.26.20",
	];

	/** @var array<int, BlockTranslator> */
	private static array $instances = [];

	private function __construct(){
		//NOOP
	}

	public static function getInstance(int $protocolVersion) : BlockTranslator{
		if(!isset(self::$instances[$protocolVersion])){
			$suffix = self::PROTOCOL_RESOURCE_SUFFIX[$protocolVersion]
				?? throw new \InvalidArgumentException("No legacy block palette registered for protocol $protocolVersion");

			$canonicalBlockStatesRaw = Filesystem::fileGetContents(self::RESOURCE_DIR . "/canonical_block_states-$suffix.nbt");
			$metaMappingRaw = Filesystem::fileGetContents(self::RESOURCE_DIR . "/block_state_meta_map-$suffix.json");

			self::$instances[$protocolVersion] = new BlockTranslator(
				BlockStateDictionary::loadFromString($canonicalBlockStatesRaw, $metaMappingRaw),
				//This BlockStateSerializer is a PM-internal concern (Block -> "current" BlockStateData),
				//not related to the client protocol version, so it's safe to reuse as-is.
				GlobalBlockStateHandlers::getSerializer()
			);
		}
		return self::$instances[$protocolVersion];
	}

	/**
	 * Translates a MODERN (1.26.30, used by this server) block network runtime ID
	 * into the runtime ID for the given legacy protocol, for sending to that client.
	 *
	 * @var array<int, array<int, int>> protocolVersion => [modernRuntimeId => legacyRuntimeId]
	 */
	private static array $newToOldCache = [];

	public static function translateModernRuntimeIdToLegacy(int $modernRuntimeId, BlockTranslator $modernTranslator, int $legacyProtocolVersion) : int{
		if(isset(self::$newToOldCache[$legacyProtocolVersion][$modernRuntimeId])){
			return self::$newToOldCache[$legacyProtocolVersion][$modernRuntimeId];
		}

		$stateData = $modernTranslator->getBlockStateDictionary()->generateDataFromStateId($modernRuntimeId);
		$legacyTranslator = self::getInstance($legacyProtocolVersion);
		$legacyDictionary = $legacyTranslator->getBlockStateDictionary();

		$legacyRuntimeId = $stateData !== null ? $legacyDictionary->lookupStateIdFromData($stateData) : null;

		if($legacyRuntimeId === null){
			//Not present in this legacy protocol (added by Mojang later on) - fall back
			//to PM's error-marker block state instead of corrupting the data.
			$legacyRuntimeId = $legacyDictionary->lookupStateIdFromData($legacyTranslator->getFallbackStateData()) ?? 0;
		}

		return self::$newToOldCache[$legacyProtocolVersion][$modernRuntimeId] = $legacyRuntimeId;
	}

	/**
	 * Translates a block network runtime ID from the given LEGACY protocol's palette into the
	 * MODERN palette (1.26.30, used internally by this server). Needed when a legacy client
	 * sends a block runtime ID to the server (e.g. inside an ItemStack).
	 *
	 * @var array<int, array<int, int>> protocolVersion => [legacyRuntimeId => modernRuntimeId]
	 */
	private static array $oldToNewCache = [];

	public static function translateLegacyRuntimeIdToModern(int $legacyRuntimeId, BlockTranslator $modernTranslator, int $legacyProtocolVersion) : int{
		if(isset(self::$oldToNewCache[$legacyProtocolVersion][$legacyRuntimeId])){
			return self::$oldToNewCache[$legacyProtocolVersion][$legacyRuntimeId];
		}

		$legacyDictionary = self::getInstance($legacyProtocolVersion)->getBlockStateDictionary();
		$stateData = $legacyDictionary->generateDataFromStateId($legacyRuntimeId);
		$modernRuntimeId = $stateData !== null ? $modernTranslator->getBlockStateDictionary()->lookupStateIdFromData($stateData) : null;

		if($modernRuntimeId === null){
			//Should essentially never happen (every legacy block still exists in 1.26.30),
			//but fall back to the modern fallback state just in case, rather than crashing.
			$modernRuntimeId = $modernTranslator->getBlockStateDictionary()->lookupStateIdFromData($modernTranslator->getFallbackStateData()) ?? 0;
		}

		return self::$oldToNewCache[$legacyProtocolVersion][$legacyRuntimeId] = $modernRuntimeId;
	}
}
