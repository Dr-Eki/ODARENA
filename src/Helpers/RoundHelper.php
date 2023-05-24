<?php

namespace OpenDominion\Helpers;

use DB;
use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
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
        return ['standard', 'standard-duration', 'deathmatch', 'deathmatch-duration', 'factions', 'factions-duration', 'artefacts'];
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

            case 'factions':
            case 'factions-duration':
                $roundModeString = 'Factions';
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
                case 'factions':
                    $roundModeString .= ' (land target)';
                    break;

                case 'standard-duration':
                case 'deathmatch-duration':
                case 'factions-duration':
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
            case 'deathmatch':
            case 'factions':
                    return 'land';

            case 'deathmatch-duration':
            case 'standard-duration':
            case 'factions-duration':
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
            case 'standard-duration':
                return 'Your dominion is in a realm with friendly dominions fighting against all other realms to become the largest dominion.';

            case 'deathmatch':
            case 'deathmatch-duration':
                return 'Every dominion for itself!';

            case 'factions':
            case 'factions-duration':
                return 'Dominions of the same factions fight against all other dominions.';

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

            case 'factions':
            case 'factions-duration':
                return '<i class="ra ra-crossed-swords ra-fw text-purple"></i>';

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

    public function getRoundRaces(Round $round): Collection
    {
        $races = Race::all()->where('playable', true);

        if(!in_array(request()->getHost(), ['sim.odarena.com', 'odarena.local', 'odarena.virtual']))
        {
            # For each race, check if round->mode is in race->round_modes, remove if not.
            foreach($races as $key => $race)
            {
                if(!in_array($round->mode, $race->round_modes))
                {
                    $races->forget($key);
                }
            }
        }

        return $races;
    }

    public function getRoundSettings(): array
    {
        return [
            'advancements' => 'Advancements',
            'barbarians' => 'Barbarians',
            'buildings' => 'Buildings',
            'decrees' => 'Decrees',
            'deities' => 'Deities',
            'expeditions' => 'Expeditions',
            'improvements' => 'Improvements',
            'research' => 'Research',
            'rezoning' => 'Rezoning',
            'invasions' => 'Invasions',
            'sabotage' => 'Sabotage',
            'spells' => 'Spells',
            'sorcery' => 'Sorcery',
            'theft' => 'Theft',
        ];
    }

    public function getRoundDefaultSettings(): array
    {
        $settings = [];

        foreach($this->getRoundSettings() as $key => $setting)
        {
            $settings[$key] = true;
        }

        return $settings;
    }

    public function getRoundDefaultSetting(string $setting): bool
    {
        $settings = $this->getRoundDefaultSettings();

        return $settings[$setting];
    }

}
