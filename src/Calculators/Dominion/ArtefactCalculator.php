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
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Services\Dominion\StatsService;

class ArtefactCalculator
{

    protected $militaryCalculator;
    protected $statsService;
    
    public function __construct()
    {
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->statsService = app(StatsService::class);
    }

    public function getNewPower(Realm $realm, Artefact $artefact): int
    {
        $base = $artefact->base_power;
        $aegis = $realm->round->ticks * (1000 * (1 + ($realm->round->ticks / 2000) + ($base / 1000000)));

        return max($base, $aegis);
    }

    public function getDamageType(Dominion $dominion): string
    {
        return 'military';
    }

    public function getAegisRestoration(RealmArtefact $realmArtefact): int
    {
        $restoration = 0;

        $restoration += $realmArtefact->max_power * 0.10 / 100;
        $restoration += $this->getRealmArtefactAegisRestoration($realmArtefact->realm);

        # Restoration plus power cannot exceed max power, cap restoration
        $restoration = min($restoration, $realmArtefact->max_power - $realmArtefact->power);

        return $restoration;
    }

    public function getRealmArtefactAegisRestoration(Realm $realm): int
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

        $chance += ($dominion->round->ticks / 1344) * ($expedition['land_discovered'] / 10);

        $chance += $this->statsService->getStat($dominion, 'artefacts_found') / 25;
        
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

    public function getDamageDealt(Dominion $attacker, array $units, Artefact $artefact = null): int
    {
        $damage = 0;

        $damage += $this->militaryCalculator->getOffensivePower($attacker, null, 1, $units, [], false);

        $damage *= $this->getDamageDealtMultiplier($attacker, $artefact);

        return $damage;
    }

    public function getDamageDealtMultiplier(Dominion $attacker, Artefact $artefact = null): float
    {
        $multiplier = 1.00;

        $multiplier += $attacker->getImprovementPerkMultiplier('artefacts_damage_dealt');
        $multiplier += $attacker->getSpellPerkMultiplier('artefacts_damage_dealt');

        if($artefact)
        {
            if($attacker->hasDeity() and isset($artefact->deity))
            {
                if($artefact->deity->id == $attacker->deity->id)
                {
                    $multiplier += 0.2;
                }
            }
        }

        return $multiplier;
    }

}
