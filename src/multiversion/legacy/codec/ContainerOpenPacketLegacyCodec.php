<?php

declare(strict_types=1);

// Clientbound-only.

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class ContainerOpenPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(ContainerOpenPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		Byte::writeUnsigned($out, $packet->windowId);
		Byte::writeUnsigned($out, $packet->windowType);
		LegacyBlockPosition::write($out, $packet->blockPosition);
		CommonTypes::putActorUniqueId($out, $packet->actorUniqueId);

		return $out->getData();
	}
}
