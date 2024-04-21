<?php

namespace OpenDominion\Services;

use OpenDominion\Models\Hold;
use OpenDominion\Models\HoldPrice;
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
        $price = $this->holdCalculator->calculateNewResourceBuyPrice($hold, $resourceKey);
        $resource = Resource::where('key', $resourceKey)->firstOrFail();

        return HoldPrice::updateOrCreate([
            'hold_id' => $hold->id,
            'resource_id' => $resource->id,
            'tick' => $tick,
            'action' => 'buy',
            'price' => $price,
        ]);
    }

    public function setHoldSellPrice(Hold $hold, int $tick, string $resourceKey): HoldPrice
    {
        $price = $this->holdCalculator->calculateNewResourceSellPrice($hold, $resourceKey);
        $resource = Resource::where('key', $resourceKey)->firstOrFail();

        return HoldPrice::updateOrCreate([
            'hold_id' => $hold->id,
            'resource_id' => $resource->id,
            'tick' => $tick,
            'action' => 'sell',
            'price' => $price,
        ]);
    }

}
