<?php

namespace OpenDominion\Http\Requests\Dominion\API;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class ArtefactAttackCalculationRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'target_artefact' => 'nullable|integer|exists:artefacts,id',
            'unit' => 'nullable|array'
        ];
    }
}
