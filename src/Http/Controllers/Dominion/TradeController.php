<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Session;

use Illuminate\Http\Request;

use OpenDominion\Exceptions\GameException;

use OpenDominion\Models\Hold;
use OpenDominion\Models\HoldSentimentEvent;
use OpenDominion\Models\Resource;
use OpenDominion\Models\TradeRoute;

use OpenDominion\Calculators\Hold\ResourceCalculator;
use OpenDominion\Calculators\Dominion\TradeCalculator;

use OpenDominion\Http\Requests\Dominion\TradeCalculationRequest;
use OpenDominion\Http\Requests\Dominion\TradeDeleteRequest;
use OpenDominion\Http\Requests\Dominion\Actions\TradeEditActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\TradeActionRequest;

use OpenDominion\Services\TradeCalculationService;
use OpenDominion\Services\Dominion\Actions\TradeActionService;


use OpenDominion\Helpers\HoldHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Traits\DominionGuardsTrait;

class TradeController extends AbstractDominionController
{
    use DominionGuardsTrait;

    protected $resourceCalculator;
    protected $tradeCalculator;

    public function __construct()
    {
        #$this->resourceCalculator = app(ResourceCalculator::class);
        #$this->tradeCalculator = app(TradeCalculator::class);
    }

    public function getTradeRoutes()
    {
        return view('pages.dominion.trade.routes', [
            'tradeCalculator' => app(TradeCalculator::class),
            'holdHelper' => app(HoldHelper::class),
        ]);
    }

    public function getTradesInProgress()
    {
        $dominion = $this->getSelectedDominion();

        $this->guardActionsDuringTick($dominion);

        $tradeCalculator = app(TradeCalculator::class);

        $tradeRoutesTickData = $tradeCalculator->getTradeRoutesTickData($dominion);

        return view('pages.dominion.trade.trades-in-progress', [
            'tradeCalculator' => $tradeCalculator,
            'tradeRoutesTickData' => $tradeRoutesTickData,
            'holdHelper' => app(HoldHelper::class),
        ]);
    }

    public function getHold(Hold $hold)
    {

        return view('pages.dominion.trade.hold', [
            'hold' => $hold,
            
            'resourceCalculator' => app(ResourceCalculator::class),
            'tradeCalculator' => app(TradeCalculator::class),

            'holdHelper' => app(HoldHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function getHolds()
    {
        $dominion = $this->getSelectedDominion();
        $holds = $dominion->round->holds;

        return view('pages.dominion.trade.holds', [
            'holds' => $holds,
            
            'resourceCalculator' => app(ResourceCalculator::class),
            'tradeCalculator' => app(TradeCalculator::class),

            'holdHelper' => app(HoldHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function getLedger()
    {
        $tradeLedgerEntries = $this->getSelectedDominion()->tradeLedger()->orderByDesc('created_at')->paginate(50);
        return view('pages.dominion.trade.ledger', ['tradeLedgerEntries' => $tradeLedgerEntries]);
    }

    public function getSentiments()
    {
        $dominion = $this->getSelectedDominion();
        $sentiments = $dominion->holdSentimentEvents()->orderByDesc('created_at')->paginate(50);

        return view('pages.dominion.trade.sentiments', ['sentiments' => $sentiments]);
    }

    public function getEditTradeRoute($hold, $resourceKey)
    {
        $resource = Resource::where('key', $resourceKey)->first();
    
        // Define the dominion
        $dominion = $this->getSelectedDominion();

        // Optionally handle the case where hold or resource doesn't exist
        if (!$hold || !$resource) {
            return redirect()->route('dominion.trade.routes');
        }
        
        $tradeRoute = TradeRoute::where('dominion_id',$dominion->id)
                                ->where('hold_id', $hold->id)
                                ->where('source_resource_id', $resource->id)
                                ->first();
        
        // Optionally handle the case where trade route doesn't exist or doesn't belong to this dominion
        if (!$tradeRoute || $tradeRoute->dominion_id !== $this->getSelectedDominion()->id) {
            return redirect()->route('dominion.trade.routes');
        }
    
        return view('pages.dominion.trade.edit-trade-route', [
            'tradeRoute' => $tradeRoute,
            'tradeCalculator' => app(TradeCalculator::class),
            'holdHelper' => app(HoldHelper::class),
        ]);
    }

    public function postDeleteTradeRoute(TradeDeleteRequest $request)
    {
        $tradeRoute = TradeRoute::findOrFail($request->get('trade_route'));

        #dd($tradeRoute);

        $tradeActionService = app(TradeActionService::class);

        try {
            $result = $tradeActionService->delete($tradeRoute);

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        return redirect()->to($result['redirect'] ?? route('dominion.trade.routes'));
    }

    public function postEditTradeRoute(TradeEditActionRequest $request)
    {
        $tradeRoute = TradeRoute::find($request->get('trade_route'));
        $soldResourceAmount = $request->get('sold_resource_amount');

        $tradeActionService = app(TradeActionService::class);

        try {
            $result = $tradeActionService->edit(
                $tradeRoute,
                $soldResourceAmount
            );

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        return redirect()->to($result['redirect'] ?? route('dominion.trade.routes'));
    }

    public function storeTradeDetails(TradeActionRequest $request)
    {
        Session::forget('trade_details'); // Clear any previous trade details
        Session::put('trade_details', $request->all());

        return redirect()->route('dominion.trade.routes.confirm-trade-route');
    }
    
    public function clearTradeDetails()
    {
        Session::forget('trade_details'); // Clear any previous trade details
        return redirect()->route('dominion.trade.routes');
    }

    public function getConfirmTradeRoute()
    {
        if(Session::has('trade_details'))
        {
            return view('pages.dominion.trade.confirm-trade-route',
            [
                'tradeDetails' => session('trade_details'),
                'tradeCalculator' => app(TradeCalculator::class),
                'holdHelper' => app(HoldHelper::class),
            ]);
        }
        else
        {
            return redirect()->route('dominion.trade.routes');
        }
    }

    public function postCreateTradeRoute(TradeActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $tradeActionService = app(TradeActionService::class);

        try {
            $result = $tradeActionService->create(
                $dominion,
                Hold::findOrFail($request->get('hold')),
                Resource::fromKey($request->get('sold_resource')),
                $request->get('sold_resource_amount'),
                Resource::fromKey($request->get('bought_resource'))
            );

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        return redirect()->to($result['redirect'] ?? route('dominion.trade.routes'));
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


