<?php

declare(strict_types=1);

/*
 * Bidirectional. `sound` was an int (see LegacySoundMap/LegacySoundMap898), now
 * a string; `firePosition` doesn't exist in either legacy version.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\multiversion\legacy\LegacySoundMap;
use pocketmine\multiversion\legacy\LegacySoundMap898;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class LevelSoundEventPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(LevelSoundEventPacket $packet, int $protocolVersion) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		$legacySoundId = $protocolVersion === 898
			? LegacySoundMap898::newStringToOldId($packet->sound)
			: LegacySoundMap::newStringToOldId($packet->sound);
		VarInt::writeUnsignedInt($out, $legacySoundId);
		CommonTypes::putVector3($out, $packet->position);
		VarInt::writeSignedInt($out, $packet->extraData);
		CommonTypes::putString($out, $packet->entityType);
		CommonTypes::putBool($out, $packet->isBabyMob);
		CommonTypes::putBool($out, $packet->disableRelativeVolume);
		LE::writeSignedLong($out, $packet->actorUniqueId);
		//firePosition is intentionally not written (doesn't exist in this version)

		return $out->getData();
	}

	public static function decodePayload(ByteBufferReader $in, int $protocolVersion) : LevelSoundEventPacket{
		$packet = new LevelSoundEventPacket();
		$legacySoundId = VarInt::readUnsignedInt($in);
		$packet->sound = $protocolVersion === 898
			? LegacySoundMap898::oldIdToNewString($legacySoundId)
			: LegacySoundMap::oldIdToNewString($legacySoundId);
		$packet->position = CommonTypes::getVector3($in);
		$packet->extraData = VarInt::readSignedInt($in);
		$packet->entityType = CommonTypes::getString($in);
		$packet->isBabyMob = CommonTypes::getBool($in);
		$packet->disableRelativeVolume = CommonTypes::getBool($in);
		$packet->actorUniqueId = LE::readSignedLong($in);
		$packet->firePosition = null;
		return $packet;
	}
}
