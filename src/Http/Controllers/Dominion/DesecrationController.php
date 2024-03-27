<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\DesecrationCalculator;
use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\DesecrateActionRequest;
use OpenDominion\Services\Dominion\DesecrationService;
use OpenDominion\Services\Dominion\QueueService;

class DesecrationController extends AbstractDominionController
{
    public function getDesecrate()
    {
        return view('pages.dominion.desecrate', [
            'desecrationCalculator' => app(DesecrationCalculator::class),
            'magicCalculator' => app(MagicCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),

            'unitHelper' => app(UnitHelper::class),

            'queueService' => app(QueueService::class),
        ]);
    }

    public function postDesecrate(DesecrateActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $desecrationService = app(DesecrationService::class);

        try {
            $result = $desecrationService->desecrate(
                $dominion,
                $request->get('unit')
            );

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        return redirect()->to($result['redirect'] ?? route('dominion.desecrate'));
    }
}
