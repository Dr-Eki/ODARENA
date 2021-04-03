<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Http\Requests\Dominion\Actions\PerformEspionageRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\EspionageActionService;
use OpenDominion\Services\Dominion\ProtectionService;

# ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Spyop;

class EspionageController extends AbstractDominionController
{
    public function getEspionage()
    {

        #$infoOps = Spyop::all()->where('scope','info')->where('enabled',1)->sortBy('key');
        $theftOps = Spyop::all()->where('scope','theft')->where('enabled',1)->sortBy('key');
        $hostileOps = Spyop::all()->where('scope','hostile')->where('enabled',1)->sortBy('key');

        return view('pages.dominion.espionage', [
            'espionageCalculator' => app(EspionageCalculator::class),
            'espionageHelper' => app(EspionageHelper::class),
            'landCalculator' => app(LandCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),

            #ODA
            'networthCalculator' => app(NetworthCalculator::class),
            #'infoOps' => $infoOps,
            'theftOps' => $theftOps,
            'hostileOps' => $hostileOps,
        ]);
    }

    public function postEspionage(PerformEspionageRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $espionageActionService = app(EspionageActionService::class);

        try {
            /** @noinspection PhpParamsInspection */
            $result = $espionageActionService->performOperation(
                $dominion,
                $request->get('operation'),
                Dominion::findOrFail($request->get('target_dominion'))
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
            'espionage.perform',
            $result['data']['operation']
        ));

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);

        return redirect()
            ->to($result['redirect'] ?? route('dominion.espionage'))
            ->with('target_dominion', $request->get('target_dominion'));
    }
}
