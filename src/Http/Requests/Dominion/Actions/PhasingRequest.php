<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class PhasingRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {

        $rules['source_unit_key'] = 'string|exists:units,key';
        $rules['source_unit_amount'] = 'integer|min:0';
        $rules['target_unit_key'] = 'string|exists:units,key';
        
        return $rules;
    }
}
