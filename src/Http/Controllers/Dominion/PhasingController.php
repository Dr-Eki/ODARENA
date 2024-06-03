<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\PhasingRequest;

use OpenDominion\Models\Unit;

use OpenDominion\Calculators\Dominion\PhasingCalculator;
use OpenDominion\Calculators\Dominion\UnitCalculator;


use OpenDominion\Services\Dominion\Actions\PhasingActionService;

class PhasingController extends AbstractDominionController
{
    public function getPhasing()
    {
        $phaser = $this->getSelectedDominion();

        $phasingConfig = config('phasing');
        $phasingConfig = $phasingConfig[$phaser->race->key] ?? [];

        return view('pages.dominion.phasing', [
            'phasingCalculator' => app(PhasingCalculator::class),
            'phasingConfig' => $phasingConfig,
            'unitCalculator' => app(UnitCalculator::class),
        ]);
    }

    public function postPhasing(PhasingRequest $request)
    {

        $phaser = $this->getSelectedDominion();
        $sourceUnit = Unit::fromKey($request->source_unit_key);
        $sourceUnitAmount = $request->source_unit_amount;
        $targetUnit = Unit::fromKey($request->target_unit_key);

        $phasingActionService = app(PhasingActionService::class);

        try {
            $result = $phasingActionService->phase($phaser, $sourceUnit, $sourceUnitAmount, $targetUnit);

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        #$request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()->to($result['redirect'] ?? route('dominion.theft'));

    }

    public function calculatePhasing(PhasingRequest $request)
    {

        $phaser = $this->getSelectedDominion();
        $sourceUnit = Unit::fromKey($request->source_unit);
        $sourceUnitAmount = $request->source_unit_amount;
        $targetUnit = Unit::fromKey($request->target_unit);

        $result = app(PhasingCalculator::class)->getPhasingCost($phaser, $sourceUnit, $sourceUnitAmount, $targetUnit);
    
        return response()->json($result);
    }
}
