<?php

declare(strict_types=1);

/*
 * Serverbound-only. PM doesn't process this packet's contents at all, but
 * without this codec, decoding the new trailing `filterProfanityChange`
 * field would underrun the buffer and could disconnect the session.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\GraphicsMode;
use pocketmine\network\mcpe\protocol\UpdateClientOptionsPacket;

final class UpdateClientOptionsPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : UpdateClientOptionsPacket{
		$graphicsMode = CommonTypes::readOptional($in, fn() => GraphicsMode::fromPacket(Byte::readUnsigned($in)));
		return UpdateClientOptionsPacket::create($graphicsMode, null);
	}
}
