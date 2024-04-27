<?php

namespace OpenDominion\Calculators\Dominion;


use Illuminate\Support\Collection;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hold;
use OpenDominion\Models\Resource;
use OpenDominion\Models\TradeRoute;

class TradeCalculator
{

    protected $resourceCalculator;

    public function __construct()
    {
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
        return in_array($resource->key, $dominion->resourceKeys());
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
        return ($soldResourceAmount * $hold->buyPrice($soldResource->key)) * $hold->sellPrice($boughtResource->key);
    }

    public function getResourceMaxOfferableAmount(Dominion $dominion, Resource $resource): int
    {
        $max = 0;
        $netProductionPerResource = $this->resourceCalculator->getResourceNetProduction($dominion, $resource->key);

        $resourceCurrentAmount = $dominion->{'resource_' . $resource->key};

        $isProducingResource = $netProductionPerResource > 0;

        $max = $isProducingResource ? $netProductionPerResource : $resourceCurrentAmount;

        return $max;
    }

    public function getAllResourcesMaxOfferableAmount(Dominion $dominion): array
    {
        $maxOfferableAmounts = [];

        foreach ($dominion->resourceKeys() as $resourceKey) {
            $resource = Resource::fromKey($resourceKey);
            $maxOfferableAmounts[$resourceKey] = $this->getResourceMaxOfferableAmount($dominion, $resource);
        }

        return $maxOfferableAmounts;
    }

    public function getTradeRoutesTickData(Dominion $dominion): Collection
    {
        $tradeRoutes = $dominion->tradeRoutes()->where('status', 1)->get()->sortBy('id');
    
        $data = collect();
    
        foreach ($tradeRoutes->groupBy('hold_id') as $holdTradeRoutes) {
            foreach ($holdTradeRoutes as $holdTradeRoute) {
                for ($tick = 1; $tick <= 12; $tick++) {
                    $holdTradeRouteQueueData = $holdTradeRoute->queues->where('tick', $tick);#->where('amount', '>', 0);
                    foreach ($holdTradeRouteQueueData as $queue) {
                        $holdKey = $holdTradeRoute->hold->key;
                        $resourceKey = $queue->resource->key;
                        $type = $queue->type;
                        $amount = $queue->amount;
    
                        // Initialize the nested structure if not already set
                        $data->put($holdKey, collect($data->get($holdKey, collect()))); // Ensure there's a collection for this hold
                        $data[$holdKey]->put($tick, collect($data[$holdKey]->get($tick, collect()))); // Ensure there's a collection for this tick
                        $data[$holdKey][$tick]->put($resourceKey, collect($data[$holdKey][$tick]->get($resourceKey, collect()))); // Ensure there's a collection for this resource
                        $data[$holdKey][$tick][$resourceKey]->put($type, $data[$holdKey][$tick][$resourceKey]->get($type, 0) + $amount); // Sum up the amount for this type
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

        $resources = collect();

        foreach($tradeRoutes as $tradeRoute)
        {
            $resources->push($tradeRoute->soldResource->key);
            $resources->push($tradeRoute->boughtResource->key);

        }

        return $resources->unique();
    }



}
