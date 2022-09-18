<?php

namespace OpenDominion\Helpers;

#use Illuminate\Support\Collection;
use OpenDominion\Models\Race;
#use OpenDominion\Models\Resource;
use OpenDominion\Models\Spell;

use OpenDominion\Calculators\Dominion\LandCalculator;

use OpenDominion\Services\Dominion\StatsService;

class SorceryHelper
{
    public function __construct()
    {
        $this->spellHelper = app(SpellHelper::class);

        $this->landCalculator = app(LandCalculator::class);

        $this->statsService = app(StatsService::class);
    }

    public function getSorcerySpellsForRace(Race $race)
    {
        $spells = Spell::all()->where('scope','hostile')->whereIn('class',['active','passive'])->where('enabled',1)->sortBy('name');

        foreach($spells as $key => $spell)
        {
            if(!$this->spellHelper->isSpellAvailableToRace($race, $spell))
            {
                $spells->forget($key);
            }
        }

        return $spells;
    }

    public function getSpellClassIcon(Spell $spell): string
    {
        if($spell->class == 'active')
        {
            return 'ra ra-bomb-explosion text-danger';
        }
        elseif($spell->class == 'passive')
        {
            return 'fas fa-hourglass-start text-info';
        }
    }

    public function getSpellClassDescription(Spell $spell): string
    {
        if($spell->class == 'active')
        {
            return 'This spell causes direct, immediate damage.';
        }
        elseif($spell->class == 'passive')
        {
            return 'This spell has a lingering effect.';
        }
    }

    public function getSpellClassBoxClass(Spell $spell): string
    {
        if($spell->class == 'active')
        {
            return 'box-danger';
        }
        elseif($spell->class == 'passive')
        {
            return 'box-info';
        }
    }

    public function getExclusivityString(Spell $spell): string
    {

        $exclusivityString = '<br><small class="text-muted">';

        if($exclusives = count($spell->exclusive_races))
        {
            foreach($spell->exclusive_races as $raceName)
            {
                $exclusivityString .= $raceName;
                if($exclusives > 1)
                {
                    $exclusivityString .= ', ';
                }
                $exclusives--;
            }

            $exclusivityString .= ' only';
        }
        elseif($excludes = count($spell->excluded_races))
        {
            $exclusivityString .= 'All except ';
            foreach($spell->excluded_races as $raceName)
            {
                $exclusivityString .= $raceName;
                if($excludes > 1)
                {
                    $exclusivityString .= ', ';
                }
                $excludes--;
            }
        }

        $exclusivityString .= '</small>';

        return $exclusivityString;

    }

    # BEGIN EVENT VIEW

    public function getPerkKeyHeader(string $perkKey): string
    {
        switch ($perkKey)
        {

            case 'destroy_resource':
                return 'Resource destroyed';

            case 'disband_spies':
                return 'Spies disbanded';

            case 'improvements_damage':
                return 'Improvements damage';

            case 'kill_peasants':
                return 'Peasants killed';

            case 'resource_theft':
                return 'Resource displacement';

            case 'duration':
                return 'Duration';

            default:
                return $perkKey;
        }
    }

    # END EVENT VIE

}
