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
        if(!$this->canResourceBeTraded($resourceKey) or !in_array($resourceKey, $hold->desired_resources))
        {
            return 0;
        }

        $resource = Resource::where('key', $resourceKey)->firstOrFail();
        $price = $resource->trade->buy;

        $price *= $this->getHoldResourcePriceMultiplier($hold, $resourceKey);

        return round($price, config('trade.price_decimals'));
    }

    public function getNewResourceSellPrice(Hold $hold, string $resourceKey): float
    {

        if(!$this->canResourceBeTraded($resourceKey) or !in_array($resourceKey, $hold->sold_resources))
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

        // Sentiment multiplier is not used here, it's in the TradeService when $dominion is known
        #$multiplier += $this->getSentimentMultiplier($hold, $dominion, $resourceKey); 

        dump('> Multiplier: ' . $multiplier);

        $multiplier *= $this->getBaseDesirabilityMultiplier($hold, $resourceKey);
        $multiplier += $this->getResourceSupplyMultiplier($hold, $resourceKey);

        dump('> Desirability: ' . $this->getBaseDesirabilityMultiplier($hold, $resourceKey));
        dump('> Supply: ' . $this->getResourceSupplyMultiplier($hold, $resourceKey));
        dump('>> Multiplier: ' . $multiplier);
        #dd($resourceKey, $hold->name);

        return $multiplier;
    }

    public function getBaseDesirabilityMultiplier(Hold $hold, string $resourceKey): float
    {

        $multiplier = 1;

        $isSoldResource = in_array($resourceKey, $hold->sold_resources);
        $isDesiredResource = in_array($resourceKey, $hold->desired_resources);

        if($isSoldResource)
        {
            $multiplier = (8/9);
        }
        elseif($isDesiredResource)
        {
            $multiplier = (9/8);
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

        #dump('* Supply multiplier');
        #dump($current, $goal, $multiplier);

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

    public function getStartingBuildings(Hold $hold): array
    {
        $land = $hold->land;

        $buildingData = [];

        $buildingData = ['farm' => $land * 0.10];
        $buildingData = ['harbour' => $land * 0.10];

        $remainingLand = $land - array_sum($buildingData);

        # How many resources are being sold?
        $soldResource = $hold->sold_resources;

        # Don't count gold.
        if (isset($soldResource['gold']))
        {
            unset($soldResource['gold']);
        }

        $soldResources = count($hold->sold_resources);

        # Land dedicated per resource
        $landPerResource = $remainingLand / $soldResources;

        foreach($hold->sold_resources as $resourceKey)
        {
            $bestMatchingBuilding = $this->getBestMatchingBuilding($hold, $resourceKey);
            isset($buildingData[$bestMatchingBuilding]) ? $buildingData[$bestMatchingBuilding] += (int)round($landPerResource) : $buildingData[$bestMatchingBuilding] = (int)round($landPerResource);
            $remainingLand -= (int)round($landPerResource);
        }

        # Remaining land is dedicated to harbour
        $buildingData['harbour'] += max(0, $remainingLand);

        return $buildingData;
    }

    public function getBestMatchingBuilding(Hold $hold, string $resourceKey): string
    {
        $resourceMap = collect([
            'acid' => 'tissue',
            'ash' => 'ore_mine',
            'blood' => 'altar',
            'body' => 'altar',
            'books' => 'school',
            'brimmer' => 'refinery',
            'figurines' => 'workshop',
            'food' => 'farm',
            'gems' => 'gem_mine',
            'gloom' => 'night_tower',
            'gold' => 'gold_mine',
            'gunpowder' => 'powder_mill',
            'horse' => 'stable',
            'instruments' => 'workshop',
            'kelp' => 'wharf',
            'lumber' => 'saw_mill',
            'magma' => 'ore_mine',
            'mana' => 'tower',
            'miasma' => 'mass_grave',
            'mud' => 'harbour',
            'obsidian' => 'gem_mine',
            'ore' => 'ore_mine',
            'pearls' => 'wharf',
            'prisoner' => 'constabulary',
            'souls' => 'altar',
            'spices' => 'farm',
            'sugar' => 'farm',
            'swamp_gas' => 'tower',
            'thunderstone' => 'dwargen_mine',
            'yak' => 'yakstead'
        ]);
        
        return $resourceMap->get($resourceKey, 'harbour');
    }

    public function getStartingUnits(Hold $hold): array
    {
        return [];
    }

    public function getTickLandGrowth(Hold $hold): int
    {
        $growth = 0;

        $growth += 2 * (1 + $hold->round->ticks/1000);

        return round($growth);
    }

}
