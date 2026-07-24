<?php

declare(strict_types=1);

/*
 * Main differences from 1.26.30:
 * - requestChangedSlots is written/read based on requestId !== 0, with no
 *   wrapping optional-bool
 * - no 2 dummy optional bytes before transactionType & trData
 * - sub-transaction data goes through LegacyTransactionDataCodec
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyNetworkInventoryAction;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\multiversion\legacy\LegacyTransactionDataCodec;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use function count;

final class InventoryTransactionPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(InventoryTransactionPacket $packet, int $protocolVersion) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		CommonTypes::writeLegacyItemStackRequestId($out, $packet->requestId);
		if($packet->requestId !== 0){
			$changedSlots = $packet->requestChangedSlots ?? [];
			VarInt::writeUnsignedInt($out, count($changedSlots));
			foreach($changedSlots as $changedSlot){
				$changedSlot->write($out);
			}
		}

		VarInt::writeUnsignedInt($out, $packet->trData->getTypeId());
		self::encodeTrData($out, $packet->trData, $protocolVersion);

		return $out->getData();
	}

	private static function encodeTrData(ByteBufferWriter $out, TransactionData $trData, int $protocolVersion) : void{
		VarInt::writeUnsignedInt($out, count($trData->getActions()));
		foreach($trData->getActions() as $action){
			LegacyNetworkInventoryAction::write($out, $action, $protocolVersion);
		}

		match(true){
			$trData instanceof NormalTransactionData, $trData instanceof MismatchTransactionData => null,
			$trData instanceof ReleaseItemTransactionData => LegacyTransactionDataCodec::encodeReleaseItem($out, $trData, $protocolVersion),
			$trData instanceof UseItemOnEntityTransactionData => LegacyTransactionDataCodec::encodeUseItemOnEntity($out, $trData, $protocolVersion),
			$trData instanceof UseItemTransactionData => LegacyTransactionDataCodec::encodeUseItem($out, $trData, $protocolVersion),
			default => throw new \InvalidArgumentException("Unsupported transaction data type " . $trData::class),
		};
	}

	public static function decodePayload(ByteBufferReader $in, int $protocolVersion) : InventoryTransactionPacket{
		$packet = new InventoryTransactionPacket();

		$packet->requestId = CommonTypes::readLegacyItemStackRequestId($in);
		$packet->requestChangedSlots = [];
		if($packet->requestId !== 0){
			for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
				$packet->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
			}
		}

		$transactionType = VarInt::readUnsignedInt($in);

		$actionCount = VarInt::readUnsignedInt($in);
		$actions = [];
		for($i = 0; $i < $actionCount; ++$i){
			$actions[] = LegacyNetworkInventoryAction::read($in, $protocolVersion);
		}

		$packet->trData = match($transactionType){
			NormalTransactionData::ID => LegacyTransactionDataCodec::decodeNormal($actions, $in, $protocolVersion),
			MismatchTransactionData::ID => LegacyTransactionDataCodec::decodeMismatch($actions, $in, $protocolVersion),
			UseItemTransactionData::ID => LegacyTransactionDataCodec::decodeUseItem($actions, $in, $protocolVersion),
			UseItemOnEntityTransactionData::ID => LegacyTransactionDataCodec::decodeUseItemOnEntity($actions, $in, $protocolVersion),
			ReleaseItemTransactionData::ID => LegacyTransactionDataCodec::decodeReleaseItem($actions, $in, $protocolVersion),
			default => throw new PacketDecodeException("Unknown transaction type $transactionType"),
		};

		return $packet;
	}
}
