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


    public function calculateNewResourceBuyPrice(Hold $hold, string $resourceKey): float
    {
        if(!$this->canResourceBeTraded($resourceKey) || !$this->canResourceBeTradedByHold($hold, $resourceKey))
        {
            return 0;
        }

        $resource = Resource::where('key', $resourceKey)->firstOrFail();
        $price = $resource->buy * (in_array($resource->key, $hold->desired_resources)) ? (4/3) : 1.0000;

        return round($price, 4);
    }

    public function calculateNewResourceSellPrice(Hold $hold, string $resourceKey): float
    {

        if(!$this->canResourceBeTraded($resourceKey) || !$this->canResourceBeTradedByHold($hold, $resourceKey))
        {
            return 0;
        }

        $resource = Resource::where('key', $resourceKey)->firstOrFail();
        $price = $resource->sell * (in_array($resource->key, $hold->sold_resources)) ? (3/4) : 1.0000;

        return round($price, 4);
    }

    public function canResourceBeTradedByHold(Hold $hold, string $resourceKey): bool
    {
        $resource = Resource::where('key', $resourceKey)->firstOrFail();
        return in_array($resource->key, $hold->desired_resources) || in_array($resource->key, $hold->sold_resources);
    }

    public function canResourceBeTraded(string $resourceKey): bool
    {
        $resource = Resource::where('key', $resourceKey)->firstOrFail();
        return ($resource->buy > 0 || $resource->sell > 0);
    }

}
