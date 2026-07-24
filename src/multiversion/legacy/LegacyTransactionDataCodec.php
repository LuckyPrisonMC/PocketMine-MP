<?php

declare(strict_types=1);

/*
 * NormalTransactionData/MismatchTransactionData carry no extra data.
 * ReleaseItemTransactionData/UseItemOnEntityTransactionData/UseItemTransactionData
 * use an unsigned actionType (1.26.30 made it signed) and go through
 * LegacyItemStackWrapper. UseItemTransactionData additionally uses Byte
 * instead of VarInt for triggerType/face/clientInteractPrediction, needs
 * LegacyBlockPosition and block runtime ID translation, and has no
 * clientCooldownState (defaults to 0).
 */

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\PredictedResult;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TriggerType;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use function count;

final class LegacyTransactionDataCodec{

	private function __construct(){
		//NOOP
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */
	public static function decodeNormal(array $actions, ByteBufferReader $in, int $protocolVersion) : NormalTransactionData{
		return NormalTransactionData::new($actions);
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */
	public static function decodeMismatch(array $actions, ByteBufferReader $in, int $protocolVersion) : MismatchTransactionData{
		if(count($actions) > 0){
			throw new PacketDecodeException("Mismatch transaction type should not have any actions associated with it, but got " . count($actions));
		}
		return MismatchTransactionData::new();
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */
	public static function decodeReleaseItem(array $actions, ByteBufferReader $in, int $protocolVersion) : ReleaseItemTransactionData{
		$actionType = VarInt::readUnsignedInt($in);
		$hotbarSlot = VarInt::readSignedInt($in);
		$itemInHand = LegacyItemStackWrapper::read($in, $protocolVersion);
		$headPosition = CommonTypes::getVector3($in);
		return ReleaseItemTransactionData::new($actions, $actionType, $hotbarSlot, $itemInHand, $headPosition);
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */
	public static function decodeUseItemOnEntity(array $actions, ByteBufferReader $in, int $protocolVersion) : UseItemOnEntityTransactionData{
		$actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$actionType = VarInt::readUnsignedInt($in);
		$hotbarSlot = VarInt::readSignedInt($in);
		$itemInHand = LegacyItemStackWrapper::read($in, $protocolVersion);
		$playerPosition = CommonTypes::getVector3($in);
		$clickPosition = CommonTypes::getVector3($in);
		return UseItemOnEntityTransactionData::new($actions, $actorRuntimeId, $actionType, $hotbarSlot, $itemInHand, $playerPosition, $clickPosition);
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */
	public static function decodeUseItem(array $actions, ByteBufferReader $in, int $protocolVersion) : UseItemTransactionData{
		$actionType = VarInt::readUnsignedInt($in);
		$triggerType = TriggerType::fromPacket(VarInt::readUnsignedInt($in));
		$blockPosition = LegacyBlockPosition::read($in);
		$face = VarInt::readSignedInt($in);
		$hotbarSlot = VarInt::readSignedInt($in);
		$itemInHand = LegacyItemStackWrapper::read($in, $protocolVersion);
		$playerPosition = CommonTypes::getVector3($in);
		$clickPosition = CommonTypes::getVector3($in);

		$legacyBlockRuntimeId = VarInt::readUnsignedInt($in);
		$modernTranslator = TypeConverter::getInstance()->getBlockTranslator();
		$blockRuntimeId = LegacyBlockTranslatorFactory::translateLegacyRuntimeIdToModern($legacyBlockRuntimeId, $modernTranslator, $protocolVersion);

		$clientInteractPrediction = PredictedResult::fromPacket(VarInt::readUnsignedInt($in));
		//clientCooldownState doesn't exist yet in protocol 1.26.0-1.26.20, use a default
		return UseItemTransactionData::new($actions, $actionType, $triggerType, $blockPosition, $face, $hotbarSlot, $itemInHand, $playerPosition, $clickPosition, $blockRuntimeId, $clientInteractPrediction, 0);
	}

	public static function encodeReleaseItem(ByteBufferWriter $out, ReleaseItemTransactionData $data, int $protocolVersion) : void{
		VarInt::writeUnsignedInt($out, $data->getActionType());
		VarInt::writeSignedInt($out, $data->getHotbarSlot());
		LegacyItemStackWrapper::write($out, $data->getItemInHand(), $protocolVersion);
		CommonTypes::putVector3($out, $data->getHeadPosition());
	}

	public static function encodeUseItemOnEntity(ByteBufferWriter $out, UseItemOnEntityTransactionData $data, int $protocolVersion) : void{
		CommonTypes::putActorRuntimeId($out, $data->getActorRuntimeId());
		VarInt::writeUnsignedInt($out, $data->getActionType());
		VarInt::writeSignedInt($out, $data->getHotbarSlot());
		LegacyItemStackWrapper::write($out, $data->getItemInHand(), $protocolVersion);
		CommonTypes::putVector3($out, $data->getPlayerPosition());
		CommonTypes::putVector3($out, $data->getClickPosition());
	}

	public static function encodeUseItem(ByteBufferWriter $out, UseItemTransactionData $data, int $protocolVersion) : void{
		VarInt::writeUnsignedInt($out, $data->getActionType());
		VarInt::writeUnsignedInt($out, $data->getTriggerType()->value);
		LegacyBlockPosition::write($out, $data->getBlockPosition());
		VarInt::writeSignedInt($out, $data->getFace());
		VarInt::writeSignedInt($out, $data->getHotbarSlot());
		LegacyItemStackWrapper::write($out, $data->getItemInHand(), $protocolVersion);
		CommonTypes::putVector3($out, $data->getPlayerPosition());
		CommonTypes::putVector3($out, $data->getClickPosition());

		$modernTranslator = TypeConverter::getInstance()->getBlockTranslator();
		$legacyBlockRuntimeId = LegacyBlockTranslatorFactory::translateModernRuntimeIdToLegacy($data->getBlockRuntimeId(), $modernTranslator, $protocolVersion);
		VarInt::writeUnsignedInt($out, $legacyBlockRuntimeId);

		VarInt::writeUnsignedInt($out, $data->getClientInteractPrediction()->value);
		//clientCooldownState is intentionally not written (doesn't exist in 1.26.0-1.26.20)
	}
}
