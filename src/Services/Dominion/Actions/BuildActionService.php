<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Building;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Traits\DominionGuardsTrait;

# ODA
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Calculators\Dominion\SpellCalculator;

class BuildActionService
{
    use DominionGuardsTrait;

    /**
     * ConstructionActionService constructor.
     */
    public function __construct()
    {
        $this->buildingHelper = app(BuildingHelper::class);
        $this->constructionCalculator = app(ConstructionCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->queueService = app(QueueService::class);
        $this->raceHelper = app(RaceHelper::class);
        $this->resourceService = app(ResourceService::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->statsService = app(StatsService::class);
    }

    /**
     * Does a construction action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws GameException
     */
    public function build(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        $data = array_only($data, array_map(function ($value) {
            return "building_{$value}";
        }, $this->buildingHelper->getBuildingKeys($dominion)->toArray()));

        $data = array_map('\intval', $data);

        $totalBuildingsToConstruct = array_sum($data);

        if(!$dominion->round->getSetting('buildings'))
        {
            throw new GameException('Building is disabled this round.');
        }

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot build while you are in stasis.');
        }

        if ($totalBuildingsToConstruct <= 0)
        {
            throw new GameException('Construction was not started due to bad input.');
        }

        if ($dominion->race->getPerkValue('cannot_build') or $dominion->getSpellPerkValue('cannot_build') or $dominion->race->getPerkValue('growth_cannot_build'))
        {
            throw new GameException('Your faction is unable to construct buildings.');
        }

        if ($totalBuildingsToConstruct > $this->constructionCalculator->getMaxAfford($dominion))
        {
            throw new GameException('You do not have enough resources to construct ' . number_format($totalBuildingsToConstruct) . '  buildings.');
        }

        $buildingsByLandType = [];

        foreach ($data as $buildingKey => $amount)
        {
            if ($amount === 0)
            {
                continue;
            }

            if ($amount < 0)
            {
                throw new GameException('Construction was not completed due to bad input.');
            }

            $buildingKey = str_replace('building_', '', $buildingKey);

            $building = Building::where('key', $buildingKey)->first();

            if ($building->enabled !== 1)
            {
                throw new GameException('Cannot build ' . $building->name . ' because it is not enabled.');
            }

            if (!$this->constructionCalculator->canBuildBuilding($dominion, $building))
            {
                throw new GameException('You do not have the necessary technological research to build ' . $building->name . '.');
            }

            $landType = $building->land_type;

            if(!isset($buildingsByLandType[$landType]))
            {
                $buildingsByLandType[$landType] = $amount;
            }
            else
            {
                $buildingsByLandType[$landType] += $amount;
            }


        }

        # Get construction materials
        $constructionMaterials = $dominion->race->construction_materials;

        $primaryResource = null;
        $secondaryResource = null;

        if(isset($constructionMaterials[0]))
        {
            $primaryResource = $constructionMaterials[0];
        }
        if(isset($constructionMaterials[1]))
        {
            $secondaryResource = $constructionMaterials[1];
        }

        foreach ($buildingsByLandType as $landType => $amount)
        {

            if ($amount > $this->landCalculator->getTotalBarrenLandByLandType($dominion, $landType))
            {
                throw new GameException("You do not have enough barren land to construct {$totalBuildingsToConstruct} buildings.");
            }

            $primaryCost = $this->constructionCalculator->getConstructionCostPrimary($dominion);# * $totalBuildingsToConstruct;
            $secondaryCost = $this->constructionCalculator->getConstructionCostSecondary($dominion);# * $totalBuildingsToConstruct;

            if(($landConstructionCostPerk = $dominion->race->getPerkMultiplier($landType.'_construction_cost')) or ($landConstructionCostPerk = $dominion->realm->getArtefactPerkMultiplier($landType.'_construction_cost')))
            {
                $primaryCost *= (1 + $landConstructionCostPerk);
                $secondaryCost *=  (1 + $landConstructionCostPerk);
            }

            $primaryCostPerLandType[$landType] = $amount * $primaryCost;
            $secondaryCostPerLandType[$landType] = $amount * $secondaryCost;
        }

        $primaryCostTotal = array_sum($primaryCostPerLandType);
        $secondaryCostTotal = array_sum($secondaryCostPerLandType);

        DB::transaction(function () use ($dominion, $data, $primaryCostTotal, $secondaryCostTotal, $primaryResource, $secondaryResource, $totalBuildingsToConstruct)
        {
            $ticks = $this->constructionCalculator->getConstructionTicks($dominion);

            $this->queueService->queueResources('construction', $dominion, $data, $ticks);

            $this->resourceService->updateResources($dominion, [$primaryResource => $primaryCostTotal*-1]);
            $this->statsService->updateStat($dominion, ($primaryResource . '_building'), $primaryCostTotal);

            if(isset($secondaryResource))
            {
                $this->resourceService->updateResources($dominion, [$secondaryResource => $secondaryCostTotal*-1]);
                $this->statsService->updateStat($dominion, ($secondaryResource . '_building'), $secondaryCostTotal);
            }

            $dominion->save(['event' => HistoryService::EVENT_ACTION_CONSTRUCT]);

        });

        if(isset($secondaryResource))
        {
            $return = [
                'message' => sprintf(
                    'Construction started at a cost of %s %s and %s %s.',
                    number_format($primaryCostTotal),
                    $primaryResource,
                    number_format($secondaryCostTotal),
                    $secondaryResource
                ),
                'data' => [
                    'primaryCost' => $primaryCostTotal,
                    'secondaryCost' => $secondaryCostTotal,
                ]
            ];
        }
        else
        {
            $return = [
                'message' => sprintf(
                    'Construction started at a cost of %s %s.',
                    number_format($primaryCostTotal),
                    $primaryResource
                ),
                'data' => [
                    'primaryCost' => $primaryCostTotal
                ]
            ];
        }

        return $return;
    }
}
