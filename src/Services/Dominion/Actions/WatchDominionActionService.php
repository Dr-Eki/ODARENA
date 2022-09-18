<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\AdvancementCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Advancement;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\WatchedDominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;


use OpenDominion\Calculators\Dominion\SpellCalculator;

class WatchDominionActionService
{
    use DominionGuardsTrait;

    public function __construct()
    {
        #$this->militaryCalculator = app(MilitaryCalculator::class);
    }

    public function watchDominion(Dominion $watcher, Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardLockedDominion($watcher);
        $this->guardActionsDuringTick($dominion);
        $this->guardActionsDuringTick($wath);

        // Check if same round
        if($watcher->round->id !== $dominion->round->id)
        {
            throw new GameException('You cannot watch dominions from other rounds.');
        }

        // Check if already watched
        if($watcher->watchedDominions()->get()->contains($dominion))
        {
            throw new GameException('You are already watching ' . $dominion->name . '.');
        }

        // Check if already watched
        if($watcher->watchedDominions()->count() >= 10)
        {
            throw new GameException('You cannot watch more than 10 dominions.');
        }

        DB::transaction(function () use ($watcher, $dominion) {
            WatchedDominion::create([
                'watcher_id' => $watcher->id,
                'dominion_id' => $dominion->id
            ]);

            $watcher->save([
                'event' => HistoryService::EVENT_WATCH_DOMINION,
                'action' => $dominion->id
            ]);
        });

        return [
            'message' => sprintf(
                'You are now watching %s (# %s).',
                $dominion->name,
                $dominion->realm->number
            )
        ];
    }
    
    public function unwatchDominion(Dominion $watcher, Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardLockedDominion($watcher);
        $this->guardActionsDuringTick($dominion);
        $this->guardActionsDuringTick($watch);

        // Check if same round
        if($watcher->round->id !== $dominion->round->id)
        {
            throw new GameException('You cannot watch dominions from other rounds.');
        }

        // Check if dominion is currently not being watched
        if(!$watcher->watchedDominions()->get()->contains($dominion))
        {
            throw new GameException('You are not currently watching ' . $dominion->name . '.');
        }

        DB::transaction(function () use ($watcher, $dominion) {
            WatchedDominion::where([
                'watcher_id' => $watcher->id,
                'dominion_id' => $dominion->id
            ])->delete();

            $watcher->save([
                'event' => HistoryService::EVENT_UNWATCH_DOMINION,
                'action' => $dominion->id
            ]);
        });

        return [
            'message' => sprintf(
                'You have stopped watching %s (# %s).',
                $dominion->name,
                $dominion->realm->number
            )
        ];
    }
}
