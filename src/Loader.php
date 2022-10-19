<?php

declare(strict_types=1);

namespace bedrockblock\BlockState;

use pocketmine\data\bedrock\block\convert\BlockStateToObjectDeserializer;
use pocketmine\network\mcpe\convert\{
	BlockStateDictionary,
	RuntimeBlockMapping
};
use pocketmine\plugin\PluginBase;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

use Closure;

use function array_keys;

final class Loader extends PluginBase{

	protected function onEnable() : void{
		$states = [];
		$registerNames = [];
		Closure::bind(
			static function(BlockStateDictionary $dictionary) use(&$states): void{
				$states = $dictionary->states;
			},
			null,
			BlockStateDictionary::class
		)(RuntimeBlockMapping::getInstance()->getBlockStateDictionary());
		Closure::bind(
			static function(BlockStateToObjectDeserializer $deserialzer) use(&$registerNames): void{
				$registerNames = array_keys($deserialzer->deserializeFuncs);
			},
			null,
			BlockStateToObjectDeserializer::class
		)(GlobalBlockStateHandlers::getDeserializer());
		$this->getServer()->getAsyncPool()->submitTask(new AsyncBlockStateTask(
			$this->getDataFolder(),
			$states,
			$registerNames
		));
	}

}
