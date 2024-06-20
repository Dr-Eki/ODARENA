<?php

namespace OpenDominion\Http\Requests\Dominion;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class HoldUnitsGiftRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            'hold' => 'required|integer|exists:holds,id',
            'dominion' => 'required|integer|exists:dominions,id',
            'units' => 'required|array'
        ];

        return $rules;
    }
}
