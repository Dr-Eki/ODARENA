<?php

namespace OpenDominion\Calculators\Dominion;


use Illuminate\Support\Collection;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hold;
use OpenDominion\Models\Resource;
use OpenDominion\Models\TradeRoute;

use OpenDominion\Calculators\HoldCalculator;

class TradeCalculator
{

    protected $holdCalculator;
    protected $resourceCalculator;

    public function __construct()
    {
        $this->holdCalculator = app(HoldCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
    }

    public function canDominionTradeWithHold(Dominion $dominion, Hold $hold): bool
    {
        return $this->canDominionBuyAnyResourcesFromHold($dominion, $hold) && $this->canDominionSellAnyResourcesToHold($dominion, $hold);
    }

    public function canDominionBuyAnyResourcesFromHold(Dominion $dominion, Hold $hold): bool
    {
        return count(array_intersect($dominion->resourceKeys(), $hold->sold_resources)) > 0;
    }

    public function canDominionSellAnyResourcesToHold(Dominion $dominion, Hold $hold): bool
    {
        return count(array_intersect($dominion->resourceKeys(), $hold->desired_resources)) > 0;
    }

    public function canDominionTradeResource(Dominion $dominion, Resource $resource): bool
    {
        return true;
        #return in_array($resource->key, $dominion->resourceKeys()) or $resource->category == 'luxury';
    }

    #$tradeData = $this->tradeCalculator->getTradeResult($dominion, $hold, $soldResource, $soldResourceAmount, $boughtResource);
    public function getTradeResult(Dominion $dominion, Hold $hold, Resource $soldResource, int $soldResourceAmount, Resource $boughtResource): array
    {

        /*
        *   The dominion is SELLING $soldResourceAmount of $soldResource to the hold.
        *   The dominion is BUYING $boughtResource from the hold.
        */

        $result = [
            'sold_resource_key' => $soldResource->key,
            'sold_resource_amount' => $soldResourceAmount,
            'bought_resource_key' => $boughtResource->key,
            'bought_resource_amount' => 0,
        ];

        $result['bought_resource_amount'] = $this->getBoughtResourceAmount($dominion, $hold, $soldResource, $soldResourceAmount, $boughtResource);

        return $result;
    }

    public function getBoughtResourceAmount(Dominion $dominion, Hold $hold, Resource $soldResource, int $soldResourceAmount, Resource $boughtResource): int
    {
        $baseAmount = $soldResourceAmount * $hold->buyPrice($soldResource->key) * $hold->sellPrice($boughtResource->key);
        $multiplier = $this->holdCalculator->getSentimentMultiplier($hold, $dominion, $soldResource->key);

        $netAmount = floor($baseAmount * $multiplier);

        $netAmount = min($netAmount, $hold->{'resource_' . $boughtResource->key});

        return max(0, $netAmount);
    }

    public function getResourceMaxOfferableAmount(Dominion $dominion, Resource $resource): int
    {
        $max = 0;
        $resourceNetProduction = $this->resourceCalculator->getNetProduction($dominion, $resource->key);

        $resourceCurrentAmount = $dominion->{'resource_' . $resource->key};

        $isProducingResource = $this->resourceCalculator->isProducingResource($dominion, $resource->key);

        $max = $isProducingResource ? $resourceNetProduction : $resourceCurrentAmount;

        return $max;
    }

    public function getTradeRoutesTickData(Dominion $dominion): Collection
    {
        $tradeRoutes = TradeRoute::where('dominion_id', $dominion->id)->get()->sortBy('id');#$dominion->tradeRoutes()->where('status', 1)->get()->sortBy('id');
    
        $data = collect();
    
        foreach ($tradeRoutes->groupBy('hold_id') as $holdTradeRoutes) {
            foreach ($holdTradeRoutes as $holdTradeRoute) {
                for ($tick = 1; $tick <= 12; $tick++) {
                    $holdTradeRouteQueueData = $holdTradeRoute->queues->where('tick', $tick);#->where('amount', '>', 0);
                    foreach ($holdTradeRouteQueueData as $queue) {
                        $holdId = $holdTradeRoute->hold->id;
                        $resourceKey = $queue->resource->key;
                        $type = $queue->type;
                        $amount = $queue->amount;
    
                        // Initialize the nested structure if not already set
                        $data->put($holdId, collect($data->get($holdId, collect()))); // Ensure there's a collection for this hold
                        $data[$holdId]->put($tick, collect($data[$holdId]->get($tick, collect()))); // Ensure there's a collection for this tick
                        $data[$holdId][$tick]->put($resourceKey, collect($data[$holdId][$tick]->get($resourceKey, collect()))); // Ensure there's a collection for this resource
                        $data[$holdId][$tick][$resourceKey]->put($type, $data[$holdId][$tick][$resourceKey]->get($type, 0) + $amount); // Sum up the amount for this type
                    }
                }
            }
        }
    
        return $data;
    }

    public function getResourcesTradedBetweenDominionAndHold(Dominion $dominion, Hold $hold): Collection
    {

        $tradeRoutes = TradeRoute::where('dominion_id', $dominion->id)
            ->where('hold_id', $hold->id)
            ->where('status', 1)
            ->get();

        #dump($tradeRoutes);

        $resources = collect();

        foreach($tradeRoutes as $tradeRoute)
        {
            #ldump($tradeRoute);
            $resources->push($tradeRoute->soldResource->key);
            $resources->push($tradeRoute->boughtResource->key);

        }

        #ldump($resources);

        return $resources->unique();
    }

    // Sentiment
    public function getTradeRouteCancellationSentimentPenalty(TradeRoute $tradeRoute, string $reason): int
    {
        $tradeRouteTicks = $tradeRoute->ticks;
        $currentTick = $tradeRoute->dominion->round->ticks;
        $tradeRouteDuration = $tradeRouteTicks - $currentTick;

        $penalty = 0;

        if($reason == 'cancelled_by_dominion' or $reason == 'dominion_insufficient_resources')
        {
            if ($tradeRouteDuration < 24)
            {
                $penalty += 200;
            }
            elseif($tradeRouteDuration < 48)
            {
                $penalty += 48;
            }
            elseif($tradeRouteDuration < 72)
            {
                $penalty += 24;
            }
            elseif($tradeRouteDuration > 192)
            {
                $penalty += 18;
            }
            elseif($tradeRouteDuration > 384)
            {
                $penalty += 24;
            }
            elseif($tradeRouteDuration > 480)
            {
                $penalty += 48;
            }
            else
            {
                $penalty += 12;
            }

            # Does the hold have any resources left?
            if($tradeRoute->hold->{'resource_' . $tradeRoute->boughtResource->key} == 0)
            {
                $penalty = 0;
            }
        }

        return $penalty;
    }

    public function getTradeRouteSlots(Dominion $dominion): int
    {
        $slots = 0;

        $startingSlots = 3;
        $startingSlots += $dominion->race->getPerkValue('trade_route_slots');

        $slots += $startingSlots;

        $slots += $dominion->getAdvancementPerkValue('trade_route_slots') / 100;

        $slots += max($dominion->land - config('game.starting_land'), 0) / 500;

        return (int)floor($slots);
    }

    public function getUsedTradeRouteSlots(Dominion $dominion): int
    {
        return $dominion->tradeRoutes()->where('status', 1)->count();
    }

    public function getAvailableTradeRouteSlots(Dominion $dominion): int
    {
        return $this->getTradeRouteSlots($dominion) - $this->getUsedTradeRouteSlots($dominion);
    }

}
