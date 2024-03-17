<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\UnitCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\ArtefactHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\InvadeActionRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Actions\InvadeActionService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;

class InvasionController extends AbstractDominionController
{
    public function getInvade()
    {

        $resourceCalculator = app(ResourceCalculator::class);
        $returningResources = $resourceCalculator->getReturningResources($this->getSelectedDominion());

        return view('pages.dominion.invade', [
            'governmentService' => app(GovernmentService::class),
            'protectionService' => app(ProtectionService::class),

            'casualtiesCalculator' => app(CasualtiesCalculator::class),
            'improvementCalculator' => app(ImprovementCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'magicCalculator' => app(MagicCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'prestigeCalculator' => app(PrestigeCalculator::class),
            'queueService' => app(QueueService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'resourceCalculator' => app(ResourceCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            'unitCalculator' => app(UnitCalculator::class),

            'returningResources' => $returningResources,

            'artefactHelper' => app(ArtefactHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function postInvade(InvadeActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $invasionActionService = app(InvadeActionService::class);

        try {
            $result = $invasionActionService->invade(
                $dominion,
                Dominion::findOrFail($request->get('target_dominion')),
                $request->get('unit'),
                $request->get('capture_buildings', false)
            );

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // analytics event

        #$request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()->to($result['redirect'] ?? route('dominion.invade'));
    }
}
