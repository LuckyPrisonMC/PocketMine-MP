<?php

declare(strict_types=1);

/*
 * Port of ClientMovementPredictionSyncPacket from bedrock-protocol 55.0.0
 * (1.26.0). Serverbound-only. Version 1.26.30 adds 3 new float fields
 * (frictionModifier, bounciness, airDragModifier) in the middle of the struct.
 *
 * Note: PM itself does NOT process the contents of this packet at all (there's no
 * specific handler for it in PM's source), so the exact values of those 3 new fields don't
 * matters here - we fill it with a reasonable vanilla default value so it's still
 * valid in case some other code reads it later.
 *
 * BitSet flags: bit length changed from 127 to 128 (EntityMetadataFlags::
 * NUMBER_OF_FLAGS increased by 1), BUT both round up to 16 bytes anyway
 * (ceil(127/8) = ceil(128/8) = 16), so it's safe to read using the vendor's own
 * built-in BitSet::read() regardless of the length given - the byte count consumed is identical.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ClientMovementPredictionSyncPacket;
use pocketmine\network\mcpe\protocol\serializer\BitSet;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class ClientMovementPredictionSyncPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : ClientMovementPredictionSyncPacket{
		$flags = BitSet::read($in, ClientMovementPredictionSyncPacket::FLAG_LENGTH);
		$scale = LE::readFloat($in);
		$width = LE::readFloat($in);
		$height = LE::readFloat($in);
		$movementSpeed = LE::readFloat($in);
		$underwaterMovementSpeed = LE::readFloat($in);
		$lavaMovementSpeed = LE::readFloat($in);
		$jumpStrength = LE::readFloat($in);
		$health = LE::readFloat($in);
		$hunger = LE::readFloat($in);
		$actorUniqueId = CommonTypes::getActorUniqueId($in);
		$actorFlyingState = CommonTypes::getBool($in);

		return ClientMovementPredictionSyncPacket::create(
			$flags,
			$scale,
			$width,
			$height,
			$movementSpeed,
			$underwaterMovementSpeed,
			$lavaMovementSpeed,
			$jumpStrength,
			$health,
			$hunger,
			0.6, //frictionModifier: vanilla default, not used by PM
			0.0, //bounciness: default, not used by PM
			0.0, //airDragModifier: default, not used by PM
			$actorUniqueId,
			$actorFlyingState,
		);
	}
}
