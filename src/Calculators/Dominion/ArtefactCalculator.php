<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Artefact;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmArtefact;
use OpenDominion\Models\Round;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Services\Dominion\StatsService;

class ArtefactCalculator
{

    protected $militaryCalculator;
    protected $rangeCalculator;
    protected $statsService;
    
    public function __construct()
    {
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->statsService = app(StatsService::class);
    }

    public function getNewPower(Realm $realm, Artefact $artefact): int
    {
        $base = $artefact->base_power;
        $ticks = max($realm->round->ticks, 1);
        
        $aegis = $base * pow(pow(5, 1/1200), $ticks);

        return (int)round($aegis);
    }

    public function getDamageType(Dominion $dominion): string
    {
        return 'military';
    }
  
    public function getAegisRestoration(RealmArtefact $realmArtefact): int
    {
        $restoration = 0;

        $restoration += $realmArtefact->max_power * 0.25 / 100;
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
            $manaDrained = $realmDominion->getBuildingPerkValue('mana_upkeep_raw_per_artefact');
            $manaOwned = $realmDominion->resource_mana;
            $manaMultiplier = $manaDrained > $manaOwned ? $manaOwned / $manaDrained : 1;
    
            $restoration += $realmDominion->getBuildingPerkValue('artefact_shield_restoration') * $manaMultiplier;
            $restoration *= $realmDominion->getImprovementPerkMultiplier('artefact_shield_restoration_mod');
        }
    
        return $restoration;
    }

    public function getChanceToDiscoverArtefactOnExpedition(Dominion $dominion, array $expedition): float
    {

        if(!$expedition['land_discovered'])
        {
            return 0;
        }

        if(isLocal()) { return 1; }

        $chance = 0;

        $chance += ($dominion->round->ticks / 1344) * (($expedition['land_discovered'] - 2) / 10) * (1 + $this->statsService->getStat($dominion, 'artefacts_found') / 26);
        
        $chance *= $this->getChanceToDiscoverArtefactMultiplier($dominion);

        $chance = min(0.80, $chance);
        $chance = max(0.00, $chance);

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

    public function canAttackArtefacts(Dominion $dominion): bool
    {
        if($this->getNumberOfQualifyingHostileDominionsInRange($dominion) < $this->getMinimumNumberOfDominionsInRangeRequired($dominion->round))
        {
            return false;
        }

        return true;
    }

    public function getNumberOfQualifyingHostileDominionsInRange(Dominion $dominion): int
    {
        return $this->rangeCalculator->getDominionsInRange($dominion, true, true)->count(); # true = exclude fogged dominions, true = exclude Barbarians
    }

    public function getMinimumNumberOfDominionsInRangeRequired(Round $round): int
    {
        #$dominionsCount = $round->activeDominions()->count();
        # Exclude barbarians
        #$dominionsCount -= $round->activeDominions()->where('race.key','barbarian')->count();
        #return max(2, round($dominionsCount * 0.10));

        return 2;
    }

}
