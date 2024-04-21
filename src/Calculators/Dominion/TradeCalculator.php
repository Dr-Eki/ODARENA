<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hold;
use OpenDominion\Models\Resource;

class TradeCalculator
{

    #protected $resourceCalculator;

    public function __construct()
    {
        #$this->resourceCalculator = app(ResourceCalculator::class);
    }

    public function canDominionTradeWithHold(Dominion $dominion, Hold $hold): bool
    {
        $dominionResourceKeys = $dominion->resourceKeys();
        $holdResourceKeys = $hold->desired_resources;

        if($hold->key == 'sunset-spire')
        {
            #dump($hold->name, $dominionResourceKeys, $holdResourceKeys);
        }

        return count(array_intersect($dominionResourceKeys, $holdResourceKeys)) > 0;
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

        # What is the hold willing to pay per unit of the resource sold by the dominion?
        $soldResourceHoldBuyPrice = $hold->buyPrice($soldResource->key);

        # What is the hold willing to sell per unit of the resource bought by the dominion?
        $boughtResourceHoldSellPrice = $hold->sellPrice($boughtResource->key);

        # What is the total value of the resource sold by the dominion?
        $soldResourceTotalValue = (int)floor($soldResourceAmount * $soldResourceHoldBuyPrice);

        # How much of the bought resource does that get us?
        $result['bought_resource_amount'] = (int)floor($soldResourceTotalValue / $boughtResourceHoldSellPrice);

        return $result;
    }

}
