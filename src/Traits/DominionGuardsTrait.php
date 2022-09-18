<?php

namespace OpenDominion\Traits;

use Carbon\Carbon;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use RuntimeException;

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
        if ($dominion->protection_ticks == 0)
        {
            $requestTimestamp = request()->server('REQUEST_TIME');
            if ($requestTimestamp !== null)
            {
                $requestTime = Carbon::createFromTimestamp($requestTimestamp);
                if (in_array($requestTime->minute,[0,15,30,45]) && $requestTime->second < $seconds)
                {
                    throw new GameException('The World Spinner is spinning the world. Your request was discarded. Try again soon, little one.');
                }
            }
        }
    }

}
