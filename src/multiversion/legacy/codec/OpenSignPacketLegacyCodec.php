<?php

declare(strict_types=1);

// Clientbound-only.

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferWriter;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\OpenSignPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class OpenSignPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(OpenSignPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		LegacyBlockPosition::write($out, $packet->getBlockPosition());
		CommonTypes::putBool($out, $packet->isFront());

		return $out->getData();
	}
}
