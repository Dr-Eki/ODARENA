<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

// ODA
use OpenDominion\Calculators\Dominion\SpellCalculator;

class ImproveActionService
{
    use DominionGuardsTrait;

    // ODA
    /** @var SpellCalculator */
    protected $spellCalculator;

    public function __construct(
        SpellCalculator $spellCalculator
    ) {
        $this->spellCalculator = $spellCalculator;
    }


    public function improve(Dominion $dominion, string $resource, array $data): array
    {
        $this->guardLockedDominion($dominion);

        $data = array_map('\intval', $data);

        $totalResourcesToInvest = array_sum($data);

        if ($totalResourcesToInvest < 0) {
            throw new GameException('Investment aborted due to bad input.');
        }

        if (!\in_array($resource, ['platinum', 'lumber', 'ore', 'gems','mana','food'], true)) {
            throw new GameException('Investment aborted due to bad resource type.');
        }

        if ($dominion->race->getPerkValue('cannot_improve_castle') == 1)
        {
            throw new GameException('Your faction is unable to use castle improvements.');
        }

        if ($totalResourcesToInvest > $dominion->{'resource_' . $resource}) {
            throw new GameException("You do not have enough {$resource} to invest.");
        }

        $worth = $this->getImprovementWorth();

        foreach ($data as $improvementType => $amount) {
            if ($amount === 0) {
                continue;
            }

            // Racial bonus multiplier
            $multiplier = (1 + $dominion->race->getPerkMultiplier('invest_bonus'));

            // Imperial Gnome: Spell (increase imp points by 10%)
            if ($this->spellCalculator->isSpellActive($dominion, 'spiral_architecture'))
            {
                $multiplier = 1.10;
            }

            $points = (($amount * $worth[$resource]) * $multiplier);

            $dominion->{'improvement_' . $improvementType} += $points;
        }

        $dominion->{'resource_' . $resource} -= $totalResourcesToInvest;
        $dominion->save(['event' => HistoryService::EVENT_ACTION_IMPROVE]);

        return [
            'message' => $this->getReturnMessageString($resource, $data, $totalResourcesToInvest),
            'data' => [
                'totalResourcesInvested' => $totalResourcesToInvest,
                'resourceInvested' => $resource,
            ],
        ];
    }

    /**
     * Returns the message for a improve action.
     *
     * @param string $resource
     * @param array $data
     * @param int $totalResourcesToInvest
     * @return string
     */
    protected function getReturnMessageString(string $resource, array $data, int $totalResourcesToInvest): string
    {
        $worth = $this->getImprovementWorth();

        $investmentStringParts = [];

        foreach ($data as $improvementType => $amount) {
            if ($amount === 0) {
                continue;
            }

            $points = ($amount * $worth[$resource]);
            $investmentStringParts[] = (number_format($points) . ' ' . $improvementType);
        }

        $investmentString = generate_sentence_from_array($investmentStringParts);

        return sprintf(
            'You invest %s %s into %s.',
            number_format($totalResourcesToInvest),
            ($resource === 'gems') ? str_plural('gem', $totalResourcesToInvest) : $resource,
            $investmentString
        );
    }

    /**
     * Returns the amount of points per resource type invested.
     *
     * @return array
     */
    public function getImprovementWorth(): array
    {
        return [
            'platinum' => 1,
            'lumber' => 2,
            'ore' => 2,
            'mana' => 5,
            'gems' => 12,
            'food' => 1,
        ];
    }
}
