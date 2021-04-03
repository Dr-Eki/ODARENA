<?php

namespace OpenDominion\Providers;

use Cache;
use Illuminate\Contracts\View\View;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\Council\Post;
use OpenDominion\Models\Council\Thread;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\SelectorService;

# ODA
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Models\GameEvent;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use Carbon\Carbon;
use OpenDominion\Helpers\RaceHelper;
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
            #$landCalculator = app(LandCalculator::class);
            $techCalculator = app(TechCalculator::class);
            $productionCalculator = app(ProductionCalculator::class);

            if (!$selectorService->hasUserSelectedDominion()) {
                return;
            }

            /** @var Dominion $dominion */
            $dominion = $selectorService->getUserSelectedDominion();

            $councilLastRead = $dominion->council_last_read;
            $newsLastRead = $dominion->news_last_read;
            if($newsLastRead == null)
            {
                $newsLastRead = $dominion->created_at;
            }

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

            $newsUnreadCount = $gameEvents = GameEvent::query()
                ->select('id')
                ->where('round_id', $dominion->round->id)
                ->where('created_at', '>', $newsLastRead)
                ->count();

            $view->with('councilUnreadCount', $councilUnreadCount);
            $view->with('newsUnreadCount', $newsUnreadCount);
            $view->with('techCalculator', $techCalculator);
            $view->with('productionCalculator', $productionCalculator);
        });

        view()->composer('partials.main-footer', function (View $view)
        {
            $selectorService = app(SelectorService::class);
            $hoursUntilRoundStarts = 0;
            $hoursUntilRoundEnds = 0;
            if($dominion = $selectorService->getUserSelectedDominion())
            {
                $hoursUntilRoundStarts = now()->startOfHour()->diffInHours(Carbon::parse($dominion->round->start_date)->startOfHour());

                if($dominion->round->hasCountdown())
                {
                      $hoursUntilRoundEnds = now()->startOfHour()->diffInHours(Carbon::parse($dominion->round->end_date)->startOfHour());
                }
            }

            $version = (Cache::has('version-html') ? Cache::get('version-html') : 'unknown');
            $view->with('version', $version);
            $view->with('hoursUntilRoundStarts', $hoursUntilRoundStarts);
            $view->with('hoursUntilRoundEnds', $hoursUntilRoundEnds);
        });

        view()->composer('partials.notification-nav', function (View $view) {
            $view->with('notificationHelper', app(NotificationHelper::class));
        });

        // todo: do we need this here in this class?
        view()->composer('partials.resources-overview', function (View $view)
        {
            $view->with('networthCalculator', app(NetworthCalculator::class));
            $view->with('dominionProtectionService', app(ProtectionService::class));
            $view->with('raceHelper', app(RaceHelper::class));
            $view->with('landCalculator', app(LandCalculator::class));
            $view->with('militaryCalculator', app(MilitaryCalculator::class));
        });

        view()->composer('partials.styles', function (View $view) {
            $version = (Cache::has('version') ? Cache::get('version') : 'unknown');
            $view->with('version', $version);
        });
    }
}
