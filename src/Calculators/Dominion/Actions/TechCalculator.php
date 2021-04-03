<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Tech;

class TechCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /**
     * TechCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     */
    public function __construct(
        LandCalculator $landCalculator,
        ImprovementCalculator $improvementCalculator)
    {
        $this->landCalculator = $landCalculator;
        $this->improvementCalculator = $improvementCalculator;
    }

    /**
     * Returns the Dominion's current experience point cost to unlock a new tech.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTechCost(Dominion $dominion, Tech $techToUnlock = Null, int $level = 1): int
    {
        # Start at 0
        $cost = 0;

        # Add 5 x acres, minimum 5,000
        $cost += max($this->landCalculator->getTotalLand($dominion) * 5, 5000);

        # Add extra cost from level (from $techToUnlock, if known, or from $level)
        if($techToUnlock !== null)
        {
            $techToUnlock = Tech::where('key', $techToUnlock->key)->first();
            $cost *= 1 + ($techToUnlock->level - 1) / 10;
        }
        elseif(isset($level) and ($level >= 1 and $level <= 10))
        {
            $cost *= 1 + ($level - 1) / 10;
        }

        $cost *= 1 + $this->getTechCostMultiplier($dominion);

        return $cost;
    }

    public function maxLevelAfforded(Dominion $dominion)
    {
        $xp = $dominion->resource_tech;
        if($xp > 4999)
        {
            for ($level = 10; $level >= 1; $level--)
            {
                if($xp >= $this->getTechCost($dominion, null, $level))
                {
                    return $level;
                }
            }
        }
        return 0;

    }

    public function canAffordTech(Dominion $dominion, int $level = 1): bool
    {
        $cost = $this->getTechCost($dominion, null, $level);

        return $dominion->resource_tech >= $cost;

    }

    public function getTechCostMultiplier(Dominion $dominion)
    {
        $multiplier = 0;

        # Perk
        if($dominion->race->getPerkMultiplier('tech_costs'))
        {
          $multiplier += $dominion->race->getPerkMultiplier('tech_costs');
        }

        return $multiplier;
    }

    /**
     * Determine if the Dominion meets the requirements to unlock a new tech.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function hasPrerequisites(Dominion $dominion, Tech $tech): bool
    {
        $unlockedTechs = $dominion->techs->pluck('key')->all();

        return count(array_diff($tech->prerequisites, $unlockedTechs)) == 0;
    }


    /**
     * Determine if the Dominion has a tech.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function hasTech(Dominion $dominion, Tech $tech): bool
    {
        $unlockedTechs = $dominion->techs->pluck('key')->all();

        return in_array($tech->key, $unlockedTechs);
    }

}
