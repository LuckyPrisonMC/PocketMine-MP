<?php

declare(strict_types=1);

// Clientbound-only.

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;

final class SetSpawnPositionPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(SetSpawnPositionPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		VarInt::writeSignedInt($out, $packet->spawnType);
		LegacyBlockPosition::write($out, $packet->spawnPosition);
		VarInt::writeSignedInt($out, $packet->dimension);
		LegacyBlockPosition::write($out, $packet->causingBlockPosition);

		return $out->getData();
	}
}
