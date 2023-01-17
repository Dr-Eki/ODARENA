<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Spell;

use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
#use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

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
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        #$this->populationCalculator = app(PopulationCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
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

    public function getDominionHarmfulSpellDamageModifier(Dominion $target, Dominion $caster = null, Spell $spell = null, string $attribute = null): float
    {
        $modifier = 1;

        // Improvements
        $modifier += $target->getImprovementPerkMultiplier('spell_damage');
        $modifier += $target->getImprovementPerkMultiplier('sorcery_damage_suffered');

        # Spell
        $modifier += $target->getSpellPerkMultiplier('damage_from_spells');
        $modifier += $target->getSpellPerkMultiplier('sorcery_damage_suffered');

        // Advancement — unused
        $modifier += $target->getAdvancementPerkMultiplier('damage_from_spells');

        for ($slot = 1; $slot <= $target->race->units->count(); $slot++)
        {
            if($reducesSpellDamagePerk = $target->race->getUnitPerkValueForUnitSlot($slot, 'reduces_spell_damage'))
            {
                $modifier -= ($this->militaryCalculator->getTotalUnitsForSlot($target, $slot) / $this->landCalculator->getTotalLand($target)) * $reducesSpellDamagePerk;
            }
        }

        #dump('Before spell specific checks: ' . $modifier);
        if(isset($spell))
        {
            $modifier += $target->race->getPerkMultiplier('damage_from_' . $spell->key);
            $modifier += $target->getBuildingPerkMultiplier('damage_from_' . $spell->key);
            $modifier += $target->getSpellPerkMultiplier('damage_from_' . $spell->key);

            ## Disband Spies: spies
            if($spell->key == 'disband_spies' and ($target->race->getPerkValue('immortal_spies') or $target->realm->getArtefactPerkMultiplier('immortal_spies')))
            {
                $modifier = -1;
            }

            ## Purification: only effective against Afflicted.
            if($spell->key == 'purification' and $target->race->name !== 'Afflicted')
            {
                $modifier = -1;
            }

            ## Solar Flare: only effective against Nox.
            if($spell->key == 'solar_rays' and $target->race->name !== 'Nox')
            {
                $modifier = -1;
            }
        }

        if($attribute == 'morale' and $target->race->getPerkValue('no_morale_changes'))
        {
            $modifier = -1;
        }


        return max(0, $modifier);
    }

}
