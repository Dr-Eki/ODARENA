<?php

namespace OpenDominion\Providers;

use Cache;
use Illuminate\Contracts\View\View;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Helpers\RoundHelper;
use OpenDominion\Models\Council\Post;
use OpenDominion\Models\Council\Thread;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\WorldNewsService;
#use OpenDominion\Models\GameEvent;
use OpenDominion\Calculators\Dominion\ResearchCalculator;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use Carbon\Carbon;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\RealmHelper;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;

class ComposerServiceProvider extends AbstractServiceProvider
{

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function boot()
    {
        view()->composer('layouts.topnav', function (View $view) {
            $view->with('selectorService', app(SelectorService::class));
        });

        view()->composer('partials.main-sidebar', function (View $view) {
            $selectorService = app(SelectorService::class);
            $realmHelper = app(RealmHelper::class);
            $techCalculator = app(TechCalculator::class);
            $researchCalculator = app(ResearchCalculator::class);
            $resourceCalculator = app(ResourceCalculator::class);
            #$productionCalculator = app(ProductionCalculator::class);
            $worldNewsService = app(WorldNewsService::class);

            if (!$selectorService->hasUserSelectedDominion()) {
                return;
            }

            /** @var Dominion $dominion */
            $dominion = $selectorService->getUserSelectedDominion();

            $councilLastRead = $dominion->council_last_read;
            $newsLastRead = $dominion->news_last_read ?? $dominion->created_at;

            $councilUnreadCount = $dominion->realm
                ->councilThreads()
                ->with('posts')
                ->get()
                ->map(static function (Thread $thread) use ($councilLastRead) {
                    $unreadCount = $thread->posts->filter(static function (Post $post) use ($councilLastRead) {
                        return $post->created_at > $councilLastRead;
                    })->count();

                    if ($thread->created_at > $councilLastRead) {
                        $unreadCount++;
                    }

                    return $unreadCount;
                })
                ->sum();

            $newsUnreadCount = $worldNewsService->getUnreadNewsCount($dominion);

            $view->with('councilUnreadCount', $councilUnreadCount);
            $view->with('newsUnreadCount', $newsUnreadCount);
            $view->with('realmHelper', $realmHelper);
            $view->with('researchCalculator', $researchCalculator);
            $view->with('resourceCalculator', $resourceCalculator);
            $view->with('techCalculator', $techCalculator);
            #$view->with('productionCalculator', $productionCalculator);
        });

        view()->composer('partials.main-footer', function (View $view)
        {
            $selectorService = app(SelectorService::class);
            $hoursUntilRoundStarts = 0;

            if($dominion = $selectorService->getUserSelectedDominion())
            {
                $hoursUntilRoundStarts = now()->startOfHour()->diffInHours(Carbon::parse($dominion->round->start_date)->startOfHour());
            }

            $version = (Cache::has('version-html') ? Cache::get('version-html') : 'unknown');
            $view->with('version', $version);
            $view->with('hoursUntilRoundStarts', $hoursUntilRoundStarts);
            $view->with('roundHelper', app(RoundHelper::class));
            $view->with('version', $version);
        });

        view()->composer('partials.notification-nav', function (View $view) {
            $view->with('notificationHelper', app(NotificationHelper::class));
        });

        // todo: do we need this here in this class?
        view()->composer('partials.resources-overview', function (View $view)
        {
            $view->with('networthCalculator', app(NetworthCalculator::class));
            $view->with('resourceCalculator', app(ResourceCalculator::class));
            $view->with('dominionProtectionService', app(ProtectionService::class));
            $view->with('raceHelper', app(RaceHelper::class));
            $view->with('landCalculator', app(LandCalculator::class));
            $view->with('populationCalculator', app(PopulationCalculator::class));
            $view->with('militaryCalculator', app(MilitaryCalculator::class));
        });

        view()->composer('partials.styles', function (View $view) {
            $version = (Cache::has('version') ? Cache::get('version') : 'unknown');
            $view->with('version', $version);
        });
    }
}
