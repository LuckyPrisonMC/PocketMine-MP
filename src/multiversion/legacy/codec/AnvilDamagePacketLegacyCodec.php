<?php

declare(strict_types=1);

// Serverbound-only.

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\network\mcpe\protocol\AnvilDamagePacket;

final class AnvilDamagePacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : AnvilDamagePacket{
		$damageAmount = Byte::readUnsigned($in);
		$blockPosition = LegacyBlockPosition::read($in);
		return AnvilDamagePacket::create($blockPosition, $damageAmount);
	}
}
