<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

#use OpenDominion\Helpers\RaceHelper;

class MilitaryHelper
{

    public function __construct()
    {
    }

    public function getTrainingTerm(Race $race)
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

}
