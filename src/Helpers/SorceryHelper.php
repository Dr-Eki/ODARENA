<?php

namespace OpenDominion\Helpers;

#use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
#use OpenDominion\Models\Resource;
use OpenDominion\Models\Spell;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MagicCalculator;

use OpenDominion\Services\Dominion\StatsService;

class SorceryHelper
{

    protected $spellHelper;
    protected $magicCalculator;
    protected $landCalculator;
    protected $statsService;

    public function __construct()
    {
        $this->spellHelper = app(SpellHelper::class);
        $this->magicCalculator = app(MagicCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->statsService = app(StatsService::class);
    }

    public function getSorcerySpellsForDominion(Dominion $dominion)
    {

        return Spell::where('enabled', 1)
        ->where('magic_level', '<=', $this->magicCalculator->getMagicLevel($dominion))
        ->where('scope','hostile')
        ->whereIn('class',['active','passive'])
        ->orderBy('class', 'asc')
        ->orderBy('magic_level', 'asc')
        ->orderBy('name', 'asc')
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
    
            return true;
        });

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
