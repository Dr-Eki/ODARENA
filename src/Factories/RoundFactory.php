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
        RoundLeague $league,
        Carbon $startDate,
        int $realmSize,
        int $packSize,
        int $playersPerRace,
        bool $mixedAlignment
    ): Round {
        $number = $this->getLastRoundNumber() + 1;
        $endDate = NULL;#(clone $startDate)->addDays(14);

        if($number % 2 === 0)
        {
            $startDate = (clone $startDate)->addHours(16);
            #$endDate = (clone $endDate)->addHours(16);
        }
        else
        {
            $startDate = (clone $startDate)->addHours(4);
            #$endDate = (clone $endDate)->addHours(4);
        }

        # End offensive actions between 180 and 360 minutes before round end
        $minutesBeforeRoundEnd = rand(180, 360);

        $offensiveActionsEndDate = NULL;#(clone $endDate)->subMinutes($minutesBeforeRoundEnd);

        return Round::create([
            'round_league_id' => $league->id,
            'number' => $number,
            'name' => 'Round ' . $number,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'offensive_actions_prohibited_at' => $offensiveActionsEndDate,
            'realm_size' => $realmSize,
            'pack_size' => $packSize,
            'players_per_race' => $playersPerRace,
            'mixed_alignment' => $mixedAlignment
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
