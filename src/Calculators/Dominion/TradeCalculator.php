<?php

// We want strict types here.
declare(strict_types=1);

namespace OpenDominion\Calculators\Dominion;

use Log;

use Illuminate\Support\Collection;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hold;
use OpenDominion\Models\Resource;
use OpenDominion\Models\TradeRoute;

use OpenDominion\Calculators\HoldCalculator;
use OpenDominion\Calculators\Hold\ResourceCalculator as HoldResourceCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator as DominionResourceCalculator;

class TradeCalculator
{

    protected $holdCalculator;
    protected $dominionResourceCalculator;
    protected $holdResourceCalculator;

    public function __construct()
    {
        $this->holdCalculator = app(HoldCalculator::class);
        $this->dominionResourceCalculator = app(DominionResourceCalculator::class);
        $this->holdResourceCalculator = app(HoldResourceCalculator::class);
    }

    public function getTradeOfferValue(Resource $resource, int $amount): float
    {
        $resourceValue = $resource->trade->buy;

        return round($resourceValue * $amount, config('trade.price_decimals'));
    }

    public function getMaxTradeValue(Dominion $dominion, Resource $resource): int
    {
        return config('trade.trade_base_max');
    }

    public function getTradeMaxAmount(Dominion $dominion, Resource $resource): int
    {
        $resourceValue = $resource->trade->buy;
        $maxTradeValue = $this->getMaxTradeValue($dominion, $resource);

        $maxTradeAmount = (int)floor($maxTradeValue / $resourceValue);

        return $maxTradeAmount;
    }

    public function getMaxTradeValueForAllResources(Dominion $dominion): array
    {
        $result = [];

        foreach(Resource::where('enabled', 1)->get() as $resource)
        {
            $result[$resource->key] = $this->getTradeMaxAmount($dominion, $resource);
        }

        return $result;
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

    public function getTradeResult(Dominion $dominion, Hold $hold, Resource $soldResource, int $soldResourceAmount, Resource $boughtResource): array
    {

        /*
        *   The dominion is SELLING $soldResourceAmount of $soldResource to the hold.
        *   The dominion is BUYING $boughtResource from the hold.
        */

        $result = [
            'sold_resource_key' => $soldResource->key,
            'sold_resource_amount' => (int)$soldResourceAmount,
            'bought_resource_key' => $boughtResource->key,
            'bought_resource_amount' => 0,
            'expected_resource_amount' => 0,
        ];

        $expectedAmount = $this->getBoughtResourceAmount($dominion, $hold, $soldResource, $soldResourceAmount, $boughtResource);
        $result['expected_resource_amount'] = $expectedAmount;

        $canHoldAffordTrade = $this->canHoldAffordTrade($hold, $boughtResource, $expectedAmount);
        $canDominionAffordTrade = $this->canDominionAffordTrade($dominion, $soldResource, $soldResourceAmount);

        if(!$canHoldAffordTrade)
        {
            $result['error']['reason'] = 'hold_insufficient_resources';
            $result['hold_can_afford_trade'] = false;
            $result['error']['details']['hold_production'] = $this->holdResourceCalculator->getProduction($hold, $boughtResource->key);
            $result['error']['details']['hold_stockpile'] = $hold->{'resource_' . $boughtResource->key};
            $result['error']['details']['hold_total_available'] = $result['error']['details']['hold_production'] + $result['error']['details']['hold_stockpile'];
            return $result;
        }
        else
        {
            $result['hold_can_afford_trade'] = true;
        }

        if(!$canDominionAffordTrade)
        {
            $result['error']['reason'] = 'dominion_insufficient_resources';
            $result['dominion_can_afford_trade'] = false;
            $result['error']['details']['dominion_production'] = $this->dominionResourceCalculator->getProduction($dominion, $soldResource->key);
            $result['error']['details']['dominion_stockpile'] = $dominion->{'resource_' . $soldResource->key};
            $result['error']['details']['dominion_total_available'] = $result['error']['details']['dominion_production'] + $result['error']['details']['dominion_stockpile'];
            return $result;
        }
        else
        {
            $result['dominion_can_afford_trade'] = true;
        }

        $result['bought_resource_amount'] = (int)$expectedAmount;

        return $result;
    }

    public function getBoughtResourceAmount(Dominion $dominion, Hold $hold, Resource $soldResource, int $soldResourceAmount, Resource $boughtResource): int
    {

        $soldResourceAmount = (int)min($soldResourceAmount, $hold->{'resource_' . $soldResource->key});

        $netAmount = $soldResourceAmount * $this->getResourceBuyPrice($dominion, $hold, $boughtResource, $soldResource);

        #if($netAmount > $hold->{'resource_' . $boughtResource->key})
        #{
        #    return 0;
        #}

        return floorInt($netAmount);

        #$netAmount = min($netAmount, $hold->{'resource_' . $boughtResource->key});

        #return (int)max(0, $netAmount);
    }

    public function getResourceBuyPrice(Dominion $dominion, Hold $hold, Resource $boughtResource, Resource $soldResource): float
    {
        $price = $hold->buyPrice($soldResource->key) * $hold->sellPrice($boughtResource->key);
        $multiplier = $this->holdCalculator->getSentimentMultiplier($hold, $dominion, $soldResource->key);

        return round($price * $multiplier, config('trade.price_decimals'));
    }

    public function getResourceMaxOfferableAmount(Dominion $dominion, Resource $resource, int $currentAmountSold = 0): int
    {
        $max = 0;
        $isProducingResource = $this->dominionResourceCalculator->isProducingResource($dominion, $resource->key);

        $resourceNetProduction = $this->dominionResourceCalculator->getNetProduction($dominion, $resource->key);

        # If $currentAmountSold > 0, the user is editing the trade route.
        # In that case, add the current amount sold to the netProduction.
        if($currentAmountSold > 0)
        {
            $resourceNetProduction += $currentAmountSold;
        }

        if($isProducingResource and $resourceNetProduction <= 0)
        {
            return 0;
        }

        $resourceCurrentAmount = $dominion->{'resource_' . $resource->key};

        $max = $isProducingResource ? $resourceNetProduction : $resourceCurrentAmount;

        return $max;
    }

    public function getTradeRoutesTickData(Dominion $dominion): Collection
    {
        $tradeRoutes = TradeRoute::where('dominion_id', $dominion->id)->get()->sortBy('id');
    
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
            ->whereIn('status', [0,1])
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

        $hold = $tradeRoute->hold;
        $dominion = $tradeRoute->dominion;
        $soldResource = $tradeRoute->soldResource;
        $soldResourceAmount = $tradeRoute->source_amount;
        $boughtResource = $tradeRoute->boughtResource;
        $expectedAmount = $tradeRoute->source_amount;

        $canHoldAffordTrade = $this->canHoldAffordTrade($hold, $boughtResource, $expectedAmount);
        $canDominionAffordTrade = $this->canDominionAffordTrade($dominion, $soldResource, $soldResourceAmount);

        if(!$canHoldAffordTrade)
        {
            return 0;
        }

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
        $landPerTradeSlot = config('trade.land_per_trade_slot');
        $landPerTradeSlot *= (1 + $dominion->getAdvancementPerkMultiplier('land_per_trade_slot'));

        $slots = $dominion->land / $landPerTradeSlot;

        return floorInt($slots);
    }

    public function getUsedTradeRouteSlots(Dominion $dominion): int
    {
        return $dominion->tradeRoutes()->where('status', 1)->count();
    }

    public function getAvailableTradeRouteSlots(Dominion $dominion): int
    {
        return $this->getTradeRouteSlots($dominion) - $this->getUsedTradeRouteSlots($dominion);
    }

    public function canHoldAffordTrade(Hold $hold, Resource $boughtResource, int $amountToBeExported): bool
    {

        # boughtResource = bought BY THE PLAYER

        $stockpile = $hold->{'resource_' . $boughtResource->key};
        $production = $this->holdResourceCalculator->getProduction($hold, $boughtResource->key);

        return ($production + $stockpile) >= $amountToBeExported;
    }

    public function canDominionAffordTrade(Dominion $dominion, Resource $soldResource, int $soldAmount): bool
    {

        # What the hold buys and the PLAYER SELLS

        $stockpile = $dominion->{'resource_' . $soldResource->key};
        $production = $this->dominionResourceCalculator->getProduction($dominion, $soldResource->key);

        #Log::info("[CAN DOMINION AFFORD TRADE] Dominion: {$dominion->name} / Stockpile: $stockpile / Production: $production / Sold: $soldAmount");

        return ($production + $stockpile) >= $soldAmount;
    }
}
