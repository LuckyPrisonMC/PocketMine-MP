<?php

declare(strict_types=1);

// windowId/sourceFlags were plain (non-nullable) ints before 1.26.30 split this into readAuthInput/readTransaction.

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;

final class LegacyNetworkInventoryAction{

	private function __construct(){
		//NOOP
	}

	public static function read(ByteBufferReader $in, int $protocolVersion) : NetworkInventoryAction{
		$action = new NetworkInventoryAction();
		$action->sourceType = VarInt::readUnsignedInt($in);

		switch($action->sourceType){
			case NetworkInventoryAction::SOURCE_CONTAINER:
				$action->windowId = VarInt::readSignedInt($in);
				break;
			case NetworkInventoryAction::SOURCE_WORLD:
				$action->sourceFlags = VarInt::readUnsignedInt($in);
				break;
			case NetworkInventoryAction::SOURCE_CREATIVE:
				break;
			case NetworkInventoryAction::SOURCE_TODO:
				$action->windowId = VarInt::readSignedInt($in);
				break;
			default:
				throw new PacketDecodeException("Unknown inventory action source type $action->sourceType");
		}

		$action->inventorySlot = VarInt::readUnsignedInt($in);
		$action->oldItem = LegacyItemStackWrapper::read($in, $protocolVersion);
		$action->newItem = LegacyItemStackWrapper::read($in, $protocolVersion);

		return $action;
	}

	public static function write(ByteBufferWriter $out, NetworkInventoryAction $action, int $protocolVersion) : void{
		VarInt::writeUnsignedInt($out, $action->sourceType);

		switch($action->sourceType){
			case NetworkInventoryAction::SOURCE_CONTAINER:
				VarInt::writeSignedInt($out, $action->windowId ?? 0);
				break;
			case NetworkInventoryAction::SOURCE_WORLD:
				VarInt::writeUnsignedInt($out, $action->sourceFlags ?? 0);
				break;
			case NetworkInventoryAction::SOURCE_CREATIVE:
				break;
			case NetworkInventoryAction::SOURCE_TODO:
				VarInt::writeSignedInt($out, $action->windowId ?? 0);
				break;
			default:
				throw new \InvalidArgumentException("Unknown inventory action source type $action->sourceType");
		}

		VarInt::writeUnsignedInt($out, $action->inventorySlot);
		LegacyItemStackWrapper::write($out, $action->oldItem, $protocolVersion);
		LegacyItemStackWrapper::write($out, $action->newItem, $protocolVersion);
	}
}
