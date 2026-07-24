<?php

declare(strict_types=1);

// Same field structure as 1.26.30, just a different BlockPosition encoding.

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class PlayerActionPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(PlayerActionPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		CommonTypes::putActorRuntimeId($out, $packet->actorRuntimeId);
		VarInt::writeSignedInt($out, $packet->action);
		LegacyBlockPosition::write($out, $packet->blockPosition);
		LegacyBlockPosition::write($out, $packet->resultPosition);
		VarInt::writeSignedInt($out, $packet->face);

		return $out->getData();
	}

	/**
	 * @param ByteBufferReader $in reader that has already passed the packet header (payload only)
	 */
	public static function decodePayload(ByteBufferReader $in) : PlayerActionPacket{
		$packet = new PlayerActionPacket();
		$packet->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$packet->action = VarInt::readSignedInt($in);
		$packet->blockPosition = LegacyBlockPosition::read($in);
		$packet->resultPosition = LegacyBlockPosition::read($in);
		$packet->face = VarInt::readSignedInt($in);
		return $packet;
	}
}
