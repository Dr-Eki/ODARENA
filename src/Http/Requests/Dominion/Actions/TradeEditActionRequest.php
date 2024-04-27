<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class TradeEditActionRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'trade_route' => 'required|integer|exists:trade_routes,id',
            'sold_resource_amount' => 'required|integer|min:1',
        ];
    }
}
