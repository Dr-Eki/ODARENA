<?php

namespace OpenDominion\Traits;

use RuntimeException;
use Carbon\Carbon;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\StatsService;

trait DominionGuardsTrait
{


    /**
     * Guards against locked Dominions.
     *
     * @param Dominion $dominion
     * @throws RuntimeException
     */
    public function guardLockedDominion(Dominion $dominion): void
    {
        if ($dominion->isLocked())
        {
            throw new RuntimeException("Dominion {$dominion->name} is locked");
        }
    }

    /**
     * Guards against actions during tick.
     *
     * @param Dominion $dominion
     * @param int $seconds
     * @throws GameException
     */
    public function guardActionsDuringTick(Dominion $dominion, int $seconds = 30): void
    {
        if (
            $dominion->protection_ticks > 0
            or $dominion->race->name == 'Barbarian'
            #or config('app.env') == 'local'
            #or config('app.env') == 'testing'
            or $dominion->round->is_ticking == 0
            )
        {
            return;
        }

        app(StatsService::class)->updateStat($dominion, 'world_spinner_encounters', 1);
        throw new GameException('The World Spinner is spinning the world. Your request was discarded. Try again soon, little one.');
    }
    

}
