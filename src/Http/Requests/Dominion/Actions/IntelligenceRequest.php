<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class IntelligenceRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'type' => 'required',
            'operation' => 'required',
            'spell_dominion' => 'integer|exists:dominions,id',
            'espionage_dominion' => 'integer|exists:dominions,id',
        ];
    }
}
