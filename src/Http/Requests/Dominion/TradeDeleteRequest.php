<?php

namespace OpenDominion\Http\Requests\Dominion;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class TradeDeleteRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'trade_route' => 'required|int|exists:trade_routes,id',
        ];
    }
}
