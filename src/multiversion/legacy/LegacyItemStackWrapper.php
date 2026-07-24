<?php

declare(strict_types=1);

/*
 * In 1.26.0-1.26.20, item stacks are encoded via CommonTypes::getItemStackWrapper()/
 * putItemStackWrapper() (id as a signed VarInt, where id=0 means "empty"
 * and immediately stops without reading any other field).
 *
 * In 1.26.30, these packets switched to CommonTypes::getNetworkItemStackDescriptor()/
 * putNetworkItemStackDescriptor() - a DIFFERENT format: id becomes a FIXED 2-byte LE short
 * (there's no shortcut for id=0), has an extra "variant" field, and
 * blockRuntimeId is read as an UNSIGNED VarInt (it used to be signed).
 *
 * IMPORTANT: the item's `id` field itself is also translated (see
 * LegacyItemTranslatorFactory), not just blockRuntimeId - the numeric item ID
 * for a given item name can differ between protocol versions too (though much
 * less drastically than block runtime IDs).
 *
 * This class exactly replicates the OLD format, used for every packet that
 * still calls getItemStackWrapper/putItemStackWrapper in version 1.26.0
 * (InventoryContentPacket, InventorySlotPacket, MobEquipmentPacket,
 * MobArmorEquipmentPacket, and every TransactionData in InventoryTransactionPacket).
 */

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

final class LegacyItemStackWrapper{

	private function __construct(){
		//NOOP
	}

	public static function read(ByteBufferReader $in, int $protocolVersion) : ItemStackWrapper{
		$legacyId = VarInt::readSignedInt($in);
		if($legacyId === 0){
			return new ItemStackWrapper(0, ItemStack::null());
		}

		$modernItemDictionary = TypeConverter::getInstance()->getItemTypeDictionary();
		$id = LegacyItemTranslatorFactory::translateLegacyIdToModern($legacyId, $modernItemDictionary, $protocolVersion);

		$count = LE::readUnsignedShort($in);
		$meta = VarInt::readUnsignedInt($in);

		$hasNetId = CommonTypes::getBool($in);
		$stackId = $hasNetId ? VarInt::readSignedInt($in) : 0;

		$legacyBlockRuntimeId = VarInt::readSignedInt($in);
		$modernBlockTranslator = TypeConverter::getInstance()->getBlockTranslator();
		$blockRuntimeId = LegacyBlockTranslatorFactory::translateLegacyRuntimeIdToModern($legacyBlockRuntimeId, $modernBlockTranslator, $protocolVersion);

		$rawExtraData = CommonTypes::getString($in);

		$itemStack = new ItemStack($id, $meta, $count, $blockRuntimeId, $rawExtraData);

		return new ItemStackWrapper($stackId, $itemStack);
	}

	public static function write(ByteBufferWriter $out, ItemStackWrapper $wrapper, int $protocolVersion) : void{
		$itemStack = $wrapper->getItemStack();

		if($itemStack->getId() === 0){
			VarInt::writeSignedInt($out, 0);
			return;
		}

		$modernItemDictionary = TypeConverter::getInstance()->getItemTypeDictionary();
		$legacyId = LegacyItemTranslatorFactory::translateModernIdToLegacy($itemStack->getId(), $modernItemDictionary, $protocolVersion);
		VarInt::writeSignedInt($out, $legacyId);

		LE::writeUnsignedShort($out, $itemStack->getCount());
		VarInt::writeUnsignedInt($out, $itemStack->getMeta());

		$hasNetId = $wrapper->getStackId() !== 0;
		CommonTypes::putBool($out, $hasNetId);
		if($hasNetId){
			VarInt::writeSignedInt($out, $wrapper->getStackId());
		}

		$modernBlockTranslator = TypeConverter::getInstance()->getBlockTranslator();
		$legacyBlockRuntimeId = LegacyBlockTranslatorFactory::translateModernRuntimeIdToLegacy($itemStack->getBlockRuntimeId(), $modernBlockTranslator, $protocolVersion);
		VarInt::writeSignedInt($out, $legacyBlockRuntimeId);
		CommonTypes::putString($out, $itemStack->getRawExtraData());
	}
}
