<?php

declare(strict_types=1);

// Same header format as the vendor's DataPacket::encodeHeader(), unchanged across versions.

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\DataPacket;

final class LegacyPacketHeader{

	private function __construct(){
		//NOOP
	}

	public static function write(ByteBufferWriter $out, DataPacket $packet) : void{
		VarInt::writeUnsignedInt(
			$out,
			$packet::NETWORK_ID |
			($packet->senderSubId << 10) |
			($packet->recipientSubId << 12)
		);
	}
}
