<?php

namespace OpenDominion\Services\Dominion\Actions;

use LogicException;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Resource;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Traits\DominionGuardsTrait;

use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

class BankActionService
{
    use DominionGuardsTrait;

    protected $resourceCalculator;
    protected $resourceService;
    protected $spellCalculator;
    protected $statsService;

    public function __construct()
    {
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->resourceService = app(ResourceService::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->statsService = app(StatsService::class);
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
        $this->guardActionsDuringTick($dominion);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot exchange resources while you are in stasis.');
        }

        // Perk: cannot_exchange
        if($dominion->race->getPerkValue('cannot_exchange'))
        {
            throw new GameException($dominion->race->name . ' cannot exchange resources.');
        }

        if($amount < 0)
        {
            throw new LogicException('Amount less than 0.');
        }

        $sourceResourceKey = str_replace('resource_','', $source);
        $targetResourceKey = str_replace('resource_','', $target);

        $sourceResource = Resource::where('key', $sourceResourceKey)->first();
        $targetResource = Resource::where('key', $targetResourceKey)->first();

        if(!in_array($sourceResourceKey, $dominion->race->resources))
        {
            throw new GameException($dominion->race->name . ' cannot use ' . $sourceResource->name . '.');
        }

        if(!in_array($targetResourceKey, $dominion->race->resources))
        {
            throw new GameException($dominion->race->name . ' cannot use ' . $targetResource->name . '.');
        }

        if ($amount > $this->resourceCalculator->getAmount($dominion, $sourceResourceKey)) {
            throw new GameException(sprintf(
                'You do not have %s %s to exchange.',
                number_format($amount),
                $sourceResource->name
            ));
        }

        $targetAmount = floor($amount * (float)$sourceResource->sell * $this->resourceCalculator->getExchangeRatePerkMultiplier($dominion) * (float)$targetResource->buy);

        $this->resourceService->updateResources($dominion, [$sourceResourceKey => $amount*-1]);
        $this->resourceService->updateResources($dominion, [$targetResourceKey => $targetAmount]);

        $dominion->most_recent_exchange_from = $source;
        $dominion->most_recent_exchange_to = $target;

        $this->statsService->updateStat($dominion, (str_replace('resource_', '', $source).'_sold'), $amount);
        $this->statsService->updateStat($dominion, (str_replace('resource_', '', $target).'_bought'), $targetAmount);

        $dominion->save(['event' => HistoryService::EVENT_ACTION_BANK]);

        $message = 'Your resources have been exchanged.';

        return [
            'message' => $message,
        ];
    }
}
