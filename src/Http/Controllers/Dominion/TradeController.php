<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Models\Hold;

use OpenDominion\Calculators\Dominion\TradeCalculator;
use OpenDominion\Services\TradeCalculationService;
use OpenDominion\Http\Requests\Dominion\TradeCalculationRequest;

use OpenDominion\Helpers\HoldHelper;
use OpenDominion\Helpers\RaceHelper;


class TradeController extends AbstractDominionController
{
    public function getTradeRoutes()
    {
        return view('pages.dominion.trade.routes', [
            
            'tradeCalculator' => app(TradeCalculator::class),
            'holdHelper' => app(HoldHelper::class),
        ]);
    }

    public function getHold(Hold $hold)
    {
        return view('pages.dominion.trade.hold', [
            'hold' => $hold,
            
            'tradeCalculator' => app(TradeCalculator::class),

            'holdHelper' => app(HoldHelper::class),
            'raceHelper' => app(RaceHelper::class),
        ]);
    }

    public function calculateTradeRoute(TradeCalculationRequest $request, TradeCalculationService $tradeCalculationService)
    {
        $dominion = $this->getSelectedDominion();
        $holdId = $request->get('hold');
        $soldResourceKey = $request->get('sold_resource');
        $soldResourceAmount = $request->get('sold_resource_amount');
        $boughtResourceKey = $request->get('bought_resource');

        $result = $tradeCalculationService->calculate($dominion, $holdId, $soldResourceKey, $soldResourceAmount, $boughtResourceKey);

        #$result = $tradeCalculationService->calculate($request->all());
    
        return response()->json($result);
    }

}


