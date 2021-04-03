<?php

namespace OpenDominion\Services\Dominion\Actions\Military;

use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

# ODA
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;

class ChangeDraftRateActionService
{
    use DominionGuardsTrait;

    /** @var SpellCalculator */
    protected $spellCalculator;

    public function __construct(
        SpellCalculator $spellCalculator
        )
    {
        $this->spellCalculator = $spellCalculator;
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

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot change your draft rate while you are in stasis.');
        }

        if (($draftRate < 0) || ($draftRate > 100)) {
            throw new RuntimeException('Draft rate not changed due to bad input.');
        }

        $dominion->draft_rate = $draftRate;
        $dominion->save(['event' => HistoryService::EVENT_ACTION_CHANGE_DRAFT_RATE]);

        return [
            'message' => sprintf('Draft rate changed to %d%%.', $draftRate),
            'data' => [
                'draftRate' => $draftRate,
            ],
        ];
    }
}
