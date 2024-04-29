<?php

namespace OpenDominion\Services;

use OpenDominion\Models\Hold;
use OpenDominion\Models\HoldSentiment;
use OpenDominion\Models\HoldSentimentEvent;
use OpenDominion\Models\HoldPrice;
use OpenDominion\Models\Round;
use OpenDominion\Models\Resource;

use OpenDominion\Calculators\HoldCalculator;
use OpenDominion\Helpers\HoldHelper;

class HoldService
{

    protected $holdCalculator;
    protected $holdHelper;

    public function __construct()
    {
        $this->holdCalculator = app(HoldCalculator::class);
        $this->holdHelper = app(HoldHelper::class);
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

    /*
    public function updateAllHoldSentiments(Round $round): void
    {
        foreach ($round->holds as $hold)
        {
            foreach($round->dominions as $dominion)
            {
                $this->updateHoldSentiment($hold, $dominion);
            }
        }
    }

    public function updateHoldSentiment(Hold $hold, $target): void
    {
        $holdSentiment = HoldSentimentEvent::sum('sentiment', ['hold_id' => $hold->id, 'target_type' => get_class($target), 'target_id' => $target->id]);

        HoldSentiment::updateOrCreate(['hold_id' => $hold->id, 'target_type' => get_class($target), 'target_id' => $target->id], ['sentiment' => $holdSentiment]);

    }
    */

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

}