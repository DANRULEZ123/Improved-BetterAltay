<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use AssertionError;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;

final class AbilitiesLayer{
	
	public const LAYER_CACHE = 0;
	public const LAYER_BASE = 1;
	public const LAYER_SPECTATOR = 2;
	public const LAYER_COMMANDS = 3;
	public const LAYER_EDITOR = 4;
	public const LAYER_LOADING_SCREEN = 5;

	public const ABILITY_BUILD = 0;
	public const ABILITY_MINE = 1;
	public const ABILITY_DOORS_AND_SWITCHES = 2; //disabling this also disables dropping items (???)
	public const ABILITY_OPEN_CONTAINERS = 3;
	public const ABILITY_ATTACK_PLAYERS = 4;
	public const ABILITY_ATTACK_MOBS = 5;
	public const ABILITY_OPERATOR = 6;
	public const ABILITY_TELEPORT = 7;
	public const ABILITY_INVULNERABLE = 8;
	public const ABILITY_FLYING = 9;
	public const ABILITY_ALLOW_FLIGHT = 10;
	public const ABILITY_INFINITE_RESOURCES = 11; //in vanilla they call this "instabuild", which is a bad name
	public const ABILITY_LIGHTNING = 12; //???
	private const ABILITY_FLY_SPEED = 13;
	private const ABILITY_WALK_SPEED = 14;
	public const ABILITY_MUTED = 15;
	public const ABILITY_WORLD_BUILDER = 16;
	public const ABILITY_NO_CLIP = 17;
	public const ABILITY_PRIVILEGED_BUILDER = 18;
	public const ABILITY_VERTICAL_FLY_SPEED = 19;
	
	public const NUMBER_OF_ABILITIES = 20;

	/**
	 * @param bool[] $boolAbilities
	 * @phpstan-param array<self::ABILITY_*, bool> $boolAbilities
	 */
	public function __construct(
		private int $layerId,
		private array $boolAbilities,
		private ?float $flySpeed,
		private ?float $verticalFlySpeed,
		private ?float $walkSpeed
	){}

	public function getLayerId() : int{ return $this->layerId; }

	/**
	 * Returns a list of abilities set/overridden by this layer. If the ability value is not set, the index is omitted.
	 * @return bool[]
	 * @phpstan-return array<self::ABILITY_*, bool>
	 */
	public function getBoolAbilities() : array{ return $this->boolAbilities; }

	public function getFlySpeed() : ?float{ return $this->flySpeed; }

	public function getVerticalFlySpeed() : ?float{ return $this->verticalFlySpeed; }

	public function getWalkSpeed() : ?float{ return $this->walkSpeed; }

	public static function decode(UpdateAbilitiesPacket $in) : self{
		$layerId = $in->getLShort();
		$setAbilities = $in->getLInt();
		$setAbilityValues = $in->getLInt();
		$flySpeed = $in->getLFloat();
		$verticalFlySpeed = $in->getLFloat();
		$walkSpeed = $in->getLFloat();

		$boolAbilities = [];
		for($i = 0; $i < self::NUMBER_OF_ABILITIES; $i++){
			if($i === self::ABILITY_FLY_SPEED || $i === self::ABILITY_WALK_SPEED){
				continue;
			}
			if(($setAbilities & (1 << $i)) !== 0){
				$boolAbilities[$i] = ($setAbilityValues & (1 << $i)) !== 0;
			}
		}
		if(($setAbilities & (1 << self::ABILITY_FLY_SPEED)) === 0){
			if($flySpeed !== 0.0){
				throw new AssertionError("Fly speed should be zero if the layer does not set it");
			}
			$flySpeed = null;
		}
		if(($setAbilities & (1 << self::ABILITY_VERTICAL_FLY_SPEED)) === 0){
			if($verticalFlySpeed !== 0.0){
				throw new AssertionError("Vertical fly speed should be zero if the layer does not set it");
			}
			$verticalFlySpeed = null;
		}
		if(($setAbilities & (1 << self::ABILITY_WALK_SPEED)) === 0){
			if($walkSpeed !== 0.0){
				throw new AssertionError("Walk speed should be zero if the layer does not set it");
			}
			$walkSpeed = null;
		}

		return new self($layerId, $boolAbilities, $flySpeed, $verticalFlySpeed, $walkSpeed);
	}

	public function encode(UpdateAbilitiesPacket $out) : void{
		$out->putLShort($this->layerId);

		$setAbilities = 0;
		$setAbilityValues = 0;
		foreach($this->boolAbilities as $ability => $value){
			$setAbilities |= (1 << $ability);
			$setAbilityValues |= ($value ? 1 << $ability : 0);
		}
		if($this->flySpeed !== null){
			$setAbilities |= (1 << self::ABILITY_FLY_SPEED);
		}
		if($this->verticalFlySpeed !== null){
			$setAbilities |= (1 << self::ABILITY_VERTICAL_FLY_SPEED);
		}
		if($this->walkSpeed !== null){
			$setAbilities |= (1 << self::ABILITY_WALK_SPEED);
		}

		$out->putLInt($setAbilities);
		$out->putLInt($setAbilityValues);
		$out->putLFloat($this->flySpeed ?? 0);
		$out->putLFloat($this->verticalFlySpeed ?? 0);
		$out->putLFloat($this->walkSpeed ?? 0);
	}
}