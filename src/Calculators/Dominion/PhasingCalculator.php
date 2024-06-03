<?php

// We want strict types here.
declare(strict_types=1);

namespace OpenDominion\Calculators\Dominion;

use Log;

use Illuminate\Support\Collection;
use OpenDominion\Exceptions\GameException;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Unit;

class PhasingCalculator
{

    protected $phasingConfig;

    public function __construct()
    {
        $this->phasingConfig = config('phasing');
    }

    public function getPhasingResult(Dominion $phaser, Unit $sourceUnit, int $sourceUnitAmount, Unit $targetUnit)
    {
        // (Almost) No validations because this method is called after checks.
        // Affordability and unit number checks are already done.

        if(!$this->canPhaseSourceToTarget($phaser, $sourceUnit, $targetUnit))
        {
            xtLog("[{$phaser->id}] [PHASING] Phasing source unit {$sourceUnit->name} to target unit {$targetUnit->name} not allowed.", 'error');

            throw new GameException('Phasing source unit to target unit not allowed.');
        }
    }

    public function getPhasingCost(Dominion $phaser, Unit $sourceUnit, int $sourceUnitAmount, Unit $targetUnit): array
    {

        $resourceKey = $this->phasingConfig[$phaser->race->key][$sourceUnit->key][$targetUnit->key]['resource'] ?? null;
        $amountPerUnit = $this->phasingConfig[$phaser->race->key][$sourceUnit->key][$targetUnit->key]['resource_amount'] ?? null;

        if(!$resourceKey)
        {
            xtLog("[{$phaser->id}] [PHASING] Phasing cost resource not found for source unit {$sourceUnit->name} to target unit {$targetUnit->name}.", 'error');

            throw new GameException('Phasing cost resource not found.');
        }

        if($amountPerUnit === null)
        {
            xtLog("[{$phaser->id}] [PHASING] Phasing cost amount not found for source unit {$sourceUnit->name} to target unit {$targetUnit->name}.", 'error');

            throw new GameException('Phasing cost amount not found.');
        }

        $cost = ceilInt($sourceUnitAmount * $amountPerUnit);
        
        return [$resourceKey => $cost];
    }

    public function canPhaserAffordingPhasing(Dominion $phaser, Unit $sourceUnit, int $sourceUnitAmount, Unit $targetUnit): bool
    {
        $cost = $this->getPhasingCost($phaser, $sourceUnit, $sourceUnitAmount, $targetUnit);

        return $phaser->{'resource_' . key($cost)} >= current($cost);
    }

    public function canPhaseSourceToTarget(Dominion $phaser, Unit $sourceUnit, Unit $targetUnit): bool
    {
        return isset($this->phasingConfig[$phaser->race->key][$sourceUnit->key][$targetUnit->key]);
    }

    public function getAvailableTargets(Dominion $phaser, Unit $sourceUnit): array
    {
        $keys = array_keys($this->phasingConfig[$phaser->race->key][$sourceUnit->key]);
        $uniqueKeys = array_unique($keys);
        sort($uniqueKeys);
        return $uniqueKeys;
    }

}
