<?php

namespace OpenDominion\Services\Dominion;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;

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


    public function getWorldNews(Dominion $viewer, Realm $realm = null, $perPage = 50, $currentPage = null)
    {
        // Retrieve user world news settings or default settings
        $userWorldNewsSettings = $viewer->user->settings['world_news'] ?? $this->worldNewsHelper->getDefaultUserWorldNewsSettings();
    
        // Transform user settings into a more usable format
        $worldNewsSettings = [];
        foreach ($userWorldNewsSettings as $eventScopeKey => $view) {
            if ($view) {
                list($settingScope, $settingEventKey) = explode('.', $eventScopeKey);
                $worldNewsSettings[$settingScope][] = $settingEventKey;
            }
        }
    
        // Retrieve all events without filtering
        $events = $viewer->round->gameEvents()
                                ->orderBy('created_at', 'desc')
                                ->get();
    
        // Filter the events based on user settings and realm
        $filteredEvents = $events->filter(function ($event) use ($viewer, $worldNewsSettings, $realm) {
            return $event->isInWorldNewsUserSettings($viewer, $worldNewsSettings) && $event->isInWorldNewsRealm($realm);
        });
    
        // Paginate the filtered collection
        $currentPage = $currentPage ?: LengthAwarePaginator::resolveCurrentPage();
        $currentPageItems = $filteredEvents->slice(($currentPage - 1) * $perPage, $perPage)->all();
    
        return new LengthAwarePaginator($currentPageItems, $filteredEvents->count(), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]);
    }

    public function getUnreadNewsCount(Dominion $dominion)
    {
        return $this->getWorldNews($dominion)->filter(function($event) use ($dominion)
        {
            return $event->created_at >= $dominion->news_last_read;
        })->count();
    }

}
