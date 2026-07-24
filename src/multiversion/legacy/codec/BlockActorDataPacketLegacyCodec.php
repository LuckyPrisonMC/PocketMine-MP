<?php

declare(strict_types=1);

// Bidirectional (clientbound + serverbound).

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;

final class BlockActorDataPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(BlockActorDataPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		LegacyBlockPosition::write($out, $packet->blockPosition);
		$out->writeByteArray($packet->nbt->getEncodedNbt());

		return $out->getData();
	}

	public static function decodePayload(ByteBufferReader $in) : BlockActorDataPacket{
		$packet = new BlockActorDataPacket();
		$packet->blockPosition = LegacyBlockPosition::read($in);
		$packet->nbt = new CacheableNbt(CommonTypes::getNbtCompoundRoot($in));
		return $packet;
	}
}
