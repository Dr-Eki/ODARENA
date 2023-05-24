<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Log;
use OpenDominion\Exceptions\GameException;

use OpenDominion\Models\Dominion;
#use OpenDominion\Models\DominionSpell;
#use OpenDominion\Models\Building;
use OpenDominion\Models\GameEvent;
#use OpenDominion\Models\GameEventStory;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Spell;
#use OpenDominion\Models\WatchedDominion;

use OpenDominion\Traits\DominionGuardsTrait;

use OpenDominion\Helpers\ConversionHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\DominionCalculator;
use OpenDominion\Calculators\Dominion\ConversionCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\ResourceConversionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\TerrainCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;

use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\GameEventService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Services\Dominion\TerrainService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;

class InvadeActionService
{
    use DominionGuardsTrait;

    /**
     * @var int The minimum morale required to initiate an invasion
     */
    protected const MIN_MORALE = 50;

    /**
     * @var float Failing an invasion by this percentage (or more) results in 'being overwhelmed'
     */
    protected const OVERWHELMED_PERCENTAGE = 20.0;

    /**
     * @var float Percentage of units to be stunned
     */
    protected const STUN_RATIO = 1;

    /**
     * @var float Lowest possible DPA.
     */
    protected const MINIMUM_DPA = 1;

    /** @var array Invasion result array. todo: Should probably be refactored later to its own class */
    protected $invasion = [
        'result' => [],
        'attacker' => [
            'units_lost' => [],
            'units_sent' => [],
        ],
        'defender' => [
            'units_defending' => [],
            'units_lost' => [],
        ],
    ];

    // todo: refactor to use $invasionResult instead
    /** @var int The amount of land lost during the invasion */
    protected $landLost = 0;

    /** @var int The amount of units lost during the invasion */
    protected $unitsLost = 0;

    protected $invasionEvent;
    protected $isAmbush = false;

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
    public function invade(Dominion $attacker, Dominion $target, array $units): array
    {
        $this->guardLockedDominion($attacker);
        $this->guardActionsDuringTick($attacker);
        $this->guardLockedDominion($target);

        if($attacker->race->name !== 'Barbarian')
        {
            $this->guardActionsDuringTick($target);
        }

        if($target->hasProtector())
        {
            $this->invasion['is_protectorate'] = true;
            $this->invasion['protectorate']['protector_id'] = $target->protector->id;
            $this->invasion['protectorate']['protected_id'] = $target->id;

            $defender = $target->protector;
            $this->guardLockedDominion($defender);

            if($attacker->race->name !== 'Barbarian')
            {
                $this->guardActionsDuringTick($defender);
            }
        }
        else
        {
            $defender = $target;
        }

        $now = time();

        DB::transaction(function () use ($attacker, $target, $defender, $units, $now) {

            // Checks
            if(!$attacker->round->getSetting('invasions'))
            {
                throw new GameException('Invasions are disabled this round.');
            }

            if ($attacker->protection_ticks > 0)
            {
                throw new GameException('You cannot invade while under protection.');
            }

            if ($target->protection_ticks > 0)
            {
                throw new GameException('You cannot invade dominions which are under protection.');
            }

            if (!$this->rangeCalculator->isInRange($attacker, $target))
            {
                throw new GameException('You cannot invade dominions outside of your range.');
            }

            if ($attacker->round->id !== $target->round->id)
            {
                throw new GameException('Nice try, but you cannot invade cross-round.');
            }

            if ($attacker->realm->id === $target->realm->id and (in_array($attacker->round->mode, ['standard','standard-duration','factions','factions-duration'])))
            {
                throw new GameException('You can only invade other dominions in the same realm in deathmatch rounds.');
            }

            if ($attacker->realm->getAllies()->contains($target->realm))
            {
                throw new GameException('You cannot invade dominions in allied realms.');
            }

            if ($attacker->id == $target->id)
            {
                throw new GameException('Nice try, but you cannot invade yourself.');
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
            $landRatio = $this->rangeCalculator->getDominionRange($attacker, $target);
            $this->invasion['land_ratio'] = $landRatio;
            $landRatio /= 100;

            if (!$this->hasAnyOP($attacker, $units))
            {
                throw new GameException('You need to send at least some units.');
            }

            if (!$this->allUnitsHaveOP($attacker, $units, $target, $landRatio))
            {
                throw new GameException('You cannot send units that have no offensive power.');
            }

            if (!$this->hasEnoughUnitsAtHome($attacker, $units))
            {
                throw new GameException('You don\'t have enough units at home to send this many units.');
            }

            if ($attacker->race->name !== 'Barbarian')
            {
                if ($attacker->morale < static::MIN_MORALE)
                {
                    throw new GameException('You do not have enough morale to invade.');
                }

                if (!$this->passes43RatioRule($attacker, $defender, $landRatio, $units))
                {
                    throw new GameException('You are sending out too much OP, based on your new home DP (4:3 rule).');
                }

                if (!$this->passesMinimumDpaCheck($attacker, $defender, $landRatio, $units))
                {
                    throw new GameException('You are sending less than the lowest possible DP of the target. Minimum DPA (Defense Per Acre) is ' . static::MINIMUM_DPA . '. Double check your calculations and units sent.');
                }

                if (!$this->passesUnitSendableCapacityCheck($attacker, $units))
                {
                    throw new GameException('You do not have enough caverns to send out this many units.');
                }
            }

            # Populate units defending
            for ($slot = 1; $slot <= $defender->race->units->count(); $slot++)
            {
                $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                  if($this->militaryCalculator->getUnitPowerWithPerks($defender, null, null, $unit, 'defense') !== 0.0)
                  {
                      $this->invasion['defender']['units_defending'][$slot] = $defender->{'military_unit'.$slot};
                  }

                  $this->invasion['defender']['units_defending']['draftees'] = $defender->military_draftees;
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

            if ($attacker->race->getPerkValue('cannot_invade'))
            {
                throw new GameException($attacker->race->name . ' cannot invade other dominions.');
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

            // Qur: Statis cannot be invaded.
            if($target->getSpellPerkValue('stasis'))
            {
                throw new GameException('A magical stasis surrounds the Qurrian lands, making it impossible for your units to invade.');
            }

            // Qur: Statis cannot invade.
            if($attacker->getSpellPerkValue('stasis'))
            {
                throw new GameException('You cannot invade while you are in stasis.');
            }

            // Firewalker: Flood The Gates.
            if($target->getSpellPerkValue('cannot_be_invaded'))
            {
                if($target->race->name == 'Firewalker')
                {
                    throw new GameException('The Firewalkers have flooded the caverns, making it impossible for your units to invade.');
                }
                else
                {
                    throw new GameException('A magical state surrounds the lands, making it impossible for your units to invade.');
                }
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
                if($this->militaryCalculator->checkDiscoverLand($attacker, $target, $data['land_conquered']))
                {
                    $this->invasion['data']['land_discovered'] = $data['land_conquered'] / ($target->race->name == 'Barbarian' ? 3 : 1);
                }
                $data['extra_land_discovered'] = $this->militaryCalculator->getExtraLandDiscovered($attacker, $target, $data['land_discovered'], $data['land_conquered']);
    

                $landGained += $data['land_conquered'];
                $landGained += $data['land_discovered'];
                $landGained += $data['extra_land_discovered'];
                
                $newLand = $this->landCalculator->getTotalLand($attacker) + $landGained;

                if($newLand > ($this->landCalculator->getTotalLand($attacker->protector) * (4/3)))
                {
                    throw new GameException('You cannot invade this target because your land gained plus current total land exceeds 133% of your protector\'s land.');
                }
            }
        
            $this->invasion['defender']['recently_invaded_count'] = $this->militaryCalculator->getRecentlyInvadedCount($defender);
            $this->invasion['attacker']['units_sent'] = $units;
            $this->invasion['attacker']['land_size'] = $this->landCalculator->getTotalLand($attacker);
            $this->invasion['defender']['land_size'] = $this->landCalculator->getTotalLand($target);

            $this->invasion['attacker']['fog'] = $attacker->getSpellPerkValue('fog_of_war') ? true : false;
            $this->invasion['defender']['fog'] = $defender->getSpellPerkValue('fog_of_war') ? true : false;

            $this->invasion['attacker']['conversions'] = array_fill(1, $attacker->race->units->count(), 0);
            $this->invasion['defender']['conversions'] = array_fill(1, $defender->race->units->count(), 0);

            $this->invasion['log']['initiated_at'] = $now;
            $this->invasion['log']['requested_at'] = $_SERVER['REQUEST_TIME'];

            $this->invasion['attacker']['show_of_force'] = false;
            if($attacker->race->name == 'Legion' and $attacker->getDecreePerkValue('show_of_force_invading_annexed_barbarian') and $target->race->name == 'Barbarian' and $this->spellCalculator->isAnnexed($target))
            {
                $this->invasion['attacker']['show_of_force'] = true;
            }

            // Handle pre-invasion
            $this->handleBeforeInvasionPerks($attacker);

            // Handle invasion results
            $this->checkInvasionSuccess($attacker, $defender, $units);
            $this->checkOverwhelmed();

            $attackerCasualties = $this->casualtiesCalculator->getInvasionCasualties($attacker, $this->invasion['attacker']['units_sent'], $target, $this->invasion, 'offense');
            $defenderCasualties = $this->casualtiesCalculator->getInvasionCasualties($defender, $this->invasion['defender']['units_defending'], $attacker, $this->invasion, 'defense');

            $this->invasion['attacker']['units_lost'] = $attackerCasualties;
            $this->invasion['defender']['units_lost'] = $defenderCasualties;

            $this->handleCasualties($attacker, $target, $this->invasion['attacker']['units_lost'], 'offense');
            $this->handleCasualties($defender, $attacker, $this->invasion['defender']['units_lost'], 'defense');
            $this->handleDefensiveDiesIntoPerks($defender);

            $this->handleAnnexedDominions($attacker, $defender, $units);

            # Only count successful, non-in-realm hits over 75% as victories.
            $countsAsVictory = 0;
            $countsAsFailure = 0;
            $countsAsRaze = 0;
            $countsAsBottomfeed = 0;

            # Successful hits over 75% count as victories
            if($landRatio >= 0.75 and $this->invasion['result']['success'])
            {
                $countsAsVictory = 1;
            }

            # Successful hits under 75% count as BFs
            if($landRatio < 0.75 and $this->invasion['result']['success'])
            {
                $countsAsBottomfeed = 1;
            }

            # Overwhelmed hits count as failures
            if($this->invasion['result']['overwhelmed'])
            {
                $countsAsFailure = 1;
            }

            # Non-overwhelmed unsuccessful hits count as tactical razes
            if(!$this->invasion['result']['overwhelmed'] and !$this->invasion['result']['success'])
            {
                $countsAsRaze = 1;
            }

            $this->handlePrestigeChanges($attacker, $defender, $units, $landRatio, $countsAsVictory, $countsAsBottomfeed, $countsAsFailure, $countsAsRaze);
            $this->handleDuringInvasionUnitPerks($attacker, $defender, $units);

            $this->handleMoraleChanges($attacker, $defender, $landRatio, $units);
            $this->handleLandGrabs($attacker, $target, $landRatio);
            $this->handleDeathmatchGovernorshipChanges($attacker, $defender);
            $this->handleXp($attacker, $defender, $units);

            # Dwarg
            $this->handleStun($attacker, $defender, $units, $landRatio);

            # Demon
            $this->handlePeasantCapture($attacker, $defender, $units, $landRatio);

            # Demon
            $this->handlePeasantKilling($attacker, $defender, $units, $landRatio);

            # Monster
            $this->handleStrengthGain($attacker, $defender, $units, $landRatio);

            # Conversions
            $offensiveConversions = array_fill(1, $attacker->race->units->count(), 0);
            $defensiveConversions = array_fill(1, $defender->race->units->count(), 0);

            $conversions = $this->conversionCalculator->getConversions($attacker, $defender, $this->invasion, $landRatio);

            if(array_sum($conversions['attacker']) > 0)
            {
                $offensiveConversions = $conversions['attacker'];
                $this->invasion['attacker']['conversions'] = $offensiveConversions;
                $this->statsService->updateStat($attacker, 'units_converted', array_sum($conversions['attacker']));
            }
            if(array_sum($conversions['defender']) > 0)
            {
                $defensiveConversions = $conversions['defender'];
                $this->invasion['defender']['conversions'] = $defensiveConversions;
                $this->statsService->updateStat($defender, 'units_converted', array_sum($conversions['defender']));
            }

            if($attacker->race->name == 'Cult')
            {
                $this->handlePsionicConversions($attacker, $defender, 'offense');
            }
            elseif($defender->race->name == 'Cult')
            {
                $this->handlePsionicConversions($defender, $attacker, 'defense');
            }

            # Resource conversions
            $resourceConversions['attacker'] = $this->resourceConversionCalculator->getResourceConversions($attacker, $defender, $this->invasion, 'offense');
            $resourceConversions['defender'] = $this->resourceConversionCalculator->getResourceConversions($defender, $attacker, $this->invasion, 'defense');
            
            #dump($resourceConversions);

            if(array_sum($resourceConversions['attacker']) > 0)
            {
                $this->invasion['attacker']['resource_conversions'] = $resourceConversions['attacker'];
                $this->handleResourceConversions($attacker, 'offense');
            }

            if(array_sum($resourceConversions['defender']) > 0)
            {
                $this->invasion['defender']['resource_conversions'] = $resourceConversions['defender'];
                $this->handleResourceConversions($defender, 'defense');
            }

            $this->handleReturningUnits($attacker, $this->invasion['attacker']['units_surviving'], $this->invasion['attacker']['conversions'], $this->invasion['defender']['conversions']);
            $this->handleDefensiveConversions($defender, $this->invasion['defender']['conversions']);

            # Afflicted
            $this->handleInvasionSpells($attacker, $defender);

            # Handle dies_into_resource, dies_into_resources, kills_into_resource, kills_into_resources
            $this->handleResourceConversions($attacker, $defender, $landRatio);

            # Salvage and Plunder
            $this->handleSalvagingAndPlundering($attacker, $defender);

            # Imperial Crypt
            $this->handleCrypt($attacker, $defender, $this->invasion['attacker']['units_surviving'], $this->invasion['attacker']['conversions'], $this->invasion['defender']['conversions']);

            # Watched Dominions
            $this->handleWatchedDominions($attacker, $defender);

            # Handle resources to  be queued
            $this->handleResourceGainsForAttacker($attacker);

            // Stat changes
            if ($this->invasion['result']['success'])
            {
                $this->statsService->updateStat($attacker, 'land_conquered', (int)$this->invasion['attacker']['land_conquered']);
                $this->statsService->updateStat($attacker, 'land_discovered', (int)$this->invasion['attacker']['land_discovered']);
                $this->statsService->updateStat($attacker, 'invasion_victories', $countsAsVictory);
                $this->statsService->updateStat($attacker, 'invasion_bottomfeeds', $countsAsBottomfeed);

                $this->statsService->updateStat($target, 'land_lost', (int)$this->invasion['attacker']['land_conquered']);
                $this->statsService->updateStat($defender, 'defense_failures', 1);
            }
            else
            {
                $this->statsService->updateStat($attacker, 'invasion_razes', $countsAsRaze);
                $this->statsService->updateStat($attacker, 'invasion_failures', $countsAsFailure);

                $this->statsService->updateStat($defender, 'defense_success', 1);
            }

            # LEGION ANNEX SUPPORT EVENTS
            $legion = null;
            if($this->spellCalculator->hasAnnexedDominions($attacker))
            {
                $legion = $attacker;
                $legionString = 'attacker';
                $type = 'invasion_support';
                $targetId = $legion->id;

                if($target->race->name == 'Barbarian')
                {
                    $legion = null;
                }
            }
            elseif($this->spellCalculator->hasAnnexedDominions($defender))
            {
                $legion = $defender;
                $legionString = 'defender';
                $type = 'defense_support';
                $targetId = $defender->id;

                if($attacker->race->name == 'Barbarian')
                {
                    $legion = null;
                }
            }

            if($legion)
            {
                if(isset($this->invasion[$legionString]['annexation']) and $this->invasion[$legionString]['annexation']['hasAnnexedDominions'] > 0 and $this->invasion['result']['op_dp_ratio'] >= 0.85)
                {
                    foreach($this->invasion[$legionString]['annexation']['annexedDominions'] as $annexedDominionId => $annexedDominionData)
                    {
                        # If there are troops to send
                        if(array_sum($this->invasion[$legionString]['annexation']['annexedDominions'][$annexedDominionId]['units_sent']) > 0)
                        {
                            $annexedDominion = Dominion::findorfail($annexedDominionId);

                            $this->invasionEvent = GameEvent::create([
                                'round_id' => $annexedDominion->round_id,
                                'source_type' => Dominion::class,
                                'source_id' => $annexedDominion->id,
                                'target_type' => Dominion::class,
                                'target_id' => $targetId,
                                'type' => $type,
                                'data' => NULL,
                                'tick' => $annexedDominion->round->ticks
                            ]);

                            $annexedDominion->save(['event' => HistoryService::EVENT_ACTION_INVADE_SUPPORT]);
                        }
                    }
                }
            }
            # LIBERATION
            elseif(isset($this->invasion['attacker']['liberation']) and $this->invasion['attacker']['liberation'])
            {
                $annexationSpell = Spell::where('key', 'annexation')->first();
                $this->spellActionService->breakSpell($target, $annexationSpell, $this->invasion['attacker']['liberation']);
            }
            
            # Failed Show of Force
            if(isset($this->invasion['attacker']['show_of_force']) and $this->invasion['attacker']['show_of_force'] and !$this->invasion['result']['success'])
            {
                $annexationSpell = Spell::where('key', 'annexation')->first();
                $this->spellActionService->breakSpell($target, $annexationSpell, $this->invasion['attacker']['liberation']);
            }

            $this->invasion['log']['finished_at'] = time();
            ksort($this->invasion);
            ksort($this->invasion['attacker']);
            ksort($this->invasion['defender']);
            ksort($this->invasion['log']);
            ksort($this->invasion['result']);

            $this->invasionEvent = GameEvent::create([
                'round_id' => $attacker->round_id,
                'source_type' => Dominion::class,
                'source_id' => $attacker->id,
                'target_type' => Dominion::class,
                'target_id' => $target->id,
                'type' => 'invasion',
                'data' => $this->invasion,
                'tick' => $attacker->round->ticks
            ]);

            
            # Debug before saving:
            ldd($this->invasion);

              $target->save(['event' => HistoryService::EVENT_ACTION_INVADE]);
            $attacker->save(['event' => HistoryService::EVENT_ACTION_INVADE]);

            if($attacker->isProtector())
            {
                $attacker->protectedDominion->save();
            }
            if($target->hasProtector())
            {
                $target->protector->save();
            }
        });

        // Notifications
        if(isset($this->invasion['is_protectorate']) and $this->invasion['is_protectorate'])
        {
            // To target (the protected dominion)
            if ($this->invasion['result']['success']) {
                $this->notificationService->queueNotification('protector_received_invasion', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $attacker->id,
                    'protectorDominionId' => $defender->id,
                    'land_lost' => $this->landLost,
                    'units_lost' => $this->invasion['defender']['units_lost'],
                ]);
            } else {
                $this->notificationService->queueNotification('protector_repelled_invasion', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $attacker->id,
                    'protectorDominionId' => $defender->id,
                    'attackerWasOverwhelmed' => $this->invasion['result']['overwhelmed'],
                    'units_lost' => $this->invasion['defender']['units_lost'],
                ]);
            }
            $this->notificationService->sendNotifications($target, 'irregular_dominion');

            // To protector
            if ($this->invasion['result']['success']) {
                $this->notificationService->queueNotification('received_invasion_as_protector', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $attacker->id,
                    'protectedDominionId' => $target->id,
                    'land_lost' => $this->landLost,
                    'units_lost' => $this->invasion['defender']['units_lost'],
                ]);
            } else {
                $this->notificationService->queueNotification('repelled_invasion_as_protector', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $attacker->id,
                    'protectedDominionId' => $target->id,
                    'attackerWasOverwhelmed' => $this->invasion['result']['overwhelmed'],
                    'units_lost' => $this->invasion['defender']['units_lost'],
                ]);
            }
            $this->notificationService->sendNotifications($defender, 'irregular_dominion');
        }
        else
        {
            // Normal
            if ($this->invasion['result']['success']) {
                $this->notificationService->queueNotification('received_invasion', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $attacker->id,
                    'land_lost' => $this->landLost,
                    'units_lost' => $this->invasion['defender']['units_lost'],
                ]);
            } else {
                $this->notificationService->queueNotification('repelled_invasion', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $attacker->id,
                    'attackerWasOverwhelmed' => $this->invasion['result']['overwhelmed'],
                    'units_lost' => $this->invasion['defender']['units_lost'],
                ]);
            }

            $this->notificationService->sendNotifications($target, 'irregular_dominion');
        }

        if ($this->invasion['result']['success'])
        {
            $message = sprintf(
                'You are victorious and defeat the forces of %s (#%s), conquering %s new acres of land! After the invasion, your troops also discovered %s acres of land.',
                $defender->name,
                $defender->realm->number,
                $this->invasion['attacker']['land_conquered'],
                ($this->invasion['attacker']['land_discovered'] + $this->invasion['attacker']['extra_land_discovered'])
            );
            $alertType = 'success';
        }
        elseif($this->invasion['result']['overwhelmed'])
        {
            $message = sprintf(
                'Your army failed miserably against the forces of %s (#%s).',
                $target->name,
                $target->realm->number
            );
            $alertType = 'danger';

        }
        else
        {
            $message = sprintf(
                'Your army fights hard but is unable to defeat the forces of %s (#%s).',
                $target->name,
                $target->realm->number
            );
            $alertType = 'danger';
        }

        return [
            'message' => $message,
            'alert-type' => $alertType,
            'redirect' => route('dominion.event', [$this->invasionEvent->id])
        ];
    }

    protected function handlePrestigeChanges(Dominion $attacker, Dominion $defender, array $units, float $landRatio, int $countsAsVictory, int $countsAsBottomfeed, int $countsAsFailure, int $countsAsRaze): void
    {

        $attackerPrestigeChange = 0;
        $defenderPrestigeChange = 0;

        # LDA mitigation
        $victoriesRatioMultiplier = 1;
        if($this->statsService->getStat($attacker, 'defense_failures') >= 10)
        {
            $victoriesRatioMultiplier = $this->statsService->getStat($attacker, 'invasion_victories') / ($this->statsService->getStat($attacker, 'invasion_victories') + $this->statsService->getStat($attacker, 'defense_failures'));
        }

        # Successful hits over 75% give prestige to attacker and remove prestige from defender
        if($countsAsVictory)
        {
            $attackerPrestigeChange += 60 * $landRatio * $victoriesRatioMultiplier;
            $defenderPrestigeChange -= 20 * $landRatio;
        }

        # Successful bottomfeeds over 60% give no prestige change.
        if($countsAsBottomfeed and $landRatio >= 0.60)
        {
            $attackerPrestigeChange += 0;
        }

        # Successful bottomfeeds under 60% give negative prestige for attacker.
        if($countsAsBottomfeed and $landRatio < 0.60)
        {
            $attackerPrestigeChange -= 20;
        }

        # Unsuccessful hits give negative prestige.
        if($countsAsFailure)
        {
            $attackerPrestigeChange -= 20;
        }

        # Razes over 75% have no prestige loss for attacker and small gain for defender.
        if($countsAsRaze and $landRatio > 0.75)
        {
            $attackerPrestigeChange += 0;
            $defenderPrestigeChange += 10;
        }

        $attackerPrestigeChange *= max(1, (1 - ($this->invasion['defender']['recently_invaded_count']/10)));
        $defenderPrestigeChange *= max(1, (1 - ($this->invasion['defender']['recently_invaded_count']/10)));

        $attackerPrestigeChangeMultiplier = 0;

        // Racial perk
        $attackerPrestigeChangeMultiplier += $attacker->race->getPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $this->militaryCalculator->getPrestigeGainsPerk($attacker, $units);
        $attackerPrestigeChangeMultiplier += $attacker->getAdvancementPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $attacker->getTechPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $attacker->getBuildingPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $attacker->getImprovementPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $attacker->getSpellPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $attacker->getDeityPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $attacker->realm->getArtefactPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $attacker->title->getPerkMultiplier('prestige_gains') * $attacker->getTitlePerkMultiplier();
        $attackerPrestigeChangeMultiplier += $attacker->getDecreePerkMultiplier('prestige_gains');

        # Monarch gains +10% always
        if($attacker->isMonarch())
        {
            $attackerPrestigeChangeMultiplier += 0.10;
        }

        # Attacker gains +20% if defender is Monarch
        if($defender->isMonarch() and $this->invasion['result']['success'])
        {
            $attackerPrestigeChangeMultiplier += 0.20;
        }

        $attackerPrestigeChange *= (1 + $attackerPrestigeChangeMultiplier);

        # Check for prestige_losses
        if($attackerPrestigeChange < 0)
        {
            $lossesMultiplier = 1;

            $lossesMultiplier -= $attacker->getSpellPerkMultiplier('prestige_losses');

            $attackerPrestigeChange *= $lossesMultiplier;
        }
        if($defenderPrestigeChange < 0)
        {
            $lossesMultiplier = 1;

            $lossesMultiplier -= $defender->getSpellPerkMultiplier('prestige_losses');

            $defenderPrestigeChange *= $lossesMultiplier;
        }

        // 1/4 gains for hitting Barbarians.
        if($defender->race->name === 'Barbarian')
        {
            $attackerPrestigeChange /= 4;

            # Liberation
            if(
                $attacker->realm->alignment !== 'evil' and
                $this->invasion['result']['success'] and
                $this->invasion['result']['op_dp_ratio'] >= 1.20 and
                $this->spellCalculator->isAnnexed($defender))
            {
                $this->invasion['attacker']['liberation'] = true;
                $attackerPrestigeChange = max(0, $attackerPrestigeChange);
                $attackerPrestigeChange *= 3;
            }
        }

        # Cut in half when hitting abandoned dominions
        if($defender->isAbandoned() and $attackerPrestigeChange > 0)
        {
            $attackerPrestigeChange /= 2;
        }

        $attackerPrestigeChange = round($attackerPrestigeChange);
        $defenderPrestigeChange = round($defenderPrestigeChange);

        if($attacker->race->getPerkValue('no_prestige'))
        {
            $attackerPrestigeChange = 0;
        }

        if($attacker->race->getPerkValue('no_prestige_loss_on_failed_invasions') and !$this->invasion['result']['success'])
        {
            $attackerPrestigeChange = 0;
        }

        if($defender->race->getPerkValue('no_prestige'))
        {
            $defenderPrestigeChange = 0;
        }

        $attackerPrestigeChange = intval($attackerPrestigeChange);
        $defenderPrestigeChange = intval($defenderPrestigeChange);

        if ($attackerPrestigeChange !== 0)
        {
            if (!$this->invasion['result']['success'])
            {
                $attacker->prestige += $attackerPrestigeChange;
            }
            else
            {
                $slowestTroopsReturnHours = $this->getSlowestUnitReturnHours($attacker, $units);

                $this->queueService->queueResources(
                    'invasion',
                    $attacker,
                    ['prestige' => $attackerPrestigeChange],
                    $slowestTroopsReturnHours
                );
            }

            $this->invasion['attacker']['prestige_change'] = $attackerPrestigeChange;
        }

        if ($defenderPrestigeChange !== 0)
        {
            $defender->prestige += $defenderPrestigeChange;
            $this->invasion['defender']['prestige_change'] = $defenderPrestigeChange;
        }

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
    protected function handleCasualties(Dominion $attacker, Dominion $enemy, array $casualties = [], string $mode = 'offense'): void
    {
        # No casualties for successful show of force.
        if(isset($this->invasion['attacker']['show_of_force']) and $this->invasion['attacker']['show_of_force'] and $this->invasion['result']['success'])
        {
            return;
        }

        if($mode == 'offense')
        {

            if($attacker->getTechPerkMultiplier('chance_of_immortality') and random_chance($attacker->getTechPerkMultiplier('chance_of_immortality')))
            {
                $this->invasion['attacker']['units_immortal'] = true;
            }

            foreach ($this->invasion['attacker']['units_lost'] as $slot => $amount)
            {
                $attacker->{"military_unit{$slot}"} -= $amount;
                $this->invasion['attacker']['units_surviving'][$slot] = $this->invasion['attacker']['units_sent'][$slot] - $this->invasion['attacker']['units_lost'][$slot];

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

        if($mode == 'defense')
        {

            if($attacker->getTechPerkMultiplier('chance_of_immortality') and random_chance($attacker->getTechPerkMultiplier('chance_of_immortality')))
            {
                $this->invasion['defender']['units_immortal'] = true;
            }

            foreach ($this->invasion['defender']['units_lost'] as $slot => $amount)
            {

                $this->invasion['defender']['units_surviving'][$slot] = $this->invasion['defender']['units_defending'][$slot] - $this->invasion['defender']['units_lost'][$slot];

                if(in_array($slot,[1,2,3,4,5,6,7,8,9,10]))
                {
                    $attacker->{"military_unit{$slot}"} -= $amount;
                    $this->statsService->updateStat($attacker, ('unit' . $slot . '_lost'), $amount);
                }
                else
                {
                    $attacker->{"military_{$slot}"} -= $amount;
                    $this->statsService->updateStat($attacker, ($slot . '_lost'), $amount);
                }
            }
        }

        $this->statsService->updateStat($enemy, 'units_killed', array_sum($casualties));
    }

    # !!! Offensive dies into handled in handleReturningUnits()!!!
    public function handleDefensiveDiesIntoPerks(Dominion $attacker)
    {
        # Look for dies_into amongst the dead.
        $diesIntoNewUnits = array_fill(1, $attacker->race->units->count(), 0);
        $diesIntoNewUnitsInstantly = array_fill(1, $attacker->race->units->count(), 0);

        $diesIntoNewUnits['spies'] = 0;
        $diesIntoNewUnits['wizards'] = 0;
        $diesIntoNewUnits['archmages'] = 0;
        $diesIntoNewUnitsInstantly['spies'] = 0;
        $diesIntoNewUnitsInstantly['wizards'] = 0;
        $diesIntoNewUnitsInstantly['archmages'] = 0;

        $unitsLost = $this->invasion['attacker']['units_lost'];

        foreach($this->invasion['defender']['units_lost'] as $slot => $casualties)
        {
            if(in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
            {
                if($diesIntoPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into'))
                {
                    $slot = (int)$diesIntoPerk[0];

                    $diesIntoNewUnits[$slot] += intval($casualties);
                }

                if($diesIntoPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_spy'))
                {
                    $diesIntoNewUnits['spies'] += intval($casualties);
                }

                if($diesIntoPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_wizard'))
                {
                    $diesIntoNewUnits['wizards'] += intval($casualties);
                }

                if($diesIntoPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_archmage'))
                {
                    $diesIntoNewUnits['archmages'] += intval($casualties);
                }

                if($diesIntoPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_on_defense'))
                {
                    $slot = (int)$diesIntoPerk[0];

                    $diesIntoNewUnits[$slot] += intval($casualties);
                }

                if($diesIntoPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_on_defense_instantly'))
                {
                    $slot = (int)$diesIntoPerk[0];

                    $diesIntoNewUnitsInstantly[$slot] += intval($casualties);
                }

                if($diesIntoMultiplePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple'))
                {
                    $slot = (int)$diesIntoMultiplePerk[0];
                    $amount = (float)$diesIntoMultiplePerk[1];

                    $diesIntoNewUnits[$slot] += intval($casualties * $amount);
                }

                if($diesIntoMultiplePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_defense'))
                {
                    $slot = (int)$diesIntoMultiplePerk[0];
                    $amount = (float)$diesIntoMultiplePerk[1];

                    $diesIntoNewUnits[$slot] += intval($casualties * $amount);
                }

                if($diesIntoMultiplePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_defense_instantly'))
                {
                    $slot = (int)$diesIntoMultiplePerk[0];
                    $amount = (float)$diesIntoMultiplePerk[1];

                    $diesIntoNewUnitsInstantly[$slot] += intval($casualties * $amount);
                }

                if(!$this->invasion['result']['success'] and $diesIntoMultiplePerkOnVictory = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_victory'))
                {
                    $slot = (int)$diesIntoMultiplePerkOnVictory[0];
                    $amount = (float)$diesIntoMultiplePerkOnVictory[1];

                    $diesIntoNewUnits[$slot] += intval($casualties * $amount);
                }
            }

        }

        # Dies into units take 1 tick to appear
        foreach($diesIntoNewUnits as $slot => $amount)
        {
            if(in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
            {
                $unitKey = 'military_unit'.$slot;
            }
            else
            {
                $unitKey = 'military_' . $slot;
            }

            $this->queueService->queueResources(
                'training',
                $attacker,
                [$unitKey => $amount],
                1
            );
        }

        # Dies into units take 1 tick to appear
        foreach($diesIntoNewUnitsInstantly as $slot => $amount)
        {
            if(in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
            {
                $unitKey = 'military_unit'.$slot;
            }
            else
            {
                $unitKey = 'military_' . $slot;
            }
            $attacker->{$unitKey} += $amount;
        }
    }


    /**
     * If $target is monarch and invasion is successful, then attacker becomes monarch and target ceases to be monarch.
     * 
     * @param Dominion $attacker
     * @param Dominion $target
     */
    protected function handleDeathmatchGovernorshipChanges(Dominion $attacker, Dominion $target): void
    {
        $this->invasion['result']['governor_changed'] = false;
        
        # Do nothing if invasion is not successful, land ratio is under 0.60, or target is not a monarch.
        if (!$this->invasion['result']['success'] or !in_array($attacker->round->mode,['deathmatch','deathmatch-duration']) or $target->race->name == 'Barbarian')
        {
            return;
        }

        # If there is no governor, attacker becomes governor if the target is in the same realm (i.e. not a Barbarian)
        if(!$this->governmentService->hasMonarch($attacker->realm) and $attacker->realm->id == $target->realm->id)
        {
            $this->governmentService->setRealmMonarch($attacker->realm, $attacker->id);
        }
        # If there is a governor, the attacker becomes governor if the target is (was) governor.
        elseif($this->governmentService->hasMonarch($attacker->realm) and $this->governmentService->getRealmMonarch($attacker->realm)->id == $target->id)
        {
            $this->governmentService->setRealmMonarch($attacker->realm, $attacker->id);
        }

        $this->invasion['result']['governor_changed'] = true;

    }


    /**
     * Handles land grabs and losses upon successful invasion.
     *
     * todo: description
     *
     * @param Dominion $attacker
     * @param Dominion $target
     */
    protected function handleLandGrabs(Dominion $attacker, Dominion $target, float $landRatio): void
    {
        // Nothing to grab if invasion isn't successful :^) — or if it's a show of force
        if (!$this->invasion['result']['success'] or (isset($this->invasion['attacker']['show_of_force']) and $this->invasion['attacker']['show_of_force']))
        {
            return;
        }

        $landRatio = $landRatio * 100;

        # Returns an integer.
        $landConquered = $this->militaryCalculator->getLandConquered($attacker, $target, $landRatio);
        $discoverLand = $this->militaryCalculator->checkDiscoverLand($attacker, $target, $landConquered);
        $extraLandDiscovered = $this->militaryCalculator->getExtraLandDiscovered($attacker, $target, $discoverLand, $landConquered);

        $this->invasion['defender']['land_lost'] = intval($landConquered);
        $this->invasion['defender']['terrain_lost'] = $this->terrainCalculator->getTerrainLost($target, $landConquered);
        $this->invasion['defender']['buildings_lost'] = $this->buildingCalculator->getBuildingsLost($target, $landConquered);

        $this->invasion['attacker']['land_conquered'] = intval($this->invasion['defender']['land_lost']);
        $this->invasion['attacker']['terrain_conquered'] = $this->invasion['defender']['terrain_lost'];

        # Remove land
        $target->land -= $landConquered;

        # Remove terrain

        ## Start with available terrain
        foreach($this->invasion['defender']['terrain_lost']['available'] as $terrainKey => $amount)
        {
            $this->terrainService->update($target, [$terrainKey => $amount]);
        }

        ## Then look through queued terrain to remove (dequeue)
        foreach($this->invasion['defender']['terrain_lost']['queued'] as $terrainKey => $amount)
        {
            if($amount > 0)
            {
                $this->queueService->dequeueResource('rezoning', $target, ('terrain_' . $terrainKey), $amount);
            }
        }

        # Remove buildings

        ## Start with available buildings
        foreach($this->invasion['defender']['buildings_lost']['available'] as $buildingKey => $amount)
        {
            $this->buildingCalculator->removeBuildings($target, [$buildingKey => $amount]);
        }

        ## Then look through queued buildings to remove (dequeue)
        foreach($this->invasion['defender']['buildings_lost']['queued'] as $buildingKey => $amount)
        {
            if($amount > 0)
            {
                $this->queueService->dequeueResource('construction', $target, ('building_' . $buildingKey), $amount);
            }
        }

        $landDiscovered = 0;
        $this->invasion['attacker']['terrain_discovered'] = [];
        $this->invasion['attacker']['land_discovered'] = 0;
        $this->invasion['attacker']['extra_land_discovered'] = 0;

        if($discoverLand)
        {
            $landDiscovered = $landConquered;
            if($target->race->name === 'Barbarian')
            {
                $landDiscovered /= 3;
            }
            $landDiscovered = intval(floor($landDiscovered));

            $this->invasion['attacker']['land_discovered'] = $landDiscovered;
            $this->invasion['attacker']['terrain_discovered'] = $this->terrainCalculator->getTerrainDiscovered($attacker, $landDiscovered);

            $this->invasion['attacker']['extra_land_discovered'] = intval($extraLandDiscovered);
        }

        $this->landLost = $landConquered;

        $mergedArrays = array_merge_recursive($this->invasion['attacker']['terrain_conquered']['available'], $this->invasion['attacker']['terrain_conquered']['queued'], $this->invasion['attacker']['terrain_discovered']);

        $summedQueueData = [];

        foreach ($mergedArrays as $terrainKey => $terrainValues) {
            if (is_array($terrainValues)) {
                $summedQueueData[$terrainKey] = array_sum($terrainValues);
            } else {
                $summedQueueData[$terrainKey] = $terrainValues;
            }
        }

        $summedQueueData = array_filter($summedQueueData, function($value) {
            return $value !== 0;
        });

        $this->invasion['attacker']['terrain_gained'] = $summedQueueData;

        foreach($summedQueueData as $terrainKey => $amount)
        {
            $queueData[('terrain_' . $terrainKey)] = abs($amount);
        }

        $this->queueService->queueResources(
            'invasion',
            $attacker,
            $queueData
        );

        $this->queueService->queueResources(
            'invasion',
            $attacker,
            ['land' => ($this->invasion['attacker']['land_conquered'] + $this->invasion['attacker']['land_discovered'] + $this->invasion['attacker']['extra_land_discovered'])]
        );

        # Populate buildings_lost_total (for display purposes)

        $mergedBuildingsArrays = array_merge_recursive($this->invasion['defender']['buildings_lost']['available'], $this->invasion['defender']['buildings_lost']['queued']);

        $buildingsLostTotal = [];

        foreach ($mergedBuildingsArrays as $terrainKey => $terrainValues) {
            if (is_array($terrainValues)) {
                $buildingsLostTotal[$terrainKey] = array_sum($terrainValues);
            } else {
                $buildingsLostTotal[$terrainKey] = $terrainValues;
            }
        }

        $buildingsLostTotal = array_filter($buildingsLostTotal, function($value) {
            return $value !== 0;
        });

        $this->invasion['defender']['buildings_lost_total'] = $buildingsLostTotal;
    }

    protected function handleMoraleChanges(Dominion $attacker, Dominion $defender, float $landRatio, array $units): void
    {

        $landRatio *= 100;
        # For successful invasions...
        if($this->invasion['result']['success'])
        {
            # Drop 10% morale for hits under 60%.
            if($landRatio < 60)
            {
                $attackerMoraleChange = -15+(-60-$landRatio);
                $defenderMoraleChange = $attackerMoraleChange*-1;
            }
            # No change for hits in 60-75%
            elseif($landRatio < 75)
            {
                $attackerMoraleChange = 0;
                $defenderMoraleChange = $attackerMoraleChange*-0.60;;
            }
            # Sliding scale for 75% and up
            elseif($landRatio >= 75)
            {
                $attackerMoraleChange = 10 * ($landRatio/75) * (1 + $landRatio/100);
                $defenderMoraleChange = $attackerMoraleChange*-0.60;
            }

            $attackerMoraleChangeMultiplier = 1;
            $attackerMoraleChangeMultiplier += $attacker->getBuildingPerkMultiplier('morale_gains');
            $attackerMoraleChangeMultiplier += $attacker->race->getPerkMultiplier('morale_change_invasion');
            $attackerMoraleChangeMultiplier += $attacker->title->getPerkMultiplier('morale_gains') * $attacker->getTitlePerkMultiplier();

            # Look for lowers_target_morale_on_successful_invasion
            foreach($attacker->race->units as $unit)
            {
                if(
                    $increasesMoraleGainsPerk = $attacker->race->getUnitPerkValueForUnitSlot($unit->slot, 'increases_morale_gains') and
                    isset($units[$unit->slot]) and
                    $this->invasion['result']['success']
                    )
                {
                    $attackerMoraleChangeMultiplier += ($this->invasion['attacker']['units_sent'][$unit->slot] / array_sum($this->invasion['attacker']['units_sent'])) * $increasesMoraleGainsPerk;
                }


                if(
                    $increasesMoraleGainsPerk = $attacker->race->getUnitPerkValueForUnitSlot($unit->slot, 'increases_morale_gains_fixed') and
                    isset($units[$unit->slot]) and
                    $this->invasion['result']['success']
                    )
                {
                    $attackerMoraleChange += $this->invasion['attacker']['units_sent'][$unit->slot] * $increasesMoraleGainsPerk;
                }
            }

            $attackerMoraleChange *= $attackerMoraleChangeMultiplier;

            $defenderMoraleChangeMultiplier = 1;
            $defenderMoraleChangeMultiplier += $defender->race->getPerkMultiplier('morale_change_invasion');

            $defenderMoraleChange *= $defenderMoraleChangeMultiplier;

            # Look for lowers_target_morale_on_successful_invasion
            for ($slot = 1; $slot <= $attacker->race->units->count(); $slot++)
            {
                if(
                    $lowersTargetMoralePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'lowers_target_morale_on_successful_invasion') and
                    isset($units[$slot]) and
                    $this->invasion['result']['success']
                    )
                {
                    $defenderMoraleChange -= $this->invasion['attacker']['units_sent'][$slot] * $lowersTargetMoralePerk;
                }
            }

        }
        # For failed invasions...
        else
        {
            # If overwhelmed, attacker loses 20%, defender gets nothing.
            if($this->invasion['result']['overwhelmed'])
            {
                $attackerMoraleChange = -20;
                $defenderMoraleChange = 0;
            }
            # Otherwise, -10% for attacker and +5% for defender
            else
            {
                $attackerMoraleChange = -10;
                $defenderMoraleChange = 10;
            }
        }

        # Halved morale gain for hitting Barbarians
        if($attackerMoraleChange > 0 and $defender->race->name == 'Barbarian')
        {
            $attackerMoraleChange /= 2;
        }

        # Look for no_morale_changes
        if($attacker->race->getPerkValue('no_morale_changes'))
        {
            $attackerMoraleChange = 0;
        }

        if($attacker->race->getPerkValue('no_morale_loss_on_failed_invasions') and !$this->invasion['result']['success'])
        {
            $attackerMoraleChange = 0;
        }
        
        if($defender->race->getPerkValue('no_morale_changes'))
        {
            $defenderMoraleChange = 0;
        }
        
        # Round
        $attackerMoraleChange = intval(round($attackerMoraleChange));
        $defenderMoraleChange = intval(round($defenderMoraleChange));

        # Change attacker morale.

        // Make sure it doesn't go below 0.
        if(($attacker->morale + $attackerMoraleChange) < 0)
        {
            $attackerMoraleChange = 0;
        }
        
        $attacker->morale += $attackerMoraleChange;

        if($attackerMoraleChange > 0 and $attacker->isProtector())
        {
            $attacker->protectedDominion->morale += intval(round($attackerMoraleChange / 4));
        }

        # Change defender morale.

        // Make sure it doesn't go below 0.
        if(($defender->morale + $defenderMoraleChange) < 0)
        {
            $defenderMoraleChange = intval($defender->morale * -1);
        }

        $defender->morale += $defenderMoraleChange;

        if($defenderMoraleChange > 0 and $defender->isProtector())
        {
            $defender->protectedDominion->morale += $defenderMoraleChange;
        }

        $this->invasion['attacker']['morale_change'] = $attackerMoraleChange;
        $this->invasion['defender']['morale_change'] = $defenderMoraleChange;

    }

    /**
     * Handles experience point (research point) generation for attacker.
     *
     * @param Dominion $attacker
     * @param array $units
     */
    protected function handleXp(Dominion $attacker, Dominion $defender, array $units): void
    {
        $researchPointsPerAcre = 60;

        # Decreased by 0.04 per round tick
        $researchPointsPerAcre -= $attacker->round->ticks * 0.04;

        # Cap at 40
        $researchPointsPerAcre = max(40, $researchPointsPerAcre);

        $researchPointsPerAcreMultiplier = 1;

        # Increase RP per acre
        $researchPointsPerAcreMultiplier += $attacker->race->getPerkMultiplier('xp_gains');
        $researchPointsPerAcreMultiplier += $attacker->getImprovementPerkMultiplier('xp_gains');
        $researchPointsPerAcreMultiplier += $attacker->getBuildingPerkMultiplier('xp_gains');
        $researchPointsPerAcreMultiplier += $attacker->getSpellPerkMultiplier('xp_gains');
        $researchPointsPerAcreMultiplier += $attacker->getDeityPerkMultiplier('xp_gains');
        $researchPointsPerAcreMultiplier += $attacker->getDecreePerkMultiplier('xp_gains');

        $isInvasionSuccessful = $this->invasion['result']['success'];
        
        if ($isInvasionSuccessful)
        {
            $landConquered = $this->invasion['attacker']['land_conquered'];
            $landDiscovered = $this->invasion['attacker']['land_discovered'];

            $researchPointsForGeneratedAcresMultiplier = 1;

            if($this->militaryCalculator->getRecentlyInvadedCountByAttacker($defender, $attacker))
            {
                $researchPointsForGeneratedAcresMultiplier = 2;
            }

            $researchPointsGained = $landConquered * $researchPointsPerAcre * $researchPointsPerAcreMultiplier;
            $researchPointsGained += $landDiscovered * $researchPointsPerAcre * $researchPointsForGeneratedAcresMultiplier;

            $researchPointsGained = intval($researchPointsGained);

            $slowestTroopsReturnHours = $this->getSlowestUnitReturnHours($attacker, $units);

            $this->queueService->queueResources(
                'invasion',
                $attacker,
                ['xp' => $researchPointsGained],
                $slowestTroopsReturnHours
            );

            $this->invasion['attacker']['xp'] = $researchPointsGained;
        }
    }

    /**
    *  Handles perks that trigger DURING the battle (before casualties).
    *
    *  Go through every unit slot and look for post-invasion perks:
    *  - burns_peasants_on_attack
    *  - damages_improvements_on_attack
    *  - eats_peasants_on_attack
    *  - eats_draftees_on_attack
    *
    * If a perk is found, see if any of that unit were sent on invasion.
    *
    * If perk is found and units were sent, calculate and take the action.
    *
    * @param Dominion $attacker
    * @param Dominion $target
    * @param array $units
    */
    protected function handleDuringInvasionUnitPerks(Dominion $attacker, Dominion $defender, array $units): void
    {

        # Only if invasion is successful
        if($this->invasion['result']['success'])
        {
            # ATTACKER
            foreach($this->invasion['attacker']['units_sent'] as $slot => $amount)
            {
                if ($destroysResourcePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'destroy_resource_on_victory'))
                {
                    $resourceKey = (string)$destroysResourcePerk[0];
                    $amountDestroyedPerUnit = (float)$destroysResourcePerk[1];
                    $maxDestroyedBySlot = (int)round(min($this->invasion['attacker']['units_sent'][$slot] * $amountDestroyedPerUnit, $this->resourceCalculator->getAmount($defender, $resourceKey)));

                    if($maxDestroyedBySlot > 0)
                    {
                        if(isset($this->invasion['attacker']['resources_destroyed'][$resourceKey]))
                        {
                            $this->invasion['attacker']['resources_destroyed'][$resourceKey] += $maxDestroyedBySlot;
                        }
                        else
                        {
                            $this->invasion['attacker']['resources_destroyed'][$resourceKey] = $maxDestroyedBySlot;
                        }

                        $this->resourceService->updateResources($defender, [$resourceKey => ($maxDestroyedBySlot * -1)]);
                    }
                }
            }
        }

        for ($slot = 1; $slot <= $attacker->race->units->count(); $slot++)
        {
          # Snow Elf: Hailstorm Cannon exhausts all mana
           if($exhaustingPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'offense_from_resource_exhausting') and isset($units[$slot]))
           {
               $resourceKey = $exhaustingPerk[0];
               $resourceAmount = $this->resourceCalculator->getAmount($attacker, $resourceKey);

               $this->invasion['attacker'][$resourceKey . '_exhausted'] = $resourceAmount;

               $this->resourceService->updateResources($attacker, [$resourceKey => ($resourceAmount * -1)]);
           }

           # Yeti: Stonethrowers spend ore (but not necessarily all of it)
           if($exhaustingPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'offense_from_resource_capped_exhausting') and isset($units[$slot]))
           {
               $amountPerUnit = (float)$exhaustingPerk[1];
               $resourceKey = (string)$exhaustingPerk[2];

               $resourceAmountExhausted = $units[$slot] * $amountPerUnit;

               $this->invasion['attacker'][$resourceKey . '_exhausted'] = $resourceAmountExhausted;

               $this->resourceService->updateResources($attacker, [$resourceKey => ($resourceAmountExhausted * -1)]);
           }

           # Imperial Gnome: brimmer to fuel the Airships
           if($spendsResourcesOnOffensePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'spends_resource_on_offense') and isset($units[$slot]))
           {
               $resourceKey = (string)$spendsResourcesOnOffensePerk[0];
               $resourceAmountPerUnit = (float)$spendsResourcesOnOffensePerk[1];
               $resource = Resource::where('key', $resourceKey)->firstOrFail();

               $resourceAmountSpent = $units[$slot] * $resourceAmountPerUnit;

               $this->invasion['attacker'][$resourceKey . '_exhausted'] = $resourceAmountSpent;

               $this->resourceService->updateResources($attacker, [$resourceKey => ($resourceAmountSpent * -1)]);
           }
        }

        # Ignore if attacker is overwhelmed.
        if(!$this->invasion['result']['overwhelmed'])
        {
            for ($unitSlot = 1; $unitSlot <= $attacker->race->units->count(); $unitSlot++)
            {
                // burns_peasants
                if (($burnsPeasantsOnAttackPerk = $attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'burns_peasants_on_attack')) and isset($units[$unitSlot]))
                {
                    $burningUnits = $units[$unitSlot];
                    $rawOpFromBurningUnits = $this->militaryCalculator->getOffensivePowerRaw($attacker, $defender, null, [$unitSlot => $burningUnits]);

                    $burnedPeasantsRatio = ($burnsPeasantsOnAttackPerk / 100) * ($rawOpFromBurningUnits / $this->invasion['attacker']['op_raw']) * min($this->invasion['result']['op_dp_ratio'], 2);
                    $burnedPeasants = (int)min(floor($defender->peasants * $burnedPeasantsRatio), $defender->peasants);

                    $defender->peasants -= $burnedPeasants;
                    $this->invasion['attacker']['peasants_burned']['peasants'] = $burnedPeasants;
                    $this->invasion['defender']['peasants_burned']['peasants'] = $burnedPeasants;
                }

                // burns_draftees
                if (($burnsDrafteesOnAttackPerk = $attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'burns_draftees_on_attack')) and isset($units[$unitSlot]))
                {
                    $burningUnits = $units[$unitSlot];
                    $rawOpFromBurningUnits = $this->militaryCalculator->getOffensivePowerRaw($attacker, $defender, null, [$unitSlot => $burningUnits]);

                    $burnedDrafteesRatio = ($burnsDrafteesOnAttackPerk / 100) * ($rawOpFromBurningUnits / $this->invasion['attacker']['op_raw']) * min($this->invasion['result']['op_dp_ratio'], 2);
                    $burnedDraftees = (int)min(floor($defender->military_draftees * $burnedDrafteesRatio), $defender->military_draftees);

                    $defender->military_draftees -= $burnedDraftees;
                    $this->invasion['attacker']['draftees_burned']['draftees'] = $burnedPeasants;
                    $this->invasion['defender']['draftees_burned']['draftees'] = $burnedPeasants;
                }

                // damages_improvements_on_attack
                if ($attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'damages_improvements_on_attack') and isset($units[$unitSlot]))
                {

                    $totalImprovementPoints = $this->improvementCalculator->getDominionImprovementTotalAmountInvested($defender);

                    $defenderImprovements = $this->improvementCalculator->getDominionImprovements($defender);

                    $damagingUnits = $units[$unitSlot];
                    $damagePerUnit = $attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'damages_improvements_on_attack');

                    $damageMultiplier = 1;
                    $damageMultiplier += $defender->getBuildingPerkMultiplier('lightning_bolt_damage');

                    $damage = $damagingUnits * $damagePerUnit * $damageMultiplier;
                    $damage = min($damage, $totalImprovementPoints);

                    if($damage > 0)
                    {
                        foreach($defenderImprovements as $defenderImprovement)
                        {
                            $improvement = Improvement::where('id', $defenderImprovement->improvement_id)->first();
                            $improvementDamage[$improvement->key] = floor($damage * ($this->improvementCalculator->getDominionImprovementAmountInvested($defender, $improvement) / $totalImprovementPoints));
                        }
                        $this->improvementCalculator->decreaseImprovements($defender, $improvementDamage);
                    }

                    $this->invasion['attacker']['improvements_damage']['improvement_points'] = $damage;
                    $this->invasion['defender']['improvements_damage']['improvement_points'] = $damage;
                }

                if ($attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'eats_peasants_on_attack') and isset($units[$unitSlot]))
                {
                    $eatingUnits = $units[$unitSlot];
                    $peasantsEatenPerUnit = (float)$attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'eats_peasants_on_attack');

                    # If defender has less than 1000 peasants, we don't eat any.
                    if($defender->peasants < 1000)
                    {
                        $eatenPeasants = 0;
                    }
                    else
                    {
                        $eatenPeasants = round($eatingUnits * $peasantsEatenPerUnit * min($this->invasion['result']['op_dp_ratio'], 1));
                        $eatenPeasants = min(($defender->peasants-1000), $eatenPeasants);
                    }

                    $defender->peasants -= $eatenPeasants;
                    $this->invasion['attacker']['peasants_eaten']['peasants'] = $eatenPeasants;
                    $this->invasion['defender']['peasants_eaten']['peasants'] = $eatenPeasants;
                }

                // Troll: eats_draftees_on_attack
                if ($attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'eats_draftees_on_attack') and isset($units[$unitSlot]))
                {
                    $eatingUnits = $units[$unitSlot];
                    $drafteesEatenPerUnit = $attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'eats_draftees_on_attack');

                    $eatenDraftees = round($eatingUnits * $drafteesEatenPerUnit * min($this->invasion['result']['op_dp_ratio'], 1));
                    $eatenDraftees = min($defender->military_draftees, $eatenDraftees);

                    $defender->military_draftees -= $eatenDraftees;
                    $this->invasion['attacker']['draftees_eaten']['draftees'] = $eatenDraftees;
                    $this->invasion['defender']['draftees_eaten']['draftees'] = $eatenDraftees;
                }

                # destroy_resource
                if ($destroysResourcePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'destroy_resource') and isset($units[$unitSlot]))
                {
                    $resourceKey = (string)$destroysResourcePerk[0];
                    $amountDestroyedPerUnit = (float)$destroysResourcePerk[1];
                    $maxDestroyedBySlot = (int)round(min($this->invasion['attacker']['units_sent'][$slot] * $amountDestroyedPerUnit, $this->resourceCalculator->getAmount($defender, $resourceKey)));

                    if($maxDestroyedBySlot > 0)
                    {
                        if(isset($this->invasion['attacker']['resources_destroyed'][$resourceKey]))
                        {
                            $this->invasion['attacker']['resources_destroyed'][$resourceKey] += $maxDestroyedBySlot;
                        }
                        else
                        {
                            $this->invasion['attacker']['resources_destroyed'][$resourceKey] = $maxDestroyedBySlot;
                        }

                        $this->resourceService->updateResources($defender, [$resourceKey => ($maxDestroyedBySlot * -1)]);
                    }
                }
            }
        }

        # DEFENDER

        foreach($defender->race->resources as $resourceKey)
        {
            $defenderResourceAmountExhausted[$resourceKey] = 0;
        }

        foreach($defender->race->units as $unit)
        {
           # Cires: gunpowder on defense (if attacker is not overwhelmed)
           # This sum up to more than the available gunpowder, which is fine, because the DP provided is calculated with the help of militaryCalculator->dpFromUnitWithoutSufficientResources()
           if($spendsResourcesOnDefensePerk = $defender->race->getUnitPerkValueForUnitSlot($unit->slot, 'spends_resource_on_defense') and isset($this->invasion['defender']['units_defending'][$unit->slot]) and !$this->invasion['result']['overwhelmed'])
           {
               $resourceKey = (string)$spendsResourcesOnDefensePerk[0];
               $resourceAmountPerUnit = (float)$spendsResourcesOnDefensePerk[1];
               $resourceUsedByThisUnit = $this->invasion['defender']['units_defending'][$unit->slot] * $resourceAmountPerUnit;

               $defenderResourceAmountExhausted[$resourceKey] += $resourceUsedByThisUnit;
           }
        }

        foreach($defenderResourceAmountExhausted as $resourceKey => $amount)
        {
            $amount = min($this->resourceCalculator->getAmount($defender, $resourceKey), $amount);
            $this->resourceService->updateResources($defender, [$resourceKey => ($amount * -1)]);
            $this->invasion['defender']['resources_spent'][$resourceKey] = $amount;
        }

    }

    protected function handleStun(Dominion $attacker, Dominion $defender, array $units, float $landRatio)
    {

        $opDpRatio = $this->invasion['attacker']['op'] / $this->invasion['defender']['dp'];

        $rawOp = 0;
        $stunningOp = 0;

        # Calculate how much of raw OP came from stunning units
        foreach($units as $slot => $amount)
        {
            $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                return ($unit->slot == $slot);
            })->first();

            $unitsOp = $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense') * $amount;

            $rawOp += $unitsOp;

            if($attacker->race->getUnitPerkValueForUnitSlot($slot, 'stuns_units'))
            {
                $stunningOp += $unitsOp;
            }
        }

        if($stunningOp > 0)
        {
            $stunningOpRatio = $stunningOp / $rawOp;

            $stunBaseDamage = 1;
            $stunMaxDamage = 2.5;

            $stunRatio = min((static::STUN_RATIO / 100) * $opDpRatio * min($stunningOpRatio, 1), 2.5);

            # Collect the stunnable units
            $stunnableUnits = array_fill(1, $defender->race->units->count(), 0);

            # Exclude certain attributes
            $unconvertibleAttributes = [
                'ammunition',
                'aspect',
                'equipment',
                'magical',
                'massive',
                'machine',
                'ship',
              ];

            foreach($this->invasion['defender']['units_defending'] as $slot => $amount)
            {
                if($slot !== 'draftees')
                {
                    if(isset($this->invasion['defender']['units_lost'][$slot]) and $this->invasion['defender']['units_lost'][$slot] > 0)
                    {
                        $amount -= $this->invasion['defender']['units_lost'][$slot];
                    }
                    $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();

                    $unitRawDp = $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'defense');
                    $unitAttributes = $this->unitHelper->getUnitAttributes($unit);

                    # Only add unit to available casualties if it has none of the unconvertible unit attributes.
                    if(count(array_intersect($unconvertibleAttributes, $unitAttributes)) === 0 and $unitRawDp < 10)
                    {
                        $stunnableUnits[$slot] = (int)$amount;
                    }
                }
                else
                {
                    if($amount > 0)
                    {
                        $amount -= $this->invasion['defender']['units_lost'][$slot];
                    }
                    $stunnableUnits['draftees'] = (int)$amount;
                }
             }

             foreach($stunnableUnits as $slot => $amount)
             {
                $amount = (int)round($amount * $stunRatio);
                $this->invasion['defender']['units_stunned'][$slot] = $amount;

                # Stunned units take 2 ticks to return
                if($slot !== 'draftees')
                {
                    $unitKey = 'military_unit'.$slot;
                }
                else
                {
                    $unitKey = 'military_draftees';
                }

                $defender->$unitKey -= $amount;
                $this->queueService->queueResources(
                    'invasion',
                    $defender,
                    [$unitKey => $amount],
                    2
                );
             }
        }
    }

    public function handlePeasantCapture(Dominion $attacker, Dominion $defender, array $units, float $landRatio): void
    {
        if($attacker->race->name !== 'Demon' or !$this->invasion['result']['success'])
        {
            return;
        }

        $rawOp = 0;
        foreach($this->invasion['attacker']['units_sent'] as $slot => $amount)
        {
            if($amount > 0)
            {
                $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $rawOpFromSlot = $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense');
                $totalRawOpFromSlot = $rawOpFromSlot * $amount;

                $rawOp += $totalRawOpFromSlot;
            }
        }

        $landConquered = $this->invasion['attacker']['land_conquered'];
        $displacedPeasants = intval(($defender->peasants / $this->invasion['defender']['land_size']) * $landConquered);

        foreach($units as $slot => $amount)
        {
            if ($attacker->race->getUnitPerkValueForUnitSlot($slot, 'captures_displaced_peasants'))
            {
                $opFromSlot = $this->militaryCalculator->getOffensivePowerRaw($attacker, $defender, $landRatio, [$slot => $amount]);
                $opRatio = $opFromSlot / $rawOp;

                $peasantsCaptured = (int)floor($displacedPeasants * $opRatio);

                #dump('Slot ' . $slot . ' OP: ' . number_format($opFromSlot) . ' which is ' . $opRatio . ' ratio relative to ' . number_format($rawOp) . ' raw OP total.');

                if(isset($this->invasion['attacker']['peasants_captured']))
                {
                    $this->invasion['attacker']['peasants_captured'] += $peasantsCaptured;
                }
                else
                {
                    $this->invasion['attacker']['peasants_captured'] = $peasantsCaptured;
                }
            }
        }

        if(isset($this->invasion['attacker']['peasants_captured']))
        {
            $this->invasion['attacker']['peasants_captured'] = intval(max(0, $this->invasion['attacker']['peasants_captured']));

            $this->queueService->queueResources(
                'invasion',
                $attacker,
                ['peasants' => $this->invasion['attacker']['peasants_captured']],
                12
            );
        }
    }

    public function handlePeasantKilling(Dominion $attacker, Dominion $defender, array $units, float $landRatio): void
    {
        if($defender->race->name !== 'Demon' or !$this->invasion['result']['success'])
        {
            return;
        }

        $this->invasion['defender']['displaced_peasants_killing']['peasants_killed'] = 0;
        $this->invasion['defender']['displaced_peasants_killing']['soul'] = 0;
        $this->invasion['defender']['displaced_peasants_killing']['blood'] = 0;

        $rawDp = 0;
        foreach($this->invasion['defender']['units_defending'] as $slot => $amount)
        {
            if($amount > 0)
            {
                if($slot == 'draftees')
                {
                    $rawDpFromSlot = 1;
                }
                elseif(in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
                {
                    $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();

                    $rawDpFromSlot = $this->militaryCalculator->getUnitPowerWithPerks($defender, $attacker, $landRatio, $unit, 'defense');
                }

                $totalRawDpFromSlot = $rawDpFromSlot * $amount;

                $rawDp += $totalRawDpFromSlot;
            }
        }

        $landConquered = $this->invasion['attacker']['land_conquered'];
        $displacedPeasants = intval(($defender->peasants / $this->invasion['defender']['land_size']) * $landConquered);

        foreach($this->invasion['defender']['units_defending'] as $slot => $amount)
        {
            if(in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
            {
                if ($defender->race->getUnitPerkValueForUnitSlot($slot, 'kills_displaced_peasants'))
                {
                    $dpFromSlot = $this->militaryCalculator->getDefensivePowerRaw($defender, $attacker, $landRatio, [$slot => $amount]);
                    $dpRatio = $dpFromSlot / $rawDp;

                    $peasantsKilled = (int)floor($displacedPeasants * $dpRatio);

                    $this->invasion['defender']['displaced_peasants_killing']['peasants_killed'] += $peasantsKilled;
                }
            }
        }

        $this->invasion['defender']['displaced_peasants_killing']['peasants_killed'] = intval(min(($defender->peasants-1000), max(0, $this->invasion['defender']['displaced_peasants_killing']['peasants_killed'])));
        $this->invasion['defender']['displaced_peasants_killing']['soul'] = $this->invasion['defender']['displaced_peasants_killing']['peasants_killed'];
        $this->invasion['defender']['displaced_peasants_killing']['blood'] = $this->invasion['defender']['displaced_peasants_killing']['peasants_killed'] * 6;

        $defender->peasants -= $this->invasion['defender']['displaced_peasants_killing']['peasants_killed'];

        $resourceArray = ['blood' => $this->invasion['defender']['displaced_peasants_killing']['blood'], 'soul' => $this->invasion['defender']['displaced_peasants_killing']['soul']];

        $this->resourceService->updateResources($defender, $resourceArray);

    }

    public function handleStrengthGain(Dominion $attacker, Dominion $defender): void
    {
        if(($attacker->race->name !== 'Monster' and $defender->race->name !== 'Monster'))
        {
            return;
        }

        if($attacker->race->name == 'Monster')
        {
            $mode = 'offense';
            $role = 'attacker';
            $monster = $attacker;
            $enemy = $defender;
        }
        else
        {
            $mode = 'defense';
            $role = 'defender';
            $monster = $defender;
            $enemy = $attacker;
        }
        
        $this->invasion[$role]['strength_gain'] = $this->militaryCalculator->getStrengthGain($monster, $enemy, $mode, $this->invasion);

        if($this->invasion[$role]['strength_gain'] !== 0)
        {
            if($mode == 'offense')
            {
                $this->queueService->queueResources(
                    'invasion',
                    $monster,
                    ['resource_strength' => $this->invasion[$role]['strength_gain']],
                    12
                );
            }
            else
            {
                $this->resourceService->updateResources($monster, ['resource_strength' => $this->invasion[$role]['strength_gain']]);
            }
        }
    }

    public function handlePsionicConversions(Dominion $cult, Dominion $enemy, string $mode = 'offense'): void
    {

        $psionicConversions = $this->conversionCalculator->getPsionicConversions($cult, $enemy, $this->invasion, $mode);

        #dump($psionicConversions);

        if(empty($psionicConversions))
        {
            return;
        }

        $this->invasion['attacker']['psionic_conversions'] = $psionicConversions;
        $this->statsService->updateStat($cult, 'units_converted_psionically', array_sum($psionicConversions));
        $this->statsService->updateStat($enemy, 'units_lost_psionically', array_sum($psionicConversions));

        if($mode == 'offense')
        {
            if(!isset($this->invasion['attacker']['conversions']))
            {
                $this->invasion['attacker']['conversions'] = array_fill(1, $cult->race->units->count(), 0);
            }

            foreach($psionicConversions['psionic_losses'] as $slot => $amount)
            {
                isset($this->invasion['defender']['units_lost'][$slot]) ? $this->invasion['defender']['units_lost'][$slot] += $amount : $this->invasion['defender']['units_lost'][$slot] = $amount;

                if(in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
                {
                    $enemy->{'military_unit'.$slot} -= $amount;
                }
                elseif($slot == 'draftees')
                {
                    $enemy->{'military_'.$slot} -= $amount;
                }
                elseif($slot == 'peasants')
                {
                    $enemy->{$slot} -= $amount;
                }
            }

            foreach($psionicConversions['psionic_conversions'] as $slot => $amount)
            {
                $this->invasion['attacker']['conversions'][$slot] += $amount;
            }
        }

        if($mode == 'defense')
        {
            if(!isset($this->invasion['defender']['conversions']))
            {
                $this->invasion['defender']['conversions'] = array_fill(1, $cult->race->units->count(), 0);
            }

            foreach($psionicConversions['psionic_losses'] as $slot => $amount)
            {
                isset($this->invasion['attacker']['units_lost'][$slot]) ? $this->invasion['attacker']['units_lost'][$slot] += $amount : $this->invasion['attacker']['units_lost'][$slot] = $amount;

                if(isset($this->invasion['attacker']['units_sent'][$slot]))
                {
                    if(in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
                    {
                        $this->invasion['attacker']['units_lost'][$slot] += $amount;
                    }
                    elseif($slot == 'draftees')
                    {
                        $this->invasion['attacker']['units_lost'][$slot] += $amount;
                    }
                    elseif($slot == 'peasants')
                    {
                        #$this->invasion['attacker']['units_lost'][$slot] += $amount;
                        #$enemy->{$slot} -= $amount;
                    }
                }
            }

            foreach($psionicConversions['psionic_conversions'] as $slot => $amount)
            {
                $this->invasion['defender']['conversions'][$slot] += $amount;
            }
        }
        
    }

    # Unit Return 2.0
    protected function handleReturningUnits(Dominion $attacker, array $units, array $convertedUnits): void
    {
        # If instant return
        if(random_chance($attacker->getImprovementPerkMultiplier('chance_of_instant_return')) or $attacker->race->getPerkValue('instant_return') or $attacker->getSpellPerkValue('instant_return'))
        {
            $this->invasion['attacker']['instantReturn'] = true;
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
                    # See if slot $slot has wins_into perk.
                    if($this->invasion['result']['success'])
                    {
                        if($attacker->race->getUnitPerkValueForUnitSlot($slot, 'wins_into'))
                        {
                            $returnsAsSlot = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'wins_into');
                            $returningUnitKey = 'military_unit' . $returnsAsSlot;
                        }
                        if($someWinIntoPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'some_win_into'))
                        {
                            $ratio = (float)$someWinIntoPerk[0] / 100;
                            $newSlot = (int)$someWinIntoPerk[1];
                            
                            $someWinIntoMultiplier = 1;
                            $someWinIntoMultiplier += $attacker->getSpellPerkMultiplier('some_win_into_mod');

                            if(isset($units[$slot]))
                            {
                                $newUnits = (int)floor($units[$slot] * $ratio * $someWinIntoMultiplier);
                                $someWinIntoUnits[$newSlot] += $newUnits;
                                $amountReturning -= $newUnits;
                            }
                        }
                    }

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
                    if(isset($this->invasion['attacker']['units_lost'][$slot]))
                    {
                        $casualties = $this->invasion['attacker']['units_lost'][$slot];

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

                        if($this->invasion['result']['success'] and $diesIntoMultiplePerkOnVictory = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_victory'))
                        {
                            # Which unit do they die into?
                            $newUnitSlot = $diesIntoMultiplePerkOnVictory[0];
                            $newUnitAmount = (float)$diesIntoMultiplePerkOnVictory[1];
                            $newUnitKey = "military_unit{$newUnitSlot}";
                            $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($attacker, $newUnitSlot);

                            $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                        }

                        if(!$this->invasion['result']['success'] and $diesIntoMultiplePerkOnVictory = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_victory'))
                        {
                            # Which unit do they die into?
                            $newUnitSlot = $diesIntoMultiplePerkOnVictory[0];
                            $newUnitAmount = $diesIntoMultiplePerkOnVictory[2];
                            $newUnitKey = "military_unit{$newUnitSlot}";
                            $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($attacker, $newUnitSlot);

                            $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                        }

                        # Check for faster_return_from_terrain
                        if($fasterReturnFromTerrainPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_from_terrain'))
                        {

                            $perChunk = $fasterReturnFromTerrainPerk[0];
                            $chunkSize = $fasterReturnFromTerrainPerk[1];
                            $terrainKey = $fasterReturnFromTerrainPerk[1];
                            $maxPerk = $fasterReturnFromTerrainPerk[1];

                            $ticksFaster = ($attacker->{'terrain_' . $terrainKey} / $attacker->land) * 100 / $chunkSize * $perChunk;
                            $ticksFaster = min($ticksFaster, $maxPerk);

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

            $this->invasion['attacker']['units_returning_raw'] = $returningUnits;

            foreach($returningUnits as $unitKey => $unitKeyTicks)
            {
                foreach($unitKeyTicks as $unitTypeTick => $amount)
                {
                    if($amount > 0)
                    {
                        $this->queueService->queueResources(
                            'invasion',
                            $attacker,
                            [$unitKey => $amount],
                            $unitTypeTick
                        );
                    }
                }

                $slot = str_replace('military_unit', '', $unitKey);
                $this->invasion['attacker']['units_returning'][$slot] = array_sum($unitKeyTicks);
            }
        }
    }

    protected function handleDefensiveConversions(Dominion $defender, array $defensiveConversions): void
    {
        if(array_sum($defensiveConversions) > 0)
        {
            # Defensive conversions take 6 ticks to appear
            foreach($defensiveConversions as $slot => $amount)
            {
                $unitKey = 'military_unit'.$slot;
                $this->queueService->queueResources(
                    'training',
                    $defender,
                    [$unitKey => $amount],
                    6
                );
            }
        }
    }

    /**
     * Handles spells cast after invasion.
     *
     * @param Dominion $attacker
     * @param Dominion $target (here becomes $defender)
     */
    protected function handleInvasionSpells(Dominion $attacker, Dominion $defender): void
    {

        $isInvasionSpell = True;

        /*
            Spells to check for:
            [AFFLICTED]
              - [ATTACKER] Pestilence: Within 50% of target's DP? Cast.
              - [ATTACKER] Great Fever: Is Invasion successful? Cast.
              - [DEFENDER] Unhealing Wounds: Is target Afflicted? Cast.
            [/AFFLICTED]
        */

        if($attacker->race->name == 'Afflicted')
        {
            # Pestilence
            if($this->invasion['attacker']['op'] / $this->invasion['defender']['dp'] >= 0.50)
            {
                $this->spellActionService->castSpell($attacker, 'pestilence', $defender, $isInvasionSpell);
                $this->invasion['attacker']['invasion_spell'][] = 'pestilence';
            }

            # Great Fever
            if($this->invasion['result']['success'])
            {
                $this->spellActionService->castSpell($attacker, 'great_fever', $defender, $isInvasionSpell);
                $this->invasion['attacker']['invasion_spell'][] = 'great_fever';
            }
        }

        if($defender->race->name == 'Afflicted')
        {
            # Festering Wounds
            $this->spellActionService->castSpell($defender, 'festering_wounds', $attacker, $isInvasionSpell);
            $result['attacker']['invasion_spell'][] = 'festering_wounds';

            # Not an invasion spell, but this goes here for now (Miasmic Charges)
            if($defender->getSpellPerkValue('resource_lost_on_invasion') and !$this->invasion['result']['overwhelmed'])
            {
                $spell = Spell::where('key', 'miasmic_charges')->first();
                $perkValueArray = $spell->getActiveSpellPerkValues($spell->key, 'resource_lost_on_invasion');

                $ratio = (float)$perkValueArray[0] / 100;
                $resourceKey = (string)$perkValueArray[1];
                $resourceAmountOwned = $this->resourceCalculator->getAmount($defender, $resourceKey);
                $resourceAmountLost = $resourceAmountOwned * ($ratio * -1);

                $this->invasion['defender']['resources_lost'][$resourceKey] = $resourceAmountLost;

                $this->resourceService->updateResources($defender, [$resourceKey => ($resourceAmountOwned * -1)]);
            }
        }

        # If defender has Pestilence, attacker gets Lesser Pestilence if attacker is not Afflicted and does not have Pestilence or Lesser Pestilence
        if($this->spellCalculator->isSpellActive($defender, 'pestilence') and $attacker->race->name !== 'Afflicted' and !$this->spellCalculator->isSpellActive($attacker, 'pestilence') and !$this->spellCalculator->isSpellActive($attacker, 'pestilence'))
        {
            $caster = $this->spellCalculator->getCaster($defender, 'pestilence');
            $this->spellActionService->castSpell($caster, 'lesser_pestilence', $attacker, $isInvasionSpell);
        }

        # If attacker has Pestilence, defender gets Lesser Pestilence if defender is not Afflicted and does not have Pestilence or Lesser Pestilence
        if($this->spellCalculator->isSpellActive($attacker, 'pestilence') and $defender->race->name !== 'Afflicted' and !$this->spellCalculator->isSpellActive($defender, 'pestilence') and !$this->spellCalculator->isSpellActive($defender, 'pestilence'))
        {
            $caster = $this->spellCalculator->getCaster($attacker, 'pestilence');
            $this->spellActionService->castSpell($caster, 'lesser_pestilence', $defender, $isInvasionSpell);
        }

        if($attacker->race->name == 'Legion' and $defender->race->name == 'Barbarian' and $this->invasion['result']['success'])
        {
            $this->spellActionService->castSpell($attacker, 'annexation', $defender, $isInvasionSpell);
            $this->invasion['result']['annexation'] = true;
        }

        # Extend annexation
        if($this->invasion['attacker']['show_of_force'] and $this->invasion['result']['success'])
        {
            $this->spellActionService->castSpell($attacker, 'annexation', $defender, $isInvasionSpell);
        }
    }

    protected function handleResourceConversions(Dominion $converter, string $mode = 'offense'): void
    {
        # Queue up for attacker
        /*
        if($mode == 'offense')
        {
            foreach($this->invasion['attacker']['resource_conversions'] as $resourceKey => $resourceAmount)
            {
                $this->queueService->queueResources(
                    'invasion',
                    $converter,
                    [('resource_'.$resourceKey) => max(0, $resourceAmount)],
                    12
                );
            }
        }
        */

        # Instantly add for defender
        if($mode == 'defense')
        {
            foreach($this->invasion['defender']['resource_conversions'] as $resourceKey => $resourceAmount)
            {
                $this->resourceService->updateResources($converter, [$resourceKey => max(0, $resourceAmount)]);
            }
        }
    }

    /**
     * Handles the salvaging of lumber, ore, and gem costs of units.
     * Also handles plunders unit perk. Because both use the same queue value.
     *
     * @param Dominion $attacker
     * @param Dominion $defender
     */
    protected function handleSalvagingAndPlundering(Dominion $attacker, Dominion $defender): void
    {
        foreach($attacker->race->resources as $resourceKey)
        {
            $result['attacker']['plunder'][$resourceKey] = 0;
        }

        $result['attacker']['salvage']['ore'] = 0;
        $result['attacker']['salvage']['lumber'] = 0;
        $result['attacker']['salvage']['gems'] = 0;

        $result['defender']['salvage']['ore'] = 0;
        $result['defender']['salvage']['lumber'] = 0;
        $result['defender']['salvage']['gems'] = 0;

        # Defender: Salvaging
        if($salvaging = $defender->race->getPerkMultiplier('salvaging'))
        {
            $unitCosts = $this->trainingCalculator->getTrainingCostsPerUnit($defender);
            foreach($this->invasion['defender']['units_lost'] as $slot => $amountLost)
            {
                if($slot !== 'draftees' and $slot !== 'peasants')
                {
                    $unitType = 'unit'.$slot;
                    $unitOreCost = isset($unitCosts[$unitType]['ore']) ? $unitCosts[$unitType]['ore'] : 0;
                    $unitLumberCost = isset($unitCosts[$unitType]['lumber']) ? $unitCosts[$unitType]['lumber'] : 0;
                    $unitGemCost = isset($unitCosts[$unitType]['gems']) ? $unitCosts[$unitType]['gems'] : 0;

                    $result['defender']['salvage']['ore'] += $amountLost * $unitOreCost * $salvaging;
                    $result['defender']['salvage']['lumber'] += $amountLost * $unitLumberCost * $salvaging;
                    $result['defender']['salvage']['gems'] += $amountLost * $unitGemCost * $salvaging;
                }
            }

            # Update statistics
            $this->statsService->updateStat($defender, 'ore_salvaged', $result['defender']['salvage']['ore']);
            $this->statsService->updateStat($defender, 'lumber_salvaged', $result['defender']['salvage']['lumber']);
            $this->statsService->updateStat($defender, 'gems_salvaged', $result['defender']['salvage']['gems']);
        }

        # Attacker gets no salvage or plunder if attack fails.
        if(!$this->invasion['result']['success'])
        {
            return;
        }

        # Attacker: Salvaging
        if($salvaging = $attacker->race->getPerkMultiplier('salvaging'))
        {
            $unitCosts = $this->trainingCalculator->getTrainingCostsPerUnit($attacker);
            foreach($this->invasion['attacker']['units_lost'] as $slot => $amountLost)
            {
                $unitType = 'unit'.$slot;
                $unitOreCost = isset($unitCosts[$unitType]['ore']) ? $unitCosts[$unitType]['ore'] : 0;
                $unitLumberCost = isset($unitCosts[$unitType]['lumber']) ? $unitCosts[$unitType]['lumber'] : 0;
                $unitGemCost = isset($unitCosts[$unitType]['gems']) ? $unitCosts[$unitType]['gems'] : 0;

                $result['attacker']['salvage']['ore'] += $amountLost * $unitOreCost * $salvaging;
                $result['attacker']['salvage']['lumber'] += $amountLost * $unitLumberCost * $salvaging;
                $result['attacker']['salvage']['gems'] += $amountLost * $unitGemCost * $salvaging;

                # Update statistics
                $this->statsService->updateStat($attacker, 'ore_salvaged', $result['attacker']['salvage']['ore']);
                $this->statsService->updateStat($attacker, 'lumber_salvaged', $result['attacker']['salvage']['lumber']);
                $this->statsService->updateStat($attacker, 'gems_salvaged', $result['attacker']['salvage']['gems']);
            }
        }

        # Attacker: Plundering
        foreach($this->invasion['attacker']['units_surviving'] as $slot => $amount)
        {
            if($plunderPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot,'plunders'))
            {
                foreach($plunderPerk as $plunder)
                {
                    $resourceToPlunder = $plunder[0];
                    $amountPlunderedPerUnit = (float)$plunder[1];

                    $amountToPlunder = $amount * $amountPlunderedPerUnit;
                    $result['attacker']['plunder'][$resourceToPlunder] += $amountToPlunder;
                }

                #dump($amountToPlunder . ' ' . $resourceToPlunder . ' plundered by unit' . $slot . '(' . $amountPlunderedPerUnit . ' each: ' . number_format($amount) . ' survivors)');
            }

            if($plunderPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot,'plunder'))
            {
                $resourceToPlunder = $plunderPerk[0];
                $amountPlunderedPerUnit = (float)$plunderPerk[1];

                $amountToPlunder = $amount * $amountPlunderedPerUnit;
                $result['attacker']['plunder'][$resourceToPlunder] += $amountToPlunder;

                #dump($amountToPlunder . ' ' . $resourceToPlunder . ' plundered by unit' . $slot . '(' . $amountPlunderedPerUnit . ' each: ' . number_format($amount) . ' survivors)');
            }
        }

        # Remove plundered resources from defender.
        foreach($result['attacker']['plunder'] as $resourceKey => $amount)
        {
            if($amount > 0)
            {
                $result['attacker']['plunder'][$resourceKey] = min($amount, $this->resourceCalculator->getAmount($defender, $resourceKey));
                $this->resourceService->updateResources($defender, [$resourceKey => ($result['attacker']['plunder'][$resourceKey] * -1)]);
            }
        }

        # Add salvaged resources to defender.
        foreach($result['defender']['salvage'] as $resourceKey => $amount)
        {
            if($amount > 0)
            {
                $this->resourceService->updateResources($defender, [$resourceKey => $amount]);
            }
        }

        # Moved to separate function for attackers to  be able to have salage, plunder, and conversions (Spirit)
        # See handleResourceGainsForAttacker()
        # Queue plundered and salvaged resources to attacker.
        /*
        foreach($result['attacker']['plunder'] as $resourceKey => $amount)
        {
            # If the resource is ore, lumber, or gems, also check for salvaged resources.
            if(in_array($resourceKey, ['ore', 'lumber', 'gems']))
            {
                $amount += $result['attacker']['salvage'][$resourceKey];
                $this->statsService->updateStat($attacker, ($resourceKey . '_salvaged'), $result['attacker']['salvage'][$resourceKey]);
            }

            if($amount > 0)
            {
                $this->statsService->updateStat($attacker, ($resourceKey . '_plundered'), $amount);
                $this->queueService->queueResources('invasion',$attacker,['resource_'.$resourceKey => $amount]);
            }
        }
        */

        $this->invasion['attacker']['salvage'] = $result['attacker']['salvage'];
        $this->invasion['attacker']['plunder'] = $result['attacker']['plunder'];
        $this->invasion['defender']['salvage'] = $result['defender']['salvage'];
    }

    # Add casualties to the Imperial Crypt.
    protected function handleCrypt(Dominion $attacker, Dominion $defender, array $offensiveConversions, array $defensiveConversions): void
    {

        if(in_array($attacker->round->mode, ['factions','factions-duration','deathmatch','deathmatch-duration','artefacts']))
        {
            return;
        }

        if($attacker->race->alignment === 'evil' or $defender->race->alignment === 'evil')
        {

            $cryptLogString = '';

            $this->invasion['defender']['crypt'] = [];
            $this->invasion['attacker']['crypt'] = [];

            # The battlefield:
            # Cap bodies by reduced conversions perk, and round.
            $defensiveBodies = round(array_sum($this->invasion['defender']['units_lost']) * $this->conversionCalculator->getConversionReductionMultiplier($defender));
            $offensiveBodies = round(array_sum($this->invasion['attacker']['units_lost']) * $this->conversionCalculator->getConversionReductionMultiplier($attacker));

            $cryptLogString .= '[CRYPT] Defensive bodies (raw): ' . number_format($defensiveBodies) . ' | ';
            $cryptLogString .= 'Offensive bodies (raw): ' . number_format($offensiveBodies) . ' | ';

            $this->invasion['defender']['crypt']['bodies_available_raw'] = $defensiveBodies;
            $this->invasion['attacker']['crypt']['bodies_available_raw'] = $offensiveBodies;

            # Loop through defensive casualties and remove units that don't qualify.
            foreach($this->invasion['defender']['units_lost'] as $slot => $lost)
            {
                if($slot !== 'draftees' and $slot !== 'peasants')
                {
                    if(!$this->conversionHelper->isSlotConvertible($slot, $defender) and !$defender->race->getUnitPerkValueForUnitSlot($slot, 'dies_into'))
                    {
                        $defensiveBodies -= $lost;
                    }
                }
            }

            # Loop through offensive casualties and remove units that don't qualify.
            foreach($this->invasion['attacker']['units_lost'] as $slot => $lost)
            {
                if($slot !== 'draftees')
                {
                    if(!$this->conversionHelper->isSlotConvertible($slot, $attacker) or $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into'))
                    {
                        $offensiveBodies -= $lost;
                    }
                }
            }

            # Remove defensive conversions (defender's conversions) from offensive bodies (they are spent)
            if(isset($this->invasion['defender']['conversion']))
            {
                $offensiveBodies -= array_sum($this->invasion['defender']['conversion']);
            }

            # Remove offensive conversions (attacker's conversions) from defensive bodies (they are spent)
            if(isset($this->invasion['attacker']['conversion']))
            {
                $defensiveBodies -= array_sum($this->invasion['attacker']['conversion']);
            }

            $this->invasion['defender']['crypt']['bodies_available_net'] = $defensiveBodies;
            $this->invasion['attacker']['crypt']['bodies_available_net'] = $offensiveBodies;

            $cryptLogString .= '[CRYPT] Defensive bodies (net): ' . number_format($defensiveBodies) . ' | ';
            $cryptLogString .= 'Offensive bodies (net): ' . number_format($offensiveBodies) . ' | ';

            $toTheCrypt = 0;

            # If defender is empire
            if($defender->race->alignment === 'evil')
            {
                  $whoHasCrypt = 'defender';
                  # If the attack is successful
                  if($this->invasion['result']['success'])
                  {
                      # 50% of defensive and 0% of offensive bodies go to the crypt.
                      $defensiveBodies /= 2;
                      $offensiveBodies *= 0;
                  }
                  # If the attack is unsuccessful
                  else
                  {
                      # 100% of defensive and 100% of offensive bodies go to the crypt.
                      $defensiveBodies += 0;
                      $offensiveBodies += 0;
                  }
            }
            # If attacker is empire
            if($attacker->race->alignment === 'evil')
            {
                  $whoHasCrypt = 'attacker';
                  # If the attack is successful
                  if($this->invasion['result']['success'])
                  {
                      # 50% of defensive and 100% of offensive bodies go to the crypt.
                      $defensiveBodies /= 2;
                      $offensiveBodies *= 1;
                  }
                  # If the attack is unsuccessful
                  else
                  {
                      # 0% of defensive and 0% of offensive bodies go to the crypt.
                      $defensiveBodies *= 0;
                      $offensiveBodies *= 0;
                  }
            }

            $cryptLogString .= 'Defensive bodies (final): ' . number_format($defensiveBodies) . ' | ';
            $cryptLogString .= 'Offensive bodies (final): ' . number_format($offensiveBodies) . ' | ';

            $toTheCrypt = max(0, round($defensiveBodies + $offensiveBodies));

            if($whoHasCrypt == 'defender')
            {
                $this->invasion['result']['crypt']['defensive_bodies'] = $defensiveBodies;
                $this->invasion['result']['crypt']['offensive_bodies'] = $offensiveBodies;
                $this->invasion['result']['crypt']['total'] = $toTheCrypt;

                $cryptLogString .= '* Bodies currently in crypt: ' . number_format($this->resourceCalculator->getRealmAmount($defender->realm, 'body')) . ' | ';

                $this->resourceService->updateRealmResources($defender->realm, ['body' => $toTheCrypt]);

                $cryptLogString .= '* Bodies added to crypt: ' . number_format($toTheCrypt) . ' *';
            }
            elseif($whoHasCrypt == 'attacker')
            {
                $this->invasion['result']['crypt']['defensive_bodies'] = $defensiveBodies;
                $this->invasion['result']['crypt']['offensive_bodies'] = $offensiveBodies;
                $this->invasion['result']['crypt']['total'] = $toTheCrypt;

                $cryptLogString .= '* Bodies currently in crypt: ' . number_format($this->resourceCalculator->getRealmAmount($attacker->realm, 'body')) . ' | ';

                $this->resourceService->updateRealmResources($attacker->realm, ['body' => $toTheCrypt]);

                $cryptLogString .= '* Bodies added to crypt: ' . number_format($toTheCrypt) . ' *';
            }

            Log::info($cryptLogString);

        }

    }

    protected function handleWatchedDominions(Dominion $attacker, Dominion $defender): void
    {

        /*
        $attackerWatchers = WatchedDominion::where('dominion_id', $attacker->id)->get();
        $defenderWatchers = WatchedDominion::where('dominion_id', $defender->id)->get();

        foreach($attackerWatchers as $attackerWatcher)
        {
            if($attackerWatcher->id !== $attacker->id and $attackerWatcher->id !== $defender->id)
            {
                # Queue notification
                $this->notificationService->queueNotification('watched_dominion_invasion', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $attacker->id,
                    'defenderDominionId' => $defender->id,
                    'land_conquered' => $this->landLost
                ]);
            }
        }

        foreach($defenderWatchers as $defenderWatcher)
        {
            if($defenderWatcher->id !== $attacker->id and $defenderWatcher->id !== $defender->id)
            {
                # Queue notification
                $this->notificationService->queueNotification('watched_dominion_invaded', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $attacker->id,
                    'defenderDominionId' => $defender->id,
                    'land_lost' => $this->landLost
                ]);
            }
        }
        */
    }

    protected function handleResourceGainsForAttacker(Dominion $attacker): void
    {
        $resourcesToQueue = [];

        foreach($attacker->race->resources as $resourceKey)
        {
            $resourcesToQueue[$resourceKey] = 0;
            $resourcesToQueue[$resourceKey] += $this->invasion['attacker']['salvage'][$resourceKey] ?? 0;
            $resourcesToQueue[$resourceKey] += $this->invasion['attacker']['plunder'][$resourceKey] ?? 0;
            $resourcesToQueue[$resourceKey] += $this->invasion['attacker']['resource_conversions'][$resourceKey] ?? 0;
        }

        foreach($resourcesToQueue as $resourceKey => $amount)
        {
            $this->queueService->queueResources(
                'invasion',
                $attacker,
                [('resource_'.$resourceKey) => max(0, $amount)],
                12
            );
        }
    }

    /**
     * Check for events that take place before the invasion:
     *  Beastfolk Ambush
     *
     * @param Dominion $attacker
     * @return void
     */
    protected function handleBeforeInvasionPerks(Dominion $attacker): void
    {
        # Check for Ambush
        $this->isAmbush = false;

        if($this->militaryCalculator->getRawDefenseAmbushReductionRatio($attacker) > 0)
        {
            $this->isAmbush = true;
        }

        $this->invasion['attacker']['ambush'] = $this->isAmbush;
    }

    /**
     * Check whether the invasion is successful.
     *
     * @param Dominion $attacker
     * @param Dominion $target ($defender is passed)
     * @param array $units
     * @return void
     */
    protected function checkInvasionSuccess(Dominion $attacker, Dominion $target, array $units): void
    {
        $landRatio = $this->rangeCalculator->getDominionRange($attacker, $target) / 100;

        $attackingForceOP = $this->militaryCalculator->getOffensivePower($attacker, $target, $landRatio, $units, [], true);
        $targetDP = $this->getDefensivePowerWithTemples($attacker, $target, $units, $landRatio, $this->isAmbush);
        
        $attackingForceRawOP = $this->militaryCalculator->getOffensivePowerRaw($attacker, $target, $landRatio, $units, [], true);
        $targetRawDP = $this->militaryCalculator->getDefensivePowerRaw($target, $attacker, $landRatio, null, 0, $this->isAmbush, true, $this->invasion['attacker']['units_sent'], false, false);

        #$this->invasion['attacker']['psionic_strength'] = $this->dominionCalculator->getPsionicStrength($attacker);
        #$this->invasion['defender']['psionic_strength'] = $this->dominionCalculator->getPsionicStrength($target);

        $this->invasion['attacker']['op'] = $attackingForceOP;
        $this->invasion['defender']['dp'] = $targetDP;

        $this->invasion['attacker']['op_raw'] = $attackingForceRawOP;
        $this->invasion['defender']['dp_raw'] = $targetRawDP;

        $this->invasion['attacker']['op_multiplier'] = $this->militaryCalculator->getOffensivePowerMultiplier($attacker, $target, $landRatio, $units, [], true);
        $this->invasion['attacker']['op_multiplier_reduction'] = $this->militaryCalculator->getOffensiveMultiplierReduction($target)-1;
        $this->invasion['attacker']['op_multiplier_net'] = $this->invasion['attacker']['op_multiplier'] - $this->invasion['attacker']['op_multiplier_reduction'];

        $this->invasion['defender']['dp_multiplier'] = $this->militaryCalculator->getDefensivePowerMultiplier($attacker, $target, $this->militaryCalculator->getDefensiveMultiplierReduction($attacker));
        $this->invasion['defender']['dp_multiplier_reduction'] = $this->militaryCalculator->getDefensiveMultiplierReduction($attacker);
        $this->invasion['defender']['dp_multiplier_net'] = $this->invasion['defender']['dp_multiplier'] - $this->invasion['defender']['dp_multiplier_reduction'];

        $this->invasion['result']['success'] = ($attackingForceOP > $targetDP);

        $this->invasion['result']['op_dp_ratio'] = $attackingForceOP / $targetDP;
        $this->invasion['result']['op_dp_ratio_raw'] = $attackingForceRawOP / $targetRawDP;

        $this->statsService->setStat($attacker, 'op_sent_max', max($this->invasion['attacker']['op'], $this->statsService->getStat($attacker, 'op_sent_max')));
        $this->statsService->updateStat($attacker, 'op_sent_total', $this->invasion['attacker']['op']);

        if(request()->getHost() === 'odarena.com')
        {
            $day = $attacker->round->start_date->subDays(1)->diffInDays(now());
            $day = sprintf('%02d', $day);
            $this->statsService->setRoundStat($attacker->round, ('day' . $day . '_top_op'), max($this->invasion['attacker']['op'], $this->statsService->getRoundStat($attacker->round, ('day' . $day . '_top_op'))));
        }

        if($this->invasion['result']['success'])
        {
            $this->statsService->setStat($target, 'dp_failure_max', max($this->invasion['defender']['dp'], $this->statsService->getStat($attacker, 'dp_failure_max')));
        }
        else
        {
            $this->statsService->setStat($target, 'dp_success_max', max($this->invasion['defender']['dp'], $this->statsService->getStat($attacker, 'dp_success_max')));
        }
    }

    /**
     * Check whether the attackers got overwhelmed by the target's defending army.
     *
     * Overwhelmed attackers have increased casualties, while the defending
     * party has reduced casualties.
     *
     */
    protected function checkOverwhelmed(): void
    {
        // Never overwhelm on successful invasions
        $this->invasion['result']['overwhelmed'] = false;

        if ($this->invasion['result']['success'])
        {
            return;
        }

        $attackingForceOP = $this->invasion['attacker']['op'];
        $targetDP = $this->invasion['defender']['dp'];

        $this->invasion['result']['overwhelmed'] = ((1 - $attackingForceOP / $targetDP) >= (static::OVERWHELMED_PERCENTAGE / 100));
    }

    /*
    *   0) Add OP from annexed dominions (already done when calculating attacker's OP)
    *   1) Remove OP units from annexed dominions.
    *   2) Incur 10% casualties on annexed units.
    *   3) Queue returning units.
    *   4) Save data to $this->invasion to create pretty battle report
    */
    protected function handleAnnexedDominions(Dominion $attacker, Dominion $defender, array $units): void
    {

        $casualties = 0.10; # / because we want to invert the ratio

        $legion = null;
        if($this->spellCalculator->hasAnnexedDominions($attacker))
        {
            $legion = $attacker;
            $legionString = 'attacker';
            $casualties /= $this->invasion['result']['op_dp_ratio'];
        }
        elseif($this->spellCalculator->hasAnnexedDominions($defender))
        {
            $legion = $defender;
            $legionString = 'defender';
            $casualties *= $this->invasion['result']['op_dp_ratio'];

            if($this->invasion['result']['overwhelmed'])
            {
                $casualties = 0;
            }
        }

        if($defender->race->getPerkValue('does_not_kill'))
        {
            $casualties = 0;
        }

        $casualties = min(max(0, $casualties), 0.20);

        if($legion and $this->invasion['result']['op_dp_ratio'] >= 0.85)
        {
            $this->invasion[$legionString]['annexation'] = [];
            $this->invasion[$legionString]['annexation']['hasAnnexedDominions'] = count($this->spellCalculator->getAnnexedDominions($legion));
            $this->invasion[$legionString]['annexation']['annexedDominions'] = [];

            foreach($this->spellCalculator->getAnnexedDominions($legion) as $annexedDominion)
            {
                $this->invasion[$legionString]['annexation']['annexedDominions'][$annexedDominion->id] = [];
                $this->invasion[$legionString]['annexation']['annexedDominions'][$annexedDominion->id]['units_sent'] = [1 => $annexedDominion->military_unit1, 2 => 0, 3 => 0, 4 => $annexedDominion->military_unit4];

                # If there are troops to send and if defender is not a Barbarian
                if(array_sum($this->invasion[$legionString]['annexation']['annexedDominions'][$annexedDominion->id]['units_sent']) > 0 and $defender->race->name !== 'Barbarian')
                {
                    # Incur casualties
                    $this->invasion[$legionString]['annexation']['annexedDominions'][$annexedDominion->id]['units_lost'] =      [1 => (int)round($annexedDominion->military_unit1 * $casualties), 2 => 0, 3 => 0, 4 => (int)round($annexedDominion->military_unit4 * $casualties)];
                    $this->invasion[$legionString]['annexation']['annexedDominions'][$annexedDominion->id]['units_returning'] = [1 => (int)round($annexedDominion->military_unit1 * (1 - $casualties)), 2 => 0, 3 => 0, 4 => (int)round($annexedDominion->military_unit4 * (1 - $casualties))];

                    # Remove the units
                    $annexedDominion->military_unit1 -= $annexedDominion->military_unit1;
                    $annexedDominion->military_unit4 -= $annexedDominion->military_unit4;

                    # Queue the units
                    foreach($this->invasion[$legionString]['annexation']['annexedDominions'][$annexedDominion->id]['units_returning'] as $slot => $returning)
                    {
                        $unitType = 'military_unit' . $slot;

                        $this->queueService->queueResources(
                            'invasion',
                            $annexedDominion,
                            [$unitType => $returning],
                            12
                        );
                    }

                    $annexedDominion->save();
                }
            }
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
    protected function allUnitsHaveOP(Dominion $attacker, array $units, Dominion $target, float $landRatio): bool
    {
        foreach ($attacker->race->units as $unit)
        {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0))
            {
                continue;
            }

            if ($this->militaryCalculator->getUnitPowerWithPerks($attacker, $target, $landRatio, $unit, 'offense', null, $units, $this->invasion['defender']['units_defending']) === 0.0 and $unit->getPerkValue('sendable_with_zero_op') != 1)
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
    protected function passes43RatioRule(Dominion $attacker, Dominion $target, float $landRatio, array $units): bool
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
     * Check if an invasion passes the 4:3-rule.
     *
     * @param Dominion $attacker
     * @param array $units
     * @return bool
     */
    protected function passesMinimumDpaCheck(Dominion $attacker, Dominion $target, float $landRatio, array $units): bool
    {
        $attackingForceOP = $this->militaryCalculator->getOffensivePower($attacker, $target, $landRatio, $units);

        return ($attackingForceOP > $this->landCalculator->getTotalLand($target) * static::MINIMUM_DPA);
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

    protected function getDefensivePowerWithTemples(
      Dominion $attacker,
      Dominion $target,
      array $units,
      float $landRatio,
      bool $isAmbush
      ): float
    {
        $dpMultiplierReduction = $this->militaryCalculator->getDefensiveMultiplierReduction($attacker);

        // Void: immunity to DP mod reductions
        if ($target->getSpellPerkValue('immune_to_temples'))
        {
            $dpMultiplierReduction = 0;
        }

        return $this->militaryCalculator->getDefensivePower(
                                                            $target,
                                                            $attacker,
                                                            $landRatio,
                                                            null,
                                                            $dpMultiplierReduction,
                                                            $this->isAmbush,
                                                            false,
                                                            $units, # Becomes $invadingUnits
                                                          );
    }

}
