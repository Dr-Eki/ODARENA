<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Session;

use Illuminate\Http\Request;

use OpenDominion\Exceptions\GameException;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hold;
use OpenDominion\Models\Resource;
#use OpenDominion\Models\TradeLedger;
use OpenDominion\Models\TradeRoute;

use OpenDominion\Calculators\HoldCalculator;
use OpenDominion\Calculators\Hold\ResourceCalculator;
use OpenDominion\Calculators\Dominion\TradeCalculator;

use OpenDominion\Http\Requests\Dominion\HoldUnitsGiftRequest;
use OpenDominion\Http\Requests\Dominion\TradeCalculationRequest;
use OpenDominion\Http\Requests\Dominion\TradeDeleteRequest;
use OpenDominion\Http\Requests\Dominion\HoldUnitsGiftCalculationRequest;
use OpenDominion\Http\Requests\Dominion\Actions\TradeEditActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\TradeActionRequest;

use OpenDominion\Services\TradeCalculationService;
use OpenDominion\Services\Dominion\Actions\TradeActionService;
use OpenDominion\Services\Dominion\UnitService;

use OpenDominion\Helpers\HoldHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Traits\DominionGuardsTrait;

class TradeController extends AbstractDominionController
{
    use DominionGuardsTrait;


    public function __construct()
    {
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

        $dominion = $this->getSelectedDominion();
        if($hold->round->id !== $dominion->round->id)
        {
            xtLog("[{$dominion->id}] Dominion tried to access an out-of-round hold [{$hold->id}].");
            return redirect()->route('dominion.trade.routes');
        }

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

    public function getHoldLedger(Hold $hold)
    {
        $viewer = $this->getSelectedDominion();
        $holdTradeLedgerEntries = $hold->tradeLedger()
            ->join('dominions', 'trade_ledger.dominion_id', '=', 'dominions.id')
            ->join('realms', 'dominions.realm_id', '=', 'realms.id')
            ->where('dominions.realm_id', $viewer->realm->id)
            ->orderByDesc('trade_ledger.created_at')
            ->paginate(50);
            
        return view('pages.dominion.trade.hold.ledger', [
            'holdTradeLedgerEntries' => $holdTradeLedgerEntries,
            'hold' => $hold,
        ]);
    }

    public function getHoldSentiments(Hold $hold, Request $request)
    {
        $viewer = $this->getSelectedDominion();
        $query = $hold->sentimentEvents()
            ->select('hold_sentiment_events.*', 'dominions.name as dominion_name', 'realms.number as realm_number')
            ->join('dominions', 'hold_sentiment_events.target_id', '=', 'dominions.id')
            ->join('realms', 'dominions.realm_id', '=', 'realms.id')
            ->where('dominions.realm_id', $viewer->realm->id)
            ->where('hold_sentiment_events.target_type', get_class($viewer));
    
        // Search filter for dominion name or description
        if ($request->has('search') && $request->search !== null) {
            $query->where(function ($query) use ($request) {
                $query->where('dominions.name', 'like', '%' . $request->search . '%')
                      ->orWhere('hold_sentiment_events.description', 'like', '%' . $request->search . '%');
            });
        }
    
        $holdSentimentEvents = $query->orderByDesc('hold_sentiment_events.created_at')
                                     ->paginate(50);
    
        return view('pages.dominion.trade.hold.sentiments', [
            'holdSentimentEvents' => $holdSentimentEvents,
            'hold' => $hold,
            'holdHelper' => app(HoldHelper::class),
            'search' => $request->search // Passing back the search term to the view
        ]);
    }

    public function getHoldGiveUnits(Hold $hold)
    {
        return view('pages.dominion.trade.hold.give-units',[
            'hold' => $hold
        ]);
    }

    public function calculateUnitsGift(HoldUnitsGiftCalculationRequest $request, HoldCalculator $holdCalculator)
    {
        $dominion = $this->getSelectedDominion();
        $hold = Hold::where('id', $request->get('hold'))->firstOrFail();
        $units = $request->get('units');

        $result = $holdCalculator->calculateUnitsSentimentValue($hold, $dominion, $units);
    
        return response()->json(['sentiment' => $result]);
    }

    public function postHoldGiveUnits(HoldUnitsGiftRequest $request)
    {

        $dominion = $this->getSelectedDominion();
        $hold = Hold::where('id', $request->get('hold'))->firstOrFail();
        $units = $request->get('units');

        try {
            $result = app(UnitService::class)->sendUnitsToHold($dominion, $hold, $units);

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        return redirect()->to($result['redirect'] ?? route('dominion.trade.hold', $hold));
    }

    public function getEditTradeRoute($tradeRoute)
    {
        $tradeRoute = TradeRoute::find((int)$tradeRoute);
    
        // Define the dominion
        $dominion = $this->getSelectedDominion();

        // Optionally handle the case where hold or resource doesn't exist
        if (!$tradeRoute or $tradeRoute->dominion_id !== $dominion->id) {
            return redirect()->route('dominion.trade.routes');
        }
        
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


