<?php

namespace OpenDominion\Services;

use Log;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Hold;
use OpenDominion\Models\HoldSentiment;
use OpenDominion\Models\HoldSentimentEvent;
use OpenDominion\Models\HoldPrice;
use OpenDominion\Models\Round;
use OpenDominion\Models\Resource;

use OpenDominion\Calculators\HoldCalculator;
use OpenDominion\Calculators\Hold\ResourceCalculator;

use OpenDominion\Helpers\HoldHelper;

use OpenDominion\Services\Hold\QueueService;
use OpenDominion\Services\Hold\ResourceService;

class HoldService
{

    protected $holdCalculator;
    protected $holdHelper;
    protected $queueService;
    protected $resourceCalculator;
    protected $resourceService;

    public function __construct()
    {
        $this->holdCalculator = app(HoldCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);

        $this->holdHelper = app(HoldHelper::class);

        $this->queueService = app(QueueService::class);
        $this->resourceService = app(ResourceService::class);
    }

    public function setRoundHoldPrices(Round $round): void
    {
        foreach ($round->holds as $hold)
        {
            $this->setHoldPrices($hold, $round->ticks);
        }
    }

    public function setHoldPrices(Hold $hold, int $tick): void
    {
        $this->setHoldBuyPrices($hold, $tick);
        $this->setHoldSellPrices($hold, $tick);
    }

    public function setHoldBuyPrices(Hold $hold, int $tick): void
    {
        foreach ($hold->resourceKeys() as $resourceKey)
        {
            $this->setHoldBuyPrice($hold, $tick, $resourceKey);
        }
    }

    public function setHoldSellPrices(Hold $hold, int $tick): void
    {
        foreach ($hold->resourceKeys() as $resourceKey)
        {
            $this->setHoldSellPrice($hold, $tick, $resourceKey);
        }
    }

    public function setHoldBuyPrice(Hold $hold, int $tick, string $resourceKey): HoldPrice
    {
        $price = $this->holdCalculator->getNewResourceBuyPrice($hold, $resourceKey);
        $resource = Resource::where('key', $resourceKey)->firstOrFail();

        return HoldPrice::updateOrCreate(['hold_id' => $hold->id, 'resource_id' => $resource->id, 'tick' => $tick, 'action' => 'buy'], ['price' => $price]);
    }

    public function setHoldSellPrice(Hold $hold, int $tick, string $resourceKey): HoldPrice
    {
        $price = $this->holdCalculator->getNewResourceSellPrice($hold, $resourceKey);
        $resource = Resource::where('key', $resourceKey)->firstOrFail();

        return HoldPrice::updateOrCreate(['hold_id' => $hold->id, 'resource_id' => $resource->id, 'tick' => $tick, 'action' => 'sell'], ['price' => $price]);
    }

    public function updateAllHoldSentiments(Round $round): void
    {
        // Assuming that holds and dominions are related to round and are Eloquent collections
        $holdIds = $round->holds->pluck('id');
        $dominionIds = $round->dominions->pluck('id');
    
        // Pre-fetch all HoldSentimentEvents for these holds and dominions
        $sentimentEvents = HoldSentimentEvent::whereIn('hold_id', $holdIds)
                                             ->whereIn('target_id', $dominionIds)
                                             ->get();
    
        // Group events by hold_id and target_id to process
        $groupedEvents = $sentimentEvents->groupBy(function ($item) {
            return $item->hold_id . '-' . $item->target_id;
        });
    
        // Loop through grouped events and sum sentiments, then update/create records
        foreach ($groupedEvents as $groupKey => $events) {
            [$holdId, $dominionId] = explode('-', $groupKey);
            $totalSentiment = $events->sum('sentiment');
            $targetType = $events->first()->target_type; // Assuming all events in group have same target type
    
            HoldSentiment::updateOrCreate(
                ['hold_id' => $holdId, 'target_id' => $dominionId, 'target_type' => $targetType],
                ['sentiment' => $totalSentiment]
            );
        }
    }

    public function handleHoldResourceProduction(Hold $hold): void
    {

        foreach($hold->sold_resources as $resourceKey)
        {
            $amountProduced = $this->resourceCalculator->getProduction($hold, $resourceKey);
            $this->resourceService->update($hold, [$resourceKey => $amountProduced]);
        }

    }

    public function updateHoldLands($round): void
    {
        foreach ($round->holds as $hold)
        {
            $this->updateHoldLand($hold);
        }
    }

    public function updateHoldLand(Hold $hold): void
    {
        $landGrowth = $this->holdCalculator->getTickLandGrowth($hold);

        $hold->land += $landGrowth;
        $hold->save();
    }

    public function handleHoldTick(Round $round): void
    {
        # Update sentiments
        $this->updateAllHoldSentiments($round);

        # Update prices
        $this->setRoundHoldPrices($round);

        # Update resources
        foreach ($round->holds as $hold)
        {
            # Handle queues
            $this->queueService->handleHoldQueues($hold);

            # Handle resource production
            $this->handleHoldResourceProduction($hold);

        }

        # Update hold land
        $this->updateHoldLands($round);

    }

    public function discoverHold(Round $round, Dominion $discoverer = null): ?Hold
    {
        # Are there any undiscovered holds?
        $undiscoveredHolds = $round->holds->where('status',0);

        if($undiscoveredHolds->count() == 0)
        {
            return null;
        }

        # Grab a random undiscovered hold
        $hold = $round->holds->where('status',0)->random();

        if(!$hold)
        {
            return null;
        }

        # Set status to discovered
        $hold->status = 1;

        # Set discovered tick
        $hold->tick_discovered = $round->ticks;

        # Save
        $hold->save();

        # Update hold prices right away
        $this->setHoldPrices($hold, $round->ticks);

        GameEvent::create([
            'round_id' => $round->id,
            'source_type' => Hold::class,
            'source_id' => $hold->id,
            'target_type' => isset($discoverer) ? Dominion::class : null,
            'target_id' => isset($discoverer) ? $discoverer->id : null,
            'type' => 'hold_discovered',
            'data' => '',
            'tick' => $round->ticks
        ]);

        if($discoverer)
        {
            HoldSentimentEvent::add($hold, $discoverer, 100, 'discovered_by_dominion');
        }
 
        return $hold;
    }


}
