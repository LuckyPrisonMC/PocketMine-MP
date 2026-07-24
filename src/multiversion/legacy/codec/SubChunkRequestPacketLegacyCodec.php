<?php

declare(strict_types=1);

/*
 * Serverbound-only. In 1.26.30 the field order flips (entries before
 * basePosition instead of after), the entry count becomes a VarInt instead
 * of a fixed 4-byte int, and basePosition switches to fixed LE ints.
 *
 * SubChunkPacket (the clientbound side, actual chunk data) doesn't need a
 * codec here - despite the renamed method, its wire format never changed.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\SubChunkRequestPacket;
use pocketmine\network\mcpe\protocol\types\SubChunkPosition;
use pocketmine\network\mcpe\protocol\types\SubChunkPositionOffset;
use function count;

final class SubChunkRequestPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : SubChunkRequestPacket{
		$dimension = VarInt::readSignedInt($in);

		$x = VarInt::readSignedInt($in);
		$y = VarInt::readSignedInt($in);
		$z = VarInt::readSignedInt($in);
		$basePosition = new SubChunkPosition($x, $y, $z);

		$entries = [];
		for($i = 0, $count = LE::readUnsignedInt($in); $i < $count; $i++){
			$entries[] = SubChunkPositionOffset::read($in);
		}

		return SubChunkRequestPacket::create($dimension, $basePosition, $entries);
	}
}
