<?php

namespace OpenDominion\Calculators;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hold;
use OpenDominion\Models\Resource;

use OpenDominion\Services\Dominion\StatsService;

class HoldCalculator
{

    protected $statsService;

    public function __construct()
    {
        $this->statsService = app(StatsService::class);
    }


    public function getNewResourceBuyPrice(Hold $hold, string $resourceKey): float
    {
        if(!$this->canResourceBeTraded($resourceKey) || /*!$this->canResourceBeTradedByHold($hold, $resourceKey) || */ !in_array($resourceKey, $hold->desired_resources))
        {
            return 0;
        }

        $resource = Resource::where('key', $resourceKey)->firstOrFail();

        $resource = Resource::where('key', $resourceKey)->firstOrFail();
        $price = $resource->trade->buy;
        $price *= $this->getHoldResourcePriceMultiplier($hold, $resourceKey);

        return round($price, config('trade.price_decimals'));
    }

    public function getNewResourceSellPrice(Hold $hold, string $resourceKey): float
    {

        if(!$this->canResourceBeTraded($resourceKey) /*|| !$this->canResourceBeTradedByHold($hold, $resourceKey)*/ || !in_array($resourceKey, $hold->sold_resources))
        {
            return 0;
        }

        $resource = Resource::where('key', $resourceKey)->firstOrFail();
        $price = $resource->trade->sell;
        $price *= $this->getHoldResourcePriceMultiplier($hold, $resourceKey);

        return round($price, config('trade.price_decimals'));
    }

    // This multiplier gets the multiplier specific for this Hold and Resource combination.
    public function getHoldResourcePriceMultiplier(Hold $hold, string $resourceKey): float
    {
        $multiplier = 1;

        if($resourceKey == 'gold')
        {
            return $multiplier;
        }

        $multiplier += $this->getBaseDesirabilityMultiplier($hold, $resourceKey);
        $multiplier += $this->getResourceSupplyMultiplier($hold, $resourceKey);

        return $multiplier;
    }

    public function getBaseDesirabilityMultiplier(Hold $hold, string $resourceKey): float
    {

        $multiplier = 0;

        $isSoldResource = in_array($resourceKey, $hold->sold_resources);
        $isDesiredResource = in_array($resourceKey, $hold->desired_resources);

        if($isSoldResource)
        {
            $multiplier += (8/9);
        }
        if($isDesiredResource)
        {
            $multiplier += (9/8);
        }

        return $multiplier;

    }

    public function getResourceSupplyMultiplier(Hold $hold, string $resourceKey): float
    {

        $resource = Resource::fromKey($resourceKey);

        $goal = config('trade.resource_base_supply_goal')[$resource->category];
        $goal += $hold->round->ticks * config('trade.resource_per_tick_supply_goal')[$resource->category];
        $current = $hold->{'resource_' . $resourceKey};

        $ratio = $current / $goal;

        $multiplier = (1 - $ratio) / 10;

        $multiplier = max(-0.90, $multiplier);

        return $multiplier;
    }

    // This function gets the sentiment multiplier for a specific Dominion and Hold combination (at the moment of trade).
    public function getSentimentMultiplier(Hold $hold, Dominion $dominion, string $resourceKey): float
    {
        $multiplier = 1;

        if($resourceKey == 'gold')
        {
            return $multiplier;
        }

        $sentiment = optional($hold->sentiments->where('target_id', $dominion->id)->first())->sentiment ?? 0;

        return $multiplier += $sentiment / config('trade.sentiment_divisor');
    }



    public function canResourceBeTradedByHold(Hold $hold, string $resourceKey): bool
    {
        $resource = Resource::where('key', $resourceKey)->firstOrFail();
        return in_array($resource->key, $hold->desired_resources) || in_array($resource->key, $hold->sold_resources);
    }

    public function canResourceBeTraded(string $resourceKey): bool
    {
        $resource = Resource::where('key', $resourceKey)->firstOrFail();
        return ($resource->trade->buy > 0 || $resource->trade->sell > 0);
    }

    public function getChanceToDiscoverHoldOnExpedition(Dominion $dominion, array $expedition): float
    {

        if(!$expedition['land_discovered'])
        {
            return 0;
        }

        if(isLocal()) { return 1; }

        $chance = 0;

        $chance += ($dominion->round->ticks / 1344) * (($expedition['land_discovered'] - 2) / 10) * (1 + $this->statsService->getStat($dominion, 'holds_found') / 26);
        
        $chance *= $this->getChanceToDiscoverHoldMultiplier($dominion);

        $chance = min(0.80, $chance);
        $chance = max(0.00, $chance);

        return $chance;
    }

    public function getChanceToDiscoverHoldMultiplier(Dominion $dominion): float
    {
        $multiplier = 1.00;

        $multiplier += $dominion->getImprovementPerkMultiplier('chance_to_discover_hold');
        $multiplier += $dominion->getBuildingPerkMultiplier('chance_to_discover_hold');
        $multiplier += $dominion->getSpellPerkMultiplier('chance_to_discover_hold');
        $multiplier += $dominion->getAdvancementPerkMultiplier('chance_to_discover_hold');
        $multiplier += $dominion->race->getPerkMultiplier('chance_to_discover_hold');

        return $multiplier;
    }

}
