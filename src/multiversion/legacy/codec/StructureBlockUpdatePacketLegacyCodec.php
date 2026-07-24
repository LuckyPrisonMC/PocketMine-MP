<?php

declare(strict_types=1);

// Serverbound-only.

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\StructureBlockUpdatePacket;

final class StructureBlockUpdatePacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : StructureBlockUpdatePacket{
		$blockPosition = LegacyBlockPosition::read($in);
		$structureEditorData = CommonTypes::getStructureEditorData($in);
		$isPowered = CommonTypes::getBool($in);
		$waterlogged = CommonTypes::getBool($in);
		return StructureBlockUpdatePacket::create($blockPosition, $structureEditorData, $isPowered, $waterlogged);
	}
}
