<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\TerrainHelper;
use OpenDominion\Models\Dominion;

use OpenDominion\Models\Realm;

class TerrainCalculator
{
    protected $dominionCalculator;
    protected $terrainHelper;

    public function __construct(
        DominionCalculator $dominionCalculator,
        TerrainHelper $terrainHelper
    ) {
        $this->dominionCalculator = $dominionCalculator;
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
        $terrains = $dominion->terrains->keyBy('key');
        $terrainLost = [];

        foreach($terrains as $terrainKey => $terrain)
        {
            $terrainLost[$terrainKey] = 0;
        }

        $totalTerrainedLand = $this->getTotalTerrainedAmount($dominion);

        if($totalTerrainedLand > 0)
        {
            foreach($terrains as $terrainKey => $terrain)
            {
                $terrainLost[$terrainKey] = intval(round($landLost * ($terrain->pivot->amount / $totalTerrainedLand)))*-1;
            }
        }

        return $terrainLost;
    }

    /**
     * Returns the Dominion's total acres of land.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTotalLand(Dominion $dominion, bool $canBeZero = false): int
    {
        $totalLand = 0;

        foreach ($this->landHelper->getLandTypes() as $landType)
        {
            $totalLand += $dominion->{'land_' . $landType};
        }

        if($canBeZero)
        {
            return $totalLand;
        }
        else
        {
            return max(1,$totalLand);
        }
    }

    public function getTotalLandIncoming(Dominion $dominion): int
    {
        $incoming = 0;
        foreach ($this->landHelper->getLandTypes() as $landType)
        {
            $incoming += $this->queueService->getExplorationQueueTotalByResource($dominion, "land_{$landType}");
            $incoming += $this->queueService->getInvasionQueueTotalByResource($dominion, "land_{$landType}");
            $incoming += $this->queueService->getExpeditionQueueTotalByResource($dominion, "land_{$landType}");
        }

        return $incoming;
    }

    /**
     * Returns the Dominion's total acres of barren land.
     * In this function, queued buildings still count as barren.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTotalBarrenLandForSwarm(Dominion $dominion): int
    {
        return ($this->getTotalLand($dominion) - $this->buildingCalculator->getTotalBuildings($dominion));
    }

    /**
     * Returns the Dominion's total barren land by land type.
     *
     * @param Dominion $dominion
     * @param string $landType
     * @return int
     */
    public function getTotalBarrenLandByLandType(Dominion $dominion, $landType): int
    {
        return $this->getBarrenLandByLandType($dominion)[$landType];
    }

    /**
     * Returns the Dominion's barren land by land type.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getBarrenLandByLandType(Dominion $dominion): array
    {
        $barrenLandByLandType = [];
        $landTypes = $this->landHelper->getLandTypes();
        $availableBuildings = $this->buildingHelper->getBuildingsByRace($dominion->race);
        $dominionBuildings = $this->buildingCalculator->getDominionBuildings($dominion);

        foreach ($landTypes as $landType)
        {
            $barrenLandByLandType[$landType] = 0;

            $barren = $dominion->{'land_' . $landType};
            foreach($availableBuildings->where('land_type',$landType) as $building)
            {
                if(isset($dominionBuildings->where('building_id', $building->id)->first()->owned) and $dominionBuildings->where('building_id', $building->id)->first()->owned > 0)
                {
                    $barren -= $dominionBuildings->where('building_id', $building->id)->first()->owned;
                }
                $barren -= $this->queueService->getConstructionQueueTotalByResource($dominion, "building_{$building->key}");
                $barren -= $this->queueService->getSabotageQueueTotalByResource($dominion, "building_{$building->key}");
            }

            $barrenLandByLandType[$landType] += $barren;
        }

        if($dominion->race->getPerkValue('indestructible_buildings'))
        {
            foreach($barrenLandByLandType as $landType => $barren)
            {
            #    $barrenLandByLandType[$landType] = max(0, $barren);
            }
        }

        return $barrenLandByLandType;

    }

    public function getLandByLandType(Dominion $dominion): array
    {
        $return = [];
        foreach ($this->landHelper->getLandTypes() as $landType)
        {
            $return[$landType] = $dominion->{"land_{$landType}"};
        }

        return $return;
    }

    public function getLandLostByLandType(Dominion $dominion, float $landLossRatio): array
    {
        $targetLand = $this->getTotalLand($dominion);
        $totalLandToLose = (int)floor($targetLand * $landLossRatio);
        $barrenLandByLandType = $this->getBarrenLandByLandType($dominion);
        $landPerType = $this->getLandByLandType($dominion);

        arsort($landPerType);

        $landLeftToLose = $totalLandToLose;
//        $totalLandLost = 0;
        $landLostByLandType = [];

        foreach ($landPerType as $landType => $totalLandForType) {
            if ($landLeftToLose === 0) {
                break;
            }

            $landTypeLoss = ($totalLandForType * $landLossRatio);

            $totalLandTypeLoss = (int)ceil($landTypeLoss);

            if ($totalLandTypeLoss === 0) {
                continue;
            }

            if ($totalLandTypeLoss > $landLeftToLose) {
                $totalLandTypeLoss = $landLeftToLose;
            }

//            $totalLandLost += $totalLandTypeLoss;
            $barrenLandForLandType = $barrenLandByLandType[$landType];

            if ($barrenLandForLandType <= $totalLandTypeLoss) {
                $barrenLandLostForLandType = $barrenLandForLandType;
            } else {
                $barrenLandLostForLandType = $totalLandTypeLoss;
            }

            $buildingsToDestroy = $totalLandTypeLoss - $barrenLandLostForLandType;
            $landLostByLandType[$landType] = [
                'land_lost' => $totalLandTypeLoss,
                'barrenLandLost' => $barrenLandLostForLandType,
                'buildingsToDestroy' => $buildingsToDestroy
            ];

            $landLeftToLose -= $totalLandTypeLoss;
        }

        return $landLostByLandType;
    }

    /**
     * Returns the Dominion's total acres of land.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTotalLandForRealm(Realm $realm): int
    {
      $land = 0;

      foreach ($realm->dominions as $dominion)
      {
          $land += $this->getTotalLand($dominion);
      }

      return $land;
  }

}
