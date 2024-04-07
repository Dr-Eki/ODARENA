<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class PrestigeCalculator
{

    protected $militaryCalculator;

    /**
     * Returns the Dominion's prestige multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPrestigeMultiplier(Dominion $dominion): float
    {

        $prestigeEffectMultiplier = 1;
        $prestigeEffectMultiplier += $dominion->race->getPerkMultiplier('prestige_effect');

        return (floor($dominion->prestige * $prestigeEffectMultiplier) / 10000);
    }

}
