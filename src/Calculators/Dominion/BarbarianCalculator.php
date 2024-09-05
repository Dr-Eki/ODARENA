<?php

// We want strict types here.
declare(strict_types=1);

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Models\Unit;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\UnitCalculator;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;

class BarbarianCalculator
{

    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var QueueService */
    protected $queueService;

    /** @var StatsService */
    protected $statsService;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var UnitCalculator */
    protected $unitCalculator;

    protected $settings;

    /**
     * BuildingCalculator constructor.
     *
     * @param BuildingHelper $buildingHelper
     * @param QueueService $queueService
     */
    public function __construct()
    {
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->unitCalculator = app(UnitCalculator::class);

        $this->queueService = app(QueueService::class);
        $this->statsService = app(StatsService::class);

        $this->settings = config('barbarians.settings');
    }

    public function getAmountToInvest(Dominion $barbarian): int
    {
        return roundInt(6000 * (1 + $barbarian->ticks / 400));
    }


    public function getDpaTarget(Dominion $barbarian = null, Round $round = null, float $npcModifier = 1000.00): int
    {

        $dpa = $this->settings['DPA_CONSTANT'];

        # Get DPA target for a specific dominion/barbarian
        if($barbarian)
        {
            $dpa += $barbarian->ticks * $this->settings['DPA_PER_TICK'];
            $dpa += $this->statsService->getStat($barbarian, 'defense_failures') * $this->settings['DPA_PER_TIMES_INVADED'];
            $dpa *= ($barbarian->npc_modifier / 1000);
        }
        # Get DPA target in general
        elseif($round)
        {
            $dpa = $this->settings['DPA_CONSTANT'] + ($round->ticks * $this->settings['DPA_PER_TICK']);
            $dpa *= ($npcModifier / 1000);
        }

        return roundInt($dpa);
    }

    public function getOpaTarget(Dominion $barbarian = null, Round $round = null, float $npcModifier = 1000.00): int
    {
        return roundInt($this->getDpaTarget($barbarian, $round, $npcModifier) * $this->settings['OPA_MULTIPLIER']);
    }

    public function getTargetedDefensivePower(Dominion $barbarian): int
    {
        $land = $barbarian->land + $this->queueService->getInvasionQueueTotalByResource($barbarian, 'land');
        return roundInt($this->getDpaTarget($barbarian) * $land);
    }

    public function getTargetedOffensivePower(Dominion $barbarian): int
    {
        $land = $barbarian->land + $this->queueService->getInvasionQueueTotalByResource($barbarian, 'land');
        return roundInt($this->getOpaTarget($barbarian) * $land);
    }

    public function getCurrentDefensivePower(Dominion $dominion): int
    {
        $slot = 1;
        $currentUnits = $dominion->{'military_unit' . $slot};

        $currentDp = $this->militaryCalculator->getDefensivePower($dominion, null, null, [$slot => $currentUnits]);

        return roundInt($currentDp);
    }

    public function getIncomingDefensivePower(Dominion $dominion): int
    {
        $incomingDp = 0;

        $slot = 1;
        $incomingUnits = $this->unitCalculator->getUnitTypeTotalIncoming($dominion, ('military_unit' . $slot));

        if($incomingUnits > 0)
        {
            $unit = $dominion->race->units->where('slot', $slot)->first();
            $unitDp = $this->militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unit, 'defense');
            $dpMod = $this->militaryCalculator->getDefensivePowerMultiplier($dominion);
    
            $incomingDp = $incomingUnits * $unitDp * $dpMod;
        }

        return roundInt($incomingDp);
    }

    public function getCurrentOffensivePower(Dominion $dominion): int
    {
        $slot = 2;
        $currentUnits = $dominion->{'military_unit' . $slot};

        $currentOp = $this->militaryCalculator->getOffensivePower($dominion, null, null, [$slot => $currentUnits]);

        return roundInt($currentOp);
    }

    public function getIncomingOffensivePower(Dominion $dominion): int
    {
        $incomingOp = 0;

        $slot = 2;
        $incomingUnits = $this->unitCalculator->getUnitTypeTotalIncoming($dominion, ('military_unit' . $slot));

        if($incomingUnits > 0)
        {
            $unit = $dominion->race->units->where('slot', $slot)->first();
            $unitOp = $this->militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unit, 'offense');
            $opMod = $this->militaryCalculator->getOffensivePowerMultiplier($dominion);
    
            $incomingOp = $incomingUnits * $unitOp * $opMod;
        }

        return roundInt($incomingOp);
    }

    public function getPaidDefensivePower(Dominion $dominion): int
    {
        return roundInt($this->getCurrentDefensivePower($dominion) + $this->getIncomingDefensivePower($dominion));
    }

    public function getPaidOffensivePower(Dominion $dominion): int
    {
        return roundInt($this->getCurrentOffensivePower($dominion) + $this->getIncomingOffensivePower($dominion));
    }

    public function getMissingDefensivePower(Dominion $dominion): int
    {
        return roundInt(max(0, $this->getTargetedDefensivePower($dominion) - $this->getPaidDefensivePower($dominion)));
    }

    public function getMissingOffensivePower(Dominion $dominion): int
    {
        return roundInt(max(0, $this->getTargetedOffensivePower($dominion) - $this->getPaidOffensivePower($dominion)));
    }

    public function getDefensiveUnitsToTrain(Dominion $dominion): int
    {
        $missingDp = $this->getMissingDefensivePower($dominion);

        $unitsToTrain = 0;

        if($missingDp <= 0)
        {
            return $unitsToTrain;
        }

        $slot = 1;
        $unit = $dominion->race->units->where('slot', $slot)->first();
        $unitDp = $this->militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unit, 'defense');
        $dpMod = $this->militaryCalculator->getDefensivePowerMultiplier($dominion);

        $unitsToTrain = $missingDp / ($unitDp * $dpMod);
        $unitsToTrain *= $this->settings['DPA_OVERSHOT']; 

        return ceilInt($unitsToTrain);
    }

    public function getOffensiveUnitsToTrain(Dominion $dominion): int
    {
        $missingOp = $this->getMissingOffensivePower($dominion);

        $unitsToTrain = 0;

        if($missingOp <= 0)
        {
            return $unitsToTrain;
        }

        $slot = 2;
        $unit = $dominion->race->units->where('slot', $slot)->first();
        $unitOp = $this->militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unit, 'offense');
        $opMod = $this->militaryCalculator->getOffensivePowerMultiplier($dominion);

        $unitsToTrain = $missingOp / ($unitOp * $opMod);
        $unitsToTrain *= $this->settings['OPA_OVERSHOT'];

        return ceilInt($unitsToTrain);
    }

    public function getExcessiveDefensivePower(Dominion $dominion): int
    {
        return roundInt(max(0, $this->getPaidDefensivePower($dominion) - $this->getTargetedDefensivePower($dominion)));
    }

    public function getExcessiveOffensivePower(Dominion $dominion): int
    {
        return roundInt(max(0, $this->getPaidOffensivePower($dominion) - $this->getTargetedOffensivePower($dominion)));
    }

    public function getDefensiveUnitsToRelease(Dominion $dominion): int
    {
        $excessiveDp = $this->getExcessiveDefensivePower($dominion);

        $unitsToRelease = 0;

        if($excessiveDp <= 0)
        {
            return $unitsToRelease;
        }

        $slot = 1;
        $unit = $dominion->race->units->where('slot', $slot)->first();
        $unitDp = $this->militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unit, 'defense');
        $dpMod = $this->militaryCalculator->getDefensivePowerMultiplier($dominion);

        $unitsToRelease = $excessiveDp / ($unitDp * $dpMod);
        #$unitsToRelease /= $this->settings['DPA_OVERSHOT']; 

        $unitsToRelease = max(0, $unitsToRelease);

        return floorInt($unitsToRelease);
    }

    public function getOffensiveUnitsToRelease(Dominion $dominion): int
    {
        $excessiveOp = $this->getExcessiveOffensivePower($dominion);

        $unitsToRelease = 0;

        if($excessiveOp <= 0)
        {
            return $unitsToRelease;
        }

        $slot = 2;
        $unit = $dominion->race->units->where('slot', $slot)->first();
        $unitDp = $this->militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unit, 'offense');
        $dpMod = $this->militaryCalculator->getDefensivePowerMultiplier($dominion);

        $unitsToRelease = $excessiveOp / ($unitDp * $dpMod);
        #$unitsToRelease /= $this->settings['OPA_OVERSHOT']; 

        $unitsToRelease = max(0, $unitsToRelease);

        return floorInt($unitsToRelease);
    }


    public function needsToTrainDefensivePower(Dominion $dominion): bool
    {
        return $this->getMissingDefensivePower($dominion) > 0;
    }

    public function needsToTrainOffensivePower(Dominion $dominion): bool
    {
        return $this->getMissingOffensivePower($dominion) > 0;
    }

    public function getChanceToHit(Dominion $dominion): int
    {
        $currentDay = $dominion->round->start_date->subDays(1)->diffInDays(now());

        $chanceOneIn = $this->settings['CHANCE_TO_HIT_CONSTANT'] - (14 - $currentDay);
        $chanceOneIn += $this->statsService->getStat($dominion, 'defense_failures') * 0.125;

        return roundInt($chanceOneIn);
    }

}
