<?php

declare(strict_types=1);

/*
 * Exact port of ActorEventPacket from bedrock-protocol 55.0.0 (1.26.0).
 * Bidirectional. The firePosition field (optional Vector3) doesn't exist at all
 * in this version - it's not written/read.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class ActorEventPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(ActorEventPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		CommonTypes::putActorRuntimeId($out, $packet->actorRuntimeId);
		Byte::writeUnsigned($out, $packet->eventId);
		VarInt::writeSignedInt($out, $packet->eventData);

		return $out->getData();
	}

	public static function decodePayload(ByteBufferReader $in) : ActorEventPacket{
		$packet = new ActorEventPacket();
		$packet->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$packet->eventId = Byte::readUnsigned($in);
		$packet->eventData = VarInt::readSignedInt($in);
		return $packet;
	}
}
