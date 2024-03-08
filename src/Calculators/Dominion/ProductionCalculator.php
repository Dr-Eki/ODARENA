<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;

class ProductionCalculator
{

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    public function __construct()
    {
        $this->militaryCalculator = app(MilitaryCalculator::class);
    }

    /**
     * Returns the Dominion's experience point production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getXpGeneration(Dominion $dominion): int
    {
        return floor($this->getXpGenerationRaw($dominion) * $this->getXpGenerationMultiplier($dominion));
    }

    public function getXpGenerationRaw(Dominion $dominion): float
    {

        if($dominion->getSpellPerkValue('no_xp_generation'))
        {
            return 0;
        }

        $xp = max(0, floor($dominion->prestige));

        $xp += $dominion->getUnitPerkProductionBonus('xp_generation_raw');
        $xp += $dominion->getBuildingPerkValue('xp_generation_raw');

        // Unit Perk: production_from_title
        $xp += $dominion->getUnitPerkProductionBonusFromTitle('xp');

        $xp += $dominion->race->getPerkValue('xp_generation_raw_from_draftees') * $dominion->military_draftees;

        return max(0, $xp);
    }

    public function getXpGenerationMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('xp_generation_mod');

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('xp_generation_mod');

        // Deity
        $multiplier += $dominion->getDeityPerkMultiplier('xp_generation_mod');

        // Artefacts
        $multiplier += $dominion->realm->getArtefactPerkMultiplier('xp_generation_mod');

        // Decree
        $multiplier += $dominion->getDecreePerkMultiplier('xp_generation_mod');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('xp_generation_mod');

        // Title
        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('xp_generation_mod') * $dominion->getTitlePerkMultiplier();
        }

        // Spell
        $multiplier += $dominion->getSpellPerkMultiplier('xp_generation_mod');

        // Improvements
        $multiplier += $dominion->getImprovementPerkMultiplier('xp_generation_mod');

        // Improvements
        $multiplier += $dominion->getTerrainPerkMultiplier('xp_generation_mod');

        return (1 + $multiplier);
    }

    public function getPrestigeInterest(Dominion $dominion): float
    {
        if($dominion->isAbandoned())
        {
            return 0;
        }

        $interestMultiplier = 1;
        foreach($dominion->race->units as $unit)
        {
            $interestMultiplier += $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'increases_morale_gains_fixed') * $this->militaryCalculator->getTotalUnitsForSlot($dominion, $unit->slot);
        }

        return $dominion->prestige * max(0, $this->militaryCalculator->getNetVictories($dominion) / 40000);
    }
}
