<?php

namespace OpenDominion\Services\Dominion;

use BadMethodCallException;
use DB;
use Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;

/**
 * Class QueueService
 *
 * @method Collection getConstructionQueue(Dominion $dominion)
 * @method int getConstructionQueueTotal(Dominion $dominion)
 * @method int getConstructionQueueTotalByResource(Dominion $dominion, string $resource)
 * @method Collection getExplorationQueue(Dominion $dominion)
 * @method int getExplorationQueueTotal(Dominion $dominion)
 * @method int getExplorationQueueTotalByResource(Dominion $dominion, string $resource)
 * @method Collection getInvasionQueue(Dominion $dominion)
 * @method int getInvasionQueueTotal(Dominion $dominion)
 * @method int getInvasionQueueTotalByResource(Dominion $dominion, string $resource)
 * @method Collection getTrainingQueue(Dominion $dominion)
 * @method int getTrainingQueueTotal(Dominion $dominion)
 * @method int getTrainingQueueTotalByResource(Dominion $dominion, string $resource)
 * @method Collection getSabotageQueue(Dominion $dominion)
 * @method int getSabotageQueueTotal(Dominion $dominion)
 * @method int getSabotageQueueTotalByResource(Dominion $dominion, string $resource)
 */
class QueueService
{
    /** @var bool */
    #protected $forTick = false;

    /**
     * Toggle if this calculator should include the following hour's resources.
     */
    #public function setForTick(bool $value)
    #{
    #    $this->forTick = $value;
    #}

    /**
     * Returns the queue of specific type of a dominion.
     *
     * @param string $source
     * @param Dominion $dominion
     * @return Collection
     */
    public function getQueue(string $source, Dominion $dominion): Collection
    {
        $hours = 0;
        #if ($this->forTick)
        #{
        #    // don't include next hour when calculating tick
        #    $hours = 1;
        #}
        return $dominion->queues
            ->where('source', $source)
            ->where('hours', '>', $hours);
    }

    /**
     * Returns the amount of incoming resource for a specific type and hour of a dominion.
     *
     * @param string $source
     * @param Dominion $dominion
     * @param string $resource
     * @param int $hour
     * @return int
     */
    public function getQueueAmount(string $source, Dominion $dominion, string $resource, int $hour): int
    {
        return $this->getQueue($source, $dominion)
                ->filter(static function ($row) use ($resource, $hour)
                {
                    return (
                        ($row->resource === $resource) &&
                        ($row->hours === $hour)
                    );
                })->first()->amount ?? 0;
    }

    /**
     * Returns the sum of resources in a queue of a specific type of a
     * dominion.
     *
     * @param string $source
     * @param Dominion $dominion
     * @return int
     */
    public function getQueueTotal(string $source, Dominion $dominion): int
    {
        return $this->getQueue($source, $dominion)
            ->sum('amount');
    }

    /**
     * Returns the sum of a specific resource in a queue of a specific type of
     * a dominion.
     *
     * @param string $source
     * @param Dominion $dominion
     * @param string $resource
     * @return int
     */
    public function getQueueTotalByResource(string $source, Dominion $dominion, string $resource): int
    {
        return $this->getQueue($source, $dominion)
            ->filter(static function ($row) use ($resource) {
                return ($row->resource === $resource);
            })->sum('amount');
    }

    public function dequeueResource(string $source, Dominion $dominion, string $resource, int $amount): void
    {

        $queue = $this->getQueue($source, $dominion)
            ->filter(static function ($row) use ($resource) {
                return ($row->resource === $resource);
            })->sortByDesc('hours');

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
                DB::table('dominion_queue')->where([
                    'dominion_id' => $dominion->id,
                    'source' => $source,
                    'resource' => $resource,
                    'hours' => $value->hours,
                ])->delete();
            }
            else
            {
                DB::table('dominion_queue')->where([
                    'dominion_id' => $dominion->id,
                    'source' => $source,
                    'resource' => $resource,
                    'hours' => $value->hours,
                ])->update([
                    'amount' => $newAmount,
                ]);
            }
        }
    }

    public function dequeueResourceForHour(string $source, Dominion $dominion, string $resource, int $amount, int $hour): void
    {

        $queue = $this->getQueue($source, $dominion)
            ->filter(static function ($row) use ($resource, $hour) {
                return (
                    $row->resource === $resource and
                    $row->hours === $hour
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
                DB::table('dominion_queue')->where([
                    'dominion_id' => $dominion->id,
                    'source' => $source,
                    'resource' => $resource,
                    'hours' => $hour,
                ])->delete();
            }
            else
            {
                DB::table('dominion_queue')->where([
                    'dominion_id' => $dominion->id,
                    'source' => $source,
                    'resource' => $resource,
                    'hours' => $hour,
                ])->update([
                    'amount' => $newAmount,
                ]);
            }
        }
    }

    /**
     * Queues new resources for a dominion.
     *
     * @param string $source
     * @param Dominion $dominion
     * @param array $data In format: [$resource => $amount, $resource2 => $amount2] etc
     * @param int $hours
     */
    /*
    public function queueResources(string $source, Dominion $dominion, array $data, int $hours = 12): void
    {
        $data = array_map('\intval', $data);
        $now = now();

        foreach ($data as $resource => $amount) {
            if ($amount === 0) {
                continue;
            }
            $q = $this->getQueue($source, $dominion);
            $existingQueueRow =
                $q->filter(static function ($row) use ($resource, $hours) {
                    return (
                        ($row->resource === $resource) &&
                        ((int)$row->hours === $hours)
                    );
                })->first();

            if ($existingQueueRow === null) {
                DB::table('dominion_queue')->insert([
                    'dominion_id' => $dominion->id,
                    'source' => $source,
                    'resource' => $resource,
                    'hours' => $hours,
                    'amount' => $amount,
                    'created_at' => $now,
                ]);

            } else {
                DB::table('dominion_queue')->where([
                    'dominion_id' => $dominion->id,
                    'source' => $source,
                    'resource' => $resource,
                    'hours' => $hours,
                ])->update([
                    'amount' => DB::raw("amount + $amount"),
                ]);
            }
        }
    }
    */
    /*
    public function queueResources(string $source, Dominion $dominion, array $data, int $ticks = 12): void
    {
        $data = array_map('\intval', $data);
        $now = now();

        foreach ($data as $resource => $amount) {
            if ($amount === 0) {
                continue;
            }

            DB::table('dominion_queue')->updateOrInsert(
                [
                    'dominion_id' => $dominion->id,
                    'source' => $source,
                    'resource' => $resource,
                    'hours' => $ticks,
                ],
                [
                    'amount' => DB::raw("amount + $amount"),
                    'created_at' => $now,
                ]
            );
        }
    }
    */
    public function queueResources(string $source, Dominion $dominion, array $data, int $ticks = 12): void
    {
        $data = array_map('\intval', $data);
        $now = now();
    
        foreach ($data as $resource => $amount)
        {
            if ($amount === 0)
            {
                continue;
            }

            // No xtLog() here, as it creates frontend output
            Log::info("[{$dominion->id}] Queue for {$dominion->name} / source: $source / resource: $resource / amount: $amount / ticks: $ticks");
    
            $attempts = 10; // Number of attempts to retry
            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                try {
                    $sql = "INSERT INTO `dominion_queue` (`dominion_id`, `source`, `resource`, `hours`, `amount`, `created_at`)
                    VALUES (:dominion_id, :source, :resource, :hours, :amount, :created_at)
                    ON DUPLICATE KEY UPDATE `amount` = `amount` + VALUES(`amount`), `created_at` = VALUES(`created_at`)";
                    
                    $bindings = [
                        'dominion_id' => $dominion->id,
                        'source' => $source,
                        'resource' => $resource,
                        'hours' => $ticks,
                        'amount' => $amount,
                        'created_at' => $now
                    ];
    
                    DB::transaction(function () use ($sql, $bindings) {
                        DB::statement($sql, $bindings);
                    });
                    break; // If successful, exit the loop
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->getCode() == 1213 && $attempt < $attempts) { // Deadlock
                        sleep(1); // Wait a bit before retrying
                        xtLog("[{$dominion->id}] Deadlock detected in QueueService::queueResources(), retrying... (attempt $attempt/$attempts)");
                        continue;
                    }
                    throw $e; // Re-throw the exception if it's not a deadlock or attempts exceeded
                }
            }
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
    
        $source = strtolower(Arr::get($methodParts, '1'));
        $method = implode('', Arr::except($methodParts, '1'));
        array_unshift($arguments, $source);
    
        return \call_user_func_array([$this, $method], $arguments);
    }
}
