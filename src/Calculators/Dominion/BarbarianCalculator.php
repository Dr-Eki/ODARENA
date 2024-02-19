<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;
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
        $this->queueService = app(QueueService::class);
        $this->statsService = app(StatsService::class);

        $this->settings = config('barbarians.settings');
    }


    public function getSetting(string $setting): string
    {
        return $this->settings[$setting] ?? null;
    }

    public function getDpaTarget(Dominion $dominion = null, Round $round = null, ?float $npcModifier = 1000): int
    {
        # Get DPA target for a specific dominion/barbarian
        if($dominion)
        {
            $dpa = $this->getSetting('DPA_CONSTANT');
            $dpa += $dominion->ticks * $this->getSetting('DPA_PER_TICK');
            $dpa += $this->statsService->getStat($dominion, 'defense_failures') * $this->getSetting('DPA_PER_TIMES_INVADED');
            $dpa *= ($dominion->npc_modifier / 1000);
        }
        # Get DPA target in general
        elseif($round)
        {
            $dpa = $this->getSetting('DPA_CONSTANT') + ($round->ticks * $this->getSetting('DPA_PER_TICK'));
            $dpa *= ($npcModifier / 1000);
        }

        $round = $round ?? Round::find($dominion->round_id);

        # Special for round league ID 7
        if($round->league->id == 7)
        {
            $dpa /= 4;
            $dpa = ceil($dpa);
        }

        return $dpa;
    }


    public function getOpaTarget(Dominion $dominion = null, Round $round = null, float $npcModifier = 1000): int
    {
        return $this->getDpaTarget($dominion, $round, $npcModifier) * $this->getSetting('OPA_MULTIPLIER');
    }

    # Includes units out on attack.
    public function getDpCurrent(Dominion $dominion): int
    {
        $dp = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 2) * $this->getSetting('UNIT2_DP');
        $dp += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 3) * $this->getSetting('UNIT3_DP');

        return $dp;
    }

    # Includes units at home and out on attack.
    public function getOpCurrent(Dominion $dominion): int
    {
        $op = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 1) * $this->getSetting('UNIT1_OP');
        $op += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 4) * $this->getSetting('UNIT4_OP');

        return $op;
    }

    # Includes units at home and out on attack.
    public function getOpAtHome(Dominion $dominion): int
    {
        $op = $dominion->military_unit1 * $this->getSetting('UNIT1_OP');
        $op += $dominion->military_unit4 * $this->getSetting('UNIT4_OP');

        return $op;
    }

    public function getDpPaid(Dominion $dominion): int
    {
        $dp = $this->getDpCurrent($dominion);
        $dp += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit2') * $this->getSetting('UNIT2_DP');
        $dp += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit3') * $this->getSetting('UNIT3_DP');
        $dp += $this->queueService->getSummoningQueueTotalByResource($dominion, 'military_unit2') * $this->getSetting('UNIT2_DP');
        $dp += $this->queueService->getSummoningQueueTotalByResource($dominion, 'military_unit3') * $this->getSetting('UNIT3_DP');

        return $dp;
    }

    public function getOpPaid(Dominion $dominion): int
    {
        $op = $this->getOpCurrent($dominion);
        $op += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit1') * $this->getSetting('UNIT1_OP');
        $op += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit4') * $this->getSetting('UNIT4_OP');
        $op += $this->queueService->getSummoningQueueTotalByResource($dominion, 'military_unit1') * $this->getSetting('UNIT1_OP');
        $op += $this->queueService->getSummoningQueueTotalByResource($dominion, 'military_unit4') * $this->getSetting('UNIT4_OP');

        return $op;
    }

    public function getDpaCurrent(Dominion $dominion): int
    {
        return $this->getDpCurrent($dominion) / $dominion->land;
    }

    public function getOpaCurrent(Dominion $dominion): int
    {
        return $this->getOpCurrent($dominion) / $dominion->land;
    }


    public function getDpaPaid(Dominion $dominion): int
    {
        return $this->getDpPaid($dominion) / $dominion->land;
    }

    public function getOpaPaid(Dominion $dominion): int
    {
        return $this->getOpPaid($dominion) / $dominion->land;
    }

    public function getOpaAtHome(Dominion $dominion): int
    {
        return $this->getOpAtHome($dominion) / $dominion->land;
    }

    public function getOpaDeltaPaid(Dominion $dominion): int
    {
        return $this->getOpaTarget($dominion) - $this->getOpaPaid($dominion);
    }

    public function getDpaDeltaPaid(Dominion $dominion): int
    {
        return $this->getDpaTarget($dominion) - $this->getDpaPaid($dominion);
    }

    public function getOpaDeltaAtHome(Dominion $dominion): int
    {
        return $this->getOpaTarget($dominion) - $this->getOpaAtHome($dominion);
    }

    public function getDpaDeltaCurrent(Dominion $dominion): int
    {
        return $this->getDpaTarget($dominion) - $this->getDpaCurrent($dominion);
    }

    public function getAmountToInvest(Dominion $barbarian): int
    {
        return 6000 * (1 + $barbarian->ticks / 400);
    }

}
