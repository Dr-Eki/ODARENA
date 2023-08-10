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
        $requestTimestamp = request()->server('REQUEST_TIME');
        $requestTime = Carbon::createFromTimestamp($requestTimestamp);
        $statsService = app(StatsService::class);

        if ($dominion->protection_ticks === 0 and $dominion->race->name !== 'Barbarian')
        {
            if (!in_array($requestTime->minute,[0,15,30,45]) && $requestTime->second < $seconds and (env('APP_ENV') !== 'local'))
            {
                $statsService->updateStat($dominion, 'world_spinner_encounters', 1);
                throw new GameException('The World Spinner is spinning the world. Your request was discarded. Try again soon, little one.');
            }
        }
    }

}
