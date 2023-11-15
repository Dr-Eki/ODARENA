<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use Illuminate\Support\Collection;

use OpenDominion\Helpers\SpellHelper;

use OpenDominion\Calculators\Dominion\MagicCalculator;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Spell;

class SpellCalculator
{

    /**
     * @var LandCalculator
     */
    protected $landCalculator;

    /**
     * @var MagicCalculator
     */
    protected $magicCalculator;

    /**
     * @var PopulationCalculator
     */
    protected $populationCalculator;

    /**
     * @var SpellHelper
     */
    protected $spellHelper;

    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->magicCalculator = app(MagicCalculator::class);
        $this->spellHelper = app(SpellHelper::class);
    }

    /**
     * Returns the mana cost of a particular spell for $dominion.
     *
     * @param Dominion $dominion
     * @param string $spell
     * @return int
     */
    public function getManaCost(Dominion $dominion, string $spellKey, bool $isInvasionSpell = false): int
    {
        if($isInvasionSpell)
        {
            return 0;
        }

        $spell = Spell::where('key',$spellKey)->first();

        if($spell->magic_level === 0)
        {
            return 0;
        }

        $totalLand = $dominion->land;

        $baseCost = $totalLand * $spell->cost;

        return round($baseCost * $this->getManaCostMultiplier($dominion));
    }

    public function getManaCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        $multiplier += $dominion->getBuildingPerkMultiplier('spell_cost');
        $multiplier += $dominion->getAdvancementPerkMultiplier('spell_costs');
        $multiplier += $dominion->getImprovementPerkMultiplier('spell_cost');
        $multiplier += $dominion->getDeityPerkMultiplier('spell_cost');
        $multiplier += $dominion->getSpellPerkMultiplier('spell_cost');

        #$multiplier += $dominion->getDecreePerkMultiplier('spell_cost_from_wizard_ratio') * $this->militaryCalculator->getWizardRatio($dominion);

        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('spell_cost') * $dominion->getTitlePerkMultiplier();
        }

        return max(0.1, $multiplier);
    }

    /**
     * Returns whether spell $type for $dominion is on cooldown.
     *
     * @param Dominion $dominion
     * @param Spell $spell
     * @param bool $isInvasionSpell
     * @return bool
     */
    public function isOnCooldown(Dominion $dominion, Spell $spell): bool
    {
        if ($this->getSpellCooldown($dominion, $spell) > 0)
        {
            return true;
        }
        return false;
    }

    /**
     * Returns the number of hours before spell $type for $dominion can be cast.
     *
     * @param Dominion $dominion
     * @param Spell $spell
     * @param bool $isInvasionSpell
     * @return bool
     */
    public function getSpellCooldown(Dominion $dominion, Spell $spell): int
    {
        if($dominionSpell = DominionSpell::where('dominion_id', $dominion->id)->where('spell_id', $spell->id)->first())
        {
            return $dominionSpell->cooldown;
        }

        return 0;
    }

    /**
     * Returns a list of spells currently affecting $dominion.
     *
     * @param Dominion $dominion
     * @param bool $forceRefresh
     * @return Collection
     */
    public function getActiveSpells(Dominion $dominion): Collection
    {
        return DominionSpell::where('caster_id',$dominion->id)->get();
    }


    public function getPassiveSpellsCastByDominion(Dominion $caster, string $scope)#: Collection
    {
        if($scope)
        {
            return collect(
                  DominionSpell::query()
                      ->join('spells', 'dominion_spells.spell_id','spells.id')
                      ->where('dominion_spells.caster_id',$caster->id)
                      ->where('spells.scope',$scope)
                      ->get()
            );
        }

        return collect(DominionSpell::where('dominion_id',$dominion->id)->get());
    }

    public function getPassiveSpellsCastOnDominion(Dominion $dominion, string $scope = null)#: Collection
    {

        if($scope)
        {
            return collect(
                  DominionSpell::query()
                      ->rightjoin('spells', 'dominion_spells.spell_id','spells.id')
                      ->where('dominion_spells.dominion_id',$dominion->id)
                      ->where('spells.scope',$scope)
                      ->get()
            );
        }


        return collect(DominionSpell::where('dominion_id',$dominion->id)->get());
    }

    /**
     * Returns whether a particular spell is affecting $dominion right now.
     *
     * @param Dominion $dominion
     * @param string $spell
     * @return bool
     */
    public function isSpellActive(Dominion $dominion, string $spellKey): bool
    {
        $spell = Spell::where('key', $spellKey)->first();

        if(!$spell)
        {
            return false;
        }

        return DominionSpell::where('spell_id',$spell->id)->where('dominion_id',$dominion->id)->where('duration','>',0)->first() ? true : false;
    }

    public function isSpellCooldownRecentlyReset(Dominion $dominion, string $spellKey): bool
    {
        $spell = Spell::where('key', $spellKey)->first();
        return DominionSpell::where('spell_id',$spell->id)->where('dominion_id',$dominion->id)->where('cooldown','=',0)->first() ? true : false;
    }

    /**
     * Returns the remaining duration (in ticks) of a spell affecting $dominion.
     *
     * @todo Rename to getSpellRemainingDuration for clarity
     * @param Dominion $dominion
     * @param string $spell
     * @return int|null
     */
    public function getSpellDuration(Dominion $dominion, string $spellKey): ?int
    {
        if (!$this->isSpellActive($dominion, $spellKey))
        {
            return null;
        }

        $spell = Spell::where('key', $spellKey)->first();
        $dominionSpell = DominionSpell::where('spell_id',$spell->id)->where('dominion_id',$dominion->id)->first();

        return $dominionSpell->duration;
    }

    public function getPassiveSpellPerkValues(Dominion $dominion, string $perkString): array
    {

        $perkValuesFromSpells = [];

        # Get all active spells.
        $activeSpells = $this->getActiveSpells($dominion);

        # Check each spell for the $perk
        foreach($activeSpells as $spell)
        {
            #$perkValuesFromSpells[] = $spell->getPerkValue($perkString);
        }

        return $perkValuesFromSpells;
    }

    public function getPassiveSpellPerkValue(Dominion $dominion, string $perk): float
    {
        $perkValuesFromSpells = $this->getPassiveSpellPerkValues($dominion, $perk);
        return array_sum($perkValuesFromSpells);
    }

    public function getPassiveSpellPerkMultiplier(Dominion $dominion, string $perk): float
    {
        return $this->getPassiveSpellPerkValue($dominion, $perk) / 100;
    }

    public function isSpellAvailableToDominion(Dominion $dominion, Spell $spell): bool
    {
        if($this->spellHelper->isSpellAvailableToRace($dominion->race, $spell))
        {
            if(isset($spell->deity))
            {
                if($dominion->hasDeity())
                {
                    return ($dominion->deity->id == $spell->deity->id);
                }
                
                return false;
            }

            return true;
        }

        return false;
    }

    public function canCastSpell(Dominion $dominion, Spell $spell, ?int $manaOwned = NULL): bool
    {
        if($spell->class === 'invasion')
        {
            return true;
        }

        # This way because calling the resource calculator here breaks the resource calculator (circular reference).
        if(isset($manaOwned) and ($this->getManaCost($dominion, $spell->key) > $manaOwned) or ($manaOwned == 0 and $this->getManaCost($dominion, $spell->key) > 0))
        {
            return false;
        }

        # Check that dominion magic level is greater than or equal to the spell level
        if($this->magicCalculator->getMagicLevel($dominion) < $spell->level)
        {
            return false;
        }

        if(
            # Cannot be on cooldown
            $this->isOnCooldown($dominion, $spell)

            # Cannot cast disabled spells
            or $spell->enabled !== 1

            # Cannot cost more WS than the dominion has
            or ($dominion->wizard_strength - $this->getWizardStrengthCost($spell)) < 0

            # Must be available to the dominion's faction (race)
            or !$this->isSpellAvailableToDominion($dominion, $spell)

            # Round must have started for info ops to be castable
            or (!$dominion->round->hasStarted() and $spell->class == 'info')

            # Dominion must not be in protection
            or ($dominion->isUnderProtection() and $spell->scope !== 'self')
          )
        {
            return false;
        }
        return true;
    }

    public function getWizardStrengthCost(Spell $spell)
    {

        if($spell->class === 'invasion')
        {
            return 0;
        }

        # Default values
        $scopeCost = [
                'hostile' => 2,
                'friendly' => 2,
                'self' => 2,
                'artefact' => 4,
            ];
        $classCost = [
                'active' => 3,
                'info' => -1,
                'passive' => 2,
            ];

        $cost = $scopeCost[$spell->scope] + $classCost[$spell->class];

        return $spell->wizard_strength ?? $cost;
    }

    public function getCaster(Dominion $target, string $spellKey): Dominion
    {
        if (!$this->isSpellActive($target, $spellKey))
        {
            return null;
        }

        $spell = Spell::where('key', $spellKey)->first();

        $dominionSpell = DominionSpell::where('spell_id',$spell->id)->where('dominion_id',$target->id)->first();
        return Dominion::findorfail($dominionSpell->caster_id);
    }

    public function getAnnexedDominions(Dominion $legion): Collection
    {
        $spell = Spell::where('key', 'annexation')->first();
        $annexedDominions = collect();

        foreach(DominionSpell::where('caster_id',$legion->id)->where('spell_id', $spell->id)->get() as $dominionSpell)
        {
            $annexedDominions->prepend(Dominion::findorfail($dominionSpell->dominion_id));
        }

        return $annexedDominions;
    }

    public function hasAnnexedDominions(Dominion $legion): bool
    {
        $spell = Spell::where('key', 'annexation')->first();
        return DominionSpell::where('caster_id',$legion->id)->where('spell_id', $spell->id)->first() ? true : false;
    }

    public function isAnnexed(Dominion $dominion): bool
    {
        $spell = Spell::where('key', 'annexation')->first();
        return DominionSpell::where('dominion_id',$dominion->id)->where('spell_id', $spell->id)->first() ? true : false;
    }

    public function getAnnexer(Dominion $dominion): Dominion
    {
        $spell = Spell::where('key', 'annexation')->first();
        $dominionSpell = DominionSpell::where('dominion_id',$dominion->id)->where('spell_id', $spell->id)->first();
        return Dominion::findorfail($dominionSpell->caster_id);
    }

    public function getTicksRemainingOfAnnexation(Dominion $legion, Dominion $dominion): int
    {
        $spell = Spell::where('key', 'annexation')->first();
        return DominionSpell::where('caster_id',$legion->id)->where('dominion_id',$dominion->id)->where('spell_id', $spell->id)->first()->duration;

    }

    public function getWizardStrengthBase(Dominion $dominion): int
    {
        $base = 100;

        $base += $dominion->realm->getArtefactPerkValue('base_wizard_strength');

        return $base;
    }

    public function getWizardStrengthRecoveryAmount(Dominion $dominion): int
    {
        $amount = 4;

        $amount += $dominion->getBuildingPerkValue('wizard_strength_recovery');
        $amount += $dominion->getAdvancementPerkValue('wizard_strength_recovery');
        $amount += $dominion->getSpellPerkValue('wizard_strength_recovery');
        $amount += $dominion->title->getPerkValue('wizard_strength_recovery') * $dominion->getTitlePerkMultiplier();

        $amount = floor($amount);

        return $amount;
    }

    public function getWizardStrengthRecoveryMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        $multiplier += $dominion->realm->getArtefactPerkValue('wizard_strength_recovery_mod');

        foreach($dominion->race->units as $unit)
        {
            $multiplier += $unit->getPerkValue('wizard_strength_recovery_mod');
        }

        return $multiplier;
    }

}
