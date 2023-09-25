<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Log;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionTerrain;
use OpenDominion\Models\Terrain;
use OpenDominion\Calculators\Dominion\TerrainCalculator;
use OpenDominion\Helpers\TerrainHelper;
use OpenDominion\Services\Dominion\QueueService;

class TerrainService
{

    protected $queueService;
    protected $terrainCalculator;
    protected $terrainHelper;

    public function __construct()
    {
        $this->queueService = app(QueueService::class);
        $this->terrainCalculator = app(TerrainCalculator::class);
        $this->terrainHelper = app(TerrainHelper::class);
    }

    public function update(Dominion $dominion, array $terrainKeys): void
    {
        foreach($terrainKeys as $terrainKey => $amount)
        {
            # Positive values: create or update DominionTerrain
            if($amount > 0)
            {
                $terrain = Terrain::where('key', $terrainKey)->first();
                $amount = intval(max(0, $amount));
                $dominionTerrain = DominionTerrain::where(['dominion_id' => $dominion->id, 'terrain_id' => $terrain->id])->first();

                if($dominionTerrain)
                {
                    $dominionTerrain->increment('amount', $amount);
                }
                else
                {
                    DB::transaction(function () use ($dominion, $terrain, $amount)
                    {
                        DominionTerrain::create([
                            'dominion_id' => $dominion->id,
                            'terrain_id' => $terrain->id,
                            'amount' => $amount
                        ]);
                    });
                }
            }
            # Negative values: update or delete DominionTerrain
            else
            {
                $terrain = Terrain::where('key', $terrainKey)->first();

                $owned = $dominion->{'terrain_' . $terrainKey};

                $amountToRemove = min(abs($amount), $owned);

                if($owned)
                {
                    if($amountToRemove)
                    {
                        DB::transaction(function () use ($dominion, $terrain, $amountToRemove)
                        {
                            DominionTerrain::where('dominion_id', $dominion->id)->where('terrain_id', $terrain->id)
                            ->decrement('amount', $amountToRemove);
                        });
                    }
                    else
                    {
                        DB::transaction(function () use ($dominion, $terrain)
                        {
                            DominionTerrain::where('dominion_id', $dominion->id)->where('terrain_id', $terrain->id)
                            ->delete();
                        });
                    }
                }
            }
        }
    }

    public function auditAndRepairTerrain(Dominion $dominion): void
    {

        $unterrainedLand = $this->terrainCalculator->getUnterrainedLand($dominion);
        $terrainedLand = $this->terrainCalculator->getTotalTerrainedAmount($dominion);
        $totalTerrainBeingRezoned = 0;
        $queueData = [];

        foreach($dominion->queues as $queue)
        {
            if($queue->source == 'rezoning')
            {
                $terrainKey = str_replace('terrain_', '', $queue->resource);
                if(Terrain::where('key', $terrainKey)->first())
                {
                    $queueData[$queue->resource] = ($queueData[$queue->resource] ?? 0) + $queue->amount;
                }
            }
        }

        $totalTerrainBeingRezoned = array_sum($queueData);

        #dd($unterrainedLand);

        if(($unterrainedLand + $totalTerrainBeingRezoned) == 0)
        {
            ldd('ook0');
            return;
        }

        # If unterrainedLand is positive, add terrain
        if(($unterrainedLand - $totalTerrainBeingRezoned) > 0)
        {
            DB::transaction(function () use ($dominion, $unterrainedLand, $terrainedLand, $totalTerrainBeingRezoned)
            {
                $totalTerrainToAdd = abs($unterrainedLand - $totalTerrainBeingRezoned);
                $terrainLeftToAdd = $totalTerrainToAdd;

                foreach($dominion->terrains as $dominionTerrain)
                {
                    $terrainRatio = $dominionTerrain->pivot->amount / $terrainedLand;
                    $amountToAdd = (int)round($totalTerrainToAdd * $terrainRatio);
    
                    if($totalTerrainToAdd > 0)
                    {
                        $this->update($dominion, [$dominionTerrain->key => $amountToAdd]);
                        Log::info("[TERRAIN AUDIT] Added {$amountToAdd} {$dominionTerrain->key} terrain to {$dominion->name} (# {$dominion->realm->number})");
                        ldump("[TERRAIN AUDIT] Added {$amountToAdd} {$dominionTerrain->key} terrain to {$dominion->name} (# {$dominion->realm->number})");
                    }

                    $terrainLeftToAdd -= $amountToAdd;
                    $terrainLeftToAdd = max(0, $terrainLeftToAdd);
                }

                #ldd('ook1');
            });

            return;
        }

        # If unterrainedLand is positive, remove terrain
        if(($unterrainedLand + $totalTerrainBeingRezoned) > 0)
        {
            #ldump('Terrain to remove: ' . abs($unterrainedLand + $totalTerrainBeingRezoned));

            DB::transaction(function () use ($dominion, $unterrainedLand, $terrainedLand, $totalTerrainBeingRezoned, $queueData)
            {
                $totalTerrainToRemove = abs($unterrainedLand + $totalTerrainBeingRezoned);
                $terrainLeftToRemove = $totalTerrainToRemove;

                // Start by removing queued land if any
                if($totalTerrainBeingRezoned)
                {
                    foreach($queueData as $queuedTerrainKey => $queuedAmount)
                    {
                        $queuedTerrainRatio = $queuedAmount / $totalTerrainBeingRezoned;
                        $amountToRemove = (int)round($totalTerrainBeingRezoned * $queuedTerrainRatio);
                        $amountToRemove = min($amountToRemove, $terrainLeftToRemove);

                        #ldump($queuedTerrainKey .':'. $queuedAmount.'-queued:'. $queuedTerrainRatio.'-ratio:'. $amountToRemove.'-remove:'. $terrainLeftToRemove.'-leftToRemove');
                        
                        if($terrainLeftToRemove > 0 and $amountToRemove > 0)
                        {
                            $this->queueService->dequeueResource('rezoning', $dominion, $queuedTerrainKey, $amountToRemove);
                            Log::info("[TERRAIN AUDIT] Dequeued {$amountToRemove} {$queuedTerrainKey} terrain from {$dominion->name} (# {$dominion->realm->number})");
                            ldump("[TERRAIN AUDIT] Dequeued {$amountToRemove} {$queuedTerrainKey} terrain from {$dominion->name} (# {$dominion->realm->number})");

                            $terrainLeftToRemove -= $amountToRemove;
                        }

                        $terrainLeftToRemove = max(0, $terrainLeftToRemove);
                        
                    }
                }

                // Move on to existing terrain
                if($terrainLeftToRemove > 0)
                {
                    foreach($dominion->terrains->sortByDesc('pivot.amount') as $dominionTerrain)
                    {
                        $terrainRatio = $dominionTerrain->pivot->amount / $terrainedLand;
                        $amountToRemove = (int)round($totalTerrainToRemove * $terrainRatio);
                        $amountToRemove = min($amountToRemove, $terrainLeftToRemove);
    
                        if($terrainLeftToRemove > 0 and $amountToRemove > 0)
                        {
                            $this->update($dominion, [$dominionTerrain->key => ($amountToRemove * -1)]);
                            Log::info("[TERRAIN AUDIT] Removed {$amountToRemove} {$dominionTerrain->key} terrain from {$dominion->name} (# {$dominion->realm->number})");
                            ldump("[TERRAIN AUDIT] Removed {$amountToRemove} {$dominionTerrain->key} terrain from {$dominion->name} (# {$dominion->realm->number})");

                            $terrainLeftToRemove -= $amountToRemove;
                        }

                        $terrainLeftToRemove = max(0, $terrainLeftToRemove);
                        
                    }
                }

                #ldd('ook2');
            });

            return;
        }

        #dd('ook4');
    }
}
