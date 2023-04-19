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

        return Spell::where('enabled', 1)
        ->where('scope', 'self')
        ->where('magic_level', $level)
        ->get()
        ->filter(function ($spell) use ($dominion) {
            Log::debug('Checking spell', ['spell' => $spell->name, 'exclusive_races' => $spell->exclusive_races]);
    
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
    
            return true;
        });

    }

}
