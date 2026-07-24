<?php

declare(strict_types=1);

// Serverbound-only.

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\network\mcpe\protocol\CommandBlockUpdatePacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class CommandBlockUpdatePacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : CommandBlockUpdatePacket{
		$packet = new CommandBlockUpdatePacket();
		$packet->isBlock = CommonTypes::getBool($in);

		if($packet->isBlock){
			$packet->blockPosition = LegacyBlockPosition::read($in);
			$packet->commandBlockMode = VarInt::readUnsignedInt($in);
			$packet->isRedstoneMode = CommonTypes::getBool($in);
			$packet->isConditional = CommonTypes::getBool($in);
		}else{
			$packet->minecartActorRuntimeId = CommonTypes::getActorRuntimeId($in);
		}

		$packet->command = CommonTypes::getString($in);
		$packet->lastOutput = CommonTypes::getString($in);
		$packet->name = CommonTypes::getString($in);
		$packet->filteredName = CommonTypes::getString($in);
		$packet->shouldTrackOutput = CommonTypes::getBool($in);
		$packet->tickDelay = LE::readUnsignedInt($in);
		$packet->executeOnFirstTick = CommonTypes::getBool($in);

		return $packet;
	}
}
