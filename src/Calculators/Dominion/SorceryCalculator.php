<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Spell;

use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
#use OpenDominion\Calculators\Dominion\SpellDamageCalculator;

class SorceryCalculator
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
        $this->spellCalculator = app(SpellCalculator::class);
        #$this->spellDamageCalculator = app(SpellDamageCalculator::class);
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
        $multiplier += clamp((($casterWpa - $targetWpa) / $casterWpa), 0, 1.5);
        $multiplier *= 2;

        return $multiplier;
    }

    public function getSorceryDamageDealtMultiplier(Dominion $caster): float
    {
        $multiplier = 1;
        $multiplier += $caster->getDecreePerkMultiplier('sorcery_damage_dealt_from_wizard_ratio') * $this->militaryCalculator->getWizardRatio($caster, 'offense');

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
    

}
