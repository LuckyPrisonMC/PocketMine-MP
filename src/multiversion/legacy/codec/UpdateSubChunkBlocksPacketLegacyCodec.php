<?php

declare(strict_types=1);

// Clientbound-only.

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\multiversion\legacy\LegacyUpdateSubChunkBlocksPacketEntry;
use pocketmine\network\mcpe\protocol\UpdateSubChunkBlocksPacket;
use function count;

final class UpdateSubChunkBlocksPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(UpdateSubChunkBlocksPacket $packet, int $protocolVersion) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		LegacyBlockPosition::write($out, $packet->getBaseBlockPosition());

		VarInt::writeUnsignedInt($out, count($packet->getLayer0Updates()));
		foreach($packet->getLayer0Updates() as $update){
			LegacyUpdateSubChunkBlocksPacketEntry::write($out, $update, $protocolVersion);
		}

		VarInt::writeUnsignedInt($out, count($packet->getLayer1Updates()));
		foreach($packet->getLayer1Updates() as $update){
			LegacyUpdateSubChunkBlocksPacketEntry::write($out, $update, $protocolVersion);
		}

		return $out->getData();
	}
}
