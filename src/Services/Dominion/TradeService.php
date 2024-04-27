<?php

namespace OpenDominion\Services\Dominion;

use DB;
use OpenDominion\Models\Round;
use OpenDominion\Models\TradeRoute;

use OpenDominion\Calculators\Dominion\TradeCalculator;

use OpenDominion\Services\HoldService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\TradeRoute\QueueService;
use OpenDominion\Services\Dominion\ResourceService as DominionResourceService;
use OpenDominion\Services\Hold\ResourceService as HoldResourceService;


class TradeService
{
    protected $dominionResourceService;
    protected $holdResourceService;
    protected $holdService;
    protected $notificationService;
    protected $tradeCalculator;
    protected $queueService;

    public function __construct()
    {
        $this->dominionResourceService = app(DominionResourceService::class);
        $this->holdResourceService = app(HoldResourceService::class);
        $this->holdService = app(HoldService::class);
        $this->notificationService = app(NotificationService::class);
        $this->tradeCalculator = app(TradeCalculator::class);
        $this->queueService = app(QueueService::class);
    }

    public function handleTradeRoutesTick(Round $round)
    {
        # Update prices
        $this->holdService->setRoundHoldPrices($round);

        $activeTradeRoutes = $round->tradeRoutes->where('status', 1);

        foreach ($activeTradeRoutes as $tradeRoute)
        {
            # Handle queues
            $this->queueService->handleTradeRouteQueues($tradeRoute);

            # Prepare new resources to be queued
            $this->handleTradeRoute($tradeRoute);
        }
    }

    public function handleTradeRoute(TradeRoute $tradeRoute)
    { 

        $dominion = $tradeRoute->dominion;
        
        $hold = $tradeRoute->hold;
        $soldResource = $tradeRoute->soldResource;
        $boughtResource = $tradeRoute->boughtResource;
        $soldResourceAmount = $tradeRoute->source_amount;

        $tradeResult = $this->tradeCalculator->getTradeResult($dominion, $hold, $soldResource, $soldResourceAmount, $boughtResource);
        $boughtResourceAmount = $tradeResult['bought_resource_amount'];

        # Queue up outgoing
        $this->queueService->queueTrade($tradeRoute, 'export', $soldResource, $soldResourceAmount);

        # Remove the resource from the dominion
        $this->dominionResourceService->updateResources($dominion, [$soldResource->key => ($soldResourceAmount * -1)]);

        # Queue up incoming
        $this->queueService->queueTrade($tradeRoute, 'import', $boughtResource, $boughtResourceAmount);

        # Remove the resource from the hold
        $this->holdResourceService->update($hold, [$boughtResource->key => ($boughtResourceAmount * -1)]);


        $this->notificationService->queueNotification('trade_route_started', [
            'hold_id' => $hold->id,
            'hold_name' => $hold->name,
            'sold_resource_id' => $soldResource->id,
            'sold_resource_name' => $soldResource->name,
            'sold_resource_amount' => $soldResourceAmount,
            'bought_resource_id' => $boughtResource->id,
            'bought_resource_name' => $boughtResource->bought_resource_name,
            'bought_resource_amount' => $boughtResourceAmount,
        ]);

    }

}
