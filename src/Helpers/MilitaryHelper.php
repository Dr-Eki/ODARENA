<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

class MilitaryHelper
{

    public function __construct()
    {
    }

    public function getTrainingButtonLabel(Race $race)
    {
        switch ($race->key) {
            case 'growth':
                return 'Mutate';
            case 'myconid':
                return 'Grow';
            case 'swarm':
                return 'Hatch';
            case 'Lux':
                return 'Ascend';
            default:
                return 'Train';
            };
    }

    public function getTrainingTerm(Race $race)
    {
        switch ($race->key) {
            case 'growth':
                return 'mutation';
            case 'myconid':
                return 'growth';
            case 'swarm':
                return 'hatching';
            case 'Lux':
                return 'ascension';
            default:
                return 'training';
            };
    }

}
