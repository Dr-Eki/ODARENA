<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Calculators\Dominion\GovernmentCalculator;
use OpenDominion\Models\Race;
#use OpenDominion\Models\GameEvent;

class GovernmentHelper
{
    // These races are permitted to be protectors.
    public function getProtectorRaces(): Collection
    {
        #return collect('Black Orc','Dark Elf','Icekin','Legion','Orc','Reptilians');
        return Race::whereIn('name',['Black Orc', 'Dark Elf', 'Orc', 'Reptilians'])->get();

    }

    // These factions can be under protectorship.
    public function getProtectorshipProtectedRaces(): Collection
    {
        #return collect('Artillery');
        return Race::where('name','Artillery')->get();
    }

}
