<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Exception;
use LogicException;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

use OpenDominion\Exceptions\GameException;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\OpsHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Building;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Resource;

use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Services\Dominion\QueueService;

use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;
use OpenDominion\Models\Spyop;


use OpenDominion\Calculators\Dominion\LandImprovementCalculator;

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

    /**
     * EspionageActionService constructor.
     */
    public function __construct()
    {
        $this->buildingHelper = app(BuildingHelper::class);
        $this->buildingCalculator = app(BuildingCalculator::class);
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
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->resourceService = app(ResourceService::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->statsService = app(StatsService::class);
        $this->landImprovementCalculator = app(LandImprovementCalculator::class);
        $this->espionageCalculator = app(EspionageCalculator::class);
    }

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
        $this->guardLockedDominion($target);
        $this->guardActionsDuringTick($dominion);
        $this->guardActionsDuringTick($target);

        #$operationInfo = $this->espionageHelper->getOperationInfo($operationKey);

        if(!$dominion->round->hasStarted())
        {
            throw new GameException('You cannot perfrm spy ops until the round has started.');
        }

        // Qur: Statis
        if($this->spellCalculator->getPassiveSpellPerkValue($target, 'stasis'))
        {
            throw new GameException('A magical stasis surrounds the Qurrian lands, making it impossible for spies to sneak in.');
        }
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot spy while you are in stasis.');
        }

        $spyop = Spyop::where('key', $operationKey)->first();

        if (!$spyop)
        {
            throw new LogicException("Cannot perform unknown operation '{$operationKey}'");
        }

        if (!$this->espionageCalculator->canPerform($dominion, $spyop))
        {
            throw new GameException("You cannot perform {$spyop->name}.");
        }

        // You need at least some positive SPA to perform espionage operations
        if ($this->militaryCalculator->getSpyRatio($dominion) === 0.0)
        {
            throw new GameException("You need at least one full spy to perform {$spyop->name}. Please train some more spies.");
        }


        $spyStrengthCost = $this->espionageCalculator->getSpyStrengthCost($spyop);

        if ($dominion->spy_strength <= 0 or ($dominion->spy_strength - $spyStrengthCost) < 0)
        {
            throw new GameException("Your wizards to not have enough strength to perform {$spyop->name}. You need {$spyStrengthCost}% spy strength to cast this spell.");
        }

        if ($this->protectionService->isUnderProtection($dominion))
        {
            throw new GameException('You cannot perform espionage operations while under protection');
        }

        if ($this->protectionService->isUnderProtection($target))
        {
            throw new GameException('You cannot perform espionage operations on targets which are under protection');
        }

        if (!$this->rangeCalculator->isInRange($dominion, $target))
        {
            throw new GameException('You cannot perform espionage operations on targets outside of your range');
        }

        if ($dominion->round->id !== $target->round->id)
        {
            throw new GameException('Nice try, but you cannot perform espionage operations cross-round');
        }

        if ($dominion->realm->id === $target->realm->id and ($dominion->round->mode == 'standard' or $dominion->round->mode == 'standard-duration'))
        {
            throw new GameException('You cannot perform espionage operations on your realmies in standard rounds.');
        }

        if ($dominion->id === $target->id)
        {
            throw new GameException('Nice try, but you cannot perform espionage operations on yourself');
        }


        # If abducting, the target must also be a faction that can abduct.
        if($operationKey == 'abduct_draftees' or $operationKey == 'abduct_peasants')
        {
            if(!$this->espionageCalculator->isSpyopAvailableToDominion($target, $spyop))
            {
                throw new GameException('Abduction not possible. Your population is not compatible with ' . $target->race->name . '. You can only abduct from other factions that can also abduct.');
            }
        }

        $result = null;

        DB::transaction(function () use ($dominion, $target, $operationKey, &$result, $spyStrengthCost, $spyop)
        {

            if ($spyop->scope === 'info')
            {
                $result = $this->performInfoGatheringOperation($dominion, $operationKey, $target);
            }
            elseif($spyop->scope === 'theft')
            {
                $result = $this->performTheftOperation($dominion, $target, $spyop);
            }
            elseif ($spyop->scope === 'hostile')
            {
                $result = $this->performHostileOperation($dominion, $target, $spyop);
            }
            else
            {
                throw new LogicException("Unknown type for espionage operation {$operationKey}");
            }

            $dominion->spy_strength -= $spyStrengthCost;

            # XP Gained.
            if(isset($result['damage']))
            {
                $xpGained = $this->calculateXpGain($dominion, $target, $result['damage']);
                $dominion->xp += $xpGained;
            }

            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION,
                'action' => $operationKey
            ]);
        });

        return [
                'message' => $result['message'],
                'data' => [
                    'operation' => $operationKey,
                ],
                'redirect' =>
                    $this->espionageHelper->isInfoGatheringOperation($operationKey) && $result['success']
                        ? route('dominion.insight.show', $target->id)
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

        $selfSpa = min(10, $this->militaryCalculator->getSpyRatio($dominion, 'offense'));
        $targetSpa = min(10, $this->militaryCalculator->getSpyRatio($target, 'defense'));

        // You need at least some positive SPA to perform espionage operations
        if ($selfSpa === 0.0)
        {
            // Don't reduce spy strength by throwing an exception here
            throw new GameException("Your spy force is too weak to cast {$operationInfo['name']}. Please train some more spies.");
        }

        if ($targetSpa !== 0.0)
        {
            $successRate = $this->opsHelper->operationSuccessChance($selfSpa, $targetSpa, static::INFO_MULTIPLIER_SUCCESS_RATE);
            $success = random_chance($successRate);

            if($target->getSpellPerkValue('blind_to_reptilian_spies_on_info') and $dominion->race->name == 'Reptilians')
            {
                $success = True;
            }

            if (!$success)
            {
                $this->statsService->updateStat($dominion, 'espionage_info_failure', 1);

                // Values (percentage)
                $spiesKilledBasePercentage = 0.25;

                $spiesKilledMultiplier = $this->getSpyLossesReductionMultiplier($dominion);

                $spyLossSpaRatio = ($targetSpa / $selfSpa);
                $spiesKilledPercentage = clamp($spiesKilledBasePercentage * $spyLossSpaRatio, 0.25, 1);

                $unitsKilled = [];
                $spiesKilled = (int)floor(($dominion->military_spies * ($spiesKilledPercentage / 100)) * $spiesKilledMultiplier);

                # Immortal spies
                if($dominion->race->getPerkValue('immortal_spies') or $dominion->getSpellPerkValue('immortal_spies') or $dominion->realm->getArtefactPerkMultiplier('immortal_spies'))
                {
                    $spiesKilled = 0;
                }

                if ($spiesKilled > 0)
                {
                    $unitsKilled['spies'] = $spiesKilled;
                    $dominion->military_spies -= $spiesKilled;

                    $this->statsService->updateStat($dominion, 'spies_killed', $spiesKilled);
                    $this->statsService->updateStat($target, 'spies_lost', $spiesKilled);

                    if($target->realm->alignment === 'evil' and !$target->race->getPerkValue('converts_executed_spies'))
                    {
                        $this->resourceService->updateRealmResources($target->realm, ['body' => $spiesKilled]);
                    }
                }

                $spyUnitsKilled = 0;
                foreach ($dominion->race->units as $unit)
                {
                    if ($unit->getPerkValue('counts_as_spy_offense'))
                    {
                        if($unit->getPerkValue('immortal_spy'))
                        {
                            $unitKilled = 0;
                        }
                        else
                        {
                            $unitKilledMultiplier = ((float)$unit->getPerkValue('counts_as_spy_offense') / 2) * ($spiesKilledPercentage / 100) * $spiesKilledMultiplier;
                            $unitKilled = (int)floor($dominion->{"military_unit{$unit->slot}"} * $unitKilledMultiplier);
                        }

                          if ($unitKilled > 0)
                          {
                              $unitsKilled[strtolower($unit->name)] = $unitKilled;
                              $dominion->{"military_unit{$unit->slot}"} -= $unitKilled;

                              $this->statsService->updateStat($dominion, ('unit' . $unit->slot . '_lost'), $unitKilled);
                              $this->statsService->updateStat($target, 'units_killed', $unitKilled);

                              $spyUnitsKilled += $unitKilled;

                              if($target->realm->alignment === 'evil' and !$target->race->getPerkValue('converts_executed_spies'))
                              {
                                $this->resourceService->updateRealmResources($target->realm, ['body' => $unitKilled]);
                              }
                          }
                    }
                }

                if($target->race == 'Demon')
                {
                    $this->resourceService->updateResources($dominion, ['soul' => ($spiesKilled + $spyUnitsKilled)]);
                }

                if($target->race->getPerkValue('converts_executed_spies'))
                {
                    $targetUnit = 'military_unit' . $target->race->getPerkValue('converts_executed_spies');
                    $this->notificationService->queueNotification('spy_conversion_occurred',['sourceDominionId' => $dominion->id, 'converted' => ($spiesKilled + $spyUnitsKilled)]);
                    $this->queueService->queueResources('training', $target, [$targetUnit => ($spiesKilled + $spyUnitsKilled)], 2);

                    $this->statsService->updateStat($target, 'units_converted', ($spiesKilled + $spyUnitsKilled));
                }

                if ($target->getSpellPerkValue('persuasion'))
                {
                    $this->notificationService->queueNotification('persuasion_occurred',['sourceDominionId' => $dominion->id, 'persuaded' => ($spiesKilled + $spyUnitsKilled)]);
                    $this->queueService->queueResources('training', $target, ['military_spies' => ($spiesKilled + $spyUnitsKilled)], 2);
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

        $this->statsService->updateStat($dominion, 'espionage_info_success', 1);

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


                // Units at home
                array_set($data, 'units.home.draftees',$target->military_draftees);
                array_set($data, 'units.home.spies',$target->military_spies);
                array_set($data, 'units.home.wizards',$target->military_wizards);
                array_set($data, 'units.home.archmages',$target->military_archmages);

                foreach (range(1, 4) as $slot)
                {
                    array_set($data, "units.home.unit{$slot}", $target->{'military_unit' . $slot});
                }

                // Units returning (100% accurate)
                $this->queueService->getInvasionQueue($target)->each(static function ($row) use (&$data)
                {
                    if (!starts_with($row->resource, 'military_')) {
                        return; // continue
                    }

                    $unitType = str_replace('military_', '', $row->resource);
                    array_set($data, "units.returning.{$unitType}.{$row->hours}", $row->amount);
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

                foreach($this->improvementHelper->getImprovementsByRace($target->race) as $improvement)
                {
                    foreach($improvement->perks as $perk)
                    {
                          array_set($data, "{$improvement->key}.points", $this->improvementCalculator->getDominionImprovementAmountInvested($target, $improvement));
                          array_set($data, "{$perk->key}.rating", $target->getImprovementPerkMultiplier($perk->key));

                    }
                }

                $infoOp->data = $data;
                break;

            case 'survey_dominion':
                $data = [];

                foreach ($this->buildingHelper->getBuildingsByRace($target->race) as $building)
                {
                    array_set($data, "constructed.{$building->key}", $this->buildingCalculator->getBuildingAmountOwned($target, $building));
                }

                $totalConstructingLand = 0;

                $this->queueService->getConstructionQueue($target)->each(static function ($row) use (&$data, &$totalConstructingLand) {
                    $buildingKey = str_replace('building_', '', $row->resource);
                    array_set($data, "constructing.{$buildingKey}.{$row->hours}", $row->amount);
                    $totalConstructingLand += (int)$row->amount;
                });

                array_set($data, 'barren_land', $this->landCalculator->getTotalBarrenLand($target));
                array_set($data, 'constructing_land', $totalConstructingLand);
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

                    $data['landtype_defense'][$landType] = $this->militaryCalculator->getDefensivePowerModifierFromLandType($target, $landType);
                }

                if($target->race->getPerkValue('land_improvements'))
                {
                    $data['land_improvements']['plain'] = $this->landImprovementCalculator->getOffensivePowerBonus($target);
                    $data['land_improvements']['mountain'] = $this->landImprovementCalculator->getGoldProductionBonus($target);
                    $data['land_improvements']['swamp'] = $this->landImprovementCalculator->getWizardPowerBonus($target);
                    $data['land_improvements']['forest'] = $this->landImprovementCalculator->getPopulationBonus($target);
                    $data['land_improvements']['hill'] = $this->landImprovementCalculator->getDefensivePowerBonus($target);
                    $data['land_improvements']['water'] = $this->landImprovementCalculator->getFoodProductionBonus($target);
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

        // Surreal Perception
        if ($target->getSpellPerkValue('reveal_ops'))
        {
            $this->notificationService
                ->queueNotification('received_spy_op', [
                    'sourceDominionId' => $dominion->id,
                    'operationKey' => $operationKey,
                ])
                ->sendNotifications($target, 'irregular_dominion');
        }

        $infoOp->save();

        return [
            'success' => true,
            'message' => 'Your spies infiltrate the ' . $target->name . ' successfully and return with a wealth of information.',
            'redirect' => route('dominion.insight.show', $target),
        ];
    }

    protected function getTheftAmount(Dominion $dominion, Dominion $target, Spyop $spyop, string $resource, float $ratio, float $spyUnits, float $maxPerSpy): int
    {
        if($spyop->scope !== 'theft' and $spyop->scope !== 'hostile')
        {
            return 0;
        }

        if($resource == 'draftees')
        {
            $availableResource = $dominion->military_draftees;
        }
        elseif($resource == 'peasants')
        {
            $availableResource = $dominion->peasants;
        }
        else
        {
            $availableResource = $this->resourceCalculator->getAmount($target, $resource);
        }

        // Unit theft protection
        for ($slot = 1; $slot <= $target->race->units->count(); $slot++)
        {
            if($theftProtection = $target->race->getUnitPerkValueForUnitSlot($slot, 'protects_resource_from_theft'))
            {

                if($theftProtection[0] == $resource)
                {
                    $availableResource -= $target->{'military_unit'.$slot} * $theftProtection[1];
                }
            }
        }

        $availableResource = max(0, $availableResource);

        $theftAmount = min($availableResource * $ratio, $spyUnits * $maxPerSpy) * (0.9 + $dominion->spy_strength / 1000);

        # The stealer can increase
        $thiefModifier = 1;
        $thiefModifier += $dominion->getAdvancementPerkMultiplier('amount_stolen');
        $thiefModifier += $dominion->getDeityPerkMultiplier('amount_stolen');
        $thiefModifier += $dominion->race->getPerkMultiplier('amount_stolen');

        $theftAmount *= $thiefModifier;

        # But the target can decrease, which comes afterwards
        $targetModifier = 0;
        $targetModifier += $target->getSpellPerkMultiplier($resource . '_theft');
        $targetModifier += $target->getSpellPerkMultiplier('all_theft');
        $targetModifier += $target->getBuildingPerkMultiplier($resource . '_theft_reduction');

        $theftAmount *= (1 + $targetModifier);

        $theftAmount = max(0, $theftAmount);

        return $theftAmount;
    }

    protected function performHostileOperation(Dominion $dominion, Dominion $target, Spyop $spyop): array
    {
        $selfSpa = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');
        $spyUnits = $this->militaryCalculator->getSpyRatioRaw($dominion) * $this->landCalculator->getTotalLand($dominion);

        if($targetSpa == 0.0 or random_chance($this->opsHelper->operationSuccessChance($selfSpa, $targetSpa, static::HOSTILE_MULTIPLIER_SUCCESS_RATE)))
        {
            $this->statsService->updateStat($dominion, 'espionage_hostile_success', 1);
            $damageDealt = [];

            foreach($spyop->perks as $perk)
            {
                $spyopPerkValues = $spyop->getSpyopPerkValues($spyop->key, $perk->key);

                if($perk->key === 'abduct_draftees' or $perk->key === 'abduct_peasants')
                {
                    if($perk->key == 'abduct_draftees')
                    {
                        $attribute = 'draftees';
                        $resourceString = 'military_draftees';
                    }
                    elseif($perk->key == 'abduct_peasants')
                    {
                        $attribute = 'peasants';
                        $resourceString = $attribute;
                    }

                    $ratio = (float)$spyopPerkValues[0] / 100;
                    $maxPerSpy = (float)$spyopPerkValues[1];

                    $damage = $this->getTheftAmount($dominion, $target, $spyop, $attribute, $ratio, $spyUnits, $maxPerSpy);

                    $target->{$resourceString} -= $damage;
                    $dominion->{$resourceString} += $damage;

                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    $this->statsService->updateStat($dominion, (str_replace('military_', '', $attribute) .  '_stolen'), 1);
                }

                if($perk->key === 'convert_peasants_vampire' or $perk->key === 'convert_draftees_vampire')
                {
                    if($perk->key == 'convert_draftees_vampire')
                    {
                        $attribute = 'draftees';
                        $resourceString = 'military_draftees';
                    }
                    elseif($perk->key == 'convert_peasants_vampire')
                    {
                        $attribute = 'peasants';
                        $resourceString = $attribute;
                    }

                    $ratio = (float)$spyopPerkValues[0] / 100;
                    $maxPerSpy = (float)$spyopPerkValues[1];
                    $newSlot = (int)$spyopPerkValues[2];
                    $newUnit = 'military_unit'.$newSlot;
                    $ticksToConvert = (int)$spyopPerkValues[3];

                    $damage = $this->getTheftAmount($dominion, $target, $spyop, $attribute, $ratio, $spyUnits, $maxPerSpy);

                    $target->{$resourceString} -= $damage;
                    $dominion->{$resourceString} += $damage;

                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    $this->queueService->queueResources('training', $dominion, [$newUnit => $damage], $ticksToConvert);

                    $this->statsService->updateStat($dominion, ('espionage_' . $attribute . '_killed'), $damage);
                    $this->statsService->updateStat($dominion, 'units_converted', $damage);
                    $this->statsService->updateStat($target, ('espionage_' . $attribute . '_lost'), $damage);
                }

                # Regular killing of draftees and wizards

                if($perk->key === 'kill_draftees')
                {
                    $attribute = 'military_draftees';
                    $ratio = $spyopPerkValues / 100;

                    $damage = $target->{$attribute} * $ratio;
                    $damage *= (1 + $this->getOpBaseDamageMultiplier($dominion, $target));
                    $damage *= (1 + $this->getOpDamageMultiplier($dominion, $target, $spyop, $attribute));

                    $damage = (int)floor($damage);

                    $target->{$attribute} -= $damage;
                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    $this->statsService->updateStat($dominion, 'espionage_draftees_killed', $damage);
                    $this->statsService->updateStat($target, 'draftees_lost', $damage);

                    if($dominion->race->getPerkValue('converts_assassinated_draftees'))
                    {
                        $newUnit = 'military_unit' . $dominion->race->getPerkValue('converts_assassinated_draftees');
                        $this->statsService->updateStat($dominion, 'units_converted', $damage);
                        $this->queueService->queueResources('training', $dominion, [$newUnit => $damage], 12);
                    }
                }

                if($perk->key === 'kill_wizards')
                {
                    $attribute = 'military_wizards';
                    $ratio = $spyopPerkValues / 100;

                    $damage = $target->{$attribute} * $ratio;
                    $damage *= (1 + $this->getOpBaseDamageMultiplier($dominion, $target));
                    $damage *= (1 + $this->getOpDamageMultiplier($dominion, $target, $spyop, $attribute));

                    $damage = (int)floor($damage);

                    $target->{$attribute} -= $damage;
                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    $this->statsService->updateStat($dominion, 'wizards_killed', $damage);
                    $this->statsService->updateStat($target, 'wizards_lost', $damage);
                }

                # Slaughter (kill and convert to food) of draftees and peasants

                if($perk->key === 'slaughter_draftees')
                {
                    $attribute = 'military_draftees';
                    $ratio = $spyopPerkValues[0] / 100;
                    $foodPerUnitKilled = $spyopPerkValues[1];

                    $damage = $target->{$attribute} * $ratio;
                    $damage *= (1 + $this->getOpBaseDamageMultiplier($dominion, $target));
                    $damage *= (1 + $this->getOpDamageMultiplier($dominion, $target, $spyop, $attribute));

                    $damage = (int)floor($damage);
                    $target->{$attribute} -= $damage;

                    $food = floor($damage * $foodPerUnitKilled);
                    $this->resourceService->updateResources($dominion, ['food' => $food]);

                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    $this->statsService->updateStat($dominion, 'draftees_killed', $damage);
                    $this->statsService->updateStat($target, 'draftees_lost', $damage);
                }

                if($perk->key === 'slaughter_peasants')
                {
                    $attribute = 'peasants';
                    $ratio = $spyopPerkValues[0] / 100;
                    $foodPerUnitKilled = $spyopPerkValues[1];

                    $damage = $target->{$attribute} * $ratio;
                    $damage *= (1 + $this->getOpBaseDamageMultiplier($dominion, $target));
                    $damage *= (1 + $this->getOpDamageMultiplier($dominion, $target, $spyop, $attribute));

                    $damage = (int)floor($damage);
                    $target->{$attribute} -= $damage;

                    $food = floor($damage * $foodPerUnitKilled);
                    $this->resourceService->updateResources($dominion, ['food' => $food]);

                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    $this->statsService->updateStat($dominion, 'peasants_killed', $damage);
                    $this->statsService->updateStat($target, 'peasants_lost', $damage);
                }

                # Butcher (kill and convert to food, soul, and blood) of draftees, peasants, and wizards
                if($perk->key === 'butcher_draftees')
                {
                    $attribute = 'military_draftees';
                    $ratio = $spyopPerkValues[0] / 100;
                    $soulsPerUnitKilled = $spyopPerkValues[1];
                    $bloodPerUnitKilled = $spyopPerkValues[2];
                    $foodPerUnitKilled = $spyopPerkValues[3];

                    $damage = $target->{$attribute} * $ratio;
                    $damage *= (1 + $this->getOpBaseDamageMultiplier($dominion, $target));
                    $damage *= (1 + $this->getOpDamageMultiplier($dominion, $target, $spyop, $attribute));

                    $damage = (int)floor($damage);
                    $target->{$attribute} -= $damage;

                    $soul = floor($damage * $soulsPerUnitKilled);
                    $blood = floor($damage * $bloodPerUnitKilled);
                    $food = floor($damage * $foodPerUnitKilled);

                    $this->resourceService->updateResources($dominion, ['soul' => $soul]);
                    $this->resourceService->updateResources($dominion, ['blood' => $blood]);
                    $this->resourceService->updateResources($dominion, ['food' => $food]);

                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    $this->statsService->updateStat($dominion, 'draftees_killed', $damage);
                    $this->statsService->updateStat($target, 'draftees_lost', $damage);
                }

                if($perk->key === 'butcher_peasants')
                {
                    $attribute = 'peasants';
                    $ratio = $spyopPerkValues[0] / 100;
                    $soulsPerUnitKilled = $spyopPerkValues[1];
                    $bloodPerUnitKilled = $spyopPerkValues[2];
                    $foodPerUnitKilled = $spyopPerkValues[3];

                    $damage = $target->{$attribute} * $ratio;
                    $damage *= (1 + $this->getOpBaseDamageMultiplier($dominion, $target));
                    $damage *= (1 + $this->getOpDamageMultiplier($dominion, $target, $spyop, $attribute));

                    $damage = (int)floor($damage);
                    $target->{$attribute} -= $damage;

                    $soul = floor($damage * $soulsPerUnitKilled);
                    $blood = floor($damage * $bloodPerUnitKilled);
                    $food = floor($damage * $foodPerUnitKilled);

                    $this->resourceService->updateResources($dominion, ['soul' => $soul]);
                    $this->resourceService->updateResources($dominion, ['blood' => $blood]);
                    $this->resourceService->updateResources($dominion, ['food' => $food]);

                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    $this->statsService->updateStat($dominion, 'peasants_killed', $damage);
                    $this->statsService->updateStat($target, 'peasants_lost', $damage);
                }

                if($perk->key === 'butcher_wizards')
                {
                    $attribute = 'military_wizards';
                    $ratio = $spyopPerkValues[0] / 100;
                    $soulsPerUnitKilled = $spyopPerkValues[1];
                    $bloodPerUnitKilled = $spyopPerkValues[2];
                    $foodPerUnitKilled = $spyopPerkValues[3];

                    $damage = $target->{$attribute} * $ratio;
                    $damage *= (1 + $this->getOpBaseDamageMultiplier($dominion, $target));
                    $damage *= (1 + $this->getOpDamageMultiplier($dominion, $target, $spyop, $attribute));

                    $damage = (int)floor($damage);
                    $target->{$attribute} -= $damage;

                    $soul = floor($damage * $soulsPerUnitKilled);
                    $blood = floor($damage * $bloodPerUnitKilled);
                    $food = floor($damage * $foodPerUnitKilled);

                    $this->resourceService->updateResources($dominion, ['soul' => $soul]);
                    $this->resourceService->updateResources($dominion, ['blood' => $blood]);
                    $this->resourceService->updateResources($dominion, ['food' => $food]);

                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    $this->statsService->updateStat($dominion, 'wizards_killed', $damage);
                    $this->statsService->updateStat($target, 'wizards_lost', $damage);
                }

                if($perk->key === 'sabotage_improvement')
                {
                    $attribute = 'improvements';
                    $improvementKey = (string)$spyopPerkValues[0];
                    $ratio = (float)$spyopPerkValues[1] / 100;

                    $improvement = Improvement::where('key', $improvementKey)->first();

                    $targetImprovementPoints = $this->improvementCalculator->getDominionImprovementAmountInvested($target, $improvement);

                    $damage = $targetImprovementPoints * $ratio;
                    $damage *= (1 + $this->getOpBaseDamageMultiplier($dominion, $target));
                    $damage *= (1 + $this->getOpDamageMultiplier($dominion, $target, $spyop, $attribute));

                    $damage = floor($damage);

                    if($damage > 0)
                    {
                        $this->improvementCalculator->decreaseImprovements($target, [$improvementKey => $damage]);
                        $this->queueService->queueResources('sabotage', $target, ['improvement_' . $improvementKey => $damage], 6);
                    }

                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    $this->statsService->updateStat($dominion, 'espionage_damage_improvements', $damage);
                }

                if($perk->key === 'sabotage_building')
                {
                    $attribute = 'building';
                    $buildingKey = (string)$spyopPerkValues[0];
                    $ratio = (float)$spyopPerkValues[1] / 100;

                    $building = Building::where('key',$buildingKey)->first();

                    $targetBuildings = $this->buildingCalculator->getBuildingAmountOwned($target, $building);

                    $damage = $targetBuildings * $ratio;
                    $damage *= (1 + $this->getOpBaseDamageMultiplier($dominion, $target));
                    $damage *= (1 + $this->getOpDamageMultiplier($dominion, $target, $spyop, $attribute));

                    $damage = (int)floor($damage);

                    $this->buildingCalculator->removeBuildings($target, [$buildingKey => $damage]);
                    $this->queueService->queueResources('sabotage', $target, ['building_' . $buildingKey => $damage], 6);
                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    $this->statsService->updateStat($dominion, 'espionage_damage_buildings', $damage);
                }
                if($perk->key === 'decrease_morale')
                {
                    $attribute = 'morale';
                    $ratio = $spyopPerkValues[0] / 100;

                    $damage = $target->{$attribute} * $ratio;
                    $damage *= (1 + $this->getOpBaseDamageMultiplier($dominion, $target));
                    $damage *= (1 + $this->getOpDamageMultiplier($dominion, $target, $spyop, $attribute));

                    $damage = (int)floor($damage);
                    $target->{$attribute} -= $damage;

                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    $this->statsService->updateStat($dominion, 'espionage_damage_morale', $damage);
                }
                if($perk->key === 'reduce_wizard_strength')
                {
                    $attribute = 'wizard_strength';
                    $ratio = $spyopPerkValues[0] / 100;

                    $damage = $target->{$attribute} * $ratio;
                    $damage *= (1 + $this->getOpBaseDamageMultiplier($dominion, $target));
                    $damage *= (1 + $this->getOpDamageMultiplier($dominion, $target, $spyop, $attribute));

                    $damage = (int)floor($damage);
                    $target->{$attribute} -= $damage;

                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    $this->statsService->updateStat($dominion, 'espionage_damage_wizard_strength', $damage);
                }

            }

            $target->save([
                'event' => HistoryService::EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION,
                'action' => $spyop->key
            ]);

            // Surreal Perception
            $sourceDominionId = null;
            if ($target->getSpellPerkValue('reveal_ops'))
            {
                $sourceDominionId = $dominion->id;
            }

            $damageString = generate_sentence_from_array($damageDealt);

            $this->notificationService
                ->queueNotification('received_spy_op', [
                    'sourceDominionId' => $sourceDominionId,
                    'operationKey' => $spyop->key,
                    'damageString' => $damageString,
                ])
                ->sendNotifications($target, 'irregular_dominion');

            return [
                'success' => true,
                'damage' => $damage,
                'message' => sprintf(
                    'Your spies infiltrate ' . $target->name . ' successfully, they lost %s.',
                    $damageString
                ),
                'redirect' => route('dominion.insight.show', $target),
            ];

        }
        else
        {
            $this->statsService->updateStat($dominion, 'espionage_hostile_failure', 1);
            // Values (percentage)
            $spiesKilledBasePercentage = 1;

            $spiesKilledMultiplier = $this->getSpyLossesReductionMultiplier($dominion);

            $spyLossSpaRatio = ($targetSpa / $selfSpa);
            $spiesKilledPercentage = clamp($spiesKilledBasePercentage * $spyLossSpaRatio, 0.5, 1.5);

            $unitsKilled = [];
            $spiesKilled = (int)floor(($dominion->military_spies * ($spiesKilledPercentage / 100)) * $spiesKilledMultiplier);

            # Immortal spies
            if($dominion->race->getPerkValue('immortal_spies') or $dominion->getSpellPerkValue('immortal_spies') or $dominion->realm->getArtefactPerkMultiplier('immortal_spies'))
            {
                $spiesKilled = 0;
            }

            if ($spiesKilled > 0)
            {
                $unitsKilled['spies'] = $spiesKilled;
                $dominion->military_spies -= $spiesKilled;

                $this->statsService->updateStat($dominion, 'spies_killed', $spiesKilled);
                $this->statsService->updateStat($target, 'spies_lost', $spiesKilled);

                if($target->realm->alignment === 'evil' and !$target->race->getPerkValue('converts_executed_spies'))
                {
                    $this->resourceService->updateRealmResources($target->realm, ['body' => $spiesKilled]);
                }
            }

            $spyUnitsKilled = 0;
            foreach ($dominion->race->units as $unit)
            {
                if ($unit->getPerkValue('counts_as_spy_offense'))
                {
                    if($unit->getPerkValue('immortal_spy'))
                    {
                        $unitKilled = 0;
                    }
                    else
                    {
                        $unitKilledMultiplier = ((float)$unit->getPerkValue('counts_as_spy_offense') / 2) * ($spiesKilledPercentage / 100) * $spiesKilledMultiplier;
                        $unitKilled = (int)floor($dominion->{"military_unit{$unit->slot}"} * $unitKilledMultiplier);
                    }

                      if ($unitKilled > 0)
                      {
                          $unitsKilled[strtolower($unit->name)] = $unitKilled;
                          $dominion->{"military_unit{$unit->slot}"} -= $unitKilled;

                          $this->statsService->updateStat($dominion, ('unit' . $unit->slot . '_lost'), $unitKilled);
                          $this->statsService->updateStat($target, 'units_killed', $unitKilled);

                          $spyUnitsKilled += $unitKilled;

                          if($target->realm->alignment === 'evil' and !$target->race->getPerkValue('converts_executed_spies'))
                          {
                            $this->resourceService->updateRealmResources($target->realm, ['body' => $unitKilled]);
                          }
                      }
                }
            }

            if($target->race == 'Demon')
            {
                $this->resourceService->updateResources($dominion, ['soul' => ($spiesKilled + $spyUnitsKilled)]);
            }

            if($target->race->getPerkValue('converts_executed_spies'))
            {
                $targetUnit = 'military_unit' . $target->race->getPerkValue('converts_executed_spies');
                $this->notificationService->queueNotification('spy_conversion_occurred',['sourceDominionId' => $dominion->id, 'converted' => ($spiesKilled + $spyUnitsKilled)]);
                $this->queueService->queueResources('training', $target, [$targetUnit => ($spiesKilled + $spyUnitsKilled)], 2);

                $this->statsService->updateStat($target, 'units_converted', ($spiesKilled + $spyUnitsKilled));
            }

            if ($target->getSpellPerkValue('persuasion'))
            {
                $this->notificationService->queueNotification('persuasion_occurred',['sourceDominionId' => $dominion->id, 'persuaded' => ($spiesKilled + $spyUnitsKilled)]);
                $this->queueService->queueResources('training', $target, ['military_spies' => ($spiesKilled + $spyUnitsKilled)], 2);
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
                    'operationKey' => $spyop->key,
                    'unitsKilled' => $unitsKilledString,
                ])
                ->sendNotifications($target, 'irregular_dominion');

            if ($unitsKilledString) {
                $message = "The enemy has prevented our {$spyop->name} attempt and managed to capture $unitsKilledString.";
            } else {
                $message = "The enemy has prevented our {$spyop->name} attempt.";
            }

            return [
                'success' => false,
                'message' => $message,
                'alert-type' => 'warning',
            ];
        }

    }

    /**
     * Calculate the XP gained when casting a black-op.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param int $damage
     * @return int
     *
     */
    protected function calculateXpGain(Dominion $dominion, Dominion $target, int $damage): int
    {
      if($damage === 0 or $damage === NULL)
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
     * Calculate the spy loss multiplier.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param int $damage
     * @return int
     *
     */
    protected function getSpyLossesReductionMultiplier(Dominion $dominion): float
    {

      $spiesKilledMultiplier = 1;

      // Buildings
      $spiesKilledMultiplier += $dominion->getBuildingPerkMultiplier('spy_losses');

      # Techs
      $spiesKilledMultiplier += $dominion->getAdvancementPerkMultiplier('spy_losses');

      // Improvements
      $spiesKilledMultiplier += $dominion->getImprovementPerkMultiplier('spy_losses');

      # Cap at 0% losses
      $spiesKilledMultiplier = max(0, $spiesKilledMultiplier);

      return $spiesKilledMultiplier;

    }


    /**
     * Returns the base damage multiplier, which is dependent on SPA difference.
     *
     * @param Dominion $caster
     * @param Dominion $target
     * @param string $spell
     * @param string $attribute
     * @return float|null
     */
    public function getOpBaseDamageMultiplier(Dominion $performer, Dominion $target): float
    {
        $performerSpa = $this->militaryCalculator->getSpyRatio($performer, 'offense');
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');
        return ($performerSpa - $targetSpa) / 10;
    }

    /**
     * Returns the damage done by a spell.
     *
     * @param Dominion $caster
     * @param Dominion $target
     * @param string $spell
     * @param string $attribute
     * @return float|null
     */
    public function getOpDamageMultiplier(Dominion $dominion, Dominion $target, Spyop $spyop, string $attribute): float
    {

        $damageMultiplier = 0;

        // Check for immortal wizards
        if($attribute === 'military_wizards')
        {
            if ($target->race->getPerkValue('immortal_wizards') != 0)
            {
                $damageMultiplier = -1;
            }
        }

        // Check for Masonries
        if($attribute === 'improvements')
        {
            $damageMultiplier -= $target->getBuildingPerkMultiplier('lightning_bolt_damage');
        }

        // Cap at -1.
        $damageMultiplier = max(-1, $damageMultiplier);

        return $damageMultiplier;

    }

  }
