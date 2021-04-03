<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;

# ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
#use OpenDominion\Calculators\Dominion\RangeCalculator;

class CasualtiesCalculator
{

    /*
     * CasualtiesCalculator constructor.
     */
    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->unitHelper = app(UnitHelper::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->spellDamageCalculator = app(SpellDamageCalculator::class);
    }

    /**
     * Get the offensive casualty multiplier for a dominion for a specific unit
     * slot.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param int $slot
     * @param array $units Units being sent out on invasion
     * @param float $landRatio
     * @param bool $isOverwhelmed
     * @param bool $attackingForceOP
     * @param bool $targetDP
     * @param bool $isInvasionSuccessful - True/False flag whether invasion was successful
     * @return float
     */
    public function getOffensiveCasualtiesMultiplierForUnitSlot(Dominion $dominion, Dominion $target, int $slot, array $units, float $landRatio, bool $isOverwhelmed, float $attackingForceOP, float $targetDP, bool $isInvasionSuccessful, bool $isAmbush): float
    {
        $multiplier = 1;

        # CHECK IMMORTALITY

        // Check if unit has fixed casualties first, so we can skip all other checks
        if ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'fixed_casualties') !== 0)
        {
            return 1;
        }

        // If you are fighting against a does_not_kill race (Lux)
        # This means that OFFENSIVE CASUALTIES are zero when INVADING a Lux.
        if($target->race->getPerkValue('does_not_kill') == 1)
        {
            $multiplier = 0;
        }

        // Then check immortality, so we can skip the other remaining checks if we indeed have immortal units, since
        // casualties will then always be 0 anyway

        // General "Almost never dies" type of immortality.
        if ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal') or $dominion->race->getUnitPerkValueForUnitSlot($slot, 'spirit_immortal'))
        {
            if (!$this->spellCalculator->getPassiveSpellPerkMultiplier($target, 'can_kill_immortal'))
            {
                $multiplier = 0;
            }
        }

        // True immortality (cannot be overridden)
        if ((bool)$dominion->race->getUnitPerkValueForUnitSlot($slot, 'true_immortal'))
        {
            $multiplier = 0;
        }

        // Range-based immortality
        if (($immortalVsLandRange = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal_vs_land_range')) !== 0)
        {
            if ($landRatio >= ($immortalVsLandRange / 100))
            {
                $multiplier = 0;
            }
        }

        // Race perk-based immortality
        if ($this->isImmortalVersusRacePerk($dominion, $target, $slot))
        {
            $multiplier = 0;
        }

        // Perk: immortal on victory
        if ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal_on_victory') and $isInvasionSuccessful)
        {
            $multiplier = 0;
        }
        # END CHECK IMMORTALITY

        # CHECK ONLY DIES VS X RAW POWER
        if ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'only_dies_vs_raw_power') !== 0)
        {
            $minPowerToKill = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'only_dies_vs_raw_power');
            $dpFromUnitsThatKill = 0;

            # Get the raw DP of each unit of $target.
            foreach ($target->race->units as $unit)
            {
                # If the raw DP on the unit is enough, add it to $dpFromUnitsThatKill.
                if($this->militaryCalculator->getUnitPowerWithPerks($target, $dominion, $landRatio, $unit, 'defense') >= $minPowerToKill)
                {
                  $dpFromUnitsThatKill += $this->militaryCalculator->getUnitPowerWithPerks($target, $dominion, $landRatio, $unit, 'defense') * $target->{"military_unit" . $unit->slot};
                }
            }

            # How much of the DP is from units that kill?
            $multiplier = $dpFromUnitsThatKill / $this->militaryCalculator->getDefensivePowerRaw($target, $dominion, $landRatio, null, 0, false, $isAmbush, false);
        }
        # END CHECK ONLY DIES VS X RAW POWER

        # CHECK UNIT AND RACIAL CASUALTY MODIFIERS

        if ($multiplier != 0)
        {

            # Buildings
            $multiplier -= $dominion->getBuildingPerkMultiplier('offensive_casualties');

            # Land-based reductions
            $multiplier -= $this->getCasualtiesReductionFromLand($dominion, $slot, 'offense');
            $multiplier -= $this->getCasualtiesReductionVersusLand($dominion, $target, $slot, 'offense');

            // Spells
            # 1. Attacker's general casualty modifier
            # 2. Attacker's specifically offensive casualties modifier
            # 3. Target's general casualty modifier for invader
            # 4. Target's general specific defensive modifier for invader
            $multiplier += $dominion->getSpellPerkMultiplier('casualties');
            $multiplier += $dominion->getSpellPerkMultiplier('offensive_casualties');
            $multiplier += $target->getSpellPerkMultiplier('increases_casualties');
            $multiplier += $target->getSpellPerkMultiplier('increases_casualties_on_defense');

            # Invasion Spell: Unhealing Wounds
            if ($this->spellCalculator->isSpellActive($dominion, 'festering_wounds'))
            {
                $multiplier += 0.50 * $this->spellDamageCalculator->getDominionHarmfulSpellDamageModifier($dominion, null, 'festering_wounds', null);
            }

            // Techs
            $multiplier -= $dominion->getTechPerkMultiplier('fewer_casualties_offense');

            // Techs
            if(isset($dominion->title))
            {
                $multiplier += $dominion->title->getPerkMultiplier('casualties') * $dominion->title->getPerkBonus($dominion);
            }

            # Infirmary
            $multiplier -= $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'infirmary');

            // Unit bonuses (multiplicative with non-unit bonuses)

            # Unit Perk: Fewer Casualties
            $multiplier -= ($dominion->race->getUnitPerkValueForUnitSlot($slot, ['fewer_casualties', 'fewer_casualties_offense']) / 100);

            # Unit Perk: Reduces or increases casualties.
            $unitCasualtiesPerk = $this->getUnitCasualtiesPerk($dominion, $target, $units, $landRatio, 'offensive', $isAmbush);

            $multiplier += $unitCasualtiesPerk['defender']['increases_casualties_on_defense'];
            $multiplier -= $unitCasualtiesPerk['attacker']['reduces_casualties'];

            // Absolute cap at 90% reduction.
            $multiplier = max(0.10, $multiplier);
        }

        # END CHECK UNIT AND RACIAL CASUALTY MODIFIERS

        return $multiplier;
    }

    /**
     * Get the defensive casualty multiplier for a dominion for a specific unit
     * slot.
     *
     * @param Dominion $dominion
     * @param Dominion $attacker
     * @param int|null $slot Null is for non-racial units and thus used as draftees casualties multiplier
     * @return float
     */
    public function getDefensiveCasualtiesMultiplierForUnitSlot(Dominion $dominion, Dominion $attacker, ?int $slot, array $units, float $landRatio, bool $isAmbush, bool $isInvasionSuccessful): float
    {
        $multiplier = 1;

        // First check immortality, so we can skip the other remaining checks if we indeed have immortal units, since
        // casualties will then always be 0 anyway

        // Only military units with a slot number could be immortal
        if ($slot !== null)
        {
            // Global immortality
            if ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal'))
            {
                if (!$this->spellCalculator->getPassiveSpellPerkMultiplier($attacker, 'can_kill_immortal'))
                {
                    $multiplier = 0;
                }
            }
            if ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'spirit_immortal') and !$isInvasionSuccessful)
            {
                $multiplier = 0;
            }
            if ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'true_immortal'))
            {
                // Note: true_immortal is used for non-SPUD races to be exempt from Divine Intervention.
                $multiplier = 0;
            }

            // Race perk-based immortality
            if (($multiplier !== 1) && $this->isImmortalVersusRacePerk($dominion, $attacker, $slot))
            {
                $multiplier = 0;
            }

            // Perk: immortal on victory
            if ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal_on_fending_off') and !$isInvasionSuccessful)
            {
                $multiplier = 0;
            }

        }

        // If you are fighting against a does_not_kill race (Lux)
        # This means that Defensive CASUALTIES are zero when INVADED BY a Lux.
        if($attacker->race->getPerkValue('does_not_kill') == 1)
        {
            $multiplier = 0;
        }

        # CHECK ONLY DIES VS X RAW POWER
        if(isset($slot))
        {
          if ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'only_dies_vs_raw_power') !== 0)
          {
              $minPowerToKill = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'only_dies_vs_raw_power');
              $opFromUnitsThatKill = 0;

              # Get the raw OP of each unit of $attacker.
              foreach ($attacker->race->units as $unit)
              {
                  if(isset($units[$unit->slot]))
                  {
                    # If the raw OP on the unit is enough, add it to $opFromUnitsThatKill.
                    if($this->militaryCalculator->getUnitPowerWithPerks($attacker, $dominion, $landRatio, $unit, 'offense') >= $minPowerToKill)
                    {
                      $opFromUnitsThatKill += $this->militaryCalculator->getUnitPowerWithPerks($attacker, $dominion, $landRatio, $unit, 'offense') * $units[$unit->slot];
                    }
                  }
              }

              # How much of the DP is from units that kill?
              $opFromUnitsThatKillRatio = $opFromUnitsThatKill / $this->militaryCalculator->getOffensivePowerRaw($attacker, );

              $multiplier = $opFromUnitsThatKillRatio;
          }
        }

        # END CHECK ONLY DIES VS X RAW POWER

        if ($multiplier != 0)
        {
            // Spells
            $multiplier += $this->spellCalculator->getPassiveSpellPerkMultiplier($dominion, 'casualties');

            # 1. Target's general casualty modifier
            # 2. Target's specifically offensive casualties modifier
            # 3. Attacker's general casualty modifier for invader
            # 4. Attacker's general specific defensive modifier for invader
            $multiplier += $dominion->getSpellPerkMultiplier('casualties');
            $multiplier += $dominion->getSpellPerkMultiplier('defensive_casualties');
            $multiplier += $attacker->getSpellPerkMultiplier('increases_casualties');
            $multiplier += $attacker->getSpellPerkMultiplier('increases_casualties_on_offense');

            # Invasion Spell: Unhealing Wounds
            if ($this->spellCalculator->isSpellActive($dominion, 'festering_wounds'))
            {
                $multiplier += 0.50 * $this->spellDamageCalculator->getDominionHarmfulSpellDamageModifier($dominion, null, 'festering_wounds', null);
            }

            # Land-based reductions
            $multiplier -= $this->getCasualtiesReductionFromLand($dominion, $slot, 'defense');
            #$multiplier -= $this->getCasualtiesReductionVersusLand($dominion, $target, $slot, 'defense'); -- Doesn't make sense in this context (attacker has no defensive casualties).

            // Buildings
            $multiplier -= $dominion->getBuildingPerkMultiplier('defensive_casualties');

            // Techs
            $multiplier -= $dominion->getTechPerkMultiplier('fewer_casualties_defense');

            // Title
            if(isset($dominion->title))
            {
                $multiplier += $dominion->title->getPerkMultiplier('casualties') * $dominion->title->getPerkBonus($dominion);
            }

            // Infirmary
            $multiplier -= $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'infirmary');

            # Unit bonuses
            // Unit Perk: Fewer Casualties (only on military units with a slot, draftees don't have this perk)
            if ($slot)
            {
                $multiplier -= ($dominion->race->getUnitPerkValueForUnitSlot($slot, ['fewer_casualties', 'fewer_casualties_defense']) / 100);
            }

            # Unit Perk: Reduces or increases casualties.
            $unitCasualtiesPerk = $this->getUnitCasualtiesPerk($attacker, $dominion, $units, $landRatio, 'defensive', $isAmbush);

            $multiplier += $unitCasualtiesPerk['attacker']['increases_casualties_on_offense'];
            $multiplier -= $unitCasualtiesPerk['defender']['reduces_casualties'];

            // Absolute cap at 90% reduction.
            $multiplier = max(0.10, $multiplier);
        }
        return $multiplier;
    }

    /**
     * Returns the Dominion's casualties by unit type.
     *
     * @param  Dominion $dominion
     * @param int $foodDeficit
     * @return array
     */
    public function getStarvationCasualtiesByUnitType(Dominion $dominion, int $foodDeficit): array
    {
        $units = $this->getStarvationUnitTypes();

        $totalCasualties = $this->getTotalStarvationCasualties($dominion, $foodDeficit);

        if ($totalCasualties === 0) {
            return [];
        }

        $peasantPopPercentage = $dominion->peasants / $this->populationCalculator->getPopulation($dominion);
        $casualties = ['peasants' => min($totalCasualties * $peasantPopPercentage, $dominion->peasants)];
        $casualties += array_fill_keys($units, 0);

        $remainingCasualties = ($totalCasualties - array_sum($casualties));
        $totalMilitaryCasualties = $remainingCasualties;

        foreach($units as $unit) {
            if($remainingCasualties == 0) {
                break;
            }

            $slotTotal = $dominion->{$unit};

            if($slotTotal == 0) {
                continue;
            }

            $slotLostMultiplier = $slotTotal / $totalMilitaryCasualties;

            $slotLost = ceil($slotTotal * $slotLostMultiplier);

            if($slotLost > $remainingCasualties) {
                $slotLost = $remainingCasualties;
            }

            $casualties[$unit] += $slotLost;
            $remainingCasualties -= $slotLost;
        }

        if ($remainingCasualties > 0) {
            $casualties['peasants'] = (int)min(
                ($remainingCasualties + $casualties['peasants']),
                $dominion->peasants
            );
        }

        $casualties = array(
          'peasants' => 0,
          'unit1' => 0,
          'unit2' => 0,
          'unit3' => 0,
          'unit4' => 0,
          'spies' => 0,
          'wizards' => 0,
          'archmage' => 0
        );

      return array_filter($casualties);
    }

    /**
     * Returns the Dominion's number of casualties due to starvation.
     *
     * @param  Dominion $dominion
     * @param int $foodDeficit
     * @return int
     */
    public function getTotalStarvationCasualties(Dominion $dominion, int $foodDeficit): int
    {
        if ($foodDeficit >= 0) {
            return 0;
        }

        $casualties = (int)(abs($foodDeficit) * 2);
        $maxCasualties = $this->populationCalculator->getPopulation($dominion) * 0.02;

        return min($casualties, $maxCasualties);
    }

    /**
     * Returns the unit types that can suffer casualties.
     *
     * @return array
     */
    protected function getStarvationUnitTypes(): array
    {
        return array_merge(
            array_map(
                function ($unit) {
                    return ('military_' . $unit);
                },
                $this->unitHelper->getUnitTypes()
            ),
            ['military_draftees']
        );
    }

    /**
     * @param Dominion $dominion
     * @param Dominion $target
     * @param int $slot
     * @return bool
     */
    protected function isImmortalVersusRacePerk(Dominion $dominion, Dominion $target, int $slot): bool
    {

        # Question: is military_unit$slot of $dominion immortal against $target?

        $raceNotImmortalAgainst = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal_except_vs');
        $raceNotImmortalAgainst = strtolower($raceNotImmortalAgainst);
        $raceNotImmortalAgainst = str_replace(' ', '_', $raceNotImmortalAgainst);

        $targetRace = $target->race->name;
        $targetRace = strtolower($targetRace);
        $targetRace = str_replace(' ', '_', $targetRace);

        if($targetRace == $raceNotImmortalAgainst or !$raceNotImmortalAgainst)
        {
          return False;
        }
        else
        {
          return True;
        }
/*
        $raceNameFormatted = strtolower($target->race->name);
        $raceNameFormatted = str_replace(' ', '_', $raceNameFormatted);

        $perkValue = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal_except_vs');

        if(!$perkValue)
        {
            return false;
        }
*/
        return $perkValue !== $raceNameFormatted;
    }


      /**
       * @param Dominion $dominion
       * @param Unit $unit
       * @return float
       */
      protected function getCasualtiesReductionFromLand(Dominion $dominion, int $slot = NULL, string $powerType): float
      {
        if ($slot == NULL)
        {
            return 0;
        }

        $landPerkData = $dominion->race->getUnitPerkValueForUnitSlot($slot, "fewer_casualties_{$powerType}_from_land", null);

        if (!$landPerkData)
        {
            return 0;
        }

        $landType = $landPerkData[0];
        $ratio = (float)$landPerkData[1];
        $max = (float)$landPerkData[2];

        $totalLand = $this->landCalculator->getTotalLand($dominion);
        $landPercentage = ($dominion->{"land_{$landType}"} / $totalLand) * 100;

        $powerFromLand = $landPercentage / $ratio;
        $powerFromPerk = min($powerFromLand, $max)/100;

        return $powerFromPerk;
      }


      protected function getCasualtiesReductionVersusLand(Dominion $dominion, Dominion $target, int $slot = NULL, string $powerType): float
      {
          if ($target === null or $slot == NULL)
          {
              return 0;
          }

          $versusLandPerkData = $dominion->race->getUnitPerkValueForUnitSlot($slot, "fewer_casualties_{$powerType}_vs_land", null);

          if(!$versusLandPerkData)
          {
              return 0;
          }

          $landType = $versusLandPerkData[0];
          $ratio = (float)$versusLandPerkData[1];
          $max = (float)$versusLandPerkData[2];

          $totalLand = $this->landCalculator->getTotalLand($target);
          $landPercentage = ($target->{"land_{$landType}"} / $totalLand) * 100;

          $powerFromLand = $landPercentage / $ratio;

          $powerFromPerk = min($powerFromLand, $max)/100;

          return $powerFromPerk;
      }
      

      /**
      *   Calculates reduces_casualties or increases_casualties.
      *   reduces_casualties: lowers casualties of all friendly units participating in the battle. ([Perk Units]/[All Units])/2
      *   increases_casualties: increases casualties of enemy units participating in the battle. ([Perk Units]/[All Units])/4
      *   $mode is either OFFENSIVE or DEFENSIVE
      **/
      protected function getUnitCasualtiesPerk(Dominion $attacker, Dominion $defender, array $units, float $landRatio, string $mode, bool $isAmbush): array
      {

          $rawOpFromSentUnits = $this->militaryCalculator->getOffensivePowerRaw($attacker, $defender, $landRatio, $units, []);
          $rawDpFromHomeUnits = $this->militaryCalculator->getDefensivePowerRaw($defender, $attacker, $landRatio, null, 0, false, $isAmbush, false);
          $defenderUnitsHome = ($defender->military_unit1 + $defender->military_unit2 + $defender->military_unit3 + $defender->military_unit4);

          $unitCasualtiesPerk['attacker']['increases_casualties_on_offense'] = 0;
          $unitCasualtiesPerk['attacker']['reduces_casualties'] = 0;

          $unitCasualtiesPerk['defender']['increases_casualties_on_defense'] = 0;
          $unitCasualtiesPerk['defender']['reduces_casualties'] = 0;

          # Check if attacker has increases_casualties or reduces_casualties
          foreach($units as $slot => $amount)
          {
              if($increasesCasualties = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'increases_casualties_on_offense'))
              {
                  $unitCasualtiesPerk['attacker']['increases_casualties_on_offense'] += $this->militaryCalculator->getOffensivePowerRaw($attacker, $defender, $landRatio, [$slot => $amount], []) / $rawOpFromSentUnits;
              }
              if($decreasesCasualties = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'reduces_casualties'))
              {
                  $unitCasualtiesPerk['attacker']['reduces_casualties'] += $amount / array_sum($units);
              }
          }

          # Check if defender has increases_casualties or reduces_casualties
          for ($slot = 1; $slot <= 4; $slot++)
          {
              if($increasesCasualties = $defender->race->getUnitPerkValueForUnitSlot($slot, 'increases_casualties_on_defense'))
              {
                  $unitCasualtiesPerk['defender']['increases_casualties_on_defense'] += $this->militaryCalculator->getDefensivePowerRaw($attacker, $defender, $landRatio, [$slot => $amount], 0, false, $isAmbush, false) / $rawDpFromHomeUnits;
              }
              if($decreasesCasualties = $defender->race->getUnitPerkValueForUnitSlot($slot, 'reduces_casualties'))
              {
                  $unitCasualtiesPerk['defender']['reduces_casualties'] += $defender->{'military_unit'.$slot} / $defenderUnitsHome;
              }
          }

          $unitCasualtiesPerk['attacker']['increases_casualties_on_offense'] /= 2;
          $unitCasualtiesPerk['attacker']['reduces_casualties'] /= 2;

          $unitCasualtiesPerk['defender']['increases_casualties_on_defense'] /= 2;
          $unitCasualtiesPerk['defender']['reduces_casualties'] /= 2;

          return $unitCasualtiesPerk;

      }


}
