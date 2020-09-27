<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;


use OpenDominion\Calculators\Dominion\SpellCalculator;

class RezoneActionService
{
    use DominionGuardsTrait;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var RezoningCalculator */
    protected $rezoningCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /**
     * RezoneActionService constructor.
     *
     * @param LandCalculator $landCalculator
     * @param RezoningCalculator $rezoningCalculator
     */
    public function __construct(
        LandCalculator $landCalculator,
        RezoningCalculator $rezoningCalculator,
        SpellCalculator $spellCalculator
        )
    {
        $this->landCalculator = $landCalculator;
        $this->rezoningCalculator = $rezoningCalculator;
        $this->spellCalculator = $spellCalculator;
    }

    /**
     * Does a rezone action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $remove Land to remove
     * @param array $add Land to add.
     * @return array
     * @throws GameException
     */
    public function rezone(Dominion $dominion, array $remove, array $add): array
    {
        $this->guardLockedDominion($dominion);

        // Qur: Statis
        if($this->spellCalculator->isSpellActive($dominion, 'stasis'))
        {
            throw new GameException('You are in stasis and cannot rezone land.');
        }

        if ($dominion->race->getPerkValue('cannot_rezone'))
        {
            throw new GameException('Your faction is unable to rezone land.');
        }

        // Level out rezoning going to the same type.
        foreach (array_intersect_key($remove, $add) as $key => $value) {
            $sub = min($value, $add[$key]);
            $remove[$key] -= $sub;
            $add[$key] -= $sub;
        }

        // Filter out empties.
        $remove = array_filter($remove);
        $add = array_filter($add);

        $totalLand = array_sum($remove);

        if (($totalLand <= 0) || $totalLand !== array_sum($add)) {
            throw new GameException('Re-zoning was not completed due to bad input.');
        }

        // Check if the requested amount of land is barren.
        foreach ($remove as $landType => $landToRemove) {

            if($landToRemove < 0) {
                throw new GameException('Re-zoning was not completed due to bad input.');
            }

            $landAvailable = $this->landCalculator->getTotalBarrenLandByLandType($dominion, $landType);
            if ($landToRemove > $landAvailable) {
                throw new GameException('You do not have enough barren land to re-zone ' . $landToRemove . ' ' . str_plural($landType, $landAvailable));
            }
        }

        $cost = $totalLand * $this->rezoningCalculator->getRezoningCost($dominion);
        $resource = $this->rezoningCalculator->getRezoningMaterial($dominion);

        if($cost > $dominion->{'resource_'.$resource})
        {
            throw new GameException("You do not have enough $resource to re-zone {$totalLand} acres of land.");
        }

        # All fine, perform changes.
        $dominion->{'resource_'.$resource} -= $cost;

        # Update spending statistics.
        $dominion->{'stat_total_'.$resource.'_spent_rezoning'}  += $cost;

        foreach ($remove as $landType => $amount) {
            $dominion->{'land_' . $landType} -= $amount;
        }
        foreach ($add as $landType => $amount) {
            $dominion->{'land_' . $landType} += $amount;
        }

        $dominion->save(['event' => HistoryService::EVENT_ACTION_REZONE]);

        return [
            'message' => sprintf(
                'Your land has been re-zoned at a cost of %1$s %2$s.',
                number_format($cost),
                $resource
            ),
            'data' => [
                'cost' => $cost,
                'resource' => $resource,
            ]
        ];
    }
}
