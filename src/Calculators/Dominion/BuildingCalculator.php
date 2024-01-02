<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use Illuminate\Support\Collection;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Building;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionBuilding;
#use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Services\Dominion\QueueService;

class BuildingCalculator
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var QueueService */
    protected $queueService;

    #/** @var LandCalculator */
    #protected $landCalculator;

    /**
     * BuildingCalculator constructor.
     *
     * @param BuildingHelper $buildingHelper
     * @param QueueService $queueService
     */
    public function __construct(
        BuildingHelper $buildingHelper,
        #LandCalculator $landCalculator,
        QueueService $queueService)
    {
        $this->buildingHelper = $buildingHelper;
        #$this->landCalculator = $landCalculator;
        $this->queueService = $queueService;
    }

    public function getBuildingsLost(Dominion $dominion, int $landLost): array
    {
        $buildingsLost = [
            'available' => array_fill_keys(Building::pluck('key')->toArray(), 0),
            'queued' => array_fill_keys(Building::pluck('key')->toArray(), 0)
        ];
    
        if ($dominion->race->getPerkValue('indestructible_buildings'))
        {
            return $buildingsLost;
        }
    
        $builtLand = $this->getTotalBuildings($dominion);
        $queuedBuildingsAmount = $this->queueService->getConstructionQueueTotal($dominion);
        $unbuiltLand = $dominion->land - $builtLand - $queuedBuildingsAmount;
        $buildingsToDestroy = $landLost - $unbuiltLand;

        // If there are no buildings to destroy, return empty array
        if ($buildingsToDestroy <= 0)
        {
            $buildingsLost['queued'] = array_filter($buildingsLost['queued']);
            $buildingsLost['available'] = array_filter($buildingsLost['available']);
            return $buildingsLost;
        }
    
        // First, take into account the queued buildings
        $buildingsLeftToLose = $buildingsToDestroy;
        foreach ($buildingsLost['queued'] as $buildingKey => $buildingAmount)
        {
            if($buildingsLeftToLose <= 0)
            {
                break;
            }

            $queuedBuildingAmount = $this->queueService->getConstructionQueueTotalByResource($dominion, 'building_' . $buildingKey);
            $buildingAmountLost = min($queuedBuildingAmount, $buildingsLeftToLose);
            
            $buildingsLost['queued'][$buildingKey] += $buildingAmountLost;

            $buildingsLeftToLose -= $buildingAmountLost;
            $buildingsLeftToLose = max(0, $buildingsLeftToLose);
        }
    
        // Then, take into account the available buildings
        if ($buildingsLeftToLose > 0 && $builtLand > 0)
        {
            foreach ($dominion->buildings as $dominionBuilding)
            {
                if ($dominionBuilding->pivot->owned > 0)
                {
                    $buildingsLost['available'][$dominionBuilding->key] = (int)round($buildingsLeftToLose * ($dominionBuilding->pivot->owned / $builtLand));
                }
            }
        }
    
        // Filter out buildings with zero value
        $buildingsLost['queued'] = array_filter($buildingsLost['queued']);
        $buildingsLost['available'] = array_filter($buildingsLost['available']);
    
        return $buildingsLost;
    }
    

    /*
    public function getBuildingsLost(Dominion $dominion, int $landLost): array
    {
        $buildingsLost = [
            'available' => [],
            'queued' => []
        ];

        if($dominion->race->getPerkValue('indestructible_buildings'))
        {
            return $buildingsLost;
        }
    
        $totalBuildings = $dominion->buildings->map(function ($building) {
            return $building->pivot->owned;
        })->sum();

        $barrenLand = $dominion->land - $this->getTotalBuildings($dominion);
        $buildingsToDestroy = max(0, $landLost - $barrenLand);

        if($buildingsToDestroy <= 0)
        {
            return $buildingsLost;
        }
    
        // First, take into account the queued buildings
        $buildingsLost['queued'] = array_fill_keys(Building::pluck('key')->toArray(), 0);
        #$rezoningQueueTotal = $this->queueService->getRezoningQueueTotal($dominion);
    
        $buildingsLeftToLose = $buildingsToDestroy;
        $lastNonZeroBuildingKey = null;
        foreach ($buildingsLost['queued'] as $buildingKey => $buildingAmount) {
            $queuedBuildingAmount = $this->queueService->getConstructionQueueTotalByResource($dominion, 'building_' . $buildingKey);
            $buildingsLost['queued'][$buildingKey] = min($queuedBuildingAmount, $buildingsLeftToLose);
            $buildingsLeftToLose -= $buildingsLost['queued'][$buildingKey];
    
            if ($buildingsLost['queued'][$buildingKey] > 0) {
                $lastNonZeroBuildingKey = $buildingKey;
            }
        }
    
        // Then, take into account the available buildings
        if ($buildingsLeftToLose > 0 && $totalBuildings > 0) {
            foreach ($dominion->buildings as $index => $dominionBuilding) {
                if ($dominionBuilding->pivot->owned > 0) {
                    $buildingsLost['available'][$dominionBuilding->key] = intval(round($buildingsLeftToLose * ($dominionBuilding->pivot->owned / $totalBuildings)));
                }
            }
        }
    
        $buildingsLost['queued'] = array_filter($buildingsLost['queued'], function ($value) {
            return $value !== 0;
        });
   
        return $buildingsLost;
    }
    */
    
    # BUILDINGS VERSION 2
    public function getTotalBuildings(Dominion $dominion): int
    {
        return $dominion->buildings->map(function ($building) {
            return $building->pivot->owned;
        })->sum();
    }

    public function dominionHasBuilding(Dominion $dominion, string $buildingKey): bool
    {
        $building = Building::where('key', $buildingKey)->first();

        if(!$building)
        {
            return false;
        }

        return DominionBuilding::where('building_id',$building->id)->where('dominion_id',$dominion->id)->first() ? true : false;
    }

    public function createOrIncrementBuildings(Dominion $dominion, array $buildingKeys): void
    {
        foreach($buildingKeys as $buildingKey => $amount)
        {
            if($amount > 0)
            {
                $building = Building::where('key', $buildingKey)->first();
                $amount = (int)max(0, $amount);

                if($this->dominionHasBuilding($dominion, $buildingKey))
                {
                    DB::transaction(function () use ($dominion, $building, $amount)
                    {
                        DominionBuilding::where('dominion_id', $dominion->id)->where('building_id', $building->id)
                        ->increment('owned', $amount);
                    });
                }
                else
                {
                    DB::transaction(function () use ($dominion, $building, $amount)
                    {
                        DominionBuilding::create([
                            'dominion_id' => $dominion->id,
                            'building_id' => $building->id,
                            'owned' => $amount
                        ]);
                    });
                }
            }
        }
    }

    public function removeBuildings(Dominion $dominion, array $buildingKeys): void
    {
        foreach($buildingKeys as $buildingKey => $amountToDestroy)
        {
            if($amountToDestroy > 0)
            {
                $building = Building::where('key', $buildingKey)->first();
                $amountToDestroy = intval($amountToDestroy);

                if($this->dominionHasBuilding($dominion, $buildingKey))
                {
                    DB::transaction(function () use ($dominion, $building, $amountToDestroy)
                    {
                        DominionBuilding::where('dominion_id', $dominion->id)->where('building_id', $building->id)
                        ->decrement('owned', $amountToDestroy);
                    });
                }
            }
        }
    }

    public function getDominionBuildings(Dominion $dominion, string $landType = null): Collection
    {
        $dominionBuildings = DominionBuilding::where('dominion_id',$dominion->id)->get();

        if($landType)
        {
            foreach($dominionBuildings as $dominionBuilding)
            {
                $building = Building::where('id', $dominionBuilding->building_id)->first();

                if($building->land_type !== $landType)
                {
                    $dominionBuildings->forget($dominionBuilding->building_id);
                }
            }
        }

        return $dominionBuildings;#DominionBuilding::where('dominion_id',$dominion->id)->get();
    }

    public function getDominionBuildingsAvailableAndOwned($dominion)
    {
        $buildings = $this->buildingHelper->getBuildingsByRace($dominion->race);
    
        foreach ($dominion->buildings as $dominionBuilding)
        {
            $building = Building::where('key', $dominionBuilding->key)->first();
    
            if ($building && !$buildings->contains('id', $building->id)) {
                $buildings->push($building);
            }
        }
    
        return $buildings->sortBy('name');
    }

    /*
    *   Returns an integer ($owned) of how many of this building the dominion has.
    *   Three arguments are permitted and evaluated in order:
    *   Building $building - if we pass a Building object
    *   string $buildingKey - if we pass a building key
    *   int $buildingId - if we pass a building ID
    *
    */
    public function getBuildingAmountOwned(Dominion $dominion, Building $building = null, string $buildingKey = null, int $buildingId = null): int
    {
        $owned = 0;

        $dominionBuildings = $this->getDominionBuildings($dominion);

        if($building)
        {
            $building = $building;
        }
        elseif($buildingKey)
        {
            $buildingKey = str_replace('building_', '', $buildingKey); # Legacy, in case of building_something is provided
            $building = Building::where('key', $buildingKey)->first();

        }
        elseif($buildingId)
        {
            $building = Building::where('id', $buildingId)->first();
        }

        if($dominionBuildings->contains('building_id', $building->id))
        {
            return $dominionBuildings->where('building_id', $building->id)->first()->owned;
        }
        else
        {
            return 0;
        }
    }

    public function buildingHasCapacityLimit(Building $building): bool
    {
        foreach($building->perks as $perk)
        {
            if($perk->key === 'building_capacity_limit')
            {
                return true;
            }
        }
        return false;
    }

    public function getBuildingCapacityLimit(Dominion $dominion, Building $building): int
    {
        # Check if building has building_capacity_limit perk
        $buildingCapacityLimitPerk = $building->perks->filter(function ($perk) {
            return $perk->key === 'building_capacity_limit';
        })->first();

        $buildingCapacityLimitPerk = explode(',', $buildingCapacityLimitPerk->pivot->value);

        $maxOfThisBuilding = (float)$buildingCapacityLimitPerk[0];
        $perOfLimitingBuilding = (float)$buildingCapacityLimitPerk[1];
        $limitingBuildingKey = (string)$buildingCapacityLimitPerk[2];
        $limitingBuilding = Building::where('key', $limitingBuildingKey)->firstOrFail();

        # How many of the $limitingBuilding does the $dominion have?
        $limitingBuildingsOwned = $this->getBuildingAmountOwned($dominion, $limitingBuilding);

        $maxCapacity = $limitingBuildingsOwned * $perOfLimitingBuilding;

        return $maxCapacity;
    }

    public function getAvailableCapacityForBuilding(Dominion $dominion, Building $building): int
    {
        $maxCapacity = $this->getBuildingCapacityLimit($dominion, $building);
        $owned = $this->getBuildingAmountOwned($dominion, $building);
        $owned += $this->queueService->getConstructionQueueTotalByResource($dominion, ('building_' . $building->key));

        return $maxCapacity - $owned;
    }

    public function getHolyLandAmount(Dominion $dominion): int
    {
        $holyLandCount = 0;

        foreach($dominion->buildings as $dominionBuilding)
        {
            $building = Building::where('key', $dominionBuilding->key)->first();

            if(isset($building->deity) and $dominion->hasDeity())
            {
                if($dominion->deity->id == $building->deity->id)
                {
                    $holyLandCount += $dominionBuilding->pivot->owned;
                }
            }
        }

        return $holyLandCount;
    }

    public function getHolyLandRatio(Dominion $dominion): float
    {
        return $this->getHolyLandAmount($dominion) / $dominion->land;
    }

}
