<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use Illuminate\Support\Collection;

use OpenDominion\Helpers\SpellHelper;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Spell;

class MagicCalculator
{

    public function __construct()
    {
        $this->spellHelper = app(SpellHelper::class);
    }

    public function getMagicLevel(Dominion $dominion): int
    {

        $magicLevel[] = $dominion->race->getPerkValue('magic_level');
        $magicLevel[] = $dominion->race->getAdvancementPerkValue('magic_level');
        $magicLevel[] = $dominion->race->getTechPerkValue('magic_level');

        $magicLevel = max($magicLevel);

        # Additive perks
        $magicLevel += $dominion->getSpellPerkMultiplier('magic_level_extra');
        $magicLevel = $dominion->getTechPerkValue('magic_level_extra');
        $magicLevel = $dominion->getAdvancementPerkValue('magic_level_extra');

        return $magicLevel;
    }


}
