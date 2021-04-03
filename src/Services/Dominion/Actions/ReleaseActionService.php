<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

# ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Services\NotificationService;
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
        MilitaryCalculator $militaryCalculator,
        SpellCalculator $spellCalculator,
        NotificationService $notificationService,
        RaceHelper $raceHelper
      )
    {
        $this->unitHelper = $unitHelper;
        $this->queueService = $queueService;
        $this->militaryCalculator = $militaryCalculator;
        $this->spellCalculator = $spellCalculator;
        $this->notificationService = $notificationService;
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

        $data = array_map('\intval', $data);

        /*

        array(8) { ["draftees"]=> int(1) ["unit1"]=> int(0) ["unit2"]=> int(0) ["unit3"]=> int(0) ["unit4"]=> int(0) ["spies"]=> int(0) ["wizards"]=> int(0) ["archmages"]=> int(0) }

        */

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot release units while you are in stasis.');
        }

        $troopsReleased = [];

        $totalTroopsToRelease = array_sum($data);

        $totalDrafteesToRelease = $data['draftees'];
        $totalSpiesToRelease = $data['spies'];
        $totalWizardsToRelease = $data['wizards'];
        $totalArchmagesToRelease = $data['archmages'];
        $totalMilitaryUnitsToRelease = $data['unit1'] + $data['unit2'] + $data['unit3'] + $data['unit4'];

        # Must be releasing something.
        if ($totalTroopsToRelease <= 0)
        {
            throw new GameException('Military release aborted due to bad input.');
        }

        $units = [
          1 => $data['unit1'],
          2 => $data['unit2'],
          3 => $data['unit3'],
          4 => $data['unit4']
        ];

        $rawDpRelease = $this->militaryCalculator->getDefensivePowerRaw($dominion, null, null, $units, 0, true, false, true);


        # Special considerations for releasing military units.
        if($rawDpRelease > 0)
        {
            # Must have at least 1% morale to release.
            if ($dominion->morale < 50)
            {
                throw new GameException('You must have at least 50% morale to release units with defensive power.');
            }

            # Cannot release if recently invaded.
            if ($this->militaryCalculator->getRecentlyInvadedCount($dominion, 3))
            {
                throw new GameException('You cannot release military units with defensive power if you have been invaded in the last three hours.');
            }

            # Cannot release if units returning from invasion.
            $totalUnitsReturning = 0;
            for ($slot = 1; $slot <= 4; $slot++)
            {
              $totalUnitsReturning += $this->queueService->getInvasionQueueTotalByResource($dominion, "military_unit{$slot}");
            }
            if ($totalUnitsReturning !== 0)
            {
                throw new GameException('You cannot release military units with defensive power when you have units returning from battle.');
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

            if ($unitType === 'draftees')
            {
                $dominion->peasants += $amount;
            }

            # Only return draftees if unit is not exempt from population.
            elseif (!$dominion->race->getUnitPerkValueForUnitSlot($slot, 'does_not_count_as_population') and !$dominion->race->getUnitPerkValueForUnitSlot($slot, 'no_draftee'))
            {
                $dominion->military_draftees += $amount;
            }

            $troopsReleased[$unitType] = $amount;
        }

        // Cult: Enthralling
        if ($this->spellCalculator->isSpellActive($dominion, 'enthralling'))
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
        $stringParts = ['You successfully released'];

        // Draftees into peasants
        if (isset($troopsReleased['draftees']))
        {
            $amount = $troopsReleased['draftees'];
            if($troopsReleased['draftees'] > 0)
            {
                $stringParts[] = sprintf('%s %s into %s',
                    number_format($amount),
                    str_plural($this->raceHelper->getDrafteesTerm($dominion->race), $amount),
                    str_plural($this->raceHelper->getPeasantsTerm($dominion->race), $amount));
            }
        }

        // Troops into draftees
        $troopsParts = [];
        foreach ($troopsReleased as $unitType => $amount)
        {
            if ($unitType === 'draftees') {
                continue;
            }

            $unitName = str_singular(strtolower($this->unitHelper->getUnitName($unitType, $dominion->race)));
            $troopsParts[] = (number_format($amount) . ' ' . ucwords(str_plural($unitName, $amount)));
        }

        if (!empty($troopsParts)) {
            if (\count($stringParts) === 2) {
                $stringParts[] = 'and';
            }

            $stringParts[] = generate_sentence_from_array($troopsParts);
            $stringParts[] = 'into ' . str_plural($this->raceHelper->getDrafteesTerm($dominion->race), $amount);
        }

        return (implode(' ', $stringParts) . '.');
    }
}
