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

    public function getAttackerPrestigeChange(Dominion $dominion, Dominion $defender, array $invasion)
    {
        $baseGain = 50;
    }

    public function getDefenderPrestigeChange(Dominion $dominion, Dominion $defender, array $invasion)
    {
        $baseLoss = 20;
    }

    public function getExpeditionerPrestigeChange(Dominion $dominion, int $landDiscovered)
    {
        return floorInt($landDiscovered / $dominion->land * 400);
    }

    public function getBasePrestigeChangeMultiplier(Dominion $dominion, array $units = []): float
    {
        $multiplier = 0;
        $multiplier += $dominion->race->getPerkMultiplier('prestige_gains');
        $multiplier += $this->militaryCalculator->getPrestigeGainsPerk($dominion, $units);
        $multiplier += $dominion->getAdvancementPerkMultiplier('prestige_gains');
        $multiplier += $dominion->getTechPerkMultiplier('prestige_gains');
        $multiplier += $dominion->getBuildingPerkMultiplier('prestige_gains');
        $multiplier += $dominion->getImprovementPerkMultiplier('prestige_gains');
        $multiplier += $dominion->getSpellPerkMultiplier('prestige_gains');
        $multiplier += $dominion->getDeityPerkMultiplier('prestige_gains');
        $multiplier += $dominion->realm->getArtefactPerkMultiplier('prestige_gains');
        $multiplier += $dominion->title->getPerkMultiplier('prestige_gains') * $dominion->getTitlePerkMultiplier();
        $multiplier += $dominion->getDecreePerkMultiplier('prestige_gains');

        return $multiplier;
    }

    public function getAttackerPrestigeChangeMultiplier(Dominion $dominion, Dominion $enemy, array $units = [], bool $isSuccessful = false): float
    {
        $baseMultiplier = $this->getBasePrestigeChangeMultiplier($dominion, $units);
        $attackerMultiplier = 1;

        # +10% if the attacker is a monarch
        $attackerMultiplier += $dominion->isMonarch() ? 0.10 : 0.00;

        # +20% if the defender is a monarch
        $attackerMultiplier += ($isSuccessful and $enemy->isMonarch()) ? 0.20 : 0.00;

        return $baseMultiplier + $attackerMultiplier;
    }

}
