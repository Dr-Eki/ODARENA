<?php

namespace OpenDominion\Http\Middleware;

use Analytics;
use Closure;

class FireAnalyticsFlashEvents
{
    /** @var AnalyticsService */
    protected $analyticsService;
/*
    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function handle($request, Closure $next)
    {
        // todo: if request method === GET?

        foreach ($this->analyticsService->getFlashEvents() as $event) {
            Analytics::trackEvent(
                $event->getCategory(),
                $event->getAction(),
                $event->getLabel(),
                $event->getValue()
            );
        }

        return $next($request);
    }
*/
}
