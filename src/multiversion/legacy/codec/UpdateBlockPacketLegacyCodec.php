<?php

declare(strict_types=1);

/*
 * Clientbound-only. blockRuntimeId needs translating through the legacy
 * block palette, same as everywhere else that carries a runtime id.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\multiversion\legacy\LegacyBlockTranslatorFactory;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;

final class UpdateBlockPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(UpdateBlockPacket $packet, int $protocolVersion) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		$modernTranslator = TypeConverter::getInstance()->getBlockTranslator();
		$legacyBlockRuntimeId = LegacyBlockTranslatorFactory::translateModernRuntimeIdToLegacy($packet->blockRuntimeId, $modernTranslator, $protocolVersion);

		LegacyBlockPosition::write($out, $packet->blockPosition);
		VarInt::writeUnsignedInt($out, $legacyBlockRuntimeId);
		VarInt::writeUnsignedInt($out, $packet->flags);
		VarInt::writeUnsignedInt($out, $packet->dataLayerId);

		return $out->getData();
	}
}
