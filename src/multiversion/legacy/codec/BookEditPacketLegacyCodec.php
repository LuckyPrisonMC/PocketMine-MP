<?php

declare(strict_types=1);

/*
 * Serverbound-only. In 1.21.130, type/inventorySlot/pageNumber/
 * secondaryPageNumber are all Byte (fixed 1 byte), later versions switched
 * to VarInt and swapped the inventorySlot/type write order.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pocketmine\network\mcpe\protocol\BookEditPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class BookEditPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : BookEditPacket{
		$packet = new BookEditPacket();
		$packet->type = Byte::readUnsigned($in);
		$packet->inventorySlot = Byte::readUnsigned($in);

		switch($packet->type){
			case BookEditPacket::TYPE_REPLACE_PAGE:
			case BookEditPacket::TYPE_ADD_PAGE:
				$packet->pageNumber = Byte::readUnsigned($in);
				$packet->text = CommonTypes::getString($in);
				$packet->photoName = CommonTypes::getString($in);
				break;
			case BookEditPacket::TYPE_DELETE_PAGE:
				$packet->pageNumber = Byte::readUnsigned($in);
				break;
			case BookEditPacket::TYPE_SWAP_PAGES:
				$packet->pageNumber = Byte::readUnsigned($in);
				$packet->secondaryPageNumber = Byte::readUnsigned($in);
				break;
			case BookEditPacket::TYPE_SIGN_BOOK:
				$packet->title = CommonTypes::getString($in);
				$packet->author = CommonTypes::getString($in);
				$packet->xuid = CommonTypes::getString($in);
				break;
			default:
				throw new PacketDecodeException("Unknown book edit type $packet->type!");
		}

		return $packet;
	}
}
