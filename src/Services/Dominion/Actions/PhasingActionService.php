<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Illuminate\Support\Str;
use OpenDominion\Exceptions\GameException;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Unit;

use OpenDominion\Traits\DominionGuardsTrait;

use OpenDominion\Calculators\Dominion\PhasingCalculator;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;

class PhasingActionService
{
    use DominionGuardsTrait;

    protected $phasingCalculator;
    protected $resourceService;
    protected $statsService;

    protected $phasing;

    public function __construct()
    {
        $this->phasingCalculator = app(PhasingCalculator::class);
        $this->resourceService = app(ResourceService::class);
        $this->statsService = app(StatsService::class);
    }

    public function phase(Dominion $phaser, Unit $sourceUnit, int $sourceUnitAmount, Unit $targetUnit): array
    {

        $this->guardLockedDominion($phaser);
        $this->guardActionsDuringTick($phaser);
        $this->guardLockedDominion($phaser);

        DB::transaction(function () use ($phaser, $sourceUnit, $sourceUnitAmount, $targetUnit)
        {

            if(!$phaser->race->getPerkValue('can_phase_units'))
            {
                throw new GameException("{$phaser->race->name} cannot phase units.");
            }

            if(!$phaser->round->hasStarted())
            {
                throw new GameException('You cannot steal until the round has started.');
            }

            if($phaser->round->hasEnded())
            {
                throw new GameException('You cannot steal after the round has ended.');
            }

            if(!$this->phasingCalculator->canPhaseSourceToTarget($phaser, $sourceUnit, $targetUnit))
            {
                throw new GameException("You cannot phase {$sourceUnit->name} to {$targetUnit->name}.");
            }

            if($phaser->{'military_unit' . $sourceUnit->slot} < $sourceUnitAmount)
            {
                throw new GameException('You do not have that many units available to phase.');
            }

            if(!$this->phasingCalculator->canPhaserAffordingPhasing($phaser, $sourceUnit, $sourceUnitAmount, $targetUnit))
            {
                throw new GameException('You cannot afford to phase that many units.');
            }

            if ($sourceUnitAmount <= 0)
            {
                throw new GameException('You need to phase at least some units.');
            }

            if($phaser->getSpellPerkValue('cannot_phase_units'))
            {
                throw new GameException('A magical state surrounds the lands, making it impossible for you to phase units.');
            }

            $cost = $this->phasingCalculator->getPhasingCost($phaser, $sourceUnit, $sourceUnitAmount, $targetUnit);

            $slotToRemove = $sourceUnit->slot;
            $slotToAdd = $targetUnit->slot;

            $phaser->{'military_unit' . $slotToRemove} -= $sourceUnitAmount;
            $phaser->{'military_unit' . $slotToAdd} += $sourceUnitAmount;

            $this->resourceService->update($phaser, [key($cost) => -current($cost)]);

            $phaser->save();

            $this->phasing = [
                'source_unit' => $sourceUnit,
                'source_unit_amount' => $sourceUnitAmount,
                'target_unit' => $targetUnit,
                'resource_key' => key($cost),
                'cost' => current($cost),
            ];

        });

        $this->statsService->updateStat($phaser, 'units_phased', $this->phasing['source_unit_amount']);
        $this->statsService->updateStat($phaser, ($this->phasing['resource_key'] . '_phasing'), $this->phasing['cost']);


        $message = sprintf(
            'You phase %s %s into %s.',
            number_format($sourceUnitAmount),
            Str::plural($sourceUnit->name, $sourceUnitAmount),
            Str::plural($targetUnit->name, $sourceUnitAmount)
        );

        return [
            'message' => $message,
            'alert-type' => 'success',
            'redirect' => route('dominion.phasing')
        ];
    }

}