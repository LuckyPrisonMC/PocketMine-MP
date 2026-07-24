<?php

declare(strict_types=1);

// Clientbound-only. minBound/maxBound need LegacyBlockPosition.

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class AddVolumeEntityPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(AddVolumeEntityPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		VarInt::writeUnsignedInt($out, $packet->getEntityNetId());
		$out->writeByteArray($packet->getData()->getEncodedNbt());
		CommonTypes::putString($out, $packet->getJsonIdentifier());
		CommonTypes::putString($out, $packet->getInstanceName());
		LegacyBlockPosition::write($out, $packet->getMinBound());
		LegacyBlockPosition::write($out, $packet->getMaxBound());
		VarInt::writeSignedInt($out, $packet->getDimension());
		CommonTypes::putString($out, $packet->getEngineVersion());

		return $out->getData();
	}
}
