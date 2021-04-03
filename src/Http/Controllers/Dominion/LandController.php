<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\RezoneActionRequest;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;

use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Http\Requests\Dominion\Actions\ExploreActionRequest;
use OpenDominion\Services\Dominion\Actions\ExploreActionService;
use OpenDominion\Services\Dominion\QueueService;

use OpenDominion\Http\Requests\Dominion\Actions\DailyBonusesLandActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\DailyBonusesGoldActionRequest;
use OpenDominion\Services\Dominion\Actions\DailyBonusesActionService;

# ODA
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Calculators\Dominion\LandImprovementCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;

class LandController extends AbstractDominionController
{
    public function getLand()
    {
        return view('pages.dominion.land', [
            'landCalculator' => app(LandCalculator::class),
            'rezoningCalculator' => app(RezoningCalculator::class),
            'explorationCalculator' => app(ExplorationCalculator::class),
            'landHelper' => app(LandHelper::class),
            'queueService' => app(QueueService::class),
            'spellCalculator' => app(SpellCalculator::class),
            'productionCalculator' => app(ProductionCalculator::class),
            'guardMembershipService' => app(GuardMembershipService::class),
            'landImprovementCalculator' => app(LandImprovementCalculator::class),
        ]);
    }

    public function postLand(RezoneActionRequest $request)
    {

        $dominion = $this->getSelectedDominion();

        # Explore
        if($request->get('action') === 'explore')
        {
            $exploreActionService = app(ExploreActionService::class);

            try {
                $result = $exploreActionService->explore($dominion, $request->get('explore'));

            } catch (GameException $e) {
                return redirect()->back()
                    ->withInput($request->all())
                    ->withErrors([$e->getMessage()]);
            }

            // todo: fire laravel event
            $analyticsService = app(AnalyticsService::class);
            $analyticsService->queueFlashEvent(new AnalyticsEvent(
                'dominion',
                'explore',
                '', // todo: make null?
                array_sum($request->get('explore'))
            ));

            $request->session()->flash('alert-success', $result['message']);
            return redirect()->route('dominion.land');
        }
        # Rezone
        elseif($request->get('action') === 'rezone')
        {
            $rezoneActionService = app(RezoneActionService::class);

            try {
                $result = $rezoneActionService->rezone(
                    $dominion,
                    $request->get('remove'),
                    $request->get('add')
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
                'rezone',
                '', // todo: make null?
                array_sum($request->get('remove'))
            ));

            $request->session()->flash('alert-success', $result['message']);
            return redirect()->route('dominion.land');
        }
        # Daily Bonus
        elseif($request->get('action') === 'daily_land')
        {
            $dailyBonusesActionService = app(DailyBonusesActionService::class);

            try {
                $result = $dailyBonusesActionService->claimLand($dominion);
            } catch (GameException $e) {
                return redirect()->back()
                    ->withInput($request->all())
                    ->withErrors([$e->getMessage()]);
            }

            $request->session()->flash('alert-success', $result['message']);
            return redirect()->route('dominion.land');
        }
    }
}
