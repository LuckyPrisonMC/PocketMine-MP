<?php

declare(strict_types=1);

// Bidirectional.

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\multiversion\legacy\LegacyItemStackWrapper;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class MobArmorEquipmentPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(MobArmorEquipmentPacket $packet, int $protocolVersion) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		CommonTypes::putActorRuntimeId($out, $packet->actorRuntimeId);
		LegacyItemStackWrapper::write($out, $packet->head, $protocolVersion);
		LegacyItemStackWrapper::write($out, $packet->chest, $protocolVersion);
		LegacyItemStackWrapper::write($out, $packet->legs, $protocolVersion);
		LegacyItemStackWrapper::write($out, $packet->feet, $protocolVersion);
		LegacyItemStackWrapper::write($out, $packet->body, $protocolVersion);

		return $out->getData();
	}

	public static function decodePayload(ByteBufferReader $in, int $protocolVersion) : MobArmorEquipmentPacket{
		$packet = new MobArmorEquipmentPacket();
		$packet->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$packet->head = LegacyItemStackWrapper::read($in, $protocolVersion);
		$packet->chest = LegacyItemStackWrapper::read($in, $protocolVersion);
		$packet->legs = LegacyItemStackWrapper::read($in, $protocolVersion);
		$packet->feet = LegacyItemStackWrapper::read($in, $protocolVersion);
		$packet->body = LegacyItemStackWrapper::read($in, $protocolVersion);
		return $packet;
	}
}
