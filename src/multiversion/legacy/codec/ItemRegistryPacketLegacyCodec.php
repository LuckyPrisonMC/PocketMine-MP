<?php

declare(strict_types=1);

/*
 * ItemRegistryPacket establishes the authoritative numeric-id <-> item-name
 * mapping for the whole session (sent once, right after StartGamePacket).
 * The wire FORMAT of this packet is confirmed byte-identical between 1.26.0
 * and 1.26.30 - only the DATA (which item has which numeric id) differs,
 * matching the same per-version item id shifts described in
 * LegacyItemTranslatorFactory.
 *
 * Because the format itself didn't change, there's no need for a hand-written
 * byte-level codec here: we just build a new ItemRegistryPacket using the
 * legacy protocol's own item dictionary entries, then delegate to the
 * packet's own built-in encode() (safe, since the wire format is identical).
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferWriter;
use pocketmine\multiversion\legacy\LegacyItemTranslatorFactory;
use pocketmine\network\mcpe\protocol\ItemRegistryPacket;

final class ItemRegistryPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(ItemRegistryPacket $packet, int $protocolVersion) : string{
		$legacyDictionary = LegacyItemTranslatorFactory::getInstance($protocolVersion);

		$legacyPacket = ItemRegistryPacket::create($legacyDictionary->getEntries());
		$legacyPacket->senderSubId = $packet->senderSubId;
		$legacyPacket->recipientSubId = $packet->recipientSubId;

		$out = new ByteBufferWriter();
		$legacyPacket->encode($out);
		return $out->getData();
	}
}
