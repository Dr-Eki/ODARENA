<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Exception;
use LogicException;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\OpsHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;

class EspionageActionService
{
    use DominionGuardsTrait;

    /**
     * @var float Hostile base success rate
     */
    protected const HOSTILE_MULTIPLIER_SUCCESS_RATE = 1.2;

    /**
     * @var float Theft base success rate
     */
    protected const THEFT_MULTIPLIER_SUCCESS_RATE = 1.2;

    /**
     * @var float Info op base success rate
     */
    protected const INFO_MULTIPLIER_SUCCESS_RATE = 1.4;

    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var EspionageHelper */
    protected $espionageHelper;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var ImprovementHelper */
    protected $improvementHelper;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var LandHelper */
    protected $landHelper;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var OpsHelper */
    protected $opsHelper;

    /** @var ProductionCalculator */
    protected $productionCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var QueueService */
    protected $queueService;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /**
     * EspionageActionService constructor.
     */
    public function __construct()
    {
        $this->buildingHelper = app(BuildingHelper::class);
        $this->espionageHelper = app(EspionageHelper::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->opsHelper = app(OpsHelper::class);
        $this->productionCalculator = app(ProductionCalculator::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
    }

    # Any changes here must also be done in espionage.blade.php.
    public const BLACK_OPS_DAYS_AFTER_ROUND_START = 1;
    public const THEFT_DAYS_AFTER_ROUND_START = 1;

    /**
     * Performs a espionage operation for $dominion, aimed at $target dominion.
     *
     * @param Dominion $dominion
     * @param string $operationKey
     * @param Dominion $target
     * @return array
     * @throws GameException
     * @throws LogicException
     */
    public function performOperation(Dominion $dominion, string $operationKey, Dominion $target): array
    {
        $this->guardLockedDominion($dominion);

        $operationInfo = $this->espionageHelper->getOperationInfo($operationKey);

        if (!$operationInfo) {
            throw new LogicException("Cannot perform unknown operation '{$operationKey}'");
        }

        if ($dominion->spy_strength <= 0) {
            throw new GameException("Your spies do not have enough strength to perform {$operationInfo['name']}.");
        }

        if ($this->protectionService->isUnderProtection($dominion)) {
            throw new GameException('You cannot perform espionage operations while under protection');
        }

        if ($this->protectionService->isUnderProtection($target)) {
            throw new GameException('You cannot perform espionage operations on targets which are under protection');
        }

        if (!$this->rangeCalculator->isInRange($dominion, $target)) {
            throw new GameException('You cannot perform espionage operations on targets outside of your range');
        }

        if ($this->espionageHelper->isResourceTheftOperation($operationKey))
        {
            if (now()->diffInDays($dominion->round->start_date) < self::THEFT_DAYS_AFTER_ROUND_START)
            {
                throw new GameException('You cannot perform resource theft for the first day of the round');
            }
            #if ($this->rangeCalculator->getDominionRange($dominion, $target) < 100) {
            if (!$this->rangeCalculator->isInRange($dominion, $target))
            {
                throw new GameException('You cannot perform resource theft on targets outside of your range');
            }
        } elseif ($this->espionageHelper->isHostileOperation($operationKey)) {
            if (now()->diffInDays($dominion->round->start_date) < self::BLACK_OPS_DAYS_AFTER_ROUND_START) {
                throw new GameException('You cannot perform black ops for the first day of the round');
            }
        }

        if ($dominion->round->id !== $target->round->id) {
            throw new GameException('Nice try, but you cannot perform espionage operations cross-round');
        }

        #if($dominion->race->alignment == 'good')
        #{
        # No in-realm ops.
          if ($dominion->realm->id === $target->realm->id) {
              throw new GameException('Nice try, but you cannot perform espionage oprations on your realmies');
          }
        #}

        $result = null;

        DB::transaction(function () use ($dominion, $target, $operationKey, &$result) {
            if ($this->espionageHelper->isInfoGatheringOperation($operationKey)) {
                $spyStrengthLost = 2;
                $result = $this->performInfoGatheringOperation($dominion, $operationKey, $target);

            } elseif ($this->espionageHelper->isResourceTheftOperation($operationKey)) {
                $spyStrengthLost = 5;
                $result = $this->performResourceTheftOperation($dominion, $operationKey, $target);

            } elseif ($this->espionageHelper->isHostileOperation($operationKey)) {
                $spyStrengthLost = 5;
                $result = $this->performHostileOperation($dominion, $operationKey, $target);

            } else {
                throw new LogicException("Unknown type for espionage operation {$operationKey}");
            }

            $spyStrengthLost = min($spyStrengthLost, $dominion->spy_strength);
            $dominion->spy_strength -= $spyStrengthLost;

            # XP Gained.
            if(isset($result['damage']))
            {
              $xpGained = $this->calculateXpGain($dominion, $target, $result['damage']);
              $dominion->resource_tech += $xpGained;
            }

            $dominion->stat_espionage_success += 1;

            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION,
                'action' => $operationKey
            ]);
        });

        $this->rangeCalculator->checkGuardApplications($dominion, $target);

        return [
                'message' => $result['message'],
                'data' => [
                    'operation' => $operationKey,
                ],
                'redirect' =>
                    $this->espionageHelper->isInfoGatheringOperation($operationKey) && $result['success']
                        ? route('dominion.op-center.show', $target->id)
                        : null,
            ] + $result;
    }

    /**
     * @param Dominion $dominion
     * @param string $operationKey
     * @param Dominion $target
     * @return array
     * @throws Exception
     */
    protected function performInfoGatheringOperation(Dominion $dominion, string $operationKey, Dominion $target): array
    {
        $operationInfo = $this->espionageHelper->getOperationInfo($operationKey);

        $selfSpa = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');

        // You need at least some positive SPA to perform espionage operations
        if ($selfSpa === 0.0) {
            // Don't reduce spy strength by throwing an exception here
            throw new GameException("Your spy force is too weak to cast {$operationInfo['name']}. Please train some more spies.");
        }

        if ($targetSpa !== 0.0) {
            $successRate = $this->opsHelper->operationSuccessChance(
                $selfSpa,
                $targetSpa,
                static::INFO_MULTIPLIER_SUCCESS_RATE
            );

            if (!random_chance($successRate)) {
                // Values (percentage)
                $spiesKilledBasePercentage = 0.25; // TODO: Higher for black ops.

                $spiesKilledMultiplier = $this->getSpyLossesReductionMultiplier($dominion);

                $spyLossSpaRatio = ($targetSpa / $selfSpa);
                $spiesKilledPercentage = clamp($spiesKilledBasePercentage * $spyLossSpaRatio, 0.25, 1);


                $unitsKilled = [];
                $spiesKilled = (int)floor(($dominion->military_spies * ($spiesKilledPercentage / 100)) * $spiesKilledMultiplier);

                # Swarm: immortal spies
                if($dominion->race->getPerkValue('immortal_spies'))
                {
                  $spiesKilled = 0;
                }

                if ($spiesKilled > 0) {
                    $unitsKilled['spies'] = $spiesKilled;
                    $dominion->military_spies -= $spiesKilled;
                }

                foreach ($dominion->race->units as $unit) {
                    if ($unit->getPerkValue('counts_as_spy_offense')) {
                        $unitKilledMultiplier = ((float)$unit->getPerkValue('counts_as_spy_offense') / 2) * ($spiesKilledPercentage / 100) * $spiesKilledMultiplier;
                        $unitKilled = (int)floor($dominion->{"military_unit{$unit->slot}"} * $unitKilledMultiplier);
                        if ($unitKilled > 0) {
                            $unitsKilled[strtolower($unit->name)] = $unitKilled;
                            $dominion->{"military_unit{$unit->slot}"} -= $unitKilled;
                        }
                    }
                }

                $unitsKilledStringParts = [];
                foreach ($unitsKilled as $name => $amount) {
                    $amountLabel = number_format($amount);
                    $unitLabel = str_plural(str_singular($name), $amount);
                    $unitsKilledStringParts[] = "{$amountLabel} {$unitLabel}";
                }
                $unitsKilledString = generate_sentence_from_array($unitsKilledStringParts);

                $this->notificationService
                    ->queueNotification('repelled_spy_op', [
                        'sourceDominionId' => $dominion->id,
                        'operationKey' => $operationKey,
                        'unitsKilled' => $unitsKilledString,
                    ])
                    ->sendNotifications($target, 'irregular_dominion');

                if ($unitsKilledString) {
                    $message = "The enemy has prevented our {$operationInfo['name']} attempt and managed to capture $unitsKilledString.";
                } else {
                    $message = "The enemy has prevented our {$operationInfo['name']} attempt.";
                }

                return [
                    'success' => false,
                    'message' => $message,
                    'alert-type' => 'warning',
                ];
            }
        }

        $infoOp = new InfoOp([
            'source_realm_id' => $dominion->realm->id,
            'target_dominion_id' => $target->id,
            'type' => $operationKey,
            'source_dominion_id' => $dominion->id,
        ]);

        switch ($operationKey) {
            case 'barracks_spy':
                $data = [
                    'units' => [
                        'home' => [],
                        'returning' => [],
                        'training' => [],
                    ],
                ];

                // Units at home (85% accurate)
                array_set($data, 'units.home.draftees', random_int(
                    round($target->military_draftees * 0.85),
                    round($target->military_draftees / 0.85)
                ));

                foreach (range(1, 4) as $slot) {
                    $amountAtHome = $target->{'military_unit' . $slot};

                    if ($amountAtHome !== 0) {
                        $amountAtHome = random_int(
                            round($amountAtHome * 0.85),
                            round($amountAtHome / 0.85)
                        );
                    }

                    array_set($data, "units.home.unit{$slot}", $amountAtHome);
                }

                // Units returning (85% accurate)
                $this->queueService->getInvasionQueue($target)->each(static function ($row) use (&$data) {
                    if (!starts_with($row->resource, 'military_')) {
                        return; // continue
                    }

                    $unitType = str_replace('military_', '', $row->resource);

                    $amount = random_int(
                        round($row->amount * 0.85),
                        round($row->amount / 0.85)
                    );

                    array_set($data, "units.returning.{$unitType}.{$row->hours}", $amount);
                });

                // Units in training (100% accurate)
                $this->queueService->getTrainingQueue($target)->each(static function ($row) use (&$data) {
                    $unitType = str_replace('military_', '', $row->resource);

                    array_set($data, "units.training.{$unitType}.{$row->hours}", $row->amount);
                });

                $infoOp->data = $data;
                break;

            case 'castle_spy':
                $data = [];

                foreach ($this->improvementHelper->getImprovementTypes($target->race->name) as $type) {
                    array_set($data, "{$type}.points", $target->{'improvement_' . $type});
                    array_set($data, "{$type}.rating",
                        $this->improvementCalculator->getImprovementMultiplierBonus($target, $type));
                }

                $infoOp->data = $data;
                break;

            case 'survey_dominion':
                $data = [];

                foreach ($this->buildingHelper->getBuildingTypes($target) as $buildingType) {
                    array_set($data, "constructed.{$buildingType}", $target->{'building_' . $buildingType});
                }

                $this->queueService->getConstructionQueue($target)->each(static function ($row) use (&$data) {
                    $buildingType = str_replace('building_', '', $row->resource);

                    array_set($data, "constructing.{$buildingType}.{$row->hours}", $row->amount);
                });

                array_set($data, 'barren_land', $this->landCalculator->getTotalBarrenLand($target));
                array_set($data, 'total_land', $this->landCalculator->getTotalLand($target));

                $infoOp->data = $data;
                break;

            case 'land_spy':
                $data = [];

                foreach ($this->landHelper->getLandTypes() as $landType) {
                    $amount = $target->{'land_' . $landType};

                    array_set($data, "explored.{$landType}.amount", $amount);
                    array_set($data, "explored.{$landType}.percentage",
                        (($amount / $this->landCalculator->getTotalLand($target)) * 100));
                    array_set($data, "explored.{$landType}.barren",
                        $this->landCalculator->getTotalBarrenLandByLandType($target, $landType));
                }

                $this->queueService->getExplorationQueue($target)->each(static function ($row) use (&$data) {
                    $landType = str_replace('land_', '', $row->resource);

                    array_set(
                        $data,
                        "incoming.{$landType}.{$row->hours}",
                        (array_get($data, "incoming.{$landType}.{$row->hours}", 0) + $row->amount)
                    );
                });

                $this->queueService->getInvasionQueue($target)->each(static function ($row) use (&$data) {
                    if (!starts_with($row->resource, 'land_')) {
                        return; // continue
                    }

                    $landType = str_replace('land_', '', $row->resource);

                    array_set(
                        $data,
                        "incoming.{$landType}.{$row->hours}",
                        (array_get($data, "incoming.{$landType}.{$row->hours}", 0) + $row->amount)
                    );
                });

                $infoOp->data = $data;
                break;

            default:
                throw new LogicException("Unknown info gathering operation {$operationKey}");
        }

        $infoOp->save();

        return [
            'success' => true,
            'message' => 'Your spies infiltrate the target\'s dominion successfully and return with a wealth of information.',
            'redirect' => route('dominion.op-center.show', $target),
        ];
    }

    /**
     * @param Dominion $dominion
     * @param string $operationKey
     * @param Dominion $target
     * @return array
     * @throws Exception
     */
    protected function performResourceTheftOperation(Dominion $dominion, string $operationKey, Dominion $target): array
    {
        if ($dominion->round->hasOffensiveActionsDisabled())
        {
            throw new GameException('Theft has been disabled for the remainder of the round.');
        }

        $operationInfo = $this->espionageHelper->getOperationInfo($operationKey);

        $selfSpa = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');

        // You need at least some positive SPA to perform espionage operations
        if ($selfSpa === 0.0) {
            // Don't reduce spy strength by throwing an exception here
            throw new GameException("Your spy force is too weak to cast {$operationInfo['name']}. Please train some more spies.");
        }

        if ($targetSpa !== 0.0) {
            $successRate = $this->opsHelper->operationSuccessChance(
                $selfSpa,
                $targetSpa,
                static::THEFT_MULTIPLIER_SUCCESS_RATE
            );

            if (!random_chance($successRate)) {
                // Values (percentage)
                $spiesKilledBasePercentage = 1;

                $spiesKilledMultiplier = $this->getSpyLossesReductionMultiplier($dominion);

                $spyLossSpaRatio = ($targetSpa / $selfSpa);
                $spiesKilledPercentage = clamp($spiesKilledBasePercentage * $spyLossSpaRatio, 0.5, 1.5);

                $unitsKilled = [];
                $spiesKilled = (int)floor(($dominion->military_spies * ($spiesKilledPercentage / 100)) * $spiesKilledMultiplier);

                # Swarm: immortal spies
                if($dominion->race->getPerkValue('immortal_spies'))
                {
                  $spiesKilled = 0;
                }

                if ($spiesKilled > 0) {
                    $unitsKilled['spies'] = $spiesKilled;
                    $dominion->military_spies -= $spiesKilled;
                }

                foreach ($dominion->race->units as $unit) {
                    if ($unit->getPerkValue('counts_as_spy_offense')) {
                        $unitKilledMultiplier = ((float)$unit->getPerkValue('counts_as_spy_offense') / 2) * ($spiesKilledPercentage / 100) * $spiesKilledMultiplier;
                        $unitKilled = (int)floor($dominion->{"military_unit{$unit->slot}"} * $unitKilledMultiplier);
                        if ($unitKilled > 0) {
                            $unitsKilled[strtolower($unit->name)] = $unitKilled;
                            $dominion->{"military_unit{$unit->slot}"} -= $unitKilled;
                        }
                    }
                }

                $unitsKilledStringParts = [];
                foreach ($unitsKilled as $name => $amount) {
                    $amountLabel = number_format($amount);
                    $unitLabel = str_plural(str_singular($name), $amount);
                    $unitsKilledStringParts[] = "{$amountLabel} {$unitLabel}";
                }
                $unitsKilledString = generate_sentence_from_array($unitsKilledStringParts);

                $this->notificationService
                    ->queueNotification('repelled_resource_theft', [
                        'sourceDominionId' => $dominion->id,
                        'operationKey' => $operationKey,
                        'unitsKilled' => $unitsKilledString,
                    ])
                    ->sendNotifications($target, 'irregular_dominion');

                if ($unitsKilledString) {
                    $message = "The enemy has prevented our {$operationInfo['name']} attempt and managed to capture $unitsKilledString.";
                } else {
                    $message = "The enemy has prevented our {$operationInfo['name']} attempt.";
                }

                return [
                    'success' => false,
                    'message' => $message,
                    'alert-type' => 'warning',
                ];
            }
        }

        switch ($operationKey) {
            case 'steal_platinum':
                $resource = 'platinum';
                $constraints = [
                    'target_amount' => 2/4,
                    'self_production' => 150,
                    'spy_carries' => 45/4,
                ];
                break;

            case 'steal_food':
                $resource = 'food';
                $constraints = [
                    'target_amount' => 2/4,
                    'self_production' => 0,
                    'spy_carries' => 0,
                ];
                break;

            case 'steal_lumber':
                $resource = 'lumber';
                $constraints = [
                    'target_amount' => 5,
                    'self_production' => 150,
                    'spy_carries' => 50/4,
                ];
                break;

            case 'steal_mana':
                $resource = 'mana';
                $constraints = [
                    'target_amount' => 3/4,
                    'self_production' => 150,
                    'spy_carries' => 50/4,
                ];
                break;

            case 'steal_ore':
                $resource = 'ore';
                $constraints = [
                    'target_amount' => 5/4,
                    'self_production' => 150,
                    'spy_carries' => 50/4,
                ];
                break;

            case 'steal_gems':
                $resource = 'gems';
                $constraints = [
                    'target_amount' => 2/4,
                    'self_production' => 100,
                    'spy_carries' => 50/4,
                ];
                break;

            case 'abduct_draftees':
                $resource = 'draftees';
                $constraints = [
                    'target_amount' => 2,
                    'self_production' => 0,
                    'spy_carries' => 1,
                ];
                break;

            case 'abduct_peasants':
                $resource = 'peasants';
                $constraints = [
                    'target_amount' => 2,
                    'self_production' => 0,
                    'spy_carries' => 1,
                ];
                break;

            default:
                throw new LogicException("Unknown resource theft operation {$operationKey}");
        }

        $amountStolen = $this->getResourceTheftAmount($dominion, $target, $resource, $constraints);

        # Amount stolen decreased by land ratio.
        $amountStolen = $amountStolen * min(1, $this->rangeCalculator->getDominionRange($dominion, $target)/100);

        # Different logic for abducting draftees or peasants.
        if($resource == 'draftees')
        {
            DB::transaction(function () use ($dominion, $target, $resource, $amountStolen, $operationKey) {
                $dominion->{"military_{$resource}"} += $amountStolen;
                $dominion->save([
                    'event' => HistoryService::EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION,
                    'action' => $operationKey
                ]);

                $target->{"military_{$resource}"} -= $amountStolen;
                $target->save([
                    'event' => HistoryService::EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION,
                    'action' => $operationKey
                ]);
            });
        }
        elseif($resource == 'peasants')
        {
            DB::transaction(function () use ($dominion, $target, $resource, $amountStolen, $operationKey) {
                $dominion->{"{$resource}"} += $amountStolen;
                $dominion->save([
                    'event' => HistoryService::EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION,
                    'action' => $operationKey
                ]);

                $target->{"{$resource}"} -= $amountStolen;
                $target->save([
                    'event' => HistoryService::EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION,
                    'action' => $operationKey
                ]);
            });
        }
        else
        {
            DB::transaction(function () use ($dominion, $target, $resource, $amountStolen, $operationKey) {
                $dominion->{"resource_{$resource}"} += $amountStolen;
                $dominion->{"stat_total_{$resource}_stolen"} += $amountStolen;
                $dominion->save([
                    'event' => HistoryService::EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION,
                    'action' => $operationKey
                ]);

                $target->{"resource_{$resource}"} -= $amountStolen;
                $target->save([
                    'event' => HistoryService::EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION,
                    'action' => $operationKey
                ]);
            });
        }

        // Surreal Perception
        $sourceDominionId = null;
        if ($this->spellCalculator->isSpellActive($target, 'surreal_perception'))
        {
            $sourceDominionId = $dominion->id;
        }

        $this->notificationService
            ->queueNotification('resource_theft', [
                'sourceDominionId' => $sourceDominionId,
                'operationKey' => $operationKey,
                'amount' => $amountStolen,
                'resource' => $resource,
            ])
            ->sendNotifications($target, 'irregular_dominion');

        return [
            'success' => true,
            'message' => sprintf(
                'Your spies infiltrate the target\'s dominion successfully and return with %s %s.',
                number_format($amountStolen),
                $resource
            ),
            'redirect' => route('dominion.op-center.show', $target),
        ];
    }

    protected function getResourceTheftAmount(
        Dominion $dominion,
        Dominion $target,
        string $resource,
        array $constraints
    ): int {
        if (($resource === 'platinum') && $this->spellCalculator->isSpellActive($target, 'fools_gold')) {
            return 0;
        }

        // Limit to percentage of target's raw production
        # For draftee abduction, limit to 1% of target's draftees.
        # For peasant abduction, limit to 0.5% of target's peasants.
        $maxTarget = true;
        if($resource == 'draftees')
        {
            $maxTarget = intval($target->military_draftees * 0.01);
        }
        elseif($resource == 'peasants')
        {
            $maxTarget = intval($target->peasants * 0.005);
        }
        elseif ($constraints['target_amount'] > 0)
        {
            $maxTarget = $target->{'resource_' . $resource} * $constraints['target_amount'] / 100;
        }

        // Limit to percentage of dominion's raw production
        # Does not apply abduct_draftees or abduct_peasants.
        $maxDominion = true;
        if ($constraints['self_production'] > 0) {
            if ($resource === 'platinum') {
                $maxDominion = floor($this->productionCalculator->getPlatinumProductionRaw($dominion) * $constraints['self_production'] / 100);
            } elseif ($resource === 'food') {
                $maxDominion = floor($this->productionCalculator->getFoodProductionRaw($dominion) * $constraints['self_production'] / 100);
            } elseif ($resource === 'lumber') {
                $maxDominion = floor($this->productionCalculator->getLumberProductionRaw($dominion) * $constraints['self_production'] / 100);
            } elseif ($resource === 'mana') {
                $maxDominion = floor($this->productionCalculator->getManaProductionRaw($dominion) * $constraints['self_production'] / 100);
            } elseif ($resource === 'ore') {
                $maxDominion = floor($this->productionCalculator->getOreProductionRaw($dominion) * $constraints['self_production'] / 100);
            } elseif ($resource === 'gems') {
                $maxDominion = floor($this->productionCalculator->getGemProductionRaw($dominion) * $constraints['self_production'] / 100);
            }
        }

        // Limit to amount carryable by spies
        $maxCarried = true;
        if ($constraints['spy_carries'] > 0) {
            // todo: refactor raw spies calculation
            $maxCarried = $this->militaryCalculator->getSpyRatioRaw($dominion) * $this->landCalculator->getTotalLand($dominion) * $constraints['spy_carries'];
        }

        // Forest Haven reduction
        if ($resource === 'platinum') {
            $forestHavenStolenPlatinumReduction = 8;
            $forestHavenStolenPlatinumReductionMax = 80;
            $stolenPlatinumMultiplier = (1 - min(
                    (($target->building_forest_haven / $this->landCalculator->getTotalLand($target)) * $forestHavenStolenPlatinumReduction),
                    ($forestHavenStolenPlatinumReductionMax / 100)
                ));

            $maxTarget *= $stolenPlatinumMultiplier;
        }

        return min($maxTarget, $maxDominion, $maxCarried);
    }

    /**
     * @param Dominion $dominion
     * @param string $operationKey
     * @param Dominion $target
     * @return array
     * @throws Exception
     */
    protected function performHostileOperation(Dominion $dominion, string $operationKey, Dominion $target): array
    {
        if ($dominion->round->hasOffensiveActionsDisabled()) {
            throw new GameException('Black ops have been disabled for the remainder of the round.');
        }

        $operationInfo = $this->espionageHelper->getOperationInfo($operationKey);

        if ($this->espionageHelper->isWarOperation($operationKey)) {
            $warDeclared = ($dominion->realm->war_realm_id == $target->realm->id || $target->realm->war_realm_id == $dominion->realm->id);
            if (!$warDeclared && !$this->militaryCalculator->recentlyInvadedBy($dominion, $target)) {
                throw new GameException("You cannot perform {$operationInfo['name']} outside of war.");
            }
        }

        $selfSpa = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');

        // You need at least some positive SPA to perform espionage operations
        if ($selfSpa === 0.0) {
            // Don't reduce spy strength by throwing an exception here
            throw new GameException("Your spy force is too weak to cast {$operationInfo['name']}. Please train some more spies.");
        }

        if ($targetSpa !== 0.0) {
            $successRate = $this->opsHelper->operationSuccessChance(
                $selfSpa,
                $targetSpa,
                static::HOSTILE_MULTIPLIER_SUCCESS_RATE
            );

            if (!random_chance($successRate)) {
                // Values (percentage)
                $spiesKilledBasePercentage = 1;

                $spiesKilledMultiplier = $this->getSpyLossesReductionMultiplier($dominion);

                $spyLossSpaRatio = ($targetSpa / $selfSpa);
                $spiesKilledPercentage = clamp($spiesKilledBasePercentage * $spyLossSpaRatio, 0.5, 1.5);

                $unitsKilled = [];
                $spiesKilled = (int)floor(($dominion->military_spies * ($spiesKilledPercentage / 100)) * $spiesKilledMultiplier);
                if ($spiesKilled > 0) {
                    $unitsKilled['spies'] = $spiesKilled;
                    $dominion->military_spies -= $spiesKilled;
                }

                foreach ($dominion->race->units as $unit) {
                    if ($unit->getPerkValue('counts_as_spy_offense')) {
                        $unitKilledMultiplier = ((float)$unit->getPerkValue('counts_as_spy_offense') / 2) * ($spiesKilledPercentage / 100) * $spiesKilledMultiplier;
                        $unitKilled = (int)floor($dominion->{"military_unit{$unit->slot}"} * $unitKilledMultiplier);
                        if ($unitKilled > 0) {
                            $unitsKilled[strtolower($unit->name)] = $unitKilled;
                            $dominion->{"military_unit{$unit->slot}"} -= $unitKilled;
                        }
                    }
                }

                $unitsKilledStringParts = [];
                foreach ($unitsKilled as $name => $amount) {
                    $amountLabel = number_format($amount);
                    $unitLabel = str_plural(str_singular($name), $amount);
                    $unitsKilledStringParts[] = "{$amountLabel} {$unitLabel}";
                }
                $unitsKilledString = generate_sentence_from_array($unitsKilledStringParts);

                $this->notificationService
                    ->queueNotification('repelled_spy_op', [
                        'sourceDominionId' => $dominion->id,
                        'operationKey' => $operationKey,
                        'unitsKilled' => $unitsKilledString,
                    ])
                    ->sendNotifications($target, 'irregular_dominion');

                if ($unitsKilledString) {
                    $message = "The enemy has prevented our {$operationInfo['name']} attempt and managed to capture $unitsKilledString.";
                } else {
                    $message = "The enemy has prevented our {$operationInfo['name']} attempt.";
                }

                return [
                    'success' => false,
                    'message' => $message,
                    'alert-type' => 'warning',
                ];
            }
        }

        $damageDealt = [];
        $baseDamage = (isset($operationInfo['percentage']) ? $operationInfo['percentage'] : 1) / 100;

        # Calculate ratio differential.
        $baseDamageMultiplier = max( min( min( ($selfSpa-$targetSpa+3)/5,1 ) * max( ($selfSpa/max($targetSpa,0.01))/5,1 ) ,3) ,0);

        $baseDamage *= $baseDamageMultiplier;

        if (isset($operationInfo['decreases'])) {
            foreach ($operationInfo['decreases'] as $attr) {
                $damage = $target->{$attr} * $baseDamage;

                // Damage reduction from Docks / Harbor
                if ($attr == 'resource_boats') {
                    $boatsProtected = $this->militaryCalculator->getBoatsProtected($target);
                    $damage = ($target->{$attr} - $boatsProtected) * $baseDamage;
                }

                // Check for immortal wizards
                if ($dominion->race->getPerkValue('immortal_wizards') != 0 && $attr == 'military_wizards') {
                    $damage = 0;
                }

                $target->{$attr} -= round($damage);
                $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attr, $damage));

                // Update statistics
                if (isset($dominion->{"stat_{$operationInfo['key']}_damage"})) {
                    $dominion->{"stat_{$operationInfo['key']}_damage"} += round($damage);
                }
            }
        }
        if (isset($operationInfo['increases'])) {
            foreach ($operationInfo['increases'] as $attr) {
                $damage = $target->{$attr} * $baseDamage;
                $target->{$attr} += round($damage);
            }
        }

        $target->save([
            'event' => HistoryService::EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION,
            'action' => $operationKey
        ]);

        // Surreal Perception
        $sourceDominionId = null;
        if ($this->spellCalculator->isSpellActive($target, 'surreal_perception'))
        {
            $sourceDominionId = $dominion->id;
        }

        $damageString = generate_sentence_from_array($damageDealt);

        $this->notificationService
            ->queueNotification('received_spy_op', [
                'sourceDominionId' => $sourceDominionId,
                'operationKey' => $operationKey,
                'damageString' => $damageString,
            ])
            ->sendNotifications($target, 'irregular_dominion');

        return [
            'success' => true,
            'damage' => $damage,
            'message' => sprintf(
                'Your spies infiltrate the target\'s dominion successfully, they lost %s.',
                $damageString
            ),
            'redirect' => route('dominion.op-center.show', $target),
        ];
    }

    /**
     * Calculate the XP (resource_tech) gained when casting a black-op.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param int $damage
     * @return int
     *
     */
    protected function calculateXpGain(Dominion $dominion, Dominion $target, int $damage): int
    {
      if($damage == 0 or $damage == NULL)
      {
        return 0;
      }
      else
      {
        $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;
        $base = 30;

        return $base * $landRatio;
      }
    }

    /**
     * Calculate the XP (resource_tech) gained when casting a black-op.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param int $damage
     * @return int
     *
     */
    protected function getSpyLossesReductionMultiplier(Dominion $dominion): int
    {

      $forestHavenSpyCasualtyReduction = 30;

      $spiesKilledMultiplier = 1;
      # Forest Havens
      $spiesKilledMultiplier -= ($dominion->building_forest_haven / $this->landCalculator->getTotalLand($dominion)) * 30;
      # Techs
      $spiesKilledMultiplier -= $dominion->getTechPerkMultiplier('spy_losses');
      # Hideouts
      $spiesKilledMultiplier -= $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'hideouts');
      # Cap at 0
      $spiesKilledMultiplier = max(0, $spiesKilledMultiplier);

      return $spiesKilledMultiplier;

    }
