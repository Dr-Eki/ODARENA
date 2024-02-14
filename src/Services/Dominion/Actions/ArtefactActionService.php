<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Log;
use OpenDominion\Exceptions\GameException;

use OpenDominion\Models\Artefact;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmArtefact;
use OpenDominion\Models\Resource;

use OpenDominion\Traits\DominionGuardsTrait;

use OpenDominion\Helpers\ConversionHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Calculators\Dominion\ArtefactCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\DominionCalculator;
use OpenDominion\Calculators\Dominion\ConversionCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\ResourceConversionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\TerrainCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;

use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Dominion\ArtefactService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\GameEventService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Services\Dominion\TerrainService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;

class ArtefactActionService
{
    use DominionGuardsTrait;

    /**
     * @var int The minimum morale required to initiate an invasion
     */
    protected const MIN_MORALE = 100;

    /**
     * @var float Lowest possible DPA.
     */
    protected const MINIMUM_DPA = 10;

    /** @var array Invasion result array. todo: Should probably be refactored later to its own class */
    protected $attack = [
        'result' => [],
        'attacker' => [
            'damage_dealt' => 0,
            'units_lost' => [],
            'units_sent' => [],
        ],
        'artefact' => [
            'damage_suffered' => 0,
            'current_power' => 0,
            'max_power' => 0,
            'new_power' => 0,
        ],
    ];

    protected $attackEvent;

    // todo: refactor to use $invasionResult instead
    /** @var int The amount of land lost during the invasion */
    protected $landLost = 0;

    /** @var int The amount of units lost during the invasion */
    protected $unitsLost = 0;

    protected $invasionEvent;
    protected $isAmbush = false;

    private $artefactCalculator;
    private $artefactService;
    private $buildingCalculator;
    private $casualtiesCalculator;
    private $conversionCalculator;
    private $conversionHelper;
    private $dominionCalculator;
    private $improvementCalculator;
    private $improvementHelper;
    private $gameEventService;
    private $governmentService;
    private $landCalculator;
    private $magicCalculator;
    private $militaryCalculator;
    private $notificationService;
    private $statsService;
    private $queueService;
    private $raceHelper;
    private $rangeCalculator;
    private $resourceCalculator;
    private $resourceConversionCalculator;
    private $resourceService;
    private $spellActionService;
    private $spellCalculator;
    private $spellHelper;
    private $terrainCalculator;
    private $terrainService;
    private $trainingCalculator;
    private $unitHelper;

    public function __construct()
    {
        $this->artefactCalculator = app(ArtefactCalculator::class);
        $this->artefactService = app(ArtefactService::class);
        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->casualtiesCalculator = app(CasualtiesCalculator::class);
        $this->conversionCalculator = app(ConversionCalculator::class);
        $this->conversionHelper = app(ConversionHelper::class);
        $this->dominionCalculator = app(DominionCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->gameEventService = app(GameEventService::class);
        $this->governmentService = app(GovernmentService::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->magicCalculator = app(MagicCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->statsService = app(StatsService::class);
        $this->queueService = app(QueueService::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->resourceConversionCalculator = app(ResourceConversionCalculator::class);
        $this->resourceService = app(ResourceService::class);
        $this->spellActionService = app(SpellActionService::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->spellHelper = app(SpellHelper::class);
        $this->terrainCalculator = app(TerrainCalculator::class);
        $this->terrainService = app(TerrainService::class);
        $this->trainingCalculator = app(TrainingCalculator::class);
        $this->raceHelper = app(RaceHelper::class);
        $this->unitHelper = app(UnitHelper::class);
    }

    /**
     * Invades dominion $target from $attacker.
     *
     * @param Dominion $attacker
     * @param Dominion $target
     * @param array $units
     * @return array
     * @throws GameException
     */
    public function militaryAttack(Dominion $attacker, Realm $realm, Artefact $artefact, array $units): array
    {

        $this->guardLockedDominion($attacker);
        $this->guardActionsDuringTick($attacker);

        $now = time();

        DB::transaction(function () use ($attacker, $realm, $artefact, $units, $now) {

            // Checks
            $realmArtefact = RealmArtefact::where('realm_id', $realm->id)->where('artefact_id', $artefact->id)->first();

            if(!$realmArtefact)
            {
                throw new GameException("{$artefact->name} not found in realm #{$realm->number}. Check that the artefact is still in that realm.");
            }
    
            if(!$attacker->round->getSetting('invasions'))
            {
                throw new GameException('Invasions are disabled this round.');
            }

            if ($attacker->round->id !== $realm->round->id)
            {
                throw new GameException('Nice try, but you cannot invade cross-round.');
            }

            if ($attacker->realm->id === $realm->id)
            {
                throw new GameException('Nice try, but you cannot attack your own artefacts.');
            }

            if ($attacker->realm->getAllies()->contains($realm))
            {
                throw new GameException('You cannot invade artefacts in allied realms.');
            }

            $hostileDominionsInRange = $this->artefactCalculator->getQualifyingHostileDominionsInRange($attacker);
            $minimumHostileDominionsInRangeRequired = $this->artefactCalculator->getMinimumNumberOfDominionsInRangeRequired($attacker->round);
            if ($hostileDominionsInRange < $minimumHostileDominionsInRangeRequired) 
            {
                throw new GameException('You must have at least ' . number_format($hostileDominionsInRange->count()) . ' hostile ' . str_plural('dominion', $hostileDominionsInRange) . ' in range to be worthy of attacking the aegis. Fogged dominions and Barbarians do not count.');
            }
            
            foreach($attacker->race->resources as $resourceKey)
            {
                if($resourceCostToInvade = $attacker->race->getPerkValue($resourceKey . '_to_invade'))
                {
                    if($this->resourceCalculator->getAmount($attacker, $resourceKey) < $resourceCostToInvade)
                    {
                        $resource = Resource::where('key', $resourceKey)->first();
                        throw new GameException('You do not have enough ' . str_plural($resource->name, $resourceCostToInvade) . ' to invade. You have ' . number_format($this->resourceCalculator->getAmount($attacker, $resourceKey)) . ' and you need at least ' . number_format($resourceCostToInvade) . '.');
                    }
                    else
                    {
                        $this->resourceService->updateResources($attacker, [$resourceKey => $resourceCostToInvade*-1]);
                    }
                }
            }

            // Sanitize input
            $units = array_map('intval', array_filter($units));
            $landRatio = 1;

            if (!$this->hasAnyOP($attacker, $units))
            {
                throw new GameException('You need to send at least some units.');
            }

            if (!$this->allUnitsHaveOP($attacker, $units, $landRatio))
            {
                throw new GameException('You cannot send units that have no offensive power.');
            }

            if (!$this->hasEnoughUnitsAtHome($attacker, $units))
            {
                throw new GameException('You don\'t have enough units at home to send this many units.');
            }

            if ($attacker->morale < static::MIN_MORALE and !$attacker->race->getPerkValue('can_invade_at_any_morale'))
            {
                throw new GameException('You do not have enough morale to invade.');
            }

            if (!$this->passes43RatioRule($attacker, null, $landRatio, $units))
            {
                throw new GameException('You are sending out too much OP, based on your new home DP (4:3 rule).');
            }

            if (!$this->passesUnitSendableCapacityCheck($attacker, $units))
            {
                throw new GameException('You do not have enough caverns to send out this many units.');
            }

            foreach($units as $slot => $amount)
            {
                $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                if(!$this->unitHelper->isUnitSendableByDominion($unit, $attacker))
                {
                    throw new GameException('You cannot send ' . $unit->name . ' on invasion.');
                }

                if($amount < 0)
                {
                    throw new GameException('Invasion was canceled due to an invalid amount of ' . str_plural($unit->name, $amount) . '.');
                }

                # OK, unit can be trained. Let's check for pairing limits.
                if($this->unitHelper->unitHasCapacityLimit($attacker, $slot) and !$this->unitHelper->checkUnitLimitForInvasion($attacker, $slot, $amount))
                {

                    throw new GameException('You can at most control ' . number_format($this->unitHelper->getUnitMaxCapacity($attacker, $slot)) . ' ' . str_plural($unit->name) . '. To control more, you need to first have more of their superior unit.');
                }

                # Check for spends_resource_on_offense
                if($spendsResourcesOnOffensePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'spends_resource_on_offense'))
                {
                    $resourceKey = (string)$spendsResourcesOnOffensePerk[0];
                    $resourceAmount = (float)$spendsResourcesOnOffensePerk[1];
                    $resource = Resource::where('key', $resourceKey)->firstOrFail();

                    $resourceAmountRequired = ceil($resourceAmount * $amount);
                    $resourceAmountOwned = $this->resourceCalculator->getAmount($attacker, $resourceKey);

                    if($resourceAmountRequired > $resourceAmountOwned)
                    {
                        throw new GameException('You do not have enough ' . $resource->name . ' to attack to send this many ' . str_plural($unit->name, $amount) . '. You need ' . number_format($resourceAmountRequired) . ' but only have ' . number_format($resourceAmountOwned) . '.');
                    }
                }
             }

            if ($attacker->race->getPerkValue('cannot_attack_artefacts'))
            {
                throw new GameException($attacker->race->name . ' cannot attack artefacts.');
            }

            // Spell: Rainy Season (cannot invade)
            if ($this->spellCalculator->isSpellActive($attacker, 'rainy_season'))
            {
                throw new GameException('You cannot invade during the Rainy Season.');
            }
            if ($attacker->getSpellPerkValue('cannot_invade'))
            {
                throw new GameException('A spell is preventing from you invading.');
            }

            // Cannot invade until round has started.
            if(!$attacker->round->hasStarted())
            {
                throw new GameException('You cannot invade until the round has started.');
            }

            // Cannot invade after round has ended.
            if($attacker->round->hasEnded())
            {
                throw new GameException('You cannot invade after the round has ended.');
            }

            // Qur: Statis cannot invade.
            if($attacker->getSpellPerkValue('stasis'))
            {
                throw new GameException('You cannot invade while you are in stasis.');
            }

            // Firewalker: Flood The Gates.
            if($attacker->getSpellPerkValue('cannot_invade'))
            {
                if($attacker->race->name == 'Firewalker')
                {
                    throw new GameException('Your caverns are flooded, making it impossible for your units to attack.');
                }
                else
                {
                    throw new GameException('A magical state surrounds the lands, making it impossible for you to invade.');
                }
            }


            # Artillery: land gained plus current total land cannot exceed 133% of protector's land.
            if($attacker->race->name == 'Artillery' and $attacker->hasProtector())
            {
                $landGained = 0;

                $data['land_conquered'] = $this->militaryCalculator->getLandConquered($attacker, $target, $landRatio);
                $data['land_discovered'] = 0;
                if($this->militaryCalculator->checkDiscoverLand($attacker, $target, $data['land_conquered'], $this->attack['attacker']['capture_buildings']))
                {
                    $this->attack['data']['land_discovered'] = $data['land_conquered'] / ($target->race->name == 'Barbarian' ? 3 : 1);
                }
                $data['extra_land_discovered'] = $this->militaryCalculator->getExtraLandDiscovered($attacker, $target, $data['land_discovered'], $data['land_conquered']);
    

                $landGained += $data['land_conquered'];
                $landGained += $data['land_discovered'];
                $landGained += $data['extra_land_discovered'];
                
                $newLand = $attacker->land + $landGained;

                if($newLand > ($attacker->protector->land) * (4/3))
                {
                    throw new GameException('You cannot invade this target because your land gained plus current total land exceeds 133% of your protector\'s land.');
                }
            }

            # Sending more than 22,000 OP in the first 12 ticks
            if($attacker->round->ticks <= 12 and $this->militaryCalculator->getOffensivePower($attacker, null, 1, $units, [], true) > 22000)
            {
                throw new GameException('You cannot send more than 22,000 OP in a single invasion during the first 12 ticks of the round.');
            }
        
            $this->attack['attacker']['units_sent'] = $units;
            $this->attack['attacker']['fog'] = $attacker->getSpellPerkValue('fog_of_war') ? true : false;
            $this->attack['artefact']['name'] = $artefact->name;
            $this->attack['artefact']['key'] = $artefact->key;
            $this->attack['artefact']['current_realm_id'] = $realm->id;
            $this->attack['artefact']['current_realm_number'] = $realm->number;
            $this->attack['artefact']['current_power'] = $realmArtefact->power;
            $this->attack['artefact']['max_power'] = $realmArtefact->max_power;

            $this->attack['log']['initiated_at'] = $now;
            $this->attack['log']['requested_at'] = $_SERVER['REQUEST_TIME'];

            $this->handleDamage($attacker, $realmArtefact, $units);

            $this->handlePrestigeChanges($attacker, $realmArtefact, $units);
            #$this->handleDuringInvasionUnitPerks($attacker, $defender, $units);

            $this->handleMoraleChanges($attacker, $realmArtefact, $units);
            $this->handleXp($attacker, $realmArtefact, $units);

            $attackerCasualties = $this->casualtiesCalculator->getInvasionCasualties($attacker, $this->attack['attacker']['units_sent'], null, $this->attack, 'offense', true);

            $this->attack['attacker']['units_lost'] = $attackerCasualties;

            $this->handleCasualties($attacker);

            $this->handleReturningUnits($attacker, $this->attack['attacker']['units_surviving'], [], []);

            # Calculate bodies left behind
            $this->handleTheDead($attacker, $this->attack['attacker']['units_lost']);

            $this->handleStats($attacker, $realm, $artefact);

            $this->attack['log']['finished_at'] = time();
            $this->attack['log']['execution_duration'] = $this->attack['log']['finished_at'] - $this->attack['log']['requested_at'];
            $this->attack['log']['request_duration'] = $this->attack['log']['initiated_at'] - $this->attack['log']['requested_at'];

            ksort($this->attack);
            ksort($this->attack['artefact']);
            ksort($this->attack['attacker']);
            ksort($this->attack['log']);
            ksort($this->attack['result']);

            $this->attackEvent = GameEvent::create([
                'round_id' => $attacker->round_id,
                'source_type' => Dominion::class,
                'source_id' => $attacker->id,
                'target_type' => RealmArtefact::class,
                'target_id' => Realm::find($realm->id)->id,
                'type' => 'artefactattack',
                'data' => $this->attack,
                'tick' => $attacker->round->ticks
            ]);

            # Debug before saving:
            #ldd($this->attack); dd('Safety!');

            $attacker->save(['event' => HistoryService::EVENT_ACTION_ATTACK_ARTEFACT]);
        });
        
        if($this->attack['result']['shield_broken'])
        {
            $message = sprintf(
                'Your units deal %s damage to %s, breaking the aegis and capturing the artefact!',
                number_format($this->attack['attacker']['damage_dealt']),
                $artefact->name
            );
        }
        else
        {
            $message = sprintf(
                'Your units deal %s damage to %s.',
                number_format($this->attack['attacker']['damage_dealt']),
                $artefact->name
            );    
        }
 
        $alertType = 'success';
        
        return [
            'message' => $message,
            'alert-type' => $alertType,
            'redirect' => route('dominion.event', [$this->attackEvent->id])
        ];
    }

    protected function handlePrestigeChanges(Dominion $attacker, RealmArtefact $realmArtefact, array $units): void
    {

        $prestigeChange = 0;

        $damageRatio = $this->attack['attacker']['damage_dealt'] / $realmArtefact->max_power;

        $basePrestigeGain = $this->attack['attacker']['damage_dealt'] / 10000;

        $prestigeChange += $basePrestigeGain * (1 + pow(($damageRatio * 2), EXP($damageRatio)));

        $prestigeChangeMultiplier = 1;

        // Racial perk
        $prestigeChangeMultiplier += $attacker->race->getPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $this->militaryCalculator->getPrestigeGainsPerk($attacker, $units);
        $prestigeChangeMultiplier += $attacker->getAdvancementPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $attacker->getTechPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $attacker->getBuildingPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $attacker->getImprovementPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $attacker->getSpellPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $attacker->getDeityPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $attacker->realm->getArtefactPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $attacker->title->getPerkMultiplier('prestige_gains') * $attacker->getTitlePerkMultiplier();
        $prestigeChangeMultiplier += $attacker->getDecreePerkMultiplier('prestige_gains');

        # Monarch gains +10% always
        if($attacker->isMonarch())
        {
            $prestigeChangeMultiplier += 0.10;
        }

        $prestigeChange *= $prestigeChangeMultiplier;

        $prestigeChange *= $this->attack['result']['shield_broken'] ? 2 : 1;

        $prestigeChange = (int)floor($prestigeChange);

        $this->attack['attacker']['prestige_gained'] = $prestigeChange;

        $slowestTroopsReturnHours = $this->getSlowestUnitReturnHours($attacker, $units);

        $this->queueService->queueResources(
            'artefactattack',
            $attacker,
            ['prestige' => $prestigeChange],
            $slowestTroopsReturnHours
        );

    }

    public function handleDamage(Dominion $attacker, RealmArtefact $realmArtefact, array $units): void
    {
        $realm = $realmArtefact->realm;
        $artefact = $realmArtefact->artefact;

        $this->attack['result']['shield_broken'] = false;
        $baseDamage = 0;

        $this->attack['attacker']['op'] = $this->artefactCalculator->getDamageDealt($attacker, $units, $artefact);
        $this->attack['attacker']['op_raw'] = $this->militaryCalculator->getOffensivePowerRaw($attacker, null, 1, $units, [], false);
        
        $baseDamage = $this->attack['attacker']['op'];

        $baseDamage = (int)floor($baseDamage);
        
        $breaksShield = $baseDamage >= $realmArtefact->power;

        $netDamage = min($baseDamage, $realmArtefact->power);

        $this->attack['attacker']['base_damage'] = $baseDamage;

        $this->attack['attacker']['damage_dealt'] = $netDamage;

        if($breaksShield)
        {
            $this->attack['result']['shield_broken'] = true;
            
            # Remove artefact from current realm
            $realmArtefact->delete();

            # Queue for attacker's realm
            $this->queueService->queueResources(
                'artefact',
                $attacker,
                [$artefact->key => 1],
                12
            );
        }
        else
        {
            $this->attack['artefact']['damage_suffered'] = $netDamage;
            $this->attack['artefact']['new_power'] = $this->attack['artefact']['current_power'] - $this->attack['artefact']['damage_suffered'];

            $this->artefactService->updateRealmArtefactPower($realm, $artefact, $netDamage*-1);
        }


        $this->attack['attacker']['casualties_ratio_modifier'] = $this->attack['attacker']['damage_dealt'] / $this->attack['attacker']['op'];

    }


    /**
     * Handles casualties for a dominion
     *
     * Offensive casualties are 8.5% of the units needed to break the target,
     * regardless of how many you send.
     *
     * On unsuccessful invasions, offensive casualties are 8.5% of all units
     * you send, doubled if you are overwhelmed.
     *
     * @param Dominion $attacker
     * @param Dominion $target
     * @param array $units
     * @return array All the units that survived and will return home
     */
    protected function handleCasualties(Dominion $attacker): void
    {
        if($attacker->getTechPerkMultiplier('chance_of_immortality') and random_chance($attacker->getTechPerkMultiplier('chance_of_immortality')))
        {
            $this->attack['attacker']['units_immortal'] = true;
        }

        foreach ($this->attack['attacker']['units_lost'] as $slot => $amount)
        {
            $attacker->{"military_unit{$slot}"} -= $amount;
            $this->attack['attacker']['units_surviving'][$slot] = $this->attack['attacker']['units_sent'][$slot] - $this->attack['attacker']['units_lost'][$slot];

            if(in_array($slot,[1,2,3,4,5,6,7,8,9,10]))
            {
                $this->statsService->updateStat($attacker, ('unit' . $slot . '_lost'), $amount);
            }
            else
            {
                $this->statsService->updateStat($attacker, ($slot . '_lost'), $amount);
            }
        }
    }

    protected function handleMoraleChanges(Dominion $attacker, RealmArtefact $realmArtefact, array $units): void
    {

        $attackerMoraleChange = 0;

        $baseMoraleChange = 50;

        $attackerMoraleChange += $baseMoraleChange * ($this->attack['attacker']['damage_dealt'] / $realmArtefact->max_power);

        $attackerMoraleChangeMultiplier = 1;
        $attackerMoraleChangeMultiplier += $attacker->getBuildingPerkMultiplier('morale_gains');
        $attackerMoraleChangeMultiplier += $attacker->race->getPerkMultiplier('morale_change_invasion');
        $attackerMoraleChangeMultiplier += $attacker->title->getPerkMultiplier('morale_gains') * $attacker->getTitlePerkMultiplier();

        # Look for increases_morale_gains
        foreach($attacker->race->units as $unit)
        {
            if(
                $increasesMoraleGainsPerk = $attacker->race->getUnitPerkValueForUnitSlot($unit->slot, 'increases_morale_gains') and
                isset($units[$unit->slot])
                )
            {
                $attackerMoraleChangeMultiplier += ($this->attack['attacker']['units_sent'][$unit->slot] / array_sum($this->attack['attacker']['units_sent'])) * $increasesMoraleGainsPerk;
            }


            if(
                $increasesMoraleGainsPerk = $attacker->race->getUnitPerkValueForUnitSlot($unit->slot, 'increases_morale_gains_fixed') and
                isset($units[$unit->slot])
                )
            {
                $attackerMoraleChange += $this->attack['attacker']['units_sent'][$unit->slot] * $increasesMoraleGainsPerk;
            }
        }

        $attackerMoraleChange *= $attackerMoraleChangeMultiplier;

        # Look for no_morale_changes
        if($attacker->race->getPerkValue('no_morale_changes'))
        {
            $attackerMoraleChange = 0;
        }
        
        # Round
        $attackerMoraleChange = (int)round($attackerMoraleChange);

        # Change attacker morale.        
        $attacker->morale += $attackerMoraleChange;

        $this->attack['attacker']['morale_change'] = $attackerMoraleChange;

    }

    /**
     * Handles experience point (research point) generation for attacker.
     *
     * @param Dominion $attacker
     * @param array $units
     */
    protected function handleXp(Dominion $attacker, RealmArtefact $realmArtefact, array $units): void
    {
        $xpPerDamageDealt = 0.0632;

        $xpPerDamageDealtMultiplier = 1;
 
        # Increase RP per acre
        $xpPerDamageDealtMultiplier += $attacker->race->getPerkMultiplier('xp_gains');
        $xpPerDamageDealtMultiplier += $attacker->getImprovementPerkMultiplier('xp_gains');
        $xpPerDamageDealtMultiplier += $attacker->getBuildingPerkMultiplier('xp_gains');
        $xpPerDamageDealtMultiplier += $attacker->getSpellPerkMultiplier('xp_gains');
        $xpPerDamageDealtMultiplier += $attacker->getDeityPerkMultiplier('xp_gains');
        $xpPerDamageDealtMultiplier += $attacker->getDecreePerkMultiplier('xp_gains');

        $xpGained = $xpPerDamageDealt * $this->attack['attacker']['damage_dealt'];
        $xpGained *= $xpPerDamageDealtMultiplier;

        $xpGained = (int)floor($xpGained);

        $slowestTroopsReturnHours = $this->getSlowestUnitReturnHours($attacker, $units);

        $this->attack['attacker']['xp_gained'] = $xpGained;

        $this->queueService->queueResources(
            'artefactattack',
            $attacker,
            ['xp' => $xpGained],
            $slowestTroopsReturnHours
        );

    }

    # Unit Return 2.0
    protected function handleReturningUnits(Dominion $attacker, array $units, array $convertedUnits): void
    {
        # If instant return
        if(random_chance($attacker->getImprovementPerkMultiplier('chance_of_instant_return')) or $attacker->race->getPerkValue('instant_return') or $attacker->getSpellPerkValue('instant_return'))
        {
            $this->attack['attacker']['instantReturn'] = true;
        }
        # Normal return
        else
        {
            $returningUnits = [
                'military_spies' => array_fill(1, 12, 0),
                'military_wizards' => array_fill(1, 12, 0),
                'military_archmages' => array_fill(1, 12, 0),
            ];

            foreach($attacker->race->units as $unit)
            {
                $returningUnits['military_unit' . $unit->slot] = array_fill(1, 12, 0);
            }

            # Check for instant_return
            for ($slot = 1; $slot <= $attacker->race->units->count(); $slot++)
            {
                if($attacker->race->getUnitPerkValueForUnitSlot($slot, 'instant_return'))
                {
                    # This removes the unit from the $returningUnits array, thereby ensuring it is neither removed nor queued.
                    unset($returningUnits['military_unit' . $slot]);
                }
            }

            $someWinIntoUnits = array_fill(1, $attacker->race->units->count(), 0);

            foreach($returningUnits as $unitKey => $values)
            {
                $unitType = str_replace('military_', '', $unitKey);
                $slot = str_replace('unit', '', $unitType);
                $amountReturning = 0;

                $returningUnitKey = $unitKey;

                if(in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
                {

                    # Remove the units from attacker and add them to $amountReturning.
                    if (array_key_exists($slot, $units))
                    {
                        $attacker->$unitKey -= $units[$slot];
                        $amountReturning += $units[$slot];
                    }

                    # Check if we have conversions for this unit type/slot
                    if (array_key_exists($slot, $convertedUnits))
                    {
                        $amountReturning += $convertedUnits[$slot];
                    }

                    # Check if we have some winning into
                    if (array_key_exists($slot, $someWinIntoUnits))
                    {
                        $amountReturning += $someWinIntoUnits[$slot];
                    }

                    # Default return time is 12 ticks.
                    $ticks = $this->getUnitReturnTicksForSlot($attacker, $slot);

                    # Default all returners to tick 12
                    $returningUnits[$returningUnitKey][$ticks] += $amountReturning;

                    # Look for dies_into and variations amongst the dead attacking units.
                    if(isset($this->attack['attacker']['units_lost'][$slot]))
                    {
                        $casualties = $this->attack['attacker']['units_lost'][$slot];

                        if($diesIntoPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into'))
                        {
                            # Which unit do they die into?
                            $newUnitSlot = $diesIntoPerk[0];
                            $newUnitKey = "military_unit{$newUnitSlot}";
                            $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($attacker, $newUnitSlot);

                            $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += $casualties;
                        }

                        if($diesIntoPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_wizard'))
                        {
                            # Which unit do they die into?
                            $newUnitKey = "military_wizards";
                            $newUnitSlotReturnTime = 12;

                            $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += $casualties;
                        }

                        if($diesIntoPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_spy'))
                        {
                            # Which unit do they die into?
                            $newUnitKey = "military_spies";
                            $newUnitSlotReturnTime = 12;

                            $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += $casualties;
                        }

                        if($diesIntoPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_archmage'))
                        {
                            # Which unit do they die into?
                            $newUnitKey = "military_archmages";
                            $newUnitSlotReturnTime = 12;

                            $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += $casualties;
                        }

                        if($diesIntoPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_on_offense'))
                        {
                            # Which unit do they die into?
                            $newUnitSlot = $diesIntoPerk[0];
                            $newUnitKey = "military_unit{$newUnitSlot}";
                            $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($attacker, $newUnitSlot);

                            $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += $casualties;
                        }

                        if($diesIntoMultiplePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple'))
                        {
                            # Which unit do they die into?
                            $newUnitSlot = $diesIntoMultiplePerk[0];
                            $newUnitAmount = (float)$diesIntoMultiplePerk[1];
                            $newUnitKey = "military_unit{$newUnitSlot}";
                            $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($attacker, $newUnitSlot);

                            $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                        }

                        if($diesIntoMultiplePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_offense'))
                        {
                            # Which unit do they die into?
                            $newUnitSlot = $diesIntoMultiplePerk[0];
                            $newUnitAmount = (float)$diesIntoMultiplePerk[1];
                            $newUnitKey = "military_unit{$newUnitSlot}";
                            $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($attacker, $newUnitSlot);

                            $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                        }

                        # Check for faster_return_from_terrain
                        if($fasterReturnFromTerrainPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_from_terrain'))
                        {

                            $perChunk = $fasterReturnFromTerrainPerk[0];
                            $chunkSize = $fasterReturnFromTerrainPerk[1];
                            $terrainKey = $fasterReturnFromTerrainPerk[2];
                            $maxPerk = $fasterReturnFromTerrainPerk[3];

                            $ticksFaster = ($attacker->{'terrain_' . $terrainKey} / $attacker->land) * 100 / $chunkSize * $perChunk;
                            $ticksFaster = min($ticksFaster, $maxPerk);
                            $ticksFaster = floor($ticksFaster);

                            $fasterReturningTicks = min(max(1, ($ticks - $ticksFaster)), 12);

                            # How many of $slot should return faster?
                            $unitsWithFasterReturnTime = $amountReturning;

                            $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                            $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                        }

                        # Check for faster_return_from_time
                        if($fasterReturnFromTimePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_from_time'))
                        {

                            $hourFrom = $fasterReturnFromTimePerk[0];
                            $hourTo = $fasterReturnFromTimePerk[1];
                            if (
                                (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                                (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                            )
                            {
                                $ticksFaster = (int)$fasterReturnFromTimePerk[2];
                            }
                            else
                            {
                                $ticksFaster = 0;
                            }

                            $fasterReturningTicks = min(max(1, ($ticks - $ticksFaster)), 12);

                            # How many of $slot should return faster?
                            $unitsWithFasterReturnTime = $amountReturning;

                            $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                            $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                        }

                        # Check for faster_return from buildings
                        if($buildingFasterReturnPerk = $attacker->getBuildingPerkMultiplier('faster_return'))
                        {
                            $fasterReturn = min(max(0, $buildingFasterReturnPerk), 1);
                            $normalReturn = 1 - $fasterReturn;
                            $ticksFaster = 6;

                            $fasterReturningTicks = min(max(1, ($ticks - $ticksFaster), 12));

                            $unitsWithFasterReturnTime = round($amountReturning * $buildingFasterReturnPerk);
                            $unitsWithRegularReturnTime = round($amountReturning - $amountWithFasterReturn);

                            $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                            $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                        }

                        # Check for faster_return_units and faster_return_units_increasing from buildings
                        if($buildingFasterReturnPerk = $attacker->getBuildingPerkValue('faster_returning_units') or $buildingFasterReturnPerk = $attacker->getBuildingPerkValue('faster_returning_units_increasing'))
                        {
                            $fasterReturn = min(max(0, $buildingFasterReturnPerk), 1);
                            $normalReturn = 1 - $fasterReturn;
                            $ticksFaster = 4;

                            $fasterReturningTicks = min(max(1, ($ticks - $ticksFaster)), 12);

                            $unitsWithFasterReturnTime = min($buildingFasterReturnPerk, $amountReturning);
                            $unitsWithRegularReturnTime = round($amountReturning - $unitsWithFasterReturnTime);

                            $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                            $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                        }
                    }
                }
            }

            # Check for faster return from pairing perks
            foreach($returningUnits as $unitKey => $unitKeyTicks)
            {
                $unitType = str_replace('military_', '', $unitKey);
                $slot = str_replace('unit', '', $unitType);
                $amountReturning = 0;

                $returningUnitKey = $unitKey;

                if(in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
                {
                    $amountReturning = array_sum($returningUnits[$unitKey]);

                    # Check for faster_return_if_paired
                    if($fasterReturnIfPairedPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_if_paired'))
                    {
                        $pairedUnitSlot = (int)$fasterReturnIfPairedPerk[0];
                        $pairedUnitKey = 'military_unit'.$pairedUnitSlot;
                        $ticksFaster = (int)$fasterReturnIfPairedPerk[1];
                        $pairedUnitKeyReturning = array_sum($returningUnits[$pairedUnitKey]);

                        # Determine new return speed
                        $fasterReturningTicks = min(max($ticks - $ticksFaster, 1), 12);

                        # How many of $slot should return faster?
                        $unitsWithFasterReturnTime = min($pairedUnitKeyReturning, $amountReturning);
                        $unitsWithRegularReturnTime = max(0, $units[$slot] - $unitsWithFasterReturnTime);

                        $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                        $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                    }

                    # Check for faster_return_if_paired_multiple
                    if($fasterReturnIfPairedMultiplePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_if_paired_multiple'))
                    {
                        $pairedUnitSlot = (int)$fasterReturnIfPairedMultiplePerk[0];
                        $pairedUnitKey = 'military_unit'.$pairedUnitSlot;
                        $ticksFaster = (int)$fasterReturnIfPairedMultiplePerk[1];
                        $unitChunkSize = (int)$fasterReturnIfPairedMultiplePerk[2];
                        $pairedUnitKeyReturning = array_sum($returningUnits[$pairedUnitKey]);

                        # Determine new return speed
                        $fasterReturningTicks = min(max($ticks - $ticksFaster, 1), 12);

                        # How many of $slot should return faster?
                        $unitsWithFasterReturnTime = min($pairedUnitKeyReturning * $unitChunkSize, $amountReturning);
                        $unitsWithRegularReturnTime = max(0, $units[$slot] - $unitsWithFasterReturnTime);

                        $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                        $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                    }

                }
            }

            $this->attack['attacker']['units_returning_raw'] = $returningUnits;

            foreach($returningUnits as $unitKey => $unitKeyTicks)
            {
                foreach($unitKeyTicks as $unitTypeTick => $amount)
                {
                    if($amount > 0)
                    {
                        $this->queueService->queueResources(
                            'artefactattack',
                            $attacker,
                            [$unitKey => $amount],
                            $unitTypeTick
                        );
                    }
                }

                $slot = str_replace('military_unit', '', $unitKey);
                $this->attack['attacker']['units_returning'][$slot] = array_sum($unitKeyTicks);
            }
        }
    }

    protected function handleTheDead(Dominion $attacker, array $attackerUnitsLost): void
    {
        $bodies = 0;
        $bodiesRemovedFromConversions = 0;

        foreach($attackerUnitsLost as $slot => $amount)
        {
            if($this->conversionHelper->isSlotConvertible($slot, $attacker))
            {
                $amount *= (2/3);
                $bodies += (int)floor($amount);
            }
        }

        $this->attack['result']['bodies']['gross'] = $bodies;

        # Deduct from $bodies the number of bodies already ransacked/converted
        $bodies -= $bodiesRemovedFromConversions;
        $this->attack['result']['bodies']['removed_from_conversions'] = $bodiesRemovedFromConversions;

        $bodies = max(0, $bodies);

        # Update RoundResources
        if($bodies > 0)
        {
            $this->resourceService->updateRoundResources($attacker->round, ['body' => $bodies]);
        }

        $this->attack['result']['bodies']['net'] = $bodies;
    }

    public function handleStats(Dominion $attacker, Realm $real, Artefact $artefact): void
    {

        $attackerRawOpLost = $this->militaryCalculator->getOffensivePowerRaw($attacker, null, 1, $this->attack['attacker']['units_lost'], []);
        $attackerModOpLost = $this->militaryCalculator->getOffensivePower($attacker, null, 1, $this->attack['attacker']['units_lost'], []);

        $this->statsService->updateStat($attacker, 'artefacts_attacks', 1);
        $this->statsService->updateStat($attacker, 'artefacts_total_op_sent', $this->attack['attacker']['op']);

        $this->statsService->updateStat($attacker, 'raw_op_lost_total', $attackerRawOpLost);
        $this->statsService->updateStat($attacker, 'mod_op_lost_total', $attackerModOpLost);

        $this->statsService->setStat($attacker, 'op_sent_max', max($this->attack['attacker']['op'], $this->statsService->getStat($attacker, 'op_sent_max')));
        $this->statsService->updateStat($attacker, 'op_sent_total', $this->attack['attacker']['op']);

        if($this->attack['result']['shield_broken'])
        {
            $this->statsService->updateStat($attacker, 'artefacts_captured', 1);
        }

    }

    /**
     * Check if dominion is sending out at least *some* OP.
     *
     * @param Dominion $attacker
     * @param array $units
     * @return bool
     */
    protected function hasAnyOP(Dominion $attacker, array $units): bool
    {
        return ($this->militaryCalculator->getOffensivePower($attacker, null, null, $units) !== 0.0);
    }

    /**
     * Check if all units being sent have positive OP.
     *
     * @param Dominion $attacker
     * @param array $units
     * @return bool
     */
    protected function allUnitsHaveOP(Dominion $attacker, array $units, float $landRatio): bool
    {
        foreach ($attacker->race->units as $unit)
        {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0))
            {
                continue;
            }

            if ($this->militaryCalculator->getUnitPowerWithPerks($attacker, null, $landRatio, $unit, 'offense', null, $units, []) === 0.0 and $unit->getPerkValue('sendable_with_zero_op') != 1)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if dominion has enough units at home to send out.
     *
     * @param Dominion $attacker
     * @param array $units
     * @return bool
     */
    protected function hasEnoughUnitsAtHome(Dominion $attacker, array $units): bool
    {
        foreach ($attacker->race->units as $unit)
        {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0))
            {
                continue;
            }

            if ($units[$unit->slot] > $attacker->{'military_unit' . $unit->slot})
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if an invasion passes the 4:3-rule.
     *
     * @param Dominion $attacker
     * @param array $units
     * @return bool
     */
    protected function passes43RatioRule(Dominion $attacker, Dominion $target = null, float $landRatio, array $units): bool
    {
        # Artillery is exempt from 4:3.
        if($attacker->race->name == 'Artillery')
        {
            return true;
        }

        $unitsHome = [
            0 => $attacker->military_draftees,
        ];

        foreach($attacker->race->units as $unit)
        {
            $unitsHome[] = $attacker->{'military_unit'.$unit->slot} - (isset($units[$unit->slot]) ? $units[$unit->slot] : 0);
        }
        $attackingForceOP = $this->militaryCalculator->getOffensivePower($attacker, $target, $landRatio, $units);
        $newHomeForcesDP = $this->militaryCalculator->getDefensivePower($attacker, null, null, $unitsHome, 0, false, false, null, true); # The "true" at the end excludes raw DP from annexed dominions

        $attackingForceMaxOP = (int)ceil($newHomeForcesDP * (4/3));

        return ($attackingForceOP <= $attackingForceMaxOP);
    }

    protected function passesUnitSendableCapacityCheck(Dominion $attacker, array $units): bool
    {
        if(!$attacker->race->getPerkValue('caverns_required_to_send_units'))
        {
            return true;
        }

        $maxSendableUnits = $this->militaryCalculator->getMaxSendableUnits($attacker);

        return (array_sum($units) <= $maxSendableUnits);
    }

    /**
     * Returns the amount of hours a military unit (with a specific slot) takes
     * to return home after battle.
     *
     * @param Dominion $attacker
     * @param int $slot
     * @return int
     */
    protected function getUnitReturnHoursForSlot(Dominion $attacker, int $slot): int
    {
        $ticks = 12;

        $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
            return ($unit->slot === $slot);
        })->first();

        if ($unit->getPerkValue('faster_return'))
        {
            $ticks -= (int)$unit->getPerkValue('faster_return');
        }

        # Check for faster_return_if_paired
        if($fasterReturnFromWizardRatio = $attacker->race->getUnitPerkValueForUnitSlot($unit->slot, 'faster_return_from_wizard_ratio'))
        {
            $ticksFasterPerWizardRatio = (float)$fasterReturnFromWizardRatio[0];
            $maxFaster = (int)$fasterReturnFromWizardRatio[1];
            
            $ticksFaster = $this->magicCalculator->getWizardRatio($attacker, 'offense') * $ticksFasterPerWizardRatio;
            $ticksFaster = min($ticksFaster, $maxFaster);

            # Determine new return speed
            $ticks -= $ticksFaster;
        }

        return $ticks;
    }

    protected function getUnitReturnTicksForSlot(Dominion $attacker, int $slot): int
    {
        $ticks = 12;

        $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
            return ($unit->slot === $slot);
        })->first();

        $ticks -= (int)$unit->getPerkValue('faster_return');
        $ticks -= (int)$attacker->getSpellPerkValue('faster_return');
        $ticks -= (int)$attacker->getAdvancementPerkValue('faster_return');
        $ticks -= (int)$attacker->realm->getArtefactPerkValue('faster_return');

        if($fasterReturnFromWizardRatio = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_from_wizard_ratio'))
        {
            $ticksFasterPerWizardRatio = (float)$fasterReturnFromWizardRatio[0];
            $maxFaster = (int)$fasterReturnFromWizardRatio[1];
            
            $ticksFaster = $this->magicCalculator->getWizardRatio($attacker, 'offense') * $ticksFasterPerWizardRatio;
            $ticksFaster = min($ticksFaster, $maxFaster);

            # Determine new return speed
            $ticks -= $ticksFaster;
        }

        if($fasterReturnFromSpyRatio = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_from_spy_ratio'))
        {
            $ticksFasterPerWizardRatio = (float)$fasterReturnFromSpyRatio[0];
            $maxFaster = (int)$fasterReturnFromSpyRatio[1];
            
            $ticksFaster = $this->militaryCalculator->getSpyRatio($attacker, 'offense') * $fasterReturnFromSpyRatio;
            $ticksFaster = min($ticksFaster, $maxFaster);

            # Determine new return speed
            $ticks -= $ticksFaster;
        }

        return min(max(1, $ticks), 12);
    }

    /**
     * Gets the amount of hours for the slowest unit from an array of units
     * takes to return home.
     *
     * Primarily used to bring prestige home earlier if you send only 9hr
     * attackers. (Land always takes 12 hrs)
     *
     * @param Dominion $attacker
     * @param array $units
     * @return int
     */
    protected function getSlowestUnitReturnHours(Dominion $attacker, array $units): int
    {
        $hours = 12;

        foreach ($units as $slot => $amount) {
            if ($amount === 0) {
                continue;
            }

            $hoursForUnit = $this->getUnitReturnHoursForSlot($attacker, $slot);

            if ($hoursForUnit < $hours) {
                $hours = $hoursForUnit;
            }
        }

        return $hours;
    }

}
