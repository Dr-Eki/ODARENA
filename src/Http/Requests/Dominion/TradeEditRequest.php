<?php

namespace OpenDominion\Http\Requests\Dominion;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class TradeEditRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'hold' => 'required|string|exists:holds,key',
            'resource' => 'required|string|exists:resources,key',  
        ];
    }
}
