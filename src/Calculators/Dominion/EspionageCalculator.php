<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Spyop;

class EspionageCalculator
{
    public function canPerform(Dominion $dominion, Spyop $spyop): bool
    {

        if(
          # Must be available to the dominion's faction (race)
          !$this->isSpyopAvailableToDominion($dominion, $spyop)

          # Cannot cast disabled spells
          or $spyop->enabled !== 1

          # Cannot cost more SS than the dominions has
          or ($dominion->spy_strength - $this->getSpyStrengthCost($spyop)) < 0

          # Round must have started
          or !$dominion->round->hasStarted()

          # Dominion must not be in protection
          or $dominion->isUnderProtection()

          # Hostile ops and theft cannot be performed within the first day
          or (($spyop->scope == 'hostile' or $spyop->scope == 'theft') and (now()->diffInDays($dominion->round->start_date) < 1))
        )
        {
            return false;
        }

        return true;
    }

    public function isSpyopAvailableToDominion(Dominion $dominion, Spyop $spyop): bool
    {
        return $this->isSpyopAvailableToRace($dominion->race, $spyop);
    }


    public function isSpyopAvailableToRace(Race $race, Spyop $spyop): bool
    {
        $isAvailable = true;

        if(count($spyop->exclusive_races) > 0 and !in_array($race->name, $spyop->exclusive_races))
        {
            $isAvailable = false;
        }

        if(count($spyop->excluded_races) > 0 and in_array($race->name, $spyop->excluded_races))
        {
            $isAvailable = false;
        }

        return $isAvailable;
    }

    public function getSpyStrengthCost(Spyop $spyop)
    {
        # Default values
        $scopeCost = [
                'info' => 1,
                'theft' => 4,
                'hostile' => 4,
            ];

        $cost = $scopeCost[$spyop->scope];

        return $spyop->spy_strength ?? $cost;
    }

    public function getSpyStrengthBase(Dominion $dominion): int
    {
        # Reserved for future use...
        return 100;
    }

    public function getSpyStrengthRecoveryAmount(Dominion $dominion): int
    {
        $amount = 4;

        $amount += $dominion->getBuildingPerkValue('spy_strength_recovery');
        $amount += $dominion->getAdvancementPerkValue('spy_strength_recovery');
        $amount += $dominion->getSpellPerkValue('spy_strength_recovery');
        $amount += $dominion->title->getPerkValue('spy_strength_recovery') * $dominion->getTitlePerkMultiplier();

        $amount = floor($amount);

        return $amount;
    }

}
