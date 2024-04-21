<?php

namespace OpenDominion\Http\Requests\Dominion;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class TradeCalculationRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {

        # Sold = sold by the player (dominion)
        # Bought = bought by the player (dominion)

        return [
            'hold' => 'required|integer|exists:holds,id',
            'sold_resource' => 'required|string|exists:resources,key',  
            'sold_resource_amount' => 'required|integer|min:1',
            'bought_resource' => 'required|string|exists:resources,key',
        ];
    }
}
