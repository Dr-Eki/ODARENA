<?php

// We want strict types here.
declare(strict_types=1);

namespace OpenDominion\Services\Dominion;

use DB;
use Illuminate\Support\Collection;
use OpenDominion\Models\Building;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hold;
use OpenDominion\Models\Resource;
use OpenDominion\Models\TickChange;
use OpenDominion\Models\Unit;


use OpenDominion\Services\Dominion\BuildingService as DominionBuildingService;
use OpenDominion\Services\Dominion\ResourceService as DominionResourceService;
use OpenDominion\Services\Dominion\UnitService as DominionUnitService;

use OpenDominion\Services\Hold\BuildingService as HoldBuildingService;
use OpenDominion\Services\Hold\ResourceService as HoldResourceService;
use OpenDominion\Services\Hold\UnitService as HoldUnitService;

use Throwable;

class TickChangeService
{
    protected $dominionBuildingService;
    protected $dominionResourceService;
    protected $dominionUnitService;

    protected $holdBuildingService;
    protected $holdResourceService;
    protected $holdUnitService;

    /**
     * TickService constructor.
     */
    public function __construct()
    {
        $this->dominionBuildingService = app(DominionBuildingService::class);
        $this->dominionResourceService = app(DominionResourceService::class);
        $this->dominionUnitService = app(DominionUnitService::class);

        $this->holdBuildingService = app(HoldBuildingService::class);
        $this->holdResourceService = app(HoldResourceService::class);
        $this->holdUnitService = app(HoldUnitService::class);

    }

    /**
     * Does an hourly tick on all active dominions.
     *
     * @throws Exception|Throwable
     */
    public function commit()
    {
        $tickChanges = TickChange::where('status', 0)->get();

        $tickChangesDominionResources = $tickChanges->where('target_type', Dominion::class)->where('source_type', Resource::class);
        $tickChangesDominionBuildings = $tickChanges->where('target_type', Dominion::class)->where('source_type', Building::class);
        $tickChangesDominionUnits = $tickChanges->where('target_type', Dominion::class)->where('source_type', Unit::class);

        $tickChangesHoldResources = $tickChanges->where('target_type', Hold::class)->where('source_type', Resource::class);
        $tickChangesHoldBuildings = $tickChanges->where('target_type', Hold::class)->where('source_type', Building::class);
        $tickChangesHoldUnits = $tickChanges->where('target_type', Hold::class)->where('source_type', Unit::class);

        $this->commitDominionResources($tickChangesDominionResources);
        $this->commitDominionBuildings($tickChangesDominionBuildings);
        #$this->commitDominionUnits($tickChangesDominionUnits);


        $this->commitHoldResources($tickChangesHoldResources);
        $this->commitHoldBuildings($tickChangesHoldBuildings);
        #$this->commitHoldUnits($tickChangesHoldUnits);
    }

    public function commitForDominion(Dominion $dominion): void
    {
        // Retrieve TickChange records with debug output
        $tickChanges = TickChange::where([
            'target_type' => Dominion::class,
            'target_id' => $dominion->id,
            'status' => 0
        ])->get();
    
        if ($tickChanges->count() == 0)
        {

            xtLog("{$dominion->id} ** No tick changes found for dominion, which is unusual for manual ticks. Initiate retry loop.");

            for ($i = 0; $i < 10; $i++)
            {
                $tickChanges = \OpenDominion\Models\TickChange::where([
                    'target_type' => \OpenDominion\Models\Dominion::class,
                    'target_id' => $dominion->id,
                    'status' => 0
                ])->get();

                xtLog("{$dominion->id} *** Retry loop iteration {$i} found {$tickChanges->count()} tick changes for dominion.");

                if($tickChanges->count() > 0)
                {
                    xtLog("{$dominion->id} *** Found tick changes for dominion after {$i} iterations.");
                    break;
                }

                xtLog("{$dominion->id} *** Sleeping for 100ms and retrying");

                usleep(100000);
            }
        } 
    
        // Separate tick changes by source type
        $tickChangesDominionResources = $tickChanges->where('source_type', Resource::class);
        $tickChangesDominionBuildings = $tickChanges->where('source_type', Building::class);
    
        $isScheduledTick = false;
    
        // Commit the changes
        $this->commitDominionResources($tickChangesDominionResources, $isScheduledTick);
        $this->commitDominionBuildings($tickChangesDominionBuildings, $isScheduledTick);

        // Set all tick changes to status 1
        $tickChanges->each(function ($tickChange) {
            $tickChange->update(['status' => 1]);
        });
    }

    protected function commitDominionResources(Collection $tickChangesDominionResources, bool $isScheduledTick = true): void
    {

        $dominionResourceChanges = [];

        foreach($tickChangesDominionResources as $tickChange)
        {
            $resourceKey = $tickChange->source->key;
            $amount = $tickChange->amount;
            isset($dominionResourceChanges[$tickChange->target_id][$resourceKey]) ? $dominionResourceChanges[$tickChange->target_id][$resourceKey] += $amount : $dominionResourceChanges[$tickChange->target_id][$resourceKey] = $amount;
        }

        foreach($dominionResourceChanges as $dominionId => $resourceData)
        {

            $dominion = Dominion::find($dominionId);

            if($dominion)
            {
                xtLog("[{$dominionId}] ** Committing tick change for dominion resources");
            }
            else
            {
                xtLog("[{$dominionId}] ** Dominion ID ($dominionId) does not correspond to a dominion.");
                continue;
            }
            
            if($dominion->protection_ticks and $isScheduledTick)
            {
                xtLog("[{$dominion->id}] ** Dominion is in protection mode, skipping tick change commit");
                continue;
            }

            foreach($resourceData as $resourceKey => $amount)
            {
                $this->dominionResourceService->update($dominion, [$resourceKey => $amount]); // Contains safety for negative $amount values that exceed current resource amount
            }
        }

        $tickChangesDominionResources->each(function ($tickChange) {
            $tickChange->update(['status' => 1]);
        });
    }

    protected function commitDominionBuildings(Collection $tickChangesDominionBuildings, bool $isScheduledTick = true): void
    {
        $dominionBuildingChanges = [];

        foreach($tickChangesDominionBuildings as $tickChange)
        {
            $buildingKey = $tickChange->source->key;
            $amount = $tickChange->amount;
            isset($dominionBuildingChanges[$tickChange->target_id][$buildingKey]) ? $dominionBuildingChanges[$tickChange->target_id][$buildingKey] += $amount : $dominionBuildingChanges[$tickChange->target_id][$buildingKey] = $amount;
        }

        foreach($dominionBuildingChanges as $dominionId => $buildingData)
        {
            $dominion = Dominion::find($dominionId);

            if($dominion)
            {
                xtLog("[{$dominionId}] ** Committing tick change for dominion buildings");
            }
            else
            {
                xtLog("[{$dominionId}] ** Dominion ID not found for tick change change");
                continue;
            }
            
            if($dominion->protection_ticks and $isScheduledTick)
            {
                xtLog("[{$dominion->id}] ** Dominion is in protection mode, skipping tick change commit");
                continue;
            }

            foreach($buildingData as $buildingKey => $amount)
            {
                xtLog("[{$dominionId}] *** Committing for tick: Building: {$buildingKey}, Amount: {$amount}");
                $this->dominionBuildingService->update($dominion, [$buildingKey => $amount]);
            }
        }

        $tickChangesDominionBuildings->each(function ($tickChange) {
            $tickChange->update(['status' => 1]);
        });
    }

    protected function commitHoldResources(Collection $tickChangesHoldResources): void
    {
        $holdResourceChanges = [];

        foreach($tickChangesHoldResources as $tickChange)
        {
            $resourceKey = $tickChange->source->key;
            $amount = $tickChange->amount;
            isset($holdResourceChanges[$tickChange->target_id][$resourceKey]) ? $holdResourceChanges[$tickChange->target_id][$resourceKey] += $amount : $holdResourceChanges[$tickChange->target_id][$resourceKey] = $amount;
        }

        foreach($holdResourceChanges as $holdId => $resourceData)
        {
            $hold = Hold::find($holdId);

            if($hold)
            {
                xtLog("[{$holdId}] ** Committing tick change for hold resources");
            }
            else
            {
                xtLog("[{$holdId}] ** Hold ID not found for tick change change");
                continue;
            }

            foreach($resourceData as $resourceKey => $amount)
            {
                xtLog("[HL{$holdId}] *** Committing for tick: Resource: {$resourceKey}, Amount: {$amount}");
                $this->holdResourceService->update($hold, [$resourceKey => $amount]);
            }
        }

        $tickChangesHoldResources->each(function ($tickChange) {
            $tickChange->update(['status' => 1]);
        });
    }

    public function commitHoldBuildings(Collection $tickChangesHoldBuildings): void
    {
        $holdBuildingChanges = [];

        foreach($tickChangesHoldBuildings as $tickChange)
        {
            $buildingKey = $tickChange->source->key;
            $amount = $tickChange->amount;
            isset($holdBuildingChanges[$tickChange->target_id][$buildingKey]) ? $holdBuildingChanges[$tickChange->target_id][$buildingKey] += $amount : $holdBuildingChanges[$tickChange->target_id][$buildingKey] = $amount;
        }

        foreach($holdBuildingChanges as $holdId => $buildingData)
        {
            $hold = Hold::find($holdId);

            if($hold)
            {
                xtLog("[{$holdId}] ** Committing tick change for hold buildings");
            }
            else
            {
                xtLog("[{$holdId}] ** Hold ID not found for tick change change");
                continue;
            }

            foreach($buildingData as $buildingKey => $amount)
            {
                xtLog("[HL{$holdId}] *** Committing for tick: Building: {$buildingKey}, Amount: {$amount}");
                $this->holdBuildingService->update($hold, [$buildingKey => $amount]);
            }
        }

        $tickChangesHoldBuildings->each(function ($tickChange) {
            $tickChange->update(['status' => 1]);
        });
    }

}
