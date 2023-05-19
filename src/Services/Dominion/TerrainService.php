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
                        #dd('[MEGA ERROR] Trying to remove more of a terrain than you have. This might have been a temporary glitch due to multiple simultaneous events. Try again, but please report your findings on Discord.', $terrain, $amountToRemove, $owned);
                    }
                }
            }
        }
    }

    public function auditAndRepairTerrain(Dominion $dominion): void
    {
        $unterrainedLand = $this->terrainCalculator->getUnterrainedLand($dominion);
        $terrainedLand = $this->terrainCalculator->getTotalTerrainedAmount($dominion);
        if($unterrainedLand == 0)
        {
            return;
        }

        if($unterrainedLand < 0)
        {
            /* Calculate how much terrain should be removed (absolute value of $unterrainedLand)
            *   proportional to how much of that terrain is owned.
            */

            $totalTerrainToRemove = abs($unterrainedLand);

            foreach($dominion->terrains as $terrain)
            {
                $terrainRatio = $terrain->amount / $this->terrainCalculator->getTotalTerrainedAmount($dominion);
                $amountToRemove = round($totalTerrainToRemove * $terrainRatio);

                if($totalTerrainToRemove <= 0)
                {
                    $terrain->amount = max(0, $terrain->amount - $amountToRemove);
                    $terrain->save();

                    Log::info("[TERRAIN AUDIT] Removed {$amountToRemove} {$terrain->key} terrain from {$dominion->name} ({$dominion->realm->number})");
                }
            }

            return;
        }

        # Remove terrain being rezoned from $unterrainedLand
        $totalTerrainBeingRezoned = 0;
        foreach($dominion->queues as $queue)
        {
            if($queue->resource == 'land' and $queue->source == 'rezoning')
            {
                $totalTerrainBeingRezoned += $queue->amount;
            }
        }

        if(($unterrainedLand - $totalTerrainBeingRezoned) > 0 and $terrainedLand > 0)
        {
            /* Calculate how much terrain should be added (absolute value of $unterrainedLand)
            *   proportional to how much of that terrain is owned.
            */

            $totalTerrainToAdd = abs($unterrainedLand);

            foreach($dominion->terrains as $terrain)
            {
                $terrainRatio = $terrain->amount / $terrainedLand;
                $amountToAdd = round($totalTerrainToAdd * $terrainRatio);

                if($totalTerrainToAdd <= 0)
                {
                    $terrain->amount = max(0, $terrain->amount + $amountToAdd);
                    $terrain->save();

                    Log::info("[TERRAIN AUDIT] Added {$amountToAdd} {$terrain->key} terrain to {$dominion->name} ({$dominion->realm->number})");
                }
            }

            return;
        }

    }
}
