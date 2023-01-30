<?php

namespace OpenDominion\Factories;

use Carbon\Carbon;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;

class RoundFactory
{

    /**
     * Creates and returns a new Round in a RoundLeague.
     *
     * @param RoundLeague $league
     * @param Carbon $startDate
     * @param int $realmSize
     * @param int $packSize
     * @param int $playersPerRace
     * @param bool $mixedAlignment
     * @return Round
     */
    public function create(
        Carbon $startDate,
        string $gameMode,
        int $goal,
        RoundLeague $roundLeague,
        string $roundName
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

        if(in_array($gameMode, ['standard-duration', 'deathmatch-duration']))
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
            'end_date' => NULL,
            'end_tick' => $endTick,
            'offensive_actions_prohibited_at' => NULL
        ]);
    }

    /**
     * Returns the last round number in a round league.
     *
     * @param RoundLeague $league
     * @return int
     */
    protected function getLastRoundNumber(): int
    {
        $round = Round::query()->max('number');
        return $round ? $round : 0;
    }
}
