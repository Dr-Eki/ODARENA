<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Illuminate\Support\Str;
use OpenDominion\Exceptions\GameException;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Resource;

use OpenDominion\Traits\DominionGuardsTrait;

use OpenDominion\Helpers\TheftHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\TheftCalculator;
use OpenDominion\Calculators\Dominion\UnitCalculator;

use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;

class TheftActionService
{
    use DominionGuardsTrait;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /** @var ResourceCalculator */
    protected $resourceCalculator;

    /** @var TheftCalculator */
    protected $theftCalculator;

    /** @var UnitCalculator */
    protected $unitCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var QueueService */
    protected $queueService;

    /** @var ResourceService */
    protected $resourceService;

    /** @var StatsService */
    protected $statsService;

    /** @var TheftHelper */
    protected $theftHelper;

    /** @var UnitHelper */
    protected $unitHelper;

    public function __construct()
    {
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->theftCalculator = app(TheftCalculator::class);
        $this->unitCalculator = app(UnitCalculator::class);

        $this->notificationService = app(NotificationService::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
        $this->resourceService = app(ResourceService::class);
        $this->statsService = app(StatsService::class);

        $this->theftHelper = app(TheftHelper::class);
        $this->unitHelper = app(UnitHelper::class);
    }

    public function steal(Dominion $thief, Dominion $target, Resource $resource, array $units): array
    {

        DB::transaction(function () use ($thief, $target, $resource, $units)
        {
            // Sanitize input
            $units = array_map('intval', array_filter($units));
            $landRatio = $this->rangeCalculator->getDominionRange($thief, $target) / 100;

            // Checks
            if (array_sum($units) <= 0)
            {
                throw new GameException('You need to send at least some units.');
            }

            if(!$thief->round->getSetting('theft'))
            {
                throw new GameException('theft is disabled this round.');
            }

            if ($this->protectionService->isUnderProtection($thief))
            {
                throw new GameException('You cannot steal while under protection.');
            }

            if ($this->protectionService->isUnderProtection($thief))
            {
                throw new GameException('You cannot steal from dominions which are under protection.');
            }

            if (!$this->rangeCalculator->isInRange($thief, $target))
            {
                throw new GameException('You cannot steal from dominions outside of your range.');
            }

            if ($thief->round->id !== $target->round->id)
            {
                throw new GameException('Nice try, but you cannot steal cross-round.');
            }

            if ($thief->realm->id === $target->realm->id and ($thief->round->mode == 'standard' or $thief->round->mode == 'standard-duration'))
            {
                throw new GameException('You cannot steal from other dominions in the same realm as you in standard rounds.');
            }

            if ($thief->realm->getAllies()->contains($target->realm))
            {
                throw new GameException('You cannot steal from dominions in allied realms.');
            }

            if ($thief->id == $target->id)
            {
                throw new GameException('Nice try, but you steal from invade yourself.');
            }

            if(!$this->theftHelper->canDominionStealResource($thief, $resource))
            {
                throw new GameException('You cannot steal ' . $resource->name . '.');
            }

            if (!in_array($resource->key, $thief->race->resources))
            {
                throw new GameException($resource->name . ' is not used by ' . $thief->race->name . ', so you cannot steal it.');
            }

            if (!in_array($resource->key, $target->race->resources))
            {
                throw new GameException($target->race->name . ' does not use ' . $resource->name . '.');
            }

            if (!$this->passes43RatioRule($thief, $target, $landRatio, $units))
            {
                throw new GameException('You are sending out too much OP, based on your new home DP (4:3 rule).');
            }

            if (!$this->passesUnitSendableCapacityCheck($thief, $units))
            {
                throw new GameException('You do not have enough caverns to send out this many units.');
            }

            if (!$this->hasEnoughUnitsAtHome($thief, $units))
            {
                throw new GameException('You don\'t have enough units at home to send this many units.');
            }

            if($thief->race->getPerkValue('no_' . $resource->key .'_theft'))
            {
                throw new GameException($thief->race->name . ' cannot steal ' . $resource->name . '.');
            }

            if($target->race->getPerkValue('no_' . $resource->key .'_theft'))
            {
                throw new GameException('Cannot steal ' . $resource->name . ' from ' . $target->race->name . '.');
            }

            foreach($units as $slot => $amount)
            {
                $unit = $thief->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                if($amount < 0)
                {
                    throw new GameException('Theft was canceled due to bad input.');
                }

                if($slot !== 'spies')
                {
                    if(!$this->unitHelper->isUnitOffensiveSpy($unit))
                    {
                        throw new GameException($unit->name . ' is not a spy unit and cannot be sent on theft missions.');
                    }

                    # OK, unit can be trained. Let's check for pairing limits.
                    if($this->unitCalculator->unitHasCapacityLimit($thief, $slot) and !$this->unitCalculator->checkUnitLimitForInvasion($thief, $slot, $amount))
                    {
                        throw new GameException('You can at most control ' . number_format($this->unitCalculator->getUnitMaxCapacity($thief, $slot)) . ' ' . Str::unitPlural($unit->name) . '. To control more, you need to first have more of their superior unit.');
                    }

                    if(!$this->unitCalculator->isUnitSendableByDominion($unit, $thief))
                    {
                        throw new GameException('You cannot send ' . $unit->name . ' on invasion.');
                    }
                }
             }

            if ($thief->race->getPerkValue('cannot_steal'))
            {
                throw new GameException($thief->race->name . ' cannot steal.');
            }

            // Spell: Rainy Season (cannot invade)
            if ($thief->getSpellPerkValue('cannot_steal'))
            {
                throw new GameException('A spell is preventing from you steal.');
            }

            // Cannot invade until round has started.
            if(!$thief->round->hasStarted())
            {
                throw new GameException('You cannot steal until the round has started.');
            }

            // Cannot invade after round has ended.
            if($thief->round->hasEnded())
            {
                throw new GameException('You cannot steal after the round has ended.');
            }

            // Qur: Statis cannot be invaded.
            if($target->getSpellPerkValue('stasis'))
            {
                throw new GameException('A magical stasis surrounds the Qurrian lands, making it impossible for your spies to steal.');
            }

            // Qur: Statis cannot invade.
            if($thief->getSpellPerkValue('stasis'))
            {
                throw new GameException('You cannot steal while you are in stasis.');
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
            if($thief->getSpellPerkValue('cannot_invade'))
            {
                if($thief->race->name == 'Firewalker')
                {
                    throw new GameException('Your caverns are flooded, making it impossible for your units to attack.');
                }
                else
                {
                    throw new GameException('A magical state surrounds the lands, making it impossible for you to invade.');
                }
            }

            // Check that thief has enough SS
            if($thief->spy_strength <= 0)
            {
                throw new GameException('You do not have enough spy strength to steal.');
            }

            $spyStrengthCost = $this->theftCalculator->getSpyStrengthCost($thief, $units);

            if($spyStrengthCost > $thief->spy_strength)
            {
                throw new GameException('You do not have enough spy strength to send that many units. You have ' . $thief->spy_strength . '% and would need ' . ($this->theftCalculator->getSpyStrengthCost($thief, $units)) . '% to send that many units.');
            }

            # CHECKS COMPLETE

            # Calculate spy units
            $thief->spy_strength -= min($spyStrengthCost, $thief->spy_strength);

            $this->theft['units'] = $units;
            $this->theft['spy_units_sent_ratio'] = $spyStrengthCost;
            $this->theft['resource']['key'] = $resource->key;
            $this->theft['resource']['name'] = $resource->name;

            # Casualties
            $units_surviving = $units;
            $killedUnits = $this->theftCalculator->getUnitsKilled($thief, $target, $units);

            foreach($killedUnits as $slot => $amountKilled)
            {
                $units_surviving[$slot] -= $amountKilled;
            }

            $this->theft['killed_units'] = $killedUnits;
            $this->theft['returning_units'] = $units_surviving;

            # Determine how much was stolen
            $this->theft['amount_owned'] = $this->resourceCalculator->getAmount($target, $resource->key);
            $amountStolen = $this->theftCalculator->getTheftAmount($thief, $target, $resource, $units_surviving);
            $this->theft['amount_stolen'] = $amountStolen;

            # Remove from target
            $this->resourceService->updateResources($target, [$resource->key => $amountStolen*-1]);

            # Queue returning resources
            $ticks = 6;

            $resourceQueueKey = 'resource_' . $resource->key;

            $this->queueService->queueResources(
                'theft',
                $thief,
                [$resourceQueueKey => $amountStolen],
                $ticks
            );

            # Remove units
            foreach($units as $slot => $amount)
            {
                if($slot == 'spies')
                {
                    $thief->military_spies -= $amount;
                }
                else
                {
                    $thief->{'military_unit' . $slot} -= $amount;
                }
            }

            # Queue returning units
            $ticks = 6;

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
                    'theft',
                    $thief,
                    [$unitType => $amount],
                    $ticks
                );
            }

            $this->theftEvent = GameEvent::create([
                'round_id' => $thief->round_id,
                'source_type' => Dominion::class,
                'source_id' => $thief->id,
                'target_type' => Dominion::class,
                'target_id' => $target->id,
                'type' => 'theft',
                'data' => $this->theft,
                'tick' => $thief->round->ticks
            ]);

            $this->notificationService->queueNotification('theft', [
                '_routeParams' => [(string)$this->theftEvent->id],
                'thiefDominionId' => $thief->id,
                'unitsKilled' => $this->theft['killed_units'],
                'resource' => $resource->id,
                'amountLost' => $this->theft['amount_stolen']
            ]);

            $this->statsService->updateStat($thief, ($resource->key .  '_stolen'), $amountStolen);
            $this->statsService->updateStat($target, ($resource->key . '_lost'), $amountStolen);

            $thief->most_recent_theft_resource = $resource->key;

            # Debug before saving:
            ldd($this->theft);

            $target->save(['event' => HistoryService::EVENT_ACTION_THEFT]);
            $thief->save(['event' => HistoryService::EVENT_ACTION_THEFT]);

        });

        $this->notificationService->sendNotifications($target, 'irregular_dominion');

        $thief->most_recent_theft_resource = $resource->key;

        $message = sprintf(
            'Your %s infiltrate %s (#%s), stealing %s %s.',
            (isset($units['spies']) and array_sum($units) < $units['spies']) ? 'spies' : 'units',
            $target->name,
            $target->realm->number,
            number_format($this->theft['amount_stolen']),
            $this->theft['resource']['name']
        );

        $alertType = 'success';

        return [
            'message' => $message,
            'alert-type' => $alertType,
            'redirect' => route('dominion.event', [$this->theftEvent->id])
        ];
    }

    /**
     * Check if dominion has enough units at home to send out.
     *
     * @param Dominion $thief
     * @param array $units
     * @return bool
     */
    protected function hasEnoughUnitsAtHome(Dominion $thief, array $units): bool
    {
        foreach ($thief->race->units as $unit)
        {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0))
            {
                continue;
            }

            if ($units[$unit->slot] > $thief->{'military_unit' . $unit->slot})
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if an invasion passes the 4:3-rule.
     *
     * @param Dominion $thief
     * @param array $units
     * @return bool
     */
    protected function passes43RatioRule(Dominion $thief, Dominion $target, float $landRatio, array $units): bool
    {
        $unitsHome = [
            0 => $thief->military_draftees,
        ];

        foreach($thief->race->units as $unit)
        {
            $unitsHome[] = $thief->{'military_unit'.$unit->slot} - (isset($units[$unit->slot]) ? $units[$unit->slot] : 0);
        }

        $attackingForceOP = $this->militaryCalculator->getOffensivePower($thief, $target, $landRatio, $units);
        $newHomeForcesDP = $this->militaryCalculator->getDefensivePower($thief, null, null, $unitsHome, 0, false, false, null, true); # The "true" at the end excludes raw DP from annexed dominions

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
     * @param Dominion $thief
     * @param int $slot
     * @return int
     */
    protected function getUnitReturnHoursForSlot(Dominion $thief, int $slot): int
    {
        $ticks = 12;

        $unit = $thief->race->units->filter(function ($unit) use ($slot) {
            return ($unit->slot === $slot);
        })->first();

        if ($unit->getPerkValue('faster_return'))
        {
            $ticks -= (int)$unit->getPerkValue('faster_return');
        }

        return $ticks;
    }

    protected function getUnitReturnTicksForSlot(Dominion $thief, int $slot): int
    {
        $ticks = 12;

        $unit = $thief->race->units->filter(function ($unit) use ($slot) {
            return ($unit->slot === $slot);
        })->first();

        $ticks -= (int)$unit->getPerkValue('faster_return');
        $ticks -= (int)$thief->getSpellPerkValue('faster_return');
        $ticks -= (int)$thief->getAdvancementPerkValue('faster_return');
        $ticks -= (int)$thief->realm->getArtefactPerkValue('faster_return');

        return min(max(1, $ticks), 12);
    }

    /**
     * Gets the amount of hours for the slowest unit from an array of units
     * takes to return home.
     *
     * Primarily used to bring prestige home earlier if you send only 9hr
     * attackers. (Land always takes 12 hrs)
     *
     * @param Dominion $thief
     * @param array $units
     * @return int
     */
    protected function getSlowestUnitReturnHours(Dominion $thief, array $units): int
    {
        $hours = 12;

        foreach ($units as $slot => $amount) {
            if ($amount === 0) {
                continue;
            }

            $hoursForUnit = $this->getUnitReturnHoursForSlot($thief, $slot);

            if ($hoursForUnit < $hours) {
                $hours = $hoursForUnit;
            }
        }

        return $hours;
    }
}
