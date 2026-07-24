<?php

declare(strict_types=1);

/*
 * For 1.26.0/1.26.10/1.26.20: a trailing `isLoggingChat` field is skipped,
 * and LevelSettings needs LegacyLevelSettings for its spawnPosition encoding.
 *
 * For 1.21.130 (protocol 898) specifically: the packet stops right after
 * networkPermissions - it doesn't have serverJoinInformation or
 * serverTelemetryData at all, and LevelSettings needs LegacyLevelSettings898
 * (which has 4 extra trailing string fields removed by 1.26.0).
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyLevelSettings;
use pocketmine\multiversion\legacy\LegacyLevelSettings898;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use function count;

final class StartGamePacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(StartGamePacket $packet, int $protocolVersion) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		CommonTypes::putActorUniqueId($out, $packet->actorUniqueId);
		CommonTypes::putActorRuntimeId($out, $packet->actorRuntimeId);
		VarInt::writeSignedInt($out, $packet->playerGamemode);

		CommonTypes::putVector3($out, $packet->playerPosition);

		LE::writeFloat($out, $packet->pitch);
		LE::writeFloat($out, $packet->yaw);

		if($protocolVersion === 898){
			LegacyLevelSettings898::write($out, $packet->levelSettings);
		}else{
			LegacyLevelSettings::write($out, $packet->levelSettings);
		}

		CommonTypes::putString($out, $packet->levelId);
		CommonTypes::putString($out, $packet->worldName);
		CommonTypes::putString($out, $packet->premiumWorldTemplateId);
		CommonTypes::putBool($out, $packet->isTrial);
		$packet->playerMovementSettings->write($out);
		LE::writeUnsignedLong($out, $packet->currentTick);

		VarInt::writeSignedInt($out, $packet->enchantmentSeed);

		VarInt::writeUnsignedInt($out, count($packet->blockPalette));
		foreach($packet->blockPalette as $entry){
			CommonTypes::putString($out, $entry->getName());
			$out->writeByteArray($entry->getStates()->getEncodedNbt());
		}

		CommonTypes::putString($out, $packet->multiplayerCorrelationId);
		CommonTypes::putBool($out, $packet->enableNewInventorySystem);
		CommonTypes::putString($out, $packet->serverSoftwareVersion);
		$out->writeByteArray($packet->playerActorProperties->getEncodedNbt());
		LE::writeUnsignedLong($out, $packet->blockPaletteChecksum);
		CommonTypes::putUUID($out, $packet->worldTemplateId);
		CommonTypes::putBool($out, $packet->enableClientSideChunkGeneration);
		CommonTypes::putBool($out, $packet->blockNetworkIdsAreHashes);
		$packet->networkPermissions->encode($out);

		if($protocolVersion !== 898){
			//serverJoinInformation's format changed completely in 1.26.30 and PM never
			//populates it anyway, so just force "absent" for legacy sessions.
			CommonTypes::putBool($out, false);
			$packet->serverTelemetryData->write($out);
		}
		//1.21.130 stops here entirely; 1.26.0/10/20 intentionally skip isLoggingChat (new in 1.26.30)

		return $out->getData();
	}
}
