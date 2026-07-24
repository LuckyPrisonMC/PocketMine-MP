<?php

declare(strict_types=1);

// Serverbound-only.

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\network\mcpe\protocol\LecternUpdatePacket;

final class LecternUpdatePacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : LecternUpdatePacket{
		$page = Byte::readUnsigned($in);
		$totalPages = Byte::readUnsigned($in);
		$blockPosition = LegacyBlockPosition::read($in);
		return LecternUpdatePacket::create($page, $totalPages, $blockPosition);
	}
}
