<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class DesecrateActionRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        for ($i = 1; $i <= 10; $i++)
        {
            $rules['unit.' . $i] = 'integer|nullable|min:0';
        }

        return $rules;
    }
}
