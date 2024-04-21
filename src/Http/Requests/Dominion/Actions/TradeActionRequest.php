<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class TradeActionRequest extends AbstractDominionRequest
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
            'bought_resource' => 'required|string|exists:resources,key',
            'sold_resource_amount' => 'required|integer|min:1',
        ];
    }
}
