<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;

use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;

class RealmHelper
{
    public function __construct()
    {
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->statsService = app(StatsService::class);
    }

    public function getAlignmentNoun(string $alignment): string
    {
        if($race = Race::where('key', $alignment)->first())
        {
            return $race->name;
        }

        switch($alignment)
        {
            case 'good':
                return 'Commonwealth';
            case 'evil':
                return 'Empire';
            case 'independent':
                return 'Independent';
            case 'npc':
                return 'Barbarian';
            default:
                return $alignment;
        }            
    }

    public function getAlignmentAdjective(string $alignment)
    {
        if($alignment === 'independent')
        {
            return 'Independent';
        }
        else
        {
            return $this->getAlignmentNoun($alignment);
        }
    }

    public function getAlignmentCouncilTerm(string $alignment)
    {
        if($alignment === 'good')
        {
            return 'Parliament';
        }
        elseif($alignment === 'evil')
        {
            return 'Senate';
        }
        elseif($alignment === 'independent')
        {
            return 'Assembly';
        }
        elseif($alignment === 'npc')
        {
            return 'Gathering';
        }
        
        return 'Council';
    }

    public function getRealmPackName($realm): string
    {
        if(!in_array($realm->round->mode,['packs', 'packs-duration']) or $realm->alignment == 'npc')
        {
            return $this->getAlignmentAdjective($realm->alignment);
        }

        $user = $realm->pack->user;

        return $user->display_name . ($user->display_name[strlen($user->display_name) - 1] == 's' ? "'" : "'s" ) . ' Pack';
    }

    public function getDominionHelpString(Dominion $dominion, Dominion $viewer): string
    {
        $isViewerFriendly = ($dominion->realm->id == $viewer->realm->id);
        if($viewer->round->mode == 'deathmatch' or $viewer->round->mode == 'deathmatch-duration')
        {
            $isViewerFriendly = false;
        }

        $isBarbarian = ($dominion->race->name == 'Barbarian');

        if($isViewerFriendly)
        {
            $string = sprintf(
                '<small class="text-muted">Ruler:</small> <em>%s</em> %s<br>
                <small class="text-muted">Morale:</small> %s<br>
                <small class="text-muted">DP:</small> %s',
                $dominion->title->name,
                $dominion->ruler_name,
                $dominion->morale,
                ($dominion->getSpellPerkValue('fog_of_war') or ($dominion->hasProtector() and $dominion->protector->getSpellPerkValue('fog_of_war')))? 'Unknown due to Sazal\'s Fog' : number_format($this->militaryCalculator->getDefensivePower($dominion))
              );
        }
        elseif($isBarbarian)
        {
            $string = sprintf(
                '<small class="text-muted">Ruler:</small> <em>%s</em> %s<br>
                <small class="text-muted">NPC modifier:</small> %s<br>
                <small class="text-muted">Times invaded:</small> %s',
                $dominion->title->name,
                $dominion->ruler_name,
                number_format($dominion->npc_modifier),
                number_format($this->statsService->getStat($dominion, 'defense_failures'))
              );
        }
        else
        {
            if($dominion->hasProtector())
            {
                $string = sprintf(
                    '<small class="text-muted">Ruler:</small> <em>%s</em> %s<br>
                    <small class="text-muted">Protector:</small> %s',
                    $dominion->title->name,
                    $dominion->ruler_name,
                    $dominion->protector->name
                  );
            }
            else
            {
                $string = sprintf(
                    '<small class="text-muted">Ruler:</small> <em>%s</em> %s',
                    $dominion->title->name,
                    $dominion->ruler_name
                  );
            }
        }

        return $string;
    }

}
