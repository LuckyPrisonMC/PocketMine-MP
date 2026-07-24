<?php

declare(strict_types=1);

/*
 * Clientbound-only. In 1.26.30 $containerName/$storage became nullable
 * (wrapped in an optional bool) - a legacy client doesn't understand that,
 * so null falls back to a safe default (containerId 0, empty item).
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyItemStackWrapper;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

final class InventorySlotPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(InventorySlotPacket $packet, int $protocolVersion) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		VarInt::writeUnsignedInt($out, $packet->windowId);
		VarInt::writeUnsignedInt($out, $packet->inventorySlot);

		($packet->containerName ?? new FullContainerName(0))->write($out);
		LegacyItemStackWrapper::write($out, $packet->storage ?? new ItemStackWrapper(0, ItemStack::null()), $protocolVersion);
		LegacyItemStackWrapper::write($out, $packet->item, $protocolVersion);

		return $out->getData();
	}
}
