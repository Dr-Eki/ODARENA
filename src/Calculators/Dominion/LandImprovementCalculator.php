<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

# ODA
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\LandImprovementsHelper;
use OpenDominion\Helpers\RaceHelper;

class LandImprovementCalculator
{

    protected $landHelper;
    protected $landImprovementsHelper;
    protected $landCalculator;
    protected $prestigeCalculator;
    protected $raceHelper;

    public function __construct(
        LandHelper $landHelper,
        LandImprovementsHelper $landImprovementsHelper,
        LandCalculator $landCalculator,
        PrestigeCalculator $prestigeCalculator,
        RaceHelper $raceHelper
        )
    {
        $this->prestigeCalculator = $prestigeCalculator;
        $this->landCalculator = $landCalculator;
        $this->landHelper = $landHelper;
        $this->landImprovementsHelper = $landImprovementsHelper;
        $this->raceHelper = $raceHelper;
    }

    /**
     * Returns the Dominion's improvement multiplier for a given improvement type.
     *
     * @param Dominion $dominion
     * @param string $improvementType
     * @return float
     */
    private function getLandImprovementBonusMultiplier(Dominion $dominion): float
    {
        $bonus = 0;
        if($dominion->race->getPerkValue('land_improvements'))
        {
            $bonus = 1;
            $bonus += $dominion->getTechPerkMultiplier('improvements');
            $bonus *= 1 + $this->prestigeCalculator->getPrestigeMultiplier($dominion);
        }

        return $bonus;
    }

    # Plat bonus from Mountains
    public function getGoldProductionBonus(Dominion $dominion): float
    {
        $bonus = 0;
        if($dominion->race->getPerkValue('land_improvements'))
        {
            $bonus = 1 * ($dominion->land_mountain / $this->landCalculator->getTotalLand($dominion));
            $bonus *= $this->getLandImprovementBonusMultiplier($dominion);
        }

        return $bonus;
    }

    # Pop bonus from Forest
    public function getPopulationBonus(Dominion $dominion): float
    {
        $bonus = 0;
        if($dominion->race->getPerkValue('land_improvements'))
        {
            $bonus = 0.75 * ($dominion->land_forest / $this->landCalculator->getTotalLand($dominion));
            $bonus *= $this->getLandImprovementBonusMultiplier($dominion);
        }

        return $bonus;
    }

    # Wiz bonus from Swamps
    public function getWizardPowerBonus(Dominion $dominion): float
    {
        $bonus = 0;
        if($dominion->race->getPerkValue('land_improvements'))
        {
            $bonus = 2 * ($dominion->land_swamp / $this->landCalculator->getTotalLand($dominion));
            $bonus *= $this->getLandImprovementBonusMultiplier($dominion);
        }

        return $bonus;
    }

    # DP bonus from Hills
    public function getDefensivePowerBonus(Dominion $dominion): float
    {
        $bonus = 0;
        if($dominion->race->getPerkValue('land_improvements'))
        {
            $bonus = 0.75 * ($dominion->land_hill / $this->landCalculator->getTotalLand($dominion));
            $bonus *= $this->getLandImprovementBonusMultiplier($dominion);
        }

        return $bonus;
    }

    # OP bonus from Plains
    public function getOffensivePowerBonus(Dominion $dominion): float
    {
        $bonus = 0;
        if($dominion->race->getPerkValue('land_improvements'))
        {
            $bonus = 0.15 * ($dominion->land_plain / $this->landCalculator->getTotalLand($dominion));
            $bonus *= $this->getLandImprovementBonusMultiplier($dominion);
        }

        return $bonus;
    }

    # Food bonus from Water
    public function getFoodProductionBonus(Dominion $dominion): float
    {
        $bonus = 0;
        if($dominion->race->getPerkValue('land_improvements'))
        {
            $bonus = 5 * ($dominion->land_water / $this->landCalculator->getTotalLand($dominion));
            $bonus *= $this->getLandImprovementBonusMultiplier($dominion);
        }

        return $bonus;
    }

    # Boat bonus from Water
    public function getBoatProductionBonus(Dominion $dominion): float
    {
        $bonus = 0;
        if($dominion->race->getPerkValue('land_improvements'))
        {
            $bonus = 5 * ($dominion->land_water / $this->landCalculator->getTotalLand($dominion));
            $bonus *= $this->getLandImprovementBonusMultiplier($dominion);
        }

        return $bonus;
    }

}
