<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Building;
use OpenDominion\Models\Dominion;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;

class MoraleCalculator
{

    public function __construct()
    {
        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
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
                $baseModifier += ($this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) / $this->populationCalculator->getPopulation($dominion)) * $increasesMorale;
            }

            if($increasesMoraleFixed = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'increases_morale_fixed') * 100)
            {
                $amountOfThisUnit = $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot);

                # Is the unit limited to a building?
                if($buildingPairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'building_limit'))
                {
                    $buildingKey = (string)$buildingPairingLimit[0];
                    $maxPerBuilding = (float)$buildingPairingLimit[1];

                    $buildingsOwned = $this->buildingCalculator->getBuildingAmountOwned($dominion, Building::where('key', $buildingKey)->firstOrFail());

                    # Cap perk to number of units
                    $amountOfThisUnit = min($amountOfThisUnit, floor($buildingsOwned * $maxPerBuilding));

                    $building = Building::where('key',$buildingKey)->first();

                    # [Anti-abuse] Check if the pairing building provides morale bonus and, if so, cap morale perk from unit to the max of this building.
                    if($buildingPerkValues = $dominion->extractBuildingPerkValues($building->getPerkValue('base_morale')))
                    {

                        $ratio = (float)$buildingPerkValues[0];
                        $perk = (float)$buildingPerkValues[1];
                        $max = (float)$buildingPerkValues[2];
                        $maxOfThisBuildingToMaxOutPerk = ceil($this->landCalculator->getTotalLand($dominion) * (($max / $perk) / 100));
                        #dump($amountOfThisUnit);
                        $amountOfThisUnit = min($amountOfThisUnit, $maxOfThisBuildingToMaxOutPerk * $maxPerBuilding);
                        #dd($ratio, $perk, $max, $this->landCalculator->getTotalLand($dominion), $maxOfThisBuildingToMaxOutPerk, $amountOfThisUnit);
                    }

                }

                $baseModifier += $amountOfThisUnit * $increasesMoraleFixed / 100;
            }
        }

        return $baseModifier;
    }

    # Multiplier added to the base morale.
    public function getBaseMoraleMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        $multiplier += $dominion->getBuildingPerkMultiplier('base_morale');
        $multiplier += $dominion->getImprovementPerkMultiplier('base_morale');
        $multiplier += $dominion->getSpellPerkMultiplier('base_morale');

        return $multiplier;

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
