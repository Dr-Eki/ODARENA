<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\ResearchCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Models\Building;
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
        MilitaryCalculator $militaryCalculator,
        LandHelper $landHelper,
        RaceHelper $raceHelper,
        ResearchCalculator $researchCalculator,
        ResourceCalculator $resourceCalculator
        )
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->landCalculator = $landCalculator;
        $this->improvementCalculator = $improvementCalculator;
        $this->landHelper = $landHelper;
        $this->militaryCalculator = $militaryCalculator;
        $this->raceHelper = $raceHelper;
        $this->researchCalculator = $researchCalculator;
        $this->resourceCalculator = $resourceCalculator;
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

        if($dominion->race->getPerkValue('no_construction_costs') or $dominion->protection_ticks == 96)
        {
            return $cost;
        }

        $cost = 250 + ($this->landCalculator->getTotalLand($dominion) * 1.5);
        $cost /= 2;

        if(count($dominion->race->construction_materials) === 1)
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

        if($dominion->race->getPerkValue('no_construction_costs') or $dominion->protection_ticks == 96)
        {
            return $cost;
        }

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

        $constructionMaterials = $dominion->race->construction_materials;
        $barrenLand = $this->landCalculator->getTotalBarrenLand($dominion);

        if($dominion->race->getPerkValue('no_construction_costs') or $dominion->protection_ticks == 96)
        {
            return $barrenLand;
        }

        $primaryResource = $constructionMaterials[0];

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

                # Resources 2.0
                floor($this->resourceCalculator->getAmount($dominion, $primaryResource) / $primaryCost),
                floor($this->resourceCalculator->getAmount($dominion, $secondaryResource) / $secondaryCost),
            );
        }
        else
        {
            $maxAfford = min(
                $barrenLand,
                floor($this->resourceCalculator->getAmount($dominion, $primaryResource) / $primaryCost),
            );
        }

        # Simian hack
        if($dominion->race->getPerkValue('forest_construction_cost'))
        {
            return max($this->landCalculator->getTotalBarrenLandByLandType($dominion, 'forest'), $maxAfford);
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

        // Advancement
        $multiplier += $dominion->getAdvancementPerkMultiplier('construction_cost');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('construction_cost');

        // Spell
        $multiplier += $dominion->getSpellPerkMultiplier('construction_cost');

        // Deity
        $multiplier += $dominion->getDeityPerkMultiplier('construction_cost');

        // Decree
        $multiplier += $dominion->getDecreePerkMultiplier('construction_cost');
        $multiplier += $dominion->getDecreePerkMultiplier('construction_cost_from_wizard_ratio') * $this->militaryCalculator->getWizardRatio($dominion);

        // Title
        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('construction_cost') * $dominion->getTitlePerkMultiplier();
        }

        // Improvements
        $multiplier += $dominion->getImprovementPerkMultiplier('construction_cost');


        // Cap at max -90%.
        $multiplier = max($multiplier, $maxReduction);

        return (1 + $multiplier);
    }

    public function getConstructionTicks(Dominion $dominion): int
    {
        $ticks = 12;

        $ticks -= $dominion->race->getPerkValue('increased_construction_speed');
        $ticks += $dominion->getTechPerkValue('construction_time_raw');
        $ticks -= $dominion->title->getPerkValue('increased_construction_speed') * $dominion->getTitlePerkMultiplier();

        $multiplier = 1;
        $multiplier += $dominion->getImprovementPerkMultiplier('construction_time');
        $multiplier += $dominion->getBuildingPerkMultiplier('construction_time');
        $multiplier += $dominion->getAdvancementPerkMultiplier('construction_time');
        $multiplier += $dominion->getDecreePerkMultiplier('construction_time_from_wizard_ratio') * $this->militaryCalculator->getWizardRatio($dominion);

        $ticks *= $multiplier;

        return max(1, ceil($ticks));
    }

    public function canBuildBuilding(Dominion $dominion, Building $building): bool
    {
        if($building->perks()->get()->contains('key', 'research_required_to_build'))
        {
            $techRequiredKey = $building->perks()->get()->where('key', 'research_required_to_build')->first()->pivot->value;

            return $dominion->techs->contains('key', $techRequiredKey);
        }

        return true;
    }
}
