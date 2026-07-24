<?php

declare(strict_types=1);

// Same as UpdateBlockPacket: blockRuntimeId needs translating through the legacy palette.

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\UpdateSubChunkBlocksPacketEntry;

final class LegacyUpdateSubChunkBlocksPacketEntry{

	private function __construct(){
		//NOOP
	}

	public static function write(ByteBufferWriter $out, UpdateSubChunkBlocksPacketEntry $entry, int $protocolVersion) : void{
		$modernTranslator = TypeConverter::getInstance()->getBlockTranslator();
		$legacyBlockRuntimeId = LegacyBlockTranslatorFactory::translateModernRuntimeIdToLegacy($entry->getBlockRuntimeId(), $modernTranslator, $protocolVersion);

		LegacyBlockPosition::write($out, $entry->getBlockPosition());
		VarInt::writeUnsignedInt($out, $legacyBlockRuntimeId);
		VarInt::writeUnsignedInt($out, $entry->getFlags());
		VarInt::writeUnsignedLong($out, $entry->getSyncedUpdateActorUniqueId());
		VarInt::writeUnsignedInt($out, $entry->getSyncedUpdateType());
	}
}
