<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Models\Dominion;

class ConstructionCalculator
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /**
     * ConstructionCalculator constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param LandCalculator $landCalculator
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        LandCalculator $landCalculator,
        ImprovementCalculator $improvementCalculator)
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->landCalculator = $landCalculator;
        $this->improvementCalculator = $improvementCalculator;
    }

    /**
     * Returns the Dominion's construction platinum cost (per building).
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPlatinumCost(Dominion $dominion): int
    {
        if($dominion->race->getPerkMultiplier('construction_cost_only_mana') or $dominion->race->getPerkMultiplier('construction_cost_only_food'))
        {
          return 0;
        }
        else
        {
          return ($this->getPlatinumCostRaw($dominion) * $this->getCostMultiplier($dominion));
        }

    }

    /**
     * Returns the Dominion's raw construction platinum cost (per building).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPlatinumCostRaw(Dominion $dominion): int
    {
        $cost = 0;
        $cost = 250 + ($this->landCalculator->getTotalLand($dominion) * 1.5);
        $cost *= 0.75;
        return round($cost);
    }

    /**
     * Returns the Dominion's construction platinum cost for a given number of acres.
     *
     * @param Dominion $dominion
     * @param int $acres
     * @return int
     */
    public function getTotalPlatinumCost(Dominion $dominion, int $acres): int
    {
        $cost = $this->getPlatinumCost($dominion);
        $totalCost = $cost * $acres;
        // Check for discounted acres after invasion
        $discountedAcres = min($dominion->discounted_land, $acres);
        if ($discountedAcres > 0) {
            $totalCost -= (int)ceil(($cost * $discountedAcres) / 2);
        }
        return $totalCost;
    }

    /**
     * Returns the Dominion's construction lumber cost (per building).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getLumberCost(Dominion $dominion): int
    {
      if($dominion->race->getPerkMultiplier('construction_cost_only_platinum') or $dominion->race->getPerkMultiplier('construction_cost_only_mana') or $dominion->race->getPerkMultiplier('construction_cost_only_food'))
      {
        return 0;
      }
      else
      {
        return ($this->getLumberCostRaw($dominion) * $this->getLumberCostMultiplier($dominion));
      }
    }

    /**
     * Returns the Dominion's raw construction lumber cost (per building).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getLumberCostRaw(Dominion $dominion): int
    {
        $cost = 0;
        $cost = 100 + (($this->landCalculator->getTotalLand($dominion) - 250) * (pi()/10));
        $cost *= 0.75;
        return round($cost);
    }

    /**
     * Returns the Dominion's construction lumber cost multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getLumberCostMultiplier(Dominion $dominion): float
    {
        return $this->getCostMultiplier($dominion);
    }

    /**
     * Returns the Dominion's construction lumber cost for a given number of acres.
     *
     * @param Dominion $dominion
     * @param int $acres
     * @return int
     */
    public function getTotalLumberCost(Dominion $dominion, int $acres): int
    {
        $cost = $this->getLumberCost($dominion);
        $totalCost = $cost * $acres;
        // Check for discounted acres after invasion
        $discountedAcres = min($dominion->discounted_land, $acres);
        if ($discountedAcres > 0) {
            $totalCost -= (int)ceil(($cost * $discountedAcres) / 2);
        }
        return $totalCost;
    }

### MANA VOID

        /**
         * Returns the Dominion's construction mana cost (per building).
         *
         * @param Dominion $dominion
         * @return float
         */
        public function getManaCost(Dominion $dominion): int
        {
            if($dominion->race->getPerkMultiplier('construction_cost_only_mana'))
            {
              return ($this->getManaCostRaw($dominion) * $this->getManaCostMultiplier($dominion));
            }
            else
            {
              return 0;
            }
        }

        /**
         * Returns the Dominion's raw construction mana cost (per building).
         *
         * @param Dominion $dominion
         * @return int
         */
        public function getManaCostRaw(Dominion $dominion): int
        {
            $cost = 0;
            $cost = 100 + (($this->landCalculator->getTotalLand($dominion) - 250) * (pi()/10));
            $cost *= 0.75;
            return round($cost);
        }

        /**
         * Returns the Dominion's construction mana cost multiplier.
         *
         * @param Dominion $dominion
         * @return float
         */
        public function getManaCostMultiplier(Dominion $dominion): float
        {
            $multiplier = $this->getCostMultiplier($dominion);

            return $multiplier;
        }

        /**
         * Returns the Dominion's construction mana cost for a given number of acres.
         *
         * @param Dominion $dominion
         * @param int $acres
         * @return int
         */
        public function getTotalManaCost(Dominion $dominion, int $acres): int
        {
            $cost = $this->getManaCost($dominion);
            $totalCost = $cost * $acres;
            // Check for discounted acres after invasion
            $discountedAcres = min($dominion->discounted_land, $acres);
            if ($discountedAcres > 0) {
                $totalCost -= (int)ceil(($cost * $discountedAcres) / 2);
            }
            return $totalCost;
        }

### MANA VOID


### FOOD GROWTH MYCONID

        /**
         * Returns the Dominion's construction food cost (per building).
         *
         * @param Dominion $dominion
         * @return float
         */
        public function getFoodCost(Dominion $dominion): int
        {
          if($dominion->race->getPerkMultiplier('construction_cost_only_food'))
          {
            return ($this->getFoodCostRaw($dominion) * $this->getFoodCostMultiplier($dominion));
          }
          else {
            return 0;
          }
        }

        /**
         * Returns the Dominion's raw construction mana cost (per building).
         *
         * @param Dominion $dominion
         * @return int
         */
        public function getFoodCostRaw(Dominion $dominion): int
        {
            $cost = 0;
            $cost = 100 + (($this->landCalculator->getTotalLand($dominion) - 250) * (pi()/10));
            $cost *= 0.75;
            return round($cost);
        }

        /**
         * Returns the Dominion's construction mana cost multiplier.
         *
         * @param Dominion $dominion
         * @return float
         */
        public function getFoodCostMultiplier(Dominion $dominion): float
        {
            $multiplier = $this->getCostMultiplier($dominion);
            return $multiplier;
        }

        /**
         * Returns the Dominion's construction mana cost for a given number of acres.
         *
         * @param Dominion $dominion
         * @param int $acres
         * @return int
         */
        public function getTotalFoodCost(Dominion $dominion, int $acres): int
        {
            $cost = $this->getFoodCost($dominion);
            $totalCost = $cost * $acres;
            // Check for discounted acres after invasion
            $discountedAcres = min($dominion->discounted_land, $acres);
            if ($discountedAcres > 0) {
                $totalCost -= (int)ceil(($cost * $discountedAcres) / 2);
            }
            return $totalCost;
        }

### FOOD GROWTH MYCONID

    /**
     * Returns the maximum number of building a Dominion can construct.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxAfford(Dominion $dominion): int
    {
        $discountedBuildings = 0;
        $platinumToSpend = $dominion->resource_platinum;
        $lumberToSpend = $dominion->resource_lumber;
        $manaToSpend = $dominion->resource_mana;
        $foodToSpend = $dominion->resource_food;

        $barrenLand = $this->landCalculator->getTotalBarrenLand($dominion);

        $platinumCost = $this->getPlatinumCost($dominion);
        $lumberCost = $this->getLumberCost($dominion);
        $manaCost = $this->getManaCost($dominion);
        $foodCost = $this->getFoodCost($dominion);

        // Check for discounted acres after invasion
        if ($dominion->discounted_land > 0)
        {
            if($platinumCost > 0)
            {
              $maxFromDiscountedPlatinum = (int)floor($platinumToSpend / ($platinumCost / 2));
            }
            else
            {
              $maxFromDiscountedPlatinum = 0;
            }

            if($lumberCost > 0)
            {
              $maxFromDiscountedLumber = (int)floor($lumberToSpend / ($lumberCost / 2));
            }
            else
            {
              $maxFromDiscountedLumber = 0;
            }

            if($manaCost > 0)
            {
              $maxFromDiscountedMana = (int)floor($manaToSpend / ($manaCost / 2));
            }
            else
            {
              $maxFromDiscountedMana = 0;
            }

            if($foodCost > 0)
            {
              $maxFromDiscountedFood = (int)floor($foodToSpend / ($foodCost / 2));
            }
            else
            {
              $maxFromDiscountedFood = 0;
            }


            // Set the number of afforded discounted buildings

            # Merfolk: only platinum
            if($dominion->race->getPerkValue('construction_cost_only_platinum'))
            {
              return $discountedBuildings + min($maxFromDiscountedPlatinum, $barrenLand);
            }
            # Void: mana construction costs
            elseif($dominion->race->getPerkValue('construction_cost_only_mana'))
            {
              return $discountedBuildings + min($maxFromDiscountedMana, $barrenLand);
            }
            # Growth and Myconid: food construction costs
            elseif($dominion->race->getPerkValue('construction_cost_only_food'))
            {
              return $discountedBuildings + min($maxFromDiscountedFood, $barrenLand);
            }
            else
            {
              $discountedBuildings = min(
                  $maxFromDiscountedPlatinum,
                  $maxFromDiscountedLumber,
                  $dominion->discounted_land,
                  $barrenLand
              );
            }

            // Subtract discounted building cost from available resources
            $platinumToSpend -= (int)ceil(($platinumCost * $discountedBuildings) / 2);
            $lumberToSpend -= (int)ceil(($lumberCost * $discountedBuildings) / 2);
            $manaToSpend -= (int)ceil(($manaCost * $discountedBuildings) / 2);
            $foodToSpend -= (int)ceil(($foodCost * $discountedBuildings) / 2);
        }

        # Merfolk: only platinum
        if($dominion->race->getPerkValue('construction_cost_only_platinum'))
        {
          return $discountedBuildings + min(
                  floor($platinumToSpend / $platinumCost),
                  ($barrenLand - $discountedBuildings)
              );
        }
        # Void: mana construction costs
        elseif($dominion->race->getPerkValue('construction_cost_only_mana'))
        {
          return $discountedBuildings + min(
                  floor($manaToSpend / $manaCost),
                  ($barrenLand - $discountedBuildings)
              );
        }
        # Growth and Myconid: food construction costs
        elseif($dominion->race->getPerkValue('construction_cost_only_food'))
        {
          return $discountedBuildings + min(
                  floor($foodToSpend / $foodCost),
                  ($barrenLand - $discountedBuildings)
              );
        }
        else
        {
          return $discountedBuildings + min(
                  floor($platinumToSpend / $platinumCost),
                  floor($lumberToSpend / $lumberCost),
                  ($barrenLand - $discountedBuildings)
              );
        }



    }

    /**
     * Returns the Dominion's global construction cost multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        $maxReduction = -90;

        // Values (percentages)
        $factoryReduction = 4;
        $factoryReductionMax = 75;

        // Factories
        $multiplier -= min(
            (($dominion->building_factory / $this->landCalculator->getTotalLand($dominion)) * $factoryReduction),
            ($factoryReductionMax / 100)
        );

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('construction_cost');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('construction_cost');

        // Workshops
        $multiplier -= $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'workshops');

        // Cap at max -90%.
        $multiplier = max($multiplier, $maxReduction);

        return (1 - $multiplier/100);
    }
}
