<?php

declare(strict_types=1);

/*
 * Enchant.id used to be a Byte, now a VarInt. EnchantOption.cost used to
 * be a VarInt, now a Byte (the two swapped). List counts stayed VarInt
 * in both versions.
 */

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\Enchant;
use pocketmine\network\mcpe\protocol\types\EnchantOption;
use function count;

final class LegacyEnchantOption{

	private function __construct(){
		//NOOP
	}

	private static function writeEnchant(ByteBufferWriter $out, Enchant $enchant) : void{
		Byte::writeUnsigned($out, $enchant->getId());
		Byte::writeUnsigned($out, $enchant->getLevel());
	}

	/**
	 * @param Enchant[] $list
	 */
	private static function writeEnchantList(ByteBufferWriter $out, array $list) : void{
		VarInt::writeUnsignedInt($out, count($list));
		foreach($list as $item){
			self::writeEnchant($out, $item);
		}
	}

	public static function write(ByteBufferWriter $out, EnchantOption $option) : void{
		Byte::writeUnsigned($out, $option->getCost());

		LE::writeUnsignedInt($out, $option->getSlotFlags());
		self::writeEnchantList($out, $option->getEquipActivatedEnchantments());
		self::writeEnchantList($out, $option->getHeldActivatedEnchantments());
		self::writeEnchantList($out, $option->getSelfActivatedEnchantments());

		CommonTypes::putString($out, $option->getName());
		CommonTypes::writeRecipeNetId($out, $option->getOptionId());
	}
}
