<?php

namespace OpenDominion\Services\Dominion\Actions;

use Illuminate\Support\Str;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

# ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\NotificationService;

use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\RaceHelper;

class ReleaseActionService
{
    use DominionGuardsTrait;

    /** @var UnitHelper */
    protected $unitHelper;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var RaceHelper */
    protected $raceHelper;


    /**
     * ReleaseActionService constructor.
     *
     * @param UnitHelper $unitHelper
     */
    public function __construct(
        UnitHelper $unitHelper,
        QueueService $queueService,
        ResourceService $resourceService,
        MilitaryCalculator $militaryCalculator,
        SpellCalculator $spellCalculator,
        NotificationService $notificationService,
        RaceHelper $raceHelper
      )
    {
        $this->unitHelper = $unitHelper;
        $this->queueService = $queueService;
        $this->resourceService = $resourceService;
        $this->militaryCalculator = $militaryCalculator;
        $this->notificationService = $notificationService;
        $this->spellCalculator = $spellCalculator;
        $this->raceHelper = $raceHelper;
    }

    /**
     * Does a release troops action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws GameException
     */
    public function release(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        $data = array_map('\intval', $data);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot release units while you are in stasis.');
        }

        if($dominion->race->getPerkValue('cannot_release_units'))
        {
            throw new GameException($dominion->race->name . ' cannot release units.');
        }

        $troopsReleased = [];

        $totalTroopsToRelease = array_sum($data);

        # Must be releasing something.
        if ($totalTroopsToRelease <= 0)
        {
            throw new GameException('Military release aborted due to bad input.');
        }

        $units = [];
        foreach($dominion->race->units as $unit)
        {
            $units[$unit->slot] = $data['unit' . $unit->slot] ?? 0;
        }

        $rawDpRelease = $this->militaryCalculator->getDefensivePowerRaw($dominion, null, null, $units, 0, false, true, null, true, true);

        # Special considerations for releasing military units.
        if($rawDpRelease > 0 and (isset($data['draftees']) and array_sum($data) > $data['draftees']))
        {
            # Must have at least 1% morale to release.
            if ($dominion->morale < 50)
            {
                throw new GameException('You must have at least 50% morale to release units with defensive power.');
            }

            # Cannot release if recently invaded.
            if ($this->militaryCalculator->getRecentlyInvadedCount($dominion, 6))
            {
                throw new GameException('You cannot release military units with defensive power if you have been invaded in the last six ticks.');
            }

            # Cannot release if units returning from invasion.
            $totalUnitsReturning = 0;
            for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
            {
                $totalUnitsReturning += $this->queueService->getInvasionQueueTotalByResource($dominion, "military_unit{$slot}");
                $totalUnitsReturning += $this->queueService->getDesecrationQueueTotalByResource($dominion, "military_unit{$slot}");
                $totalUnitsReturning += $this->queueService->getArtefactattackQueueTotalByResource($dominion, "military_unit{$slot}");
            }
            if ($totalUnitsReturning !== 0)
            {
                throw new GameException('You cannot release military units with defensive power when you have units returning.');
            }

        }
        foreach ($data as $unitType => $amount) {
            if ($amount === 0) { // todo: collect()->except(amount == 0)
                continue;
            }

            if ($amount < 0) {
                throw new GameException('Military release aborted due to bad input.');
            }

            if ($amount > $dominion->{'military_' . $unitType}) {
                throw new GameException('Military release was not completed due to bad input.');
            }
        }

        foreach ($data as $unitType => $amount)
        {
            if ($amount === 0)
            {
                continue;
            }

            $slot = intval(str_replace('unit','',$unitType));

            if ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'cannot_be_released'))
            {
                throw new GameException('Cannot release that unit.');
            }

            $dominion->{'military_' . $unitType} -= $amount;

            $drafteesAmount = $amount;

            # Check for housing_count
            if($nonStandardHousing = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'housing_count'))
            {
                $amount = floor($amount * $nonStandardHousing);
            }

            $unit = $dominion->race->units->firstWhere('slot', $slot);

            if ($unitType === 'draftees')
            {
                $dominion->peasants += $amount;
            }
            # Only return draftees if unit is not exempt from population.
            elseif (
                        !$dominion->race->getUnitPerkValueForUnitSlot($slot, 'does_not_count_as_population') and 
                        !$dominion->race->getUnitPerkValueForUnitSlot($slot, 'no_draftee') and 
                        !$dominion->race->getUnitPerkValueForUnitSlot($slot, 'no_draftee_on_release') and 
                        !$dominion->race->getPerkValue('no_drafting') and
                        (isset($unit->cost['draftees']) and $unit->cost['draftees'] > 0)
                    )
            {
                $dominion->military_draftees += $amount;
            }

            # Return peasant if the unit cost peasant
            if(isset($unit->cost['peasant']) and $unit->cost['peasant'] > 0)
            {
                $dominion->peasants += $amount;
            }

            if ($releasesIntoResourcePerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'releases_into_resource'))
            {
                $amount = ceil($releasesIntoResourcePerk[0] * $amount);
                $resourceKey = $releasesIntoResourcePerk[1];
                $this->resourceService->updateResources($dominion, [$resourceKey => $amount]);
            }

            if ($releasesIntoResourcesPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'releases_into_resources'))
            {
                foreach($releasesIntoResourcesPerk as $releasesIntoResourcesPerk)
                {
                    $amount = ceil($releasesIntoResourcePerk[0] * $amount);
                    $resourceKey = $releasesIntoResourcePerk[1];
                    $this->resourceService->updateResources($dominion, [$resourceKey => $amount]);
                }
            }

            $troopsReleased[$unitType] = $amount;
        }

        // Cult: Enthralling
        if ($dominion->getSpellPerkValue('enthralling'))
        {
            $cult = $this->spellCalculator->getCaster($dominion, 'enthralling');

            # Calculate how many are enthralled.
            # Cap at max 1 per 100 Mystic.
            $enthralled = min($totalTroopsToRelease, $cult->military_unit4/100);

            $enthralled = intval($enthralled);

            $ticks = rand(6,12);

            #$this->queueService->queueResources('training', $dominion, $data, $hours);
            $this->queueService->queueResources('training', $cult, ['military_unit1' => $enthralled], $ticks);
            $this->notificationService->queueNotification('enthralling_occurred',['sourceDominionId' => $dominion->id, 'enthralled' => $enthralled]);
            $this->notificationService->sendNotifications($cult, 'irregular_dominion');

        }

        $dominion->save(['event' => HistoryService::EVENT_ACTION_RELEASE]);

        return [
            'message' => $this->getReturnMessageString($dominion, $troopsReleased),
            'data' => [
                'totalTroopsReleased' => $totalTroopsToRelease,
            ],
        ];
    }

    /**
     * Returns the message for a release action.
     *
     * @param Dominion $dominion
     * @param array $troopsReleased
     * @return string
     */
    protected function getReturnMessageString(Dominion $dominion, array $troopsReleased): string
    {

        $unitStrings = [];

        foreach($troopsReleased as $unitType => $amount)
        {

            if($unitType == 'draftees')
            {
                $releasedInto = Str::plural($this->raceHelper->getPeasantsTerm($dominion->race), $amount);
                $releasedUnitName = Str::plural($this->raceHelper->getDrafteesTerm($dominion->race), $amount);

                $unitStrings[] = sprintf('%s %s into %s', number_format($amount), $releasedUnitName, $releasedInto);
                
            }
            else
            {
                $unit = $dominion->race->units->firstWhere('slot', intval(str_replace('unit','',$unitType)));

                if(isset($unit->cost['draftees']) and $unit->cost['draftees'] > 0)
                {
                    $releasedInto = Str::plural($this->raceHelper->getDrafteesTerm($dominion->race), $amount);
                    $releasedUnitName = $unit->name;
                }
                elseif(isset($unit->cost['peasant']) and $unit->cost['peasant'] > 0)
                {
                    $releasedInto = Str::plural($this->raceHelper->getPeasantsTerm($dominion->race), $amount);
                    $releasedUnitName = $unit->name;
                }
                else
                {
                    $releasedInto = null;
                    $releasedUnitName = $unit->name;
                }

                if($releasedInto)
                {
                    $unitStrings[] = sprintf('%s %s into %s',
                        number_format($amount),
                        Str::plural($unit->name, $amount),
                        Str::plural($releasedInto, $amount));
                }
                else
                {
                    $unitStrings[] = sprintf('%s %s',
                        number_format($amount),
                        Str::plural($unit->name, $amount));
                }
            }

        }

        return 'You released ' . generate_sentence_from_array($unitStrings) . '.';

    }
}
