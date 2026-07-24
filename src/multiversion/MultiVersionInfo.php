<?php

declare(strict_types=1);

/*
 * Stores the list of Minecraft: Bedrock Edition protocols allowed
 * to connect to this server, besides the main protocol supported
 * by vendor/pocketmine/bedrock-protocol (ProtocolInfo::CURRENT_PROTOCOL).
 *
 * IMPORTANT: Adding a protocol here ONLY stops the client of that version
 * from being kicked during the handshake. This does NOT automatically
 * translate the packet/block/item format between versions. As long as there's no
 * additional packet translation code, a legacy client may experience
 * bugs (missing items, wrong blocks, etc.) depending on how far
 * the protocol difference is.
 */

namespace pocketmine\multiversion;

use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class MultiVersionInfo{

	private function __construct(){
		//NOOP
	}

	/**
	 * List of additional supported protocols, besides ProtocolInfo::CURRENT_PROTOCOL.
	 *
	 * Format: protocol_id => Minecraft version (used for displaying /version etc.)
	 *
	 * Ordered from OLDEST to NEWEST (important for
	 * getOldestSupportedVersion()).
	 *
	 * @var array<int, string>
	 */
	private const ADDITIONAL_SUPPORTED_PROTOCOLS = [
		898 => "1.21.130",
		924 => "1.26.0",
		944 => "1.26.10",
		975 => "1.26.20",
	];

	/**
	 * @return int[]
	 */
	public static function getSupportedProtocols() : array{
		return [
			ProtocolInfo::CURRENT_PROTOCOL,
			...array_keys(self::ADDITIONAL_SUPPORTED_PROTOCOLS)
		];
	}

	public static function isProtocolSupported(int $protocol) : bool{
		return in_array($protocol, self::getSupportedProtocols(), true);
	}

	/**
	 * Oldest supported Minecraft version (for display in /version).
	 * Falls back to the main version if there are no additional protocols.
	 */
	public static function getOldestSupportedVersion() : string{
		if(count(self::ADDITIONAL_SUPPORTED_PROTOCOLS) === 0){
			return ProtocolInfo::MINECRAFT_VERSION_NETWORK;
		}
		return array_values(self::ADDITIONAL_SUPPORTED_PROTOCOLS)[0];
	}

	/**
	 * Newest supported Minecraft version (for display in /version).
	 */
	public static function getNewestSupportedVersion() : string{
		return ProtocolInfo::MINECRAFT_VERSION_NETWORK;
	}

	/**
	 * Version range string for display, e.g. "1.26.0 - 1.26.30".
	 * If only 1 version is supported, only that version is shown.
	 */
	public static function getVersionRangeString() : string{
		$oldest = self::getOldestSupportedVersion();
		$newest = self::getNewestSupportedVersion();
		if($oldest === $newest){
			return $newest;
		}
		return $oldest . " - " . $newest;
	}
}
