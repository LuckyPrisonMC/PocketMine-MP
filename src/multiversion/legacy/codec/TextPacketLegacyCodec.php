<?php

declare(strict_types=1);

/*
 * Only needed for protocol 898 (1.21.130). That version requires a set of
 * literal "dummy strings" per category right after the category byte - these
 * were removed entirely by 1.26.0. Everything else about this packet is
 * unchanged.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\TextPacket;
use function count;

final class TextPacketLegacyCodec{

	private const CATEGORY_MESSAGE_ONLY = 0;
	private const CATEGORY_AUTHORED_MESSAGE = 1;
	private const CATEGORY_MESSAGE_WITH_PARAMETERS = 2;

	private const CATEGORY_DUMMY_STRINGS = [
		self::CATEGORY_MESSAGE_ONLY => [
			'raw',
			'tip',
			'systemMessage',
			'textObjectWhisper',
			'textObjectAnnouncement',
			'textObject'
		],
		self::CATEGORY_AUTHORED_MESSAGE => [
			'chat',
			'whisper',
			'announcement'
		],
		self::CATEGORY_MESSAGE_WITH_PARAMETERS => [
			'translate',
			'popup',
			'jukeboxPopup',
		]
	];

	private function __construct(){
		//NOOP
	}

	private static function categoryForType(int $type) : int{
		return match($type){
			TextPacket::TYPE_RAW,
			TextPacket::TYPE_TIP,
			TextPacket::TYPE_SYSTEM,
			TextPacket::TYPE_JSON_WHISPER,
			TextPacket::TYPE_JSON_ANNOUNCEMENT,
			TextPacket::TYPE_JSON => self::CATEGORY_MESSAGE_ONLY,

			TextPacket::TYPE_CHAT,
			TextPacket::TYPE_WHISPER,
			TextPacket::TYPE_ANNOUNCEMENT => self::CATEGORY_AUTHORED_MESSAGE,

			TextPacket::TYPE_TRANSLATION,
			TextPacket::TYPE_POPUP,
			TextPacket::TYPE_JUKEBOX_POPUP => self::CATEGORY_MESSAGE_WITH_PARAMETERS,

			default => throw new \InvalidArgumentException("Invalid TextPacket type: $type"),
		};
	}

	public static function encode(TextPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		CommonTypes::putBool($out, $packet->needsTranslation);

		$category = self::categoryForType($packet->type);
		Byte::writeUnsigned($out, $category);
		foreach(self::CATEGORY_DUMMY_STRINGS[$category] as $dummyString){
			CommonTypes::putString($out, $dummyString);
		}

		Byte::writeUnsigned($out, $packet->type);
		switch($packet->type){
			case TextPacket::TYPE_CHAT:
			case TextPacket::TYPE_WHISPER:
			case TextPacket::TYPE_ANNOUNCEMENT:
				CommonTypes::putString($out, $packet->sourceName);
				// no break
			case TextPacket::TYPE_RAW:
			case TextPacket::TYPE_TIP:
			case TextPacket::TYPE_SYSTEM:
			case TextPacket::TYPE_JSON_WHISPER:
			case TextPacket::TYPE_JSON:
			case TextPacket::TYPE_JSON_ANNOUNCEMENT:
				CommonTypes::putString($out, $packet->message);
				break;

			case TextPacket::TYPE_TRANSLATION:
			case TextPacket::TYPE_POPUP:
			case TextPacket::TYPE_JUKEBOX_POPUP:
				CommonTypes::putString($out, $packet->message);
				VarInt::writeUnsignedInt($out, count($packet->parameters));
				foreach($packet->parameters as $p){
					CommonTypes::putString($out, $p);
				}
				break;
		}

		CommonTypes::putString($out, $packet->xboxUserId);
		CommonTypes::putString($out, $packet->platformChatId);
		CommonTypes::writeOptional($out, $packet->filteredMessage, CommonTypes::putString(...));

		return $out->getData();
	}

	public static function decodePayload(ByteBufferReader $in) : TextPacket{
		$packet = new TextPacket();
		$packet->needsTranslation = CommonTypes::getBool($in);

		$category = Byte::readUnsigned($in);
		$expectedDummyStrings = self::CATEGORY_DUMMY_STRINGS[$category] ?? throw new PacketDecodeException("Unknown category ID $category");
		foreach($expectedDummyStrings as $k => $expectedDummyString){
			$actual = CommonTypes::getString($in);
			if($expectedDummyString !== $actual){
				throw new PacketDecodeException("Dummy string mismatch for category $category at position $k: expected $expectedDummyString, got $actual");
			}
		}

		$packet->type = Byte::readUnsigned($in);
		switch($packet->type){
			case TextPacket::TYPE_CHAT:
			case TextPacket::TYPE_WHISPER:
			case TextPacket::TYPE_ANNOUNCEMENT:
				if($category !== self::CATEGORY_AUTHORED_MESSAGE){
					throw new PacketDecodeException("Decoded TextPacket has invalid structure: type {$packet->type} requires category CATEGORY_AUTHORED_MESSAGE");
				}
				$packet->sourceName = CommonTypes::getString($in);
				$packet->message = CommonTypes::getString($in);
				break;
			case TextPacket::TYPE_RAW:
			case TextPacket::TYPE_TIP:
			case TextPacket::TYPE_SYSTEM:
			case TextPacket::TYPE_JSON_WHISPER:
			case TextPacket::TYPE_JSON:
			case TextPacket::TYPE_JSON_ANNOUNCEMENT:
				if($category !== self::CATEGORY_MESSAGE_ONLY){
					throw new PacketDecodeException("Decoded TextPacket has invalid structure: type {$packet->type} requires category CATEGORY_MESSAGE_ONLY");
				}
				$packet->message = CommonTypes::getString($in);
				break;
			case TextPacket::TYPE_TRANSLATION:
			case TextPacket::TYPE_POPUP:
			case TextPacket::TYPE_JUKEBOX_POPUP:
				if($category !== self::CATEGORY_MESSAGE_WITH_PARAMETERS){
					throw new PacketDecodeException("Decoded TextPacket has invalid structure: type {$packet->type} requires category CATEGORY_MESSAGE_WITH_PARAMETERS");
				}
				$packet->message = CommonTypes::getString($in);
				$count = VarInt::readUnsignedInt($in);
				for($i = 0; $i < $count; ++$i){
					$packet->parameters[] = CommonTypes::getString($in);
				}
				break;
		}

		$packet->xboxUserId = CommonTypes::getString($in);
		$packet->platformChatId = CommonTypes::getString($in);
		$packet->filteredMessage = CommonTypes::readOptional($in, CommonTypes::getString(...));

		return $packet;
	}
}
