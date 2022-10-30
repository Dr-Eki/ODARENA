<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class ResearchActionRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'tech_id' => 'required|integer|exists:techs,id',
        ];
    }
}
