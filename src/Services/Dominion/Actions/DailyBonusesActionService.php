<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Traits\DominionGuardsTrait;

class DailyBonusesActionService
{
    use DominionGuardsTrait;

    public function __construct()
    {
        $this->resourceService = app(ResourceService::class);
        $this->statsService = app(StatsService::class);
    }

    /**
     * Claims the daily gold bonus for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function claimGold(Dominion $dominion): array
    {
        throw new GameException('The resource bonus has been removed.');

        return 'The resource bonus has been removed.';
    }

    /**
     * Claims the daily land bonus for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function claimLand(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot explore while you are in stasis.');
        }

        if ($dominion->daily_land)
        {
            throw new GameException('You already claimed your land bonus for today.');
        }

        if($dominion->protection_ticks > 0 or !$dominion->round->hasStarted())
        {
          throw new GameException('You cannot claim daily bonus during protection or before the round has started.');
        }

        $landGained = rand(1,200) == 1 ? 100 : rand(10, 40);
        $xpGained = $landGained * 20;
        $attribute = ('land_' . $dominion->race->home_land_type);

        $dominion->{$attribute} += $landGained;
        $dominion->xp += $xpGained;

        $this->statsService->updateStat($dominion, 'land_discovered', $landGained);

        $dominion->daily_land = true;
        $dominion->save(['event' => HistoryService::EVENT_ACTION_DAILY_BONUS]);

        return [
            'message' => sprintf(
                'You gain %d acres of %s and %s XP.',
                $landGained,
                str_plural($dominion->race->home_land_type),
                number_format($xpGained)
            ),
            'data' => [
                'landGained' => $landGained,
            ],
        ];
    }
}
