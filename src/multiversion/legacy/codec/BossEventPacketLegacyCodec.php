<?php

declare(strict_types=1);

/*
 * Exact port of BossEventPacket from bedrock-protocol 55.0.0 (1.26.0).
 * Bidirectional. The OLD format uses a switch+fall-through based on eventType
 * (fields written differently depending on event type), the eventType/color/
 * overlay use a VarInt (not a Byte), and there's a `darkenScreen` field that has been
 * REMOVED in version 1.26.30 - since the modern object no longer stores this
 * at all, we use a default of `false` when re-encoding for a legacy client
 * (the most common value used by vanilla).
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class BossEventPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(BossEventPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		CommonTypes::putActorUniqueId($out, $packet->bossActorUniqueId);
		VarInt::writeUnsignedInt($out, $packet->eventType);

		switch($packet->eventType){
			case BossEventPacket::TYPE_REGISTER_PLAYER:
			case BossEventPacket::TYPE_UNREGISTER_PLAYER:
			case BossEventPacket::TYPE_QUERY:
				CommonTypes::putActorUniqueId($out, $packet->playerActorUniqueId);
				break;
			case BossEventPacket::TYPE_SHOW:
				CommonTypes::putString($out, $packet->title);
				CommonTypes::putString($out, $packet->filteredTitle);
				LE::writeFloat($out, $packet->healthPercent);
				//intentional fall-through, same as version 1.26.0
				// no break
			case BossEventPacket::TYPE_PROPERTIES:
				LE::writeUnsignedShort($out, 0); //darkenScreen: no longer exists on the modern object, default to false
				// no break
			case BossEventPacket::TYPE_TEXTURE:
				VarInt::writeUnsignedInt($out, $packet->color);
				VarInt::writeUnsignedInt($out, $packet->overlay);
				break;
			case BossEventPacket::TYPE_HEALTH_PERCENT:
				LE::writeFloat($out, $packet->healthPercent);
				break;
			case BossEventPacket::TYPE_TITLE:
				CommonTypes::putString($out, $packet->title);
				CommonTypes::putString($out, $packet->filteredTitle);
				break;
			default:
				break;
		}

		return $out->getData();
	}

	public static function decodePayload(ByteBufferReader $in) : BossEventPacket{
		$packet = new BossEventPacket();
		$packet->bossActorUniqueId = CommonTypes::getActorUniqueId($in);
		$packet->eventType = VarInt::readUnsignedInt($in);

		switch($packet->eventType){
			case BossEventPacket::TYPE_REGISTER_PLAYER:
			case BossEventPacket::TYPE_UNREGISTER_PLAYER:
			case BossEventPacket::TYPE_QUERY:
				$packet->playerActorUniqueId = CommonTypes::getActorUniqueId($in);
				break;
			case BossEventPacket::TYPE_SHOW:
				$packet->title = CommonTypes::getString($in);
				$packet->filteredTitle = CommonTypes::getString($in);
				$packet->healthPercent = LE::readFloat($in);
				// no break
			case BossEventPacket::TYPE_PROPERTIES:
				$raw = LE::readUnsignedShort($in); //darkenScreen: read but discarded, no longer exists on the modern object
				if($raw !== 0 && $raw !== 1){
					throw new PacketDecodeException("Invalid darkenScreen value $raw");
				}
				// no break
			case BossEventPacket::TYPE_TEXTURE:
				$packet->color = VarInt::readUnsignedInt($in);
				$packet->overlay = VarInt::readUnsignedInt($in);
				break;
			case BossEventPacket::TYPE_HEALTH_PERCENT:
				$packet->healthPercent = LE::readFloat($in);
				break;
			case BossEventPacket::TYPE_TITLE:
				$packet->title = CommonTypes::getString($in);
				$packet->filteredTitle = CommonTypes::getString($in);
				break;
			default:
				break;
		}

		return $packet;
	}
}
