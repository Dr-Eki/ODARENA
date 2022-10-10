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
use OpenDominion\Models\Spyop;
use OpenDominion\Models\Unit;

use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SabotageCalculator;

use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;

class SabotageActionService
{

    public function __construct()
    {
        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->sabotageCalculator = app(SabotageCalculator::class);

        $this->notificationService = app(NotificationService::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
        $this->resourceService = app(ResourceService::class);
        $this->statsService = app(StatsService::class);

        $this->unitHelper = app(UnitHelper::class);
    }

    public function sabotage(Dominion $saboteur, Dominion $target, Spyop $spyop, array $units): array
    {

        DB::transaction(function () use ($saboteur, $target, $spyop, $units)
        {
            // Sanitize input
            $units = array_map('intval', array_filter($units));
            $landRatio = $this->rangeCalculator->getDominionRange($saboteur, $target) / 100;

            // Checks
            if (array_sum($units) <= 0)
            {
                throw new GameException('You need to send at least some units.');
            }

            if ($this->protectionService->isUnderProtection($saboteur))
            {
                throw new GameException('You cannot sabotage while under protection.');
            }

            if ($this->protectionService->isUnderProtection($saboteur))
            {
                throw new GameException('You cannot sabotage dominions which are under protection.');
            }

            if (!$this->rangeCalculator->isInRange($saboteur, $target))
            {
                throw new GameException('You cannot sabotage dominions outside of your range.');
            }

            if ($saboteur->round->id !== $target->round->id)
            {
                throw new GameException('Nice try, but you cannot sabotage cross-round.');
            }

            if ($saboteur->realm->id === $target->realm->id and ($saboteur->round->mode == 'standard' or $saboteur->round->mode == 'standard-duration'))
            {
                throw new GameException('You cannot sabotage from other dominions in the same realm as you in standard rounds.');
            }

            if ($saboteur->id == $target->id)
            {
                throw new GameException('Nice try, but you sabotage yourself.');
            }

            if (!$this->passes43RatioRule($saboteur, $target, $landRatio, $units))
            {
                throw new GameException('You are sending out too much OP, based on your new home DP (4:3 rule).');
            }

            if (!$this->passesUnitSendableCapacityCheck($dominion, $units))
            {
                throw new GameException('You do not have enough caverns to send out this many units.');
            }

            if (!$this->hasEnoughUnitsAtHome($saboteur, $units))
            {
                throw new GameException('You don\'t have enough units at home to send this many units.');
            }

            foreach($units as $slot => $amount)
            {
                $unit = $saboteur->race->units->filter(function ($unit) use ($slot)
                {
                    return ($unit->slot === $slot);
                })->first();

                if($amount < 0)
                {
                    throw new GameException('Sabotage was canceled due to bad input.');
                }

                if($slot !== 'spies')
                {
                    if(!$this->unitHelper->isUnitOffensiveSpy($unit))
                    {
                        throw new GameException($unit->name . ' is not a spy unit and cannot be sent on sabotage missions.');
                    }

                    # OK, unit can be trained. Let's check for pairing limits.
                    if($this->unitHelper->unitHasCapacityLimit($saboteur, $slot) and !$this->unitHelper->checkUnitLimitForInvasion($saboteur, $slot, $amount))
                    {
                        throw new GameException('You can at most control ' . number_format($this->unitHelper->getUnitMaxCapacity($saboteur, $slot)) . ' ' . str_plural($unit->name) . '. To control more, you need to first have more of their superior unit.');
                    }

                    if(!$this->unitHelper->isUnitSendableByDominion($unit, $saboteur))
                    {
                        throw new GameException('You cannot send ' . $unit->name . ' on sabotage.');
                    }
                }
            }

            if ($saboteur->race->getPerkValue('cannot_sabotage'))
            {
                throw new GameException($saboteur->race->name . ' cannot sabotage.');
            }

            if ($saboteur->race->getPerkValue('cannot_sabotage'))
            {
                throw new GameException($saboteur->race->name . ' cannot sabotage.');
            }

            // Spell: Rainy Season (cannot invade)
            if ($saboteur->getSpellPerkValue('cannot_sabotage'))
            {
                throw new GameException('A spell is preventing from you sabotage.');
            }

            // Cannot invade until round has started.
            if(!$saboteur->round->hasStarted())
            {
                throw new GameException('You cannot sabotage until the round has started.');
            }

            // Cannot invade after round has ended.
            if($saboteur->round->hasEnded())
            {
                throw new GameException('You cannot sabotage after the round has ended.');
            }

            // Qur: Statis cannot be invaded.
            if($target->getSpellPerkValue('stasis'))
            {
                throw new GameException('A magical stasis surrounds the Qurrian lands, making it impossible for your spies to sabotage.');
            }

            // Qur: Statis cannot invade.
            if($saboteur->getSpellPerkValue('stasis'))
            {
                throw new GameException('You cannot sabotage while you are in stasis.');
            }

            // Check that saboteur has enough SS
            if($saboteur->spy_strength <= 0)
            {
                throw new GameException('You do not have enough spy strength to sabotage.');
            }

            $spyStrengthCost = $this->sabotageCalculator->getSpyStrengthCost($saboteur, $units);

            if($spyStrengthCost > $saboteur->spy_strength)
            {
                throw new GameException('You do not have enough spy strength to send that many units. You have ' . $saboteur->spy_strength . '% and would need ' . $spyStrengthCost . '% to send that many units.');
            }

            # END VALIDATION

            $this->sabotage = [
                'spyop_key' => $spyop->key,
                'saboteur' => [
                        'fog' => $saboteur->getSpellPerkValue('fog_of_war') ? true : false,
                        'spy_strength_current' => $saboteur->spy_strength,
                        'spy_strength_spent' => $spyStrengthCost,
                        'spy_ratio' => $this->militaryCalculator->getSpyRatio($saboteur, 'offense')
                    ],
                'target' => [
                        'crypt_bodies' => 0,
                        'fog' => $target->getSpellPerkValue('fog_of_war') ? true : false,
                        'reveal_ops' => $target->getSpellPerkValue('reveal_ops') ? true : false,
                        'spy_strength_current' => $target->spy_strength,
                        'spy_ratio' => $this->militaryCalculator->getSpyRatio($target, 'defense')
                    ],
                'damage' => [],
            ];

            foreach($spyop->perks as $perk)
            {
                $spyopPerkValues = $spyop->getSpyopPerkValues($spyop->key, $perk->key);

                if($perk->key === 'kill_peasants')
                {
                    $baseDamage = (float)$spyopPerkValues;
                    $attribute = 'peasants';

                    $ratioMultiplier = $this->sabotageCalculator->getRatioMultiplier($saboteur, $target, $spyop, $attribute, $units, false);
                    $saboteurDamageMultiplier = $this->sabotageCalculator->getSaboteurDamageMultiplier($saboteur, $attribute);
                    $targetDamageMultiplier = $this->sabotageCalculator->getTargetDamageMultiplier($saboteur, $attribute);

                    $damage = array_sum($units) * $baseDamage * $ratioMultiplier * $saboteurDamageMultiplier * $targetDamageMultiplier;

                    # Factor in peasant DP to increase/decrease peasant killed
                    $damage /= ($target->race->getPerkValue('peasant_dp') ?: 1);

                    $damage = floor($damage);

                    $damageDealt = min($damage, $target->peasants);
                    

                    $peasantsBefore = $target->peasants;
                    $target->peasants -= $damageDealt;
                    $peasantsAfter = $peasantsBefore - $damageDealt;

                    $this->statsService->updateStat($saboteur, 'sabotage_peasants_killed', $damageDealt);
                    $this->statsService->updateStat($target, 'sabotage_peasants_lost', $damageDealt);

                    # For Empire, add killed draftees go in the crypt
                    if($target->realm->alignment === 'evil')
                    {
                        $this->resourceService->updateRealmResources($target->realm, ['body' => $damageDealt]);
                        $this->sabotage['target']['crypt_bodies'] += $damageDealt;
                    }

                    $this->sabotage['damage'][$perk->key] = [
                        'ratio_multiplier' => $ratioMultiplier,
                        'saboteur_damage_multiplier' => $saboteurDamageMultiplier,
                        'target_damage_multiplier' => $targetDamageMultiplier,
                        'damage' => $damage,
                        'damage_dealt' => $damageDealt,
                        'peasants_before' => (int)$peasantsBefore,
                        'peasants_after' => (int)$peasantsAfter,
                    ];
                }

                if($perk->key === 'kill_draftees')
                {
                    $baseDamage = (float)$spyopPerkValues;
                    $attribute = 'draftees';

                    $ratioMultiplier = $this->sabotageCalculator->getRatioMultiplier($saboteur, $target, $spyop, $attribute, $units, false);
                    $saboteurDamageMultiplier = $this->sabotageCalculator->getSaboteurDamageMultiplier($saboteur, $attribute);
                    $targetDamageMultiplier = $this->sabotageCalculator->getTargetDamageMultiplier($saboteur, $attribute);

                    $damage = array_sum($units) * $baseDamage * $ratioMultiplier * $saboteurDamageMultiplier * $targetDamageMultiplier;

                    # Factor in draftee DP to increase/decrease draftees killed
                    $damage /= ($target->race->getPerkValue('draftee_dp') ?: 1);

                    $damage = floor($damage);

                    $damageDealt = min($damage, $target->military_draftees);

                    $drafteesBefore = $target->military_draftees;
                    $target->military_draftees -= $damageDealt;
                    $drafteesAfter = $drafteesBefore - $damageDealt;

                    $this->statsService->updateStat($saboteur, 'sabotage_draftees_killed', $damageDealt);
                    $this->statsService->updateStat($target, 'sabotage_draftees_lost', $damageDealt);

                    # For Empire, add killed draftees go in the crypt
                    if($target->realm->alignment === 'evil')
                    {
                        $this->resourceService->updateRealmResources($target->realm, ['body' => $damageDealt]);
                        $this->sabotage['target']['crypt_bodies'] += $damageDealt;
                    }

                    $this->sabotage['damage'][$perk->key] = [
                        'ratio_multiplier' => $ratioMultiplier,
                        'saboteur_damage_multiplier' => $saboteurDamageMultiplier,
                        'target_damage_multiplier' => $targetDamageMultiplier,
                        'damage' => $damage,
                        'damage_dealt' => $damageDealt,
                        'draftees_before' => (int)$drafteesBefore,
                        'draftees_after' => (int)$drafteesAfter,
                    ];
                }

                if($perk->key === 'decrease_morale')
                {
                    $baseDamage = (float)$spyopPerkValues;
                    $attribute = 'morale';

                    $ratioMultiplier = $this->sabotageCalculator->getRatioMultiplier($saboteur, $target, $spyop, $attribute, $units, false);
                    $saboteurDamageMultiplier = $this->sabotageCalculator->getSaboteurDamageMultiplier($saboteur, $attribute);
                    $targetDamageMultiplier = $this->sabotageCalculator->getTargetDamageMultiplier($saboteur, $attribute);

                    $damage = array_sum($units) * $baseDamage * $ratioMultiplier * $saboteurDamageMultiplier * $targetDamageMultiplier;

                    $damage = floor($damage);

                    $damageDealt = min($damage, $target->morale);

                    $moraleBefore = $target->morale;
                    $target->morale -= $damageDealt;
                    $moraleAfter = $moraleBefore - $damageDealt;

                    $this->statsService->updateStat($saboteur, 'sabotage_morale_damage_dealt', $damageDealt);
                    $this->statsService->updateStat($target, 'sabotage_morale_damage_suffered', $damageDealt);

                    $this->sabotage['damage'][$perk->key] = [
                        'ratio_multiplier' => $ratioMultiplier,
                        'saboteur_damage_multiplier' => $saboteurDamageMultiplier,
                        'target_damage_multiplier' => $targetDamageMultiplier,
                        'damage' => $damage,
                        'damage_dealt' => $damageDealt,
                        'morale_before' => (int)$moraleBefore,
                        'morale_after' => (int)$moraleAfter,
                    ];
                }

                if($perk->key === 'decrease_wizard_strength')
                {
                    $baseDamage = (float)$spyopPerkValues;
                    $attribute = 'wizard_strength';

                    $ratioMultiplier = $this->sabotageCalculator->getRatioMultiplier($saboteur, $target, $spyop, $attribute, $units, false);
                    $saboteurDamageMultiplier = $this->sabotageCalculator->getSaboteurDamageMultiplier($saboteur, $attribute);
                    $targetDamageMultiplier = $this->sabotageCalculator->getTargetDamageMultiplier($saboteur, $attribute);

                    $damage = array_sum($units) * $baseDamage * $ratioMultiplier * $saboteurDamageMultiplier * $targetDamageMultiplier;

                    $damage = floor($damage);

                    $damageDealt = min($damage, $target->wizard_strength);

                    $wizardStrengthBefore = $target->wizard_strength;
                    $target->wizard_strength -= $damageDealt;
                    $wizardStrengthAfter = $wizardStrengthBefore - $damageDealt;

                    $this->statsService->updateStat($saboteur, 'sabotage_wizard_strength_damage_dealt', $damageDealt);
                    $this->statsService->updateStat($target, 'sabotage_wizard_strength_damage_suffered', $damageDealt);

                    $this->sabotage['damage'][$perk->key] = [
                        'ratio_multiplier' => $ratioMultiplier,
                        'saboteur_damage_multiplier' => $saboteurDamageMultiplier,
                        'target_damage_multiplier' => $targetDamageMultiplier,
                        'damage' => $damage,
                        'damage_dealt' => $damageDealt,
                        'wizard_strength_before' => (int)$wizardStrengthBefore,
                        'wizard_strength_after' => (int)$wizardStrengthAfter,
                    ];
                }

                if($perk->key === 'sabotage_construction')
                {
                    $constructionBuildings = [];
                    $sabotagedConstruction = [];
                    $baseDamage = (float)$spyopPerkValues;
                    $attribute = 'construction';

                    $ratioMultiplier = $this->sabotageCalculator->getRatioMultiplier($saboteur, $target, $spyop, $attribute, $units, false);
                    $saboteurDamageMultiplier = $this->sabotageCalculator->getSaboteurDamageMultiplier($saboteur, $attribute);
                    $targetDamageMultiplier = $this->sabotageCalculator->getTargetDamageMultiplier($saboteur, $attribute);

                    $damage = (int)floor(array_sum($units) * $baseDamage * $ratioMultiplier * $saboteurDamageMultiplier * $targetDamageMultiplier);

                    $this->queueService->setForTick(false); # OFF

                    foreach($this->queueService->getConstructionQueue($target)->sortBy('hours')->shuffle() as $index => $constructionBuilding)
                    {
                        $buildingKey = str_replace('building_', '', $constructionBuilding->resource);
                        $hours = $constructionBuilding->hours;
                        $amount = $constructionBuilding->amount;
                        $constructionBuildings[$buildingKey] = [$hours => $amount];
                    }

                    if(!empty($constructionBuildings))
                    {
                          $damageRemaining = $damage;
                          foreach($constructionBuildings as $buildingKey => $construction)
                          {
                              if($damageRemaining <= 0)
                              {
                                  break;
                              }

                              $hours = key($construction);
                              $newHours = min(12, $hours + 2);

                              $amount = $construction[$hours];
                              $amountSabotaged = (int)min($amount, $damageRemaining);

                              $buildingResourceKey = 'building_' . $buildingKey;

                              $this->queueService->dequeueResourceForHour('construction', $target, $buildingResourceKey, $amountSabotaged, $hours);
                              $this->queueService->queueResources('construction', $target, [$buildingResourceKey => $amountSabotaged], $newHours);

                              $building = Building::where('key', $buildingKey)->first();

                              $sabotagedConstruction[$buildingKey] = [
                                  'name' => $building->name,
                                  'amount_construction' => $amount,
                                  'amount_sabotaged' => $amountSabotaged,
                                  'hours_before' => $hours,
                                  'hours_after' => $newHours
                                  ];

                              $damageRemaining -= $amountSabotaged;
                          }
                      }

                      $this->queueService->setForTick(true); # ON

                      $this->statsService->updateStat($saboteur, 'sabotage_damage_construction', $damage);

                      $this->sabotage['damage'][$perk->key] = [
                          'ratio_multiplier' => $ratioMultiplier,
                          'saboteur_damage_multiplier' => $saboteurDamageMultiplier,
                          'target_damage_multiplier' => $targetDamageMultiplier,
                          'damage' => $damage,
                          'damage_dealt' => $sabotagedConstruction
                      ];
                }
            }

            if($perk->key == 'sabotage_building')
            {
                $attribute = 'building';
                $buildingKey = (string)$spyopPerkValues[0];
                $ratio = (float)$spyopPerkValues[1] / 100;
                $this->sabotage['saboteur']['spy_strength_spent'];

                $building = Building::where('key',$buildingKey)->first();

                $targetBuildingsOwned = $this->buildingCalculator->getBuildingAmountOwned($target, $building);

                $baseDamage = (float)$spyopPerkValues;

                $ratioMultiplier = $this->sabotageCalculator->getRatioMultiplier($saboteur, $target, $spyop, $attribute, $units, false);
                $saboteurDamageMultiplier = $this->sabotageCalculator->getSaboteurDamageMultiplier($saboteur, $attribute);
                $targetDamageMultiplier = $this->sabotageCalculator->getTargetDamageMultiplier($saboteur, $attribute);

                $damage = array_sum($units) * $baseDamage * $ratioMultiplier * $saboteurDamageMultiplier * $targetDamageMultiplier;
                $damage = floor($damage);

                $damageDealt = min($damage, $targetBuildingsOwned);

                $this->buildingCalculator->removeBuildings($target, [$buildingKey => ['builtBuildingsToDestroy' => $damageDealt]]);
                $this->queueService->queueResources('repair', $target, [('building_' . $buildingKey) => $damageDealt], 6);

                $this->statsService->updateStat($saboteur, 'sabotage_buildings_damage_dealt', $damageDealt);
                $this->statsService->updateStat($target, 'sabotage_buildings_damage_suffered', $damageDealt);

                $this->sabotage['damage'][$perk->key] = [
                    'ratio_multiplier' => $ratioMultiplier,
                    'saboteur_damage_multiplier' => $saboteurDamageMultiplier,
                    'target_damage_multiplier' => $targetDamageMultiplier,
                    'damage' => $damage,
                    'damage_dealt' => $damageDealt,
                    'building_key' => $building->key,
                    'building_name' => $building->name
                ];
            }

            if($perk->key == 'sabotage_improvement')
            {
                $attribute = 'improvement';
                $improvementKey = (string)$spyopPerkValues[0];
                $baseDamage = (float)$spyopPerkValues[1] / 100;
                $this->sabotage['saboteur']['spy_strength_spent'];

                $improvement = Improvement::where('key', $improvementKey)->first();

                $targetImprovementPoints = $this->improvementCalculator->getDominionImprovementAmountInvested($target, $improvement);

                $ratioMultiplier = $this->sabotageCalculator->getRatioMultiplier($saboteur, $target, $spyop, $attribute, $units, false);
                $saboteurDamageMultiplier = $this->sabotageCalculator->getSaboteurDamageMultiplier($saboteur, $attribute);
                $targetDamageMultiplier = $this->sabotageCalculator->getTargetDamageMultiplier($saboteur, $attribute);

                $damage = array_sum($units) * $baseDamage * $ratioMultiplier * $saboteurDamageMultiplier * $targetDamageMultiplier;
                $damage = floor($damage);

                $damageDealt = min($damage, $targetImprovementPoints);

                $this->improvementCalculator->decreaseImprovements($target, [$improvementKey => $damage]);
                $this->queueService->queueResources('restore', $target, ['improvement_' . $improvementKey => $damage], 6);

                $this->statsService->updateStat($saboteur, 'sabotage_improvements_damage_dealt', $damageDealt);
                $this->statsService->updateStat($target, 'sabotage_improvements_damage_suffered', $damageDealt);

                $this->sabotage['damage'][$perk->key] = [
                    'ratio_multiplier' => $ratioMultiplier,
                    'saboteur_damage_multiplier' => $saboteurDamageMultiplier,
                    'target_damage_multiplier' => $targetDamageMultiplier,
                    'damage' => $damage,
                    'damage_dealt' => $damageDealt,
                    'improvement_key' => $improvement->key,
                    'improvements_name' => $improvement->name
                ];
            }

            # Calculate spy units
            $saboteur->spy_strength -= min($spyStrengthCost, $saboteur->spy_strength);

            $this->sabotage['units'] = $units;
            $this->sabotage['spy_units_sent_ratio'] = $spyStrengthCost;

            # Casualties
            $surviving_units = $units;
            $killedUnits = $this->sabotageCalculator->getUnitsKilled($saboteur, $target, $units);

            foreach($killedUnits as $slot => $amountKilled)
            {
                $surviving_units[$slot] -= $amountKilled;
            }

            $this->sabotage['killed_units'] = $killedUnits;
            $this->sabotage['returning_units'] = $surviving_units;

            # Remove units
            foreach($units as $slot => $amount)
            {
                if($slot == 'spies')
                {
                    $saboteur->military_spies -= $amount;
                }
                else
                {
                    $saboteur->{'military_unit' . $slot} -= $amount;
                }
            }

            # Queue returning units
            $ticks = 12;

            foreach($surviving_units as $slot => $amount)
            {
                if($slot == 'spies')
                {
                    $unitType = 'military_spies';
                }
                else
                {
                    $unitType = 'military_unit' . $slot;
                }

                $this->queueService->queueResources(
                    'sabotage',
                    $saboteur,
                    [$unitType => $amount],
                    $ticks
                );
            }

            $this->sabotageEvent = GameEvent::create([
                'round_id' => $saboteur->round_id,
                'source_type' => Dominion::class,
                'source_id' => $saboteur->id,
                'target_type' => Dominion::class,
                'target_id' => $target->id,
                'type' => 'sabotage',
                'data' => $this->sabotage,
                'tick' => $saboteur->round->ticks
            ]);

            $this->notificationService->queueNotification('sabotage', [
                '_routeParams' => [(string)$this->sabotageEvent->id],
                'saboteur_dominion_id' => $saboteur->id,
                'data' => $this->sabotage
            ]);

            # Debug before saving:
            if(request()->getHost() === 'odarena.local' or request()->getHost() === 'odarena.virtual')
            {
                dd($this->sabotage);
            }

            $target->save(['event' => HistoryService::EVENT_ACTION_SABOTAGE]);
            $saboteur->save(['event' => HistoryService::EVENT_ACTION_SABOTAGE]);

        });

        $this->notificationService->sendNotifications($target, 'irregular_dominion');

        $message = sprintf(
            'Your %s sabotage %s (#%s).',
            (isset($units['spies']) and array_sum($units) < $units['spies']) ? 'spies' : 'units',
            $target->name,
            $target->realm->number
        );

        $alertType = 'success';

        return [
            'message' => $message,
            'alert-type' => $alertType,
            'redirect' => route('dominion.event', [$this->sabotageEvent->id])
        ];
    }

    /**
     * Check if dominion has enough units at home to send out.
     *
     * @param Dominion $saboteur
     * @param array $units
     * @return bool
     */
    protected function hasEnoughUnitsAtHome(Dominion $saboteur, array $units): bool
    {
        foreach ($saboteur->race->units as $unit)
        {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0))
            {
                continue;
            }

            if ($units[$unit->slot] > $saboteur->{'military_unit' . $unit->slot})
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if an invasion passes the 4:3-rule.
     *
     * @param Dominion $saboteur
     * @param array $units
     * @return bool
     */
    protected function passes43RatioRule(Dominion $saboteur, Dominion $target, float $landRatio, array $units): bool
    {
        $unitsHome = [
            0 => $saboteur->military_draftees,
        ];

        foreach($saboteur->race->units as $unit)
        {
            $unitsHome[] = $saboteur->{'military_unit'.$unit->slot} - (isset($units[$unit->slot]) ? $units[$unit->slot] : 0);
        }

        $attackingForceOP = $this->militaryCalculator->getOffensivePower($saboteur, $target, $landRatio, $units);
        $newHomeForcesDP = $this->militaryCalculator->getDefensivePower($saboteur, null, null, $unitsHome, 0, false, false, null, true); # The "true" at the end excludes raw DP from annexed dominions

        $attackingForceMaxOP = (int)ceil($newHomeForcesDP * (4/3));

        return ($attackingForceOP <= $attackingForceMaxOP);
    }

    protected function passesUnitSendableCapacityCheck(Dominion $dominion, array $units): bool
    {
        if(!$dominion->race->getPerkValue('caverns_required_to_send_units'))
        {
            return true;
        }

        return (array_sum($units) <= $dominion->getBuildingPerkValue('unit_send_capacity'));
    }

}
