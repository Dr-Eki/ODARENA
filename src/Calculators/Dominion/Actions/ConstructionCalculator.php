<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Models\Dominion;

# ODA
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\RaceHelper;

class ConstructionCalculator
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandHelper */
    protected $landHelper;

    /** @var RaceHelper */
    protected $raceHelper;

    /**
     * ConstructionCalculator constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param LandCalculator $landCalculator
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        LandCalculator $landCalculator,
        ImprovementCalculator $improvementCalculator,
        LandHelper $landHelper,
        RaceHelper $raceHelper
        )
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->landCalculator = $landCalculator;
        $this->improvementCalculator = $improvementCalculator;
        $this->landHelper = $landHelper;
        $this->raceHelper = $raceHelper;
    }

    protected const SINGLE_RESOURCE_COST_DIVISOR = 5;

    public function getSettings(): array
    {
        return $constants = [
            'SINGLE_RESOURCE_COST_DIVISOR' => static::SINGLE_RESOURCE_COST_DIVISOR,
        ];
    }

    /**
     * Returns the Dominion's construction raw cost for the primary resource.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getConstructionCostPrimaryRaw(Dominion $dominion): float
    {
        $cost = 0;
        $cost = 250 + ($this->landCalculator->getTotalLand($dominion) * 1.5);
        $cost /= 2;

        if(count($this->raceHelper->getConstructionMaterials($dominion->race)) === 1)
        {
            $cost /= static::SINGLE_RESOURCE_COST_DIVISOR;
        }

        return $cost;
    }

    /**
     * Returns the Dominion's construction total cost for the primary resource.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getConstructionCostPrimary(Dominion $dominion): float
    {
        return round($this->getConstructionCostPrimaryRaw($dominion) * $this->getCostMultiplier($dominion));
    }

    /**
     * Returns the Dominion's construction cost for the secondary resource.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getConstructionCostSecondaryRaw(Dominion $dominion): float
    {
        $cost = 0;
        $cost = 100 + (($this->landCalculator->getTotalLand($dominion) - 250) * (pi()/10));
        $cost /= 2;
        return $cost;
    }

    /**
     * Returns the Dominion's construction total cost for the secondary resource.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getConstructionCostSecondary(Dominion $dominion): float
    {
        return round($this->getConstructionCostSecondaryRaw($dominion) * $this->getCostMultiplier($dominion));
    }

    /**
     * Returns the maximum number of building a Dominion can construct.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxAfford(Dominion $dominion): int
    {

        $constructionMaterials = $this->raceHelper->getConstructionMaterials($dominion->race);
        $barrenLand = $this->landCalculator->getTotalBarrenLand($dominion);

        #if(isset($constructionMaterials[0]))
        #{
            $primaryResource = $constructionMaterials[0];
        #}
        if(isset($constructionMaterials[1]))
        {
            $secondaryResource = $constructionMaterials[1];
        }

        $primaryCost = $this->getConstructionCostPrimary($dominion);
        $secondaryCost = $this->getConstructionCostSecondary($dominion);

        if(isset($secondaryResource))
        {
            $maxAfford = min(
                $barrenLand,
                floor($dominion->{'resource_'.$primaryResource} / $primaryCost),
                floor($dominion->{'resource_'.$secondaryResource}  / $secondaryCost),
            );
        }
        else
        {
            $maxAfford = min(
                $barrenLand,
                floor($dominion->{'resource_'.$primaryResource} / $primaryCost),
            );
        }

        return $maxAfford;
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

        $maxReduction = -0.90;

        // Buildings
        $multiplier -= $dominion->getBuildingPerkMultiplier('construction_cost');

        // Faction Bonus
        $multiplier += $dominion->race->getPerkMultiplier('construction_cost');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('construction_cost');

        // Title
        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('construction_cost') * $dominion->title->getPerkBonus($dominion);
        }

        // Workshops
        $multiplier -= $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'workshops');

        // Cap at max -90%.
        $multiplier = max($multiplier, $maxReduction);

        return (1 + $multiplier);
    }
}
