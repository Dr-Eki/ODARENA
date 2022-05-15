<?php

namespace OpenDominion\Calculators\Dominion;

use Log;

use Illuminate\Support\Carbon;

use OpenDominion\Helpers\ImprovementHelper;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Race;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Tech;
use OpenDominion\Models\Unit;

use OpenDominion\Calculators\Dominion\DeityCalculator;
use OpenDominion\Calculators\Dominion\LandImprovementCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;

use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;


class MilitaryCalculator
{
    /** @var bool */
    protected $forTick = false;

    public function __construct()
    {
        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->governmentService = app(GovernmentService::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->prestigeCalculator = app(PrestigeCalculator::class);
        $this->queueService = app(QueueService::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->statsService = app(StatsService::class);
        $this->techCalculator = app(TechCalculator::class);
        $this->landImprovementCalculator = app(LandImprovementCalculator::class);
        $this->improvementHelper = app(ImprovementHelper::class);
    }

    /**
     * Toggle if this calculator should include the following hour's resources.
     */
    public function setForTick(bool $value)
    {
        $this->forTick = $value;
        $this->queueService->setForTick($value);
    }

    /**
     * Returns the Dominion's offensive power.
     *
     * @param Dominion $dominion
     * @param Dominion|null $target
     * @param float|null $landRatio
     * @param array|null $units
     * @return float
     */
    public function getOffensivePower(
        Dominion $attacker,
        Dominion $defender = null,
        float $landRatio = null,
        array $units = null,
        array $calc = [],
        bool $isInvasion = false
    ): float
    {
        $op = ($this->getOffensivePowerRaw($attacker, $defender, $landRatio, $units, $calc) * $this->getOffensivePowerMultiplier($attacker, $defender));

        $op *= $this->getMoraleMultiplier($attacker);

        if($isInvasion)
        {
            $op *= $this->getOffensiveMultiplierReduction($defender, $isInvasion);
        }

        return $op;

        #return ($op * $this->getMoraleMultiplier($attacker));
    }

    /**
     * Returns the Dominion's raw offensive power.
     *
     * @param Dominion $dominion
     * @param Dominion|null $target
     * @param float|null $landRatio
     * @param array|null $units
     * @return float
     */
    public function getOffensivePowerRaw(
        Dominion $attacker,
        Dominion $defender = null,
        float $landRatio = null,
        array $units = null,
        array $calc = []
    ): float
    {
        $op = 0;

        foreach ($attacker->race->units as $unit)
        {
            $powerOffense = $this->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense', $calc, $units);
            $numberOfUnits = 0;

            if ($units === null)
            {
                $numberOfUnits = (int)$attacker->{'military_unit' . $unit->slot};
            }
            elseif (isset($units[$unit->slot]) && ((int)$units[$unit->slot] !== 0))
            {
                $numberOfUnits = (int)$units[$unit->slot];
            }

            if ($numberOfUnits !== 0)
            {
                $bonusOffense = 0;

                # Round 59: Without the if clauses, these two break each other (Artillery works but Yeti doesn't or vice versa)

                if($attacker->race->getUnitPerkValueForUnitSlot($unit->slot, "offense_from_pairing", null))
                {
                    $bonusOffense += $this->getBonusPowerFromPairingPerk($attacker, $unit, 'offense', $units);
                }

                if($attacker->race->getUnitPerkValueForUnitSlot($unit->slot, "offense_from_resource_capped_exhausting", null))
                {
                    $bonusOffense = $this->getUnitPowerFromResourceCappedExhaustingPerk($attacker, $unit, 'offense', $units);
                }

                $powerOffense += $bonusOffense / $numberOfUnits;
            }

            $op += ($powerOffense * $numberOfUnits);
        }

        $op += $this->getRawMilitaryPowerFromAnnexedDominions($attacker, $defender);

        return $op;
    }

    /**
     * Returns the Dominion's offensive power multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerMultiplier(Dominion $attacker, Dominion $defender = null): float
    {
        $multiplier = 1;

        // Buildings
        $multiplier += $attacker->getBuildingPerkMultiplier('offensive_power');

        // Deity
        $multiplier += $attacker->getDeityPerkMultiplier('offensive_power');

        # Retaliation perk
        if($attacker->round->mode == 'standard' or $attacker->round->mode == 'standard-duration' or $attacker->round->mode == 'artefacts')
        {
            if ($this->isOwnRealmRecentlyInvadedByTarget($attacker, $defender))
            {
                $multiplier += $attacker->getDeityPerkMultiplier('offensive_power_on_retaliation');
            }
        }
        elseif($attacker->round->mode == 'deathmatch' or $attacker->round->mode == 'deathmatch-duration')
        {
            if ($this->isSelfRecentlyInvadedByTarget($attacker, $defender))
            {
                $multiplier += $attacker->getDeityPerkMultiplier('offensive_power_on_retaliation');
            }
        }

        if ($attacker->hasDeity())
        {
            $multiplier += $attacker->getSpellPerkMultiplier('offensive_power_from_devotion');
        }

        // Improvements
        $multiplier += $attacker->getImprovementPerkMultiplier('offensive_power');

        // Racial Bonus
        $multiplier += $attacker->race->getPerkMultiplier('offense');

        // Artefact
        $multiplier += $attacker->realm->getArtefactPerkMultiplier('offensive_power');

        if($attacker->isMonarch())
        {
            $multiplier += $attacker->realm->getArtefactPerkMultiplier('governor_offensive_power');
        }

        // Techs
        $multiplier += $attacker->getTechPerkMultiplier('offense');

        // Spell
        $multiplier += $attacker->getSpellPerkMultiplier('offensive_power');

        // Prestige
        $multiplier += $this->prestigeCalculator->getPrestigeMultiplier($attacker);

        // Land improvements
        $multiplier += $attacker->getLandImprovementPerkMultiplier('offensive_power_mod');

        return $multiplier;
    }

    /**
     * Returns the Dominion's offensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerRatio(Dominion $dominion): float
    {
        return ($this->getOffensivePower($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's raw offensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerRatioRaw(Dominion $dominion): float
    {
        return ($this->getOffensivePowerRaw($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's defensive power.
     *
     * @param Dominion $dominion
     * @param Dominion|null $target
     * @param float|null $landRatio
     * @param array|null $units
     * @param float $multiplierReduction
     * @param bool $ignoreDraftees
     * @param bool $isAmbush
     * @param bool $ignoreRawDpFromBuildings
     * @param array $invadingUnits
     * @return float
     */
    public function getDefensivePower(
        Dominion $defender,                     # 1
        Dominion $attacker = null,              # 2
        float $landRatio = null,                # 3
        array $units = null,                    # 4
        float $multiplierReduction = 0,         # 5
        bool $ignoreDraftees = false,           # 6
        bool $isAmbush = false,                 # 7
        bool $ignoreRawDpFromBuildings = false, # 8
        array $invadingUnits = null,            # 9
        bool $ignoreRawDpFromAnnexedDominions = false # 10
    ): float
    {
        $dp = $this->getDefensivePowerRaw($defender, $attacker, $landRatio, $units, $multiplierReduction, $ignoreDraftees, $isAmbush, $ignoreRawDpFromBuildings, $invadingUnits, $ignoreRawDpFromAnnexedDominions);
        $dp *= $this->getDefensivePowerMultiplier($defender, $attacker, $multiplierReduction);

        return ($dp * $this->getMoraleMultiplier($defender));
    }

    /**
     * Returns the Dominion's raw defensive power.
     *
     * @param Dominion $dominion
     * @param Dominion|null $target
     * @param float|null $landRatio
     * @param array|null $units
     * @param bool $ignoreDraftees
     * @param bool $isAmbush
     * @return float
     */
    public function getDefensivePowerRaw(
        Dominion $defender,                             # 1
        Dominion $attacker = null,                      # 2
        float $landRatio = null,                        # 3
        array $units = null,                            # 4
        float $multiplierReduction = 0,                 # 5
        bool $ignoreDraftees = false,                   # 6
        bool $isAmbush = false,                         # 7
        bool $ignoreRawDpFromBuildings = false,         # 8
        array $invadingUnits = null,                    # 9
        bool $ignoreRawDpFromAnnexedDominions = false,  # 10
        bool $ignoreRawDpFromSpells = false             # 11
    ): float
    {
        $dp = 0;

        // Values
        $minDPPerAcre = 10; # LandDP
        $dpPerDraftee = 1;

        if($ignoreDraftees)
        {
            $dpPerDraftee = 0;
        }
        else
        {
            if($defender->race->getPerkValue('draftee_dp'))
            {
                $dpPerDraftee = $defender->race->getPerkValue('draftee_dp');
            }
            else
            {
                $dpPerDraftee = 1;
            }
        }

        # If DP per draftee is 0, ignore them (no casualties).
        if($dpPerDraftee === 0)
        {
            $ignoreDraftees = True;
        }

        // Peasants
        $dp += $defender->peasants * $defender->getSpellPerkValue('defensive_power_from_peasants');

        // Military
        foreach ($defender->race->units as $unit)
        {
            $powerDefense = $this->getUnitPowerWithPerks($defender, $attacker, $landRatio, $unit, 'defense', null, $units, $invadingUnits);

            $numberOfUnits = 0;

            if ($units === null)
            {
                $numberOfUnits = (int)$defender->{'military_unit' . $unit->slot};
            }
            elseif (isset($units[$unit->slot]) && ((int)$units[$unit->slot] !== 0))
            {
                $numberOfUnits = (int)$units[$unit->slot];
            }

            if ($numberOfUnits !== 0)
            {
                $bonusDefense = $this->getBonusPowerFromPairingPerk($defender, $unit, 'defense', $units);
                $powerDefense += $bonusDefense / $numberOfUnits;
            }

            $dp += ($powerDefense * $numberOfUnits);
        }

        // Draftees
        if (!$ignoreDraftees or isset($units['draftees']))
        {

            if ($units !== null && isset($units[0]))
            {
                $dp += ((int)$units[0] * $dpPerDraftee);
            }
            elseif ($units !== null && isset($units['draftees']))
            {
                $dp += ((int)$units['draftees'] * $dpPerDraftee);
            }
            else
            {
                $dp += ($defender->military_draftees * $dpPerDraftee);
            }
        }

        if (!$ignoreRawDpFromBuildings)
        {
            // Buildings
            $dp += $defender->getBuildingPerkValue('raw_defense');
        }

        if (!$ignoreRawDpFromSpells)
        {
            // Spells
            $dp += $defender->getSpellPerkValue('defense_from_resource');
        }

        if(!$ignoreRawDpFromAnnexedDominions)
        {
            $dp += $this->getRawMilitaryPowerFromAnnexedDominions($defender);
        }

        // Beastfolk: Ambush
        if($isAmbush)
        {
            $dp = $dp * (1 - $this->getRawDefenseAmbushReductionRatio($attacker));
        }

        // Attacking Forces skip land-based defenses
        if ($units !== null)
        {
            return $dp;
        }

        $dp = max($dp, $minDPPerAcre * $this->landCalculator->getTotalLand($defender));

        return $dp;
    }

    /**
     * Returns the Dominion's defensive power multiplier.
     *
     * @param Dominion $dominion
     * @param float $multiplierReduction
     * @return float
     */
    public function getDefensivePowerMultiplier(Dominion $dominion, Dominion $attacker = null, float $multiplierReduction = 0): float
    {
        $multiplier = 0;

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('defensive_power');

        // Deity
        $multiplier += $dominion->getDeityPerkMultiplier('defensive_power');

        // Improvements
        $multiplier += $dominion->getImprovementPerkMultiplier('defensive_power');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('defense');

        // Spell
        $multiplier += $this->getSpellMultiplier($dominion, $attacker, 'defense');

        // Land improvements
        $multiplier += $dominion->getLandImprovementPerkMultiplier('defensive_power_mod');

        // Multiplier reduction when we want to factor in temples from another dominion
        $multiplier = max(($multiplier + $multiplierReduction), 0);

        return 1 + $multiplier;
    }

    /**
     * Returns the Dominion's defensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerRatio(Dominion $dominion): float
    {
        return ($this->getDefensivePower($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's raw defensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerRatioRaw(Dominion $dominion): float
    {
        return ($this->getDefensivePowerRaw($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    public function getUnitPowerWithPerks(
        Dominion $dominion,
        ?Dominion $target,
        ?float $landRatio,
        Unit $unit = null,
        string $powerType,
        ?array $calc = [],
        array $units = null,
        array $invadingUnits = null
    ): float
    {
        if($unit == null)
        {
            return 0;
        }

        $unitPower = $unit->{"power_$powerType"};

        $unitPower += $this->getUnitPowerFromLandBasedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromBuildingBasedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromWizardRatioPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromFixedWizardRatioPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromWizardStrengthPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromSpyRatioPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromSpyRatioCappedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromSpyStrengthPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromPrestigePerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromRecentlyInvadedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromTicksPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromMilitaryPercentagePerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromVictoriesPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromNetVictoriesPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromRecentVictoriesPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromResourcePerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromResourceExhaustingPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromTimePerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromSpell($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromAdvancement($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromRulerTitle($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromDeity($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromDevotion($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromBuildingsBasedPerk($dominion, $unit, $powerType); # This perk uses multiple buildings!
        $unitPower += $this->getUnitPowerFromImprovementPointsPerImprovement($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromImprovementPoints($dominion, $unit, $powerType);

        if ($landRatio !== null)
        {
            $unitPower += $this->getUnitPowerFromStaggeredLandRangePerk($dominion, $landRatio, $unit, $powerType);
        }

        if ($target !== null || !empty($calc))
        {
            $unitPower += $this->getUnitPowerFromVersusBuildingPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromVersusLandPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromVersusBarrenLandPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromVersusPrestigePerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromVersusResourcePerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromMob($dominion, $target, $unit, $powerType, $calc, $units, $invadingUnits);
            $unitPower += $this->getUnitPowerFromBeingOutnumbered($dominion, $target, $unit, $powerType, $calc, $units, $invadingUnits);
            $unitPower += $this->getUnitPowerFromVersusSpellPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromTargetRecentlyInvadedPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromTargetIsLargerPerk($dominion, $target, $unit, $powerType, $calc);


        }

        return $unitPower;
    }

    protected function getUnitPowerFromLandBasedPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $landPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_land", null);

        if (!$landPerkData) {
            return 0;
        }

        $landType = $landPerkData[0];
        $ratio = (int)$landPerkData[1];
        $max = (int)$landPerkData[2];
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        $landPercentage = ($dominion->{"land_{$landType}"} / $totalLand) * 100;

        $powerFromLand = $landPercentage / $ratio;
        $powerFromPerk = min($powerFromLand, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromBuildingBasedPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $buildingPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_building", null);

        if (!$buildingPerkData)
        {
            return 0;
        }

        $buildingType = $buildingPerkData[0];
        $ratio = (int)$buildingPerkData[1];
        $max = (int)$buildingPerkData[2];
        $totalLand = $this->landCalculator->getTotalLand($dominion);
        $landPercentage = ($this->buildingCalculator->getBuildingAmountOwned($dominion, null, $buildingType) / $totalLand) * 100;

        $powerFromBuilding = $landPercentage / $ratio;
        $powerFromPerk = min($powerFromBuilding, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromWizardRatioPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $wizardRatioPerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_from_wizard_ratio");

        if (!$wizardRatioPerk) {
            return 0;
        }

        $powerFromPerk = (float)$wizardRatioPerk * $this->getWizardRatio($dominion, $powerType);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromFixedWizardRatioPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $wizardRatioPerkData = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "fixed_{$powerType}_from_wizard_ratio");

        if (!$wizardRatioPerkData) {
            return 0;
        }

        $wizardRatioPerk = (float)$wizardRatioPerkData[0];
        $wizardRatioRequired = (float)$wizardRatioPerkData[1];

        $powerFromPerk = 0;

        if($this->getWizardRatio($dominion, $powerType) >= $wizardRatioRequired)
        {
            $powerFromPerk = $wizardRatioPerk;
        }

        return $powerFromPerk;
    }

    protected function getUnitPowerFromWizardStrengthPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $wizardStrengthPerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_from_wizard_strength");

        if (!$wizardStrengthPerk) {
            return 0;
        }

        $powerFromPerk = (float)$wizardStrengthPerk * ($dominion->wizard_strength / 100);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromSpyRatioPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $spyRatioPerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_from_spy_ratio");

        if (!$spyRatioPerk) {
            return 0;
        }

        $powerFromPerk = (float)$spyRatioPerk * $this->getSpyRatio($dominion, $powerType);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromSpyRatioCappedPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $spyRatioPerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_from_spy_ratio_capped");

        if (!$spyRatioPerk) {
            return 0;
        }

        $perSpa = (float)$spyRatioPerk[0];
        $max = (float)$spyRatioPerk[1];

        $powerFromPerk = min($max, $perSpa * $this->getSpyRatio($dominion, $powerType));

        return $powerFromPerk;
    }

    protected function getUnitPowerFromSpyStrengthPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $spyStrengthPerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_from_spy_strength");

        if (!$spyStrengthPerk) {
            return 0;
        }

        $powerFromPerk = (float)$spyStrengthPerk * ($dominion->spy_strength / 100);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromPrestigePerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $prestigePerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_from_prestige");

        if (!$prestigePerk) {
            return 0;
        }

        $amount = (float)$prestigePerk[0];
        $max = (int)$prestigePerk[1];

        $powerFromPerk = min(floor($dominion->prestige) / $amount, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromStaggeredLandRangePerk(Dominion $dominion, float $landRatio = null, Unit $unit, string $powerType): float
    {
        $staggeredLandRangePerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_staggered_land_range");

        if (!$staggeredLandRangePerk) {
            return 0;
        }

        if ($landRatio === null) {
            $landRatio = 0;
        }

        $powerFromPerk = 0;

        foreach ($staggeredLandRangePerk as $rangePerk) {
            $range = ((int)$rangePerk[0]) / 100;
            $power = (float)$rangePerk[1];

            if ($range > $landRatio) {
                continue;
            }

            $powerFromPerk = $power;
        }

        return $powerFromPerk;
    }

    protected function getBonusPowerFromPairingPerk(Dominion $dominion, Unit $unit, string $powerType, array $units = null): float
    {
        $pairingPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_pairing", null);

        if (!$pairingPerkData)
        {
            return 0;
        }

        $unitSlot = (int)$pairingPerkData[0];
        $amount = (float)$pairingPerkData[1];
        if (isset($pairingPerkData[2]))
        {
            $numRequired = (float)$pairingPerkData[2];
        }
        else
        {
            $numRequired = 1;
        }

        $powerFromPerk = 0;
        $numberPaired = 0;

        if ($units === null)
        {
            $numberPaired = min($dominion->{'military_unit' . $unit->slot}, floor((int)$dominion->{'military_unit' . $unitSlot} / $numRequired));
        }
        elseif (isset($units[$unitSlot]) && ((int)$units[$unitSlot] !== 0))
        {
            $numberPaired = min($units[$unit->slot], floor((int)$units[$unitSlot] / $numRequired));
        }

        $powerFromPerk = $numberPaired * $amount;

        return $powerFromPerk;
    }

    protected function getUnitPowerFromResourceCappedExhaustingPerk(Dominion $dominion, Unit $unit, string $powerType, array $units = null): float
    {
        $resourcePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_resource_capped_exhausting", null);

        if (!$resourcePerkData or !isset($units[$unit->slot]))
        {
            return 0;
        }

        $opPerBunch = (float)$resourcePerkData[0];
        $resourcePerUnitRequired = (float)$resourcePerkData[1];
        $resourceKey = (string)$resourcePerkData[2];

        $resourceAmountOwned = $this->resourceCalculator->getAmount($dominion, $resourceKey);

        # How many units have enough of resource?
        $unitsWithEnoughResources = (int)min(floor($resourceAmountOwned / $resourcePerUnitRequired), $units[$unit->slot]);

        $powerFromPerk = $unitsWithEnoughResources * $opPerBunch;

        return $powerFromPerk;
    }

    protected function getUnitPowerFromVersusBuildingPerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType, ?array $calc = []): float
    {
        if ($target === null && empty($calc)) {
            return 0;
        }

        $versusBuildingPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_vs_building", null);
        if (!$versusBuildingPerkData) {
            return 0;
        }

        $buildingKey = $versusBuildingPerkData[0];
        $ratio = (int)$versusBuildingPerkData[1];
        $max = (int)$versusBuildingPerkData[2];

        $landPercentage = 0;
        if (!empty($calc)) {
            # Override building percentage for invasion calculator
            if (isset($calc["{$buildingKey}_percent"])) {
                $landPercentage = (float) $calc["{$buildingKey}_percent"];
            }
        } elseif ($target !== null) {
            $totalLand = $this->landCalculator->getTotalLand($target);
            $landPercentage = ($this->buildingCalculator->getBuildingAmountOwned($dominion, null, $buildingKey) / $totalLand) * 100;
        }

        $powerFromBuilding = $landPercentage / $ratio;
        if ($max < 0) {
            $powerFromPerk = max(-1 * $powerFromBuilding, $max);
        } else {
            $powerFromPerk = min($powerFromBuilding, $max);
        }

        return $powerFromPerk;
    }


    protected function getUnitPowerFromVersusLandPerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType, ?array $calc = []): float
    {
        if ($target === null && empty($calc)) {
            return 0;
        }

        $versusLandPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_vs_land", null);
        if(!$versusLandPerkData) {
            return 0;
        }

        $landType = $versusLandPerkData[0];
        $ratio = (int)$versusLandPerkData[1];
        $max = (int)$versusLandPerkData[2];

        $landPercentage = 0;
        if (!empty($calc)) {
            # Override land percentage for invasion calculator
            if (isset($calc["{$landType}_percent"])) {
                $landPercentage = (float) $calc["{$landType}_percent"];
            }
        } elseif ($target !== null) {
            $totalLand = $this->landCalculator->getTotalLand($target);
            $landPercentage = ($target->{"land_{$landType}"} / $totalLand) * 100;
        }

        $powerFromLand = $landPercentage / $ratio;
        if ($max < 0) {
            $powerFromPerk = max(-1 * $powerFromLand, $max);
        } else {
            $powerFromPerk = min($powerFromLand, $max);
        }

        return $powerFromPerk;
    }

    protected function getUnitPowerFromVersusBarrenLandPerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType, ?array $calc = []): float
    {
        if ($target === null && empty($calc))
        {
            return 0;
        }

        $versusLandPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_vs_barren_land", null);
        if(!$versusLandPerkData)
        {
            return 0;
        }

        $ratio = (int)$versusLandPerkData[0];
        $max = (float)$versusLandPerkData[1];

        $barrenLandPercentage = 0;

        if (!empty($calc))
        {
            # Override land percentage for invasion calculator
            if (isset($calc["barren_land_percent"]))
            {
                $barrenLandPercentage = (float) $calc["barren_land_percent"];
            }
        }
        elseif ($target !== null)
        {
            $totalLand = $this->landCalculator->getTotalLand($target);
            $barrenLand = $this->landCalculator->getTotalBarrenLandForSwarm($target);
            $barrenLandPercentage = ($barrenLand / $totalLand) * 100;
            $barrenLandPercentage = max(0, $barrenLandPercentage);
        }

        $powerFromLand = $barrenLandPercentage / $ratio;

        if ($max < 0)
        {
            $powerFromPerk = max(-1 * $powerFromLand, $max);
        }
        else
        {
            $powerFromPerk = min($powerFromLand, $max);
        }

        return $powerFromPerk;
    }

    protected function getUnitPowerFromRecentlyInvadedPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $amount = 0;

        $recentlyInvadedPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_if_recently_invaded", null);

        if(!$recentlyInvadedPerkData)
        {
            return 0;
        }

        $power = (int)$recentlyInvadedPerkData[0];
        $ticks = (int)$recentlyInvadedPerkData[1];

        if($this->getRecentlyInvadedCount($dominion, $ticks) > 0)
        {
            $amount += $power;
        }

        return $amount;
    }

    protected function getUnitPowerFromRecentlyVictoriousPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $amount = 0;

        $recentlyVictoriousPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_if_recently_victorious", null);

        if(!$recentlyVictoriousPerkData)
        {
            return 0;
        }

        $power = (int)$recentlyVictoriousPerkData[0];
        $ticks = (int)$recentlyVictoriousPerkData[1];

        if($this->getRecentlyVictoriousCount($dominion, $ticks) > 0)
        {
            $amount += $power;
        }

        return $amount;
    }

    protected function getUnitPowerFromTargetRecentlyInvadedPerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType): float
    {
        $amount = 0;

        if(isset($target) and $this->getRecentlyInvadedCount($target) > 0)
        {
            $amount = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot,"{$powerType}_if_target_recently_invaded");
        }

        return $amount;
    }

    protected function getUnitPowerFromTargetIsLargerPerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType): float
    {
        $amount = 0;

        if(isset($target) and $this->landCalculator->getTotalLand($target) > $this->landCalculator->getTotalLand($dominion))
        {
            $amount = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot,"{$powerType}_if_target_is_larger");
        }

        return $amount;
    }

    protected function getUnitPowerFromTicksPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {

        $ticksPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_per_tick", null);

        if (!$ticksPerkData or !$dominion->round->hasStarted())
        {
            return 0;
        }
        $powerPerHour = (float)$ticksPerkData;
        $powerFromTicks = $powerPerHour * $dominion->round->ticks;

        $powerFromPerk = $powerFromTicks;

        return $powerFromPerk;
    }

    protected function getUnitPowerFromVersusPrestigePerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType): float
    {
        $prestigePerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, $powerType . "vs_prestige");

        if (!$prestigePerk)
        {
            return 0;
        }

        # Check if calcing on Invade page calculator.
        if (!empty($calc))
        {
            if (isset($calc['prestige']))
            {
                $prestige = intval($calc['prestige']);
            }
        }
        # Otherwise, SKARPT LÄGE!
        elseif ($target !== null)
        {
            $prestige = floor($target->prestige);
        }

        $amount = (int)$prestigePerk[0];
        $max = (int)$prestigePerk[1];

        $powerFromPerk = min($prestige / $amount, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromMilitaryPercentagePerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $militaryPercentagePerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, $powerType . "_from_military_percentage");

        if (!$militaryPercentagePerk)
        {
            return 0;
        }

        $military = 0;

        # Draftees, Spies, Wizards, and Arch Mages always count.
        $military += $dominion->military_draftees;
        $military += $dominion->military_spies;
        $military += $dominion->military_wizards;
        $military += $dominion->military_archmages;

        # Units in training
        $military += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_spies');
        $military += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_wizards');
        $military += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_archmages');

        for ($unitSlot = 1; $unitSlot <= 4; $unitSlot++)
        {
            $military += $this->getTotalUnitsForSlot($dominion, $unitSlot);
            $military += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$unitSlot}");
        }

        $militaryPercentage = min(1, $military / ($military + $dominion->peasants));

        $powerFromPerk = min($militaryPercentagePerk * $militaryPercentage, 2);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromVictoriesPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $victoriesPerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, $powerType . "_from_victories");

        if (!$victoriesPerk)
        {
            return 0;
        }

        $victories = $this->statsService->getStat($dominion, 'invasion_victories');

        $powerPerVictory = (float)$victoriesPerk[0];
        $max = (float)$victoriesPerk[1];

        $powerFromPerk = min($powerPerVictory * $victories, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromRecentVictoriesPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $recentVictoriesPerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, $powerType . "_from_recent_victories");

        if (!$recentVictoriesPerk)
        {
            return 0;
        }

        $perRecentVictory = (float)$recentVictoriesPerk[0];
        $recencyTicks = (int)$recentVictoriesPerk[1];

        $powerFromPerk = 0;

        $recentInvasions = GameEvent::query()
            ->where('tick', '>=', ($dominion->round->ticks - $recencyTicks))
            ->where([
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'type' => 'invasion',
            ])
            ->get();

        foreach($recentInvasions as $key => $recentInvasion)
        {
            if((isset($recentInvasion->data['land_ratio'])) and $recentInvasion->data['land_ratio'] >= 75 and $recentInvasion->data['result']['success'])
            {
                $powerFromPerk += $perRecentVictory;
            }
        }

        return $powerFromPerk;
    }

    protected function getUnitPowerFromNetVictoriesPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $victoriesPerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, $powerType . "_from_net_victories");

        if (!$victoriesPerk)
        {
            return 0;
        }
        $netVictories = $this->getNetVictories($dominion);
        $netVictoriesForPerk = max(0, $netVictories);

        $powerPerVictory = (float)$victoriesPerk[0];
        $max = (float)$victoriesPerk[1];

        $powerFromPerk = min($powerPerVictory * $netVictoriesForPerk, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromVersusResourcePerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType, ?array $calc = []): float
    {
        if ($target === null && empty($calc))
        {
            return 0;
        }

        $versusResourcePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_vs_resource", null);

        if(!$versusResourcePerkData)
        {
            return 0;
        }

        $resource = (string)$versusResourcePerkData[0];
        $ratio = (int)$versusResourcePerkData[1];
        $max = (int)$versusResourcePerkData[2];

        $targetResources = 0;
        if (!empty($calc))
        {
            # Override resource amount for invasion calculator
            if (isset($calc[$resource]))
            {
                $targetResources = (int)$calc[$resource];
            }
        }
        elseif ($target !== null)
        {
            $targetResources = $this->resourceCalculator->getAmount($target, $resource);
        }

        $powerFromResource = $targetResources / $ratio;
        if ($max < 0)
        {
            $powerFromPerk = max(-1 * $powerFromResource, $max);
        }
        else
        {
            $powerFromPerk = min($powerFromResource, $max);
        }

        # No resource bonus vs. Barbarian (for now)
        if($target !== null and $target->race->name == 'Barbarian')
        {
          $powerFromPerk = 0;
        }

        return $powerFromPerk;
    }

    protected function getUnitPowerFromResourcePerk(Dominion $dominion, Unit $unit, string $powerType): float
    {

        $fromResourcePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_resource", null);

        if(!$fromResourcePerkData)
        {
            return 0;
        }

        $resource = (string)$fromResourcePerkData[0];
        $ratio = (int)$fromResourcePerkData[1];

        $resourceAmount = $this->resourceCalculator->getAmount($dominion, $resource);

        $powerFromResource = $resourceAmount / $ratio;
        $powerFromPerk = $powerFromResource;

        return $powerFromPerk;
    }

    protected function getUnitPowerFromResourceExhaustingPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {

        $fromResourcePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_resource_exhausting", null);

        if(!$fromResourcePerkData)
        {
            return 0;
        }

        $resource = (string)$fromResourcePerkData[0];
        $ratio = (float)$fromResourcePerkData[1];

        $powerFromPerk = $this->resourceCalculator->getAmount($dominion, $resource) / $ratio;

        return $powerFromPerk;
    }

      protected function getUnitPowerFromMob(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType, ?array $calc = [], array $units = null, array $invadingUnits = null): float
      {

          if ($target === null and empty($calc))
          {
              return 0;
          }

          $mobPerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_mob", null);

          if(!$mobPerk)
          {
              return 0;
          }

          $powerFromPerk = 0;

          if (!empty($calc))
          {
              #return 0;
              # Override resource amount for invasion calculator
              if (isset($calc['opposing_units']))
              {
                  if($calc['units_sent'] > $calc['opposing_units'])
                  {
                      $powerFromPerk = $mobPerk[0];
                  }
              }
          }
          elseif ($target !== null)
          {
              # mob_on_offense: Do we ($units) outnumber the defenders ($target)?
              if($powerType == 'offense')
              {
                  $targetUnits = 0;
                  $targetUnits += $target->military_draftees;
                  $targetUnits += $target->military_unit1;
                  $targetUnits += $target->military_unit2;
                  $targetUnits += $target->military_unit3;
                  $targetUnits += $target->military_unit4;

                  if(isset($units))
                  {
                      if(array_sum($units) > $targetUnits)
                      {
                          $powerFromPerk = $mobPerk[0];
                      }
                  }
              }

              # mob_on_offense: Do we ($dominion) outnumber the attackers ($units)?
              if($powerType == 'defense')
              {
                  $mobUnits = 0;
                  $mobUnits += $dominion->military_draftees;
                  $mobUnits += $dominion->military_unit1;
                  $mobUnits += $dominion->military_unit2;
                  $mobUnits += $dominion->military_unit3;
                  $mobUnits += $dominion->military_unit4;

                  if(isset($invadingUnits) and $mobUnits > array_sum($invadingUnits))
                  {
                      $powerFromPerk = $mobPerk[0];
                  }
              }
          }

          return $powerFromPerk;
      }

      protected function getUnitPowerFromBeingOutnumbered(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType, ?array $calc = [], array $units = null, array $invadingUnits = null): float
      {

          if ($target === null and empty($calc))
          {
              return 0;
          }

          $mobPerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_being_outnumbered", null);

          if(!$mobPerk)
          {
              return 0;
          }

          $powerFromPerk = 0;

          if (!empty($calc))
          {
              #return 0;
              # Override resource amount for invasion calculator
              if (isset($calc['opposing_units']))
              {
                  if($calc['units_sent'] < $calc['opposing_units'])
                  {
                      $powerFromPerk = $mobPerk[0];
                  }
              }
          }
          elseif ($target !== null)
          {
              # mob_on_offense: Do we ($units) outnumber the defenders ($target)?
              if($powerType == 'offense')
              {
                  $targetUnitsString = '';

                  $targetUnits = 0;
                  $targetUnits += $target->military_draftees;
                  $targetUnits += $target->military_unit1;
                  $targetUnits += $target->military_unit2;
                  $targetUnits += $target->military_unit3;
                  $targetUnits += $target->military_unit4;

                  if(isset($units))
                  {
                      if(array_sum($units) < $targetUnits)
                      {
                          $powerFromPerk = $mobPerk[0];
                      }
                  }
              }

              # mob_on_offense: Do we ($dominion) outnumber the attackers ($units)?
              if($powerType == 'defense')
              {
                  $mobUnits = 0;
                  $mobUnits += $dominion->military_draftees;
                  $mobUnits += $dominion->military_unit1;
                  $mobUnits += $dominion->military_unit2;
                  $mobUnits += $dominion->military_unit3;
                  $mobUnits += $dominion->military_unit4;

                  if(isset($invadingUnits) and $mobUnits < array_sum($invadingUnits))
                  {
                      $powerFromPerk = $mobPerk[0];
                  }
              }
          }

          return $powerFromPerk;
      }

      protected function getUnitPowerFromTimePerk(Dominion $dominion, Unit $unit, string $powerType): float
      {

          $timePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_time", null);

          if (!$timePerkData or !$dominion->round->hasStarted())
          {
              return 0;
          }

          $powerFromTime = (float)$timePerkData[2];

          $hourFrom = $timePerkData[0];
          $hourTo = $timePerkData[1];
          if (
              (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
              (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
          )
          {
              $powerFromPerk = $powerFromTime;
          }
          else
          {
              $powerFromPerk = 0;
          }

          return $powerFromPerk;
      }

      protected function getUnitPowerFromSpell(Dominion $dominion, Unit $unit, string $powerType): float
      {

          $spellPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_spell", null);
          $powerFromPerk = 0;

          if (!$spellPerkData)
          {
              return 0;
          }

          $powerFromSpell = (float)$spellPerkData[1];
          $spellKey = (string)$spellPerkData[0];

          if ($this->spellCalculator->isSpellActive($dominion, $spellKey))
          {
              $powerFromPerk = $powerFromSpell;
          }

          return $powerFromPerk;

      }

      # Untested/unused
      protected function getUnitPowerFromVersusSpellPerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType, ?array $calc = []): float
      {
          if ($target === null && empty($calc))
          {
              return 0;
          }

          $spellPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_versus_spell", null);

          if(!$spellPerkData)
          {
              return 0;
          }

          $powerVersusSpell = (float)$spellPerkData[1];
          $spell = $spellPerkData[0];

          if (!empty($calc))
          {
              # Override resource amount for invasion calculator
              if (isset($calc[$spell]))
              {
                  $powerFromPerk = $powerVersusSpell;
              }
          }
          elseif ($target !== null)
          {
              if($targetSpellActive = $this->spellCalculator->isSpellActive($target, $spell));
              {
                  $powerFromPerk = $powerVersusSpell;
              }
          }

          return $powerFromPerk;
      }



      protected function getUnitPowerFromAdvancement(Dominion $dominion, Unit $unit, string $powerType): float
      {

          $advancementPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_advancements", null);
          $powerFromPerk = 0;

          if (!$advancementPerkData)
          {
              return 0;
          }

          foreach($advancementPerkData as $advancementSet)
          {
                $key = $advancementSet[0];
                $power = (float)$advancementSet[1];
                $tech = Tech::where('key', $key)->first();

                if($this->techCalculator->hasTech($dominion, $tech))
                {
                    $powerFromPerk += $power;
                }
          }

          return $powerFromPerk;
      }

      protected function getUnitPowerFromRulerTitle(Dominion $dominion, Unit $unit, string $powerType): float
      {

          $titlePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_title", null);
          $powerFromPerk = 0;

          if (!$titlePerkData or $dominion->isAbandoned() or !isset($dominion->title))
          {
              return 0;
          }

          if($dominion->title->key == $titlePerkData[0])
          {
              $powerFromPerk += $titlePerkData[1];
          }

          return $powerFromPerk;
      }

      protected function getUnitPowerFromDeity(Dominion $dominion, Unit $unit, string $powerType): float
      {

          $deityPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_deity", null);
          $powerFromPerk = 0;

          if (!$deityPerkData or $dominion->isAbandoned() or !$dominion->hasDeity())
          {
              return 0;
          }

          if($dominion->deity->key == $deityPerkData[0])
          {
              $powerFromPerk += $deityPerkData[1];
          }

          return $powerFromPerk;
      }

      protected function getUnitPowerFromDevotion(Dominion $dominion, Unit $unit, string $powerType): float
      {
          $deityPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_devotion", null);

          $powerFromPerk = 0;

          if (!$deityPerkData or $dominion->isAbandoned())
          {
              return 0;
          }

          $deityKey = $deityPerkData[0];
          $perTick = (float)$deityPerkData[1];
          $max = (float)$deityPerkData[2];

          if($dominion->deity->key == $deityPerkData[0])
          {
              $powerFromPerk += min($dominion->devotion->duration * $perTick, $max);
          }
          #dd($powerFromPerk);

          return $powerFromPerk;
      }

      protected function getUnitPowerFromBuildingsBasedPerk(Dominion $dominion, Unit $unit, string $powerType): float
      {
          $buildingsPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_buildings", null);

          if (!$buildingsPerkData)
          {
              return 0;
          }

          $buildingTypes = $buildingsPerkData[0];
          $ratio = (int)$buildingsPerkData[1];
          $max = (int)$buildingsPerkData[2];
          $totalLand = $this->landCalculator->getTotalLand($dominion);
          $buildingsLand = 0;

          foreach($buildingTypes as $buildingKey)
          {
              $buildingsLand += $this->buildingCalculator->getBuildingAmountOwned($dominion, null, $buildingKey);
              $buildingsLand += $this->queueService->getConstructionQueueTotalByResource($dominion, 'building_' . $buildingKey);
          }

          $landPercentage = ($buildingsLand / $totalLand) * 100;

          $powerFromBuilding = $landPercentage / $ratio;
          $powerFromPerk = min($powerFromBuilding, $max);

          return $powerFromPerk;
      }

      protected function getUnitPowerFromImprovementPointsPerImprovement(Dominion $dominion, Unit $unit, string $powerType): float
      {
          $dominionImprovements = $this->improvementCalculator->getDominionImprovements($dominion);
          $dominionImprovementsPerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_per_improvement", null);

          if (!$dominionImprovementsPerk)
          {
              return 0;
          }

          $powerPerImp = (float)$dominionImprovementsPerk[0];
          $pointsPerImp = (int)$dominionImprovementsPerk[1];

          $powerFromPerk = 0;

          foreach($dominionImprovements as $dominionImprovement)
          {
              $improvement = Improvement::where('id', $dominionImprovement->improvement_id)->first();
              if($this->improvementCalculator->getDominionImprovementAmountInvested($dominion, $improvement) >= $pointsPerImp)
              {
                  $powerFromPerk += $powerPerImp;
              }
          }

          return $powerFromPerk;
      }

      protected function getUnitPowerFromImprovementPoints(Dominion $dominion, Unit $unit, string $powerType): float
      {
          $dominionImprovementsPerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_improvement_points", null);

          if (!$dominionImprovementsPerk)
          {
              return 0;
          }

          $dominionImprovementPoints = $this->improvementCalculator->getDominionImprovementTotalAmountInvested($dominion);

          $pointsPerChunk = (float)$dominionImprovementsPerk[0];
          $chunkSize = (int)$dominionImprovementsPerk[1];
          $max = (float)$dominionImprovementsPerk[2];

          $powerFromPerk = ($dominionImprovementPoints / $chunkSize) * $pointsPerChunk;

          return min($max, $powerFromPerk);
      }

    /**
     * Returns the Dominion's morale modifier.
     *
     * Net OP/DP gets lowered linearly by up to -20% at 0% morale.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getMoraleMultiplier(Dominion $dominion): float
    {
        return 0.90 + $dominion->morale / 1000;
    }

    /**
     * Returns the Dominion's spy ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatio(Dominion $dominion, string $type = 'offense'): float
    {
        return ($this->getSpyRatioRaw($dominion, $type) * $this->getSpyRatioMultiplier($dominion, $type)  * (0.9 + $dominion->spy_strength / 1000));
    }

    /**
     * Returns the Dominion's raw spy ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatioRaw(Dominion $dominion, string $type = 'offense'): float
    {
        $spies = $dominion->military_spies;

        // Add units which count as (partial) spies (Lizardfolk Chameleon)
        foreach ($dominion->race->units as $unit)
        {
            if ($type === 'offense' && $unit->getPerkValue('counts_as_spy_offense'))
            {
                $spies += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_spy_offense'));
            }

            if ($type === 'defense' && $unit->getPerkValue('counts_as_spy_defense'))
            {
                $spies += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_spy_defense'));
            }

            if ($unit->getPerkValue('counts_as_spy'))
            {
                $spies += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_spy'));
            }

            if ($timePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, ("counts_as_spy_" . $type . "_from_time"), null))
            {
                $powerFromTime = (float)$timePerkData[2];
                $hourFrom = $timePerkData[0];
                $hourTo = $timePerkData[1];
                if (
                    (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                    (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                )
                {
                    $spies += floor($dominion->{"military_unit{$unit->slot}"} * $powerFromTime);
                }
            }
        }

        $multiplier = 1;
        $multiplier += $dominion->getSpellPerkMultiplier('spy_strength');
        $multiplier += $dominion->getSpellPerkMultiplier('spy_strength_on_' . $type);
        $multiplier += $dominion->getBuildingPerkMultiplier('spy_strength');
        $multiplier += $dominion->getBuildingPerkMultiplier('spy_strength_on_' . $type);

        // Shroud
        $spies *= $multiplier;

        return ($spies / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's spy ratio multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatioMultiplier(Dominion $dominion, string $type = 'offense'): float
    {
        $multiplier = 0;

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('spy_strength');

        // Improvements
        $multiplier += $dominion->getImprovementPerkMultiplier('spy_strength');

        // Tech
        $multiplier += $dominion->getTechPerkMultiplier('spy_strength');

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('spy_strength');

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('spy_strength');
        $multiplier += $dominion->getBuildingPerkMultiplier('spy_strength_on_' . $type);

        // Deity
        $multiplier += $dominion->getDeityPerkMultiplier('spy_strength');

        // Title
        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('spy_strength') * $dominion->getTitlePerkMultiplier();
        }

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's spy strength regeneration.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyStrengthRegen(Dominion $dominion): float
    {
        $regen = 4;
        $regen += $dominion->getTechPerkValue('spy_strength_recovery');
        $regen += $dominion->getImprovementPerkValue('spy_strength_recovery');
        $regen += $dominion->getSpellPerkValue('spy_strength_recovery');
        $regen += $dominion->getBuildingPerkValue('spy_strength_recovery');

        return (float)$regen;
    }

    /**
     * Returns the Dominion's wizard ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatio(Dominion $dominion, string $type = 'offense'): float
    {
        return ($this->getWizardRatioRaw($dominion, $type) * $this->getWizardRatioMultiplier($dominion, $type) * (0.9 + $dominion->wizard_strength / 1000));
    }

    /**
     * Returns the Dominion's raw wizard ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatioRaw(Dominion $dominion, string $type = 'offense'): float
    {
        $wizards = $this->getTotalUnitsForSlot($dominion, 'wizards');
        $wizards += $this->getTotalUnitsForSlot($dominion, 'archmages') * 2;

        // Add units which count as (partial) spies (Dark Elf Adept)
        foreach ($dominion->race->units as $unit)
        {
            if ($type === 'offense' && $countsAsWizardOffensePerk = $unit->getPerkValue('counts_as_wizard_offense'))
            {
                $wizards += floor($dominion->{"military_unit{$unit->slot}"} * $countsAsWizardOffensePerk);
            }

            if ($type === 'defense' && $countsAsWizardDefensePerk = $unit->getPerkValue('counts_as_wizard_defense'))
            {
                $wizards += floor($dominion->{"military_unit{$unit->slot}"} * $countsAsWizardDefensePerk);
            }

            if ($countsAsWizardPerk = $unit->getPerkValue('counts_as_wizard'))
            {
                $wizards += floor($dominion->{"military_unit{$unit->slot}"} * $countsAsWizardPerk);
            }

            if ($timePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, ("counts_as_wizard_" . $type . "_from_time"), null))
            {
                $powerFromTime = (float)$timePerkData[2];
                $hourFrom = $timePerkData[0];
                $hourTo = $timePerkData[1];
                if (
                    (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                    (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                )
                {
                    $wizards += floor($dominion->{"military_unit{$unit->slot}"} * $powerFromTime);
                }
            }

            if ($landPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, ("counts_as_wizard_from_land"), null))
            {
                $power = (float)$landPerkData[0];
                $ratio = (float)$landPerkData[1];
                $landType = (string)$landPerkData[2];

                $wizards += $dominion->{"military_unit{$unit->slot}"} * (((($dominion->{'land_' . $landType} / $this->landCalculator->getTotalLand($dominion)) * 100) / $ratio) * $power);
            }

            if ($landPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, ("counts_as_wizard_on_" . $type ."_from_land"), null))
            {
                $power = (float)$landPerkData[0];
                $ratio = (float)$landPerkData[1];
                $landType = (string)$landPerkData[2];

                $wizards += $dominion->{"military_unit{$unit->slot}"} * (((($dominion->{'land_' . $landType} / $this->landCalculator->getTotalLand($dominion)) * 100) / $ratio) * $power);
            }

            # Check for wizard_from_title
            $titlePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "wizard_from_title", null);
            if($titlePerkData)
            {
                $titleKey = $titlePerkData[0];
                $titlePower = $titlePerkData[1];
                if($dominion->title->key == $titleKey)
                {
                    $wizards += floor($dominion->{"military_unit{$unit->slot}"} * (float) $titlePower);
                }
            }
        }

        return ($wizards / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's wizard ratio multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatioMultiplier(Dominion $dominion, string $type = 'offense'): float
    {
        $multiplier = 0;

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('wizard_strength');

        // Improvements
        $multiplier += $dominion->getImprovementPerkMultiplier('wizard_strength');

        // Tech
        $multiplier += $dominion->getTechPerkMultiplier('wizard_strength');

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('wizard_strength');
        $multiplier += $dominion->getBuildingPerkMultiplier('wizard_strength_on_' . $type);

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('wizard_strength');
        $multiplier += $dominion->getBuildingPerkMultiplier('wizard_strength_on_' . $type);

        // Land improvements
        $multiplier += $dominion->getLandImprovementPerkMultiplier('wizard_strength');

        // Title
        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('wizard_strength') * $dominion->getTitlePerkMultiplier();
        }

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's wizard strength regeneration.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardStrengthRegen(Dominion $dominion): float
    {
        $regen = 5;

        // todo: Master of Magi / Dark Artistry tech
        // todo: check if this needs to be a float

        return (float)$regen;
    }

    /**
     * Returns the Dominion's raw wizard ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardPoints(Dominion $dominion, string $type = 'offense'): float
    {
        $wizardPoints = $dominion->military_wizards + ($dominion->military_archmages * 2);

        // Add units which count as (partial) spies (Dark Elf Adept)
        foreach ($dominion->race->units as $unit)
        {
            if ($type === 'offense' && $unit->getPerkValue('counts_as_wizard_offense'))
            {
                $wizardPoints += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_wizard_offense'));
            }

            if ($type === 'defense' && $unit->getPerkValue('counts_as_wizard_defense'))
            {
                $wizardPoints += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_wizard_defense'));
            }

            if ($unit->getPerkValue('counts_as_wizard'))
            {
                $wizardPoints += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_wizard'));
            }

            if ($timePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, ("counts_as_wizard_" . $type . "_from_time"), null))
            {
                $powerFromTime = (float)$timePerkData[2];
                $hourFrom = $timePerkData[0];
                $hourTo = $timePerkData[1];
                if (
                    (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                    (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                )
                {
                    $spyPoints += floor($dominion->{"military_unit{$unit->slot}"} * $powerFromTime);
                }
            }
        }

        return $wizardPoints * $this->getWizardRatioMultiplier($dominion, $type);
    }

    /**
     * Returns the Dominion's raw wizard ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyPoints(Dominion $dominion, string $type = 'offense'): float
    {
        $spyPoints = $dominion->military_spies;

        foreach ($dominion->race->units as $unit)
        {
            if ($type === 'offense' && $unit->getPerkValue('counts_as_spy_offense'))
            {
                $spyPoints += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_spy_offense'));
            }

            if ($type === 'defense' && $unit->getPerkValue('counts_as_spy_defense'))
            {
                $spyPoints += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_spy_defense'));
            }

            if ($unit->getPerkValue('counts_as_spy'))
            {
                $spyPoints += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_spy'));
            }

            if ($timePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, ("counts_as_spy_" . $type . "_from_time"), null))
            {
                $powerFromTime = (float)$timePerkData[2];
                $hourFrom = $timePerkData[0];
                $hourTo = $timePerkData[1];
                if (
                    (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                    (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                )
                {
                    $spyPoints += floor($dominion->{"military_unit{$unit->slot}"} * $powerFromTime);
                }
            }
        }

        return $spyPoints * $this->getSpyRatioMultiplier($dominion, $type);
    }

    /**
     * Gets the total amount of living specialist/elite units for a Dominion.
     *
     * Total amount includes units at home and units returning from battle.
     *
     * @param Dominion $dominion
     * @param int $slot
     * @return int
     */
    public function getTotalUnitsForSlot(Dominion $dominion, $slot): int
    {
        if(is_int($slot))
        {
            return (
                $dominion->{'military_unit' . $slot} +
                $this->queueService->getInvasionQueueTotalByResource($dominion, "military_unit{$slot}") +
                $this->queueService->getExpeditionQueueTotalByResource($dominion, "military_unit{$slot}") +
                $this->queueService->getTheftQueueTotalByResource($dominion, "military_unit{$slot}") +
                $this->queueService->getSabotageQueueTotalByResource($dominion, "military_unit{$slot}")
            );
        }
        elseif(in_array($slot, ['draftees', 'spies', 'wizards', 'archmages']))
        {
            return (
                $dominion->{'military_' . $slot} +
                $this->queueService->getInvasionQueueTotalByResource($dominion, "military_{$slot}") +
                $this->queueService->getExpeditionQueueTotalByResource($dominion, "military_{$slot}") +
                $this->queueService->getTheftQueueTotalByResource($dominion, "military_{$slot}") +
                $this->queueService->getSabotageQueueTotalByResource($dominion, "military_{$slot}")
            );
        }
        else
        {
            return 0;
        }
    }

    public function getTotalSpyUnits(Dominion $dominion, string $type = 'offense'): int
    {
        $spies = $this->getTotalUnitsForSlot($dominion, 'spies');

        // Add units which count as (partial) spies (Lizardfolk Chameleon)
        foreach ($dominion->race->units as $unit)
        {
            if ($type === 'offense' && $unit->getPerkValue('counts_as_spy_offense'))
            {
                $spies += $this->getTotalUnitsForSlot($dominion, $unit->slot);
            }

            if ($type === 'defense' && $unit->getPerkValue('counts_as_spy_defense'))
            {
                $spies += $this->getTotalUnitsForSlot($dominion, $unit->slot);
            }

            if ($unit->getPerkValue('counts_as_spy'))
            {
                $spies += $this->getTotalUnitsForSlot($dominion, $unit->slot);
            }

            if ($timePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, ("counts_as_spy_" . $type . "_from_time"), null))
            {
                $powerFromTime = (float)$timePerkData[2];
                $hourFrom = $timePerkData[0];
                $hourTo = $timePerkData[1];
                if (
                    (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                    (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                )
                {
                    $spies += $this->getTotalUnitsForSlot($dominion, $unit->slot) * $powerFromTime;
                }
            }
        }

        return (int)floor($spies);
    }

    public function getTotalSpyUnitsAtHome(Dominion $dominion, string $type = 'offense'): int
    {
        #$spies = $this->getTotalUnitsForSlot($dominion, 'spies'); $dominion->military_spies;
        $spies = $dominion->military_spies;

        // Add units which count as (partial) spies (Lizardfolk Chameleon)
        foreach ($dominion->race->units as $unit)
        {
            if ($type === 'offense' && $unit->getPerkValue('counts_as_spy_offense'))
            {
                $spies += $dominion->{"military_unit{$unit->slot}"};
            }

            if ($type === 'defense' && $unit->getPerkValue('counts_as_spy_defense'))
            {
                $spies += $dominion->{"military_unit{$unit->slot}"};
            }

            if ($unit->getPerkValue('counts_as_spy'))
            {
                $spies += $dominion->{"military_unit{$unit->slot}"};
            }

            if ($timePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, ("counts_as_spy_" . $type . "_from_time"), null))
            {
                $powerFromTime = (float)$timePerkData[2];
                $hourFrom = $timePerkData[0];
                $hourTo = $timePerkData[1];
                if (
                    (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                    (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                )
                {
                    $spies += $dominion->{"military_unit{$unit->slot}"} * $powerFromTime;
                }
            }
        }

        return (int)floor($spies);
    }

    public function getMaxSpyUnitsSendable(Dominion $dominion): int
    {
        $spyUnitsAvailable = $this->getTotalSpyUnitsAtHome($dominion);
        $spyUnitsTotal = $this->getTotalSpyUnits($dominion);

        $spyStrengthRatio = $dominion->spy_strength / 100;

        return (int)floor(min($spyUnitsAvailable, $spyUnitsTotal * $spyStrengthRatio));
    }

    /**
     * Returns the number of time the Dominion was recently invaded.
     *
     * 'Recent' refers to the past 6 hours.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getRecentlyInvadedCount(Dominion $dominion, int $ticks = 24): int
    {
        // todo: this touches the db. should probably be in invasion or military service instead
        $invasionEvents = GameEvent::query()
            ->where('tick', '>=', ($dominion->round->ticks - $ticks))
            ->where([
                'target_type' => Dominion::class,
                'target_id' => $dominion->id,
                'type' => 'invasion',
            ])
            ->get();

        if ($invasionEvents->isEmpty())
        {
            return 0;
        }

        $invasionEvents = $invasionEvents->filter(function (GameEvent $event)
        {
            return !$event->data['result']['overwhelmed'];
        });

        return $invasionEvents->count();
    }

    /**
     * Returns the number of time the Dominion was recently victorious.
     *
     * 'Recent' refers to the past 24 ticks.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getRecentlyVictoriousCount(Dominion $dominion, int $ticks = 24): int
    {
        // todo: this touches the db. should probably be in invasion or military service instead
        $invasionEvents = GameEvent::query()
            ->where('tick', '>=', ($dominion->round->ticks - $ticks))
            ->where([
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'type' => 'invasion',
            ])
            ->get();

        if ($invasionEvents->isEmpty())
        {
            return 0;
        }

        $invasionEvents = $invasionEvents->filter(function (GameEvent $event)
        {
            return ($event->data['result']['success'] and $event->data['land_ratio'] >= 75);
        });

        return $invasionEvents->count();
    }

    /**
     * Returns the number of time the Dominion was recently invaded by the attacker.
     *
     * 'Recent' refers to the past 2 hours by default.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getRecentlyInvadedCountByAttacker(Dominion $defender, Dominion $attacker, int $ticks = 8): int
    {
        $invasionEvents = GameEvent::query()
            ->where('tick', '>=', ($defender->round->ticks - $ticks))
            ->where([
                'target_type' => Dominion::class,
                'target_id' => $defender->id,
                'source_id' => $attacker->id,
                'type' => 'invasion',
            ])
            ->get();

        if ($invasionEvents->isEmpty())
        {
            return 0;
        }

        $invasionEvents = $invasionEvents->filter(function (GameEvent $event)
        {
            return !$event->data['result']['overwhelmed'];
        });

        return $invasionEvents->count();
    }

    /**
     * Checks if $defender recently invaded $attacker's realm.
     *
     * 'Recent' refers to the past 6 hours.
     *
     * @param Dominion $dominion
     * @param Dominion $attacker
     * @return bool
     */
    public function isOwnRealmRecentlyInvadedByTarget(Dominion $attacker, Dominion $defender = null, int $ticks = 24): bool
    {
        if($defender)
        {
            $invasionEvents = GameEvent::query()
                              ->join('dominions as source_dominion','game_events.source_id','source_dominion.id')
                              ->join('dominions as target_dominion','game_events.target_id','target_dominion.id')
                              ->where('game_events.tick', '>=', ($attacker->round->ticks - $ticks))
                              ->where([
                                  'game_events.type' => 'invasion',
                                  'game_events.source_id' => $defender->id,
                                  'target_dominion.realm_id' => $attacker->realm_id,
                              ])
                              ->get();

            if (!$invasionEvents->isEmpty())
            {
                return true;
            }
            else
            {
              return false;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Checks if $defender recently invaded $attacker's realm.
     *
     * 'Recent' refers to the past 6 hours.
     *
     * @param Dominion $dominion
     * @param Dominion $attacker
     * @return bool
     */
    public function isSelfRecentlyInvadedByTarget(Dominion $attacker, Dominion $defender = null, int $ticks = 24): bool
    {
        if($defender)
        {
            $invasionEvents = GameEvent::query()
                              ->join('dominions as source_dominion','game_events.source_id','source_dominion.id')
                              ->join('dominions as target_dominion','game_events.target_id','target_dominion.id')
                              ->where('game_events.tick', '>=', ($attacker->round->ticks - $ticks))
                              ->where([
                                  'game_events.type' => 'invasion',
                                  'game_events.source_id' => $defender->id,
                                  'game_events.target_id' => $attacker->id,
                              ])
                              ->get();

            if (!$invasionEvents->isEmpty())
            {
                return true;
            }
            else
            {
              return false;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Gets the dominion's OP or DP ($power) bonus from spells.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpellMultiplier(Dominion $dominion, Dominion $target = null, string $power): float
    {

      $multiplier = 0;

      if($power == 'offense')
      {
          $multiplier += $dominion->getSpellPerkMultiplier('offensive_power');# $this->spellCalculator->getPassiveSpellPerkMultiplier($dominion, 'offensive_power');

          // Spell: Retribution (+20% OP)
          # Condition: target must have invaded $dominion's realm in the last six hours.
          if ($dominion->getSpellPerkValue('offensive_power_on_retaliation') and $this->isOwnRealmRecentlyInvadedByTarget($dominion, $target))
          {
              $multiplier += $dominion->getSpellPerkMultiplier('offensive_power_on_retaliation');
          }

      }
      elseif($power == 'defense')
      {
          $multiplier += $dominion->getSpellPerkMultiplier('defensive_power');# $this->spellCalculator->getPassiveSpellPerkMultiplier($dominion, 'defensive_power');

          #dd($dominion->getSpellPerkMultiplier('defensive_power'));

          // Spell: Chitin
          if(isset($target))
          {
              if ($dominion->getSpellPerkValue('defensive_power_vs_insect_swarm') and $this->spellCalculator->isSpellActive($target, 'insect_swarm'))
              {
                  $multiplier += $dominion->getSpellPerkValue('defensive_power_vs_insect_swarm');
              }
          }

      }

      return $multiplier;

    }

    /**
     * Get the dominion's prestige gain perk.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPrestigeGainsPerk(Dominion $dominion, array $units): float
    {
        $unitsIncreasingPrestige = 0;
        # Look for increases_prestige_gains
        foreach($units as $slot => $amount)
        {
            if($increasesPrestige = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'increases_prestige_gains'))
            {
                $unitsIncreasingPrestige += $amount * $increasesPrestige;
            }
        }

        return $unitsIncreasingPrestige / array_sum($units);
    }


    /**
     * Simple true/false if Dominion has units returning from battle.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function hasReturningUnits(Dominion $dominion): bool
    {
        $hasReturningUnits = 0;
        for ($slot = 1; $slot <= 4; $slot++)
        {
            $hasReturningUnits += $this->queueService->getInvasionQueueTotalByResource($dominion, "military_unit{$slot}");
            $hasReturningUnits += $this->queueService->getExpeditionQueueTotalByResource($dominion, "military_unit{$slot}");
            $hasReturningUnits += $this->queueService->getTheftQueueTotalByResource($dominion, "military_unit{$slot}");
            $hasReturningUnits += $this->queueService->getSabotageQueueTotalByResource($dominion, "military_unit{$slot}");
        }

        return $hasReturningUnits;
    }


    /*
    *   Land gains formula go here, because they break the game when they were in the Land Calculator.
    *   (???)
    *
    */

    public function getLandConquered(Dominion $attacker, Dominion $defender, float $landRatio): int
    {
        $rangeMultiplier = $landRatio/100;

        $attackerLandWithRatioModifier = ($this->landCalculator->getTotalLand($attacker));

        if ($landRatio < 55)
        {
            $landConquered = (0.304 * ($rangeMultiplier ** 2) - 0.227 * $rangeMultiplier + 0.048) * $attackerLandWithRatioModifier;
        }
        elseif ($landRatio < 75)
        {
            $landConquered = (0.154 * $rangeMultiplier - 0.069) * $attackerLandWithRatioModifier;
        }
        else
        {
            $landConquered = (0.129 * $rangeMultiplier - 0.048) * $attackerLandWithRatioModifier;
        }

        $landConquered *= 0.75;

        return round(max(10, $landConquered));
    }

    public function checkDiscoverLand(Dominion $attacker, Dominion $defender): int
    {
        if($this->getRecentlyInvadedCountByAttacker($defender, $attacker) == 0 and !$defender->isAbandoned() and !$attacker->getSpellPerkValue('no_land_discovered'))
        {
            return true;
        }

        return false;
    }

    public function getExtraLandDiscovered(Dominion $attacker, Dominion $defender, bool $discoverLand, int $landConquered): int
    {
        $multiplier = 0;

        if(!$discoverLand)
        {
            return 0;
        }

        if($defender->race->name === 'Barbarian')
        {
            $landConquered /= 3;
        }

        // Spells
        $multiplier += $attacker->getSpellPerkMultiplier('land_discovered');

        // Buildings
        $multiplier += $attacker->getBuildingPerkMultiplier('land_discovered');

        // Improvements
        $multiplier += $attacker->getImprovementPerkMultiplier('land_discovered');

        // Deity
        $multiplier += $attacker->getDeityPerkMultiplier('land_discovered');

        // Resource: XP (max +100% from 2,000,000 XP) – only for factions which cannot take advancements (Troll)
        if($attacker->race->getPerkValue('cannot_tech'))
        {
            $multiplier += min($attacker->xp, 1500000) / 1500000;
        }

        return round($landConquered * $multiplier);

    }

    public function getRawDefenseAmbushReductionRatio(Dominion $attacker): float
    {
        $ambushSpellKey = 'ambush';
        $ambushReductionRatio = 0.0;

        if(!$this->spellCalculator->isSpellActive($attacker, $ambushSpellKey))
        {
            return $ambushReductionRatio;
        }

        $spell = Spell::where('key', $ambushSpellKey)->first();

        $spellPerkValues = $spell->getActiveSpellPerkValues($spell->key, 'reduces_target_raw_defense_from_land');

        $reduction = $spellPerkValues[0];
        $ratio = $spellPerkValues[1];
        $landType = $spellPerkValues[2];
        $max = $spellPerkValues[3] / 100;

        $landTypeRatio = $attacker->{'land_' . $landType} / $this->landCalculator->getTotalLand($attacker);

        $ambushReductionRatio = min(($landTypeRatio / $ratio) * $reduction, $max);

        return $ambushReductionRatio;
    }

    public function getDefensivePowerModifierFromLandType(Dominion $dominion, string $landType): float
    {
        $multiplier = 0.0;

        $multiplier += $dominion->race->getPerkValue('defense_from_'.$landType) * ($dominion->{'land_'.$landType} / $this->landCalculator->getTotalLand($dominion));

        return $multiplier;
    }

    public function getNetVictories(Dominion $dominion): int
    {
        return $this->statsService->getStat($dominion, 'invasion_victories') - $this->statsService->getStat($dominion, 'defense_failures');
    }

    public function getRawMilitaryPowerFromAnnexedDominion(Dominion $dominion): int
    {
        $militaryPower = 0;
        for ($slot = 1; $slot <= 4; $slot++)
        {
            $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
                return ($unit->slot == $slot);
            })->first();
            $op = $unit->power_offense;

            $militaryPower += $dominion->{'military_unit'.$slot} * $unit->power_offense;
        }

        return $militaryPower;
    }

    public function getRawMilitaryPowerFromAnnexedDominions(Dominion $legion, Dominion $target = null): int
    {
        $militaryPower = 0;

        if(isset($target) and $target->race->name == 'Barbarian')
        {
            return 0;
        }

        foreach($this->spellCalculator->getAnnexedDominions($legion) as $dominion)
        {
            $militaryPower += $this->getRawMilitaryPowerFromAnnexedDominion($dominion);
        }

        return $militaryPower;
    }

    public function getDefensiveMultiplierReduction(Dominion $attacker): float
    {
        $reduction = 0;
        $reduction += $attacker->getBuildingPerkMultiplier('target_defensive_power_mod');
        $reduction += $attacker->getSpellPerkMultiplier('target_defensive_power_mod');
        $reduction += $attacker->getImprovementPerkMultiplier('target_defensive_power_mod');
        $reduction += $attacker->getDeityPerkMultiplier('target_defensive_power_mod');

        return $reduction;
    }

    public function getOffensiveMultiplierReduction(Dominion $defender): float
    {
        $reduction = 1;

        $reduction -= $defender->getBuildingPerkMultiplier('attacker_offensive_power_mod');
        $reduction -= $defender->getSpellPerkMultiplier('attacker_offensive_power_mod');
        $reduction -= $defender->getImprovementPerkMultiplier('attacker_offensive_power_mod');
        $reduction -= $defender->getDeityPerkMultiplier('attacker_offensive_power_mod');

        return $reduction;
    }

}
