<?php

namespace OpenDominion\Services\Hold;

use BadMethodCallException;
use DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use OpenDominion\Models\Hold;
use OpenDominion\Models\Building;
use OpenDominion\Models\Hold\Queue;

use OpenDominion\Services\Hold\BuildingService;
use OpenDominion\Services\Hold\ResourceService;


class QueueService
{

    #protected $forTick = false;

    protected $buildingService;
    protected $resourceService;

    public function __construct()
    {
        $this->buildingService = app(BuildingService::class);
        $this->resourceService = app(ResourceService::class);
    }

    public function queueBuilding(Hold $hold, string $buildingKey, int $amount, int $tick = 12): void
    {
        if($amount == 0)
        {
            return;
        }

        $now = now();

        $building = Building::fromKey($buildingKey);
    
        $queue = Queue::firstOrCreate(
            [
                'hold_id' => $hold->id,
                'type' => 'construction',
                'item_type' => get_class($building),
                'item_id' => $building->id,
                'tick' => $tick,
                'status' => 1
            ],
            [
                'amount' => 0,
                'created_at' => $now,
            ]
        );
    
        $queue->increment('amount', $amount);
        $queue->save();

        #dump('> Queued ' . $amount . ' ' . $resource->name . ' for trade route ' . $hold->id . ' at tick ' . $tick . ', going from ' . $hold->dominion->name . ' to ' . $hold->hold->name);
    }

    public function handleHoldQueues(Hold $hold): void
    {
        $this->advanceHoldQueues($hold);
        $this->finishHoldQueues($hold);
    }

    public function advanceHoldQueues(Hold $hold): void
    {
        $hold->queues()
            ->where('status', 1)
            ->where('tick','>',0)
            ->decrement('tick');
    }

    public function finishHoldQueues(Hold $hold): void
    {
        $finishedQueues = $hold->queues()
            ->where('status', 1)
            ->where('tick', 0)
            ->get();

        if($finishedQueues->count() > 0)
        {
            foreach($finishedQueues as $key => $finishedQueue)
            {
                $amount = $finishedQueue->amount;
    
                #dump('Finished queue ' . $finishedQueue->id . ' of type ' . $finishedQueue->type . ' with ' . $finishedQueue->amount . ' ' . $finishedQueue->resource->name . ' for trade route ' . $hold->id . ' at tick ' . $finishedQueue->tick . ' (' . $key . '/' . $finishedQueues->count()-1 . ')');

                # Update dominion resources
                if($finishedQueue->type == 'construction')
                {
                    $this->buildingService->update($hold->dominion, [$finishedQueue->resource->key => $amount]);
                    #dump('+ Added ' . $finishedQueue->amount . ' ' . $finishedQueue->resource->name . ' to dominion ' . $hold->dominion->name);
                }
                $hold->save();
            }

            $finishedQueues->each->delete();
        }

        $overDueQueues = $hold->queues()
            ->where('status', 1)
            ->where('tick', '<', 0)
            ->get();

        $overDueQueues->each->delete();

    }


    /**
     * Returns the queue of specific type of a trade route.
     *
     * @param string $type
     * @param Hold $hold
     * @return Collection
     */
    public function getQueue(string $type, Hold $hold): Collection
    {
        $tick = 0;
        #if ($this->forTick)
        #{
        #    // don't include next tick when calculating tick
        #    $tick = 1;
        #}
        return $hold->queues
            ->where('type', $type)
            ->where('tick', '>', $tick);
    }

    /**
     * Returns the amount of incoming resource for a specific type and tick of a trade route.
     *
     * @param string $type
     * @param Hold $hold
     * @param string $resource
     * @param int $tick
     * @return int
     */
    public function getQueueAmount(string $type, Hold $hold, string $resource, int $tick): int
    {
        return $this->getQueue($type, $hold)
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
     * @param Hold $hold
     * @return int
     */
    public function getQueueTotal(string $type, Hold $hold): int
    {
        return $this->getQueue($type, $hold)
            ->sum('amount');
    }

    /**
     * Returns the sum of a specific resource in a queue of a specific type of
     * a trade route.
     *
     * @param string $type
     * @param Hold $hold
     * @param string $resource
     * @return int
     */
    public function getQueueTotalByResource(string $type, Hold $hold, string $resource): int
    {
        return $this->getQueue($type, $hold)
            ->filter(static function ($row) use ($resource) {
                return ($row->resource === $resource);
            })->sum('amount');
    }

    public function dequeueResource(string $type, Hold $hold, string $resource, int $amount): void
    {

        $queue = $this->getQueue($type, $hold)
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
                DB::table('hold_queues')->where([
                    'hold_id' => $hold->id,
                    'type' => $type,
                    'resource' => $resource,
                    'tick' => $value->tick,
                ])->delete();
            }
            else
            {
                DB::table('hold_queues')->where([
                    'hold_id' => $hold->id,
                    'type' => $type,
                    'resource' => $resource,
                    'tick' => $value->tick,
                ])->update([
                    'amount' => $newAmount,
                ]);
            }
        }
    }

    public function dequeueResourceForTick(string $type, Hold $hold, string $resource, int $amount, int $tick): void
    {

        $queue = $this->getQueue($type, $hold)
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
                DB::table('hold_queues')->where([
                    'hold_id' => $hold->id,
                    'type' => $type,
                    'resource' => $resource,
                    'tick' => $tick,
                ])->delete();
            }
            else
            {
                DB::table('hold_queues')->where([
                    'hold_id' => $hold->id,
                    'type' => $type,
                    'resource' => $resource,
                    'tick' => $tick,
                ])->update([
                    'amount' => $newAmount,
                ]);
            }
        }
    }

    public function queueResources(string $type, Hold $hold, array $data, int $tick = 12): void
    {
        $data = array_map('\intval', $data);
        $now = now();
    
        foreach ($data as $resource => $amount) {
            if ($amount === 0) {
                continue;
            }
    
            $queue = DB::table('hold_queues')->firstOrCreate(
                [
                    'hold_id' => $hold->id,
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
