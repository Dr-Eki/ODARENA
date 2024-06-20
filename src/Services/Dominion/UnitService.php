<?php

namespace OpenDominion\Services\Dominion;

use OpenDominion\Exceptions\GameException;

use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hold;
use OpenDominion\Models\Hold\Queue as HoldQueue;
use OpenDominion\Models\DominionUnit;
use OpenDominion\Models\Unit;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;

class UnitService
{

    protected $militaryCalculator;

    public function __construct()
    {
        $this->militaryCalculator = app(MilitaryCalculator::class);
    }

    public function addUnits(Dominion $dominion, array $units, int $state): void
    {
        foreach($units as $unitKey => $amount)
        {
            $unit = Unit::where('key', $unitKey)->first();
            $amount = (int)abs($amount);
            $state = (int)$state;

            # Create or update DominionUnit
            DominionUnit::updateOrCreate([
                    'dominion_id' => $dominion->id,
                    'unit_id' => $unit->id,
                    'amount' => $amount,
                    'state' => $state,
            ]);
        }
    }

    public function removeUnits(Dominion $dominion, array $units): void
    {
        foreach($units as $unitKey => $amount)
        {
            $unit = Unit::where('key', $unitKey)->first();
            $amount = (int)abs($amount);

            # Update DominionUnit, delete if new amount is 0 or less
            $dominionUnit = DominionUnit::where([
                'dominion_id' => $dominion->id,
                'unit_id' => $unit->id,
            ])->first();

            if($dominionUnit)
            {
                $temporaryAmount = $dominionUnit->amount - $amount;

                if($temporaryAmount <= 0)
                {
                    $dominionUnit->delete();
                }
                else
                {
                    $dominionUnit->amount = $temporaryAmount;
                    $dominionUnit->save();
                }
            }
        }
    }

    public function changeUnitsState(Dominion $dominion, array $units, int $state): void
    {
        foreach($units as $unitKey => $amount)
        {
            $unit = Unit::where('key', $unitKey)->first();
            $amount = (int)abs($amount);
            $state = (int)$state;

            # Update DominionUnit
            DominionUnit::updateOrCreate([
                'dominion_id' => $dominion->id,
                'unit_id' => $unit->id,
                'amount' => $amount,
                'state' => $state,
            ]);
        }
    }

    public function sendUnitsToHold(Dominion $dominion, Hold $hold, array $units): void
    {

        $unitsAsSlots = [];

        if($dominion->isAbandoned() or $dominion->isLocked())
        {
            throw new GameException('Cannot send units from an abandoned or locked dominion.');
        }

        if($dominion->round->id !== $hold->round->id)
        {
            throw new GameException('Cannot send units to a hold from a different round.');
        }

        foreach($units as $unitKey => $amount)
        {
            $unit = Unit::where('key', $unitKey)->first();

            if(!$unit)
            {
                throw new GameException("Invalid unit key: {$unitKey}. This unit cannot be sent to a hold.");
            }

            if($amount == 0)
            {
                unset($units[$unitKey]);
            }

            if($amount < 0)
            {
                throw new GameException('Invalid amount.');
            }

            if($amount > $dominion->{'military_unit' . $unit->slot})
            {
                throw new GameException('Not enough units.');
            }

            $unitsAsSlots[$unit->slot] = $amount;
        }

        if (!$this->militaryCalculator->passes43RatioRule($dominion, null, null, $unitsAsSlots))
        {
            throw new GameException('You are giving away too many units, based on your new home DP (4:3 rule).');
        }

        DB::transaction( function() use ($dominion, $hold, $units)
        {

            # Create HoldQueue
            foreach($units as $unitKey => $amount)
            {
                $unit = Unit::where('key', $unitKey)->first();
                $amount = (int)abs($amount);

                $holdQueue = HoldQueue::updateOrCreate(
                    [
                        'hold_id' => $hold->id,
                        'type' => 'units_gift',
                        'item_type' => Unit::class,
                        'item_id' => $unit->id,
                        'tick' => 12,
                        'source_type' => Dominion::class,
                        'source_id' => $dominion->id,
                    ],
                    [
                        'amount' => DB::raw("amount + $amount")
                    ]
                );

                if($holdQueue)
                {
                    $dominion->{'military_unit' . $unit->slot} -= $amount;
                    $dominion->save();
                }
            }
        });
    }

}
