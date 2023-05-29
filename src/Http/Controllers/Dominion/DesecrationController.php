<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\DesecrationCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\DesecrateActionRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Services\Dominion\DesecrationService;
use OpenDominion\Services\Dominion\QueueService;

class DesecrationController extends AbstractDominionController
{
    public function getDesecrate()
    {
        return view('pages.dominion.desecrate', [
            'desecrationCalculator' => app(DesecrationCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),

            'unitHelper' => app(UnitHelper::class),

            'queueService' => app(QueueService::class),
        ]);
    }

    public function postDesecrate(DesecrateActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $desecrationService = app(DesecrationService::class);
        $battlefield = GameEvent::findOrFail($request->get('battlefield'));

        try {
            $result = $desecrationService->desecrate(
                $dominion,
                $request->get('unit'),
                $battlefield
            );

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // analytics event

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()->to($result['redirect'] ?? route('dominion.desecrate'));
    }
}
