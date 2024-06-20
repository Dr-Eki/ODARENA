<?php

// We want strict types here.
declare(strict_types=1);

namespace OpenDominion\Services\Hold;

use OpenDominion\Exceptions\GameException;

use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hold;
use OpenDominion\Models\Dominion\Queue as DominionQueue;
use OpenDominion\Models\HoldUnit;
use OpenDominion\Models\Unit;

use OpenDominion\Calculators\Hold\MilitaryCalculator;

class UnitService
{

    protected $militaryCalculator;

    public function __construct()
    {
        #$this->militaryCalculator = app(MilitaryCalculator::class);
    }

    public function addUnits(Hold $hold, array $units, int $state = 0): void
    {
        foreach($units as $unitKey => $amount)
        {
            $unit = Unit::where('key', $unitKey)->first();
            $amount = (int)abs($amount);
            $state = (int)$state;

            # Create or update HoldUnit
            HoldUnit::updateOrCreate([
                    'hold_id' => $hold->id,
                    'unit_id' => $unit->id,
                    'amount' => $amount,
                    'state' => $state,
            ]);
        }
    }

    public function removeUnits(Hold $hold, array $units): void
    {
        foreach($units as $unitKey => $amount)
        {
            $unit = Unit::where('key', $unitKey)->first();
            $amount = (int)abs($amount);

            # Update HoldUnit, delete if new amount is 0 or less
            $holdUnit = HoldUnit::where([
                'hold_id' => $hold->id,
                'unit_id' => $unit->id,
            ])->first();

            if($holdUnit)
            {
                $temporaryAmount = $holdUnit->amount - $amount;

                if($temporaryAmount <= 0)
                {
                    $holdUnit->delete();
                }
                else
                {
                    $holdUnit->amount = $temporaryAmount;
                    $holdUnit->save();
                }
            }
        }
    }

    public function changeUnitsState(Hold $hold, array $units, int $state): void
    {
        foreach($units as $unitKey => $amount)
        {
            $unit = Unit::where('key', $unitKey)->first();
            $amount = (int)abs($amount);
            $state = (int)$state;

            # Update HoldUnit
            HoldUnit::updateOrCreate([
                'hold_id' => $hold->id,
                'unit_id' => $unit->id,
                'amount' => $amount,
                'state' => $state,
            ]);
        }
    }

    public function sendUnitsToDominion(Hold $hold, Dominion $dominion, array $units): void
    {

        $unitsAsSlots = [];

        if($hold->isAbandoned() or $hold->isLocked())
        {
            throw new GameException('Cannot send units from an abandoned or locked hold.');
        }

        if($hold->round->id !== $hold->round->id)
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

            if($amount > $hold->{'military_unit' . $unit->slot})
            {
                throw new GameException('Not enough units.');
            }

            $unitsAsSlots[$unit->slot] = $amount;
        }

        if (!$this->militaryCalculator->passes43RatioRule($hold, null, null, $unitsAsSlots))
        {
            throw new GameException('You are giving away too many units, based on your new home DP (4:3 rule).');
        }

        DB::transaction( function() use ($hold, $dominion, $units)
        {

            # Create HoldQueue
            foreach($units as $unitKey => $amount)
            {
                $unit = Unit::where('key', $unitKey)->first();
                $amount = (int)abs($amount);
                // Add logic
            }
        });
    }

}
