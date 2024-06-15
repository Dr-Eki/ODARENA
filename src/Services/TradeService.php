<?php

// We want strict types here.
declare(strict_types=1);

namespace OpenDominion\Services;

use DB;
use OpenDominion\Models\TickChange;
use OpenDominion\Models\TradeLedger;
use OpenDominion\Models\TradeRoute;

use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\TradeCalculator;
use OpenDominion\Models\HoldSentimentEvent;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\TradeRoute\QueueService;
use OpenDominion\Services\Dominion\ResourceService as DominionResourceService;
use OpenDominion\Services\Hold\ResourceService as HoldResourceService;


class TradeService
{
    protected $dominionResourceService;
    protected $holdResourceService;
    protected $notificationService;
    protected $resourceCalculator;
    protected $tradeCalculator;
    protected $queueService;

    public function __construct()
    {
        $this->dominionResourceService = app(DominionResourceService::class);
        $this->holdResourceService = app(HoldResourceService::class);
        $this->notificationService = app(NotificationService::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->tradeCalculator = app(TradeCalculator::class);
        $this->queueService = app(QueueService::class);
    }

    public function handleTradeRoute(TradeRoute $tradeRoute): void
    {
        if($tradeRoute->status !== 1 or $tradeRoute->dominion->isAbandoned())
        {
            return;
        }


        DB::transaction(function() use ($tradeRoute)
        {
            $dominion = $tradeRoute->dominion;
            
            $hold = $tradeRoute->hold;
            $soldResource = $tradeRoute->soldResource;
            $boughtResource = $tradeRoute->boughtResource;
            $soldResourceAmount = $tradeRoute->source_amount;

            $dominionSoldResourceStockpile = $dominion->{'resource_' . $soldResource->key};
            $amountToTakeFromStockpile = 0;
            $dominionSoldResourceNetProduction = $this->resourceCalculator->getNetProduction($dominion, $soldResource->key);

            if($this->resourceCalculator->isProducingResource($dominion, $soldResource->key))
            {
                # Producing resources are already accounted for in production, so we take it from there (it's removed in the ResourceCalculator)
                $soldResourceAmount = $soldResourceAmount;

                if($dominionSoldResourceNetProduction < 0)
                {
                    $amountToTakeFromStockpile = min(abs($dominionSoldResourceNetProduction), $dominionSoldResourceStockpile);
                }
            }

            $tradeResult = $this->tradeCalculator->getTradeResult($dominion, $hold, $soldResource, $soldResourceAmount, $boughtResource);
            $tradeResult['dominion_has_enough_from_income'] = $dominionSoldResourceNetProduction > 0;
            $tradeResult['dominion_amount_from_stockpile'] = $amountToTakeFromStockpile;

            if(isset($tradeResult['error']))
            {
                if($tradeResult['error']['reason'] == 'hold_insufficient_resources')
                {
                    $this->notificationService->queueNotification('trade_failed', [
                        'hold_id' => $hold->id,
                        'hold_name' => $hold->name,
                        'sold_resource_id' => $soldResource->id,
                        'sold_resource_name' => $soldResource->name,
                        'sold_resource_amount' => $soldResourceAmount,
                        'bought_resource_id' => $boughtResource->id,
                        'bought_resource_name' => $boughtResource->name,
                        'expected_bought_resource_amount' => $tradeResult['expected_resource_amount'],
                        'hold_total_available' => $tradeResult['error']['details']['hold_total_available'],
                        'hold_production' => $tradeResult['error']['details']['hold_production'],
                        'hold_stockpile' => $tradeResult['error']['details']['hold_stockpile'],
                    ]);
        
                    # Record trade ledger
                    TradeLedger::create([
                        'round_id' => $tradeRoute->round_id,
                        'dominion_id' => $dominion->id,
                        'hold_id' => $hold->id,
                        'tick' => $tradeRoute->round->ticks,
                        'return_tick' => $tradeRoute->round->ticks + 12, # Hardcoded for now
                        'return_ticks' => 12, # Hardcoded for now
                        'source_resource_id' => $soldResource->id,
                        'target_resource_id' => $boughtResource->id,
                        'source_amount' => $soldResourceAmount,
                        'target_amount' => 0,
                        'trade_dominion_sentiment' => optional($hold->sentiments->where('target_id', $dominion->id)->first())->sentiment ?? 0,
                        'trade_result_data' => json_encode($tradeResult)
                    ]);

                    xtLog("[{$dominion->id}] *** Hold is out of resources. Trade failed between {$dominion->name} (# {$dominion->realm->number}) and {$hold->name}. The hold does not have enough {$boughtResource->name}: {$tradeResult['expected_resource_amount']} expected, {$tradeResult['error']['details']['hold_total_available']} available, {$tradeResult['error']['details']['hold_production']} production, {$tradeResult['error']['details']['hold_stockpile']} stockpile.");

                    $this->cancelTradeRoute($tradeRoute, 'hold_insufficient_resources');
                }

                if($tradeResult['error']['reason'] == 'dominion_insufficient_resources')
                {
                    $this->notificationService->queueNotification('trade_failed_and_cancelled', [
                        'hold_id' => $hold->id,
                        'hold_name' => $hold->name,
                        'sold_resource_id' => $soldResource->id,
                        'sold_resource_name' => $soldResource->name,
                        'sold_resource_amount' => $soldResourceAmount,
                        'bought_resource_id' => $boughtResource->id,
                        'bought_resource_name' => $boughtResource->name,
                        'expected_bought_resource_amount' => $tradeResult['expected_resource_amount'],
                        'dominion_total_available' => $tradeResult['error']['details']['dominion_total_available'],
                        'dominion_production' => $tradeResult['error']['details']['dominion_production'],
                        'dominion_stockpile' => $tradeResult['error']['details']['dominion_stockpile'],
                    ]);
        
                    # Record trade ledger
                    TradeLedger::create([
                        'round_id' => $tradeRoute->round_id,
                        'dominion_id' => $dominion->id,
                        'hold_id' => $hold->id,
                        'tick' => $tradeRoute->round->ticks,
                        'return_tick' => $tradeRoute->round->ticks + 12, # Hardcoded for now
                        'return_ticks' => 12, # Hardcoded for now
                        'source_resource_id' => $soldResource->id,
                        'target_resource_id' => $boughtResource->id,
                        'source_amount' => $soldResourceAmount,
                        'target_amount' => 0,
                        'trade_dominion_sentiment' => optional($hold->sentiments->where('target_id', $dominion->id)->first())->sentiment ?? 0,
                        'trade_result_data' => json_encode($tradeResult)
                    ]);

                    xtLog("[{$dominion->id}] *** Dominion is out of resources. Trade failed between {$dominion->name} (# {$dominion->realm->number}) and {$hold->name}. The dominion does not have enough {$soldResource->name}: {$soldResourceAmount} expected, {$tradeResult['error']['details']['dominion_total_available']} available, {$tradeResult['error']['details']['dominion_production']} production, {$tradeResult['error']['details']['dominion_stockpile']} stockpile.");

                    $this->cancelTradeRoute($tradeRoute, 'dominion_insufficient_resources');
                }

                # Send notifications
                $this->notificationService->sendNotifications($dominion, 'hourly_dominion');

                return;
            }

            $boughtResourceAmount = $tradeResult['bought_resource_amount'];

            # Prepare TickChange records
            TickChange::create([
                'tick' => $dominion->round->ticks,
                'source_type' => get_class($soldResource),
                'source_id' => $soldResource->id,
                'target_type' => get_class($dominion),
                'target_id' => $dominion->id,
                'amount' => -$soldResourceAmount,
                'status' => 0,
                'type' => 'trade_sold',
            ]);

            TickChange::create([
                'tick' => $hold->round->ticks,
                'source_type' => get_class($boughtResource),
                'source_id' => $boughtResource->id,
                'target_type' => get_class($hold),
                'target_id' => $hold->id,
                'amount' => -$boughtResourceAmount,
                'status' => 0,
                'type' => 'trade_sold',
            ]);

            # Queue up outgoing
            $this->queueService->queueTrade($tradeRoute, 'export', $soldResource, $soldResourceAmount);

            # Queue up incoming
            $this->queueService->queueTrade($tradeRoute, 'import', $boughtResource, $boughtResourceAmount);

            # Record trade ledger
            TradeLedger::create([
                'round_id' => $tradeRoute->round_id,
                'dominion_id' => $dominion->id,
                'hold_id' => $hold->id,
                'tick' => $tradeRoute->round->ticks,
                'return_tick' => $tradeRoute->round->ticks + 12, # Hardcoded for now
                'return_ticks' => 12, # Hardcoded for now
                'source_resource_id' => $soldResource->id,
                'target_resource_id' => $boughtResource->id,
                'source_amount' => $soldResourceAmount,
                'target_amount' => $boughtResourceAmount,
                'trade_dominion_sentiment' => optional($hold->sentiments->where('target_id', $dominion->id)->first())->sentiment ?? 0,
                'trade_result_data' => json_encode($tradeResult)
            ]);

            xtLog("[{$dominion->id}] *** Trade between {$dominion->name} (# {$dominion->realm->number}) and {$hold->name} was successful. Sold {$soldResourceAmount} {$soldResource->name} for {$boughtResourceAmount} {$boughtResource->name}.", 'info');
        });

    }

    public function cancelTradeRoute(TradeRoute $tradeRoute, string $reason): void
    {
        if($tradeCancellationSentimentPenalty = $this->tradeCalculator->getTradeRouteCancellationSentimentPenalty($tradeRoute, $reason))
        {
            HoldSentimentEvent::add($tradeRoute->hold, $tradeRoute->dominion, -$tradeCancellationSentimentPenalty, $reason);
        }

        $tradeRoute->status = 0;
        $tradeRoute->save();

        xtLog("[TR{$tradeRoute->dominion->id}] *** Trade between {$tradeRoute->dominion->name} (# {$tradeRoute->dominion->realm->number}) and {$tradeRoute->hold->name} was cancelled. Reason: {$reason}.");
    }

}
