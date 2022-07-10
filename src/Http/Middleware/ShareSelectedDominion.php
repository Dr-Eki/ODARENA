<?php

namespace OpenDominion\Http\Middleware;

use Bugsnag;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenDominion\Services\Dominion\SelectorService;

class ShareSelectedDominion
{
    /** @var SelectorService */
    protected $dominionSelectorService;

    /**
     * ShareSelectedDominion constructor.
     *
     * @param SelectorService $dominionSelectorService
     */
    public function __construct(SelectorService $dominionSelectorService)
    {
        $this->dominionSelectorService = $dominionSelectorService;
    }

    public function handle($request, Closure $next)
    {
        if ($this->dominionSelectorService->hasUserSelectedDominion()) {
            try {
                $dominion = $this->dominionSelectorService->getUserSelectedDominion();

            } catch (ModelNotFoundException $e) {
                $this->dominionSelectorService->unsetUserSelectedDominion();

                $request->session()->flash('alert-danger', 'The database has been reset for development purposes since your last visit. Please re-register a new account. You can use the same credentials you\'ve used before.');

                return redirect()->route('home');
            }

            // Add dominion context to Bugsnag
            Bugsnag::registerCallback(function (Bugsnag\Report $report) use ($dominion) {
                /** @noinspection NullPointerExceptionInspection */
                $report->setMetaData([
                    'dominion' => array_except($dominion->toArray(), ['race', 'realm']),
                ]);
            });

            view()->share('selectedDominion', $dominion);
        }

        return $next($request);
    }
}
