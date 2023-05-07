<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\TerrainHelper;

use OpenDominion\Models\Dominion;
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
        $landSize = 0;

        foreach($dominion->terrains as $terrain)
        {
            $landSize += $terrain->pivot->amount;
        }

        return $landSize;
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
            foreach ($dominion->terrains as $terrainKey => $terrain) {
                $terrainLost['available'][$terrainKey] = intval(round(negative($landLost * ($terrain->pivot->amount / $totalTerrainedLand))));
            }
        }
    
        if (array_sum($terrainLost['available']) < $landLost)
        {
            $terrainLost['queued'] = array_fill_keys(Terrain::pluck('key')->toArray(), 0);
            $rezoningQueueTotal = $this->queueService->getRezoningQueueTotal($dominion);
    
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
    

    public function getTerrainDiscovered(Dominion $dominion, int $landChange, $isLoss = false): array
    {
        $terrainChanged = [];
        foreach(Terrain::all() as $terrain)
        {
            $terrainRatio = $dominion->{'terrain_' . $terrain->key} / $dominion->land;
            $terrainChanged[$terrain->key] = floor($landChange * $terrainRatio) * ($isLoss ? -1 : 1);
        }
    
        if(array_sum($terrainChanged) !== $landChange)
        {
            $terrainChanged[$dominion->race->homeTerrain()->key] += ($landChange - array_sum($terrainChanged)) * ($isLoss ? -1 : 1);
        }

        $terrainChanged = array_map('intval', $terrainChanged);

        $terrainChanged = array_filter($terrainChanged, function($value) {
            return $value !== 0;
        });    
    
        return $terrainChanged;
    }

}
