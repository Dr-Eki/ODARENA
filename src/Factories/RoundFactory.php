<?php

namespace OpenDominion\Factories;

use Carbon\Carbon;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;

class RoundFactory
{


    public function create(
        Carbon $startDate,
        string $gameMode,
        int $goal,
        RoundLeague $roundLeague,
        string $roundName,
        array $settings
    ): Round {
        $number = $this->getLastRoundNumber() + 1;

        if($number % 2 === 0)
        {
            $startDate = (clone $startDate)->addHours(16);
        }
        else
        {
            $startDate = (clone $startDate)->addHours(4);
        }

        $endTick = NULL;

        if(in_array($gameMode, ['standard-duration', 'deathmatch-duration', 'factions-duration', 'packs-duration']))
        {
            $endTick = $goal;
        }

        return Round::create([
            'round_league_id' => $roundLeague->id,
            'number' => $number,
            'name' => $roundName,
            'goal' => $goal,
            'ticks' => 0,
            'mode' => $gameMode,
            'start_date' => $startDate,
            'has_ended' => 0,
            'end_date' => NULL,
            'end_tick' => $endTick,
            'offensive_actions_prohibited_at' => NULL,
            'settings' => $settings,
        ]);
    }


    protected function getLastRoundNumber(): int
    {
        return Round::query()->max('number') ?? 0;
    }
}
