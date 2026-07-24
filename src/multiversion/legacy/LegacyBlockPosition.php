<?php

declare(strict_types=1);

/*
 * In protocols 1.26.0-1.26.20, BlockPosition writes X/Z as signed VarInts
 * but Y as an unsigned VarInt (a legacy bit-reinterpretation quirk, not
 * zigzag). 1.26.30 merged this with the already-existing signed variant, so
 * X/Y/Z are all plain signed VarInts now.
 *
 * Only needed for BlockPosition fields that used the old unsigned-Y form -
 * fields that already used getSignedBlockPosition() need no translation,
 * since that variant's behavior didn't change.
 */

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\utils\Binary;

final class LegacyBlockPosition{

	private function __construct(){
		//NOOP
	}

	/**
	 * Equivalent to CommonTypes::getBlockPosition() in bedrock-protocol 55.0.0 (1.26.0).
	 */
	public static function read(ByteBufferReader $in) : BlockPosition{
		$x = VarInt::readSignedInt($in);
		$y = Binary::signInt(VarInt::readUnsignedInt($in)); //Y is written unsigned even though its value can be negative
		$z = VarInt::readSignedInt($in);
		return new BlockPosition($x, $y, $z);
	}

	/**
	 * Equivalent to CommonTypes::putBlockPosition() in bedrock-protocol 55.0.0 (1.26.0).
	 */
	public static function write(ByteBufferWriter $out, BlockPosition $blockPosition) : void{
		VarInt::writeSignedInt($out, $blockPosition->getX());
		VarInt::writeUnsignedInt($out, Binary::unsignInt($blockPosition->getY()));
		VarInt::writeSignedInt($out, $blockPosition->getZ());
	}
}
