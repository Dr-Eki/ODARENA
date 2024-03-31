<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

class MilitaryHelper
{

    public function __construct()
    {
        // ..
    }

    protected $terminology = [
        'growth' => ['label' => 'Grow', 'term' => 'growing'],
        'myconid' => ['label' => 'Grow', 'term' => 'growth'],
        'swarm' => ['label' => 'Hatch', 'term' => 'hatching'],
        'lux' => ['label' => 'Ascend', 'term' => 'ascension'],
        'void' => ['label' => 'Form', 'term' => 'forming'],
        'default' => ['label' => 'Train', 'term' => 'training'],
    ];

    public function getTrainingButtonLabel(Race $race)
    {
        return $this->terminology[$race->key]['label'] ?? $this->terminology['default']['label'];
    }

    public function getTrainingTerm(Race $race)
    {
        return $this->terminology[$race->key]['term'] ?? $this->terminology['default']['term'];
    }

}
