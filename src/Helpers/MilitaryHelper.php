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
        'growth' => ['label' => 'mutate', 'term' => 'mutation'],
        'myconid' => ['label' => 'grow', 'term' => 'growth'],
        'swarm' => ['label' => 'hatch', 'term' => 'hatching'],
        'lux' => ['label' => 'ascend', 'term' => 'ascension'],
        'void' => ['label' => 'form', 'term' => 'forming'],
        'default' => ['label' => 'train', 'term' => 'training'],
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
