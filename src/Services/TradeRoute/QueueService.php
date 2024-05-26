<?php

namespace OpenDominion\Services\TradeRoute;

use BadMethodCallException;
use DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use OpenDominion\Models\HoldSentimentEvent;
use OpenDominion\Models\Resource;
use OpenDominion\Models\TradeRoute;
use OpenDominion\Models\TradeRoute\Queue;

use OpenDominion\Services\Dominion\ResourceService as DominionResourceService;
use OpenDominion\Services\Hold\ResourceService as HoldResourceService;


class QueueService
{

    #protected $forTick = false;

    protected $dominionResourceService;
    protected $holdResourceService;

    public function __construct()
    {
        $this->dominionResourceService = app(DominionResourceService::class);
        $this->holdResourceService = app(HoldResourceService::class);
    }

    /**
     * Toggle if this calculator should include the following tick's resources.
     */
    #public function setForTick(bool $value)
    #{
    #    $this->forTick = $value;
    #}

    public function queueTrade(TradeRoute $tradeRoute, string $type, Resource $resource, int $amount, array $units = [], int $tick = 12): void
    {
        if($amount == 0 or $tradeRoute->status == 0)
        {
            xtLog("[TR{$tradeRoute->id}] **** Not queuing $type of $amount {$resource->name} for trade route at tick $tick, dominion ID {$tradeRoute->dominion->id} and hold ID {$tradeRoute->hold->id} because amount is 0 or trade route is inactive");
            return;
        }

        $now = now();
    
        $queue = Queue::firstOrCreate(
            [
                'trade_route_id' => $tradeRoute->id,
                'resource_id' => $resource->id,
                'tick' => $tick,
                'type' => $type,
                'status' => 1
            ],
            [
                'amount' => 0,
                'units' => json_encode([]),
                'created_at' => $now,
            ]
        );
    
        $queue->increment('amount', $amount);
        $queue->units = json_encode($units);
        $queue->save();
        
        $preposition1 = ($type == 'import') ? 'to' : 'from';
        $preposition2 = ($type == 'import') ? 'from' : 'to';

        xtLog("[TR{$tradeRoute->id}] **** Queued $type of $amount {$resource->name} for trade route at tick $tick $preposition1 {$tradeRoute->dominion->name} $preposition2 {$tradeRoute->hold->name}");
        
    }

    public function advanceTradeRouteQueues(TradeRoute $tradeRoute): void
    {
        DB::transaction(function () use ($tradeRoute)
        {
            // Delete leftover zero tick trades
            $tradeRouteQueuesDeleted = $tradeRoute->queues()
                ->where('tick', '=', 0)
                ->delete();
            xtLog("[TR{$tradeRoute->id}] **** Deleted $tradeRouteQueuesDeleted zero tick trade route queues");

            // Decrement all ticks
            $tradeRouteQueuesTicked = $tradeRoute->queues()
                ->where('tick', '>', 0)
                ->decrement('tick');

            xtLog("[TR{$tradeRoute->id}] **** Decremented $tradeRouteQueuesTicked trade route queues");
        });
    }
   

    public function finishTradeRouteQueues(TradeRoute $tradeRoute): void
    {
        DB::transaction(function () use ($tradeRoute)
        {
            $finishedQueues = $tradeRoute->queues()
                ->where('tick', 0)
                ->get();
    
            if (!$finishedQueues->count()) {
                return;
            }
    
            $tradeRoute->increment('trades');
            $tradeRoute->increment('total_bought', $finishedQueues->where('type', 'import')->sum('amount'));
            $tradeRoute->increment('total_sold', $finishedQueues->where('type', 'export')->sum('amount'));
    
            foreach ($finishedQueues as $key => $finishedQueue)
            {
                $amount = $finishedQueue->amount;
    
                xtLog("[TR{$tradeRoute->id}] **** Finished queue {$finishedQueue->id} of type {$finishedQueue->type} with {$finishedQueue->amount} {$finishedQueue->resource->name} for trade route {$tradeRoute->id} at tick {$finishedQueue->tick} ($key/" . ($finishedQueues->count()-1) . "), dominion ID {$tradeRoute->dominion->id} and hold ID {$tradeRoute->hold->id}");
    
                if ($finishedQueue->type == 'import')
                {
                    $this->dominionResourceService->update($tradeRoute->dominion, [$finishedQueue->resource->key => $amount]);
                    xtLog("[TR{$tradeRoute->id}] ***** Added {$finishedQueue->amount} {$finishedQueue->resource->name} to dominion {$tradeRoute->dominion->name} (ID {$tradeRoute->dominion->id})");
                }
                elseif ($finishedQueue->type == 'export')
                {
                    $this->holdResourceService->update($tradeRoute->hold, [$finishedQueue->resource->key => $amount]);
                    xtLog("[TR{$tradeRoute->id}] ***** Added {$finishedQueue->amount} {$finishedQueue->resource->name} to hold {$tradeRoute->hold->name}");
                }
    
                $isDeleted = $finishedQueue->delete();
                if ($isDeleted)
                {
                    xtLog("[TR{$tradeRoute->id}] ***** Deleted queue {$finishedQueue->id}");
                }
                else
                {
                    xtLog("[TR{$tradeRoute->id}] ***** Failed to delete queue {$finishedQueue->id}");
                }
            }
    
            if ($tradeRoute->status == 1) {
                HoldSentimentEvent::add($tradeRoute->hold, $tradeRoute->dominion, config('holds.sentiment_values.trade_completed'), 'trade_completed');
            }
    
            $overDueQueuesDeleted = $tradeRoute->queues()
                ->where('status', 1)
                ->where('tick', '<', 0)
                ->delete();
            
            if($overDueQueuesDeleted)
            {
                xtLog("[TR{$tradeRoute->id}] **** Deleted $overDueQueuesDeleted overdue trade route queues");
            }
        });
    }

    /**
     * Returns the queue of specific type of a trade route.
     *
     * @param string $type
     * @param TradeRoute $tradeRoute
     * @return Collection
     */
    public function getQueue(string $type, TradeRoute $tradeRoute): Collection
    {
        $tick = 0;
        #if ($this->forTick)
        #{
        #    // don't include next tick when calculating tick
        #    $tick = 1;
        #}
        return $tradeRoute->queues
            ->where('type', $type)
            ->where('tick', '>', $tick);
    }

    /**
     * Returns the amount of incoming resource for a specific type and tick of a trade route.
     *
     * @param string $type
     * @param TradeRoute $tradeRoute
     * @param string $resource
     * @param int $tick
     * @return int
     */
    public function getQueueAmount(string $type, TradeRoute $tradeRoute, string $resource, int $tick): int
    {
        return $this->getQueue($type, $tradeRoute)
                ->filter(static function ($row) use ($resource, $tick)
                {
                    return (
                        ($row->resource === $resource) &&
                        ($row->tick === $tick)
                    );
                })->first()->amount ?? 0;
    }

    /**
     * Returns the sum of resources in a queue of a specific type of a
     * trade route.
     *
     * @param string $type
     * @param TradeRoute $tradeRoute
     * @return int
     */
    public function getQueueTotal(string $type, TradeRoute $tradeRoute): int
    {
        return $this->getQueue($type, $tradeRoute)
            ->sum('amount');
    }

    /**
     * Returns the sum of a specific resource in a queue of a specific type of
     * a trade route.
     *
     * @param string $type
     * @param TradeRoute $tradeRoute
     * @param string $resource
     * @return int
     */
    public function getQueueTotalByResource(string $type, TradeRoute $tradeRoute, string $resource): int
    {
        return $this->getQueue($type, $tradeRoute)
            ->filter(static function ($row) use ($resource) {
                return ($row->resource === $resource);
            })->sum('amount');
    }

    public function dequeueResource(string $type, TradeRoute $tradeRoute, string $resource, int $amount): void
    {

        $queue = $this->getQueue($type, $tradeRoute)
            ->filter(static function ($row) use ($resource) {
                return ($row->resource === $resource);
            })->sortByDesc('tick');

        $leftToDequeue = $amount;

        foreach ($queue as $value)
        {
            $amountEnqueued = $value->amount;
            $amountDequeued = $leftToDequeue;

            if($amountEnqueued < $leftToDequeue)
            {
                $amountDequeued = $amountEnqueued;
            }

            $leftToDequeue -= $amountDequeued;
            $newAmount = $amountEnqueued - $amountDequeued;

            if($newAmount == 0)
            {
                DB::table('trade_route_queue')->where([
                    'trade_route_id' => $tradeRoute->id,
                    'type' => $type,
                    'resource' => $resource,
                    'tick' => $value->tick,
                ])->delete();
            }
            else
            {
                DB::table('trade_route_queue')->where([
                    'trade_route_id' => $tradeRoute->id,
                    'type' => $type,
                    'resource' => $resource,
                    'tick' => $value->tick,
                ])->update([
                    'amount' => $newAmount,
                ]);
            }
        }
    }

    public function dequeueResourceForTick(string $type, TradeRoute $tradeRoute, string $resource, int $amount, int $tick): void
    {

        $queue = $this->getQueue($type, $tradeRoute)
            ->filter(static function ($row) use ($resource, $tick) {
                return (
                    $row->resource === $resource and
                    $row->tick === $tick
                  );
            });

        $leftToDequeue = $amount;

        foreach ($queue as $value)
        {
            $amountEnqueued = $value->amount;
            $amountDequeued = $leftToDequeue;

            if($amountEnqueued < $leftToDequeue) {
                $amountDequeued = $amountEnqueued;
            }

            $leftToDequeue -= $amountDequeued;
            $newAmount = $amountEnqueued - $amountDequeued;

            if($newAmount == 0)
            {
                DB::table('trade_route_queue')->where([
                    'trade_route_id' => $tradeRoute->id,
                    'type' => $type,
                    'resource' => $resource,
                    'tick' => $tick,
                ])->delete();
            }
            else
            {
                DB::table('trade_route_queue')->where([
                    'trade_route_id' => $tradeRoute->id,
                    'type' => $type,
                    'resource' => $resource,
                    'tick' => $tick,
                ])->update([
                    'amount' => $newAmount,
                ]);
            }
        }
    }

    public function queueResources(string $type, TradeRoute $tradeRoute, array $data, int $tick = 12): void
    {
        $data = array_map('\intval', $data);
        $now = now();
    
        foreach ($data as $resource => $amount) {
            if ($amount === 0) {
                continue;
            }
    
            $queue = DB::table('trade_route_queue')->firstOrCreate(
                [
                    'trade_route_id' => $tradeRoute->id,
                    'type' => $type,
                    'resource' => $resource,
                    'tick' => $tick,
                ],
                [
                    'amount' => 0,
                    'created_at' => $now,
                ]
            );
    
            $queue->increment('amount', $amount);
        }
    }

    /**
     * Helper getter to call queue methods with types specified in the method
     * name.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */

    public function __call($name, $arguments)
    {
        preg_match_all('/((?:^|[A-Z])[a-z]+)/', $name, $matches);
        $methodParts = $matches[1];
    
        if (!((Arr::get($methodParts, '0') === 'get') && (Arr::get($methodParts, '2') === 'Queue'))) {
            throw new BadMethodCallException(sprintf(
                'Method %s->%s does not exist.', static::class, $name
            ));
        }
    
        $type = strtolower(Arr::get($methodParts, '1'));
        $method = implode('', Arr::except($methodParts, '1'));
        array_unshift($arguments, $type);
    
        return \call_user_func_array([$this, $method], $arguments);
    }
}
