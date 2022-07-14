<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;

use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Services\Dominion\QueueService;

class DominionCalculator
{
    public function __construct()
    {
        #$this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);

        $this->raceHelper = app(RaceHelper::class);
        $this->unitHelper = app(UnitHelper::class);

        $this->queueService = app(QueueService::class);
    }
    
    /**
     * Returns the Dominion's mental strength.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPsionicStrength(Dominion $dominion): float
    {
        $base = $this->raceHelper->getBasePsionicStrength($dominion->race);

        $multiplier = 1;

        $population = $this->populationCalculator->getPopulation($dominion);

        if(!$dominion->race->getPerkValue('no_population'))
        {
            for ($slot = 1; $slot <= 4; $slot++)
            {
                $unitStrength = 0;
                $unit = $dominion->race->units->filter(function ($unit) use ($slot)
                {
                    return ($unit->slot === $slot);
                })->first();
    
                $attributes = $this->unitHelper->getUnitAttributes($unit);
    
                foreach($attributes as $attribute)
                {
                    $unitStrength += $this->unitHelper->getUnitAttributePsionicStrengthValue($attribute);
                }
    
                $strengthFromUnit = $unitStrength * $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot);
    
                $multiplier += ($strengthFromUnit / $population);
    
            }
        }

        $multiplier += $dominion->getDeityPerkMultiplier('psionic_strength');
        $multiplier += $dominion->getSpellPerkMultiplier('psionic_strength');
        $multiplier += $dominion->getImprovementPerkMultiplier('psionic_strength');
        $multiplier += $dominion->getBuildingPerkMultiplier('psionic_strength');

        $strength = $base * $multiplier;

        return max(0, $strength);

    }

}