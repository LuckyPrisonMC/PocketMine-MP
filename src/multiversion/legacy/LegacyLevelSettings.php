<?php

declare(strict_types=1);

/*
 * Two new trailing fields in 1.26.30 (serverEditorConnectionPolicy,
 * allowAnonymousBlockDropsInEditorWorlds) are skipped, and spawnPosition
 * goes through LegacyBlockPosition instead of the vendor's current encoding.
 * Everything else here is unchanged between versions.
 */

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\EducationUriResource;
use pocketmine\network\mcpe\protocol\types\LevelSettings;

final class LegacyLevelSettings{

	private function __construct(){
		//NOOP
	}

	public static function write(ByteBufferWriter $out, LevelSettings $ls) : void{
		LE::writeUnsignedLong($out, $ls->seed);
		$ls->spawnSettings->write($out);
		VarInt::writeSignedInt($out, $ls->generator);
		VarInt::writeSignedInt($out, $ls->worldGamemode);
		CommonTypes::putBool($out, $ls->hardcore);
		VarInt::writeSignedInt($out, $ls->difficulty);
		LegacyBlockPosition::write($out, $ls->spawnPosition);
		CommonTypes::putBool($out, $ls->hasAchievementsDisabled);
		VarInt::writeSignedInt($out, $ls->editorWorldType);
		CommonTypes::putBool($out, $ls->createdInEditorMode);
		CommonTypes::putBool($out, $ls->exportedFromEditorMode);
		VarInt::writeSignedInt($out, $ls->time);
		VarInt::writeSignedInt($out, $ls->eduEditionOffer);
		CommonTypes::putBool($out, $ls->hasEduFeaturesEnabled);
		CommonTypes::putString($out, $ls->eduProductUUID);
		LE::writeFloat($out, $ls->rainLevel);
		LE::writeFloat($out, $ls->lightningLevel);
		CommonTypes::putBool($out, $ls->hasConfirmedPlatformLockedContent);
		CommonTypes::putBool($out, $ls->isMultiplayerGame);
		CommonTypes::putBool($out, $ls->hasLANBroadcast);
		VarInt::writeSignedInt($out, $ls->xboxLiveBroadcastMode);
		VarInt::writeSignedInt($out, $ls->platformBroadcastMode);
		CommonTypes::putBool($out, $ls->commandsEnabled);
		CommonTypes::putBool($out, $ls->isTexturePacksRequired);
		CommonTypes::putGameRules($out, $ls->gameRules, true);
		$ls->experiments->write($out);
		CommonTypes::putBool($out, $ls->hasBonusChestEnabled);
		CommonTypes::putBool($out, $ls->hasStartWithMapEnabled);
		VarInt::writeSignedInt($out, $ls->defaultPlayerPermission);
		LE::writeSignedInt($out, $ls->serverChunkTickRadius);
		CommonTypes::putBool($out, $ls->hasLockedBehaviorPack);
		CommonTypes::putBool($out, $ls->hasLockedResourcePack);
		CommonTypes::putBool($out, $ls->isFromLockedWorldTemplate);
		CommonTypes::putBool($out, $ls->useMsaGamertagsOnly);
		CommonTypes::putBool($out, $ls->isFromWorldTemplate);
		CommonTypes::putBool($out, $ls->isWorldTemplateOptionLocked);
		CommonTypes::putBool($out, $ls->onlySpawnV1Villagers);
		CommonTypes::putBool($out, $ls->disablePersona);
		CommonTypes::putBool($out, $ls->disableCustomSkins);
		CommonTypes::putBool($out, $ls->muteEmoteAnnouncements);
		CommonTypes::putString($out, $ls->vanillaVersion);
		LE::writeSignedInt($out, $ls->limitedWorldWidth);
		LE::writeSignedInt($out, $ls->limitedWorldLength);
		CommonTypes::putBool($out, $ls->isNewNether);
		($ls->eduSharedUriResource ?? new EducationUriResource("", ""))->write($out);
		CommonTypes::writeOptional($out, $ls->experimentalGameplayOverride, CommonTypes::putBool(...));
		Byte::writeUnsigned($out, $ls->chatRestrictionLevel);
		CommonTypes::putBool($out, $ls->disablePlayerInteractions);
		//stop here - the 2 trailing fields don't exist on 1.26.0-1.26.20 clients
	}
}
