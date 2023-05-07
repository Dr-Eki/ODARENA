<?php

namespace OpenDominion\Calculators\Dominion;

#use DB;
#use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OpenDominion\Helpers\ArtefactHelper;
use OpenDominion\Models\Artefact;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;

class ArtefactCalculator
{
    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->artefactHelper = app(ArtefactHelper::class);
    }

    public function getNewPower(Realm $realm, Artefact $artefact): int
    {
        $base = $artefact->base_power;
        $power = $realm->round->ticks * (1000 * (1 + ($realm->round->ticks / 2000) + ($base / 1000000)));

        return max($base, $power);
    }

    public function getChanceToDiscoverArtefactOnExpedition(Dominion $dominion, array $expedition): float
    {

        if(!$expedition['land_discovered'])
        {
            return 0;
        }
        
        return 1;

        return log($dominion->round->ticks) / 10 * $expedition['land_discovered'] / 100;
    }

    public function getChanceToDiscoverArtefactOnInvasion(Dominion $dominion, array $invasion): float
    {
        $chance = 0;



        return $chance;
    }

}
