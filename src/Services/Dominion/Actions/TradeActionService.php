<?php

// We want strict types here.
declare(strict_types=1);

namespace OpenDominion\Services\Dominion\Actions;

use DB;

use OpenDominion\Exceptions\GameException;

use OpenDominion\Calculators\Dominion\TradeCalculator;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Hold;
use OpenDominion\Models\HoldSentimentEvent;
use OpenDominion\Models\TradeRoute;
use OpenDominion\Models\Resource;

use OpenDominion\Traits\DominionGuardsTrait;

class TradeActionService
{
    use DominionGuardsTrait;

    protected $tradeRoute = null;

    protected $tradeCalculator;

    public function __construct()
    {
        $this->tradeCalculator = app(TradeCalculator::class);
    }

    public function create(Dominion $dominion, Hold $hold, Resource $soldResource, int $soldResourceAmount, Resource $boughtResource)
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        if($dominion->protection_ticks > 0)
        {
            throw new GameException('You cannot trade while in protection.');
        }

        #if(!$dominion->round->hasStarted())
        #{
        #    throw new GameException('You cannot trade until the round begins.');
        #}

        if($dominion->round->hasEnded())
        {
            throw new GameException('You cannot trade after the round has ended.');
        }

        $holdSentiment = $hold->getSentiment($dominion) ?? 0;

        if(($sentimentRequired = $this->tradeCalculator->getSentimentRequiredToEstablishTradeRoute($hold, $dominion)) > $holdSentiment)
        {
            throw new GameException('You do not have enough sentiment to establish a new trade route with ' . $hold->name . '. Sentiment required: ' . number_format($sentimentRequired) . '. Current: ' . number_format($holdSentiment) . '.');
        }

        $tradeOfferValue = $this->tradeCalculator->getTradeOfferValue($soldResource, $soldResourceAmount);
        $maxTradeValue = $this->tradeCalculator->getMaxTradeValue($dominion, $soldResource);

        if($tradeOfferValue > $maxTradeValue)
        {
            throw new GameException('You cannot trade this amount of this resource. You can at most offer trades at a base value of ' . number_format($maxTradeValue) . ' gold\'s worth of ' . $soldResource->name . ' and you offered ' . number_format($tradeOfferValue) .'.');
        }

        #$existingTradeRoute = TradeRoute::where([
        #    'dominion_id' => $dominion->id,
        #    'hold_id' => $hold->id,
        #    'source_resource_id' => $soldResource->id,
        #    'target_resource_id' => $boughtResource->id,
        #    'status' => 1
        #])->first();
#
        #if($existingTradeRoute)
        #{
        #    throw new GameException('You already have a trade route to this hold trading these resources.');
        #}

        if($this->tradeCalculator->getAvailableTradeRouteSlots($dominion) <= 0)
        {
            throw new GameException('Insufficient trade routes available. You cannot create any more trade routes at this time.');
        }

        if(!$hold)
        {
            throw new GameException('Invalid hold.');
        }

        if($hold->status !== 1)
        {
            throw new GameException('This hold has not yet been discovered.');
        }

        if(!$soldResource)
        {
            throw new GameException('Invalid sold resource.');
        }

        if(!$boughtResource)
        {
            throw new GameException('Invalid bought resource.');
        }

        if($soldResource->id == $boughtResource->id)
        {
            throw new GameException('You cannot trade a resource for itself.');
        }

        if($soldResourceAmount <= 0)
        {
            throw new GameException('Invalid amount. You must trade at least 1 of this resource.');
        }

        if ($dominion->round->id !== $hold->round->id)
        {
            throw new GameException('You cannot trade with holds from other rounds.');
        }

        if (!$this->tradeCalculator->canDominionTradeWithHold($dominion, $hold))
        {
            throw new GameException('You cannot trade with this hold.');
        }

        if (!$this->tradeCalculator->canDominionTradeResource($dominion, $soldResource))
        {
            throw new GameException('You cannot sell this resource.');
        }

        if (!$this->tradeCalculator->canDominionTradeResource($dominion, $boughtResource))
        {
            throw new GameException('You cannot buy this resource.');
        }

        if ($soldResourceAmount > $this->tradeCalculator->getResourceMaxOfferableAmount($dominion, $soldResource))
        {
            throw new GameException('You cannot trade this amount of this resource. You can at most offer ' . number_format($this->tradeCalculator->getResourceMaxOfferableAmount($dominion, $soldResource)) . '.');
        }

        if($this->tradeCalculator->getBoughtResourceAmount($dominion, $hold, $soldResource, $soldResourceAmount, $boughtResource) <= 0)
        {
            throw new GameException('Trade route could not be established as the expected return is 0.');
        }

        DB::transaction(function () use ($dominion, $hold, $soldResource, $soldResourceAmount, $boughtResource) {
            $this->tradeRoute = TradeRoute::create([
                'round_id' => $dominion->round->id,
                'dominion_id' => $dominion->id,
                'hold_id' => $hold->id,
                'source_resource_id' => $soldResource->id,
                'target_resource_id' => $boughtResource->id,
                'source_amount' => $soldResourceAmount,
            ]);
        });

        if($this->tradeRoute)
        {
            $gameEvent = GameEvent::create([
                'round_id' => $dominion->round_id,
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'target_type' => Hold::class,
                'target_id' => $hold->id,
                'type' => 'tradeRouteCreated',
                'data' => $this->tradeRoute,
                'tick' => $dominion->round->ticks
            ]);

            HoldSentimentEvent::add($hold, $dominion, config('holds.sentiment_values.trade_route_established'), 'trade_route_established');

            $message = vsprintf('Trade route to trade %s for %s with %s has been created.', [
                $soldResource->name,
                $boughtResource->name,
                $hold->name
            ]);
    
            return [
                'message' => $message,
                'alert-type' => 'success',
                'redirect' => route('dominion.trade.routes')
            ];
        }
    }

    public function edit(TradeRoute $tradeRoute, int $soldResourceAmount)
    {
        $dominion = $tradeRoute->dominion;

        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        if($dominion->protection_ticks > 0)
        {
            throw new GameException('You cannot trade while in protection.');
        }

        if($dominion->round->hasEnded())
        {
            throw new GameException('You cannot edit a trade after the round has ended.');
        }
        

        $hold = $tradeRoute->hold;
        $soldResource = $tradeRoute->soldResource;
        $boughtResource = $tradeRoute->boughtResource;

        $tradeOfferValue = $this->tradeCalculator->getTradeOfferValue($soldResource, $soldResourceAmount);
        $maxTradeValue = $this->tradeCalculator->getMaxTradeValue($dominion, $soldResource);

        if($tradeOfferValue > $maxTradeValue)
        {
            throw new GameException('You cannot trade this amount of this resource. You can at most offer trades at a base value of ' . number_format($maxTradeValue) . ' gold\'s worth of ' . $soldResource->name . ' and you offered ' . number_format($tradeOfferValue) .'.');
        }

        if(!$hold)
        {
            throw new GameException('Invalid hold.');
        }

        if($hold->status !== 1)
        {
            throw new GameException('This hold has not yet been discovered.');
        }

        if(!$soldResource)
        {
            throw new GameException('Invalid sold resource.');
        }

        if(!$boughtResource)
        {
            throw new GameException('Invalid bought resource.');
        }

        if($soldResource->id == $boughtResource->id)
        {
            throw new GameException('You cannot trade a resource for itself.');
        }

        if($soldResourceAmount <= 0)
        {
            throw new GameException('Invalid amount. You must trade at least 1 of this resource.');
        }

        if ($dominion->round->id !== $hold->round->id)
        {
            throw new GameException('You cannot trade with holds from other rounds.');
        }

        if (!$this->tradeCalculator->canDominionTradeWithHold($dominion, $hold))
        {
            throw new GameException('You cannot trade with this hold.');
        }

        if (!$this->tradeCalculator->canDominionTradeResource($dominion, $soldResource))
        {
            throw new GameException('You cannot sell this resource.');
        }

        if (!$this->tradeCalculator->canDominionTradeResource($dominion, $boughtResource))
        {
            throw new GameException('You cannot buy this resource.');
        }

        if ($soldResourceAmount > $this->tradeCalculator->getResourceMaxOfferableAmount($dominion, $soldResource, $soldResourceAmount))
        {
            throw new GameException('You cannot trade this amount of this resource. You can at most offer ' . number_format($this->tradeCalculator->getResourceMaxOfferableAmount($dominion, $soldResource)) . '.');
        }

        if($this->tradeCalculator->getBoughtResourceAmount($dominion, $hold, $soldResource, $soldResourceAmount, $boughtResource) <= 0)
        {
            throw new GameException('Trade route could not be established as the expected return is 0.');
        }

        DB::transaction(function () use ($tradeRoute, $soldResourceAmount) {

            if($soldResourceAmount < $tradeRoute->source_amount)
            {
                $reductionRatio = $soldResourceAmount / $tradeRoute->source_amount;
                $baseSentimentPenalty = config('holds.sentiment_values.sold_amount_reduced');
                $penalty = ceilInt($baseSentimentPenalty * $reductionRatio);

                HoldSentimentEvent::add($tradeRoute->hold, $tradeRoute->dominion, -$penalty, 'sold_amount_reduced');
            }

            $tradeRoute->update([
                'source_amount' => $soldResourceAmount,
            ]);
        });

        if($this->tradeRoute)
        {
            $message = vsprintf('Trade route to trade %s for %s with %s has been updated.', [
                $soldResource->name,
                $boughtResource->name,
                $hold->name
            ]);
    
            return [
                'message' => $message,
                'alert-type' => 'success',
                'redirect' => route('dominion.trade.routes')
            ];
        }

    }

    public function delete(TradeRoute $tradeRoute)
    {
        $dominion = $tradeRoute->dominion;

        if($dominion->protection_ticks > 0)
        {
            throw new GameException('You cannot trade while in protection.');
        }

        $hold = $tradeRoute->hold;
        $soldResource = $tradeRoute->soldResource;
        $boughtResource = $tradeRoute->boughtResource;

        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        if(!$hold)
        {
            throw new GameException('Invalid hold.');
        }

        DB::transaction(function () use ($tradeRoute) {

            $sentimentPenalty = $this->tradeCalculator->getTradeRouteCancellationSentimentPenalty($tradeRoute, 'cancelled_by_dominion');

            if($sentimentPenalty)
            {
                HoldSentimentEvent::add($tradeRoute->hold, $tradeRoute->dominion, -$sentimentPenalty, 'trade_route_cancelled_by_dominion');
            }

            $tradeRoute->status = 0;
            $tradeRoute->save();
        });

        $message = vsprintf('Trade route to trade %s for %s with %s has been cancelled.', [
            $soldResource->name,
            $boughtResource->name,
            $hold->name
        ]);

        return [
            'message' => $message,
            'alert-type' => 'success',
            'redirect' => route('dominion.trade.routes')
        ];
    }

}
