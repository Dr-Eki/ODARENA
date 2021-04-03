<?php

namespace OpenDominion\Services\Dominion\Actions;

use LogicException;
use OpenDominion\Calculators\Dominion\Actions\BankingCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

use OpenDominion\Calculators\Dominion\SpellCalculator;

class BankActionService
{
    use DominionGuardsTrait;

    /** @var BankingCalculator */
    protected $bankingCalculator;

    /**
     * BankActionService constructor.
     *
     * @param BankingCalculator $bankingCalculator
     */
    public function __construct(
        BankingCalculator $bankingCalculator,
        SpellCalculator $spellCalculator
        )
    {
        $this->bankingCalculator = $bankingCalculator;
        $this->spellCalculator = $spellCalculator;
    }

    /**
     * Does a bank action for a Dominion.
     *
     * @param Dominion $dominion
     * @param string $source
     * @param string $target
     * @param int $amount
     * @return array
     * @throws LogicException
     * @throws GameException
     */
    public function exchange(Dominion $dominion, string $source, string $target, int $amount): array
    {
        $this->guardLockedDominion($dominion);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot exchange resources while you are in stasis.');
        }

        if($amount < 0)
        {
            throw new LogicException('Amount less than 0.');
        }

        // Get the resource information.
        $resources = $this->bankingCalculator->getResources($dominion);
        if (empty($resources[$source])) {
            throw new LogicException('Failed to find resource ' . $source);
        }
        if (empty($resources[$target])) {
            throw new LogicException('Failed to find resource ' . $target);
        }
        $sourceResource = $resources[$source];
        $targetResource = $resources[$target];

        if ($amount > $dominion->{$source}) {
            throw new GameException(sprintf(
                'You do not have %s %s to exchange.',
                number_format($amount),
                $sourceResource['label']
            ));
        }

        if($source == 'peasants' and $amount > ($dominion->peasants - 1000))
        {
            throw new GameException(sprintf(
                'You cannot sacrifice %s peasants. You must leave at least 1,000 peasants alive.',
                number_format($amount),
            ));
        }

        $targetAmount = floor($amount * $sourceResource['sell'] * $targetResource['buy']);

        $dominion->{$source} -= $amount;
        $dominion->{$target} += $targetAmount;

        $dominion->most_recent_exchange_from = $source;
        $dominion->most_recent_exchange_to = $target;

        $dominion->{'stat_total_'.str_replace('resource_','',$source).'_sold'} += $amount;
        $dominion->{'stat_total_'.str_replace('resource_','',$target).'_bought'} += $targetAmount;

        $dominion->save(['event' => HistoryService::EVENT_ACTION_BANK]);

        $message = 'Your resources have been exchanged.';

        return [
            'message' => $message,
        ];
    }
}
