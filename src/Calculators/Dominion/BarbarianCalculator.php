<?php

// We want strict types here.
declare(strict_types=1);

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;

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


    public function getDpaTarget(Dominion $dominion = null, Round $round = null, ?float $npcModifier = 1000): int
    {
        # Get DPA target for a specific dominion/barbarian
        if($dominion)
        {
            $dpa = $this->settings['DPA_CONSTANT'];
            $dpa += $dominion->ticks * $this->settings['DPA_PER_TICK'];
            $dpa += $this->statsService->getStat($dominion, 'defense_failures') * $this->settings['DPA_PER_TIMES_INVADED'];
            $dpa *= ($dominion->npc_modifier / 1000);
        }
        # Get DPA target in general
        elseif($round)
        {
            $dpa = $this->settings['DPA_CONSTANT'] + ($round->ticks * $this->settings['DPA_PER_TICK']);
            $dpa *= ($npcModifier / 1000);
        }

        return ceilInt($dpa);
    }


    public function getOpaTarget(Dominion $dominion = null, Round $round = null, float $npcModifier = 1000): int
    {
        return floorInt($this->getDpaTarget($dominion, $round, $npcModifier) * $this->settings['OPA_MULTIPLIER']);
    }

    # Includes units out on attack.
    public function getDpCurrent(Dominion $dominion): int
    {
        $unitAmount = $this->unitCalculator->getUnitTypeTotalTrained($dominion, 'military_unit1');

        return ceilInt($this->militaryCalculator->getDefensivePower($dominion, null, null, [1 => $unitAmount]));
    }

    # Includes units at home and out on attack.
    public function getOpCurrent(Dominion $dominion): int
    {
        $unitAmount = $this->unitCalculator->getUnitTypeTotalTrained($dominion, 'military_unit2');

        return ceilInt($this->militaryCalculator->getOffensivePower($dominion, null, null, [2 => $unitAmount]));
    }

    # Includes units at home and out on attack.
    public function getOpAtHome(Dominion $dominion): int
    {
        $unitAmount = $this->unitCalculator->getUnitTypeTotalAtHome($dominion, 'military_unit2');

        return ceilInt($this->militaryCalculator->getOffensivePower($dominion, null, null, [2 => $unitAmount]));
    }

    public function getDpPaid(Dominion $dominion): int
    {
        $unitAmount = $this->unitCalculator->getUnitTypeTotalPaid($dominion, 'military_unit1');

        return ceilInt($this->militaryCalculator->getDefensivePower($dominion, null, null, [1 => $unitAmount]));
    }

    public function getOpPaid(Dominion $dominion): int
    {
        $unitAmount = $this->unitCalculator->getUnitTypeTotalPaid($dominion, 'military_unit2');

        return ceilInt($this->militaryCalculator->getOffensivePower($dominion, null, null, [2 => $unitAmount]));
    }

    public function getDpaCurrent(Dominion $dominion): int
    {
        return roundInt($this->getDpCurrent($dominion) / $dominion->land);
    }

    public function getOpaCurrent(Dominion $dominion): int
    {
        return roundInt($this->getOpCurrent($dominion) / $dominion->land);
    }

    public function getDpaPaid(Dominion $dominion): int
    {
        return roundInt($this->getDpPaid($dominion) / $dominion->land);
    }

    public function getOpaPaid(Dominion $dominion): int
    {
        return roundInt($this->getOpPaid($dominion) / $dominion->land);
    }

    public function getOpaAtHome(Dominion $dominion): int
    {
        return roundInt($this->getOpAtHome($dominion) / $dominion->land);
    }

    public function getOpaDeltaPaid(Dominion $dominion): int
    {
        return roundInt($this->getOpaTarget($dominion) - $this->getOpaPaid($dominion));
    }

    public function getDpaDeltaPaid(Dominion $dominion): int
    {
        return roundInt($this->getDpaTarget($dominion) - $this->getDpaPaid($dominion));
    }

    public function getOpaDeltaAtHome(Dominion $dominion): int
    {
        return roundInt($this->getOpaTarget($dominion) - $this->getOpaAtHome($dominion));
    }

    public function getDpaDeltaCurrent(Dominion $dominion): int
    {
        return roundInt($this->getDpaTarget($dominion) - $this->getDpaCurrent($dominion));
    }

    public function getAmountToInvest(Dominion $barbarian): int
    {
        return roundInt(6000 * (1 + $barbarian->ticks / 400));
    }

}
