<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\ExpeditionCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\ExpeditionActionRequest;
use OpenDominion\Services\Dominion\Actions\ExpeditionActionService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\RaceHelper;

class ExpeditionController extends AbstractDominionController
{
    public function getExpedition()
    {
        return view('pages.dominion.expedition', [
            'protectionService' => app(ProtectionService::class),

            'expeditionCalculator' => app(ExpeditionCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'magicCalculator' => app(MagicCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),

            'raceHelper' => app(RaceHelper::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function postExpedition(ExpeditionActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $expeditionActionService = app(ExpeditionActionService::class);

        try {
            $result = $expeditionActionService->send(
                $dominion,
                $request->get('unit')
            );

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // analytics event

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()->to($result['redirect'] ?? route('dominion.expedition'));
    }
}
