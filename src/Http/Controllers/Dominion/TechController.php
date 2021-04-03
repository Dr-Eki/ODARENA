<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Http\Requests\Dominion\Actions\TechActionRequest;
use OpenDominion\Models\Tech;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\TechActionService;

class TechController extends AbstractDominionController
{
    public function getTechs()
    {

        $techs = Tech::all()->where('enabled',1)->keyBy('key');

        $techs = $techs->sortBy(function ($tech, $key)
        {
            return $tech['name'] . str_pad($tech['level'], 2, '0', STR_PAD_LEFT);
        });

        return view('pages.dominion.advancements', [
            'techs' => $techs,#Tech::all()->where('enabled',1)->keyBy('key')->orderBy('level','name'),#->sortBy('name'),#sortBy('key'),
            'techCalculator' => app(TechCalculator::class),
            'techHelper' => app(TechHelper::class),
        ]);
    }

    public function postTechs(TechActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $techActionService = app(TechActionService::class);

        try {
            $result = $techActionService->unlock(
                $dominion,
                $request->get('key')
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'tech',
            $request->get('key')
        ));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.advancements');
    }
}
