<?php

namespace OpenDominion\Calculators;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Unit;

class NetworthCalculator
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /**
     * NetworthCalculator constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator
    ) {
        $this->buildingCalculator = $buildingCalculator;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
    }

    /**
     * Returns a Realm's networth.
     *
     * @param Realm $realm
     * @return int
     */
    public function getRealmNetworth(Realm $realm): int
    {
        $networth = 0;

        foreach ($realm->dominions as $dominion)
        {
            $networth += $this->getDominionNetworth($dominion);
        }

        return $networth;
    }

    /**
     * Returns a Dominion's networth.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getDominionNetworth(Dominion $dominion): int
    {
        $networth = 0;

        // Values
        $networthPerSpy = 5;
        $networthPerWizard = 5;
        $networthPerArchMage = 10;
        $networthPerLand = 20;
        $networthPerBuilding = 5;

        foreach ($dominion->race->units as $unit)
        {
            $totalUnitsOfType = $this->militaryCalculator->getTotalUnitsForSlot($dominion, $unit->slot);
            $networth += $totalUnitsOfType * $this->getUnitNetworth($unit);
        }

        $networth += ($dominion->military_spies * $networthPerSpy);
        $networth += ($dominion->military_wizards * $networthPerWizard);
        $networth += ($dominion->military_archmages * $networthPerArchMage);

        $networth += ($this->landCalculator->getTotalLand($dominion) * $networthPerLand);
        $networth += ($this->buildingCalculator->getTotalBuildings($dominion) * $networthPerBuilding);

        $networth += $dominion->resource_soul / 10;

        return round($networth);
    }

    /**
     * Returns a single Unit's networth.
     *
     * @param Dominion $dominion
     * @param Unit $unit
     * @return float
     */
     public function getUnitNetworth(Unit $unit): float
     {
        if (isset($unit->static_networth) and $unit->static_networth > 0)
        {
          return $unit->static_networth;
        }
        else
        {
          return ($unit->cost_gold
                  + $unit->cost_ore*1.25
                  + $unit->cost_lumber*1.5
                  + $unit->cost_food*1.5
                  + $unit->cost_mana*2.5
                  + $unit->cost_gem*5
                  + $unit->cost_soul*7.5
                  + $unit->cost_champion*1.25
                  + $unit->cost_blood*2
                  + $unit->cost_unit1*10
                  + $unit->cost_unit2*10
                  + $unit->cost_unit3*20
                  + $unit->cost_unit4*20
                  + $unit->cost_spy*500
                  + $unit->cost_wizard*500
                  + $unit->cost_archmage*1000
                  + $unit->cost_morale*10
                  + $unit->cost_peasant*2.5
                  + $unit->cost_prestige*10
                  + $unit->cost_wild_yeti*30
              )/100;
          }

      }
}
