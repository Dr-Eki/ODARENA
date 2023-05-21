<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use Illuminate\Support\Collection;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Building;
use OpenDominion\Models\Round;

use Illuminate\Support\Carbon;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;

class BarbarianCalculator
{

    protected const DPA_CONSTANT = 27.5;
    protected const DPA_OVERSHOT = 1.10;
    protected const DPA_PER_TICK = 0.125;
    protected const DPA_PER_TIMES_INVADED = 0.006;

    protected const OPA_MULTIPLIER = 1.10;

    # Train % of new units as specs. /1000
    protected const SPECS_RATIO_MIN = 50;
    protected const SPECS_RATIO_MAX = 500;

    # Chance to hit
    protected const CHANCE_TO_HIT_CONSTANT = 14;

    # Gain % of land between these two values when hitting. /1000
    # Current formula returns:
    #  40% - 1.5% raw 3.0% total
    #  60% - 1.7% raw 3.4% total
    #  65% - 2.3% raw 4.6% total
    #  75% - 3.6% raw 7.2% total
    #  85% - 4.6% raw 9.2% total
    #  95% - 5.5% raw 11.0% total
    # 100% - 6.0% raw 12.0% total
    # 120% - 8.0% raw 16.0% total
    # 133% - 9.20% raw 18.4% total

    protected const LAND_GAIN_MIN = 30;
    protected const LAND_GAIN_MAX = 100;

    # Send between these two values when hitting. /1000
    protected const SENT_RATIO_MIN = 800;
    protected const SENT_RATIO_MAX = 1000;

    # Lose % of units between these two values when hitting. /1000
    protected const CASUALTIES_MIN = 50;
    protected const CASUALTIES_MAX = 100;

    # Train between these two values % of required units per tick. /1000
    // Disabled, always training 100%.
    protected const UNITS_TRAINED_MIN = 800;
    protected const UNITS_TRAINED_MAX = 1200;

    # Training time in ticks
    protected const UNITS_TRAINING_TICKS = 6;
    protected const CONSTRUCTION_TIME = 12;

    # Unit powers
    protected const UNIT1_OP = 3;
    protected const UNIT2_DP = 3;
    protected const UNIT3_DP = 5;
    protected const UNIT4_OP = 5;

    # Chance each tick for new Barbarian to spawn
    protected const ONE_IN_CHANCE_TO_SPAWN = 60;

    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var QueueService */
    protected $queueService;

    /** @var StatsService */
    protected $statsService;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

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
    }

    public function getSettings(): array
    {
        $settings = [
            'DPA_CONSTANT' => static::DPA_CONSTANT,
            'DPA_OVERSHOT' => static::DPA_OVERSHOT,
            'DPA_PER_TICK' => static::DPA_PER_TICK,
            'DPA_PER_TIMES_INVADED' => static::DPA_PER_TIMES_INVADED,
            'OPA_MULTIPLIER' => static::OPA_MULTIPLIER,
            'SPECS_RATIO_MIN' => static::SPECS_RATIO_MIN,
            'SPECS_RATIO_MAX' => static::SPECS_RATIO_MAX,
            'CHANCE_TO_HIT_CONSTANT' => static::CHANCE_TO_HIT_CONSTANT,
            'LAND_GAIN_MIN' => static::LAND_GAIN_MIN,
            'LAND_GAIN_MAX' => static::LAND_GAIN_MAX,
            'SENT_RATIO_MIN' => static::SENT_RATIO_MIN,
            'SENT_RATIO_MAX' => static::SENT_RATIO_MAX,
            'CASUALTIES_MIN' => static::CASUALTIES_MIN,
            'CASUALTIES_MAX' => static::CASUALTIES_MAX,
            'UNITS_TRAINED_MIN' => static::UNITS_TRAINED_MIN,
            'UNITS_TRAINED_MAX' => static::UNITS_TRAINED_MAX,
            'UNITS_TRAINING_TICKS' => static::UNITS_TRAINING_TICKS,
            'CONSTRUCTION_TIME' => static::CONSTRUCTION_TIME,
            'UNIT1_OP' => static::UNIT1_OP,
            'UNIT2_DP' => static::UNIT2_DP,
            'UNIT3_DP' => static::UNIT3_DP,
            'UNIT4_OP' => static::UNIT4_OP,
            'ONE_IN_CHANCE_TO_SPAWN' => static::ONE_IN_CHANCE_TO_SPAWN,
        ];

        return $settings;
    }

    public function getSetting(string $setting): string
    {
        $value = null;
        $settings = $this->getSettings();
        if(isset($settings[$setting]))
        {
            $value = $settings[$setting];
        }

        return $value;
    }

    public function getDpaTarget(Dominion $dominion = null, Round $round = null, ?float $npcModifier = 1000): int
    {
        # Get DPA target for a specific dominion/barbarian
        if($dominion)
        {
            $dpa = static::DPA_CONSTANT;
            $dpa += $dominion->ticks * static::DPA_PER_TICK;
            $dpa += $this->statsService->getStat($dominion, 'defense_failures') * static::DPA_PER_TIMES_INVADED;
            $dpa *= ($dominion->npc_modifier / 1000);
        }
        # Get DPA target in general
        elseif($round)
        {
            $dpa = static::DPA_CONSTANT + ($round->ticks * static::DPA_PER_TICK);
            $dpa *= ($npcModifier / 1000);
        }

        $round = $round ?? Round::find($dominion->round_id);

        # Special for round league ID 7
        if($round->league->id == 7)
        {
            $dpa /= 5;
            $dpa = ceil($dpa);
        }

        return $dpa;
    }


    public function getOpaTarget(Dominion $dominion = null, Round $round = null, float $npcModifier = 1000): int
    {
        return $this->getDpaTarget($dominion, $round, $npcModifier) * static::OPA_MULTIPLIER;
    }

    # Includes units out on attack.
    public function getDpCurrent(Dominion $dominion): int
    {
        $dp = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 2) * static::UNIT2_DP;
        $dp += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 3) * static::UNIT3_DP;

        return $dp;
    }

    # Includes units at home and out on attack.
    public function getOpCurrent(Dominion $dominion): int
    {
        $op = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 1) * static::UNIT1_OP;
        $op += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 4) * static::UNIT4_OP;

        return $op;
    }

    # Includes units at home and out on attack.
    public function getOpAtHome(Dominion $dominion): int
    {
        $op = $dominion->military_unit1 * static::UNIT1_OP;
        $op += $dominion->military_unit4 * static::UNIT4_OP;

        return $op;
    }

    public function getDpPaid(Dominion $dominion): int
    {
        $dp = $this->getDpCurrent($dominion);
        $dp += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit2') * static::UNIT2_DP;
        $dp += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit3') * static::UNIT3_DP;

        return $dp;
    }

    public function getOpPaid(Dominion $dominion): int
    {
        $op = $this->getOpCurrent($dominion);
        $op += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit1') * static::UNIT1_OP;
        $op += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit4') * static::UNIT4_OP;

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
