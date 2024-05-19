<?php

namespace OpenDominion\Http\Requests\Dominion\API;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class InsightBattleCalculatorRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'source_dominion' => 'nullable|integer',
            'target_dominion' => 'nullable|integer',
            'unit' => 'nullable|array',
            'calc' => 'nullable|array',
        ];
    }
}
