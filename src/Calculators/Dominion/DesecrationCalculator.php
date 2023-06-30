<?php

namespace OpenDominion\Calculators\Dominion;

use Log;
use Collection;
use OpenDominion\Exceptions\GameException;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
#use OpenDominion\Services\Dominion\QueueService;

class DesecrationCalculator
{
    protected $queueService;

    public function __construct(
        #QueueService $queueService
    ) {
        #$this->queueService = $queueService;
    }

    public function getMaxBodiesDesecrated(Dominion $desecrator, array $desecratingUnits): int
    {
        $bodies = 0;

        foreach($desecratingUnits as $slot => $amount)
        {
            if(($desecrationPerk = $desecrator->race->getUnitPerkValueForUnitSlot($slot, 'desecration')))
            {
                $perUnit = (float)$desecrationPerk[0];
                $bodies += $perUnit * $amount;
            }
        }

        return $bodies;
    }

    public function getBodiesDesecrated(Dominion $desecrator, array $desecratingUnits, GameEvent $battlefield): int
    {
        $maxDesecrated = $this->getMaxBodiesDesecrated($desecrator, $desecratingUnits);
        $availableBodies = $battlefield->data['result']['bodies']['available'];

        $bodiesDesecrated = min($maxDesecrated, $availableBodies);

        $bodiesDesecrated = max(0, $bodiesDesecrated);

        return $bodiesDesecrated;
    }

    public function getDesecrationResult(Dominion $desecrator, array $desecratingUnits, int $bodies): array
    {
        $result = []; # [resourceKey => amount] pairs

        $bodiesRemaining = $bodies;

        foreach($desecratingUnits as $slot => $amount)
        {
            #if($bodiesRemaining <= 0)
            #{
            #    break;
            #}

            $desecrationPerk = $desecrator->race->getUnitPerkValueForUnitSlot($slot, 'desecration');

            $perUnit = (float)$desecrationPerk[0];
            $perBody = (float)$desecrationPerk[1];
            $resourceKey = $desecrationPerk[2];

            $bodiesUsed = min($bodiesRemaining, $amount * $perUnit);

            $resourceCreated = $bodiesUsed * $perBody;

            $result[$resourceKey] = (int)floor(($result[$resourceKey] ?? 0) + $resourceCreated);

            $bodiesRemaining = $bodiesRemaining - $bodiesUsed;
        }

        return $result;
    }

    public function getAvailableBattlefields(Dominion $desecrator)
    {
        # $minimumTick should be within the last 384 ticks
        $minimumTick = max($desecrator->round->ticks - 384, 0);

        # Get game events where type is 'invasion' or 'barbarian_invasion'
        return $desecrator->round->gameEvents()
            ->whereIn('type', ['invasion', 'barbarian_invasion'])
            ->where('tick', '>=', $minimumTick)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function isOwnRealmDesecration(Dominion $desecrator, GameEvent $battlefield): bool
    {
        if($battlefield->type === 'barbarian_invasion')
        {
            return false;
        }

        if($battlefield->data['result']['success'])
        {
            return $desecrator->realm->id == $battlefield->source->realm->id;
        }

        if(!$battlefield->data['result']['success'])
        {
            return $desecrator->realm->id == $battlefield->target->realm->id;
        }
    }
}
