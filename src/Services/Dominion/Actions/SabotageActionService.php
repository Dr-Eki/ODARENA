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
#use OpenDominion\Models\Unit;

use OpenDominion\Traits\DominionGuardsTrait;

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

    use DominionGuardsTrait;

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

            if(!$saboteur->round->getSetting('sabotage'))
            {
                throw new GameException('Sabotage is disabled this round.');
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

            if ($saboteur->realm->id === $target->realm->id and ($saboteur->round->mode == 'standard' or $saboteur->round->mode == 'standard-duration' or $saboteur->round->mode == 'artefacts'))
            {
                throw new GameException('You cannot sabotage from other dominions in the same realm as you in this round.');
            }

            if ($saboteur->realm->getAllies()->contains($target->realm))
            {
                throw new GameException('You cannot sabotage dominions in allied realms.');
            }

            if ($saboteur->id == $target->id)
            {
                throw new GameException('Nice try, but you sabotage yourself.');
            }

            if (!$this->passes43RatioRule($saboteur, $target, $landRatio, $units))
            {
                throw new GameException('You are sending out too much OP, based on your new home DP (4:3 rule).');
            }

            if (!$this->passesUnitSendableCapacityCheck($saboteur, $units))
            {
                throw new GameException('You do not have enough caverns to send out this many units.');
            }

            if (!$this->hasEnoughUnitsAtHome($saboteur, $units))
            {
                throw new GameException('You don not have enough units at home to send this many units.');
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

            // Spell: Rainy Season (cannot sabotage)
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
                throw new GameException('You do not have enough spy strength to send that many units. You have ' . $saboteur->spy_strength . ' and would need ' . $spyStrengthCost . ' to send that many units.');
            }

            # END VALIDATION

            $this->sabotage = [
                'spyop_key' => $spyop->key,
                'saboteur' => [
                        'fog' => $saboteur->getSpellPerkValue('fog_of_war') ? true : false,
                        'spy_strength_current' => $saboteur->spy_strength,
                        'spy_strength_spent' => $spyStrengthCost,
                        'spy_ratio' => $this->militaryCalculator->getSpyRatio($saboteur, 'offense'),
                        'sabotage_power_sent' => $this->militaryCalculator->getUnitsSabotagePower($saboteur, $units),
                        'units_sent' => $units
                    ],
                'target' => [
                        'crypt_bodies' => 0,
                        'fog' => $target->getSpellPerkValue('fog_of_war') ? true : false,
                        'reveal_ops' => $target->getSpellPerkValue('reveal_ops') ? true : false,
                        'spy_strength_current' => $target->spy_strength,
                        'spy_ratio' => $this->militaryCalculator->getSpyRatio($target, 'defense')
                    ],
                'damage' => $this->sabotageCalculator->getSabotageDamage($saboteur, $target, $spyop, $units, $spyStrengthCost),
            ];

            foreach($this->sabotage['damage'] as $type => $sabotageDamage)
            {

                # Handle buildings damage
                if($type == 'buildings')
                {
                    foreach($sabotageDamage['mod'] as $buildingKey => $damageRatio)
                    {
                        $building = Building::where('key', $buildingKey)->first();

                        $targetBuildingsOwned = $this->buildingCalculator->getBuildingAmountOwned($target, $building);
                
                        $damage = min($targetBuildingsOwned * $damageRatio, $targetBuildingsOwned);
                        $damage = (int)floor($damage);

                        $this->buildingCalculator->removeBuildings($target, [$buildingKey => ['builtBuildingsToDestroy' => $damage]]);
                        $this->queueService->queueResources('repair', $target, [('building_' . $buildingKey) => $damage], 6);
        
                        $this->statsService->updateStat($saboteur, 'sabotage_buildings_damage_dealt', $damage);
                        $this->statsService->updateStat($target, 'sabotage_buildings_damage_suffered', $damage);

                        $this->sabotage['damage_dealt'][$type] = [$buildingKey => $damage];
                    }
                }

                # Handle construction (unfinished buildings)
                if($type == 'construction')
                {

                    $damageRatio = $sabotageDamage['mod']['construction'];

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
                        foreach($constructionBuildings as $buildingKey => $construction)
                        {
                            $hours = key($construction);
                            $newHours = min(12, $hours + 2);

                            $amount = $construction[$hours];
                            $amountSabotaged = (int)floor($amount * $damageRatio);

                            $buildingResourceKey = 'building_' . $buildingKey;

                            $this->queueService->dequeueResourceForHour('construction', $target, $buildingResourceKey, $amountSabotaged, $hours);
                            $this->queueService->queueResources('construction', $target, [$buildingResourceKey => $amountSabotaged], $newHours);

                            isset($this->sabotage['damage_dealt'][$type][$buildingKey]) ? $this->sabotage['damage_dealt'][$type][$buildingKey] += $amountSabotaged : $this->sabotage['damage_dealt'][$type][$buildingKey] = $amountSabotaged;
                        }

                        $this->statsService->updateStat($saboteur, 'sabotage_construction_damage_dealt', array_sum($this->sabotage['damage_dealt'][$type]));
                        $this->statsService->updateStat($target, 'sabotage_construction_damage_suffered', array_sum($this->sabotage['damage_dealt'][$type]));
                    }

                    $this->queueService->setForTick(true); # ON

                }

                # Handle improvements damage
                if($type == 'improvements')
                {
                    foreach($sabotageDamage['mod'] as $improvementKey => $damageRatio)
                    {
                        $improvement = Improvement::where('key', $improvementKey)->first();

                        $targetImprovementPoints = $this->improvementCalculator->getDominionImprovementAmountInvested($target, $improvement);

                        $damage = min($targetImprovementPoints * $damageRatio, $targetImprovementPoints);
                        $damage = (int)floor($damage);
        
                        $this->improvementCalculator->decreaseImprovements($target, [$improvementKey => $damage]);
                        $this->queueService->queueResources('restore', $target, ['improvement_' . $improvementKey => $damage], 6);
        
                        $this->statsService->updateStat($saboteur, 'sabotage_improvements_damage_dealt', $damage);
                        $this->statsService->updateStat($target, 'sabotage_improvements_damage_suffered', $damage);

                        $this->sabotage['damage_dealt'][$type] = [$improvementKey => $damage];
                    }
                }

                # Handle resource damage
                if($type == 'resources')
                {
                    foreach($sabotageDamage['mod'] as $resourceKey => $damageRatio)
                    {
                        $resource = Resource::where('key', $resourceKey)->first();

                        $targetResourceAmount = $this->resourceCalculator->getAmount($target, $resourceKey);

                        $damage = min($targetResourceAmount * $damageRatio, $targetResourceAmount);
                        $damage = (int)floor($damage);
        
                        $this->resourceService->updateResources($target, [$resourceKey => $damage*-1]);
                        #$this->queueService->queueResources('restore', $target, [$resourceKey => $damage], 6);
        
                        $this->statsService->updateStat($saboteur, 'sabotage_resources_damage_dealt', $damage);
                        $this->statsService->updateStat($target, 'sabotage_resources_damage_suffered', $damage);

                        $this->sabotage['damage_dealt'][$type] = [$resourceKey => $damage];
                    }
                }

                # Handle peasants, draftees, morale, spy strength, wizard strength
                if($type == 'peasants' || $type == 'military_draftees' || $type == 'morale' || $type == 'spy_strength' || $type == 'wizard_strength')
                {
                    foreach($sabotageDamage['mod'] as $resourceKey => $damageRatio)
                    {
                        $damage = min($target->{$type} * $damageRatio, $target->{$type});
                        $damage = (int)floor($damage);

                        #dump("Damage ratio of $damageRatio for $type yields $damage damage against " . $target->{$type} . " $type");
            
                        $target->{$type} -= $damage;

                        $this->statsService->updateStat($saboteur, 'sabotage_' . $type . '_damage_dealt', $damage);
                        $this->statsService->updateStat($target, 'sabotage_' . $type . '_damage_suffered', $damage);

                        $this->sabotage['damage_dealt'][$type] = [$type => $damage];
                    }
                }

                # Handle convert_peasants_to_vampires_unit1
                if($type == 'convert_peasants_to_vampires_unit1')
                {
                    foreach($sabotageDamage['mod'] as $resourceKey => $damageRatio)
                    {
                        # Damage here is the multiplier by which to multiply the amount of killed peasants to get the number of new Servants
                        $assassinatedPeasants = $this->sabotage['damage_dealt']['peasants']['peasants'];

                        $damage = min($assassinatedPeasants * $damageRatio, $assassinatedPeasants);
                        $damage = (int)floor($damage);

                        $this->queueService->queueResources('sabotage', $saboteur, ['military_unit1' => $damage], 12);

                        #dump($damage . ' Servants created from ' . $assassinatedPeasants . ' peasants killed, with a damage ratio of ' . $damageRatio . '');
            
                        $this->statsService->updateStat($saboteur, 'sabotage_units_converted', $damage);

                        $this->sabotage['damage_dealt'][$type] = ['convert_peasants_to_vampires_unit1' => $damage];
                    }
                }

            }

            # Calculate spy units
            $saboteur->spy_strength -= min($spyStrengthCost, $saboteur->spy_strength);

            $this->sabotage['units'] = $units;
            $this->sabotage['spy_units_sent_ratio'] = $spyStrengthCost;

            # Casualties
            $units_surviving = $units;
            $killedUnits = $this->sabotageCalculator->getUnitsKilled($saboteur, $target, $units);

            foreach($killedUnits as $slot => $amountKilled)
            {
                $units_surviving[$slot] -= $amountKilled;
            }

            $this->sabotage['killed_units'] = $killedUnits;
            $this->sabotage['returning_units'] = $units_surviving;

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

            foreach($units_surviving as $slot => $amount)
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

    protected function passesUnitSendableCapacityCheck(Dominion $attacker, array $units): bool
    {
        if(!$attacker->race->getPerkValue('caverns_required_to_send_units'))
        {
            return true;
        }

        $maxSendableUnits = $this->militaryCalculator->getMaxSendableUnits($attacker);

        return (array_sum($units) <= $maxSendableUnits);
    }

}
