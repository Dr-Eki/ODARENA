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


use OpenDominion\Services\Dominion\BuildingService;
use OpenDominion\Services\Dominion\ResourceService as DominionResourceService;
use OpenDominion\Services\Hold\ResourceService as HoldResourceService;

use Throwable;

class TickChangeService
{
    protected $buildingService;
    protected $dominionResourceService;
    protected $holdResourceService;

    /**
     * TickService constructor.
     */
    public function __construct()
    {
        $this->buildingService = app(BuildingService::class);
        $this->dominionResourceService = app(DominionResourceService::class);
        $this->holdResourceService = app(HoldResourceService::class);

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
        $tickChangesHoldResources = $tickChanges->where('target_type', Hold::class)->where('source_type', Resource::class);

        $this->commitDominionResources($tickChangesDominionResources);
        $this->commitDominionBuildings($tickChangesDominionBuildings);
        $this->commitHoldResources($tickChangesHoldResources);
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
                $currentAmount = $dominion->{$resourceKey};
                if($amount < 0 && abs($amount) > $currentAmount)
                {
                    $amount = -$currentAmount;
                }

                $this->dominionResourceService->update($dominion, [$resourceKey => $amount]);
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
                $this->buildingService->update($dominion, [$buildingKey => $amount]);
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
                $currentAmount = $hold->{$resourceKey};
                if($amount < 0 && abs($amount) > $currentAmount)
                {
                    $amount = -$currentAmount;
                }

                xtLog("[{$holdId}] *** Committing for tick: Resource: {$resourceKey}, Amount: {$amount}");
                $this->holdResourceService->update($hold, [$resourceKey => $amount]);
            }
        }

        $tickChangesHoldResources->each(function ($tickChange) {
            $tickChange->update(['status' => 1]);
        });
    }
}
