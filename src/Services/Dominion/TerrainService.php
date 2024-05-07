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

    public function update(Dominion $dominion, array $data): void
    {
        foreach($data as $terrainKey => $amount)
        {
            $terrain = Terrain::where('key', $terrainKey)->first();
    
            if (!$terrain or $amount == 0) {
                continue;
            }
    
            $dominionTerrain = DominionTerrain::firstOrNew([
                'dominion_id' => $dominion->id,
                'terrain_id' => $terrain->id,
            ]);
            
            $dominionTerrain->save();
    
            if ($amount > 0)
            {
                $dominionTerrain->increment('amount', $amount);
            }
            else
            {
                $amount = abs($amount);
                $amount = min($amount, $dominionTerrain->amount);
                $dominionTerrain->decrement('amount', $amount);
            }
    
            $dominionTerrain->save();

            if ($dominionTerrain->amount <= 0)
            {
                $dominionTerrain->delete();
            }
        }
    }

    public function auditAndRepairTerrain(Dominion $dominion): void
    {
        /* These are the cases when repairing should be done cases that should be repaired:
        * 1. Dominion has more terrain than land: remove terrain
        * 2. Dominion has less terrain than land: add terrain
        *
        * If the amount of terrain owned is equal to land, the audit is passed.
        *
        * Turn off logging in the future.
        */


        #Log::info("[TERRAIN AUDIT] Auditing {$dominion->name} (# {$dominion->realm->number}).");

        if($this->terrainCalculator->hasTerrainAmountEqualToLand($dominion))
        {
            # Audit passed!
            #Log::info("[TERRAIN AUDIT] Audit passed for {$dominion->name} (# {$dominion->realm->number}).");
            return;
        }


        $terrainedLand = $this->terrainCalculator->getTotalTerrainedAmount($dominion);

        # If has MoreTerrainThanLand
        if($this->terrainCalculator->hasMoreTerrainThanLand($dominion))
        {
            #Log::info("[TERRAIN AUDIT] Audit failed for {$dominion->name} (# {$dominion->realm->number}): has more terrain than land.");
            #ldump("[TERRAIN AUDIT] Audit failed for {$dominion->name} (# {$dominion->realm->number}): has more terrain than land.");

            DB::transaction(function () use ($dominion, $terrainedLand)
            {
                $difference = $this->terrainCalculator->getTerrainLandAmountDifference($dominion, true);
                $amountLeftToRemove = $difference;

                # Move on to existing terrain
                if($amountLeftToRemove > 0)
                {
                    foreach($dominion->terrains->sortByDesc('pivot.amount') as $dominionTerrain)
                    {
                        $terrainRatio = $dominionTerrain->pivot->amount / $terrainedLand;
                        $amountToRemove = (int)round($difference * $terrainRatio);
                        $amountToRemove = min($amountToRemove, $amountLeftToRemove);

                        if($amountLeftToRemove > 0 and $amountToRemove > 0)
                        {
                            $this->update($dominion, [$dominionTerrain->key => ($amountToRemove * -1)]);
                            #Log::info("[TERRAIN AUDIT] Removed {$amountToRemove} {$dominionTerrain->key} terrain from {$dominion->name} (# {$dominion->realm->number})");
                            #ldump("[TERRAIN AUDIT] Removed {$amountToRemove} {$dominionTerrain->key} terrain from {$dominion->name} (# {$dominion->realm->number})");

                            $amountLeftToRemove -= $amountToRemove;
                        }

                        $amountLeftToRemove = max(0, $amountLeftToRemove);
                    }
                }

                if($amountLeftToRemove > 0)
                {
                    $this->update($dominion, [$dominion->race->homeTerrain()->key => ($amountLeftToRemove * -1)]);
                    #Log::info("[TERRAIN AUDIT] Rounding correction: {$amountLeftToRemove} {$dominion->race->homeTerrain()->key} removed.");
                    #ldump("[TERRAIN AUDIT] Rounding correction: {$amountLeftToRemove} {$dominion->race->homeTerrain()->key} removed.");
                }

            });

            return;
        }

        if($this->terrainCalculator->hasLessTerrainThanLand($dominion))
        {
            #Log::info("[TERRAIN AUDIT] Audit failed for {$dominion->name} (# {$dominion->realm->number}): has more terrain than land.");
            #ldump("[TERRAIN AUDIT] Audit failed for {$dominion->name} (# {$dominion->realm->number}): has more terrain than land.");


            DB::transaction(function () use ($dominion, $terrainedLand)
            {
                $difference = $this->terrainCalculator->getTerrainLandAmountDifference($dominion, true);
                $amountLeftToAdd = $difference;
                $terrainKeys = Terrain::inRandomOrder()->pluck('key')->toArray();

                #ldump('Terrain missing: ' . $difference);
                $terrainAdded = 0;

                # Move on to existing terrain
                if($amountLeftToAdd > 0)
                {
                    foreach($terrainKeys as $terrainKey)
                    {
                        $dominionTerrain = $dominion->terrains->where('key', $terrainKey)->first();

                        if($dominionTerrain and $dominionTerrain->pivot->amount > 0)
                        {
                            $terrainRatio = $dominionTerrain->pivot->amount / $terrainedLand;
                        }
                        elseif(!isset($dominionTerrain))
                        {
                            $terrainRatio = 1 / count($terrainKeys);
                        }
                        else
                        {
                            #Log::info("[TERRAIN AUDIT] Skipping {$terrainKey} terrain for {$dominion->name} (# {$dominion->realm->number}): no ratio available.");
                            #ldump("[TERRAIN AUDIT] Skipping {$terrainKey} terrain for {$dominion->name} (# {$dominion->realm->number}): no ratio available.");
                            continue;
                        }

                        #ldump('Terrain ratio: ' . $terrainRatio . ' for ' . $terrainKey);

                        $amountToAdd = (int)round($difference * $terrainRatio);
                        $amountToAdd = min($amountToAdd, $amountLeftToAdd);

                        if($amountToAdd > 0)
                        {
                            $this->update($dominion, [$terrainKey => $amountToAdd]);
                            #Log::info("[TERRAIN AUDIT] Added {$amountToAdd} {$terrainKey} terrain to {$dominion->name} (# {$dominion->realm->number})");
                            #ldump("[TERRAIN AUDIT] Added {$amountToAdd} {$terrainKey} terrain to {$dominion->name} (# {$dominion->realm->number})");

                            $amountLeftToAdd -= $amountToAdd;
                        }

                        $amountLeftToAdd = max(0, $amountLeftToAdd);
                        $terrainAdded += $amountToAdd;

                        #ldump('* Difference: ' . $difference . ' / Terrained Land: ' . $terrainedLand . ' / Amount Left to Add: ' . $amountLeftToAdd);
                    }
                }

                if($amountLeftToAdd > 0)
                {
                    $terrainAdded += $amountLeftToAdd;
                    $this->update($dominion, [$dominion->race->homeTerrain()->key => $amountLeftToAdd]);
                    #Log::info("[TERRAIN AUDIT] Rounding correction: {$amountLeftToAdd} {$dominion->race->homeTerrain()->key} added.");
                    #ldump("[TERRAIN AUDIT] Rounding correction: {$amountLeftToAdd} {$dominion->race->homeTerrain()->key} added.");
                }

                #ldd('Total terrain added: '. $terrainAdded);

            });

            return;
        }

        Log::info("[TERRAIN AUDIT] Audit failed for {$dominion->name} (# {$dominion->realm->number}): unknown error.");

    }


    public function handleTerrainTransformation(Dominion $dominion): void
    {
        $continue = false;
        $toTerrainKey = null;
        $maxTerrainToTransform = 0;
        foreach(Terrain::all()->pluck('key') as $terrainKey)
        {
            if($transformationPerkRatio = $dominion->race->getPerkMultiplier('turns_terrain_to_' . $terrainKey))
            {
                $toTerrainKey = $terrainKey;
                $maxTerrainToTransform = $dominion->land * $transformationPerkRatio;
            }
        }

        # Amount of terrain that isn't this terrain
        $otherTerrainAmount = $dominion->land - $dominion->{'terrain_' . $toTerrainKey};

        if($maxTerrainToTransform == 0 or $otherTerrainAmount == 0)
        {
            return;
        }

        $terrainToRemove = (int)ceil($maxTerrainToTransform);

        DB::transaction(function () use ($dominion, $toTerrainKey, $terrainToRemove)
        {
            foreach($dominion->terrains as $dominionTerrain)
            {
                if($dominionTerrain->key == $toTerrainKey)
                {
                    continue;
                }

                if(($terrainAmount = $dominionTerrain->pivot->amount) > 0)
                {
                    $amountToRemove = (int)ceil(min($terrainToRemove, $terrainAmount));
                    $this->update($dominion, [$dominionTerrain->key => ($amountToRemove * -1)]);
                    $this->update($dominion, [$toTerrainKey => $amountToRemove]);
                    $terrainToRemove -= $amountToRemove;
                }
            }
        });


    }
}
