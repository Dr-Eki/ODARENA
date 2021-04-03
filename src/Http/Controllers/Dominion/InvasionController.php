<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\InvadeActionRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Actions\InvadeActionService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\ProtectionService;

# ODA
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Services\Dominion\Actions\SendUnitsActionService;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Calculators\Dominion\LandImprovementCalculator;

class InvasionController extends AbstractDominionController
{
    public function getInvade()
    {
        return view('pages.dominion.invade', [
            'governmentService' => app(GovernmentService::class),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'unitHelper' => app(UnitHelper::class),

            # ODA
            'spellCalculator' => app(SpellCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'prestigeCalculator' => app(PrestigeCalculator::class),
            'improvementCalculator' => app(ImprovementCalculator::class),
            'raceHelper' => app(RaceHelper::class),
            'landImprovementCalculator' => app(LandImprovementCalculator::class)
        ]);
    }

    public function postInvade(InvadeActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $invasionActionService = app(InvadeActionService::class);
        #$sendUnitsActionService = app(SendUnitsActionService::class);

        try {
            $result = $invasionActionService->invade(
            #$result = $sendUnitsActionService->sendUnits(
                $dominion,
                Dominion::findOrFail($request->get('target_dominion')),
                $request->get('unit')
            );

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // analytics event

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()->to($result['redirect'] ?? route('dominion.invade'));
    }
}
