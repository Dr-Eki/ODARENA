<?php

namespace OpenDominion\Services\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hold;
use OpenDominion\Models\TradeRoute;

class TradeRoutesService
{


    public function __construct()
    {
    }

    public function createTradeRoute(Dominion $trader, Hold $hold)
    {
        $tradeRoute = new TradeRoute();
        $tradeRoute->dominion_id = $trader->id;
        $tradeRoute->source_province_id = $hold->province_id;
        $tradeRoute->source_land_type = $hold->land_type;
        $tradeRoute->target_province_id = $hold->province_id;
        $tradeRoute->target_land_type = $hold->land_type;
        $tradeRoute->resource = $hold->resource;
        $tradeRoute->amount = $hold->amount;
        $tradeRoute->active = true;
        $tradeRoute->save();
    }

    public function changeHoldRelationship(Dominion $trader, Hold $hold, $amount)
    {
        $hold->amount -= $amount;
        $hold->save();

        $tradeRoute = new TradeRoute();
        $tradeRoute->dominion_id = $trader->id;
        $tradeRoute->source_province_id = $hold->province_id;
        $tradeRoute->source_land_type = $hold->land_type;
        $tradeRoute->target_province_id = $hold->province_id;
        $tradeRoute->target_land_type = $hold->land_type;
        $tradeRoute->resource = $hold->resource;
        $tradeRoute->amount = $amount;
        $tradeRoute->active = true;
        $tradeRoute->save();
    }

}
