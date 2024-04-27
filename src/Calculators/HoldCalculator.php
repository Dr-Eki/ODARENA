<?php

namespace OpenDominion\Calculators;

use OpenDominion\Models\Hold;
use OpenDominion\Models\Resource;

class HoldCalculator
{

    #protected $resourceCalculator;

    public function __construct()
    {
        #$this->resourceCalculator = app(ResourceCalculator::class);
    }


    public function getNewResourceBuyPrice(Hold $hold, string $resourceKey): float
    {
        if(!$this->canResourceBeTraded($resourceKey) || !$this->canResourceBeTradedByHold($hold, $resourceKey))
        {
            return 0;
        }

        $resource = Resource::where('key', $resourceKey)->firstOrFail();

        $price = $resource->trade->buy;
        
        if($resource->key !== 'gold')
        {
            $price *= (in_array($resource->key, $hold->desired_resources) ? (9/8) : 1.0000);

        }
        return round($price, config('trade.price_decimals'));
    }

    public function getNewResourceSellPrice(Hold $hold, string $resourceKey): float
    {

        if(!$this->canResourceBeTraded($resourceKey) || !$this->canResourceBeTradedByHold($hold, $resourceKey))
        {
            return 0;
        }

        $resource = Resource::where('key', $resourceKey)->firstOrFail();
        $price = $resource->trade->sell;
        
        if($resource->key !== 'gold')
        {
            $price *= (in_array($resource->key, $hold->sold_resources) ? (8/9) : 1.0000);
        }

        return round($price, config('trade.price_decimals'));
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

}
