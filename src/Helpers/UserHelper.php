<?php

namespace OpenDominion\Helpers;

use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\User;

use OpenDominion\Calculators\Dominion\LandCalculator;

use OpenDominion\Services\Dominion\StatsService;

class UserHelper
{

    public function __construct()
    {
        $this->chroniclesHelper = app(ChroniclesHelper::class);
        $this->roundHelper = app(RoundHelper::class);

        $this->landCalculator = app(LandCalculator::class);

        $this->statsService = app(StatsService::class);
    }


    public function getRoundsPlayed(User $user, bool $lifetime = true)
    {
        $maxRoundsAgo = $this->chroniclesHelper->getDefaultRoundsAgo();
        if($lifetime)
        {
            $maxRoundsAgo = $this->chroniclesHelper->getMaxRoundNumber();
        }

        return $this->getUserDominions($user, false, $maxRoundsAgo)->count();
    }

    public function getUserDominions(User $user, bool $inclueActiveRounds = false, int $maxRoundsAgo = 20)
    {
        $dominions = Dominion::where('user_id', $user->id)
                      ->where('is_locked','=',0)
                      ->where('protection_ticks','=',0)
                      ->whereRaw(' round_id >= (SELECT max(number) FROM rounds) - ?', [$maxRoundsAgo])
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

        return $dominions;
    }

    public function getTotalLandForUser(User $user, bool $lifetime = true): int
    {
        $totalLand = 0;

        $maxRoundsAgo = $this->chroniclesHelper->getDefaultRoundsAgo();
        if($lifetime)
        {
            $maxRoundsAgo = $this->chroniclesHelper->getMaxRoundNumber();
        }

        foreach($this->getUserDominions($user, false, $maxRoundsAgo) as $dominion)
        {
            $totalLand += $dominion->land;
        }

        return $totalLand;
    }

    public function getMaxLandForUser(User $user, bool $lifetime = true): int
    {
        $land = 0;

        $maxRoundsAgo = $this->chroniclesHelper->getDefaultRoundsAgo();
        if($lifetime)
        {
            $maxRoundsAgo = $this->chroniclesHelper->getMaxRoundNumber();
        }

        foreach($this->getUserDominions($user, false, $maxRoundsAgo) as $dominion)
        {
            $land = max($land, $dominion->land);
        }

        return $land;
    }

    public function getStatSumForUser(User $user, string $statKey): float
    {
        $value = 0.00;

        foreach($this->getUserDominions($user) as $dominion)
        {
            $value += $this->statsService->getStat($dominion, $statKey);
        }

        return $value;
    }

    public function getPrestigeSumForUser(User $user): float
    {
        $value = 0.00;

        foreach($this->getUserDominions($user) as $dominion)
        {
            $value += $dominion->prestige;
        }

        return $value;
    }

    public function getStatMaxForUser(User $user, string $statKey): float
    {
        $value = 0.00;

        foreach($this->getUserDominions($user) as $dominion)
        {
            $value = max($this->statsService->getStat($dominion, $statKey), $value);
        }

        return $value;
    }

    public function getTopRaces(User $user, int $max = null): array
    {
        $races = [];

        foreach($this->getUserDominions($user) as $dominion)
        {
            #if(isset($races[$dominion->race->name]))
            #{
            #    $races[$dominion->race->name] += 1;
            #}
            #else
            #{
            #    $races[$dominion->race->name] = 1;
            #}
            $races[$dominion->race->name] = (isset($races[$dominion->race->name]) ? $races[$dominion->race->name] + 1 : 1);
        }

        arsort($races);

        if($max)
        {
            $races = array_slice($races, 0, $max);
        }

        return $races;
    }

    public function getUniqueRacesCountForUser(User $user, bool $lifetime = true): int
    {
        $races = [];

        $maxRoundsAgo = $this->chroniclesHelper->getDefaultRoundsAgo();
        if($lifetime)
        {
            $maxRoundsAgo = $this->chroniclesHelper->getMaxRoundNumber();
        }

        foreach($this->getUserDominions($user, false, $maxRoundsAgo) as $dominion)
        {
            if(!$dominion->isAbandoned())
            {
                $races[] = $dominion->race->id;
            }
        }

        $races = array_unique($races);

        return count($races);
    }

    public function getTopPlacementsForUser(User $user, int $places = 3): array
    {
        # Format: $topPlacement[$roundId] = [$dominionId => $placement]
        $topPlacements = [];

        foreach($this->getUserDominions($user) as $dominion)
        {
            if(($placementInRound = $this->roundHelper->getDominionPlacementInRound($dominion)) <= $places)
            {
                $topPlacements[$placementInRound][] = $dominion->id;
            }
        }

        ksort($topPlacements);

        return $topPlacements;
    }

}
