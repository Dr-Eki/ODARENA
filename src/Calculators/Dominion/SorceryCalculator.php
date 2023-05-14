<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Spell;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

class SorceryCalculator
{

    #private $improvementCalculator;
    private $landCalculator;
    private $militaryCalculator;
    private $spellCalculator;


    /**
     * SpellCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     * @param SpellHelper $spellHelper
     */
    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
    }

    public function canPerformSorcery(Dominion $caster): bool
    {
        if($caster->wizard_strength >= 4)
        {
            return true;
        }

        return false;
    }

    public function getSorcerySpellManaCost(Dominion $caster, Spell $spell, int $wizardStrength): int
    {
        $manaCost = $this->getManaCost($caster, $spell->key);

        return $manaCost * $wizardStrength;
    }

    public function getSorcerySpellDuration(Dominion $caster, Dominion $target, Spell $spell, int $wizardStrength, Resource $enhancementResource = null, int $enhancementAmount = 0): int
    {
        $duration = $spell->duration;

        $duration *= $wizardStrength;

        $multiplier = 1;
        $multiplier += $this->getSorcerySpellDamageMultiplier($caster, $target, $spell, $wizardStrength, $enhancementResource, $enhancementAmount) / 25;
        $multiplier += $caster->realm->getArtefactPerkMultiplier('sorcery_spell_duration');

        $duration *= $multiplier;

        $duration = floor($duration);

        $duration = min($duration, 96);

        return $duration;
    }

    public function getSorcerySpellDamageMultiplier(Dominion $caster, Dominion $target, Spell $spell, int $wizardStrength, Resource $enhancementResource = null, int $enhancementAmount = 0, string $perkKey = null): float
    {
        $multiplier = 1;

        $multiplier += $this->getSorceryDamageDealtMultiplier($caster, $target);

        $multiplier *= $this->getSorceryWizardStrengthMultiplier($wizardStrength);
        $multiplier *= $this->getSorceryWizardRatioMultiplier($caster, $target);

        return $multiplier;
    }

    public function getSorceryWizardStrengthMultiplier(int $wizardStrength): float
    {
        return max($wizardStrength, $wizardStrength * (exp($wizardStrength/120)-1));
    }

    public function getSorceryWizardRatioMultiplier(Dominion $caster, Dominion $target): float
    {
        $multiplier = 0;
        $casterWpa = $this->militaryCalculator->getWizardRatio($caster, 'offense');
        $targetWpa = $this->militaryCalculator->getWizardRatio($target, 'defense');

        if($casterWpa <= 0)
        {
            return 0;
        }
        if($targetWpa <= 0)
        {
            return 1.5;
        }
        $multiplier += min((($casterWpa - $targetWpa) / $casterWpa), 1.5);

        return $multiplier;
    }

    public function getSorceryDamageDealtMultiplier(Dominion $caster): float
    {
        $multiplier = 1;
        $multiplier += $caster->getDecreePerkMultiplier('sorcery_damage_dealt_from_wizard_ratio') * $this->militaryCalculator->getWizardRatio($caster, 'offense');
        $multiplier += $caster->getSpellPerkMultiplier('sorcery_damage_dealt');

        return $multiplier;
    }

    public function getManaCost(Dominion $dominion, string $spellKey, bool $isInvasionSpell = false): int
    {
        if($isInvasionSpell)
        {
            return 0;
        }

        $spell = Spell::where('key',$spellKey)->first();

        $totalLand = $this->landCalculator->getTotalLand($dominion);

        $baseCost = $totalLand * $spell->cost;

        return round($baseCost * $this->getManaCostMultiplier($dominion));
    }

    public function getManaCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        $multiplier += $dominion->getBuildingPerkMultiplier('sorcery_cost');
        $multiplier += $dominion->getAdvancementPerkMultiplier('sorcery_cost');
        $multiplier += $dominion->getImprovementPerkMultiplier('sorcery_cost');
        $multiplier += $dominion->getDeityPerkMultiplier('sorcery_cost');
        $multiplier += $dominion->getSpellPerkMultiplier('sorcery_cost');

        $multiplier += $dominion->getDecreePerkMultiplier('sorcery_cost_from_wizard_ratio') * $this->militaryCalculator->getWizardRatio($dominion);

        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('sorcery_cost') * $dominion->getTitlePerkMultiplier();
        }

        return max(0.1, $multiplier);
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
