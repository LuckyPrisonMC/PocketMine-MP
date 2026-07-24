<?php

declare(strict_types=1);

/*
 * Exact port of ClientCacheBlobStatusPacket from bedrock-protocol 55.0.0
 * (1.26.0). Serverbound-only. Read order differs: the old version reads BOTH
 * both counts (miss then hit) FIRST before any of the hashes; the new version reads per-section
 * (miss count + miss hashes, then hit count + hit hashes).
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ClientCacheBlobStatusPacket;

final class ClientCacheBlobStatusPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : ClientCacheBlobStatusPacket{
		$missCount = VarInt::readUnsignedInt($in);
		$hitCount = VarInt::readUnsignedInt($in);

		$missHashes = [];
		for($i = 0; $i < $missCount; ++$i){
			$missHashes[] = LE::readUnsignedLong($in);
		}

		$hitHashes = [];
		for($i = 0; $i < $hitCount; ++$i){
			$hitHashes[] = LE::readUnsignedLong($in);
		}

		return ClientCacheBlobStatusPacket::create($hitHashes, $missHashes);
	}
}
