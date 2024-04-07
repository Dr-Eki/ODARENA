<?php

namespace OpenDominion\Calculators\Dominion;

use Log;
use OpenDominion\Exceptions\GameException;

use OpenDominion\Helpers\TerrainHelper;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Terrain;

use OpenDominion\Services\Dominion\QueueService;

class TerrainCalculator
{
    protected $dominionCalculator;
    protected $queueService;
    protected $terrainHelper;

    public function __construct(
        DominionCalculator $dominionCalculator,
        QueueService $queueService,
        TerrainHelper $terrainHelper
    ) {
        $this->dominionCalculator = $dominionCalculator;
        $this->queueService = $queueService;
        $this->terrainHelper = $terrainHelper;
    }

    public function getUnterrainedLand(Dominion $dominion): int
    {
        return $dominion->land - $this->getTotalTerrainedAmount($dominion);
    }

    public function getTotalTerrainedAmount(Dominion $dominion): int
    {
        return $dominion->terrains->sum('pivot.amount');
    }

    public function getTotalTerrainedRezoning(Dominion $dominion): int
    {
        return $this->queueService->getRezoningQueueTotal($dominion);
    }

    public function getTerrainLost(Dominion $dominion, int $landLost): array
    {
        
        $terrainLost = [
            'available' => [],
            'queued' => []
        ];

        if($landLost <= 0)
        {
            return $terrainLost;
        }
    
        $totalTerrainedLand = $this->getTotalTerrainedAmount($dominion);
    
        if ($totalTerrainedLand > 0) {
            foreach ($dominion->terrains as $dominionTerrain)
            {
                $terrainLost['available'][$dominionTerrain->key] = intval(round(negative($landLost * ($dominionTerrain->pivot->amount / $totalTerrainedLand))));
            }
        }
    
        if ($landLost > $totalTerrainedLand)
        {
            $terrainLost['queued'] = array_fill_keys(Terrain::pluck('key')->toArray(), 0);
            $rezoningQueueTotal = $this->queueService->getRezoningQueueTotal($dominion);

            if($rezoningQueueTotal <= 0)
            {
                Log::error('Rezoning queue total is 0 or less, but terrain left to lose after taking from available is greater than 0. This should not happen.');
                Log::error('Dominion: ' . $dominion->id . ' (' . $dominion->name . ')');
                Log::error('Land lost: ' . $landLost);
                Log::error('Total terrained land: ' . $totalTerrainedLand);
                Log::error('Terrain lost: ' . print_r($terrainLost, true));
                Log::error('Rezoning queue total: ' . $rezoningQueueTotal);
                Log::error('Rezoning queue: ' . print_r($this->queueService->getRezoningQueue($dominion), true));
                Log::error('An error occurred while calculating terrain lost. This may fix itself after next tick. Please report it as a bug.');

                #throw new GameException('An error occurred while calculating terrain lost. This may fix itself after next tick. Please report it as a bug.');
            }
    
            $terrainLeftToLose = $landLost - array_sum($terrainLost['available']);
    
            $lastNonZeroTerrainKey = null;
            foreach ($terrainLost['queued'] as $terrainKey => $terrainAmount) {
                $terrainLost['queued'][$terrainKey] = intval($terrainLeftToLose * ($this->queueService->getRezoningQueueTotalByResource($dominion, ('terrain_' . $terrainKey)) / $rezoningQueueTotal));
                
                if ($terrainLost['queued'][$terrainKey] > 0) {
                    $lastNonZeroTerrainKey = $terrainKey;
                }
            }
    
            // Adjust the last non-zero terrain by the difference between the desired total and the current total
            if ($lastNonZeroTerrainKey !== null) {
                $terrainLost['queued'][$lastNonZeroTerrainKey] += $terrainLeftToLose - array_sum($terrainLost['queued']);
            }

            $terrainLost['queued'] = array_filter($terrainLost['queued'], function($value) {
                return $value !== 0;
            });
        }
    
        return $terrainLost;
    }
    

    public function getTerrainDiscovered(Dominion $dominion, int $landChange): array
    {
        $terrainChanged = [];
        foreach(Terrain::all() as $terrain)
        {
            $terrainRatio = $dominion->{'terrain_' . $terrain->key} / $dominion->land;
            $terrainChanged[$terrain->key] = round($landChange * $terrainRatio); # floor() caused missing land
        }
    
        if(array_sum($terrainChanged) !== $landChange)
        {
            $terrainChanged[$dominion->race->homeTerrain()->key] += ($landChange - array_sum($terrainChanged));
        }

        $terrainChanged = array_map('intval', $terrainChanged);

        $terrainChanged = array_filter($terrainChanged, function($value) {
            return $value !== 0;
        });    
    
        return $terrainChanged;
    }

    # Audit support functions

    public function hasMoreTerrainThanLand(Dominion $dominion): bool
    {
        return ($this->getTotalTerrainedAmount($dominion) + $this->getTotalTerrainedRezoning($dominion)) > $dominion->land;
    }

    public function hasLessTerrainThanLand(Dominion $dominion): bool
    {
        return ($this->getTotalTerrainedAmount($dominion) + $this->getTotalTerrainedRezoning($dominion)) < $dominion->land;
    }

    public function hasTerrainAmountEqualToLand(Dominion $dominion): bool
    {
        return ($this->getTotalTerrainedAmount($dominion) + $this->getTotalTerrainedRezoning($dominion)) == $dominion->land;
    }

    public function getTerrainLandAmountDifference(Dominion $dominion, bool $returnAbsolute): int
    {
        #$difference = ($this->getTotalTerrainedAmount($dominion) + $this->getTotalTerrainedRezoning($dominion)) - $dominion->land;
        $difference = ($this->getTotalTerrainedAmount($dominion) + $this->getTotalTerrainedRezoning($dominion)) - $dominion->land;

        if($returnAbsolute)
        {
            return abs($difference);
        }

        return $difference;
    }

    public function getStartingTerrain(Race $race, int $startingLand = 1000): array
    {
        $terrains = Terrain::all();
        $landLeftToDistribute = $startingLand;

        $startingTerrain = [];

        #foreach($terrains as $terrain)
        #{
        #    $startingTerrain[$terrain->key] = 0;
        #}

        if($race->key == 'barbarian')
        {

            $startingTerrainKeys = array_keys($startingTerrain);
            $startingTerrainRatios = [];
            $sum = 0;

            // Generate a random ratio for each terrain
            foreach ($startingTerrainKeys as $terrainKey) {
                $ratio = mt_rand(5, 20) / 100;
                $startingTerrainRatios[$terrainKey] = $ratio;
                $sum += $ratio;
            }

            // Normalize the ratios so their sum is exactly 1
            foreach ($startingTerrainRatios as $terrainKey => $ratio) {
                $startingTerrainRatios[$terrainKey] = $ratio / $sum;
            }
            
            // Apply the ratio to all terrains
            foreach($startingTerrain as $terrainKey => $terrainAmount)
            {
                $startingTerrain[$terrainKey] = (int)round($startingLand * $startingTerrainRatios[$terrainKey]);
                $landLeftToDistribute -= $startingTerrain[$terrainKey];
            }

            // Distribute any remaining land to home terrain
            if($landLeftToDistribute > 0)
            {
                $startingTerrain[$race->homeTerrain()->key] += max(0, $landLeftToDistribute);
            }
        }
        else
        {
            $startingTerrain[$race->homeTerrain()->key] = $startingLand;
        }

        #$startingTerrain = array_filter($startingTerrain, function($value) {
        #    return $value !== 0;
        #});

        return $startingTerrain;
    }

}
