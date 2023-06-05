<?php

namespace OpenDominion\Calculators;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
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
    public function __construct() {

          $this->buildingCalculator = app(BuildingCalculator::class);
          $this->landCalculator = app(LandCalculator::class);
          $this->militaryCalculator = app(MilitaryCalculator::class);
          $this->resourceCalculator = app(ResourceCalculator::class);
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

        foreach ($dominion->race->units as $unit)
        {
            $totalUnitsOfType = $this->militaryCalculator->getTotalUnitsForSlot($dominion, $unit->slot);
            $networth += $totalUnitsOfType * $this->getUnitNetworth($dominion, $unit);
        }

        $networth += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'spies') * 5;
        $networth += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'wizards') * 5;
        $networth += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'archmages') * 10;
        
        $networth += ($dominion->land * 20);
        $networth += ($this->buildingCalculator->getTotalBuildings($dominion) * 5);

        if($dominion->race->name == 'Demon')
        {
            $networth += $this->resourceCalculator->getAmount($dominion, 'soul') / 9;
        }

        $networth += $this->resourceCalculator->getAmount($dominion, 'marshling');
        
        if($dominion->race->name == 'Yeti')
        {
            $networth += min($this->resourceCalculator->getAmount($dominion, 'ore') / 60, $this->militaryCalculator->getTotalUnitsForSlot($dominion, 4)) * 6;
        }

        return round($networth);
    }

    /**
     * Returns a single Unit's networth.
     *
     * @param Dominion $dominion
     * @param Unit $unit
     * @return float
     */
     public function getUnitNetworth(Dominion $dominion, Unit $unit): float
     {
        if ($unit->static_networth !== 0)
        {
            return $unit->static_networth;
        }
        else
        {
            return (
                      $this->militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unit, 'offense') +
                      $this->militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unit, 'defense')
                    );
          }

      }
}
