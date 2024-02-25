<?php

namespace OpenDominion\Services\Dominion\Actions\Military;

use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

use OpenDominion\Exceptions\GameException;

class ChangeDraftRateActionService
{
    use DominionGuardsTrait;


    public function __construct()
    {
    }

    /**
     * Does a military change draft rate action for a Dominion.
     *
     * @param Dominion $dominion
     * @param int $draftRate
     * @return array
     * @throws RuntimeException
     */
    public function changeDraftRate(Dominion $dominion, int $draftRate): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot change your draft rate while you are in stasis.');
        }

        if($dominion->race->getPerkValue('no_drafting'))
        {
            throw new GameException($dominion->race->name . ' does not use draftees and cannot change draft rate.');
        }

        if($dominion->race->getPerkValue('cannot_change_draft_rate'))
        {
            throw new GameException($dominion->race->name . ' cannot change draft rate.');
        }

        if (($draftRate < 0) || ($draftRate > 100)) {
            throw new RuntimeException('Draft rate not changed due to bad input.');
        }

        $dominion->draft_rate = (int)floor($draftRate);
        $dominion->save(['event' => HistoryService::EVENT_ACTION_CHANGE_DRAFT_RATE]);

        return [
            'message' => sprintf('Draft rate changed to %d%%.', $draftRate),
            'data' => [
                'draftRate' => $draftRate,
            ],
        ];
    }
}
