<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Log;
use OpenDominion\Exceptions\GameException;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Building;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Unit;

use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ConversionCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;

use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;

class ArtefactActionService
{

    /**
     * @var int The minimum morale required to initiate an invasion
     */
    protected const MIN_MORALE = 50;

    /**
     * @var float Failing an invasion by this percentage (or more) results in 'being overwhelmed'
     */
    protected const OVERWHELMED_PERCENTAGE = 15.0;

    /**
     * @var float Percentage of units to be stunned
     */
    protected const STUN_RATIO = 1;

    /**
     * @var float Lowest possible DPA.
     */
    protected const MINIMUM_DPA = 10;

    /** @var array Invasion result array. todo: Should probably be refactored later to its own class */
    protected $invasionResult = [
        'result' => [],
        'attacker' => [
            'units_lost' => [],
        ],
        'defender' => [
            'units_lost' => [],
        ],
    ];

    // todo: refactor to use $invasionResult instead
    /** @var int The amount of land lost during the invasion */
    protected $landLost = 0;

    /** @var int The amount of units lost during the invasion */
    protected $unitsLost = 0;

    public function __construct()
    {
        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->casualtiesCalculator = app(CasualtiesCalculator::class);
        $this->conversionCalculator = app(ConversionCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->protectionService = app(ProtectionService::class);
        $this->statsService = app(StatsService::class);
        $this->queueService = app(QueueService::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->resourceService = app(ResourceService::class);
        $this->spellActionService = app(SpellActionService::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->spellHelper = app(SpellHelper::class);
        $this->trainingCalculator = app(TrainingCalculator::class);
        $this->raceHelper = app(RaceHelper::class);
        $this->unitHelper = app(UnitHelper::class);
    }

    /**
     * Invades dominion $target from $dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return array
     * @throws GameException
     */
    public function invade(Dominion $dominion, Dominion $target, array $units): array
    {

        $now = time();

        DB::transaction(function () use ($dominion, $target, $units, $now) {
            // Checks

            if ($this->protectionService->isUnderProtection($dominion))
            {
                throw new GameException('You cannot invade while under protection.');
            }

            if ($this->protectionService->isUnderProtection($target))
            {
                throw new GameException('You cannot invade dominions which are under protection.');
            }

            if (!$this->rangeCalculator->isInRange($dominion, $target))
            {
                throw new GameException('You cannot invade dominions outside of your range.');
            }

            if ($dominion->round->id !== $target->round->id)
            {
                throw new GameException('Nice try, but you cannot invade cross-round.');
            }

            if ($dominion->realm->id === $target->realm->id and ($dominion->round->mode == 'standard' or $dominion->round->mode == 'standard-duration'))
            {
                throw new GameException('You can only invade other dominions in the same realm in deathmatch rounds.');
            }

            if ($dominion->id == $target->id)
            {
                throw new GameException('Nice try, but you cannot invade yourself.');
            }

            foreach($dominion->race->resources as $resourceKey)
            {
                if($resourceCostToInvade = $dominion->race->getPerkValue($resourceKey . '_to_invade'))
                {
                    if($this->resourceCalculator->getAmount($dominion, $resourceKey) < $resourceCostToInvade)
                    {
                        $resource = Resource::where('key', $resourceKey)->first();
                        throw new GameException('You do not have enough ' . str_plural($resource->name, $resourceCostToInvade) . ' to invade. You have ' . number_format($this->resourceCalculator->getAmount($dominion, $resourceKey)) . ' and you need at least ' . number_format($resourceCostToInvade) . '.');
                    }
                    else
                    {
                        $this->resourceService->updateResources($dominion, [$resourceKey => $resourceCostToInvade*-1]);
                    }
                }
            }

            // Sanitize input
            $units = array_map('intval', array_filter($units));
            $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target);
            $this->invasionResult['land_ratio'] = $landRatio;
            $landRatio /= 100;

            # Populate units defending
            for ($slot = 1; $slot <= $target->race->units->count(); $slot++)
            {
                $unit = $target->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                  if($this->militaryCalculator->getUnitPowerWithPerks($target, null, null, $unit, 'defense') !== 0.0)
                  {
                      $this->invasionResult['defender']['units_defending'][$slot] = $target->{'military_unit'.$slot};
                  }
            }

            if (!$this->hasAnyOP($dominion, $units))
            {
                throw new GameException('You need to send at least some units.');
            }

            if (!$this->allUnitsHaveOP($dominion, $units, $target, $landRatio))
            {
                throw new GameException('You cannot send units that have no offensive power.');
            }

            if (!$this->hasEnoughUnitsAtHome($dominion, $units))
            {
                throw new GameException('You don\'t have enough units at home to send this many units.');
            }

            if ($dominion->race->name !== 'Barbarian')
            {
                if ($dominion->morale < static::MIN_MORALE)
                {
                    throw new GameException('You do not have enough morale to invade.');
                }

                if (!$this->passes43RatioRule($dominion, $target, $landRatio, $units))
                {
                    throw new GameException('You are sending out too much OP, based on your new home DP (4:3 rule).');
                }

                if (!$this->passesUnitSendableCapacityCheck($dominion, $units))
                {
                    throw new GameException('You do not have enough caverns to send out this many units.');
                }
            }

            foreach($units as $slot => $amount)
            {
                if($amount < 0)
                {
                    throw new GameException('Invasion was canceled due to bad input.');
                }

                # OK, unit can be trained. Let's check for pairing limits.
                if($this->unitHelper->unitHasCapacityLimit($dominion, $slot) and !$this->unitHelper->checkUnitLimitForInvasion($dominion, $slot, $amount))
                {
                    $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();

                    throw new GameException('You can at most control ' . number_format($this->unitHelper->getUnitMaxCapacity($dominion, $slot)) . ' ' . str_plural($unit->name) . '. To control more, you need to first have more of their superior unit.');
                }
             }

            if ($dominion->race->getPerkValue('cannot_invade'))
            {
                throw new GameException($dominion->race->name . ' cannot invade other dominions.');
            }

            // Spell: Rainy Season (cannot invade)
            if ($this->spellCalculator->isSpellActive($dominion, 'rainy_season'))
            {
                throw new GameException('You cannot invade during the Rainy Season.');
            }
            if ($dominion->getSpellPerkValue('cannot_invade'))
            {
                throw new GameException('A spell is preventing from you invading.');
            }

            // Cannot invade until round has started.
            if(!$dominion->round->hasStarted())
            {
                throw new GameException('You cannot invade until the round has started.');
            }

            // Cannot invade after round has ended.
            if($dominion->round->hasEnded())
            {
                throw new GameException('You cannot invade after the round has ended.');
            }

            // Qur: Statis cannot be invaded.
            if($target->getSpellPerkValue('stasis'))
            {
                throw new GameException('A magical stasis surrounds the Qurrian lands, making it impossible for your units to invade.');
            }

            // Qur: Statis cannot invade.
            if($dominion->getSpellPerkValue('stasis'))
            {
                throw new GameException('You cannot invade while you are in stasis.');
            }

            $this->invasionResult['defender']['recently_invaded_count'] = $this->militaryCalculator->getRecentlyInvadedCount($target);
            $this->invasionResult['attacker']['units_sent'] = $units;
            $this->invasionResult['attacker']['land_size'] = $this->landCalculator->getTotalLand($dominion);
            $this->invasionResult['defender']['land_size'] = $this->landCalculator->getTotalLand($target);

            $this->invasionResult['attacker']['fog'] = $dominion->getSpellPerkValue('fog_of_war') ? true : false;
            $this->invasionResult['defender']['fog'] = $target->getSpellPerkValue('fog_of_war') ? true : false;

            $this->invasionResult['log']['initiated_at'] = $now;
            $this->invasionResult['log']['requested_at'] = $_SERVER['REQUEST_TIME'];

            $attackerCasualties = $this->casualtiesCalculator->getInvasionCasualties($dominion, $this->invasionResult['attacker']['units_sent'], $target, $this->invasionResult, 'offense');
            $defenderCasualties = $this->casualtiesCalculator->getInvasionCasualties($target, $this->invasionResult['defender']['units_defending'], $dominion, $this->invasionResult, 'defense');

            $this->invasionResult['attacker']['units_lost'] = $attackerCasualties;
            $this->invasionResult['defender']['units_lost'] = $defenderCasualties;

            $this->handleCasualties($dominion, $target, $this->invasionResult['attacker']['units_lost'], 'offense');
            $this->handleCasualties($target, $dominion, $this->invasionResult['defender']['units_lost'], 'defense');
            #$this->handleDefensiveDiesIntoPerks($target);

            if (!isset($this->invasionResult['result']['ignore_draftees']))
            {
                $this->invasionResult['defender']['units_defending']['draftees'] = $target->military_draftees;
            }

            # Only count successful, non-in-realm hits over 75% as victories.
            $countsAsVictory = 0;
            $countsAsFailure = 0;
            $countsAsRaze = 0;
            $countsAsBottomfeed = 0;

            # Successful hits over 75% count as victories
            if($landRatio >= 0.75 and $this->invasionResult['result']['success'])
            {
                $countsAsVictory = 1;
            }

            # Successful hits under 75% count as BFs
            if($landRatio < 0.75 and $this->invasionResult['result']['success'])
            {
                $countsAsBottomfeed = 1;
            }

            # Overwhelmed hits count as failures
            if($this->invasionResult['result']['overwhelmed'])
            {
                $countsAsFailure = 1;
            }

            # Non-overwhelmed unsuccessful hits count as tactical razes
            if(!$this->invasionResult['result']['overwhelmed'] and !$this->invasionResult['result']['success'])
            {
                $countsAsRaze = 1;
            }

            $this->handlePrestigeChanges($dominion, $target, $units, $landRatio, $countsAsVictory, $countsAsBottomfeed, $countsAsFailure, $countsAsRaze);
            $this->handleDuringInvasionUnitPerks($dominion, $target, $units);

            $this->handleMoraleChanges($dominion, $target, $landRatio, $units);
            $this->handleResearchPoints($dominion, $target, $units);

            # Dwarg
            #$this->handleStun($dominion, $target, $units, $landRatio);

            # Conversions
            $offensiveConversions = array_fill(1, $dominion->race->units->count(), 0);
            $defensiveConversions = array_fill(1, $target->race->units->count(), 0);
            $conversions = $this->conversionCalculator->getConversions($dominion, $target, $this->invasionResult, $landRatio);

            if(array_sum($conversions['attacker']) > 0)
            {
                $offensiveConversions = $conversions['attacker'];
                $this->invasionResult['attacker']['conversions'] = $offensiveConversions;
                $this->statsService->updateStat($dominion, 'units_converted', array_sum($conversions['attacker']));
            }
            if(array_sum($conversions['defender']) > 0)
            {
                $defensiveConversions = $conversions['defender'];
                $this->invasionResult['defender']['conversions'] = $defensiveConversions;
                $this->statsService->updateStat($target, 'units_converted', array_sum($conversions['defender']));
            }

            $this->handleReturningUnits($dominion, $this->invasionResult['attacker']['surviving_units'], $offensiveConversions, $defensiveConversions);
            $this->handleDefensiveConversions($target, $defensiveConversions);

            # Handle dies_into_resource, dies_into_resources, kills_into_resource, kills_into_resources
            $this->handleResourceConversions($dominion, $target, $landRatio);

            # Imperial Crypt
            #$this->handleCrypt($dominion, $target, $this->invasionResult['attacker']['surviving_units'], $offensiveConversions, $defensiveConversions);

            // Stat changes
            if ($this->invasionResult['result']['success'])
            {
                $this->statsService->updateStat($dominion, 'land_conquered', (int)array_sum($this->invasionResult['attacker']['land_conquered']));
                $this->statsService->updateStat($dominion, 'land_discovered', (int)array_sum($this->invasionResult['attacker']['land_discovered']));
                $this->statsService->updateStat($dominion, 'invasion_victories', $countsAsVictory);
                $this->statsService->updateStat($dominion, 'invasion_bottomfeeds', $countsAsBottomfeed);

                $this->statsService->updateStat($target, 'land_lost', (int)array_sum($this->invasionResult['attacker']['land_conquered']));
                $this->statsService->updateStat($target, 'defense_failures', 1);
            }
            else
            {
                $this->statsService->updateStat($dominion, 'invasion_razes', $countsAsRaze);
                $this->statsService->updateStat($dominion, 'invasion_failures', $countsAsFailure);

                $this->statsService->updateStat($target, 'defense_success', 1);
            }

            # Debug before saving:
            if(request()->getHost() === 'odarena.local' or request()->getHost() === 'odarena.virtual')
            {
                dd($this->invasionResult);
            }

            $this->invasionResult['log']['finished_at'] = time();

            $this->invasionEvent = GameEvent::create([
                'round_id' => $dominion->round_id,
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'target_type' => RealmArtefact::class,
                'target_id' => $target->id,
                'type' => 'invasion',
                'data' => $this->invasionResult,
                'tick' => $dominion->round->ticks
            ]);

            // todo: move to its own method
            // Notification
            if ($this->invasionResult['result']['success']) {
                $this->notificationService->queueNotification('received_invasion', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $dominion->id,
                    'land_lost' => $this->landLost,
                    'units_lost' => $this->unitsLost,
                ]);
            } else {
                $this->notificationService->queueNotification('repelled_invasion', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $dominion->id,
                    'attackerWasOverwhelmed' => $this->invasionResult['result']['overwhelmed'],
                    'units_lost' => $this->unitsLost,
                ]);
            }

            $target->save(['event' => HistoryService::EVENT_ACTION_INVADE]);
            $dominion->save(['event' => HistoryService::EVENT_ACTION_INVADE]);
        });

        $this->notificationService->sendNotifications($target, 'irregular_dominion');

        if ($this->invasionResult['result']['success'])
        {
            $message = sprintf(
                'You are victorious and defeat the forces of %s (#%s), conquering %s new acres of land! During the invasion, your troops also discovered %s acres of land.',
                $target->name,
                $target->realm->number,
                number_format(array_sum($this->invasionResult['attacker']['land_conquered'])),
                number_format(array_sum($this->invasionResult['attacker']['land_discovered']) + array_sum($this->invasionResult['attacker']['extra_land_discovered']))
            );
            $alertType = 'success';
        }
        else
        {
            $message = sprintf(
                'Your army fails to defeat the forces of %s (#%s).',
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

        $attackerPrestigeChange *= max(1, (1 - ($this->invasionResult['defender']['recently_invaded_count']/10)));
        $defenderPrestigeChange *= max(1, (1 - ($this->invasionResult['defender']['recently_invaded_count']/10)));

        $attackerPrestigeChangeMultiplier = 0;

        // Racial perk
        $attackerPrestigeChangeMultiplier += $attacker->race->getPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $this->militaryCalculator->getPrestigeGainsPerk($attacker, $units);
        $attackerPrestigeChangeMultiplier += $attacker->getAdvancementPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $attacker->getBuildingPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $attacker->getImprovementPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $attacker->getSpellPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $attacker->getDeityPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $attacker->realm->getArtefactPerkMultiplier('prestige_gains');
        $attackerPrestigeChangeMultiplier += $attacker->title->getPerkMultiplier('prestige_gains') * $attacker->getTitlePerkMultiplier();

        $attackerPrestigeChange *= (1 + $attackerPrestigeChangeMultiplier);

        // 1/4 gains for hitting Barbarians.
        if($defender->race->name === 'Barbarian')
        {
            $attackerPrestigeChange /= 4;

            # Liberation
            if(
                $attacker->realm->alignment !== 'evil' and
                $this->invasionResult['result']['success'] and
                $this->invasionResult['result']['op_dp_ratio'] >= 1.20 and
                $this->spellCalculator->isAnnexed($defender))
            {
                $this->invasionResult['attacker']['liberation'] = true;
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

        if ($attackerPrestigeChange !== 0)
        {
            if (!$this->invasionResult['result']['success'])
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

            $this->invasionResult['attacker']['prestige_change'] = $attackerPrestigeChange;
        }

        if ($defenderPrestigeChange !== 0)
        {
            $defender->prestige += $defenderPrestigeChange;
            $this->invasionResult['defender']['prestige_change'] = $defenderPrestigeChange;
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
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return array All the units that survived and will return home
     */
    protected function handleCasualties(Dominion $dominion, Dominion $enemy, array $casualties = [], string $mode = 'offense'): void
    {

        if($mode == 'offense')
        {
            foreach ($this->invasionResult['attacker']['units_lost'] as $slot => $amount)
            {
                $dominion->{"military_unit{$slot}"} -= $amount;
                $this->invasionResult['attacker']['surviving_units'][$slot] = $this->invasionResult['attacker']['units_sent'][$slot] - $this->invasionResult['attacker']['units_lost'][$slot];

                if(in_array($slot,[1,2,3,4,5,6,7,8,9,10]))
                {
                    $this->statsService->updateStat($dominion, ('unit' . $slot . '_lost'), $amount);
                }
                else
                {
                    $this->statsService->updateStat($dominion, ($slot . '_lost'), $amount);
                }
            }
        }

        $this->statsService->updateStat($enemy, 'units_killed', array_sum($casualties));
    }

    protected function handleMoraleChanges(Dominion $attacker, Dominion $defender, float $landRatio, array $units): void
    {

        $this->invasionResult['attacker']['morale_change'] = 0;

    }

    /**
     * Handles experience point (research point) generation for attacker.
     *
     * @param Dominion $dominion
     * @param array $units
     */
    protected function handleResearchPoints(Dominion $dominion, Dominion $target, array $units): void
    {

        $researchPointsPerAcre = 40;

        $researchPointsPerAcreMultiplier = 1;

        # Increase RP per acre
        $researchPointsPerAcreMultiplier += $dominion->race->getPerkMultiplier('xp_gains');
        $researchPointsPerAcreMultiplier += $dominion->getImprovementPerkMultiplier('xp_gains');
        $researchPointsPerAcreMultiplier += $dominion->getBuildingPerkMultiplier('xp_gains');
        $researchPointsPerAcreMultiplier += $dominion->getSpellPerkMultiplier('xp_gains');
        $researchPointsPerAcreMultiplier += $dominion->getDeityPerkMultiplier('xp_gains');

        $isInvasionSuccessful = $this->invasionResult['result']['success'];
        if ($isInvasionSuccessful)
        {
            $landConquered = array_sum($this->invasionResult['attacker']['land_conquered']);

            $researchPointsForGeneratedAcres = 1;

            if(!$this->militaryCalculator->getRecentlyInvadedCountByAttacker($target, $dominion))
            {
                $researchPointsForGeneratedAcres = 2;
            }

            $researchPointsGained = round($landConquered * $researchPointsForGeneratedAcres * $researchPointsPerAcre * $researchPointsPerAcreMultiplier);
            $slowestTroopsReturnHours = $this->getSlowestUnitReturnHours($dominion, $units);

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                ['xp' => $researchPointsGained],
                $slowestTroopsReturnHours
            );

            $this->invasionResult['attacker']['xp'] = $researchPointsGained;
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
    * @param Dominion $dominion
    * @param Dominion $target
    * @param array $units
    */
    protected function handleDuringInvasionUnitPerks(Dominion $attacker, Dominion $target, array $units): void
    {

        # Only if invasion is successful
        if($this->invasionResult['result']['success'])
        {
            # ATTACKER
            foreach($this->invasionResult['attacker']['units_sent'] as $slot => $amount)
            {
                if ($destroysResourcePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'destroy_resource_on_victory'))
                {
                    $resourceKey = (string)$destroysResourcePerk[0];
                    $amountDestroyedPerUnit = (float)$destroysResourcePerk[1];
                    $maxDestroyedBySlot = (int)round(min($this->invasionResult['attacker']['units_sent'][$slot] * $amountDestroyedPerUnit, $this->resourceCalculator->getAmount($target, $resourceKey)));

                    if($maxDestroyedBySlot > 0)
                    {
                        if(isset($this->invasionResult['attacker']['resources_destroyed'][$resourceKey]))
                        {
                            $this->invasionResult['attacker']['resources_destroyed'][$resourceKey] += $maxDestroyedBySlot;
                        }
                        else
                        {
                            $this->invasionResult['attacker']['resources_destroyed'][$resourceKey] = $maxDestroyedBySlot;
                        }

                        $this->resourceService->updateResources($target, [$resourceKey => ($maxDestroyedBySlot * -1)]);
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

               $this->invasionResult['attacker'][$resourceKey . '_exhausted'] = $resourceAmount;

               $this->resourceService->updateResources($attacker, [$resourceKey => ($resourceAmount * -1)]);
           }

           if($exhaustingPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'offense_from_resource_capped_exhausting') and isset($units[$slot]))
           {
               $amountPerUnit = (float)$exhaustingPerk[1];
               $resourceKey = (string)$exhaustingPerk[2];

               $resourceAmountExhausted = $units[$slot] * $amountPerUnit;

               $this->invasionResult['attacker'][$resourceKey . '_exhausted'] = $resourceAmountExhausted;

               $this->resourceService->updateResources($attacker, [$resourceKey => ($resourceAmountExhausted * -1)]);
           }
        }



        # Ignore if attacker is overwhelmed.
        if(!$this->invasionResult['result']['overwhelmed'])
        {
            for ($unitSlot = 1; $unitSlot <= $attacker->race->units->count(); $unitSlot++)
            {
                // burns_peasants
                if ($attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'burns_peasants_on_attack') and isset($units[$unitSlot]))
                {
                    $burningUnits = $units[$unitSlot];
                    $peasantsBurnedPerUnit = (float)$attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'burns_peasants_on_attack');

                    # If target has less than 1000 peasants, we don't burn any.
                    if($target->peasants < 1000)
                    {
                        $burnedPeasants = 0;
                    }
                    else
                    {
                        $burnedPeasants = $burningUnits * $peasantsBurnedPerUnit * min($this->invasionResult['result']['op_dp_ratio'], 1);
                        $burnedPeasants = min(($target->peasants-1000), $burnedPeasants);
                    }

                    $target->peasants -= $burnedPeasants;
                    $this->invasionResult['attacker']['peasants_burned']['peasants'] = $burnedPeasants;
                    $this->invasionResult['defender']['peasants_burned']['peasants'] = $burnedPeasants;
                }

                // damages_improvements_on_attack
                if ($attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'damages_improvements_on_attack') and isset($units[$unitSlot]))
                {

                    $totalImprovementPoints = $this->improvementCalculator->getDominionImprovementTotalAmountInvested($target);

                    $targetImprovements = $this->improvementCalculator->getDominionImprovements($target);

                    $damagingUnits = $units[$unitSlot];
                    $damagePerUnit = $attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'damages_improvements_on_attack');

                    $damageMultiplier = 1;
                    $damageMultiplier += $target->getBuildingPerkMultiplier('lightning_bolt_damage');

                    $damage = $damagingUnits * $damagePerUnit * $damageMultiplier;
                    $damage = min($damage, $totalImprovementPoints);

                    if($damage > 0)
                    {
                        foreach($targetImprovements as $targetImprovement)
                        {
                            $improvement = Improvement::where('id', $targetImprovement->improvement_id)->first();
                            $improvementDamage[$improvement->key] = floor($damage * ($this->improvementCalculator->getDominionImprovementAmountInvested($target, $improvement) / $totalImprovementPoints));
                        }
                        $this->improvementCalculator->decreaseImprovements($target, $improvementDamage);
                    }

                    $this->invasionResult['attacker']['improvements_damage']['improvement_points'] = $damage;
                    $this->invasionResult['defender']['improvements_damage']['improvement_points'] = $damage;
                }


                if ($attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'eats_peasants_on_attack') and isset($units[$unitSlot]))
                {
                    $eatingUnits = $units[$unitSlot];
                    $peasantsEatenPerUnit = (float)$attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'eats_peasants_on_attack');

                    # If target has less than 1000 peasants, we don't eat any.
                    if($target->peasants < 1000)
                    {
                        $eatenPeasants = 0;
                    }
                    else
                    {
                        $eatenPeasants = round($eatingUnits * $peasantsEatenPerUnit * min($this->invasionResult['result']['op_dp_ratio'], 1));
                        $eatenPeasants = min(($target->peasants-1000), $eatenPeasants);
                    }

                    $target->peasants -= $eatenPeasants;
                    $this->invasionResult['attacker']['peasants_eaten']['peasants'] = $eatenPeasants;
                    $this->invasionResult['defender']['peasants_eaten']['peasants'] = $eatenPeasants;
                }

                // Troll: eats_draftees_on_attack
                if ($attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'eats_draftees_on_attack') and isset($units[$unitSlot]))
                {
                    $eatingUnits = $units[$unitSlot];
                    $drafteesEatenPerUnit = $attacker->race->getUnitPerkValueForUnitSlot($unitSlot, 'eats_draftees_on_attack');

                    $eatenDraftees = round($eatingUnits * $drafteesEatenPerUnit * min($this->invasionResult['result']['op_dp_ratio'], 1));
                    $eatenDraftees = min($target->military_draftees, $eatenDraftees);

                    $target->military_draftees -= $eatenDraftees;
                    $this->invasionResult['attacker']['draftees_eaten']['draftees'] = $eatenDraftees;
                    $this->invasionResult['defender']['draftees_eaten']['draftees'] = $eatenDraftees;
                }
            }
        }

    }

    # Unit Return 2.0
    protected function handleReturningUnits(Dominion $attacker, array $units, array $convertedUnits): void
    {
        # If instant return
        if(random_chance($attacker->getImprovementPerkMultiplier('chance_of_instant_return')) or $attacker->race->getPerkValue('instant_return') or $attacker->getSpellPerkValue('instant_return'))
        {
            $this->invasionResult['attacker']['instantReturn'] = true;
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
            $someWinIntoUnits = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

            foreach($returningUnits as $unitKey => $values)
            {
                $unitType = str_replace('military_', '', $unitKey);
                $slot = str_replace('unit', '', $unitType);
                $amountReturning = 0;

                $returningUnitKey = $unitKey;

                if(in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
                {
                    # See if slot $slot has wins_into perk.
                    if($this->invasionResult['result']['success'])
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

                            if(isset($units[$slot]))
                            {
                                $newUnits = (int)floor($units[$slot] * $ratio);
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
                    if(isset($this->invasionResult['attacker']['units_lost'][$slot]))
                    {
                        $casualties = $this->invasionResult['attacker']['units_lost'][$slot];

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

                        if($this->invasionResult['result']['success'] and $diesIntoMultiplePerkOnVictory = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_victory'))
                        {
                            # Which unit do they die into?
                            $newUnitSlot = $diesIntoMultiplePerkOnVictory[0];
                            $newUnitAmount = (float)$diesIntoMultiplePerkOnVictory[1];
                            $newUnitKey = "military_unit{$newUnitSlot}";
                            $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($attacker, $newUnitSlot);

                            $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                        }

                        if(!$this->invasionResult['result']['success'] and $diesIntoMultiplePerkOnVictory = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_victory'))
                        {
                            # Which unit do they die into?
                            $newUnitSlot = $diesIntoMultiplePerkOnVictory[0];
                            $newUnitAmount = $diesIntoMultiplePerkOnVictory[2];
                            $newUnitKey = "military_unit{$newUnitSlot}";
                            $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($attacker, $newUnitSlot);

                            $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                        }

                        # Check for faster_return_if_paired
                        if($fasterReturnIfPairedPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_if_paired'))
                        {
                            $pairedUnitSlot = (int)$fasterReturnIfPairedPerk[0];
                            $pairedUnitKey = 'military_unit'.$pairedUnitSlot;
                            $ticksFaster = (int)$fasterReturnIfPairedPerk[1];
                            $pairedUnitKeyReturning = array_sum($returningUnits[$pairedUnitKey]);

                            # Determine new return speed
                            $fasterReturningTicks = min(max($ticks - $ticksFaster, 1), 12);

                            #dump($slot . ':' . $ticksFaster . ':' . $fasterReturningTicks);

                            # How many of $slot should return faster?
                            $unitsWithFasterReturnTime = min($pairedUnitKeyReturning, $amountReturning);
                            $unitsWithRegularReturnTime = max(0, $units[$slot] - $unitsWithFasterReturnTime);

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

            #dump($returningUnits);

            $this->invasionResult['attacker']['units_returning_raw'] = $returningUnits;

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
                $this->invasionResult['attacker']['units_returning'][$slot] = array_sum($unitKeyTicks);
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

    protected function handleResourceConversions(Dominion $attacker, Dominion $defender, float $landRatio): void
    {
        foreach($attacker->race->resources as $resourceKey)
        {
            $attackerResourceTemplate['resource_' . $resourceKey] = 0;
        }
        foreach($defender->race->resources as $resourceKey)
        {
            $defenderResourceTemplate['resource_' . $resourceKey] = 0;
        }

        $this->invasionResult['attacker']['resource_conversion'] = $attackerResourceTemplate;
        $this->invasionResult['defender']['resource_conversion'] = $defenderResourceTemplate;

        $rawOp = 0;
        foreach($this->invasionResult['attacker']['units_sent'] as $slot => $amount)
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

        $rawDp = 0;
        foreach($this->invasionResult['defender']['units_defending'] as $slot => $amount)
        {
            if($amount > 0)
            {
                if($slot !== 'draftees' and $slot !== 'peasants')
                {
                    $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();
                }

                $rawDpFromSlot = $this->militaryCalculator->getUnitPowerWithPerks($defender, $attacker, $landRatio, $unit, 'defense');
                $totalRawDpFromSlot = $rawDpFromSlot * $amount;

                $rawDp += $totalRawDpFromSlot;

                # slot:amount_defending:raw_dp_per_unit_of_this_slot:raw_dp_from_slot
                #dump($slot . ':' . $amount . ':' . $rawDpFromSlot . ':' . $totalRawDpFromSlot);
            }

        }

        # Remove non-living units from $dpFromLostDefendingUnits and $opFromLostAttackingUnits
        $livingDefendingUnits = $this->invasionResult['defender']['units_lost'];
        $livingAttackingUnits = $this->invasionResult['attacker']['units_lost'];

        foreach($livingDefendingUnits as $slot => $amount)
        {
            if($slot !== 'draftees' and !$this->unitHelper->unitSlotHasAttributes($defender->race, $slot, ['living']))
            {
                unset($livingDefendingUnits[$slot]);
            }
        }

        foreach($livingAttackingUnits as $slot => $amount)
        {
            if($slot !== 'draftees' and !$this->unitHelper->unitSlotHasAttributes($attacker->race, $slot, ['living']))
            {
                unset($livingAttackingUnits[$slot]);
            }
        }

        $dpFromLostDefendingUnits = $this->militaryCalculator->getDefensivePowerRaw($defender, $attacker, $landRatio, $livingDefendingUnits, 0, false, $this->isAmbush, true);
        $opFromLostAttackingUnits = $this->militaryCalculator->getOffensivePowerRaw($attacker, $defender, $landRatio, $livingAttackingUnits);

        $this->invasionResult['defender']['dp_from_lost_living_defending_units'] = $dpFromLostDefendingUnits;
        $this->invasionResult['attacker']['op_from_lost_living_attacking_units'] = $opFromLostAttackingUnits;

        foreach($this->invasionResult['attacker']['units_sent'] as $slot => $amount)
        {
            # Attacker: kills_into_resource_per_casualty SINGLE RESOURCE
            if($killsIntoResourcePerCasualty = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'kills_into_resource_per_casualty'))
            {
                $amountPerCasualty = $killsIntoResourcePerCasualty[0] * $this->conversionCalculator->getConversionReductionMultiplier($defender);
                $resource = 'resource_' . $killsIntoResourcePerCasualty[1];

                $opFromSlot = $this->militaryCalculator->getOffensivePowerRaw($attacker, $defender, $landRatio, [$slot => $amount]);

                foreach($this->invasionResult['defender']['units_lost'] as $slotKilled => $amountKilled)
                {
                    if($this->unitHelper->unitSlotHasAttributes($defender->race, $slotKilled, ['living']))
                    {
                          $killsAttributableToThisSlot = $amountKilled * ($opFromSlot / $rawOp);
                          $this->invasionResult['attacker']['resource_conversion'][$resource] += round($killsAttributableToThisSlot * $amountPerCasualty);

                          #dump('[Enemy slot ' . $slotKilled . ' killed: ' . number_format($amountKilled) . '] Slot ' . $slot . ' OP: ' . number_format($opFromSlot) . '/' . number_format($rawOp) . ' = ' . ($opFromSlot / $rawOp) . '. Kills attributable: ' . number_format($killsAttributableToThisSlot) . '.');
                    }
                }
            }

            # Attacker: kills_into_resource_per_casualty_on_success SINGLE RESOURCE, ON SUCCESS
            if($killsIntoResourcePerCasualty = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'kills_into_resource_per_casualty_on_success') and $this->invasionResult['result']['success'])
            {
                $amountPerCasualty = $killsIntoResourcePerCasualty[0] * $this->conversionCalculator->getConversionReductionMultiplier($defender);
                $resource = 'resource_' . $killsIntoResourcePerCasualty[1];

                $opFromSlot = $this->militaryCalculator->getOffensivePowerRaw($attacker, $defender, $landRatio, [$slot => $amount]);

                foreach($this->invasionResult['defender']['units_lost'] as $slotKilled => $amountKilled)
                {
                    if($this->unitHelper->unitSlotHasAttributes($defender->race, $slotKilled, ['living']))
                    {
                          $killsAttributableToThisSlot = $amountKilled * ($opFromSlot / $rawOp);
                          #$this->queueService->queueResources('invasion',$attacker,[$resource => round($killsAttributableToThisSlot * $amountPerCasualty)]);
                          $this->invasionResult['attacker']['resource_conversion'][$resource] += round($killsAttributableToThisSlot * $amountPerCasualty);
                    }
                }
            }

            # Attacker: kills_into_resources_per_casualty MULTIPLE RESOURCES
            if($killsIntoResourcesPerCasualty = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'kills_into_resources_per_casualty'))
            {
                foreach($killsIntoResourcesPerCasualty as $killsIntoResourcesPerCasualtyPerk)
                {
                    $amountPerCasualty = $killsIntoResourcesPerCasualtyPerk[0] * $this->conversionCalculator->getConversionReductionMultiplier($defender);
                    $resource = 'resource_' . $killsIntoResourcesPerCasualtyPerk[1];

                    $opFromSlot = $this->militaryCalculator->getOffensivePowerRaw($attacker, $defender, $landRatio, [$slot => $amount]);

                    foreach($this->invasionResult['defender']['units_lost'] as $slotKilled => $amountKilled)
                    {
                        if($this->unitHelper->unitSlotHasAttributes($defender->race, $slotKilled, ['living']))
                        {
                              $killsAttributableToThisSlot = $amountKilled * ($opFromSlot / $rawOp);
                              #$this->queueService->queueResources('invasion',$attacker,[$resource => round($killsAttributableToThisSlot * $amountPerCasualty)]);
                              $this->invasionResult['attacker']['resource_conversion'][$resource] += round($killsAttributableToThisSlot * $amountPerCasualty);
                        }
                    }
                }
            }

            # Attacker: kills_into_resource_per_value SINGLE RESOURCE
            if($killsIntoResourcePerCasualty = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'kills_into_resource_per_value'))
            {
                $amountPerPoint = $killsIntoResourcePerCasualty[0] * $this->conversionCalculator->getConversionReductionMultiplier($defender);
                $resource = 'resource_' . $killsIntoResourcePerCasualty[1];

                $opFromSlot = $this->militaryCalculator->getOffensivePowerRaw($attacker, $defender, $landRatio, [$slot => $amount]);

                foreach($this->invasionResult['defender']['units_lost'] as $slotKilled => $amountKilled)
                {
                    if($this->unitHelper->unitSlotHasAttributes($defender->race, $slotKilled, ['living']))
                    {
                          $killsAttributableToThisSlot = $dpFromLostDefendingUnits * ($opFromSlot / $rawOp);
                          $this->invasionResult['attacker']['resource_conversion'][$resource] += round($killsAttributableToThisSlot * $amountPerPoint);
                    }
                }

            }

            # Attacker: kills_into_resources_per_value MULTIPLE RESOURCES
            if($killsIntoResourcesPerCasualty = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'kills_into_resources_per_value'))
            {
                foreach($killsIntoResourcesPerCasualty as $killsIntoResourcesPerCasualtyPerk)
                {
                    $amountPerPoint = $killsIntoResourcesPerCasualtyPerk[0] * $this->conversionCalculator->getConversionReductionMultiplier($defender);
                    $resource = 'resource_' . $killsIntoResourcesPerCasualtyPerk[1];

                    $opFromSlot = $this->militaryCalculator->getOffensivePowerRaw($attacker, $defender, $landRatio, [$slot => $amount]);

                    foreach($this->invasionResult['defender']['units_lost'] as $slotKilled => $amountKilled)
                    {
                        if($this->unitHelper->unitSlotHasAttributes($defender->race, $slotKilled, ['living']))
                        {
                              $dpKilledFromSlot = $this->militaryCalculator->getDefensivePowerRaw($defender, $attacker, $landRatio, [$slotKilled => $amountKilled], 0, false, $this->isAmbush, true, [$this->invasionResult['attacker']['units_sent']], true);
                              #$dpKilledByThisSlot =  * ($opFromSlot / $rawOp);
                              $killsAttributableToThisSlot = $dpKilledFromSlot * ($opFromSlot / $rawOp);
                              $resourceAmount = round($killsAttributableToThisSlot * $amountPerPoint);
                              $this->invasionResult['attacker']['resource_conversion'][$resource] += $resourceAmount;

                              #dump('[Enemy slot ' . $slotKilled . ' killed: ' . number_format($amountKilled) . ', DP: ' . number_format($dpKilledFromSlot) .'] Slot ' . $slot . ' OP: ' . number_format($opFromSlot) . '/' . number_format($rawOp) . ' = ' . ($opFromSlot / $rawOp) . '. DP killed attributable: ' . number_format($killsAttributableToThisSlot) . ', yielding ' . number_format($resourceAmount) . ' ' . $resource . '.');
                        }
                    }
                }
            }

            # Attacker: dies_into_resource
            if($diesIntoResourcePerCasualty = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_resource'))
            {
                $amountPerCasualty = $diesIntoResourcePerCasualty[0];
                $resource = 'resource_' . $diesIntoResourcePerCasualty[1];
                $this->invasionResult['attacker']['resource_conversion'][$resource] += floor($this->invasionResult['attacker']['units_lost'][$slot] * $amountPerCasualty);
            }

            # Attacker: dies_into_resource_on_success: triggers if an offensive unit has the perk and invasion is successful
            if($diesIntoResourcePerCasualty = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_resource_on_success') and $this->invasionResult['result']['success'])
            {
                $amountPerCasualty = $diesIntoResourcePerCasualty[0];
                $resource = 'resource_' . $diesIntoResourcePerCasualty[1];
                $this->invasionResult['attacker']['resource_conversion'][$resource] += floor($this->invasionResult['attacker']['units_lost'][$slot] * $amountPerCasualty);
            }

        }

        #
        foreach($this->invasionResult['defender']['units_defending'] as $slot => $amount)
        {
            if($slot !== 'draftees' and $slot !== 'peasants')
            {
                 # Defender: kills_into_resource_per_casualty SINGLE RESOURCE
                if($killsIntoResourcesPerCasualty = $defender->race->getUnitPerkValueForUnitSlot($slot, 'kills_into_resource_per_casualty'))
                {
                    $amountPerCasualty = $killsIntoResourcesPerCasualty[0] * $this->conversionCalculator->getConversionReductionMultiplier($attacker);
                    $resource = 'resource_' . $killsIntoResourcesPerCasualty[1];

                    $dpFromSlot = $this->militaryCalculator->getDefensivePowerRaw($defender, $attacker, $landRatio, [$slot => $amount]);

                    foreach($this->invasionResult['attacker']['units_lost'] as $slotKilled => $amountKilled)
                    {
                        if($this->unitHelper->unitSlotHasAttributes($attacker->race, $slotKilled, ['living']))
                        {
                              $killsAttributableToThisSlot = $amountKilled * ($dpFromSlot / $rawDp);
                              #$this->queueService->queueResources('invasion', $defender, [$resource => round($killsAttributableToThisSlot * $amountPerCasualty)]);
                              $this->invasionResult['defender']['resource_conversion'][$resource] += round($killsAttributableToThisSlot * $amountPerCasualty);
                        }
                    }
                }

                # Defender: kills_into_resource_per_casualty_on_success SINGLE RESOURCE
                if($killsIntoResourcesPerCasualty = $defender->race->getUnitPerkValueForUnitSlot($slot, 'kills_into_resource_per_casualty_on_success') and !$this->invasionResult['result']['success'])
                {
                  $amountPerCasualty = $killsIntoResourcesPerCasualty[0] * $this->conversionCalculator->getConversionReductionMultiplier($attacker);
                  $resource = 'resource_' . $killsIntoResourcesPerCasualty[1];

                  $dpFromSlot = $this->militaryCalculator->getDefensivePowerRaw($defender, $attacker, $landRatio, [$slot => $amount]);

                  foreach($this->invasionResult['attacker']['units_lost'] as $slotKilled => $amountKilled)
                  {
                      if($this->unitHelper->unitSlotHasAttributes($attacker->race, $slotKilled, ['living']))
                      {
                            $killsAttributableToThisSlot = $amountKilled * ($dpFromSlot / $rawDp);
                            #$this->queueService->queueResources('invasion', $defender, [$resource => round($killsAttributableToThisSlot * $amountPerCasualty)]);
                            $this->invasionResult['defender']['resource_conversion'][$resource] += round($killsAttributableToThisSlot * $amountPerCasualty);
                      }
                  }
                }

                # Defender: kills_into_resources_per_casualty MULTIPLE RESOURCES
                if($killsIntoResourcesPerCasualty = $defender->race->getUnitPerkValueForUnitSlot($slot, 'kills_into_resources_per_casualty'))
                {
                  foreach($killsIntoResourcesPerCasualty as $killsIntoResourcesPerCasualtyPerk)
                  {
                      $amountPerCasualty = $killsIntoResourcesPerCasualtyPerk[0] * $this->conversionCalculator->getConversionReductionMultiplier($attacker);
                      $resource = 'resource_' . $killsIntoResourcesPerCasualtyPerk[1];

                      $dpFromSlot = $this->militaryCalculator->getDefensivePowerRaw($defender, $attacker, $landRatio, [$slot => $amount]);

                      foreach($this->invasionResult['attacker']['units_lost'] as $slotKilled => $amountKilled)
                      {
                          if($this->unitHelper->unitSlotHasAttributes($attacker->race, $slotKilled, ['living']))
                          {
                                $killsAttributableToThisSlot = $amountKilled * ($dpFromSlot / $rawDp);
                                $this->invasionResult['defender']['resource_conversion'][$resource] += round($killsAttributableToThisSlot * $amountPerCasualty);

                                #echo "<pre>Slot $slot accounts for " . $dpFromSlot / $rawDp . " of $opFromLostAttackingUnits OP killed, meaning they killed $killsAttributableToThisSlot raw OP</pre>";
                          }
                      }
                  }
                }

                # Defender: kills_into_resource_per_value SINGLE RESOURCE
                if($killsIntoResourcePerCasualty = $defender->race->getUnitPerkValueForUnitSlot($slot, 'kills_into_resource_per_value'))
                {
                  $amountPerPoint = $killsIntoResourcePerCasualty[0] * $this->conversionCalculator->getConversionReductionMultiplier($attacker);
                  $resource = 'resource_' . $killsIntoResourcePerCasualty[1];

                  $dpFromSlot = $this->militaryCalculator->getDefensivePowerRaw($defender, $attacker, $landRatio, [$slot => $amount]);

                  foreach($this->invasionResult['attacker']['units_lost'] as $slotKilled => $amountKilled)
                  {
                      if($this->unitHelper->unitSlotHasAttributes($attacker->race, $slotKilled, ['living']))
                      {
                            $killsAttributableToThisSlot = $opFromLostAttackingUnits * ($dpFromSlot / $rawDp);
                            $this->invasionResult['defender']['resource_conversion'][$resource] += round($killsAttributableToThisSlot * $amountPerPoint);

                            #dump($killsAttributableToThisSlot, $opFromLostAttackingUnits, $dpFromSlot, $rawDp, ($dpFromSlot / $rawDp));
                      }
                  }
                }

                # Defender: kills_into_resources_per_value MULTIPLE RESOURCES
                if($killsIntoResourcesPerCasualty = $defender->race->getUnitPerkValueForUnitSlot($slot, 'kills_into_resources_per_value'))
                {
                    foreach($killsIntoResourcesPerCasualty as $killsIntoResourcesPerValuePerk)
                    {
                        $amountPerPoint = $killsIntoResourcesPerValuePerk[0] * $this->conversionCalculator->getConversionReductionMultiplier($attacker);
                        $resource = 'resource_' . $killsIntoResourcesPerValuePerk[1];

                        $dpFromSlot = $this->militaryCalculator->getDefensivePowerRaw($defender, $attacker, $landRatio, [$slot => $amount]);

                        foreach($this->invasionResult['attacker']['units_lost'] as $slotKilled => $amountKilled)
                        {
                            if($this->unitHelper->unitSlotHasAttributes($attacker->race, $slotKilled, ['living']))
                            {
                                  $killsAttributableToThisSlot = $opFromLostAttackingUnits * ($dpFromSlot / $rawDp);
                                  $this->invasionResult['defender']['resource_conversion'][$resource] += round($killsAttributableToThisSlot * $amountPerPoint);
                            }
                        }
                    }
                }

                # Defender: dies_into_resource
                if(isset($this->invasionResult['defender']['units_lost'][$slot]) and $diesIntoResourcePerCasualty = $defender->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_resource'))
                {
                    $amountPerCasualty = $diesIntoResourcePerCasualty[0];
                    $resource = 'resource_' . $diesIntoResourcePerCasualty[1];
                    $this->invasionResult['defender']['resource_conversion'][$resource] += floor($this->invasionResult['defender']['units_lost'][$slot] * $amountPerCasualty);
                }

                # Defender: dies_into_resource_on_success: triggers if a defensive unit has the perk and invasion is NOT successful
                if(isset($this->invasionResult['defender']['units_lost'][$slot]) and $diesIntoResourcePerCasualtyOnSuccess = $defender->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_resource_on_success') and !$this->invasionResult['result']['success'])
                {
                    $amountPerCasualty = $diesIntoResourcePerCasualtyOnSuccess[0];
                    $resource = 'resource_' . $diesIntoResourcePerCasualtyOnSuccess[1];
                    $this->invasionResult['defender']['resource_conversion'][$resource] += floor($this->invasionResult['defender']['units_lost'][$slot] * $amountPerCasualty);
                }
            }
        }

        # Add/Queue the sources
        foreach($this->invasionResult['attacker']['resource_conversion'] as $resourceType => $amount)
        {
            if($amount > 0)
            {
                $this->queueService->queueResources('invasion', $attacker, [$resourceType => $amount]);
            }
        }

        foreach($this->invasionResult['defender']['resource_conversion'] as $resourceType => $amount)
        {
            if($amount > 0)
            {
                $this->queueService->queueResources('invasion', $defender, [$resourceType => $amount]);
            }
        }

    }
    /**
     * Check if dominion is sending out at least *some* OP.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function hasAnyOP(Dominion $dominion, array $units): bool
    {
        return ($this->militaryCalculator->getOffensivePower($dominion, null, null, $units) !== 0.0);
    }

    /**
     * Check if all units being sent have positive OP.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function allUnitsHaveOP(Dominion $dominion, array $units, Dominion $target, float $landRatio): bool
    {
        foreach ($dominion->race->units as $unit)
        {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0))
            {
                continue;
            }

            if ($this->militaryCalculator->getUnitPowerWithPerks($dominion, $target, $landRatio, $unit, 'offense', null, $units, $this->invasionResult['defender']['units_defending']) === 0.0 and $unit->getPerkValue('sendable_with_zero_op') != 1)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if dominion has enough units at home to send out.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function hasEnoughUnitsAtHome(Dominion $dominion, array $units): bool
    {
        foreach ($dominion->race->units as $unit)
        {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0))
            {
                continue;
            }

            if ($units[$unit->slot] > $dominion->{'military_unit' . $unit->slot})
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if an invasion passes the 4:3-rule.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function passes43RatioRule(Dominion $dominion, Dominion $target, float $landRatio, array $units): bool
    {
        $unitsHome = [
            0 => $dominion->military_draftees,
        ];

        foreach($dominion->race->units as $unit)
        {
            $unitsHome[] = $dominion->{'military_unit'.$unit->slot} - (isset($units[$unit->slot]) ? $units[$unit->slot] : 0);
        }
        $attackingForceOP = $this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units);
        $newHomeForcesDP = $this->militaryCalculator->getDefensivePower($dominion, null, null, $unitsHome, 0, false, false, null, true); # The "true" at the end excludes raw DP from annexed dominions

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
     * @param Dominion $dominion
     * @param int $slot
     * @return int
     */
    protected function getUnitReturnHoursForSlot(Dominion $dominion, int $slot): int
    {
        $ticks = 12;

        $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
            return ($unit->slot === $slot);
        })->first();

        if ($unit->getPerkValue('faster_return'))
        {
            $ticks -= (int)$unit->getPerkValue('faster_return');
        }

        return $ticks;
    }

    protected function getUnitReturnTicksForSlot(Dominion $dominion, int $slot): int
    {
        $ticks = 12;

        $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
            return ($unit->slot === $slot);
        })->first();

        $ticks -= (int)$unit->getPerkValue('faster_return');
        $ticks -= (int)$dominion->getSpellPerkValue('faster_return');
        $ticks -= (int)$dominion->getAdvancementPerkValue('faster_return');
        $ticks -= (int)$dominion->realm->getArtefactPerkValue('faster_return');

        return min(max(1, $ticks), 12);
    }

    /**
     * Gets the amount of hours for the slowest unit from an array of units
     * takes to return home.
     *
     * Primarily used to bring prestige home earlier if you send only 9hr
     * attackers. (Land always takes 12 hrs)
     *
     * @param Dominion $dominion
     * @param array $units
     * @return int
     */
    protected function getSlowestUnitReturnHours(Dominion $dominion, array $units): int
    {
        $hours = 12;

        foreach ($units as $slot => $amount) {
            if ($amount === 0) {
                continue;
            }

            $hoursForUnit = $this->getUnitReturnHoursForSlot($dominion, $slot);

            if ($hoursForUnit < $hours) {
                $hours = $hoursForUnit;
            }
        }

        return $hours;
    }

    
}
