<?php

namespace OpenDominion\Calculators\Dominion;

#use DB;
#use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OpenDominion\Helpers\ArtefactHelper;
use OpenDominion\Models\Artefact;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmArtefact;
use OpenDominion\Services\Dominion\StatsService;

class ArtefactCalculator
{

    protected $statsService;

    public function __construct()
    {
        $this->statsService = app(StatsService::class);
    }

    public function getNewPower(Realm $realm, Artefact $artefact): int
    {
        $base = $artefact->base_power;
        $power = $realm->round->ticks * (1000 * (1 + ($realm->round->ticks / 2000) + ($base / 1000000)));

        return max($base, $power);
    }

    public function getDamageType(Dominion $dominion): string
    {
        return 'military';
    }

    public function getShieldRestoration(RealmArtefact $realmArtefact): int
    {

        $artefact = $realmArtefact->artefact;
        $realm = $realmArtefact->realm;

        $restoration = 0;

        $restoration += $artefact->base_power / 1000;
        $restoration += $this->getRealmArtefactShieldRestoration($realm);

        return $restoration;
    }

    public function getRealmArtefactShieldRestoration(Realm $realm): int
    {
        $restoration = 0;

        foreach($realm->dominions as $realmDominion)
        {
            $restorationFromDominion = 0;
            $restorationFromDominion += $realmDominion->getBuildingPerkValue('artefact_shield_restoration');

            $restorationFromDominion *= $realmDominion->getImprovementPerkMultiplier('artefact_shield_restoration_mod');

            $restoration += $restorationFromDominion;
        }

        return $restoration;
    }

    public function getChanceToDiscoverArtefactOnExpedition(Dominion $dominion, array $expedition): float
    {

        if(!$expedition['land_discovered'])
        {
            return 0;
        }

        return 1;

        $chance = 0;

        $chance += ($dominion->round->ticks / 1344) * ($expedition['land_discovered'] / 50);

        $chance += $this->statsService->getStat($dominion, 'artefacts_found') / 50;
        
        $chance *= $this->getChanceToDiscoverArtefactMultiplier($dominion);

        return $chance;
    }

    public function getChanceToDiscoverArtefactOnInvasion(Dominion $dominion, array $invasion): float
    {
        $chance = 0;
        
        $chance *= $this->getChanceToDiscoverArtefactMultiplier($dominion);

        return $chance;
    }

    public function getChanceToDiscoverArtefactMultiplier(Dominion $dominion): float
    {
        $multiplier = 1.00;

        $multiplier += $dominion->getImprovementPerkMultiplier('chance_to_discover_artefacts');
        $multiplier += $dominion->getBuildingPerkMultiplier('chance_to_discover_artefacts');
        $multiplier += $dominion->getSpellPerkMultiplier('chance_to_discover_artefacts');
        $multiplier += $dominion->getAdvancementPerkMultiplier('chance_to_discover_artefacts');
        $multiplier += $dominion->race->getPerkMultiplier('chance_to_discover_artefacts');

        return $multiplier;
    }

}
