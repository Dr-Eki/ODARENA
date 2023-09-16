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
        return (floor($dominion->prestige) / 10000);
    }

}
