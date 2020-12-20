<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class CastSpellRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'spell' => 'required',
            'target_dominion' => 'integer|exists:dominions,id',
            'friendly_dominion' => 'integer|exists:dominions,id',
        ];
    }
}
