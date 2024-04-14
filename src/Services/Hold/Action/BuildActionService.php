<?php

namespace OpenDominion\Services\Hold\Actions;

use DB;
use Illuminate\Support\Arr;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
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

    protected $buildingHelper;
    protected $buildingCalculator;
    protected $constructionCalculator;
    protected $landCalculator;
    protected $landHelper;
    protected $queueService;
    protected $raceHelper;
    protected $resourceService;
    protected $spellCalculator;
    protected $statsService;

    /**
     * ConstructionActionService constructor.
     */
    public function __construct()
    {
        $this->buildingHelper = app(BuildingHelper::class);
        $this->buildingCalculator = app(BuildingCalculator::class);
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

        $data = Arr::only($data, array_map(function ($value) {
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

        if ($totalBuildingsToConstruct > $this->landCalculator->getTotalBarrenLand($dominion))
        {
            throw new GameException('You do not have enough barren land to construct ' . number_format($totalBuildingsToConstruct) . ' buildings.');
        }

        if ($dominion->race->getPerkValue('cannot_build') or $dominion->getSpellPerkValue('cannot_build'))
        {
            throw new GameException('Your faction is unable to construct buildings.');
        }

        if ($totalBuildingsToConstruct > $this->constructionCalculator->getMaxAfford($dominion))
        {
            throw new GameException('You do not have enough resources to construct ' . number_format($totalBuildingsToConstruct) . '  buildings.');
        }

        $primaryCostTotal = 0;
        $secondaryCostTotal = 0;

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

            if($this->buildingCalculator->buildingHasCapacityLimit($building))
            {
                $buildingCapacityLimit = $this->buildingCalculator->getBuildingCapacityLimit($dominion, $building);
                $availableCapacityForBuilding = $this->buildingCalculator->getAvailableCapacityForBuilding($dominion, $building);

                if($amount > $availableCapacityForBuilding)
                {
                    throw new GameException('You do not have enough capacity to build ' . $amount . ' ' . $building->name . ' (you have ' . $availableCapacityForBuilding . ' available capacity out of ' . $buildingCapacityLimit . ').');
                }
            }


            if (!$building->enabled)
            {
                throw new GameException('Cannot build ' . $building->name . ' because it is not enabled.');
            }

            if (!$this->constructionCalculator->canBuildBuilding($dominion, $building))
            {
                throw new GameException('You cannot build ' . $building->name . '.');
            }

            # Check pairing limit.
            if(($pairingLimit = $building->getPerkValue('pairing_limit')))
            {
                $pairingLimit = explode(',', $pairingLimit);

                $pairingBuilding = Building::where('key', $pairingLimit[1])->firstOrFail();
                $chunkSize = (int)$pairingLimit[0];

                # Get amount owned of $pairingBuilding
                $pairingBuildingRecord = $dominion->buildings()->where('key', $pairingBuilding->key)->first();
                $pairingBuildingOwned = $pairingBuildingRecord ? ($pairingBuildingRecord->pivot->owned ?? 0) : 0;

                $maxCapacity = intval(floor($pairingBuildingOwned / $chunkSize));

                # Get amount owned of $pairedBuilding
                $pairedBuildingRecord = $dominion->buildings()->where('key', $building->key)->first();
                $pairedOwnedAndUnderConstruction = $pairedBuildingRecord ? ($pairedBuildingRecord->pivot->owned ?? 0) : 0;
                $pairedOwnedAndUnderConstruction += $this->queueService->getConstructionQueueTotalByResource($dominion, "building_{$building->key}");
                $pairedOwnedAndUnderConstruction += $this->queueService->getRepairQueueTotalByResource($dominion, "building_{$building->key}");
                $pairedOwnedAndUnderConstruction += $this->queueService->getInvasionQueueTotalByResource($dominion, "building_{$building->key}");

                $availableCapacityForBuilding = max($maxCapacity - $pairedOwnedAndUnderConstruction, 0);

                if($amount > $availableCapacityForBuilding)
                {
                    #throw new GameException('You cannot build ' . number_format($amount) . ' more ' . Str::plural($building->name, $amount) . ' because you only have enough ' . $pairingBuilding->name . ' for ' . number_format($availableCapacityForBuilding) . ' new ' . Str::plural($building->name, $availableCapacityForBuilding) . '.');
                }
            }
            if(($multiplePairingLimit = $building->getPerkValue('multiple_pairing_limit')))
            {
                /*
                *   $pairingBuildings are the building on which the $building we're building is limited by.
                *   $building is the building we're building.
                */

                $multiplePairingLimit = explode(',', $multiplePairingLimit);
                $chunkSize = (float)$multiplePairingLimit[0];
                $buildingKeys = (array)explode(';', $multiplePairingLimit[1]);
                $pairingBuildings = [];

                $pairingBuildingsOwned = 0;

                foreach($buildingKeys as $buildingKey)
                {
                    $pairingBuilding = Building::where('key', $buildingKey)->firstOrFail();
                    $pairingBuildingRecord = $dominion->buildings()->where('key', $pairingBuilding->key)->first();
                    $pairingBuildingsOwned += $pairingBuildingRecord->pivot->owned ?? 0;
                    $pairingBuildings[] = $pairingBuilding->name;
                }

                $buildingOwnedAndUnderConstruction = $dominion->buildings()->where('key', $building->key)->first()->pivot->owned ?? 0;
                $buildingOwnedAndUnderConstruction += $this->queueService->getConstructionQueueTotalByResource($dominion, "building_{$building->key}");
                $buildingOwnedAndUnderConstruction += $this->queueService->getRepairQueueTotalByResource($dominion, "building_{$building->key}");
                $buildingOwnedAndUnderConstruction += $this->queueService->getInvasionQueueTotalByResource($dominion, "building_{$building->key}");

                $maxCapacity = (int)floor($pairingBuildingsOwned / $chunkSize);

                $availableCapacityForBuilding = max($maxCapacity - $buildingOwnedAndUnderConstruction, 0);

                if($amount > $availableCapacityForBuilding)
                {
                    #throw new GameException('You cannot build ' . number_format($amount) . ' more ' . Str::plural($building->name, $amount) . ' because you only have enough ' . generate_sentence_from_array($pairingBuildings) . ' for ' . number_format($availableCapacityForBuilding) . ' more ' . Str::plural($building->name, $availableCapacityForBuilding) . '.');
                }
            }


            $primaryCost = $this->constructionCalculator->getConstructionCostPrimary($dominion);# * $totalBuildingsToConstruct;
            $secondaryCost = $this->constructionCalculator->getConstructionCostSecondary($dominion);# * $totalBuildingsToConstruct;

            $primaryCostTotal += $amount * $primaryCost;
            $secondaryCostTotal += $amount * $secondaryCost;
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

            $this->statsService->updateStat($dominion, 'buildings_built', array_sum($data));

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
