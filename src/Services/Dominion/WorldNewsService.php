<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Auth;
use Illuminate\Support\Collection;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\User;

use OpenDominion\Helpers\WorldNewsHelper;

class WorldNewsService
{

    protected $roundService;
    protected $worldNewsHelper;

    public function __construct()
    {
        $this->roundService = app(RoundService::class);
        $this->worldNewsHelper = app(WorldNewsHelper::class);
    }

    # The default view, contains all news for the current round.
    # forDominion = visible for this dominion (regardless of realm, subject to own preferences)
    public function getWorldNewsForDominion(Dominion $dominion, string $worldNewsScope = 'default', $maxTicksAgo = 192): Collection
    {
        $user = Auth::user();
        if($user->id !== $dominion->user->id)
        {
            abort(403);
        }

        $worldNewsSettings = $user->settings['world_news'] ?? $this->worldNewsHelper->getDefaultUserWorldNewsSettings();

        $events = $dominion->round->gameEvents()
                                    ->where('tick', '>=', ($dominion->round->ticks - $maxTicksAgo))
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        # Works but is slow.
        foreach($events as $index => $event)
        {
            foreach($worldNewsSettings as $eventScopeKey => $view)
            {
                $settingScope = explode('.',$eventScopeKey)[0];
                $settingEventKey = explode('.',$eventScopeKey)[1];

                if($this->getDominionScopeRelation($dominion, $event) == $settingScope and $event->type == $settingEventKey and !$view)
                {
                    $events->forget($index);
                }
            }
        }

        return $events;
    }

    public function getWorldNewsForRealm(Realm $realm, Dominion $viewer, $maxTicksAgo = 192): Collection
    {
        $user = Auth::user();

        if($user->id !== $viewer->user->id)
        {
            abort(403);
        }

        $worldNewsSettings = $user->settings['world_news'] ?? $this->worldNewsHelper->getDefaultUserWorldNewsSettings();

        $events = $realm->round->gameEvents()
                                    ->where('tick', '>=', ($realm->round->ticks - $maxTicksAgo))
                                    ->orderBy('created_at', 'desc')
                                    ->get();
        
        $events = $this->filterRealmEvents($events, $realm);

        foreach($events as $index => $event)
        {
            foreach($worldNewsSettings as $eventScopeKey => $view)
            {
                $settingScope = explode('.',$eventScopeKey)[0];
                $settingEventKey = explode('.',$eventScopeKey)[1];

                if($this->getDominionScopeRelation($viewer, $event) == $settingScope and $event->type == $settingEventKey and !$view)
                {
                    $events->forget($index);
                }
            }
        }

        return $events;
    }

    private function getDominionScopeRelation(Dominion $dominion, GameEvent $event)
    {
        if(
            $event->source_type == Dominion::class and $event->source_id == $dominion->id or
            $event->target_type == Dominion::class and $event->target_id == $dominion->id or
            $event->source_type == Dominion::class and $event->source->realm->id == $dominion->realm->id or
            #$event->target_type == Dominion::class and $event->target->realm->id == $dominion->realm->id or

            $event->target_type == Realm::class and $event->target_id == $dominion->realm->id or
            $event->target_type == Realm::class and $event->target_id == $dominion->realm->id
        )
        {
            return 'own';
        }
        else
        {
            return 'other';
        }
    }

    private function filterRealmEvents(Collection $events, Realm $realm)
    {
        $events = $events->filter(function($event) use ($realm) {
            return (
                $event->source_type == Dominion::class and Dominion::findOrFail($event->source_id)->realm->id == $realm->id or
                $event->target_type == Dominion::class and Dominion::findOrFail($event->target_id)->realm->id == $realm->id or
                $event->target_type == Realm::class and $event->target_id == $realm->id or
                $event->target_type == Realm::class and $event->target_id == $realm->id
            );
        });

        return $events;
    }

    public function getUnreadNewsCount(Dominion $dominion)
    {
        return $this->getWorldNewsForDominion($dominion)->filter(function($event) use ($dominion)
        {
            return $event->created_at >= $dominion->news_last_read;
        })->count();
    }

}
