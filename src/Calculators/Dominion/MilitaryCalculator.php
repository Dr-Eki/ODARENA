<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\ImprovementHelper;

use OpenDominion\Models\Advancement;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Unit;

use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;

use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;


class MilitaryCalculator
{
    /** @var bool */
    protected $forTick = false;

    /** @var AdvancementCalculator */
    protected $advancementCalculator;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var GovernmentService */
    protected $governmentService;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MagicCalculator */
    protected $magicCalculator;

    /** @var PrestigeCalculator */
    protected $prestigeCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var ResourceCalculator */
    protected $resourceCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var StatsService */
    protected $statsService;


    /** @var ImprovementHelper */
    protected $improvementHelper;

    public function __construct()
    {
        $this->advancementCalculator = app(AdvancementCalculator::class);
        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->governmentService = app(GovernmentService::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->magicCalculator = app(MagicCalculator::class);
        $this->prestigeCalculator = app(PrestigeCalculator::class);
        $this->queueService = app(QueueService::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->statsService = app(StatsService::class);
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

        $op *= $this->getMoraleMultiplier($attacker, 'offense');

        if($isInvasion)
        {
            $op *= $this->getOffensiveMultiplierReduction($defender, $isInvasion);
        }

        return $op;
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

                if($attacker->race->getUnitPerkValueForUnitSlot($unit->slot, "offense_from_pairings", null))
                {
                    $bonusOffense += $this->getBonusPowerFromPairingsPerk($attacker, $unit, 'offense', $units);
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
        
        // vs. No Deity
        if(isset($defender) and !$defender->hasDeity())
        {
            $multiplier += $attacker->getDeityPerkMultiplier('offensive_power_vs_no_deity');
            $multiplier += $attacker->getTechPerkMultiplier('offensive_power_vs_no_deity');
        }
        
        // vs. Other Deity
        if(isset($defender) and $defender->hasDeity() and $attacker->hasDeity() and $defender->deity->id !== $attacker->deity->id)
        {
            $multiplier += $attacker->getDeityPerkMultiplier('offensive_power_vs_other_deity');
            $multiplier += $attacker->getTechPerkMultiplier('offensive_power_vs_other_deity');
        }

        # Retaliation perk
        if(in_array($attacker->round->mode,['deathmatch','deathmatch-duration']))
        {
            if ($this->isSelfRecentlyInvadedByTarget($attacker, $defender))
            {
                $multiplier += $attacker->getDeityPerkMultiplier('offensive_power_on_retaliation');
            }
        }
        else
        {
            if ($this->isOwnRealmRecentlyInvadedByTarget($attacker, $defender))
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
        $multiplier += $attacker->getTechPerkMultiplier('offensive_power');

        // Advancements
        $multiplier += $attacker->getAdvancementPerkMultiplier('offensive_power');

        // Spells
        $multiplier += $this->getSpellMultiplier($attacker, $defender, 'offense');
        #$multiplier += $attacker->getSpellPerkMultiplier('offensive_power');

        // Prestige
        $multiplier += $this->prestigeCalculator->getPrestigeMultiplier($attacker);

        // Decree
        $multiplier += $attacker->getDecreePerkMultiplier('offensive_power');

        // Terrain
        $multiplier += $attacker->getTerrainPerkMultiplier('offensive_power_mod');

        // Title
        $multiplier += $attacker->title->getPerkMultiplier('offensive_power') * $attacker->getTitlePerkMultiplier();

        // Units
        foreach($attacker->race->units as $unit)
        {
            $multiplier += $attacker->race->getUnitPerkValueForUnitSlot($unit->slot, 'offensive_power_mod') / 100 * $this->getTotalUnitsForSlot($attacker, $unit->slot);
        }

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
        return ($this->getOffensivePower($dominion) / $dominion->land);
    }

    /**
     * Returns the Dominion's raw offensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerRatioRaw(Dominion $dominion): float
    {
        return ($this->getOffensivePowerRaw($dominion) / $dominion->land);
    }

    /**
     * Returns the Dominion's defensive power.
     *
     * @param Dominion $dominion
     * @param Dominion|null $target
     * @param float|null $landRatio
     * @param array|null $units
     * @param float $multiplierReduction
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
        bool $isAmbush = false,                 # 6
        bool $ignoreRawDpFromBuildings = false, # 7
        array $invadingUnits = null,            # 8
        bool $ignoreRawDpFromAnnexedDominions = false # 9
    ): float
    {
        if($defender->hasProtector())
        {
            $defender = $defender->protector;
        }

        $dp = $this->getDefensivePowerRaw($defender, $attacker, $landRatio, $units, $multiplierReduction, $isAmbush, $ignoreRawDpFromBuildings, $invadingUnits, $ignoreRawDpFromAnnexedDominions);
        $dp *= $this->getDefensivePowerMultiplier($defender, $attacker, $multiplierReduction);

        return ($dp * $this->getMoraleMultiplier($defender, 'defense'));
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
        bool $isAmbush = false,                         # 6
        bool $ignoreRawDpFromBuildings = false,         # 7
        array $invadingUnits = null,                    # 8
        bool $ignoreRawDpFromAnnexedDominions = false,  # 9
        bool $ignoreRawDpFromSpells = false             # 10
    ): float
    {
        
        if($defender->hasProtector())
        {
            $defender = $defender->protector;
        }

        $dp = 0;

        // Values
        $minDPPerAcre = 10; # LandDP
        $dpPerDraftee = ($defender->race->getPerkValue('draftee_dp') + $defender->getTechPerkValue('draftee_dp')) ?: 1;

        # If DP per draftee is 0, ignore them (no casualties).
        $ignoreDraftees = false;
        if($dpPerDraftee === 0)
        {
            $ignoreDraftees = true;
        }

        // Peasants
        $dp += $defender->peasants * $defender->getSpellPerkValue('defensive_power_from_peasants');
        $dp += $defender->peasants * ($defender->race->getPerkValue('peasant_dp') + $defender->getTechPerkValue('peasant_dp'));
        $dp += $defender->peasants * $defender->getDecreePerkValue('defensive_power_from_peasants');

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
                $bonusDefense += $this->getBonusPowerFromPairingsPerk($defender, $unit, 'defense', $units);
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

        // Sires: Remove DP from units without sufficient gunpowder.
        $dp -= $this->dpFromUnitWithoutSufficientResources($defender, $attacker, $landRatio, $units, $invadingUnits);
        
        // Attacking Forces skip land-based defenses
        if ($units !== null)
        {
            return $dp;
        }

        $dp = max($dp, $minDPPerAcre * $defender->land);

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

        if($dominion->hasProtector())
        {
            $dominion = $dominion->protector;
        }

        $multiplier = 0;

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('defensive_power');

        // Deity
        $multiplier += $dominion->getDeityPerkMultiplier('defensive_power');

        // Improvements
        $multiplier += $dominion->getImprovementPerkMultiplier('defensive_power');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('defensive_power');

        // Advancements
        $multiplier += $dominion->getAdvancementPerkMultiplier('defensive_power');

        // Spell
        $multiplier += $this->getSpellMultiplier($dominion, $attacker, 'defense');
        $multiplier += $dominion->getSpellPerkMultiplier('defensive_power');

        // Deity
        $multiplier += $dominion->getDecreePerkMultiplier('defensive_power');

        // Title
        $multiplier += $dominion->title->getPerkMultiplier('defensive_power') * $dominion->getTitlePerkMultiplier();

        // Deity
        $multiplier += $dominion->race->getPerkMultiplier('defensive_power');

        // Terrain
        $multiplier += $dominion->getTerrainPerkMultiplier('defensive_power_mod');
        
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
        return ($this->getDefensivePower($dominion) / $dominion->land);
    }

    /**
     * Returns the Dominion's raw defensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerRatioRaw(Dominion $dominion): float
    {
        return ($this->getDefensivePowerRaw($dominion) / $dominion->land);
    }

    public function getUnitPowerWithPerks(
        Dominion $dominion,
        ?Dominion $target,
        ?float $landRatio = null,
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

        $unitPower += $this->getUnitPowerFromTerrainBasedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromBuildingBasedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromWizardRatioPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromFixedWizardRatioPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromWizardStrengthPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromSpyRatioPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromSpyRatioCappedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromSpyStrengthPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromPrestigePerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromRecentlyInvadedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromRecentlyVictoriousPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromTicksPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromMilitaryPercentagePerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromVictoriesPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromNetVictoriesPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromRecentVictoriesPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromResourcePerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromResourceExhaustingPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromTimePerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromSpell($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromTimesSpellCast($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromActiveSelfSpells($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromAdvancement($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromRulerTitle($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromDeity($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromDevotion($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromBuildingsBasedPerk($dominion, $unit, $powerType); # This perk uses multiple buildings!
        $unitPower += $this->getUnitPowerFromImprovementPointsPerImprovement($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromImprovementPoints($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromResearch($dominion, $unit, $powerType);

        if ($landRatio !== null)
        {
            $unitPower += $this->getUnitPowerFromStaggeredLandRangePerk($dominion, $landRatio, $unit, $powerType);
        }

        if ($target !== null || !empty($calc))
        {
            $unitPower += $this->getUnitPowerFromVersusBarrenLandPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromVersusBuildingPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromVersusOtherDeityPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromVersusTerrainPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromVersusNoDeity($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromVersusPrestigePerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromVersusResourcePerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromVersusMilitaryPercentagePerk($dominion, $target, $unit, $powerType, $calc, $units, $invadingUnits);
            $unitPower += $this->getUnitPowerFromVersusFixedMilitaryPercentagePerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromMob($dominion, $target, $unit, $powerType, $calc, $units, $invadingUnits);
            $unitPower += $this->getUnitPowerFromBeingOutnumbered($dominion, $target, $unit, $powerType, $calc, $units, $invadingUnits);
            $unitPower += $this->getUnitPowerFromVersusSorcerySpellsPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromTargetRecentlyInvadedPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromTargetRecentlyVictoriousPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromTargetIsLargerPerk($dominion, $target, $unit, $powerType, $calc);


        }

        return $unitPower;
    }

    protected function getUnitPowerFromTerrainBasedPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $landPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_terrain", null);

        if (!$landPerkData) {
            return 0;
        }

        $terrainKey = $landPerkData[0];
        $ratio = (int)$landPerkData[1];
        $max = (int)$landPerkData[2];
        $totalLand = $dominion->land;

        $landPercentage = ($dominion->{"terrain_{$terrainKey}"} / $totalLand) * 100;

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
        $totalLand = $dominion->land;
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

        $powerFromPerk = (float)$wizardRatioPerk * $this->magicCalculator->getWizardRatio($dominion, $powerType);

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

        if($this->magicCalculator->getWizardRatio($dominion, $powerType) >= $wizardRatioRequired)
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

    protected function getBonusPowerFromPairingsPerk(Dominion $dominion, Unit $unit, string $powerType, array $units = null): float
    {
        $pairingsPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_pairings", null);

        if (!$pairingsPerkData)
        {
            return 0;
        }

        $powerFromPerk = 0;

        foreach($pairingsPerkData as $pairingPerkData)
        {

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

            $numberPaired = 0;

            if ($units === null)
            {
                $numberPaired = min($dominion->{'military_unit' . $unit->slot}, floor((int)$dominion->{'military_unit' . $unitSlot} / $numRequired));
            }
            elseif (isset($units[$unitSlot]) && ((int)$units[$unitSlot] !== 0))
            {
                $numberPaired = min($units[$unit->slot], floor((int)$units[$unitSlot] / $numRequired));
            }

            $powerFromPerk += $numberPaired * $amount;
        }

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
            $totalLand = $target->land;
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


    protected function getUnitPowerFromVersusTerrainPerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType, ?array $calc = []): float
    {
        if ($target === null && empty($calc)) {
            return 0;
        }

        $versusLandPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_vs_terrain", null);
        if(!$versusLandPerkData) {
            return 0;
        }

        $terrainKey = $versusLandPerkData[0];
        $ratio = (int)$versusLandPerkData[1];
        $max = (int)$versusLandPerkData[2];

        $landPercentage = 0;
        if (!empty($calc)) {
            # Override land percentage for invasion calculator
            if (isset($calc["{$terrainKey}_percent"])) {
                $landPercentage = (float) $calc["{$terrainKey}_percent"];
            }
        } elseif ($target !== null) {
            $landPercentage = ($target->{"terrain_{$terrainKey}"} / $target->land) * 100;
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
            $totalLand = $target->land;
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

    protected function getUnitPowerFromVersusOtherDeityPerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType, ?array $calc = []): float
    {
        if ($target === null && empty($calc) or !$dominion->hasDeity())
        {
            return 0;
        }

        $otherDeityPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_vs_other_deity", null);
        $powerFromPerk = 0;

        if (!$otherDeityPerkData or $dominion->isAbandoned())
        {
            return 0;
        }

        if(!$target->hasDeity() or ($dominion->deity->id !== $target->deity->id))
        {
            $powerFromPerk += $otherDeityPerkData;
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

        $power = (float)$recentlyVictoriousPerkData[0];
        $ticks = (float)$recentlyVictoriousPerkData[1];

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

    protected function getUnitPowerFromTargetRecentlyVictoriousPerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType): float
    {
        $amount = 0;

        if(isset($target) and $this->getRecentlyVictoriousCount($target) > 0)
        {
            $amount = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot,"{$powerType}_if_target_is_recently_victorious");
        }

        return $amount;
    }

    protected function getUnitPowerFromTargetIsLargerPerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType): float
    {
        $amount = 0;

        if(isset($target) and $target->land > $dominion->land)
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

    protected function getUnitPowerFromVersusNoDeity(Dominion $dominion, Dominion $attacker = null, Unit $unit, string $powerType): float
    {
        $powerFromPerk = 0;
        $vsNoDeityPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_power_vs_no_deity", null);

        if(!$vsNoDeityPerkData or !isset($attacker))
        {
            return 0;
        }

        if($attacker->hasDeity())
        {
            $powerFromPerk = (float)$vsNoDeityPerkData;
        }

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
        $military += $this->queueService->getSummoningQueueTotalByResource($dominion, 'military_spies');
        $military += $this->queueService->getSummoningQueueTotalByResource($dominion, 'military_wizards');
        $military += $this->queueService->getSummoningQueueTotalByResource($dominion, 'military_archmages');

        for ($unitSlot = 1; $unitSlot <= $dominion->race->units->count(); $unitSlot++)
        {
            $military += $this->getTotalUnitsForSlot($dominion, $unitSlot);
            $military += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$unitSlot}");
            $military += $this->queueService->getSummoningQueueTotalByResource($dominion, "military_unit{$unitSlot}");
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
          elseif ($target)
          {
                # mob_on_offense: Do we ($units) outnumber the defenders ($target)?
                if($powerType == 'offense')
                {
                    $targetUnits = $this->getTotalUnitsAtHome($target, true, true);

                    if(isset($units))
                    {
                        if(array_sum($units) > $targetUnits)
                        {
                            $powerFromPerk = $mobPerk[0];
                        }
                    }
                }

                # mob_on_defense: Do we ($dominion) outnumber the attackers ($units)?
                if($powerType == 'defense')
                {
                    $mobUnits = $this->getTotalUnitsAtHome($dominion, true, true);

                    if(isset($invadingUnits) and $mobUnits > array_sum($invadingUnits))
                    {
                        $powerFromPerk = $mobPerk[0];
                    }
                }
          }

          return $powerFromPerk;
      }

        protected function getUnitPowerFromVersusMilitaryPercentagePerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType, ?array $calc = [], array $units = null, array $invadingUnits = null): float
        {
            $militaryPercentagePerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, $powerType . "_vs_military_percentage");

            if (!$militaryPercentagePerk or !isset($target))
            {
                return 0;
            }

            $perPercentage = (float)$militaryPercentagePerk[0];
            $max = (int)$militaryPercentagePerk[1];

            $military = 0;

            # Draftees, Spies, Wizards, and Arch Mages always count.
            $military += $target->military_draftees;
            $military += $target->military_spies;
            $military += $target->military_wizards;
            $military += $target->military_archmages;

            # Units in training
            $military += $this->queueService->getTrainingQueueTotalByResource($target, 'military_spies');
            $military += $this->queueService->getTrainingQueueTotalByResource($target, 'military_wizards');
            $military += $this->queueService->getTrainingQueueTotalByResource($target, 'military_archmages');
            $military += $this->queueService->getSummoningQueueTotalByResource($target, 'military_spies');
            $military += $this->queueService->getSummoningQueueTotalByResource($target, 'military_wizards');
            $military += $this->queueService->getSummoningQueueTotalByResource($target, 'military_archmages');

            foreach($target->race->units as $unit)
            {
                $military += $this->getTotalUnitsForSlot($dominion, $unit->slot);
                $military += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$unit->slot}");
                $military += $this->queueService->getSummoningQueueTotalByResource($dominion, "military_unit{$unit->slot}");
            }

            $militaryPercentage = min(1, $military / ($military + $dominion->peasants));

            $powerFromPerk = min($perPercentage * $militaryPercentage, $max);

            return $powerFromPerk;
        }

        protected function getUnitPowerFromVersusFixedMilitaryPercentagePerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType, ?array $calc = [], array $units = null, array $invadingUnits = null): float
        {
            $militaryPercentagePerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, $powerType . "_vs_fixed_military_percentage");

            if (!$militaryPercentagePerk or !isset($target))
            {
                return 0;
            }

            $power = (float)$militaryPercentagePerk[0];
            $cutoff = (int)$militaryPercentagePerk[1];

            $military = 0;

            # Draftees, Spies, Wizards, and Arch Mages always count.
            $military += $target->military_draftees;
            $military += $target->military_spies;
            $military += $target->military_wizards;
            $military += $target->military_archmages;

            # Units in training
            $military += $this->queueService->getTrainingQueueTotalByResource($target, 'military_spies');
            $military += $this->queueService->getTrainingQueueTotalByResource($target, 'military_wizards');
            $military += $this->queueService->getTrainingQueueTotalByResource($target, 'military_archmages');
            $military += $this->queueService->getSummoningQueueTotalByResource($target, 'military_spies');
            $military += $this->queueService->getSummoningQueueTotalByResource($target, 'military_wizards');
            $military += $this->queueService->getSummoningQueueTotalByResource($target, 'military_archmages');

            foreach($target->race->units as $unit)
            {
                $military += $this->getTotalUnitsForSlot($dominion, $unit->slot);
                $military += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$unit->slot}");
                $military += $this->queueService->getSummoningQueueTotalByResource($dominion, "military_unit{$unit->slot}");
            }

            $militaryPercentage = min(1, $military / ($military + $dominion->peasants));

            if($militaryPercentage >= $cutoff)
            {
                $powerFromPerk = $power;
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
                  $targetUnits = $this->getTotalUnitsAtHome($target, true, true);

                  if(isset($units))
                  {
                      if(array_sum($units) < $targetUnits)
                      {
                          $powerFromPerk = $mobPerk[0];
                      }
                  }
              }

              # mob_on_defense: Do we ($dominion) outnumber the attackers ($units)?
              if($powerType == 'defense')
              {
                  $mobUnits = $this->getTotalUnitsAtHome($dominion, true, true);

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

          if ($dominion->isSpellActive($spellKey))
          {
              $powerFromPerk = $powerFromSpell;
          }

          return $powerFromPerk;

      }


      protected function getUnitPowerFromTimesSpellCast(Dominion $dominion, Unit $unit, string $powerType): float
      {

          $spellPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_times_spell_cast", null);
          $powerFromPerk = 0;

          if (!$spellPerkData)
          {
              return 0;
          }

          $powerFromSpell = (float)$spellPerkData[0];
          $spellKey = (string)$spellPerkData[1];
          $max = isset($spellPerkData[2]) ? (int)$spellPerkData[2] : null;

          $spell = Spell::where('key', $spellKey)->firstOrFail();

          $timeSpellCast = $this->magicCalculator->getTimesSpellCastByDominion($dominion, $spell);

          $powerFromPerk = $powerFromSpell * $timeSpellCast;

        if ($max)
        {
            $powerFromPerk = min($powerFromPerk, $max);
        }

          return $powerFromPerk;

      }

        protected function getUnitPowerFromActiveSelfSpells(Dominion $dominion, Unit $unit, string $powerType): float
        {

            $spellPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_active_self_spells", null);
            $powerFromPerk = 0;

            if (!$spellPerkData)
            {
                return 0;
            }

            $activeSpells = 0;

            # Get all spells from dominion where scope is self and class is passive
            foreach($dominion->activeSpells as $index => $dominionSpell)
            {
                if($dominionSpell->spell->scope == 'self' and $dominionSpell->spell->class == 'passive')
                {
                    $activeSpells += 1;
                }
            }

            $powerPerSpell = (float)$spellPerkData;

            $powerFromPerk = $powerPerSpell * $activeSpells;

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
            $advancementKey = $advancementSet[0];
            $levelRequired = (int)$advancementSet[1];
            $power = (float)$advancementSet[2];

            $advancement = Advancement::where('key', $advancementKey)->firstOrFail();

            if($this->advancementCalculator->hasAdvancementLevel($dominion, $advancement, $levelRequired))
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
          $totalLand = $dominion->land;
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

      protected function getUnitPowerFromResearch(Dominion $dominion, Unit $unit, string $powerType): float
      {
          $researchPerk = $dominion->getTechPerkValue("units_raw_{$powerType}");

          $unitPowerType = ($powerType == 'offense' ? 'power_offense' : 'power_defense');

          if (!$researchPerk or $unit->{$unitPowerType} == 0)
          {
              return 0;
          }

          return $researchPerk;
      }

      protected function getUnitPowerFromVersusSorcerySpellsPerk(Dominion $dominion, ?Dominion $target, Unit $unit, string $powerType, ?array $calc = []): float
      {

          $spellPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_target_active_offensive_spells", null);
          $powerFromPerk = 0;

          if (!$spellPerkData)
          {
              return 0;
          }

          $powerPerSpell = (float)$spellPerkData[0];
          $max = (float)$spellPerkData[1];

          $activeSpells = 0;

          # Get all spells from dominion where scope is offensive and class is passive
          foreach($dominion->activeSpells as $index => $dominionSpell)
          {
              if($dominionSpell->spell->scope == 'hostile' and $dominionSpell->spell->class == 'passive')
              {
                  $activeSpells++;
              }
          }

          $powerFromPerk = min($powerPerSpell * $activeSpells, $max);

          return $powerFromPerk;

      }

    /**
     * Returns the Dominion's morale modifier.
     *
     * Net OP/DP gets lowered linearly by up to -20% at 0% morale.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getMoraleMultiplier(Dominion $dominion, string $mode): float
    {
        if($dominion->getSpellPerkValue('no_morale_bonus_on_' . $mode))
        {
            return 1;
        }
        
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

        $spies += $this->magicCalculator->getWizardPoints($dominion) * $dominion->getDecreePerkValue('wizards_count_as_spies');

        return ($spies / $dominion->land);
    }

    /**
     * Returns the Dominion's spy ratio multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatioMultiplier(Dominion $dominion, string $type = 'offense'): float
    {
        $multiplier = 1;

        // Deity
        $multiplier += $dominion->getDeityPerkMultiplier('spy_strength');

        // Deity
        $multiplier += $dominion->getDecreePerkMultiplier('spy_strength');

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('spy_strength');

        // Improvements
        $multiplier += $dominion->getImprovementPerkMultiplier('spy_strength');

        // Advancement
        $multiplier += $dominion->getAdvancementPerkMultiplier('spy_strength');

        // Tech
        $multiplier += $dominion->getTechPerkMultiplier('spy_strength');
        $multiplier += $dominion->getTechPerkMultiplier('spy_strength_on_' . $type);

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('spy_strength');

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('spy_strength');
        $multiplier += $dominion->getBuildingPerkMultiplier('spy_strength_on_' . $type);

        // Title
        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('spy_strength') * $dominion->getTitlePerkMultiplier();
            $multiplier += $dominion->title->getPerkMultiplier('spy_strength_on_' . $type) * $dominion->getTitlePerkMultiplier();
        }

        return $multiplier;
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
        $regen += $dominion->getAdvancementPerkValue('spy_strength_recovery');
        $regen += $dominion->getTechPerkValue('spy_strength_recovery');
        $regen += $dominion->getImprovementPerkValue('spy_strength_recovery');
        $regen += $dominion->getSpellPerkValue('spy_strength_recovery');
        $regen += $dominion->getBuildingPerkValue('spy_strength_recovery');

        return (float)$regen;
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
                $this->queueService->getDesecrationQueueTotalByResource($dominion, "military_unit{$slot}") +
                $this->queueService->getStunQueueTotalByResource($dominion, "military_unit{$slot}") +
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
                $this->queueService->getDesecrationQueueTotalByResource($dominion, "military_{$slot}") +
                $this->queueService->getStunQueueTotalByResource($dominion, "military_{$slot}") +
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
    public function getRecentlyInvadedCountByAttacker(Dominion $defender, Dominion $attacker, int $ticks = 12): int
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

    public function getRecentInvasionsSent(Dominion $dominion, int $ticks = 12): int
    {
        return GameEvent::query()
            ->where('tick', '>=', ($dominion->round->ticks - $ticks))
            ->where([
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'type' => 'invasion',
            ])
            ->count();
    }

    /**
     * Checks if $defender recently invaded $attacker's realm.
     *
     * 'Recent' refers to the past 24 ticks.
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
        

        return false;
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
            $multiplier += $dominion->getSpellPerkMultiplier('offensive_power');
            
            # Retaliation spells (only vs. self in deathmatches)
            if(in_array($dominion->round->mode,['deathmatch','deathmatch-duration']))
            {
                if ($this->isSelfRecentlyInvadedByTarget($dominion, $target))
                {
                    $multiplier += $dominion->getSpellPerkMultiplier('offensive_power_on_retaliation');
                }
            }
            else
            {
                if ($this->isOwnRealmRecentlyInvadedByTarget($dominion, $target))
                {
                    $multiplier += $dominion->getSpellPerkMultiplier('offensive_power_on_retaliation');
                }
            }

        }
        elseif($power == 'defense')
        {
            $multiplier += $dominion->getSpellPerkMultiplier('defensive_power');# $this->spellCalculator->getPassiveSpellPerkMultiplier($dominion, 'defensive_power');
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
        for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
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

        $attackerLandWithRatioModifier = ($attacker->land);

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

        return floor(max(10, $landConquered));
    }

    public function checkDiscoverLand(Dominion $attacker, Dominion $defender, bool $captureBuildings = false): bool
    {
        if(
                $this->getRecentlyInvadedCountByAttacker($defender, $attacker, 8) == 0
                and !$defender->isAbandoned()
                and !$attacker->getSpellPerkValue('no_land_discovered')
                and $captureBuildings != true
            )
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

        // Techs
        $multiplier += $attacker->getTechPerkMultiplier('land_discovered');

        // Deity
        $multiplier += $attacker->getDeityPerkMultiplier('land_discovered');

        // Troll XP: (max +100% from 2,250,000 XP) – only for factions which cannot take advancements (Troll)
        if($attacker->race->getPerkValue('cannot_research') and $attacker->race->name == 'Troll')
        {
            $multiplier += min($attacker->xp, 2250000) / 2250000;
        }

        return floor($landConquered * $multiplier);

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

        $spellPerkValues = $spell->getActiveSpellPerkValues($spell->key, 'reduces_target_raw_defense_from_terrain');

        $reduction = $spellPerkValues[0];
        $ratio = $spellPerkValues[1];
        $terrainKey = $spellPerkValues[2];
        $max = $spellPerkValues[3] / 100;

        $landTypeRatio = min($attacker->{'terrain_' . $terrainKey}, $attacker->land) / $attacker->land;

        $ambushReductionRatio = min(($landTypeRatio / $ratio) * $reduction, $max);

        return $ambushReductionRatio;
    }

    public function getDefensivePowerModifierFromTerrain(Dominion $dominion, string $terrainKey): float
    {
        $multiplier = 0.0;

        $multiplier += $dominion->race->getPerkValue('defense_from_'.$terrainKey) * ($dominion->{'terrain_'.$terrainKey} / $dominion->land);

        return $multiplier;
    }

    public function getNetVictories(Dominion $dominion): int
    {
        return $this->statsService->getStat($dominion, 'invasion_victories') - $this->statsService->getStat($dominion, 'defense_failures');
    }

    public function getRawMilitaryPowerFromAnnexedDominion(Dominion $dominion): int
    {
        $militaryPower = 0;
        for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
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
        $reduction += $attacker->getDecreePerkMultiplier('target_defensive_power_mod');
        $reduction += $attacker->getTechPerkMultiplier('target_defensive_power_mod');
        $reduction += $attacker->realm->getArtefactPerkMultiplier('target_defensive_power_mod');

        return $reduction;
    }

    public function getOffensiveMultiplierReduction(Dominion $defender): float
    {
        $reduction = 1;

        $reduction += $defender->getBuildingPerkMultiplier('attacker_offensive_power_mod');
        $reduction += $defender->getSpellPerkMultiplier('attacker_offensive_power_mod');
        $reduction += $defender->getImprovementPerkMultiplier('attacker_offensive_power_mod');
        $reduction += $defender->getDeityPerkMultiplier('attacker_offensive_power_mod');

        return $reduction;
    }

    public function getStrengthGain(Dominion $monster, Dominion $enemy, string $mode, array $invasionResult): int
    {
        $rawOp = $invasionResult['attacker']['op_raw'];
        $rawDp = $invasionResult['defender']['dp_raw'];
        $opDpRatio = $invasionResult['result']['op_dp_ratio'];

        $strength = 0;

        if($mode == 'offense')
        {
            # Successful invasion: 10% of own raw OP required to break the target and 10% of the target's raw DP.
            if($invasionResult['result']['success'])
            {
                $strength += $rawOp * 0.10 * (1/$opDpRatio);
                $strength += $rawDp * 0.10;
            }
            # Non-overwhelmed failed invasion: <code>7.5% * [OP:DP Ratio]</code> of own raw OP and <code>5% * [OP:DP Ratio]</code> of target's raw DP.
            elseif(!$invasionResult['result']['success'] and !$invasionResult['result']['overwhelmed'])
            {
                $strength += $rawOp * 0.075 * $opDpRatio;
                $strength += $rawDp * 0.05 * $opDpRatio;
            }
            # Overwhelmed failed invasion: <code>-20% * (1/[OP:DP Ratio])</code> of own raw OP and <code>-10% * [OP:DP Ratio]</code> of target's raw DP.
            elseif(!$invasionResult['result']['success'] and $invasionResult['result']['overwhelmed'])
            {
                $strength += $rawOp * -0.20 * (1/$opDpRatio);
                $strength += $rawDp * -0.10 * $opDpRatio;
            }
        }
        elseif($mode == 'defense')
        {
            # Successfully fending off: 10% of own raw DP and 10% * of the invader's raw OP.
            if(!$invasionResult['result']['success'])
            {
                $strength += $rawDp * 0.10;
                $strength += $rawOp * 0.10 * (1/$opDpRatio);
            }

            # Failed fending off (successfully invaded): <code>-7.5% * [OP:DP Ratio]</code> of invader's raw OP.
            if($invasionResult['result']['success'])
            {
                $strength += $rawOp * -0.10 * $opDpRatio;
            }
        }

        $strengthGainMultiplier = 1;
        $strengthGainMultiplier += $monster->getSpellPerkValue('strength_gain_mod');

        $strength *= $strengthGainMultiplier;

        return $strength;

    }

    public function estimateMaxSendable(Dominion $dominion, Dominion $enemy = null): array
    {
        $ratios = [];
        $sortedUnits = [];
        $units = [];
        $unitsDefending = array_fill(1, $dominion->race->units->count(), 0);
        $unitsSent = array_fill(1, $dominion->race->units->count(), 0);
        
        if($enemy)
        {
            $landRatio = $dominion->land / $enemy->land;
        }
        else
        {
            $landRatio = 1;
        }        

        foreach($dominion->race->units as $unit)
        {
            $units[$unit->slot] = [
                    'amount' => $dominion->{'military_unit' . $unit->slot},
                    'op' => $this->getUnitPowerWithPerks($dominion, $enemy, $landRatio, $unit, 'offense', [], []) * $this->getOffensivePowerMultiplier($dominion, $enemy),
                    'dp' => $this->getUnitPowerWithPerks($dominion, $enemy, $landRatio, $unit, 'defense', null, [], []) * $this->getDefensivePowerMultiplier($dominion, $enemy),
            ];

            if($units[$unit->slot]['dp'] > 0 and $units[$unit->slot]['op'] > 0)
            {
                $ratios[$unit->slot] = $units[$unit->slot]['op'] / $units[$unit->slot]['dp'];
            }
            elseif($units[$unit->slot]['dp'] == 0 and $units[$unit->slot]['op'] > 0)
            {
                $ratios[$unit->slot] = $units[$unit->slot]['op'];
            }
            else
            {
                $ratios[$unit->slot] = 0;
            }

            $unitsDefending[$unit->slot] = $this->getTotalUnitsForSlot($dominion, $unit->slot);
        }

        arsort($ratios);

        foreach($ratios as $slot => $ratio)
        {
            $sortedUnits[$slot] = $units[$slot];
        }
        
        $maxRatio = 4/3;
        $x = 0;

        foreach($sortedUnits as $unitSlot => $unitData)
        {
            /*
            # Exhaust all of this unit
            while($unitsDefending[$unitSlot] > 0)
            {
                $unitsSent[$unitSlot] = min($unitsSent[$unitSlot] + 1, $units[$unitSlot]['amount']);
                $unitsDefending[$unitSlot] = max($unitsDefending[$unitSlot] - 1, 0);
            
                $op = $this->getOffensivePower($dominion, $enemy, null, $unitsSent);
                $dp = $this->getDefensivePower($dominion, $enemy, null, $unitsDefending);
                $ratio = $op / $dp;

                if($ratio >= $maxRatio)
                {
                    break;
                }

                $x++;
            }

            if($ratio >= $maxRatio)
            {
                break;
            }
            */
            $nextGuess = $units[$unitSlot]['amount'];

            while($unitsDefending[$unitSlot] > 0 && $nextGuess > 0 && $unitsSent[$unitSlot] < $units[$unitSlot]['amount'])
            {
                $unitsSent[$unitSlot] = min($unitsSent[$unitSlot] + $nextGuess, $units[$unitSlot]['amount']);
                $unitsDefending[$unitSlot] = max($unitsDefending[$unitSlot] - $nextGuess, 0);
            
                $op = $this->getOffensivePower($dominion, $enemy, null, $unitsSent);
                $dp = $this->getDefensivePower($dominion, $enemy, null, $unitsDefending);
                $ratio = $op / $dp;
                
            
                if($ratio >= $maxRatio || $unitsSent[$unitSlot] > $units[$unitSlot]['amount'])
                {
                    $unitsSent[$unitSlot] = $unitsSent[$unitSlot] - $nextGuess;
                    $unitsDefending[$unitSlot] = $unitsDefending[$unitSlot] + $nextGuess;
                    if($nextGuess == 1)
                    {
                        $nextGuess = 0;
                    }
                    else
                    {
                        $nextGuess = min(floor($nextGuess / 2), 1);
                    }
                }
            }
        }

        return [
            'offensive_power' => $op,
            'defensive_power' => $dp,
            'ratio' => $ratio,
            'units_sent' => $unitsSent,
            'units_defending' => $unitsDefending
        ];

    }

    public function getTotalUnitsAtHome(Dominion $dominion, bool $includeDraftees = true, bool $includeSpiesAndWizards = false, bool $includePeasants = false, bool $includeUnitsInTraining = false): int
    {
        $units = 0;

        foreach($dominion->race->units as $unit)
        {
            $units += $dominion->{'military_unit' . $unit->slot};

            if($includeUnitsInTraining)
            {
                $units += $this->queueService->getTrainingQueueTotalByResource($dominion, ('military_unit' . $unit->slot));
                $units += $this->queueService->getSummonigQueueTotalByResource($dominion, ('military_unit' . $unit->slot));
            }
        }
        
        if($includeDraftees)
        {
            $units += $dominion->military_draftees;
        }

        if($includeSpiesAndWizards)
        {
            $units += $dominion->military_spies;
            $units += $dominion->military_wizards;
            $units += $dominion->military_archmages;

            if($includeUnitsInTraining)
            {
                $units += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_spies');
                $units += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_wizards');
                $units += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_archmages');
                $units += $this->queueService->getSummoningQueueTotalByResource($dominion, 'military_spies');
                $units += $this->queueService->getSummoningQueueTotalByResource($dominion, 'military_wizards');
                $units += $this->queueService->getSummoningQueueTotalByResource($dominion, 'military_archmages');
            }
        }
        
        if($includePeasants)
        {
            $units += $dominion->peasants;
        }

        return $units;

    }

    public function dpFromUnitWithoutSufficientResources(Dominion $defender, Dominion $attacker = null, ?float $landRatio = null, ?array $units = [], ?array $invadingUnits = []): float
    {
        $dpFromUnitsWithoutSufficientResources = 0;

        foreach($defender->race->resources as $resourceKey)
        {
            $resourceAmountReserved[$resourceKey] = 0;
            $resourceAmountOwned[$resourceKey] = $this->resourceCalculator->getAmount($defender, $resourceKey);
            $resourceAmountRemaining[$resourceKey] = $this->resourceCalculator->getAmount($defender, $resourceKey);
        }

        foreach($defender->race->units->sortByDesc('power_defense') as $unit)
        {
            if($spendsResourceOnDefensePerk = $defender->race->getUnitPerkValueForUnitSlot($unit->slot, 'spends_resource_on_defense'))
            {
                $powerDefense = $this->getUnitPowerWithPerks($defender, $attacker, $landRatio, $unit, 'defense', null, $units, $invadingUnits);

                $resourceKey = $spendsResourceOnDefensePerk[0];
                $amountRequiredPerUnit = $spendsResourceOnDefensePerk[1];

                $unitsDefending = $defender->{'military_unit' . $unit->slot};

                # Remove units sent from units defending
                if(isset($units[$unit->slot]))
                {
                    $unitsDefending -= $units[$unit->slot];
                }

                $unitsWithEnoughResources = max(min($unitsDefending, $resourceAmountRemaining[$resourceKey] / $amountRequiredPerUnit), 0);

                $resourceAmountRequiredByThisUnit = $unitsDefending * $amountRequiredPerUnit;
                $resourceAmountRemaining[$resourceKey] = max($resourceAmountRemaining[$resourceKey] - $resourceAmountRequiredByThisUnit, 0);

                if($unitsDefending > $unitsWithEnoughResources)
                {
                    $dpFromUnitsWithoutSufficientResources += $powerDefense * ($unitsDefending - $unitsWithEnoughResources);
                }
            }
        }
    
        return $dpFromUnitsWithoutSufficientResources;

    }

    public function getMaxSendableUnits(Dominion $dominion): int
    {
        $maxUnits = 0;
        $maxUnits += $dominion->getBuildingPerkValue('unit_send_capacity');

        $multiplier = 1;
        $multiplier += $dominion->getAdvancementPerkMultiplier('unit_send_capacity_mod');
        $multiplier += $dominion->getTechPerkMultiplier('unit_send_capacity_mod');
        $multiplier += $dominion->getImprovementPerkMultiplier('unit_send_capacity_mod');
        $multiplier += $dominion->getDecreePerkMultiplier('unit_send_capacity_mod');
        $multiplier += $dominion->getSpellPerkMultiplier('unit_send_capacity_mod');

        return $maxUnits * $multiplier;
    }

    public function getUnitSabotagePoints(Dominion $saboteur, string $unitType): float
    {
        if($unitType == 'spies')
        {
            return 1;
        }

        $unitSlot = (int)str_replace('unit','',$unitType);

        $unit = $saboteur->race->units->filter(function ($unit) use ($unitSlot) {
            return ($unit->slot === $unitSlot);
        })->first();

        # Get unit offensive power
        $unitOp = $this->getUnitPowerWithPerks($saboteur, null, null, $unit, 'offense');

        # Get unit counts_as_spy perk power
        $unitSpyPower = $unit->getPerkValue('counts_as_spy');
        $unitSpyPower += $unit->getPerkValue('counts_as_spy_offense');
        $unitSpyPower += $unit->getPerkValue('counts_as_spy_on_sabotage');

        if ($timePerkData = $saboteur->race->getUnitPerkValueForUnitSlot($unit->slot, ("counts_as_spy_offense_from_time"), null))
        {
            $powerFromTime = (float)$timePerkData[2];
            $hourFrom = $timePerkData[0];
            $hourTo = $timePerkData[1];
            if (
                (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
            )
            {
                $unitSpyPower += $powerFromTime;
            }
        }

        return $unitOp * $unitSpyPower;
    }

    public function getUnitsSabotagePower(Dominion $saboteur, array $units): float
    {
        $sabotagePower = 0;

        foreach($units as $unitType => $amount)
        {
            $sabotagePower += $this->getUnitSabotagePoints($saboteur, $unitType) * $amount;
        }

        return $sabotagePower;
    }

    public function getRecentAttacksBetweenRealms(Realm $source, Realm $target, int $ticks = 48): int
    {
        # Check if there have been invasion Game Events between thet wo realms in the last $ticks ticks (default 48).
        $recentAttacks = GameEvent::where('source_id', $source->id)
            ->where('target_id', $target->id)
            ->where('type', 'invasion')
            ->where('tick', '>=', ($source->round->ticks - $ticks))
            ->count();
        
        $recentAttacks += GameEvent::where('source_id', $target->id)
            ->where('target_id', $source->id)
            ->where('type', 'invasion')
            ->where('tick', '>=', ($source->round->ticks - $ticks))
            ->count();

        return $recentAttacks;
    }

    public function getTotalPowerOfUnit(Dominion $dominion, Dominion $enemy, float $landRatio = null, Unit $unit, $units = null, $invadingUnits = null): float
    {
        $op = $this->getUnitPowerWithPerks($dominion, $enemy, $landRatio, $unit, 'offense', null, $units, $invadingUnits);
        $dp = $this->getUnitPowerWithPerks($dominion, $enemy, $landRatio, $unit, 'defense', null, $units, $invadingUnits);

        return $op + $dp;
    }

}
