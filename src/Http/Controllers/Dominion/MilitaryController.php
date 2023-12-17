<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Models\Resource;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\DominionHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Http\Requests\Dominion\Actions\ReleaseActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\Military\ChangeDraftRateActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\Military\TrainActionRequest;

use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;

use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\Actions\ReleaseActionService;
use OpenDominion\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;

class MilitaryController extends AbstractDominionController
{
    public function getMilitary()
    {
        $self = $this->getSelectedDominion();
        $queueService = app(QueueService::class);
        $returningResources = [];

        foreach($self->race->resources as $resourceKey)
        {
            $returningResources[$resourceKey] = $queueService->getInvasionQueueTotalByResource($self, 'resource_' . $resourceKey);
            $returningResources[$resourceKey] += $queueService->getExpeditionQueueTotalByResource($self, 'resource_' . $resourceKey);
            $returningResources[$resourceKey] += $queueService->getTheftQueueTotalByResource($self, 'resource_' . $resourceKey);
            $returningResources[$resourceKey] += $queueService->getSabotageQueueTotalByResource($self, 'resource_' . $resourceKey);
            $returningResources[$resourceKey] += $queueService->getDesecrationQueueTotalByResource($self, 'resource_' . $resourceKey);
            $returningResources[$resourceKey] += $queueService->getStunQueueTotalByResource($self, 'resource_' . $resourceKey);
        }

        $returningResources['prestige'] = $queueService->getInvasionQueueTotalByResource($self, 'prestige');
        $returningResources['prestige'] += $queueService->getExpeditionQueueTotalByResource($self, 'prestige');
        $returningResources['prestige'] += $queueService->getTheftQueueTotalByResource($self, 'prestige');
        $returningResources['prestige'] += $queueService->getSabotageQueueTotalByResource($self, 'prestige');
        $returningResources['prestige'] += $queueService->getDesecrationQueueTotalByResource($self, 'prestige');
        $returningResources['prestige'] += $queueService->getStunQueueTotalByResource($self, 'prestige');

        $returningResources['xp'] = $queueService->getInvasionQueueTotalByResource($self, 'xp');
        $returningResources['xp'] += $queueService->getExpeditionQueueTotalByResource($self, 'xp');
        $returningResources['xp'] += $queueService->getTheftQueueTotalByResource($self, 'xp');
        $returningResources['xp'] += $queueService->getSabotageQueueTotalByResource($self, 'xp');
        $returningResources['xp'] += $queueService->getDesecrationQueueTotalByResource($self, 'xp');
        $returningResources['xp'] += $queueService->getStunQueueTotalByResource($self, 'xp');

        return view('pages.dominion.military', [
            'casualtiesCalculator' => app(CasualtiesCalculator::class),
            'dominionHelper' => app(DominionHelper::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'resourceCalculator' => app(ResourceCalculator::class),
            'queueService' => $queueService,
            'trainingCalculator' => app(TrainingCalculator::class),
            'unitHelper' => app(UnitHelper::class),
            'improvementCalculator' => app(ImprovementCalculator::class),
            'prestigeCalculator' => app(PrestigeCalculator::class),
            'raceHelper' => app(RaceHelper::class),
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'returningResources' => $returningResources,
            'spellCalculator' => app(SpellCalculator::class),
        ]);
    }

    public function postChangeDraftRate(ChangeDraftRateActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $changeDraftRateActionService = app(ChangeDraftRateActionService::class);
        $newDraftRate = intval($request->get('draft_rate'));

        try {
            $result = $changeDraftRateActionService->changeDraftRate($dominion, $newDraftRate);

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'military.change-draft-rate',
            '',
            $result['data']['draftRate']
        ));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.military');
    }

    public function postReleaseDraftees(ReleaseActionRequest $request)
    {
        $release = $request->get('release');
        foreach($release as $unitType => $amount)
        {
            if($unitType !== 'draftees')
            {
                $release[$unitType] = '0';
            }
        }

        #dd($release, $request->get('release'));

        $dominion = $this->getSelectedDominion();
        $releaseActionService = app(ReleaseActionService::class);

        try {
            $result = $releaseActionService->release($dominion, $release);

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'release',
            null, // todo: make null everywhere where ''
            $result['data']['totalTroopsReleased']
        ));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.military');

    }

    public function postTrain(TrainActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $militaryTrainActionService = app(TrainActionService::class);

        try {
            $result = $militaryTrainActionService->train($dominion, $request->get('train'));

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.military');
    }

    public function getRelease()
    {
        return view('pages.dominion.release', [
            'unitHelper' => app(UnitHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
        ]);
    }

    public function postRelease(ReleaseActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $releaseActionService = app(ReleaseActionService::class);

        try {
            $result = $releaseActionService->release($dominion, $request->get('release'));

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'release',
            null, // todo: make null everywhere where ''
            $result['data']['totalTroopsReleased']
        ));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.military.release');

    }
}
