<?php

declare(strict_types=1);

/*
 * Central dispatcher for translating packets between the legacy protocols
 * (1.21.130/1.26.0/1.26.10/1.26.20) and this server's protocol (1.26.30).
 *
 * Outgoing: the packet is already encoded in the modern format; for legacy
 * sessions we swap that buffer for one produced by the matching legacy codec.
 *
 * Incoming: a legacy client sends bytes in the old format. Rather than
 * touching PocketMine's normal decode flow, we decode using the legacy codec
 * to get a populated packet object, then re-encode it with the vendor's own
 * (modern) encode() so the rest of the pipeline can process it unchanged.
 *
 * Protocol 898 (1.21.130) needs a few codecs that 924/944/975 don't
 * (TextPacket, BookEditPacket, and protocol-specific LevelSettings/
 * StartGamePacket/sound map variants) since it sits on the other side of the
 * 1.21.x -> 1.26.x version scheme change and has its own set of differences
 * from this server's protocol.
 *
 * Known gap: CommandParameterTypes::CODEBUILDERARGS shifted value between
 * these protocols. Only affects commands using that parameter type, an
 * Education Edition feature unlikely to be used by regular plugins.
 */

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\codec\AddVolumeEntityPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\ActorEventPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\AnvilDamagePacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\BlockActorDataPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\BlockEventPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\BookEditPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\BossEventPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\ClientCacheBlobStatusPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\ClientMovementPredictionSyncPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\CommandBlockUpdatePacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\ContainerOpenPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\InventoryContentPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\InventorySlotPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\InventoryTransactionPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\ItemRegistryPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\LecternUpdatePacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\LevelSoundEventPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\MobArmorEquipmentPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\MobEquipmentPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\OpenSignPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\PlaySoundPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\PlayerActionPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\PlayerEnchantOptionsPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\SetSpawnPositionPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\StartGamePacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\StructureBlockUpdatePacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\StructureTemplateDataRequestPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\SubChunkRequestPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\TextPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\UpdateBlockPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\UpdateClientOptionsPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\UpdateSubChunkBlocksPacketLegacyCodec;
use pocketmine\multiversion\MultiVersionInfo;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AnvilDamagePacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\BookEditPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\ClientCacheBlobStatusPacket;
use pocketmine\network\mcpe\protocol\ClientMovementPredictionSyncPacket;
use pocketmine\network\mcpe\protocol\CommandBlockUpdatePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemRegistryPacket;
use pocketmine\network\mcpe\protocol\LecternUpdatePacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\OpenSignPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerEnchantOptionsPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\StructureBlockUpdatePacket;
use pocketmine\network\mcpe\protocol\StructureTemplateDataRequestPacket;
use pocketmine\network\mcpe\protocol\SubChunkRequestPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\UpdateClientOptionsPacket;
use pocketmine\network\mcpe\protocol\UpdateSubChunkBlocksPacket;
use function in_array;
use function strlen;

final class LegacyPacketRegistry{

	private function __construct(){
		//NOOP
	}

	/**
	 * List of packet IDs that have a legacy codec for the INCOMING direction (serverbound).
	 * Kept separate from the translate logic so it can be checked cheaply (just peeking the pid)
	 * before actually decoding anything.
	 *
	 * @var int[]
	 */
	private const INCOMING_TRANSLATABLE_PIDS = [
		ProtocolInfo::PLAYER_ACTION_PACKET,
		ProtocolInfo::BLOCK_ACTOR_DATA_PACKET,
		ProtocolInfo::ANVIL_DAMAGE_PACKET,
		ProtocolInfo::COMMAND_BLOCK_UPDATE_PACKET,
		ProtocolInfo::LECTERN_UPDATE_PACKET,
		ProtocolInfo::STRUCTURE_BLOCK_UPDATE_PACKET,
		ProtocolInfo::STRUCTURE_TEMPLATE_DATA_REQUEST_PACKET,
		ProtocolInfo::INVENTORY_TRANSACTION_PACKET,
		ProtocolInfo::MOB_EQUIPMENT_PACKET,
		ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET,
		ProtocolInfo::ACTOR_EVENT_PACKET,
		ProtocolInfo::BOSS_EVENT_PACKET,
		ProtocolInfo::SUB_CHUNK_REQUEST_PACKET,
		ProtocolInfo::CLIENT_MOVEMENT_PREDICTION_SYNC_PACKET,
		ProtocolInfo::CLIENT_CACHE_BLOB_STATUS_PACKET,
		ProtocolInfo::UPDATE_CLIENT_OPTIONS_PACKET,
		ProtocolInfo::LEVEL_SOUND_EVENT_PACKET,
		ProtocolInfo::TEXT_PACKET,
		ProtocolInfo::BOOK_EDIT_PACKET,
	];

	public static function isLegacySession(int $protocolVersion) : bool{
		return $protocolVersion !== ProtocolInfo::CURRENT_PROTOCOL && MultiVersionInfo::isProtocolSupported($protocolVersion);
	}

	/**
	 * @internal called from NetworkSession when sending a packet to the client.
	 */
	public static function translateOutgoing(int $protocolVersion, ClientboundPacket $packet, string $modernEncodedBuffer) : string{
		if(!self::isLegacySession($protocolVersion)){
			return $modernEncodedBuffer;
		}

		return match(true){
			$packet instanceof StartGamePacket => StartGamePacketLegacyCodec::encode($packet, $protocolVersion),
			$packet instanceof PlayerActionPacket => PlayerActionPacketLegacyCodec::encode($packet),
			$packet instanceof UpdateBlockPacket => UpdateBlockPacketLegacyCodec::encode($packet, $protocolVersion),
			$packet instanceof AddVolumeEntityPacket => AddVolumeEntityPacketLegacyCodec::encode($packet),
			$packet instanceof BlockActorDataPacket => BlockActorDataPacketLegacyCodec::encode($packet),
			$packet instanceof BlockEventPacket => BlockEventPacketLegacyCodec::encode($packet),
			$packet instanceof ContainerOpenPacket => ContainerOpenPacketLegacyCodec::encode($packet),
			$packet instanceof OpenSignPacket => OpenSignPacketLegacyCodec::encode($packet),
			$packet instanceof SetSpawnPositionPacket => SetSpawnPositionPacketLegacyCodec::encode($packet),
			$packet instanceof UpdateSubChunkBlocksPacket => UpdateSubChunkBlocksPacketLegacyCodec::encode($packet, $protocolVersion),
			$packet instanceof InventoryContentPacket => InventoryContentPacketLegacyCodec::encode($packet, $protocolVersion),
			$packet instanceof InventorySlotPacket => InventorySlotPacketLegacyCodec::encode($packet, $protocolVersion),
			$packet instanceof InventoryTransactionPacket => InventoryTransactionPacketLegacyCodec::encode($packet, $protocolVersion),
			$packet instanceof MobEquipmentPacket => MobEquipmentPacketLegacyCodec::encode($packet, $protocolVersion),
			$packet instanceof MobArmorEquipmentPacket => MobArmorEquipmentPacketLegacyCodec::encode($packet, $protocolVersion),
			$packet instanceof ActorEventPacket => ActorEventPacketLegacyCodec::encode($packet),
			$packet instanceof BossEventPacket => BossEventPacketLegacyCodec::encode($packet),
			$packet instanceof PlaySoundPacket => PlaySoundPacketLegacyCodec::encode($packet),
			$packet instanceof PlayerEnchantOptionsPacket => PlayerEnchantOptionsPacketLegacyCodec::encode($packet),
			$packet instanceof LevelSoundEventPacket => LevelSoundEventPacketLegacyCodec::encode($packet, $protocolVersion),
			$packet instanceof ItemRegistryPacket => ItemRegistryPacketLegacyCodec::encode($packet, $protocolVersion),
			$packet instanceof TextPacket => $protocolVersion === 898 ? TextPacketLegacyCodec::encode($packet) : $modernEncodedBuffer,
			default => $modernEncodedBuffer,
		};
	}

	/**
	 * @internal called from NetworkSession when receiving a packet from the client.
	 * Returns a buffer that is ALREADY in modern format (ready for the built-in decode()),
	 * or the original buffer as-is if no translation is needed.
	 */
	public static function translateIncoming(int $protocolVersion, string $buffer) : string{
		if(!self::isLegacySession($protocolVersion) || strlen($buffer) === 0){
			return $buffer;
		}

		$pid = self::peekPacketId($buffer);
		if(!in_array($pid, self::INCOMING_TRANSLATABLE_PIDS, true)){
			return $buffer;
		}

		$reader = new ByteBufferReader($buffer);
		[$senderSubId, $recipientSubId] = self::readHeaderSubIds($reader);

		$packet = match($pid){
			ProtocolInfo::PLAYER_ACTION_PACKET => PlayerActionPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::BLOCK_ACTOR_DATA_PACKET => BlockActorDataPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::ANVIL_DAMAGE_PACKET => AnvilDamagePacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::COMMAND_BLOCK_UPDATE_PACKET => CommandBlockUpdatePacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::LECTERN_UPDATE_PACKET => LecternUpdatePacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::STRUCTURE_BLOCK_UPDATE_PACKET => StructureBlockUpdatePacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::STRUCTURE_TEMPLATE_DATA_REQUEST_PACKET => StructureTemplateDataRequestPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::INVENTORY_TRANSACTION_PACKET => InventoryTransactionPacketLegacyCodec::decodePayload($reader, $protocolVersion),
			ProtocolInfo::MOB_EQUIPMENT_PACKET => MobEquipmentPacketLegacyCodec::decodePayload($reader, $protocolVersion),
			ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET => MobArmorEquipmentPacketLegacyCodec::decodePayload($reader, $protocolVersion),
			ProtocolInfo::ACTOR_EVENT_PACKET => ActorEventPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::BOSS_EVENT_PACKET => BossEventPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::SUB_CHUNK_REQUEST_PACKET => SubChunkRequestPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::CLIENT_MOVEMENT_PREDICTION_SYNC_PACKET => ClientMovementPredictionSyncPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::CLIENT_CACHE_BLOB_STATUS_PACKET => ClientCacheBlobStatusPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::UPDATE_CLIENT_OPTIONS_PACKET => UpdateClientOptionsPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::LEVEL_SOUND_EVENT_PACKET => LevelSoundEventPacketLegacyCodec::decodePayload($reader, $protocolVersion),
			ProtocolInfo::TEXT_PACKET => $protocolVersion === 898 ? TextPacketLegacyCodec::decodePayload($reader) : null,
			ProtocolInfo::BOOK_EDIT_PACKET => $protocolVersion === 898 ? BookEditPacketLegacyCodec::decodePayload($reader) : null,
			default => null,
		};

		if($packet === null){
			return $buffer;
		}

		$packet->senderSubId = $senderSubId;
		$packet->recipientSubId = $recipientSubId;

		//Re-encode using the vendor's own encode() method (automatically modern/1.26.30 format),
		//so the rest of PocketMine's built-in decode() flow doesn't need to change at all.
		$out = new ByteBufferWriter();
		$packet->encode($out);
		return $out->getData();
	}

	private static function peekPacketId(string $buffer) : int{
		$reader = new ByteBufferReader($buffer);
		$header = VarInt::readUnsignedInt($reader);
		return $header & DataPacket::PID_MASK;
	}

	/**
	 * @return array{int, int} [senderSubId, recipientSubId]
	 */
	private static function readHeaderSubIds(ByteBufferReader $in) : array{
		$header = VarInt::readUnsignedInt($in);
		$senderSubId = ($header >> 10) & 0x03;
		$recipientSubId = ($header >> 12) & 0x03;
		return [$senderSubId, $recipientSubId];
	}
}
