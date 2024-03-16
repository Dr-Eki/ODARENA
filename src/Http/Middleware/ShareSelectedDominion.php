<?php

namespace OpenDominion\Http\Middleware;

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

                $request->session()->flash('alert-danger', 'Action not possible. Please refresh the page and try again.');

                return redirect()->route('home');
            }

            view()->share('selectedDominion', $dominion);
        }

        return $next($request);
    }
}
