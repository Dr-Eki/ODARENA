<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Spell;

use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;

class SpellDamageCalculator
{
    /**
     * SpellCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     * @param SpellHelper $spellHelper
     */
    public function __construct()
    {
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
    }

    /*
    *   The base damage a spell does is determined by comparing the ratios of caster and target.
    *   If the caster has higher ratio than the target, it does more damage.
    *   The multiplier is capped between 0 (if target WPA > caster WPA) and 1 (if delta is greater than 10).
    *
    */
    public function getSpellBaseDamageMultiplier(Dominion $caster, Dominion $target): float
    {

        $casterWpa = $this->militaryCalculator->getWizardRatio($caster, 'offense');
        $targetWpa = $this->militaryCalculator->getWizardRatio($target, 'defense');

        $multiplier = 1;

        $multiplier += (max(($casterWpa - $targetWpa), 0) / 10);

        return max(0, $multiplier);
    }

    public function getDominionHarmfulSpellDamageModifier(Dominion $target, ?Dominion $caster, ?Spell $spell, ?string $attribute): float
    {
          $modifier = 1;

          // Improvements
          $modifier += $target->getImprovementPerkMultiplier('spell_damage');

          # Spell
          $modifier += $target->getSpellPerkMultiplier('damage_from_spells');

          // Advancement — unused
          $modifier += $target->getTechPerkMultiplier('damage_from_spells');

          if(isset($spell))
          {
              ## Insect Swarm
              if($spell->key == 'insect_swarm')
              {
                  $modifier += $target->race->getPerkMultiplier('damage_from_insect_swarm');
              }

              ## Fireballs: peasants and food
              if($spell->key == 'fireball' or $spell->key == 'pyroclast')
              {
                  if($target->race->getPerkMultiplier('damage_from_fireballs'))
                  {
                      $modifier += $target->race->getPerkMultiplier('damage_from_fireballs');
                  }

                  if($attribute == 'peasants')
                  {
                      $modifier += $target->getBuildingPerkMultiplier('fireball_damage');
                  }
              }

              ## Lightning Bolts: improvements
              if($spell->key == 'lightning_bolt')
              {
                  # General bolt damage modification.
                  $modifier += $target->race->getPerkMultiplier('damage_from_lightning_bolts');
                  $modifier -= $target->getBuildingPerkMultiplier('lightning_bolt_damage');
              }

              ## Disband Spies: spies
              if($spell->key == 'disband_spies')
              {
                  if ($target->race->getPerkValue('immortal_spies'))
                  {
                      $modifier = -1;
                  }
              }

              ## Purification: only effective against Afflicted.
              if($spell->key == 'purification')
              {
                  if($target->race->name !== 'Afflicted')
                  {
                      $modifier = -1;
                  }
              }

              ## Solar Flare: only effective against Nox.
              if($spell->key == 'solar_flare')
              {
                  if($target->race->name !== 'Nox')
                  {
                      $modifier = -1;
                  }
              }

          }

          return max(0, $modifier);
    }

}
