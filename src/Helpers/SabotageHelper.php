<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Models\Race;
use OpenDominion\Models\Spyop;

use OpenDominion\Calculators\Dominion\EspionageCalculator;

class SabotageHelper
{
    public function __construct()
    {
        $this->espionageCalculator = app(EspionageCalculator::class);
        $this->raceHelper = app(RaceHelper::class);
    }

    public function getSabotageOperationsForRace(Race $race)
    {
        $spyops = Spyop::all()->where('scope','hostile')->where('enabled',1)->sortBy('name');

        foreach($spyops as $key => $spyop)
        {
            if(!$this->espionageCalculator->isSpyopAvailableToRace($race, $spyop))
            {
                $spyops->forget($key);
            }
        }

        return $spyops;
    }

    public function getDamageTypeString(string $damageType, Race $targetRace, int $damage = 2)
    {
        switch($damageType)
        {
            case 'peasants':
                return str_plural($this->raceHelper->getPeasantsTerm($targetRace), $damage) . ' killed';
            
            case 'draftees':
            case 'military_draftees':
                return str_plural($this->raceHelper->getDrafteesTerm($targetRace), $damage) . ' killed';

            case 'convert_peasants_to_vampires_unit1':
                return 'New Servants';
                
            default:
                return ucwords($damageType);
        }
    }

}
