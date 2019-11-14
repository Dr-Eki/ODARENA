<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Unit;
use OpenDominion\Services\Dominion\QueueService;

# ODA
use Illuminate\Support\Carbon;

class MilitaryCalculator
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var PrestigeCalculator */
    private $prestigeCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /**
     * MilitaryCalculator constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param ImprovementCalculator $improvementCalculator
     * @param LandCalculator $landCalculator
     * @param PrestigeCalculator $prestigeCalculator
     * @param QueueService $queueService
     * @param SpellCalculator $spellCalculator
     * @param PopulationCalculator $populationCalculator
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        PrestigeCalculator $prestigeCalculator,
        QueueService $queueService,
        SpellCalculator $spellCalculator
        )
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->prestigeCalculator = $prestigeCalculator;
        $this->queueService = $queueService;
        $this->spellCalculator = $spellCalculator;
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
        Dominion $dominion,
        Dominion $target = null,
        float $landRatio = null,
        array $units = null,
        array $calc = []
    ): float
    {
        $op = ($this->getOffensivePowerRaw($dominion, $target, $landRatio, $units, $calc) * $this->getOffensivePowerMultiplier($dominion));

        return ($op * $this->getMoraleMultiplier($dominion));
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
        Dominion $dominion,
        Dominion $target = null,
        float $landRatio = null,
        array $units = null,
        array $calc = []
    ): float
    {
        $op = 0;

        foreach ($dominion->race->units as $unit) {
            $powerOffense = $this->getUnitPowerWithPerks($dominion, $target, $landRatio, $unit, 'offense', $calc);
            $numberOfUnits = 0;

            if ($units === null) {
                $numberOfUnits = (int)$dominion->{'military_unit' . $unit->slot};
            } elseif (isset($units[$unit->slot]) && ((int)$units[$unit->slot] !== 0)) {
                $numberOfUnits = (int)$units[$unit->slot];
            }

            if ($numberOfUnits !== 0) {
                $bonusOffense = $this->getBonusPowerFromPairingPerk($dominion, $unit, 'offense', $units);
                $powerOffense += $bonusOffense / $numberOfUnits;
            }

            $op += ($powerOffense * $numberOfUnits);
        }

        return $op;
    }

    /**
     * Returns the Dominion's offensive power multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)

        $opPerGryphonNest = 2;
        $gryphonNestMaxOp = 40;


        $spellBloodrage = 10;
        $spellCrusade = 5;
        $spellHowling = 10;
        $spellKillingRage = 10;
        $spellWarsong = 10;
        $spellNightfall = 5;

        // Gryphon Nests
        # Spell: Snow Elf - Gryphon's Call: no OP bonus from GNs.
        if (!$this->spellCalculator->isSpellActive($dominion, 'gryphons_call'))
        {
          $multiplier += min(
              (($opPerGryphonNest * $dominion->building_gryphon_nest) / $this->landCalculator->getTotalLand($dominion)),
              ($gryphonNestMaxOp / 100)
          );

        }

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('offense');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('offense');

        // Improvement: Forges
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'forges');

        // Beastfolk: Plains increases OP
        if($dominion->race->name == 'Beastfolk')
        {
          $multiplier += 0.2 * ($dominion->{"land_plain"} / $this->landCalculator->getTotalLand($dominion));
        }

        // Racial Spell
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, [
            'bloodrage' => $spellBloodrage,
            'crusade' => $spellCrusade,
            'howling' => $spellHowling,
            'killing_rage' => $spellKillingRage,
            'warsong' => $spellWarsong,
            'nightfall' => $spellNightfall,
        ]);

        // Prestige
        $multiplier += $this->prestigeCalculator->getPrestigeMultiplier($dominion);

        return (1 + $multiplier);
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
     * @return float
     */
    public function getDefensivePower(
        Dominion $dominion,
        Dominion $target = null,
        float $landRatio = null,
        array $units = null,
        float $multiplierReduction = 0,
        bool $ignoreDraftees = false,
        bool $isAmbush = false
    ): float
    {
        $dp = $this->getDefensivePowerRaw($dominion, $target, $landRatio, $units, $ignoreDraftees, $isAmbush);
        $dp *= $this->getDefensivePowerMultiplier($dominion, $multiplierReduction);

        return ($dp * $this->getMoraleMultiplier($dominion));
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
        Dominion $dominion,
        Dominion $target = null,
        float $landRatio = null,
        array $units = null,
        bool $ignoreDraftees = false,
        bool $isAmbush = false
    ): float
    {
        $dp = 0;

        // Values
        $minDPPerAcre = 1.5;
        $dpPerDraftee = 1;
        $forestHavenDpPerPeasant = 0.75;
        $peasantsPerForestHaven = 20;

        # Some draftees are weaker (Ants, Growth), and some draftees
        # count as no DP. If no DP, draftees do not participate in battle.
        if($dominion->race->getPerkValue('draftee_dp'))
        {
          if($dominion->race->getPerkValue('draftee_dp') === 0)
          {
            $dpPerDraftee = 0;
            $ignoreDraftees == TRUE;
          }
          elseif($dominion->race->getPerkValue('draftee_dp') > 0)
          {
            $dpPerDraftee = $dominion->race->getPerkValue('draftee_dp');
          }
        }

        // Military
        foreach ($dominion->race->units as $unit) {
            $powerDefense = $this->getUnitPowerWithPerks($dominion, $target, $landRatio, $unit, 'defense');

            $numberOfUnits = 0;

            if ($units === null) {
                $numberOfUnits = (int)$dominion->{'military_unit' . $unit->slot};
            } elseif (isset($units[$unit->slot]) && ((int)$units[$unit->slot] !== 0)) {
                $numberOfUnits = (int)$units[$unit->slot];
            }

            if ($numberOfUnits !== 0) {
                $bonusDefense = $this->getBonusPowerFromPairingPerk($dominion, $unit, 'defense', $units);
                $powerDefense += $bonusDefense / $numberOfUnits;
            }

            $dp += ($powerDefense * $numberOfUnits);
        }

        // Draftees
        if (!$ignoreDraftees) {
            if ($units !== null && isset($units[0])) {
                $dp += ((int)$units[0] * $dpPerDraftee);
            } else {
                $dp += ($dominion->military_draftees * $dpPerDraftee);
            }
        }

        // Beastfolk: Ambush (reduce raw DP by 2 x Forest %, max -10)
        if($isAmbush)
        {
          $forestRatio = $target->{'land_forest'} / $this->landCalculator->getTotalLand($target);
          $forestRatioModifier = $forestRatio / 5;
          $ambushReduction = min($forestRatioModifier, 0.10);
          $dp = $dp * (1 - $ambushReduction);
        }

        // Attacking Forces skip land-based defenses
        if ($units !== null)
            return $dp;

        // Forest Havens
        $dp += min(
            ($dominion->peasants * $forestHavenDpPerPeasant),
            ($dominion->building_forest_haven * $forestHavenDpPerPeasant * $peasantsPerForestHaven)
        ); // todo: recheck this


        return max(
            $dp,
            ($minDPPerAcre * $this->landCalculator->getTotalLand($dominion))
        );
    }

    /**
     * Returns the Dominion's defensive power multiplier.
     *
     * @param Dominion $dominion
     * @param float $multiplierReduction
     * @return float
     */
    public function getDefensivePowerMultiplier(Dominion $dominion, float $multiplierReduction = 0): float
    {
        $multiplier = 0;

        // Values (percentages)
        $dpPerGuardTower = 2;
        $guardTowerMaxDp = 40;
        $spellAresCall = 10;
        $spellBlizzard = 5; # From 15%
        $spellFrenzy = 10; # From 10%
        $spellHowling = 10;

        // Guard Towers
        $multiplier += min(
            (($dpPerGuardTower * $dominion->building_guard_tower) / $this->landCalculator->getTotalLand($dominion)),
            ($guardTowerMaxDp / 100)
        );

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('defense');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('defense');

        // Improvement: Walls
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'walls');

        // Beastfolk: Hills increases DP
        if($dominion->race->name == 'Beastfolk')
        {
          $multiplier += 1 * ($dominion->{'land_hill'} / $this->landCalculator->getTotalLand($dominion));
        }

        // Spell: Howling (+10%)
        $multiplierFromHowling = $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'howling', $spellHowling);
        $multiplier += $multiplierFromHowling;

        // Spell: Coastal Cannons
        if ($this->spellCalculator->isSpellActive($dominion, 'coastal_cannons'))
        {
          $multiplierFromCoastalCannons = $dominion->{'land_water'} / $this->landCalculator->getTotalLand($dominion);
          $multiplier += min($multiplierFromCoastalCannons,0.20);
        }
        else {
          $multiplierFromCoastalCannons = 0;
        }

        // Spell: Fimbulwinter (+10% DP)
        if ($this->spellCalculator->isSpellActive($dominion, 'fimbulwinter'))
        {
          $multiplier += 0.10;
        }

        // Spell: Blizzard (+5%)
        $multiplierFromBlizzard = $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'blizzard', $spellBlizzard);
        $multiplier += $multiplierFromBlizzard;

        // Spell: Frenzy (Halfling) (+10%)
        $multiplierFromFrenzy = $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'defensive_frenzy', $spellFrenzy);
        $multiplier += $multiplierFromFrenzy;

        // Spell: Ares' Call (+10%)
        if($multiplierFromHowling == 0 && $multiplierFromBlizzard == 0 && $multiplierFromFrenzy == 0 and $multiplierFromCoastalCannons == 0) {
            $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'ares_call',
                $spellAresCall);
        }

        // Multiplier reduction when we want to factor in temples from another
        // dominion
        $multiplier = max(($multiplier - $multiplierReduction), 0);

        return (1 + $multiplier);
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
        Unit $unit,
        string $powerType,
        array $calc = []
    ): float
    {
        $unitPower = $unit->{"power_$powerType"};

        $unitPower += $this->getUnitPowerFromLandBasedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromBuildingBasedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromRawWizardRatioPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromRawSpyRatioPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromPrestigePerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromRecentlyInvadedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromHoursPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromMilitaryPercentagePerk($dominion, $unit, $powerType);

        if ($landRatio !== null) {
            $unitPower += $this->getUnitPowerFromStaggeredLandRangePerk($dominion, $landRatio, $unit, $powerType);
        }

        if ($target !== null || !empty($calc)) {
            $unitPower += $this->getUnitPowerFromVersusRacePerk($dominion, $target, $unit, $powerType);
            $unitPower += $this->getUnitPowerFromVersusBuildingPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromVersusLandPerk($dominion, $target, $unit, $powerType, $calc);
            $unitPower += $this->getUnitPowerFromVersusPrestigePerk($dominion, $target, $unit, $powerType, $calc);
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
        $constructedOnly = false;
        //$constructedOnly = $landPerkData[3]; todo: implement for Nox?
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        if (!$constructedOnly)
        {
            $landPercentage = ($dominion->{"land_{$landType}"} / $totalLand) * 100;
        }
        else
        {
            $buildingsForLandType = $this->buildingCalculator->getTotalBuildingsForLandType($dominion, $landType);

            $landPercentage = ($buildingsForLandType / $totalLand) * 100;
        }

        $powerFromLand = $landPercentage / $ratio;
        $powerFromPerk = min($powerFromLand, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromBuildingBasedPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $buildingPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_building", null);

        if (!$buildingPerkData) {
            return 0;
        }

        $buildingType = $buildingPerkData[0];
        $ratio = (int)$buildingPerkData[1];
        $max = (int)$buildingPerkData[2];
        $totalLand = $this->landCalculator->getTotalLand($dominion);
        $landPercentage = ($dominion->{"building_{$buildingType}"} / $totalLand) * 100;

        $powerFromBuilding = $landPercentage / $ratio;
        $powerFromPerk = min($powerFromBuilding, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromRawWizardRatioPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $wizardRatioPerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_raw_wizard_ratio");

        if (!$wizardRatioPerk) {
            return 0;
        }

        $ratio = (float)$wizardRatioPerk[0];
        $max = (int)$wizardRatioPerk[1];

        $wizardRawRatio = $this->getWizardRatioRaw($dominion, 'offense');
        $powerFromWizardRatio = $wizardRawRatio * $ratio;
        $powerFromPerk = min($powerFromWizardRatio, $max);

        return $powerFromPerk;
    }


    protected function getUnitPowerFromRawSpyRatioPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $spyRatioPerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_raw_spy_ratio");

        if(!$spyRatioPerk) {
            return 0;
        }

        $ratio = (float)$spyRatioPerk[0];
        $max = (int)$spyRatioPerk[1];

        $spyRawRatio = $this->getSpyRatioRaw($dominion, 'offense');
        $powerFromSpyRatio = $spyRawRatio * $ratio;
        $powerFromPerk = min($powerFromSpyRatio, $max);

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

        $powerFromPerk = min($dominion->prestige / $amount, $max);

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

            if ($range > $landRatio) { // TODO: Check this, might be a bug here
                continue;
            }

            $powerFromPerk = $power;
        }

        return $powerFromPerk;
    }

    protected function getUnitPowerFromVersusRacePerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType): float
    {
        if ($target === null) {
            return 0;
        }

        $raceNameFormatted = strtolower($target->race->name);
        $raceNameFormatted = str_replace(' ', '_', $raceNameFormatted);

        $versusRacePerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_vs_{$raceNameFormatted}");

        return $versusRacePerk;
    }

    protected function getBonusPowerFromPairingPerk(Dominion $dominion, Unit $unit, string $powerType, array $units = null): float
    {
        $pairingPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_pairing", null);
        if (!$pairingPerkData) {
            return 0;
        }

        $unitSlot = (int)$pairingPerkData[0];
        $amount = (int)$pairingPerkData[1];
        if (isset($pairingPerkData[2])) {
            $numRequired = (int)$pairingPerkData[2];
        } else {
            $numRequired = 1;
        }

        $powerFromPerk = 0;
        $numberPaired = 0;
        if ($units === null) {
            $numberPaired = min($dominion->{'military_unit' . $unit->slot}, floor((int)$dominion->{'military_unit' . $unitSlot} / $numRequired));
        } elseif (isset($units[$unitSlot]) && ((int)$units[$unitSlot] !== 0)) {
            $numberPaired = min($units[$unit->slot], floor((int)$units[$unitSlot] / $numRequired));
        }
        $powerFromPerk = $numberPaired * $amount;

        return $powerFromPerk;
    }

    protected function getUnitPowerFromVersusBuildingPerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType, array $calc = []): float
    {
        if ($target === null && empty($calc)) {
            return 0;
        }

        $versusBuildingPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_vs_building", null);
        if (!$versusBuildingPerkData) {
            return 0;
        }

        $buildingType = $versusBuildingPerkData[0];
        $ratio = (int)$versusBuildingPerkData[1];
        $max = (int)$versusBuildingPerkData[2];

        $landPercentage = 0;
        if (!empty($calc)) {
            # Override building percentage for invasion calculator
            if (isset($calc["{$buildingType}_percent"])) {
                $landPercentage = (float) $calc["{$buildingType}_percent"];
            }
        } elseif ($target !== null) {
            $totalLand = $this->landCalculator->getTotalLand($target);
            $landPercentage = ($target->{"building_{$buildingType}"} / $totalLand) * 100;
        }

        $powerFromBuilding = $landPercentage / $ratio;
        if ($max < 0) {
            $powerFromPerk = max(-1 * $powerFromBuilding, $max);
        } else {
            $powerFromPerk = min($powerFromBuilding, $max);
        }

        return $powerFromPerk;
    }


    protected function getUnitPowerFromVersusLandPerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType, array $calc = []): float
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

    protected function getUnitPowerFromRecentlyInvadedPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $amount = 0;

        if($this->getRecentlyInvadedCount($dominion) > 0)
        {
          $amount = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot,"{$powerType}_if_recently_invaded");
        }

        return $amount;
    }

    protected function getUnitPowerFromHoursPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $hoursPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_per_hour", null);

        if (!$hoursPerkData)
        {
            return 0;
        }

        #$hoursSinceRoundStarted = ($dominion->round->start_date)->diffInHours(now());
        $hoursSinceRoundStarted = now()->startOfHour()->diffInHours(Carbon::parse($dominion->round->start_date)->startOfHour());

        $powerPerHour = (float)$hoursPerkData[0];
        $max = (float)$hoursPerkData[1];

        $powerFromHours = $powerPerHour * $hoursSinceRoundStarted;

        $powerFromPerk = min($powerFromHours, $max);

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
            $prestige = $target->prestige;
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
        $military += $dominion->military_unit1;
        $military += $dominion->military_unit2;
        $military += $dominion->military_unit3;
        $military += $dominion->military_unit4;
        $military += $dominion->military_spies;
        $military += $dominion->military_wizards;
        $military += $dominion->military_archmages;
        $military += $dominion->military_draftees;

        $militaryPercentage = min(1, $military / ($military + $dominion->peasants));

        $powerFromPerk = min($militaryPercentagePerk * $militaryPercentage, 1);

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
    public function getMoraleMultiplier(Dominion $dominion): float
    {
        #return (1 + (($dominion->morale - 100) / 100));
        #return clamp((0.9 + ($dominion->morale / 1000)), 0.9, 2.0);
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
        return ($this->getSpyRatioRaw($dominion, $type) * $this->getSpyRatioMultiplier($dominion));
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
        foreach ($dominion->race->units as $unit) {
            if ($type === 'offense' && $unit->getPerkValue('counts_as_spy_offense')) {
                $spies += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_spy_offense'));
            }

            if ($type === 'defense' && $unit->getPerkValue('counts_as_spy_defense')) {
                $spies += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_spy_defense'));
            }
        }

        return ($spies / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's spy ratio multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatioMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('spy_strength');

        # Hideouts
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'hideouts');

        // Beastfolk: Cavern increases Spy Strength
        if($dominion->race->name == 'Beastfolk')
        {
          $multiplier += 1 * ($dominion->{"land_cavern"} / $this->landCalculator->getTotalLand($dominion));
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

        // todo: Spy Master / Dark Artistry tech

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
        return ($this->getWizardRatioRaw($dominion, $type) * $this->getWizardRatioMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw wizard ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatioRaw(Dominion $dominion, string $type = 'offense'): float
    {
        $wizards = $dominion->military_wizards + ($dominion->military_archmages * 2);

        // Add units which count as (partial) spies (Dark Elf Adept)
        foreach ($dominion->race->units as $unit) {
            if ($type === 'offense' && $unit->getPerkValue('counts_as_wizard_offense')) {
                $wizards += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_wizard_offense'));
            }

            if ($type === 'defense' && $unit->getPerkValue('counts_as_wizard_defense')) {
                $wizards += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_wizard_defense'));
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
    public function getWizardRatioMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('wizard_strength');

        // Improvement: Towers
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'towers');

        // Beastfolk: Swamp increases Wizard Strength
        if($dominion->race->name == 'Beastfolk')
        {
          $multiplier += 2 * ($dominion->{"land_swamp"} / $this->landCalculator->getTotalLand($dominion))  * $this->prestigeCalculator->getPrestigeMultiplier($dominion);
        }

        // Tech: Magical Weaponry  (+15%)
        // todo

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
     * Gets the total amount of living specialist/elite units for a Dominion.
     *
     * Total amount includes units at home and units returning from battle.
     *
     * @param Dominion $dominion
     * @param int $slot
     * @return int
     */
    public function getTotalUnitsForSlot(Dominion $dominion, int $slot): int
    {
        return (
            $dominion->{'military_unit' . $slot} +
            $this->queueService->getInvasionQueueTotalByResource($dominion, "military_unit{$slot}")
        );
    }

    /**
     * Returns the number of time the Dominion was recently invaded.
     *
     * 'Recent' refers to the past 24 hours.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getRecentlyInvadedCount(Dominion $dominion): int
    {
        // todo: this touches the db. should probably be in invasion or military service instead
        $invasionEvents = GameEvent::query()
            #->where('created_at', '>=', now()->subDay(1))
            ->where('created_at', '>=', now()->subHours(6))
            ->where([
                'target_type' => Dominion::class,
                'target_id' => $dominion->id,
                'type' => 'invasion',
            ])
            ->get();

        if ($invasionEvents->isEmpty()) {
            return 0;
        }

        $invasionEvents = $invasionEvents->filter(function (GameEvent $event) {
            return !$event->data['result']['overwhelmed'];
        });

        return $invasionEvents->count();
    }
}
