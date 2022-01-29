<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;


use OpenDominion\Calculators\Dominion\PopulationCalculator;

class MoraleCalculator
{

    public function __construct()
    {
        $this->populationCalculator = app(PopulationCalculator::class);
    }

    public function getMorale(Dominion $dominion): int
    {
        return $dominion->morale;
    }

    public function getBaseMorale(Dominion $dominion): float
    {
        $baseMorale = 100;

        $baseMorale += $this->getBaseMoraleModifier($dominion);
        $baseMorale *= $this->getBaseMoraleMultiplier($dominion);

        return $baseMorale;
    }

    # Added to base morale: 100 + the result of this function.
    public function getBaseMoraleModifier(Dominion $dominion): float
    {
        $baseModifier = 0;

        # Look for increases_morale
        for ($slot = 1; $slot <= 4; $slot++)
        {
            if($increasesMorale = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'increases_morale_by_population'))
            {
                # $increasesMorale is 1 for Immortal Guard and 2 for Immortal Knight
                $baseModifier += ($this->getTotalUnitsForSlot($dominion, $slot) / $this->populationCalculator->getPopulation($dominion)) * $increasesMorale;
            }
            if($increasesMoraleFixed = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'increases_morale_fixed'))
            {
                $baseModifier += $this->getTotalUnitsForSlot($dominion, $slot) * $increasesMoraleFixed / 100;
            }
        }

        return $baseModifier;
    }

    # Multiplier added to the base morale.
    public function getBaseMoraleMultiplier(Dominion $dominion): float
    {
        $modifier = 1;

        $modifier += $dominion->getBuildingPerkMultiplier('base_morale');
        $modifier += $dominion->getImprovementPerkMultiplier('base_morale');
        $modifier += $dominion->getSpellPerkMultiplier('base_morale');

        return $modifier;

    }

    public function moraleChangeModifier(Dominion $dominion): float
    {
        $moraleChangeModifier = 1;

        $moraleChangeModifier += $dominion->race->getPerkMultiplier('morale_change_tick');

        return max(0.10, $moraleChangeModifier);

    }

    public function getMoraleMultiplier(Dominion $dominion): float
    {
        return 0.90 + floor($dominion->morale) / 1000;
    }

}