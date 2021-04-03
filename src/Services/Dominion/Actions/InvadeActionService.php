<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Building;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Unit;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;

# ODA
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Calculators\Dominion\ConversionCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Helpers\RaceHelper;

class InvadeActionService
{
    use DominionGuardsTrait;

    /**
     * @var float Base percentage of boats sunk
     */
    protected const BOATS_SUNK_BASE_PERCENTAGE = 5.0;

    /**
     * @var float Base percentage of defensive casualties
     */
    protected const CASUALTIES_DEFENSIVE_BASE_PERCENTAGE = 4.0;

    /**
     * @var float Max percentage of defensive casualties
     */
    protected const CASUALTIES_DEFENSIVE_MAX_PERCENTAGE = 6.0;

    /**
     * @var float Base percentage of offensive casualties
     */
    protected const CASUALTIES_OFFENSIVE_BASE_PERCENTAGE = 8.5;

    /**
     * @var int The minimum morale required to initiate an invasion
     */
    protected const MIN_MORALE = 50;

    /**
     * @var float Failing an invasion by this percentage (or more) results in 'being overwhelmed'
     */
    protected const OVERWHELMED_PERCENTAGE = 15.0;

    /**
     * @var int Bonus prestige when invading successfully
     */
    protected const PRESTIGE_CHANGE_ADD = 20;

    /**
     * @var float Base prestige % change for both parties when invading
     */
    protected const PRESTIGE_CHANGE_PERCENTAGE = 8.5;

    /**
     * @var float Percentage of mind controlled units that perish
     */
    protected const MINDCONTROLLED_UNITS_CASUALTIES = 10;

    /**
     * @var float Percentage of units to be stunned
     */
    protected const STUN_RATIO = 1;

    /**
     * @var float Percentage of units to be stunned
     */
    protected const MINIMUM_DPA = 10;

    /** @var array Invasion result array. todo: Should probably be refactored later to its own class */
    protected $invasionResult = [
        'result' => [],
        'attacker' => [
            'unitsLost' => [],
        ],
        'defender' => [
            'unitsLost' => [],
        ],
    ];

    // todo: refactor to use $invasionResult instead
    /** @var int The amount of land lost during the invasion */
    protected $landLost = 0;

    /** @var int The amount of units lost during the invasion */
    protected $unitsLost = 0;

    /**
     * InvadeActionService constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param CasualtiesCalculator $casualtiesCalculator
     * @param GovernmentService $governmentService
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     * @param NotificationService $notificationService
     * @param ProtectionService $protectionService
     * @param QueueService $queueService
     * @param RangeCalculator $rangeCalculator
     * @param SpellCalculator $spellCalculator
     */
    public function __construct()
    {
        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->casualtiesCalculator = app(CasualtiesCalculator::class);
        $this->conversionCalculator = app(ConversionCalculator::class);
        $this->governmentService = app(GovernmentService::class);
        $this->guardMembershipService = app(GuardMembershipService::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
        $this->rangeCalculator = app(RangeCalculator::class);
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
        $this->guardLockedDominion($dominion);
        $this->guardLockedDominion($target);

        DB::transaction(function () use ($dominion, $target, $units) {
            // Checks
            #if ($dominion->round->hasOffensiveActionsDisabled()) {
            #    throw new GameException('Invasions have been disabled for the remainder of the round.');
            #}

            if ($this->protectionService->isUnderProtection($dominion)) {
                throw new GameException('You cannot invade while under protection');
            }

            if ($this->protectionService->isUnderProtection($target)) {
                throw new GameException('You cannot invade dominions which are under protection');
            }

            if (!$this->rangeCalculator->isInRange($dominion, $target)) {
                throw new GameException('You cannot invade dominions outside of your range');
            }

            if ($dominion->round->id !== $target->round->id) {
                throw new GameException('Nice try, but you cannot invade cross-round');
            }

            if ($dominion->realm->id === $target->realm->id) {
                throw new GameException('You may not invade other dominions of the same realm.');
            }

            if ($dominion->id == $target->id)
            {
              throw new GameException('You cannot invade yourself.');
            }

            // Sanitize input
            $units = array_map('intval', array_filter($units));
            $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;

            # Populate units defending
            for ($slot = 1; $slot <= 4; $slot++)
            {
                $unit = $target->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                  if($this->militaryCalculator->getUnitPowerWithPerks($target, null, null, $unit, 'defense') !== 0.0)
                  {
                      $this->invasionResult['defender']['unitsDefending'][$slot] = $target->{'military_unit'.$slot};
                  }
            }

            if (!$this->hasAnyOP($dominion, $units)) {
                throw new GameException('You need to send at least some units.');
            }

            if (!$this->allUnitsHaveOP($dominion, $units, $target, $landRatio)) {
                throw new GameException('You cannot send units that have no offensive power.');
            }

            if (!$this->hasEnoughUnitsAtHome($dominion, $units)) {
                throw new GameException('You don\'t have enough units at home to send this many units.');
            }

            #if (!$this->hasEnoughBoats($dominion, $units)) {
            #    throw new GameException('You do not have enough boats to send this many units.');
            #}

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

                if (!$this->passesMinimumDpaCheck($dominion, $target, $landRatio, $units))
                {
                    throw new GameException('You are sending less than the lowest possible DP of the target. Minimum DPA (Defense Per Acre) is ' . static::MINIMUM_DPA . '. Double check your calculations and units sent.');
                }

            }

            foreach($units as $amount)
            {
               if($amount < 0) {
                   throw new GameException('Invasion was canceled due to bad input.');
               }
             }

            if ($dominion->race->getPerkValue('cannot_invade') == 1)
            {
                throw new GameException('Your faction is unable to invade.');
            }

            foreach($units as $amount)
            {
                if($amount < 0)
                {
                    throw new GameException('Invasion was canceled due to bad input.');
                }
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

            // Check building_limit
            foreach($units as $unitSlot => $amount)
            {
                if($buildingLimit = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot,'building_limit'))
                {
                    $buildingKeyLimitedTo = $buildingLimit[0]; # Land type
                    $unitsPerBuilding = (float)$buildingLimit[1]; # Units per building

                    # Improvement that can raise the limit
                    if(isset($buildingLimit[2]))
                    {
                        $unitsPerBuilding *= (1 + $this->improvementCalculator->getImprovementMultiplierBonus($dominion, $buildingLimit[2]));
                    }

                    $building = Building::where('key', $buildingKeyLimitedTo)->first();
                    $dominionBuildings = $this->buildingCalculator->getDominionBuildings($dominion);
                    $amountOfLimitingBuilding = $dominionBuildings->where('building_id', $building->id)->first()->owned;

                    $maxSendableOfThisUnit = $amountOfLimitingBuilding * $unitsPerBuilding;

                    if($amount > $maxSendableOfThisUnit)
                    {
                        throw new GameException('You can at most send ' . number_format($upperLimit) . ' ' . str_plural($this->unitHelper->getUnitName($unitSlot, $dominion->race), $upperLimit) . '. To send more, you must build more '. ucwords(str_plural($buildingLimit[0], 2)) .' or improve your ' . ucwords(str_plural($buildingLimit[2], 3)) . '.');
                    }
                }

                if($buildingLimit = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot,'building_limit_increasable'))
                {
                    // We have building limit for this unit.
                    $buildingLimitedTo = $buildingLimit[0]; # Land type
                    $unitsPerBuilding = (float)$buildingLimit[1]; # Units per building
                    $improvementToIncrease = $buildingLimit[2]; # Resource that can raise the limit
                    $improvementMultiplier = (float)$buildingLimit[3]; # Multiplier of the improvement

                    $unitsPerBuilding *= (1 + $this->improvementCalculator->getImprovementMultiplierBonus($dominion, $improvementToIncrease)) * $improvementMultiplier;

                    $amountOfLimitingBuilding = $this->buildingCalculator->getBuildingAmountOwned($dominion, null, $buildingLimitedTo);

                    $upperLimit = intval($amountOfLimitingBuilding * $unitsPerBuilding);

                    if($amount > $upperLimit)
                    {
                        throw new GameException('You can at most send ' . number_format($upperLimit) . ' ' . str_plural($this->unitHelper->getUnitName($unitSlot, $dominion->race), $upperLimit) . '. To send more, you must build more '. ucwords(str_plural($buildingLimit[0], 2)) .' or improve your ' . ucwords(str_plural($buildingLimit[2], 3)) . '.');
                    }
                }
            }

            // Cannot invade until round has started.
            if(!$dominion->round->hasStarted())
            {
                throw new GameException('You cannot invade until the round has started.');
            }

            // Dimensionalists: must have Portals open.
            if($dominion->race->name == 'Dimensionalists' and !$dominion->getSpellPerkValue('opens_portal'))
            {
                throw new GameException('You cannot attack unless a portal is open.');
            }

            // Qur: Statis cannot be invaded.
            if($this->spellCalculator->getPassiveSpellPerkValue($target, 'stasis'))
            {
                throw new GameException('A magical stasis surrounds the Qurrian lands, making it impossible for your units to invade.');
            }

            // Qur: Statis cannot invade.
            if($dominion->getSpellPerkValue('stasis'))
            {
                throw new GameException('You cannot invade while you are in stasis.');
            }

            $this->invasionResult['defender']['recentlyInvadedCount'] = $this->militaryCalculator->getRecentlyInvadedCount($target);
            $this->invasionResult['attacker']['unitsSent'] = $units;
            $this->invasionResult['attacker']['landSize'] = $this->landCalculator->getTotalLand($dominion);
            $this->invasionResult['defender']['landSize'] = $this->landCalculator->getTotalLand($target);

            // Handle pre-invasion
            $this->handleBeforeInvasionPerks($dominion);
            $this->handleMindControl($target, $dominion, $units);

            // Handle invasion results
            $this->checkInvasionSuccess($dominion, $target, $units);
            $this->checkOverwhelmed();

            if (!isset($this->invasionResult['result']['ignoreDraftees']))
            {
                $this->invasionResult['defender']['unitsDefending']['draftees'] = $target->military_draftees;
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

            $this->rangeCalculator->checkGuardApplications($dominion, $target);

            #$this->handleBoats($dominion, $target, $units);
            $this->handlePrestigeChanges($dominion, $target, $units, $landRatio, $countsAsVictory, $countsAsBottomfeed, $countsAsFailure, $countsAsRaze);
            $this->handleDuringInvasionUnitPerks($dominion, $target, $units);

            #$survivingUnits = $this->handleOffensiveCasualties($dominion, $target, $units, $landRatio,  $this->invasionResult['defender']['mindControlledUnits']);
            $this->invasionResult['attacker']['survivingUnits'] = $this->handleOffensiveCasualties($dominion, $target, $units, $landRatio,  $this->invasionResult['defender']['mindControlledUnits']);

            $totalDefensiveCasualties = $this->handleDefensiveCasualties($dominion, $target, $units, $landRatio);

            $this->handleMoraleChanges($dominion, $target, $landRatio);
            $this->handleLandGrabs($dominion, $target, $landRatio, $units);
            $this->handleResearchPoints($dominion, $target, $units);

            # Qur
            $this->handleZealots($dominion, $target, $this->invasionResult['attacker']['survivingUnits']);

            # Cult
            $this->handleMenticide($target, $dominion);

            # Dwarf
            $this->handleStun($dominion, $target, $units, $landRatio);

            # Conversions
            $offensiveConversions = array_fill(1, 4, 0);
            $defensiveConversions = array_fill(1, 4, 0);
            $conversions = $this->conversionCalculator->getConversions($dominion, $target, $this->invasionResult, $landRatio);

            if(array_sum($conversions['attacker']) > 0)
            {
                $offensiveConversions = $conversions['attacker'];
                $this->invasionResult['attacker']['conversions'] = $offensiveConversions;
                $dominion->stat_total_units_converted += array_sum($conversions['attacker']);
            }
            if(array_sum($conversions['defender']) > 0)
            {
                $defensiveConversions = $conversions['defender'];
                $this->invasionResult['defender']['conversions'] = $defensiveConversions;
                $target->stat_total_units_converted += array_sum($conversions['attacker']);
            }

            $this->handleReturningUnits($dominion, $this->invasionResult['attacker']['survivingUnits'], $offensiveConversions, $this->invasionResult['defender']['mindControlledUnits'], $defensiveConversions);
            $this->handleDefensiveConversions($target, $defensiveConversions);

            # Afflicted
            $this->handleInvasionSpells($dominion, $target);

            # Demon
            $this->handleSoulBloodFoodCollection($dominion, $target, $landRatio);

            # Norse
            $this->handleChampionCreation($dominion, $target, $units, $landRatio);

            # Salvage and Plunder
            $this->handleSalvagingAndPlundering($dominion, $target, $this->invasionResult['attacker']['survivingUnits']);

            # Growth
            $this->handleMetabolism($dominion, $target, $landRatio);

            # Imperial Crypt
            $this->handleCrypt($dominion, $target, $this->invasionResult['attacker']['survivingUnits'], $offensiveConversions, $defensiveConversions);

            // Stat changes
            if ($this->invasionResult['result']['success'])
            {
                $dominion->stat_total_land_conquered += (int)array_sum($this->invasionResult['attacker']['landConquered']);
                $dominion->stat_total_land_discovered += (int)array_sum($this->invasionResult['attacker']['landDiscovered']);
                $dominion->stat_attacking_success += $countsAsVictory;
                $dominion->stat_attacking_bottomfeeds += $countsAsBottomfeed;

                $target->stat_total_land_lost += (int)array_sum($this->invasionResult['attacker']['landConquered']);
                $target->stat_defending_failures += 1;
            }
            else
            {
                $dominion->stat_attacking_razes += $countsAsRaze;
                $dominion->stat_attacking_failures += $countsAsFailure;

                $target->stat_defending_success += 1;
                $target->realm->stat_defending_success += 1;
            }

            # Debug before saving:
            if(request()->getHost() === 'odarena.local')
            {
                dd($this->invasionResult);
            }

            // todo: move to GameEventService
            $this->invasionEvent = GameEvent::create([
                'round_id' => $dominion->round_id,
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'target_type' => Dominion::class,
                'target_id' => $target->id,
                'type' => 'invasion',
                'data' => $this->invasionResult,
            ]);

            // todo: move to its own method
            // Notification
            if ($this->invasionResult['result']['success']) {
                $this->notificationService->queueNotification('received_invasion', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $dominion->id,
                    'landLost' => $this->landLost,
                    'unitsLost' => $this->unitsLost,
                ]);
            } else {
                $this->notificationService->queueNotification('repelled_invasion', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $dominion->id,
                    'attackerWasOverwhelmed' => $this->invasionResult['result']['overwhelmed'],
                    'unitsLost' => $this->unitsLost,
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
                number_format(array_sum($this->invasionResult['attacker']['landConquered'])),
                number_format(array_sum($this->invasionResult['attacker']['landDiscovered']) + array_sum($this->invasionResult['attacker']['extraLandDiscovered']))
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

    /**
     * Handles prestige changes for both dominions.
     *
     * Prestige gains and losses are based on several factors. The most
     * important one is the range (aka relative land size percentage) of the
     * target compared to the attacker.
     *
     * -   X -  65 equals a very weak target, and the attacker is penalized with a prestige loss, no matter the outcome
     * -  66 -  74 equals a weak target, and incurs no prestige changes for either side, no matter the outcome
     * -  75 - 119 equals an equal target, and gives full prestige changes, depending on if the invasion is successful
     * - 120 - X   equals a strong target, and incurs no prestige changes for either side, no matter the outcome
     *
     * Due to the above, people are encouraged to hit targets in 75-119 range,
     * and are discouraged to hit anything below 66.
     *
     * Failing an attack above 66% range only results in a prestige loss if the
     * attacker is overwhelmed by the target defenses.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     */
    protected function handlePrestigeChanges(Dominion $attacker, Dominion $defender, array $units, float $landRatio, int $countsAsVictory, int $countsAsBottomfeed, int $countsAsFailure, int $countsAsRaze): void
    {

        $attackerPrestigeChange = 0;
        $defenderPrestigeChange = 0;

        # LDA mitigation
        $victoriesRatioMultiplier = 1;
        if($attacker->stat_defending_failures >= 10)
        {
            $victoriesRatioMultiplier = $attacker->stat_attacking_success / ($attacker->stat_attacking_success + $attacker->stat_defending_failures);
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

        $attackerPrestigeChange *= max(1, (1 - ($this->invasionResult['defender']['recentlyInvadedCount']/10)));
        $defenderPrestigeChange *= max(1, (1 - ($this->invasionResult['defender']['recentlyInvadedCount']/10)));

        $attackerPrestigeChangeMultiplier = 0;

        // Racial perk
        $attackerPrestigeChangeMultiplier += $attacker->race->getPerkMultiplier('prestige_gains');

        // Unit perk
        $attackerPrestigeChangeMultiplier += $this->militaryCalculator->getPrestigeGainsPerk($attacker, $units);

        // Tech
        $attackerPrestigeChangeMultiplier += $attacker->getTechPerkMultiplier('prestige_gains');

        // Building
        $attackerPrestigeChangeMultiplier += $attacker->getBuildingPerkMultiplier('prestige_gains');

        // Title
        $attackerPrestigeChangeMultiplier += $attacker->title->getPerkMultiplier('prestige_gains') * $attacker->title->getPerkBonus($attacker);

        $attackerPrestigeChange *= (1 + $attackerPrestigeChangeMultiplier);

        // 1/3 gains for hitting Barbarians.
        if($defender->race->name === 'Barbarian')
        {
            $attackerPrestigeChange /= 3;
        }

        $attackerPrestigeChange = round($attackerPrestigeChange);
        $defenderPrestigeChange = round($defenderPrestigeChange);

        # Cap prestige gain at 0 for abandoned dominions.
        if($defender->isAbandoned())
        {
            $attackerPrestigeChange = max($attackerPrestigeChange, 0);
        }

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

            $this->invasionResult['attacker']['prestigeChange'] = $attackerPrestigeChange;
        }

        if ($defenderPrestigeChange !== 0)
        {
            $defender->prestige += $defenderPrestigeChange;
            $this->invasionResult['defender']['prestigeChange'] = $defenderPrestigeChange;
        }
    }

    /**
     * Handles offensive casualties for the attacking dominion.
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
    protected function handleOffensiveCasualties(Dominion $dominion, Dominion $target, array $units, float $landRatio, array $mindControlledUnits): array
    {
        $isInvasionSuccessful = $this->invasionResult['result']['success'];
        $isOverwhelmed = $this->invasionResult['result']['overwhelmed'];
        $attackingForceOP = $this->invasionResult['attacker']['op'];
        $targetDP = $this->invasionResult['defender']['dp'];
        $offensiveCasualtiesPercentage = (static::CASUALTIES_OFFENSIVE_BASE_PERCENTAGE / 100);

        $offensiveCasualtiesPercentage *= 1 + $this->spellCalculator->getPassiveSpellPerkMultiplier($target, 'increases_casualties_on_defense');

        # Dark Elf: Enchanted Blades - increase offensive casualties by offensive WPA * 0.05.
        if ($this->spellCalculator->isSpellActive($target, 'enchanted_blades'))
        {
            $offensiveCasualtiesPercentage *= (1 + $this->militaryCalculator->getWizardRatio($dominion, 'offense') * 0.05);
        }

        $offensiveUnitsLost = [];

        if(array_sum($mindControlledUnits) > 0)
        {
            foreach($mindControlledUnits as $slot => $amount)
            {
                $units[$slot] -= $amount;
            }
        }

        if ($isInvasionSuccessful)
        {
            $totalUnitsSent = array_sum($units);

            $averageOPPerUnitSent = ($attackingForceOP / $totalUnitsSent);
            $OPNeededToBreakTarget = ($targetDP + 1);
            $unitsNeededToBreakTarget = round($OPNeededToBreakTarget / $averageOPPerUnitSent);

            $totalUnitsLeftToKill = ceil($unitsNeededToBreakTarget * $offensiveCasualtiesPercentage);

            foreach ($units as $slot => $amount)
            {
                $slotTotalAmountPercentage = ($amount / $totalUnitsSent);

                if ($slotTotalAmountPercentage === 0)
                {
                    continue;
                }

                $unitsToKill = ceil($unitsNeededToBreakTarget * $offensiveCasualtiesPercentage * $slotTotalAmountPercentage);
                $offensiveUnitsLost[$slot] = $unitsToKill;

                if ($totalUnitsLeftToKill < $unitsToKill)
                {
                    $unitsToKill = $totalUnitsLeftToKill;
                }

                $totalUnitsLeftToKill -= $unitsToKill;

                $fixedCasualtiesPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'fixed_casualties');

                if ($fixedCasualtiesPerk)
                {
                    $fixedCasualtiesRatio = $fixedCasualtiesPerk / 100;
                    $unitsActuallyKilled = (int)ceil($amount * $fixedCasualtiesRatio);
                    $offensiveUnitsLost[$slot] = $unitsActuallyKilled;
                }
            }
        }
        else
        {
            if ($isOverwhelmed)
            {
                $offensiveCasualtiesPercentage *= 2;
            }

            foreach ($units as $slot => $amount) {
                $fixedCasualtiesPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'fixed_casualties');
                if ($fixedCasualtiesPerk) {
                    $fixedCasualtiesRatio = $fixedCasualtiesPerk / 100;
                    $unitsToKill = (int)ceil($amount * $fixedCasualtiesRatio);
                    $offensiveUnitsLost[$slot] = $unitsToKill;
                    continue;
                }

                $unitsToKill = (int)ceil($amount * $offensiveCasualtiesPercentage);
                $offensiveUnitsLost[$slot] = $unitsToKill;
            }
        }

        foreach ($offensiveUnitsLost as $slot => &$amount)
        {
            // Reduce amount of units to kill by further multipliers
            $unitsToKillMultiplier = $this->casualtiesCalculator->getOffensiveCasualtiesMultiplierForUnitSlot($dominion, $target, $slot, $units, $landRatio, $isOverwhelmed, $attackingForceOP, $targetDP, $isInvasionSuccessful, $this->isAmbush);

            $this->invasionResult['attacker']['unitPerks']['offensiveCasualties'][$slot] = $unitsToKillMultiplier;

            if ($unitsToKillMultiplier !== 0)
            {
                $amount = (int)floor($amount * $unitsToKillMultiplier);
            }
            else
            {
                $amount = 0;
            }

            if ($amount > 0)
            {
                // Actually kill the units. RIP in peace, glorious warriors ;_;7
                $dominion->{"military_unit{$slot}"} -= $amount;
                $this->invasionResult['attacker']['unitsLost'][$slot] = $amount;
                $dominion->{'stat_total_unit' . $slot . '_lost'} += $amount;
                $target->{'stat_total_units_killed'} += $amount;
            }
        }
        unset($amount); // Unset var by reference from foreach loop above to prevent unintended side-effects

        $survivingUnits = $units;

        foreach ($units as $slot => $amount) {
            if (isset($offensiveUnitsLost[$slot])) {
                $survivingUnits[$slot] -= $offensiveUnitsLost[$slot];
            }
        }

        #$survivingUnits['attackerUnitsDiedInBattleSlot1'] = $attackerUnitsDiedInBattleSlot1;

        return $survivingUnits;
    }

    /**
     * Handles defensive casualties for the defending dominion.
     *
     * Defensive casualties are base 4.5% across all units that help defending.
     *
     * This scales with relative land size, and invading OP compared to
     * defending OP, up to max 6%.
     *
     * Unsuccessful invasions results in reduced defensive casualties, based on
     * the invading force's OP.
     *
     * Defensive casualties are spread out in ratio between all units that help
     * defend, including draftees. Being recently invaded reduces defensive
     * casualties: 100%, 80%, 60%, 50%, 33%, 0.25%.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @return int
     */
    protected function handleDefensiveCasualties(Dominion $dominion, Dominion $target, array $units, float $landRatio): int
    {
        if ($this->invasionResult['result']['overwhelmed'])
        {
            return 0;
        }

        $attackingForceOP = $this->invasionResult['attacker']['op'];
        $targetDP = $this->invasionResult['defender']['dp'];
        $defensiveCasualtiesPercentage = (static::CASUALTIES_DEFENSIVE_BASE_PERCENTAGE / 100) * min(1, $landRatio);

        // Reduce casualties if target has been hit recently
        $recentlyInvadedCount = $this->invasionResult['defender']['recentlyInvadedCount'];

        $defensiveCasualtiesPercentage *= max(0.1, (1 - ($this->invasionResult['defender']['recentlyInvadedCount']/10)));

        // Scale casualties further with invading OP vs target DP
        $defensiveCasualtiesPercentage *= ($attackingForceOP / $targetDP);

        // Cap max casualties
        $defensiveCasualtiesPercentage = min(
            $defensiveCasualtiesPercentage,
            (static::CASUALTIES_DEFENSIVE_MAX_PERCENTAGE / 100)
        );

        $defensiveUnitsLost = [];

        // Demon: racial spell Infernal Fury increases defensive casualties by 20%.
        $casualtiesMultiplier = 1;

        $casualtiesMultiplier *= 1 + $this->spellCalculator->getPassiveSpellPerkMultiplier($target, 'increases_casualties_on_offense');

        # Dark Elf: Enchanted Blades - increase offensive casualties by offensive WPA * 0.05.
        if ($this->spellCalculator->isSpellActive($dominion, 'enchanted_blades'))
        {
            $casualtiesMultiplier *= (1 + $this->militaryCalculator->getWizardRatio($dominion, 'offense') * 0.05);
        }

        $drafteesLost = (int)floor($target->military_draftees * $defensiveCasualtiesPercentage * ($this->casualtiesCalculator->getDefensiveCasualtiesMultiplierForUnitSlot($target, $dominion, null, $units, $landRatio, $this->isAmbush, $this->invasionResult['result']['success']) * $casualtiesMultiplier));

        // Spell
        $drafteesLost = min($target->military_draftees, $drafteesLost * (1 + $dominion->getSpellPerkMultiplier('increases_enemy_draftee_casualties')));

        if ($drafteesLost > 0)
        {
            $target->military_draftees -= $drafteesLost;
            $this->unitsLost += $drafteesLost;
            $this->invasionResult['defender']['unitsLost']['draftees'] = $drafteesLost;
        }

        if($target->getSpellPerkValue('defensive_power_from_peasants'))
        {
            $peasantsLost = (int)floor($target->military_peasants * $defensiveCasualtiesPercentage * ($this->casualtiesCalculator->getDefensiveCasualtiesMultiplierForUnitSlot($target, $dominion, null, $units, $landRatio, $this->isAmbush, $this->invasionResult['result']['success']) * $casualtiesMultiplier));

            // Spell
            $peasantsLost = min($target->military_draftees, $peasantsLost * (1 + $dominion->getSpellPerkMultiplier('increases_enemy_draftee_casualties')));

            if($peasantsLost > 0)
            {
                $target->peasants -= $peasantsLost;
                $this->unitsLost += $drafteesLost;
                $this->invasionResult['defender']['unitsLost']['peasants'] = $peasantsLost;
            }

        }

        // Non-draftees
        foreach ($target->race->units as $unit)
        {

            if ($this->militaryCalculator->getUnitPowerWithPerks($target, $dominion, $landRatio, $unit, 'defense', null, $units, $this->invasionResult['attacker']['unitsSent']) === 0.0)
            {
                continue;
            }

            $slotLostMultiplier = $this->casualtiesCalculator->getDefensiveCasualtiesMultiplierForUnitSlot($target, $dominion, $unit->slot, $units, $landRatio, $this->isAmbush, $this->invasionResult['result']['success']);
            $slotLost = (int)floor($target->{"military_unit{$unit->slot}"} * $defensiveCasualtiesPercentage * $slotLostMultiplier * $casualtiesMultiplier);
            $this->invasionResult['defender']['unitPerks']['defensiveCasualties'][$unit->slot] = $slotLostMultiplier;

            if ($slotLost > 0)
            {
                $defensiveUnitsLost[$unit->slot] = $slotLost;
                $target->{'stat_total_unit' . $unit->slot . '_lost'} += $slotLost;
                $dominion->{'stat_total_units_killed'} += $slotLost;
                $this->unitsLost += $slotLost; // todo: refactor
            }
        }

        # Look for dies_into amongst the dead defenders.
        $diesIntoNewUnits = array_fill(1,4,0);

        foreach($defensiveUnitsLost as $slot => $casualties)
        {
            if($diesIntoPerk = $target->race->getUnitPerkValueForUnitSlot($slot, 'dies_into'))
            {
                $slot = (int)$diesIntoPerk[0];

                $diesIntoNewUnits[$slot] += intval($casualties);
            }

            if($diesIntoMultiplePerk = $target->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple'))
            {
                $slot = (int)$diesIntoMultiplePerk[0];
                $amount = (int)$diesIntoMultiplePerk[1];

                $diesIntoNewUnits[$slot] += intval($casualties * $amount);
            }

            if(!$this->invasionResult['result']['success'] and $diesIntoMultiplePerkOnVictory = $target->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_victory'))
            {
                $slot = (int)$diesIntoMultiplePerkOnVictory[0];
                $amount = (int)$diesIntoMultiplePerkOnVictory[1];

                $diesIntoNewUnits[$slot] += intval($casualties * $amount);
            }
        }

        # Dies into units take 1 tick to appear
        foreach($diesIntoNewUnits as $slot => $amount)
        {
            $unitKey = 'military_unit'.$slot;
            $this->queueService->queueResources(
                'training',
                $target,
                [$unitKey => $amount],
                1
            );
        }


        foreach ($defensiveUnitsLost as $slot => $amount)
        {
            $target->{"military_unit{$slot}"} -= $amount;

            $this->invasionResult['defender']['unitsLost'][$slot] = $amount;
        }

        return $this->unitsLost;
    }

    /**
     * Handles land grabs and losses upon successful invasion.
     *
     * todo: description
     *
     * @param Dominion $dominion
     * @param Dominion $target
     */
    protected function handleLandGrabs(Dominion $dominion, Dominion $target, float $landRatio, array $units): void
    {
        // Nothing to grab if invasion isn't successful :^)
        if (!$this->invasionResult['result']['success'])
        {
            return;
        }

        $landRatio = $landRatio * 100;

        # Returns an integer.
        $landConquered = $this->militaryCalculator->getLandConquered($dominion, $target, $landRatio);
        $discoverLand = $this->militaryCalculator->checkDiscoverLand($dominion, $target, $landConquered);
        $extraLandDiscovered = $this->militaryCalculator->getExtraLandDiscovered($dominion, $target, $discoverLand, $landConquered);

        $this->invasionResult['attacker']['landConquered'] = [];
        $this->invasionResult['attacker']['landDiscovered'] = [];
        $this->invasionResult['defender']['landLost'] = [];
        $this->invasionResult['defender']['buildingsLost'] = [];
        $this->invasionResult['defender']['totalBuildingsLost'] = 0;

        $landLossRatio = ($landConquered / $this->landCalculator->getTotalLand($target));
        $landAndBuildingsLostPerLandType = $this->landCalculator->getLandLostByLandType($target, $landLossRatio);

        $landGainedPerLandType = [];

        foreach ($landAndBuildingsLostPerLandType as $landType => $landAndBuildingsLost)
        {
            $buildingsToDestroy = $landAndBuildingsLost['buildingsToDestroy'];
            $landLost = $landAndBuildingsLost['landLost'];
            $buildingsLostForLandType = $this->buildingCalculator->getBuildingsToDestroy($target, $buildingsToDestroy, $landType);

            // Remove land
            $target->{"land_$landType"} -= $landLost;
            $this->invasionResult['defender']['totalBuildingsLost'] += $landLost;

            // Destroy buildings
            foreach ($buildingsLostForLandType as $buildingType => $buildingsLost)
            {
                $this->buildingCalculator->removeBuildings($target, [$buildingType => $buildingsLost]);

                $builtBuildingsToDestroy = $buildingsLost['builtBuildingsToDestroy'];

                $resourceName = "building_{$buildingType}";

                $this->invasionResult['defender']['buildingsLost'][$buildingType] = $buildingsLost;

                $buildingsInQueueToRemove = $buildingsLost['buildingsInQueueToRemove'];

                if ($buildingsInQueueToRemove !== 0)
                {
                    $this->queueService->dequeueResource('construction', $target, $resourceName, $buildingsInQueueToRemove);
                }
            }

            if (!isset($landGainedPerLandType["land_{$landType}"]))
            {
                $landGainedPerLandType["land_{$landType}"] = 0;
            }

            $landGainedPerLandType["land_{$landType}"] += $landLost;

            $this->invasionResult['attacker']['landConquered'][$landType] = $landLost;


            $landDiscovered = 0;
            if($discoverLand)
            {
                $landDiscovered = $landLost;
                if($target->race->name === 'Barbarian')
                {
                    $landDiscovered = (int)round($landLost/3);
                }

                $this->invasionResult['attacker']['landDiscovered'][$landType] = $landDiscovered;

                $landGainedPerLandType["land_{$landType}"] += $landDiscovered;
            }

            $this->invasionResult['defender']['landLost'][$landType] = $landLost;

        }
        $this->invasionResult['attacker']['extraLandDiscovered'][$dominion->race->home_land_type] = $extraLandDiscovered;

        if(isset($landGainedPerLandType['land_'.$dominion->race->home_land_type]))
        {
            $landGainedPerLandType['land_'.$dominion->race->home_land_type] += $extraLandDiscovered;
        }
        else
        {
            $landGainedPerLandType['land_'.$dominion->race->home_land_type] = $extraLandDiscovered;
        }

        $this->landLost = $landConquered;

        $queueData = $landGainedPerLandType;

        $this->queueService->queueResources(
            'invasion',
            $dominion,
            $queueData
        );
    }

    /**
     * Handles morale changes for attacker.
     *
     * Attacker morale gets reduced by 5%, more so if they attack a target below
     * 75% range (up to 10% reduction at 40% target range).
     *
     * @param Dominion $dominion
     * @param Dominion $target
     */
    protected function handleMoraleChanges(Dominion $dominion, Dominion $target, float $landRatio): void
    {

        $landRatio = $landRatio * 100;
        # For successful invasions...
        if($this->invasionResult['result']['success'])
        {
            # Drop 10% morale for hits under 60%.
            if($landRatio < 60)
            {
                $attackerMoraleChange = -15;
            }
            # No change for hits in lower RG (60-75).
            elseif($landRatio < 75)
            {
                $attackerMoraleChange = 0;
            }
            # Increase 15% for hits 75-85%.
            elseif($landRatio < 85)
            {
                $attackerMoraleChange = 15;
            }
            # Increase 20% for hits 85-100%
            elseif($landRatio < 100)
            {
                $attackerMoraleChange = 20;
            }
            # Increase 25% for hits 100% and up.
            else
            {
                $attackerMoraleChange = 25;
            }
            # Defender gets the inverse of attacker morale change,
            # if it greater than 0.
            if($attackerMoraleChange > 0)
            {
                $defenderMoraleChange = $attackerMoraleChange*-1;
            }
            else
            {
                $defenderMoraleChange = 0;
            }

            $attackerMoraleChangeMultiplier = 0;
            $attackerMoraleChangeMultiplier += $dominion->getBuildingPerkMultiplier('morale_gains');
            $attackerMoraleChangeMultiplier += $dominion->race->getPerkMultiplier('morale_change_invasion');
            $attackerMoraleChangeMultiplier += $dominion->title->getPerkMultiplier('morale_gains') * $dominion->title->getPerkBonus($dominion);

            $attackerMoraleChange *= (1 + $attackerMoraleChangeMultiplier);


            $defenderMoraleChangeMultiplier = 0;
            $defenderMoraleChangeMultiplier += $dominion->race->getPerkMultiplier('morale_change_invasion');

            $defenderMoraleChange *= (1 + $defenderMoraleChangeMultiplier);

        }
        # For failed invasions...
        else
        {
            # If overwhelmed, attacker loses 20%, defender gets nothing.
            if($this->invasionResult['result']['overwhelmed'])
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

        # Change attacker morale.

        // Make sure it doesn't go below 0.
        if(($dominion->morale + $attackerMoraleChange) < 0)
        {
            $attackerMoraleChange = 0;
        }
        $dominion->morale += $attackerMoraleChange;

        # Change defender morale.

        // Make sure it doesn't go below 0.
        if(($target->morale + $defenderMoraleChange) < 0)
        {
            $defenderMoraleChange = 0;
        }
        $target->morale += $defenderMoraleChange;

        $this->invasionResult['attacker']['moraleChange'] = $attackerMoraleChange;
        $this->invasionResult['defender']['moraleChange'] = $defenderMoraleChange;

    }

    /**
     * Handles experience point (research point) generation for attacker.
     *
     * @param Dominion $dominion
     * @param array $units
     */
    protected function handleResearchPoints(Dominion $dominion, Dominion $target, array $units): void
    {

        $researchPointsPerAcre = 0;
        # No RP for in-realm invasions.
        if($dominion->realm->id !== $target->realm->id)
        {
            $researchPointsPerAcre = 25;
        }

        $researchPointsPerAcreMultiplier = 1;

        # Increase RP per acre
        if($dominion->race->getPerkMultiplier('research_points_per_acre'))
        {
            $researchPointsPerAcreMultiplier += $dominion->race->getPerkMultiplier('research_points_per_acre');
        }

        $researchPointsPerAcreMultiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'observatory');

        $isInvasionSuccessful = $this->invasionResult['result']['success'];
        if ($isInvasionSuccessful)
        {
            $landConquered = array_sum($this->invasionResult['attacker']['landConquered']);

            $researchPointsForGeneratedAcres = 1;

            if($this->militaryCalculator->getRecentlyInvadedCountByAttacker($target, $dominion) == 0)
            {
                $researchPointsForGeneratedAcres = 2;
            }


            $researchPointsGained = round($landConquered * $researchPointsForGeneratedAcres * $researchPointsPerAcre * $researchPointsPerAcreMultiplier);
            $slowestTroopsReturnHours = $this->getSlowestUnitReturnHours($dominion, $units);

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                ['resource_tech' => $researchPointsGained],
                $slowestTroopsReturnHours
            );

            $this->invasionResult['attacker']['researchPoints'] = $researchPointsGained;
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
    protected function handleDuringInvasionUnitPerks(Dominion $dominion, Dominion $target, array $units): void
    {
        # Snow Elf: Hailstorm Cannon exhausts all mana
        for ($slot = 1; $slot <= 4; $slot++)
        {
            if($exhaustingPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'offense_from_resource_exhausting') and isset($units[$slot]))
            {
                $resource = $exhaustingPerk[0];

                $this->invasionResult['attacker']['mana_exhausted'] = $dominion->{'resource_' . $resource};

                $dominion->{'resource_' . $resource} = 0;
            }
        }

        # Ignore if attacker is overwhelmed.
        if(!$this->invasionResult['result']['overwhelmed'])
        {
            for ($unitSlot = 1; $unitSlot <= 4; $unitSlot++)
            {
                // burns_peasants
                if ($dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'burns_peasants_on_attack') and isset($units[$unitSlot]))
                {
                  $burningUnits = $units[$unitSlot];
                  $peasantsBurnedPerUnit = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'burns_peasants_on_attack');

                  # If target has less than 1000 peasants, we don't burn any.
                  if($target->peasants < 1000)
                  {
                      $burnedPeasants = 0;
                  }
                  else
                  {
                      $burnedPeasants = $burningUnits * $peasantsBurnedPerUnit;
                      $burnedPeasants = min(($target->peasants-1000), $burnedPeasants);
                  }
                  $target->peasants -= $burnedPeasants;
                  $this->invasionResult['attacker']['peasants_burned']['peasants'] = $burnedPeasants;
                  $this->invasionResult['defender']['peasants_burned']['peasants'] = $burnedPeasants;

                }

                // Artillery: damages_improvements_on_attack
                if ($dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'damages_improvements_on_attack') and isset($units[$unitSlot]))
                {
                  $castleToBeDamaged = [];

                  $damageReductionFromMasonries = 1 - $target->getBuildingPerkMultiplier('lightning_bolt_damage');

                  $damagingUnits = $units[$unitSlot];
                  $damagePerUnit = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'damages_improvements_on_attack');
                  $damageDone = $damagingUnits * $damagePerUnit * $damageReductionFromMasonries;


                  # Calculate target's total imp points, where imp points > 0.
                  foreach ($this->improvementHelper->getImprovementTypes($target) as $type)
                  {
                    if($target->{'improvement_' . $type} > 0)
                    {
                      $castleToBeDamaged[$type] = $target->{'improvement_' . $type};
                    }
                  }
                  $castleTotal = array_sum($castleToBeDamaged);

                  # Calculate how much of damage should be applied to each type.
                  foreach ($castleToBeDamaged as $type => $points)
                  {
                    # The ratio this improvement type is of the total amount of imp points.
                    $typeDamageRatio = $points / $castleTotal;

                    # The ratio is applied to the damage done.
                    $typeDamageDone = intval($damageDone * $typeDamageRatio);

                    # Do the damage.
                    $target->{'improvement_' . $type} -= min($target->{'improvement_' . $type}, $typeDamageDone);
                  }

                  $this->invasionResult['attacker']['improvements_damage']['improvement_points'] = $damageDone;
                  $this->invasionResult['defender']['improvements_damage']['improvement_points'] = $damageDone;
                }


                if ($dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'eats_peasants_on_attack') and isset($units[$unitSlot]))
                {
                    $eatingUnits = $units[$unitSlot];
                    $peasantsEatenPerUnit = (float)$dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'eats_peasants_on_attack');

                    # If target has less than 1000 peasants, we don't eat any.
                    if($target->peasants < 1000)
                    {
                      $eatenPeasants = 0;
                    }
                    else
                    {
                        $eatenPeasants = round($eatingUnits * $peasantsEatenPerUnit);
                        $eatenPeasants = min(($target->peasants-1000), $eatenPeasants);
                    }
                    $target->peasants -= $eatenPeasants;
                    $this->invasionResult['attacker']['peasants_eaten']['peasants'] = $eatenPeasants;
                    $this->invasionResult['defender']['peasants_eaten']['peasants'] = $eatenPeasants;
                }

                // Troll: eats_draftees_on_attack
                if ($dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'eats_draftees_on_attack') and isset($units[$unitSlot]))
                {
                    $eatingUnits = $units[$unitSlot];
                    $drafteesEatenPerUnit = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'eats_draftees_on_attack');

                    $eatenDraftees = round($eatingUnits * $drafteesEatenPerUnit);
                    $eatenDraftees = min($target->military_draftees, $eatenDraftees);

                    $target->military_draftees -= $eatenDraftees;
                    $this->invasionResult['attacker']['draftees_eaten']['draftees'] = $eatenDraftees;
                    $this->invasionResult['defender']['draftees_eaten']['draftees'] = $eatenDraftees;
                }
            }
        }

    }

    protected function handleMenticide(Dominion $cult, Dominion $attacker)
    {
        if(array_sum($this->invasionResult['defender']['mindControlledUnits']) > 0 and ($this->spellCalculator->isSpellActive($cult, 'menticide')))
        {
            $this->invasionResult['defender']['isMenticide'] = true;
        }
        else
        {
            return;
        }

        $this->invasionResult['defender']['mindControlledUnitsReleased'] = array_fill(1, 4, 0);
        $newThralls = 0;

        foreach($this->invasionResult['defender']['mindControlledUnits'] as $slot => $amount)
        {
            $newThralls += $amount * (1 - (static::MINDCONTROLLED_UNITS_CASUALTIES / 100));
        }

        $this->invasionResult['defender']['menticide']['newThralls'] = $newThralls;
        $cult->military_unit1 += $newThralls;
    }

    protected function handleStun(Dominion $attacker, Dominion $defender, array $units, float $landRatio)
    {

        $opDpRatio = $this->invasionResult['attacker']['op'] / $this->invasionResult['defender']['dp'];

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

            $stunRatio = min((static::STUN_RATIO / 100) * $opDpRatio * min($stunningOpRatio, 1), 2.5);

            # Collect the stunnable units
            $stunnableUnits = array_fill(1, 4, 0);

            # Exclude certain attributes
            $unconvertibleAttributes = [
                'ammunition',
                'equipment',
                'magical',
                'massive',
                'machine',
                'ship',
              ];

            foreach($this->invasionResult['defender']['unitsDefending'] as $slot => $amount)
            {
                if($slot !== 'draftees')
                {
                    if(isset($this->invasionResult['defender']['unitsLost'][$slot]) and $this->invasionResult['defender']['unitsLost'][$slot] > 0)
                    {
                        $amount -= $this->invasionResult['defender']['unitsLost'][$slot];
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
                        $amount -= $this->invasionResult['defender']['unitsLost'][$slot];
                    }
                    $stunnableUnits['draftees'] = (int)$amount;
                }
             }

             foreach($stunnableUnits as $slot => $amount)
             {
                $amount = (int)round($amount * $stunRatio);
                $this->invasionResult['defender']['unitsStunned'][$slot] = $amount;

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


    /**
     * Handles the surviving units returning home.
     * Also handles "returning" defensive conversions.
     *
     * @param Dominion $dominion
     * @param array $units
     * @param array $convertedUnits
     */
    protected function handleReturningUnits(Dominion $dominion, array $units, array $convertedUnits, array $mindControlledUnits): void
    {
        $returningUnits = [
          'military_unit1' => 0,
          'military_unit2' => 0,
          'military_unit3' => 0,
          'military_unit4' => 0,
        ];

        #echo '<pre>';var_dump($convertedUnits);echo '</pre>';

        for ($i = 1; $i <= 4; $i++)
        {

            $unitKey = "military_unit{$i}";
            $returningUnitKey = $unitKey;
            $returningAmount = 0;

            # See if slot $i has wins_into perk.
            if($this->invasionResult['result']['success'])
            {
                if($dominion->race->getUnitPerkValueForUnitSlot($i, 'wins_into'))
                {
                    $returnsAsSlot = $dominion->race->getUnitPerkValueForUnitSlot($i, 'wins_into');
                    $returningUnitKey = 'military_unit' . $returnsAsSlot;
                }
            }

            if (array_key_exists($i, $units))
            {
                $returningAmount += $units[$i];
                $dominion->$unitKey -= $units[$i];
            }

            if (array_key_exists($i, $convertedUnits))
            {
                $returningAmount += $convertedUnits[$i];
            }

            if(isset($mindControlledUnits[$i]) and $mindControlledUnits[$i] > 0)
            {
                # Release non-menticided mind controlled units, less 10%.
                $returningAmount += $this->invasionResult['defender']['mindControlledUnitsReleased'][$i];
            }

            $returningUnits[$returningUnitKey] += $returningAmount;
        }

        # Look for dies_into amongst the dead attacking units.
        foreach($this->invasionResult['attacker']['unitsLost'] as $slot => $casualties)
        {
            $unitKey = "military_unit{$slot}";

            if($diesIntoPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into'))
            {
                # Which unit do they die into?
                $newUnitSlot = $diesIntoPerk[0];
                $newUnitKey = "military_unit{$newUnitSlot}";

                $returningUnits[$newUnitKey] += $casualties;
            }

            if($diesIntoMultiplePerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple'))
            {
                # Which unit do they die into?
                $newUnitSlot = $diesIntoMultiplePerk[0];
                $newUnitAmount = $diesIntoMultiplePerk[1];

                $newUnitKey = "military_unit{$newUnitSlot}";

                $returningUnits[$newUnitKey] += floor($casualties * $newUnitAmount);
            }

            if($this->invasionResult['result']['success'] and $diesIntoMultiplePerkOnVictory = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_victory'))
            {
                # Which unit do they die into?
                $newUnitSlot = $diesIntoMultiplePerkOnVictory[0];
                $newUnitAmount = $diesIntoMultiplePerkOnVictory[1];

                $newUnitKey = "military_unit{$newUnitSlot}";

                $returningUnits[$newUnitKey] += floor($casualties * $newUnitAmount);
            }

            if(!$this->invasionResult['result']['success'] and $diesIntoMultiplePerkOnVictory = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_victory'))
            {
                # Which unit do they die into?
                $newUnitSlot = $diesIntoMultiplePerkOnVictory[0];
                $newUnitAmount = $diesIntoMultiplePerkOnVictory[2];

                $newUnitKey = "military_unit{$newUnitSlot}";

                $returningUnits[$newUnitKey] += floor($casualties * $newUnitAmount);
            }

        }

      #echo '<pre>';print_r($returningUnits);echo '</pre>';

      foreach($returningUnits as $unitKey => $returningAmount)
      {

          if($returningAmount > 0)
          {
              $slot = (int)str_replace('military_unit','',$unitKey);

              $returnTicks = $this->getUnitReturnHoursForSlot($dominion, $slot);

              $returnTicks -= $dominion->getSpellPerkValue('faster_return');

              # Check for faster_return_if_paired
              if($dominion->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_if_paired'))
              {
                  $fasterReturnIfPairedPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_if_paired');
                  $pairedUnitSlot = (int)$fasterReturnIfPairedPerk[0];
                  $pairedUnitKey = 'military_unit'.$pairedUnitSlot;
                  $ticksFaster = (int)$fasterReturnIfPairedPerk[1];

                  # How many of $slot should return faster?
                  $unitsWithFasterReturnTime = min($returningUnits[$pairedUnitKey], $returningAmount);
                  $unitsWithRegularReturnTime = $returningAmount - $unitsWithFasterReturnTime;

                  # Queue faster units
                  $this->queueService->queueResources(
                      'invasion',
                      $dominion,
                      [$unitKey => $unitsWithFasterReturnTime],
                      max(1, $returnTicks - $ticksFaster)
                  );

                  # Queue slower units
                  $this->queueService->queueResources(
                      'invasion',
                      $dominion,
                      [$unitKey => $unitsWithRegularReturnTime],
                      $returnTicks
                  );


              }
              else
              {
                  $this->queueService->queueResources(
                      'invasion',
                      $dominion,
                      [$unitKey => $returningAmount],
                      $returnTicks
                  );
              }

              $this->invasionResult['attacker']['unitsReturning'][$slot] = $returningAmount;
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
     * Handles the surviving units returning home.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     */
     /*
    protected function handleBoats(Dominion $dominion, Dominion $target, array $units): void
    {
        $unitsTotal = 0;
        $unitsThatSinkBoats = 0;
        $unitsThatNeedsBoatsByReturnHours = [];
        // Calculate boats sent and attacker sinking perk
        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            $unitsTotal += (int)$units[$unit->slot];

            if ($unit->getPerkValue('sink_boats_offense') !== 0)
            {
                $unitsThatSinkBoats += (int)$units[$unit->slot];
            }

            if ($unit->need_boat)
            {
                $hours = $this->getUnitReturnHoursForSlot($dominion, $unit->slot);

                if (!isset($unitsThatNeedsBoatsByReturnHours[$hours])) {
                    $unitsThatNeedsBoatsByReturnHours[$hours] = 0;
                }

                $unitsThatNeedsBoatsByReturnHours[$hours] += (int)$units[$unit->slot];
            }
        }
        if (!$this->invasionResult['result']['overwhelmed'] && $unitsThatSinkBoats > 0)
        {
            $defenderBoatsProtected = $this->militaryCalculator->getBoatsProtected($target);
            $defenderBoatsSunkPercentage = (static::BOATS_SUNK_BASE_PERCENTAGE / 100) * ($unitsThatSinkBoats / $unitsTotal);

            $targetQueuedBoats = $this->queueService->getInvasionQueueTotalByResource($target, 'resource_boats');

            $targetBoatTotal = $target->resource_boats + $targetQueuedBoats;

            $defenderBoatsSunk = (int)floor(max(0, $targetBoatTotal - $defenderBoatsProtected) * $defenderBoatsSunkPercentage);

            if ($defenderBoatsSunk > $targetQueuedBoats)
            {
                $this->queueService->dequeueResource('invasion', $target, 'boats', $targetQueuedBoats);
                $target->resource_boats -= $defenderBoatsSunk - $targetQueuedBoats;
            }
            else
            {
                $this->queueService->dequeueResource('invasion', $target, 'boats', $defenderBoatsSunk);
            }
            $this->invasionResult['defender']['boatsLost'] = $defenderBoatsSunk;
        }

        $defendingUnitsTotal = 0;
        $defendingUnitsThatSinkBoats = 0;
        $attackerBoatsLost = 0;
        // Defender sinking perk
        foreach ($target->race->units as $unit)
        {
            $defendingUnitsTotal += $target->{"military_unit{$unit->slot}"};
            if ($unit->getPerkValue('sink_boats_defense') !== 0)
            {
                $defendingUnitsThatSinkBoats += $target->{"military_unit{$unit->slot}"};
            }
        }
        if ($defendingUnitsThatSinkBoats > 0)
        {
            $attackerBoatsSunkPercentage = (static::BOATS_SUNK_BASE_PERCENTAGE / 100) * ($defendingUnitsThatSinkBoats / $defendingUnitsTotal);
        }

        // Queue returning boats
        foreach ($unitsThatNeedsBoatsByReturnHours as $hours => $amountUnits)
        {
            $boatsByReturnHourGroup = (int)floor($amountUnits / $dominion->race->getBoatCapacity());

            $dominion->resource_boats -= $boatsByReturnHourGroup;

            if ($defendingUnitsThatSinkBoats > 0) {
                $attackerBoatsSunk = (int)ceil($boatsByReturnHourGroup * $attackerBoatsSunkPercentage);
                $attackerBoatsLost += $attackerBoatsSunk;
                $boatsByReturnHourGroup -= $attackerBoatsSunk;
            }

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                ['resource_boats' => $boatsByReturnHourGroup],
                $hours
            );
        }
        if ($attackerBoatsLost > 0) {
            $this->invasionResult['attacker']['boatsLost'] = $attackerBoatsSunk;
        }
    }
    */

    /**
     * Handles spells cast after invasion.
     *
     * @param Dominion $dominion
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
            if($this->invasionResult['attacker']['op'] / $this->invasionResult['defender']['dp'] >= 0.50)
            {
                $this->spellActionService->castSpell($attacker, 'pestilence', $defender, $isInvasionSpell);
            }

            # Great Fever
            if($this->invasionResult['result']['success'])
            {
                $this->spellActionService->castSpell($attacker, 'great_fever', $defender, $isInvasionSpell);
            }
        }

        if($defender->race->name == 'Afflicted')
        {
            # Unhealing Wounds
            $this->spellActionService->castSpell($defender, 'festering_wounds', $attacker, $isInvasionSpell);
        }
    }

    /**
     * Handles the collection of souls for Demons.
     *
     * @param Dominion $attacker
     * @param Dominion $defender
     */
    protected function handleSoulBloodFoodCollection(Dominion $attacker, Dominion $defender, float $landRatio): void
    {
        $souls = 0;
        $blood = 0;
        $food = 0;

        if($attacker->race->name == 'Demon' or $defender->race->name == 'Demon')
        {
            # Demon attacking non-Demon
            if($attacker->race->name == 'Demon' and $defender->race->name !== 'Demon')
            {
                $unitsKilled = $this->invasionResult['defender']['unitsLost'];
                $dpFromKilledUnits = $this->militaryCalculator->getDefensivePowerRaw($defender, $attacker, $landRatio, $unitsKilled, 0, false, $this->isAmbush, true);

                $this->invasionResult['attacker']['dpFromKilledUnits'] = $dpFromKilledUnits;

                $blood += $dpFromKilledUnits * 1/3;
                $food += $dpFromKilledUnits * 4;

                $souls += array_sum($this->invasionResult['defender']['unitsLost']);
                $souls *= (1 - $defender->race->getPerkMultiplier('reduced_conversions'));

                $this->invasionResult['attacker']['demonic_collection']['souls'] = $souls;
                $this->invasionResult['attacker']['demonic_collection']['blood'] = $blood;
                $this->invasionResult['attacker']['demonic_collection']['food'] = $food;

                $this->queueService->queueResources(
                    'invasion',
                    $attacker,
                    [
                        'resource_soul' => $souls,
                        'resource_blood' => $blood,
                        'resource_food' => $food,
                    ]
                );
            }
            # Demon defending against non-Demon
            elseif($attacker->race->name !== 'Demon' and $defender->race->name == 'Demon')
            {
                $opFromKilledUnits = $this->militaryCalculator->getOffensivePowerRaw($attacker, $defender, $landRatio, $this->invasionResult['attacker']['unitsLost'], [], [], false, true);

                foreach($this->invasionResult['attacker']['unitsLost'] as $casualties)
                {
                    $souls += $casualties;
                    $blood += $opFromKilledUnits * 1/3;
                    $food += $casualties * 2;
                }

                $this->invasionResult['defender']['opFromKilledUnits'] = $opFromKilledUnits;

                $souls *= (1 - $attacker->race->getPerkMultiplier('reduced_conversions'));

                $this->invasionResult['defender']['demonic_collection']['souls'] = $souls;
                $this->invasionResult['defender']['demonic_collection']['blood'] = $blood;
                $this->invasionResult['defender']['demonic_collection']['food'] = $food;

                $defender->resource_soul += $souls;
                $defender->resource_blood += $blood;
                $defender->resource_food += $food;
            }
        }
    }

    /**
     * Handles the creation of champions for Norse.
     *
     * @param Dominion $attacker
     * @param Dominion $defender
     */
    protected function handleChampionCreation(Dominion $attacker, Dominion $defender, array $units, float $landRatio): void
    {
        $champions = 0;

        if ($attacker->race->name == 'Norse')
        {
            if($landRatio >= 0.75 and $this->invasionResult['result']['success'] and isset($this->invasionResult['attacker']['unitsLost']['1']) and $this->invasionResult['attacker']['unitsLost']['1'] > 0)
            {
                $champions = $this->invasionResult['attacker']['unitsLost']['1'];

                $this->invasionResult['attacker']['champion']['champions'] = $champions;

                $this->queueService->queueResources(
                    'invasion',
                    $attacker,
                    [
                        'resource_champion' => $champions,
                    ]
                );
            }
        }

        if ($defender->race->name == 'Norse')
        {
            if(!$this->invasionResult['result']['success'] and isset($this->invasionResult['defender']['unitsLost']['1']) and $this->invasionResult['defender']['unitsLost']['1'] > 0)
            {
                $champions = $this->invasionResult['defender']['unitsLost']['1'];

                $this->invasionResult['defender']['champion']['champions'] = $champions;

                $defender->resource_champion += $champions;
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
    protected function handleSalvagingAndPlundering(Dominion $attacker, Dominion $defender, array $survivingUnits): void
    {

        $result['attacker']['plunder']['gold'] = 0;
        $result['attacker']['plunder']['mana'] = 0;
        $result['attacker']['plunder']['food'] = 0;
        $result['attacker']['plunder']['ore'] = 0;
        $result['attacker']['plunder']['lumber'] = 0;
        $result['attacker']['plunder']['gems'] = 0;

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
            foreach($this->invasionResult['defender']['unitsLost'] as $slot => $amountLost)
            {
                if($slot !== 'draftees' and $slot !== 'peasants')
                {
                    $unitType = 'unit'.$slot;
                    $unitOreCost = $unitCosts[$unitType]['ore'];
                    $unitLumberCost = $unitCosts[$unitType]['lumber'];
                    $unitGemCost = $unitCosts[$unitType]['gem'];

                    $result['defender']['salvage']['ore'] += $amountLost * $unitOreCost * $salvaging;
                    $result['defender']['salvage']['lumber'] += $amountLost * $unitLumberCost * $salvaging;
                    $result['defender']['salvage']['gems'] += $amountLost * $unitGemCost * $salvaging;

                    # Update statistics
                    $defender->stat_total_ore_salvaged += $result['defender']['salvage']['ore'];
                    $defender->stat_total_lumber_salvaged += $result['defender']['salvage']['lumber'];
                    $defender->stat_total_gem_salvaged += $result['defender']['salvage']['gems'];
                }
            }
        }

        # Attacker gets no salvage or plunder if attack fails.
        if(!$this->invasionResult['result']['success'])
        {
            return;
        }

        # Attacker: Salvaging
        if($salvaging = $attacker->race->getPerkMultiplier('salvaging'))
        {
            $unitCosts = $this->trainingCalculator->getTrainingCostsPerUnit($attacker);
            foreach($this->invasionResult['attacker']['unitsLost'] as $slot => $amountLost)
            {
                $unitType = 'unit'.$slot;
                $unitOreCost = $unitCosts[$unitType]['ore'];
                $unitLumberCost = $unitCosts[$unitType]['lumber'];
                $unitGemCost = $unitCosts[$unitType]['gem'];

                $result['attacker']['salvage']['ore'] += $amountLost * $unitOreCost * $salvaging;
                $result['attacker']['salvage']['lumber'] += $amountLost * $unitLumberCost * $salvaging;
                $result['attacker']['salvage']['gems'] += $amountLost * $unitGemCost * $salvaging;

                # Update statistics
                $attacker->stat_total_ore_salvaged += $result['attacker']['salvage']['ore'];
                $attacker->stat_total_lumber_salvaged += $result['attacker']['salvage']['lumber'];
                $attacker->stat_total_gem_salvaged += $result['attacker']['salvage']['gems'];
            }
        }

        # Attacker: Plundering
        foreach($survivingUnits as $slot => $amount)
        {
            if($plunderPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot,'plunders'))
            {
                foreach($plunderPerk as $plunder)
                {
                    $resourceToPlunder = $plunder[0];
                    $amountPlunderedPerUnit = $plunder[1];

                    if($resourceToPlunder === 'gem')
                    {
                        $resourceToPlunder = 'gems';
                    }

                    $amountToPlunder = intval(min($defender->{'resource_'.$resourceToPlunder}, $amount * $amountPlunderedPerUnit));
                    $result['attacker']['plunder'][$resourceToPlunder] += $amountToPlunder;
                    #echo '<pre>You plunder ' . $amountToPlunder . ' ' . $resourceToPlunder. '. The target has ' . $defender->{'resource_'.$resourceToPlunder} . ' ' . $resourceToPlunder. '</pre>';
                }
            }

            if($plunderPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot,'plunder'))
            {
                $resourceToPlunder = $plunderPerk[0];
                $amountPlunderedPerUnit = $plunderPerk[1];

                if($resourceToPlunder === 'gem')
                {
                    $resourceToPlunder = 'gems';
                }

                $amountToPlunder = intval(min($defender->{'resource_'.$resourceToPlunder}, $amount * $amountPlunderedPerUnit));
                $result['attacker']['plunder'][$resourceToPlunder] += $amountToPlunder;
                #echo '<pre>You plunder ' . $amountToPlunder . ' ' . $resourceToPlunder. '. The target has ' . $defender->{'resource_'.$resourceToPlunder} . ' ' . $resourceToPlunder. '</pre>';
            }
        }

        # Remove plundered resources from defender.
        foreach($result['attacker']['plunder'] as $resource => $amount)
        {
            $result['attacker']['plunder'][$resource] = min($amount, $defender->{'resource_'.$resource});
            $defender->{'resource_'.$resource} -= $result['attacker']['plunder'][$resource];
        }

        # Add salvaged resources to defender.
        foreach($result['defender']['salvage'] as $resource => $amount)
        {
            $defender->{'resource_'.$resource} += $amount;
        }

        # Queue plundered and salvaged resources to attacker.
        foreach($result['attacker']['plunder'] as $resource => $amount)
        {
            # If the resource is ore, lumber, or gems, also check for salvaged resources.
            if(in_array($resource, ['ore', 'lumber', 'gems']))
            {
                $amount += $result['attacker']['salvage'][$resource];
            }

            $this->queueService->queueResources(
                'invasion',
                $attacker,
                [
                    'resource_'.$resource => $amount
                ]
            );

            # Update statistics
            $attacker->{'stat_total_' . $resource . '_plundered'} += $amount;

        }

        $this->invasionResult['attacker']['salvage'] = $result['attacker']['salvage'];
        $this->invasionResult['attacker']['plunder'] = $result['attacker']['plunder'];
        $this->invasionResult['defender']['salvage'] = $result['defender']['salvage'];
    }

    # Add casualties to the Imperial Crypto.
    protected function handleCrypt(Dominion $attacker, Dominion $defender, array $offensiveConversions, array $defensiveConversions): void
    {

        if($attacker->race->alignment === 'evil' or $defender->race->alignment === 'evil')
        {

            # Units with these attributes do not go in the Crypt.
            $unkillableAttributes = [
                'ammunition',
                'equipment',
                'magical',
                'machine',
                'ship',
                'massive',
              ];

            # The battlefield:
            # Cap bodies by reduced_conversions perk, and round.
            $defensiveBodies = round(array_sum($this->invasionResult['defender']['unitsLost']) * (1 - $defender->race->getPerkMultiplier('reduced_conversions')));
            $offensiveBodies = round(array_sum($this->invasionResult['attacker']['unitsLost']) * (1 - $attacker->race->getPerkMultiplier('reduced_conversions')));

            # Loop through defensive casualties and remove units that don't qualify.
            foreach($this->invasionResult['defender']['unitsLost'] as $slot => $lost)
            {
                if($slot !== 'draftees' and $slot !== 'peasants')
                {
                    $isUnitConvertible = true;

                    $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                            return ($unit->slot == $slot);
                        })->first();

                    if($this->unitHelper->unitSlotHasAttributes($defender->race, $slot, $unkillableAttributes))
                    {
                        $isUnitConvertible = false;
                    }

                    if(!$isUnitConvertible)
                    {
                        $defensiveBodies -= $lost;
                    }
                }
            }

            # Loop through offensive casualties and remove units that don't qualify.
            foreach($this->invasionResult['attacker']['unitsLost'] as $slot => $lost)
            {
                if($slot !== 'draftees')
                {
                    $isUnitConvertible = true;

                    $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                            return ($unit->slot == $slot);
                        })->first();

                    if($this->unitHelper->unitSlotHasAttributes($attacker->race, $slot, $unkillableAttributes))
                    {
                        $isUnitConvertible = false;
                    }

                    if(!$isUnitConvertible)
                    {
                        $offensiveBodies -= $lost;
                    }
                }
            }

            # Remove defensive conversions (defender's conversions) from offensive bodies (they are spent)
            if(isset($this->invasionResult['defender']['conversion']))
            {
                $offensiveBodies -= array_sum($this->invasionResult['defender']['conversion']);
            }

            # Remove offensive conversions (attacker's conversions) from defensive bodies (they are spent)
            if(isset($this->invasionResult['attacker']['conversion']))
            {
                $defensiveBodies -= array_sum($this->invasionResult['attacker']['conversion']);
            }

            $toTheCrypt = 0;

            # If defender is empire
            if($defender->race->alignment === 'evil')
            {
                  $whoHasCrypt = 'defender';
                  # If the attack is successful
                  if($this->invasionResult['result']['success'])
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
                  if($this->invasionResult['result']['success'])
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

            $toTheCrypt = max(0, round($defensiveBodies + $offensiveBodies));

            if($whoHasCrypt == 'defender')
            {

                $this->invasionResult['defender']['crypt']['defensiveBodies'] = $defensiveBodies;
                $this->invasionResult['defender']['crypt']['offensiveBodies'] = $offensiveBodies;
                $this->invasionResult['defender']['crypt']['total'] = $toTheCrypt;

                $defender->realm->fill([
                    'crypt' => ($defender->realm->crypt + $toTheCrypt),
                ])->save();
            }
            elseif($whoHasCrypt == 'attacker')
            {

                $this->invasionResult['attacker']['crypt']['defensiveBodies'] = $defensiveBodies;
                $this->invasionResult['attacker']['crypt']['offensiveBodies'] = $offensiveBodies;
                $this->invasionResult['attacker']['crypt']['total'] = $toTheCrypt;

                $attacker->realm->fill([
                    'crypt' => ($attacker->realm->crypt + $toTheCrypt),
                ])->save();
            }

        }

    }

    /**
     * Handles eating of units by Growth when Metabolism is active.
     *
     * @param Dominion $attacker
     * @param Dominion $defender
     */
    protected function handleMetabolism(Dominion $attacker, Dominion $defender, float $landRatio): void
    {
        $food = 0;

        $uneatableUnitAttributes = [
          'ammunition',
          'equipment',
          'magical',
          'machine',
        ];

        # Eat defensive casualties
        if ($this->spellCalculator->getPassiveSpellPerkValue($attacker, 'convert_enemy_casualties_to_food'))
        {
                $dpFromEatenUnits = 0;
                $unitsEaten = array_fill(1, 4, 0);

                foreach($this->invasionResult['defender']['unitsLost'] as $slot => $amount)
                {
                    if($slot === 'draftees')
                    {
                        $unitsEaten[$slot] = $amount;
                    }
                    else
                    {
                        # Get the $unit
                        $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                                return ($unit->slot == $slot);
                            })->first();

                        # Get the unit attributes
                        $unitAttributes = $this->unitHelper->getUnitAttributes($unit);

                        if(count(array_intersect($uneatableUnitAttributes, $unitAttributes)) === 0)
                        {
                            $unitsEaten[$slot] = $amount;
                        }
                    }
                }

                $dpFromKilledUnits = $this->militaryCalculator->getDefensivePowerRaw($defender, $attacker, $landRatio, $unitsEaten, 0, false, $this->isAmbush, true);

                $food += $dpFromEatenUnits * 8;

                $this->invasionResult['attacker']['metabolism']['unitsEaten'] = $unitsEaten;
                $this->invasionResult['attacker']['metabolism']['dpFromUnitsEaten'] = $dpFromEatenUnits;
                $this->invasionResult['attacker']['metabolism']['food'] = $food;

                $this->queueService->queueResources(
                    'invasion',
                    $attacker,
                    [
                        'resource_food' => $food,
                    ]
                );
          }
          # Eat offensive casualties
          elseif ($this->spellCalculator->getPassiveSpellPerkValue($defender, 'convert_enemy_casualties_to_food'))
          {
              $unitsKilled = array_fill(1, 4, 0);

              foreach($this->invasionResult['attacker']['unitsLost'] as $slot => $amount)
              {
                  # Get the $unit
                  $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                          return ($unit->slot == $slot);
                      })->first();

                  # Get the unit attributes
                  $unitAttributes = $this->unitHelper->getUnitAttributes($unit);

                  if(count(array_intersect($uneatableUnitAttributes, $unitAttributes)) === 0)
                  {
                      $unitsEaten[$slot] = $amount;
                  }
              }

              $opFromKilledEaten = $this->militaryCalculator->getDefensivePowerRaw($defender, $attacker, $landRatio, $unitsEaten, 0, false, $this->isAmbush, true);

              $food += $opFromKilledEaten * 8;

              $this->invasionResult['attacker']['metabolism']['unitsEaten'] = $unitsEaten;
              $this->invasionResult['attacker']['metabolism']['opFromUnitsEaten'] = $opFromKilledEaten;
              $this->invasionResult['attacker']['metabolism']['food'] = $food;

              $defender->resource_food += $food;
          }
    }


    /**
     * Handles eating of units by Growth when Metabolism is active.
     *
     * @param Dominion $attacker
     * @param Dominion $defender
     */
    protected function handleZealots(Dominion $attacker, Dominion $defender, array $survivingUnits): void
    {
        $immortalDefenders = array_fill(1, 4, 0);
        $immortalDefendersDeaths = array_fill(1, 4, 0);


        $immortalAttackers = array_fill(1, 4, 0);
        $immortalAttackersDeaths = array_fill(1, 4, 0);

        $zealots = 0;
        $immortalsKilledPerZealot = 1.5;
        $soulsDestroyedPerZealot = 2;

        $unkillableAttributes = ['machine', 'ship', 'ammunition'];

        if($attacker->race->name === 'Qur' and !$this->invasionResult['result']['overwhelmed'])
        {
            # See if target has any immortal units
            foreach($this->invasionResult['defender']['unitsDefending'] as $slot => $amount)
            {
                if($slot !== 'draftees')
                {
                    $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                        return ($unit->slot == $slot);
                    })->first();

                    if($unit->power_defense !== 0 and ($defender->race->getUnitPerkValueForUnitSlot($slot, 'immortal') or $defender->race->getUnitPerkValueForUnitSlot($slot, 'true_immortal')) and !$this->unitHelper->unitSlotHasAttributes($defender->race, $slot, $unkillableAttributes))
                    {
                        $immortalDefenders[$slot] += $defender->{'military_unit'.$slot};
                    }
                }
            }

            # See if qur sent any Zealots
            foreach($this->invasionResult['attacker']['unitsSent'] as $slot => $amount)
            {
                if($attacker->race->getUnitPerkValueForUnitSlot($slot, 'kills_immortal'))
                {
                    $zealots += $amount;
                }
            }

            $immortalsKilled = min($zealots * $immortalsKilledPerZealot, array_sum($immortalDefenders) * 0.03);

            # Determine ratio of each immortal defender to kill.
            if(array_sum($immortalDefenders) > 0)
            {
                foreach($immortalDefenders as $slot => $amount)
                {
                    $immortalDefendersDeaths[$slot] = floor($immortalsKilled * ($amount / array_sum($immortalDefenders)));
                }

                foreach($immortalDefendersDeaths as $slot => $deaths)
                {
                    if($deaths > 0)
                    {
                          $deaths = intval($deaths);
                          $defender->{"military_unit{$slot}"} -= $deaths;
                          if(isset($this->invasionResult['defender']['unitsLost'][$slot]))
                          {
                              $this->invasionResult['defender']['unitsLost'][$slot] += $deaths;
                          }
                          else
                          {
                              $this->invasionResult['defender']['unitsLost'][$slot] = $deaths;
                          }

                          $defender->{'stat_total_unit' . $slot . '_lost'} += $deaths;
                          $attacker->{'stat_total_units_killed'} += $deaths;
                    }
                }
            }

            // SOULS

            # See if Qur sent any Zealots
            foreach($this->invasionResult['attacker']['unitsSent'] as $slot => $amount)
            {
                if($attacker->race->getUnitPerkValueForUnitSlot($slot, 'destroys_souls'))
                {
                    $zealots += $amount;
                }
            }

            # See if the target has souls.
            if($defender->resource_soul > 0)
            {
                $soulsDestroyed = (int)floor(min($defender->resource_soul * 0.04, ($zealots * $soulsDestroyedPerZealot)));

                $this->invasionResult['attacker']['soulsDestroyed'] = $soulsDestroyed;
                $defender->resource_soul -= $soulsDestroyed;
                $defender->{'stat_total_soul_destroyed'} += $soulsDestroyed;
            }
        }
        elseif($defender->race->name === 'Qur')
        {
              # See if attacker has any immortal units
              foreach($this->invasionResult['attacker']['unitsSent'] as $slot => $amount)
              {
                  $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                      return ($unit->slot == $slot);
                  })->first();

                  if($attacker->race->getUnitPerkValueForUnitSlot($slot, 'immortal') or $attacker->race->getUnitPerkValueForUnitSlot($slot, 'true_immortal') and !$this->unitHelper->unitSlotHasAttributes($attacker->race, $slot, $unkillableAttributes))
                  {
                      $immortalAttackers[$slot] += $defender->{'military_unit'.$slot};
                  }
              }

              # See if Qur has any Zealots
              foreach($this->invasionResult['defender']['unitsDefending'] as $slot => $amount)
              {
                  if($slot !== 'draftees' and $defender->race->getUnitPerkValueForUnitSlot($slot, 'kills_immortal'))
                  {
                      $zealots += $amount;
                  }
              }

              $immortalsKilled = min($zealots * $immortalsKilledPerZealot, array_sum($immortalAttackers) * 0.03);

              # Determine ratio of each immortal defender to kill.
              if(array_sum($immortalAttackers) > 0)
              {
                  foreach($immortalAttackers as $slot => $amount)
                  {
                      $immortalAttackersDeaths[$slot] = floor($immortalsKilled * ($amount / array_sum($immortalAttackers)));
                  }

                  foreach($immortalAttackersDeaths as $slot => $deaths)
                  {
                      if($deaths > 0)
                      {
                            $deaths = intval($deaths);
                            $defender->{"military_unit{$slot}"} -= $deaths;
                            if(isset($this->invasionResult['attacker']['unitsLost'][$slot]))
                            {
                                $this->invasionResult['attacker']['unitsLost'][$slot] += $deaths;
                            }
                            else
                            {
                                $this->invasionResult['attacker']['unitsLost'][$slot] = $deaths;
                            }

                            $this->invasionResult['attacker']['survivingUnits'][$slot] -= $deaths;

                            $attacker->{'stat_total_unit' . $slot . '_lost'} += $deaths;
                            $defender->{'stat_total_units_killed'} += $deaths;
                      }
                  }
              }

              // SOULS
              $zealots = 0;

              # See if Qur is defending with any Zealots
              foreach($this->invasionResult['defender']['unitsDefending'] as $slot => $amount)
              {
                  if($slot !== 'draftees' and $defender->race->getUnitPerkValueForUnitSlot($slot, 'destroys_souls'))
                  {
                      $zealots += $amount;
                  }
              }

              # See if the target has souls.
              if($attacker->resource_soul > 0)
              {
                  $soulsDestroyed = (int)floor(min($attacker->resource_soul * 0.08, ($zealots * $soulsDestroyedPerZealot)));

                  $this->invasionResult['defender']['soulsDestroyed'] = $soulsDestroyed;
                  $attacker->resource_soul -= $soulsDestroyed;
                  $attacker->{'stat_total_soul_destroyed'} += $soulsDestroyed;
              }
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

        $this->invasionResult['attacker']['ambush'] = $this->isAmbush;
    }

    /**
     * Check for Cultist Mind Control spell:
     *
     * @param Dominion $cult
     * @param Dominion $attacker
     * @param array $units
     * @return void
     */
    protected function handleMindControl(Dominion $cult, Dominion $attacker, array $units): void
    {
        if ($this->spellCalculator->isSpellActive($cult, 'mind_control'))
        {
            $this->invasionResult['defender']['isMindControl'] = true;
        }
        else
        {
            $this->invasionResult['defender']['isMindControl'] = false;
            $this->invasionResult['defender']['mindControlledUnits'] = [];
            return;
        }

        # How many Mystics do we have?
        $availableMystics = $cult->military_unit4;

        # Check invading forces for units that are only SENTIENT
        $mindControlledUnits = [];
        $nonControllableAttributes = [
            'ammunition',
            'equipment',
            'magical',
            'massive',
            'machine',
            'mindless',
            'ship',
            'wise',
          ];
        foreach($units as $slot => $amount)
        {
            $mindControlledUnits[$slot] = 0;

            # Get the $unit
            $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot == $slot);
                })->first();

            # Get the attributes
            $unitAttributes = $this->unitHelper->getUnitAttributes($unit);

            $isUnitControllable = true;
            if(in_array('sentient', $unitAttributes))
            {
                foreach($nonControllableAttributes as $nonControllableAttribute)
                {
                    if(in_array($nonControllableAttribute, $unitAttributes))
                    {
                        $isUnitControllable = false;
                        break;
                    }
                }
            }

            if($isUnitControllable)
            {
                $mindControlledUnits[$slot] = min($amount, $availableMystics);
            }

            $availableMystics -= $mindControlledUnits[$slot];
            $this->invasionResult['defender']['mindControlledUnits'][$slot] = $mindControlledUnits[$slot];
            $this->invasionResult['defender']['mindControlledUnitsReleased'][$slot] = $mindControlledUnits[$slot] * (1 - (static::MINDCONTROLLED_UNITS_CASUALTIES / 100));
        }

    }


    /**
     * Check whether the invasion is successful.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return void
     */
    protected function checkInvasionSuccess(Dominion $dominion, Dominion $target, array $units): void
    {
        $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;
        $attackingForceOP = $this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units, [], $this->invasionResult['defender']['mindControlledUnits']);
        $targetDP = $this->getDefensivePowerWithTemples($dominion, $target, $units, $landRatio, $this->isAmbush, $this->invasionResult['defender']['mindControlledUnits']);
        $this->invasionResult['attacker']['op'] = $attackingForceOP;
        $this->invasionResult['defender']['dp'] = $targetDP;
        $this->invasionResult['result']['success'] = ($attackingForceOP > $targetDP);
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
        $this->invasionResult['result']['overwhelmed'] = false;

        if ($this->invasionResult['result']['success'])
        {
            return;
        }

        $attackingForceOP = $this->invasionResult['attacker']['op'];
        $targetDP = $this->invasionResult['defender']['dp'];

        $this->invasionResult['result']['overwhelmed'] = ((1 - $attackingForceOP / $targetDP) >= (static::OVERWHELMED_PERCENTAGE / 100));
    }

/*
    protected function passesOpAtLeast50percentOfDpRule(): bool
    {
        if($this->invasionResult['result']['success']) {
            return true;
        }

        return $this->invasionResult['attacker']['op'] / $this->invasionResult['defender']['dp'] > 0.5;
    }
*/

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

            if ($this->militaryCalculator->getUnitPowerWithPerks($dominion, $target, $landRatio, $unit, 'offense', null, $units, $this->invasionResult['defender']['unitsDefending']) === 0.0 and $unit->getPerkValue('sendable_with_zero_op') != 1)
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
     * Check if dominion has enough boats to send units out.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
     /*
    protected function hasEnoughBoats(Dominion $dominion, array $units): bool
    {
        $unitsThatNeedBoats = 0;

        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            if ($unit->need_boat) {
                $unitsThatNeedBoats += (int)$units[$unit->slot];
            }
        }

        return ($dominion->resource_boats >= ceil($unitsThatNeedBoats / $dominion->race->getBoatCapacity()));
    }
    */

    /**
     * Check if an invasion passes the 33%-rule.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return bool
     */
    protected function passes33PercentRule(Dominion $dominion, Dominion $target, array $units): bool
    {
        #$attackingForceOP = $this->militaryCalculator->getOffensivePower($dominion, $target, null, $units);
        $attackingForceDP = $this->militaryCalculator->getDefensivePower($dominion, null, null, $units);
        $currentHomeForcesDP = $this->militaryCalculator->getDefensivePower($dominion);

        $unitsReturning = [];
        for ($slot = 1; $slot <= 4; $slot++)
        {
            $unitsReturning[$slot] = $this->queueService->getInvasionQueueTotalByResource($dominion, "military_unit{$slot}");
        }

        $returningForcesDP = $this->militaryCalculator->getDefensivePower($dominion, null, null, $unitsReturning);

        $totalDP = $currentHomeForcesDP + $returningForcesDP;

        $newHomeForcesDP = ($currentHomeForcesDP - $attackingForceDP);

        return ($newHomeForcesDP >= $totalDP * (1/3));
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
            1 => $dominion->military_unit1 - (isset($units[1]) ? $units[1] : 0),
            2 => $dominion->military_unit2 - (isset($units[2]) ? $units[2] : 0),
            3 => $dominion->military_unit3 - (isset($units[3]) ? $units[3] : 0),
            4 => $dominion->military_unit4 - (isset($units[4]) ? $units[4] : 0)
        ];
        $attackingForceOP = $this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units);
        $newHomeForcesDP = $this->militaryCalculator->getDefensivePower($dominion, null, null, $unitsHome);

        $attackingForceMaxOP = (int)ceil($newHomeForcesDP * (4/3));

        return ($attackingForceOP <= $attackingForceMaxOP);
    }

    /**
     * Check if an invasion passes the 4:3-rule.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function passesMinimumDpaCheck(Dominion $dominion, Dominion $target, float $landRatio, array $units): bool
    {
        $attackingForceOP = $this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units);

        return ($attackingForceOP > $this->landCalculator->getTotalLand($target) * static::MINIMUM_DPA);
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
        $hours = 12;

        /** @var Unit $unit */
        $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
            return ($unit->slot === $slot);
        })->first();

        if ($unit->getPerkValue('faster_return') !== 0)
        {
            $hours -= (int)$unit->getPerkValue('faster_return');
        }

        return $hours;
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

    protected function getDefensivePowerWithTemples(
      Dominion $attacker,
      Dominion $target,
      array $units,
      float $landRatio,
      bool $isAmbush,
      array $mindControlledUnits
      ): float
    {
        // Values (percentages)
        $dpReductionPerTemple = 1.8;
        $templeMaxDpReduction = 36;
        $ignoreDraftees = false;

        $dpMultiplierReduction = $attacker->getBuildingPerkMultiplier('defensive_modifier_reduction');

        // Void: Spell (remove DP reduction from Temples)
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
                                                            $ignoreDraftees,
                                                            $this->isAmbush,
                                                            false,
                                                            $units, # Becomes $invadingUnits
                                                            $mindControlledUnits
                                                          );
    }

}
