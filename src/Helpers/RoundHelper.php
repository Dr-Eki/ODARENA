<?php

namespace OpenDominion\Helpers;

use DB;
use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;

use OpenDominion\Calculators\Dominion\LandCalculator;

use OpenDominion\Services\Dominion\StatsService;

class RoundHelper
{
    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->statsService = app(StatsService::class);
    }

    public function getRoundModes(): array
    {
        return ['standard', 'standard-duration', 'deathmatch', 'deathmatch-duration', 'artefacts'];
    }

    public function getRoundModeString(Round $round = null, string $roundModeKey = null, bool $detailed = false): string
    {

        $roundModeKey = $round ? $round->mode : $roundModeKey;

        $roundModeString = '';

        switch ($roundModeKey)
        {
            case 'standard':
            case 'standard-duration':
                $roundModeString = 'Standard';
                break;

            case 'deathmatch':
            case 'deathmatch-duration':
                $roundModeString = 'Deathmatch';
                break;

            case 'artefacts':
                $roundModeString = 'Artefacts';
                break;
        }

        if($detailed)
        {
            switch ($roundModeKey)
            {
                case 'standard':
                case 'deathmatch':
                    $roundModeString .= ' (land target)';
    
                    break;
                case 'standard-duration':
                case 'deathmatch-duration':
                    $roundModeString .= ' (fixed duration)';
                    break;
        
                case 'artefacts':
                    $roundModeString .= '';
                    break;
            }
        }

        return $roundModeString;

    }

    public function getRoundModeGoalString(Round $round = null, string $roundModeKey = null): string
    {

        $roundModeKey = $round ? $round->mode : $roundModeKey;

        switch ($roundModeKey)
        {
            case 'standard':
                return 'land';

            case 'standard-duration':
                return 'ticks';

            case 'deathmatch':
                return 'land';

            case 'deathmatch-duration':
                return 'ticks';

            case 'artefacts':
                return 'artefacts';
        }
    }


    public function getRoundCountdownTickLength(): int
    {
        return 48;
    }

    public function getRoundModeDescription(Round $round = null, string $roundModeKey = null): string
    {

        $roundModeKey = $round ? $round->mode : $roundModeKey;

        switch ($roundModeKey)
        {
            case 'standard':
                return 'Your dominion is in a realm with friendly dominions fighting against all other realms to become the largest dominion.';

            case 'standard-duration':
                return 'Your dominion is in a realm with friendly dominions fighting against all other realms to become the largest dominion.';

            case 'deathmatch':
                return 'Every dominion for itself!';

            case 'deathmatch-duration':
                return 'Every dominion for itself!';

            case 'artefacts':
                return 'Your dominion is in a realm with friendly dominions and the goal is to be the first realm to capture at least the required number of artefacts.';
        }
    }

    public function getRoundModeIcon(Round $round = null, string $roundModeKey = null): string
    {

        $roundModeKey = $round ? $round->mode : $roundModeKey;

        switch ($roundModeKey)
        {
            case 'standard':
            case 'standard-duration':
                return '<i class="fas fa-users fa-fw text-green"></i>';

            case 'deathmatch':
            case 'deathmatch-duration':
                return '<i class="ra ra-daggers ra-fw text-red"></i>';

            case 'artefacts':
                return '<i class="ra ra-alien-fire text-orange"></i>';

            default:
                return '&mdash;';
        }
    }

    public function getRoundDominions(Round $round, bool $inclueActiveRounds = false, bool $excludeBarbarians = false): Collection
    {
        $dominions = Dominion::where('round_id', $round->id)
                      ->where('is_locked','=',0)
                      ->where('protection_ticks','=',0)
                      ->get();

        if(!$inclueActiveRounds)
        {
            foreach($dominions as $key => $dominion)
            {
                if(!$dominion->round->hasEnded())
                {
                    $dominions->forget($key);
                }
            }
        }

        if($excludeBarbarians)
        {
            foreach($dominions as $key => $dominion)
            {
                if($dominion->race->name == 'Barbarian')
                {
                    $dominions->forget($key);
                }
            }
        }

        return $dominions;
    }

    public function getRoundDominionsByLand(Round $round, int $max = null): array
    {
        $dominions = [];

        foreach($this->getRoundDominions($round) as $dominion)
        {
            $dominions[$dominion->id] = $this->landCalculator->getTotalLand($dominion);
        }

        arsort($dominions);

        if($max)
        {
            $dominions = array_slice($dominions, 0, $max, true);
        }

        $rankedList = [];

        $rank = 1;
        foreach($dominions as $dominionId => $landSize)
        {
            $rankedList[$rank] = $dominionId;
            $rank++;
        }

        return $rankedList;
    }

    public function getDominionPlacementInRound(Dominion $dominion): int
    {
        $round = $dominion->round;
        $dominions = $this->getRoundDominionsByLand($round);

        return array_search($dominion->id, $dominions);
    }

    public function getRoundPlacementEmoji(int $placement): string
    {
        switch($placement)
        {
            case 1:
                return "🥇";
            case 2:
                return "🥈";
            case 3:
                return "🥉";
            default:
                return '';
        }
    }

}
