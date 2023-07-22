<?php

namespace OpenDominion\Calculators\Dominion;

use Log;

use Illuminate\Support\Collection;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Spell;

class MagicCalculator
{

    public function __construct()
    {
        #$this->spellHelper = app(SpellHelper::class);
    }

    public function getMagicLevel(Dominion $dominion): int
    {

        $magicLevel[] = $dominion->race->magic_level;
        $magicLevel[] = $dominion->getAdvancementPerkValue('magic_level');
        $magicLevel[] = $dominion->getTechPerkValue('magic_level');
        $magicLevel[] = $dominion->getDecreePerkValue('magic_level_extra');

        $magicLevel = max($magicLevel);

        # Additive perks
        $magicLevel += $dominion->getSpellPerkMultiplier('magic_level_extra');
        $magicLevel += $dominion->getTechPerkValue('magic_level_extra');
        $magicLevel += $dominion->getAdvancementPerkValue('magic_level_extra');
        $magicLevel += $dominion->getDecreePerkValue('magic_level_extra');

        return max(0, $magicLevel);
    }

    public function getLevelSpells(Dominion $dominion, int $level = 0)
    {

        $spells = Spell::where('enabled', 1)
        ->where('scope', 'self')
        ->where('magic_level', $level)
        ->get()
        ->filter(function ($spell) use ($dominion) {
            // Check excluded_races
            if (!empty($spell->excluded_races) && in_array($dominion->race->name, $spell->excluded_races))
            {
                return false;
            }
    
            // Check exclusive_races
            if (!empty($spell->exclusive_races) && !in_array($dominion->race->name, $spell->exclusive_races))
            {
                return false;
            }
    
            // Check deity
            if ($spell->deity && (!$dominion->hasDeity() or $spell->deity->key !== $dominion->deity->key))
            {
                return false;
            }
    
            return true;
        })
        ->sortBy('name');

        return $spells;

    }

    public function getSpells(Dominion $dominion)
    {
        $spells = new Collection();

        for ($i = 0; $i <= $this->getMagicLevel($dominion); $i++)
        {
            $spells = $spells->merge($this->getLevelSpells($dominion, $i));
        }

        return $spells;    
    }

}
