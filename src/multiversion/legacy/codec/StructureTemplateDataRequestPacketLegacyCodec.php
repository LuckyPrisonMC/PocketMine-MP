<?php

declare(strict_types=1);

// Serverbound-only.

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\StructureTemplateDataRequestPacket;

final class StructureTemplateDataRequestPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : StructureTemplateDataRequestPacket{
		$structureTemplateName = CommonTypes::getString($in);
		$structureBlockPosition = LegacyBlockPosition::read($in);
		$structureSettings = CommonTypes::getStructureSettings($in);
		$requestType = Byte::readUnsigned($in);
		return StructureTemplateDataRequestPacket::create($structureTemplateName, $structureBlockPosition, $structureSettings, $requestType);
	}
}
