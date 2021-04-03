<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class FriendlyOpsRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'type' => 'required',
            'spell' => 'required',
            'friendly_dominion' => 'integer|exists:dominions,id',
        ];
    }
}
