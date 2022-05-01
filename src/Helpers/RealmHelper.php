<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;

use OpenDominion\Services\Dominion\StatsService;

class RealmHelper
{
    public function __construct()
    {
        $this->statsService = app(StatsService::class);
    }

    public function getAlignmentNoun(string $alignment): string
    {
        if($alignment === 'good')
        {
            return 'Commonwealth';
        }
        elseif($alignment === 'evil')
        {
            return 'Empire';
        }
        elseif($alignment === 'independent')
        {
            return 'Independent';
        }
        elseif($alignment === 'npc')
        {
            return 'Barbarian';
        }
        else
        {
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
                <small class="text-muted">Morale:</small> %s%%',
                $dominion->title->name,
                $dominion->ruler_name,
                $dominion->morale
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
            $string = sprintf(
                '<small class="text-muted">Ruler:</small> <em>%s</em> %s',
                $dominion->title->name,
                $dominion->ruler_name
              );
        }

        return $string;
    }

}
