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
        /* These are the cases when repairing should be done cases that should be repaired:
        * 1. Dominion has more terrain than land: remove terrain
        * 2. Dominion has more terrain land plus terrain being rezoned than land: remove terrain (rezoned terrain first, then existing terrain)
        * 3. Dominion has less terrain than land and no terrain being rezoned: add terrain
        * 4. Dominion has less terrain than land and terrain being rezoned: add terrain but consider land+rezoned terrain as total land
        *
        * If the amount of terrain owned (meaning terrain attached and terrain rezoned) is equal to land, the audit is passed.
        *
        * Turn off logging in the future.
        */

        if($this->terrainCalculator->hasTerrainAmountEqualToLand($dominion))
        {
            # Audit passed!
            Log::info("[TERRAIN AUDIT] Audit passed for {$dominion->name} (# {$dominion->realm->number}).");
            return;
        }

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

        # If has MoreTerrainThanLand
        if($this->terrainCalculator->hasMoreTerrainThanLand($dominion))
        {
            Log::info("[TERRAIN AUDIT] Audit failed for {$dominion->name} (# {$dominion->realm->number}): has more terrain than land.");
            #ldump("[TERRAIN AUDIT] Audit failed for {$dominion->name} (# {$dominion->realm->number}): has more terrain than land.");

            $difference = $this->terrainCalculator->getTerrainLandAmountDifference($dominion, true);
            $amountLeftToRemove = $difference;
            $differenceMinusRezoning = $difference - $totalTerrainBeingRezoned;

            // Begin with total terrain being rezoned
            if($totalTerrainBeingRezoned > 0)
            {
                foreach($queueData as $queuedTerrainKey => $queuedAmount)
                {
                    if($queuedAmount == 0)
                    {
                        continue;
                    }

                    $queuedTerrainRatio = $queuedAmount / $totalTerrainBeingRezoned;
                    $amountToRemove = (int)round($totalTerrainBeingRezoned * $queuedTerrainRatio);
                    $amountToRemove = min($amountToRemove, $amountLeftToRemove);
                    
                    if($amountLeftToRemove > 0 and $amountToRemove > 0)
                    {
                        $this->queueService->dequeueResource('rezoning', $dominion, $queuedTerrainKey, $amountToRemove);
                        Log::info("[TERRAIN AUDIT] Dequeued {$amountToRemove} {$queuedTerrainKey} terrain from {$dominion->name} (# {$dominion->realm->number})");
                        #ldump("[TERRAIN AUDIT] Dequeued {$amountToRemove} {$queuedTerrainKey} terrain from {$dominion->name} (# {$dominion->realm->number})");

                        $amountLeftToRemove -= $amountToRemove;
                    }

                    $amountLeftToRemove = max(0, $amountLeftToRemove);

                    if($amountLeftToRemove == 0)
                    {
                        break;
                    }
                }
            }

            // Move on to existing terrain
            if($amountLeftToRemove > 0)
            {
                foreach($dominion->terrains->sortByDesc('pivot.amount') as $dominionTerrain)
                {
                    $terrainRatio = $dominionTerrain->pivot->amount / $terrainedLand;
                    $amountToRemove = (int)round($differenceMinusRezoning * $terrainRatio);
                    $amountToRemove = min($amountToRemove, $amountLeftToRemove);

                    if($amountLeftToRemove > 0 and $amountToRemove > 0)
                    {
                        $this->update($dominion, [$dominionTerrain->key => ($amountToRemove * -1)]);
                        Log::info("[TERRAIN AUDIT] Removed {$amountToRemove} {$dominionTerrain->key} terrain from {$dominion->name} (# {$dominion->realm->number})");
                        #ldump("[TERRAIN AUDIT] Removed {$amountToRemove} {$dominionTerrain->key} terrain from {$dominion->name} (# {$dominion->realm->number})");

                        $amountLeftToRemove -= $amountToRemove;
                    }

                    $amountLeftToRemove = max(0, $amountLeftToRemove);
                }
            }

            #ldd('hasMoreTerrainThanLand');
            return;
        }

        if($this->terrainCalculator->hasLessTerrainThanLand($dominion))
        {
            Log::info("[TERRAIN AUDIT] Audit failed for {$dominion->name} (# {$dominion->realm->number}): has less terrain than land.");

            DB::transaction(function () use ($dominion, $unterrainedLand, $terrainedLand, $totalTerrainBeingRezoned)
            {
                $totalTerrainToAdd = abs($unterrainedLand - $totalTerrainBeingRezoned);
                $terrainLeftToAdd = $totalTerrainToAdd;
                $terrainAdded = 0;

                foreach($dominion->terrains as $dominionTerrain)
                {
                    $terrainRatio = $dominionTerrain->pivot->amount / $terrainedLand;
                    $amountToAdd = (int)round($totalTerrainToAdd * $terrainRatio);
    
                    if($totalTerrainToAdd > 0)
                    {

                        # Check to not over round
                        if($terrainAdded + $amountToAdd > $totalTerrainToAdd)
                        {
                            $amountToAdd = $totalTerrainToAdd - $terrainAdded;
                        }

                        $this->update($dominion, [$dominionTerrain->key => $amountToAdd]);
                        Log::info("[TERRAIN AUDIT] Added {$amountToAdd} {$dominionTerrain->key} terrain to {$dominion->name} (# {$dominion->realm->number})");
                        #ldump("[TERRAIN AUDIT] Added {$amountToAdd} {$dominionTerrain->key} terrain to {$dominion->name} (# {$dominion->realm->number})");
                    }

                    $terrainLeftToAdd -= $amountToAdd;
                    $terrainLeftToAdd = max(0, $terrainLeftToAdd);
                    $terrainAdded += $amountToAdd;
                }
            });

            #ldd('hasLessTerrainThanLand');
            return;
            
        }

    }
}
