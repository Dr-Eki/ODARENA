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
            for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
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

    public function getLandSize(Dominion $dominion): int
    {
        $landSize = 0;

        foreach($dominion->terrains as $terrain)
        {
            $landSize += $terrain->pivot->amount;
        }

        return $landSize;
    }


    public function getTotalBuildings(Dominion $dominion): int
    {
        $totalBuildings = 0;

        foreach($dominion->buildings as $building)
        {
            $totalBuildings += $building->pivot->amount;
        }

        return $totalBuildings;
    }

    public function getTotalBuildingsByTerrain(Dominion $dominion, string $terrainKey)
    {
        $totalBuildings = 0;

        foreach($dominion->buildings as $building)
        {
            if($building->building->terrain === $terrainKey)
            {
                $totalBuildings += $building->amount;
            }
        }

        return $totalBuildings;
    }

    public function getTotalBarrenLand(Dominion $dominion): int
    {
        return $this->getLandSize($dominion) - $this->getTotalBuildings($dominion);
    }

    public function getTotalBarrenLandByTerrain(Dominion $dominion, string $terrainKey): int
    {
        return $dominion->{'terrain_' . $terrainKey} - $this->getTotalBuildingsByTerrain($dominion, $terrainKey);
    }

}
