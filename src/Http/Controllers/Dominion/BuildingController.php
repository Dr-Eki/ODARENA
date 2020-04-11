<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Http\Requests\Dominion\Actions\ConstructActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\DestroyActionRequest;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\ConstructActionService;
use OpenDominion\Services\Dominion\Actions\DestroyActionService;
use OpenDominion\Services\Dominion\QueueService;


use OpenDominion\Models\Building;
use OpenDominion\Services\Dominion\Actions\BuildActionService;
use OpenDominion\Http\Requests\Dominion\Actions\BuildActionRequest;

class BuildingController extends AbstractDominionController
{
    public function getBuildings()
    {
        return view('pages.dominion.buildings', [
            'buildingCalculator' => app(BuildingCalculator::class),
            'buildingHelper' => app(BuildingHelper::class),
            'constructionCalculator' => app(ConstructionCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'queueService' => app(QueueService::class),
        ]);
    }

    public function postBuildings(BuildActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $buildActionService = app(BuildActionService::class);

        try {
            $result = $buildActionService->construct($dominion, $request->get('construct'));

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'construct',
            '',
            array_sum($request->get('construct')) // todo: get from $result
        ));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.construct');
    }

    public function getDestroy()
    {
        return view('pages.dominion.destroy', [
            'buildingCalculator' => app(BuildingCalculator::class),
            'buildingHelper' => app(BuildingHelper::class),
            'landCalculator' => app(LandCalculator::class),
        ]);
    }

    public function postDestroy(DestroyActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $destroyActionService = app(DestroyActionService::class);

        try {
            $result = $destroyActionService->destroy($dominion, $request->get('destroy'));

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'destroy',
            '',
            $result['data']['totalBuildingsDestroyed']
        ));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.destroy');
    }
}
