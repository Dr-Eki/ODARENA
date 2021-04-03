<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class DemolishActionRequest extends AbstractDominionRequest
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /**
     * DestroyActionRequest constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->buildingHelper = app(BuildingHelper::class);
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [];

        foreach ($this->buildingHelper->getBuildingKeys() as $buildingKey)
        {
            $rules['demolish.' . $buildingKey] = 'integer|nullable|min:0';
        }

        return $rules;
    }
}
