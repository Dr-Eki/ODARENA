<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Models\Dominion;

class RezoningCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /**
     * RezoningCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     */
    public function __construct(
        LandCalculator $landCalculator,
        SpellCalculator $spellCalculator,
        ImprovementCalculator $improvementCalculator
    ) {
        $this->landCalculator = $landCalculator;
        $this->spellCalculator = $spellCalculator;
        $this->improvementCalculator = $improvementCalculator;
    }


    /**
     * Returns the Dominion's construction materials.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getRezoningMaterial(Dominion $dominion): string
    {
        if($dominion->race->construction_materials === null)
        {
            return [];
        }
        $materials = explode(',', $dominion->race->construction_materials);

        return $materials[0];
    }

    /**
     * Returns the Dominion's rezoning gold cost (per acre of land).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getRezoningCost(Dominion $dominion): int
    {

      $cost = 0;
      $cost += $this->landCalculator->getTotalLand($dominion);
      $cost -= 250;
      $cost *= 0.6;
      $cost += 250;

      $cost *= 0.85;

      $cost *= $this->getCostMultiplier($dominion);

      return round($cost);

    }

    /**
     * Returns the maximum number of acres of land a Dominion can rezone.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxAfford(Dominion $dominion): int
    {

        $resource = $this->getRezoningMaterial($dominion);
        $cost = $this->getRezoningCost($dominion);

        return min(
            floor($dominion->{'resource_'.$resource} / $cost),
            $this->landCalculator->getTotalBarrenLand($dominion)
          );

    }

    /**
     * Returns the Dominion's rezoning cost multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        $maxReduction = -0.90;

        // Buildings
        $multiplier -= $dominion->getBuildingPerkMultiplier('rezone_cost');

        // Faction Bonus
        $multiplier += $dominion->race->getPerkMultiplier('rezone_cost');

        # Workshops
        $multiplier -= $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'workshops');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('rezone_cost');

        // Techs
        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('rezone_cost') * $dominion->title->getPerkBonus($dominion);
        }

        $multiplier = max($multiplier, $maxReduction);

        return (1 + $multiplier);
    }
}
