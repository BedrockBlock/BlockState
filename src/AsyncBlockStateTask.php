<?php

declare(strict_types = 1);

namespace bedrockblock\BlockState;

use pocketmine\nbt\tag\Tag;
use pocketmine\scheduler\AsyncTask;
use pocketmine\data\bedrock\block\BlockStateData;

use Closure;

use function file_put_contents;
use function yaml_emit;
use function igbinary_serialize;
use function igbinary_unserialize;

final class AsyncBlockStateTask extends AsyncTask{

	private const FILENAME = 'BlockStates.yml';

	private string $states;

	private string $registerNames;

	/**
	 * @param BlockStateData[] $states
	 * @phpstan-param list<BlockStateDictionaryEntry> $states
	 * @param string[] $registerNames
	 */
	public function __construct(private string $path, array $states, array $registerNames){
		$this->states = igbinary_serialize($states);
		$this->registerNames = igbinary_serialize($registerNames);
	}

	public function onRun() : void{
		$states = igbinary_unserialize($this->states);
		$registerNames = igbinary_unserialize($this->registerNames);

		/**
		 * @param Tag[] $nbts
		 * @phpstan-param array<string, Tag> $nbts
		 */
		$nbtsToarray = (static function(array $nbts) : array{
			$arr = [];
			foreach($nbts as $key => $nbt){
				$arr[$key] = Closure::bind(static fn(Tag $nbt) : array => [
					'type' => $nbt->getTypeName(),
					'value' => $nbt->getValue()
				], null, Tag::class)($nbt);
			}
			return $arr;
		});

		$stateData = [];
		$filterStateData = [];
		foreach($states as $state){
			$s = $state->getStateData();
			$name = $s->getName();
			$stateData[$name][] = $arr = [
				'meta' => $state->getMeta(),
				'state' => $nbtsToarray($s->getStates())
			];
			if(!in_array($name, $registerNames, true)){
				$filterStateData[$name][] = $arr;
			}
		}

		file_put_contents($this->path . self::FILENAME, yaml_emit($stateData));
		file_put_contents($this->path . 'Unregister' . self::FILENAME, yaml_emit($filterStateData));
	}

}
