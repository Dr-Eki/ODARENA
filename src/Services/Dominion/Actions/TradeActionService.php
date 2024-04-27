<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;

use OpenDominion\Exceptions\GameException;

use OpenDominion\Calculators\Dominion\TradeCalculator;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Hold;
use OpenDominion\Models\TradeRoute;
use OpenDominion\Models\Resource;

use OpenDominion\Traits\DominionGuardsTrait;

class TradeActionService
{
    use DominionGuardsTrait;

    protected $tradeRoute;

    protected $tradeCalculator;

    public function __construct()
    {
        $this->tradeCalculator = app(TradeCalculator::class);
    }

    public function create(Dominion $dominion, Hold $hold, Resource $soldResource, int $soldResourceAmount, Resource $boughtResource)
    {

        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        if(!$hold)
        {
            throw new GameException('Invalid hold.');
        }

        if(!$soldResource)
        {
            throw new GameException('Invalid sold resource.');
        }

        if(!$boughtResource)
        {
            throw new GameException('Invalid bought resource.');
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
            throw new GameException('You cannot trade this amount of this resource.');
        }

        # Check if a trade route already exists for this dominion, hold, and resources
        $existingTradeRoute = TradeRoute::where('dominion_id', $dominion->id)
            ->where('hold_id', $hold->id)
            ->where('source_resource_id', $soldResource->id)
            ->where('target_resource_id', $boughtResource->id)
            ->first();

        if ($existingTradeRoute)
        {
            throw new GameException('You are already trading ' . $soldResource->name . ' for ' . $boughtResource->name . ' with ' . $hold->name . '.');
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
            }
        });

        if($this->tradeRoute)
        {
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


}
