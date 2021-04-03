<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
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

          # Espionage cannot be performed at all after offensive actions are disabled
          or $dominion->round->hasOffensiveActionsDisabled()

          # Round must have started
          or !$dominion->round->hasStarted()

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
        $isAvailable = true;

        if(count($spyop->exclusive_races) > 0 and !in_array($dominion->race->name, $spyop->exclusive_races))
        {
            $isAvailable = false;
        }

        if(count($spyop->excluded_races) > 0 and in_array($dominion->race->name, $spyop->excluded_races))
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


}
