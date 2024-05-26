<?php

namespace OpenDominion\Services\Dominion\Actions;

use Illuminate\Support\Str;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Improvement;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Traits\DominionGuardsTrait;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

class ImproveActionService
{
    use DominionGuardsTrait;
    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var ImprovementHelper */
    protected $improvementHelper;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var ResourceCalculator */
    protected $resourceCalculator;

    /** @var ResourceService */
    protected $resourceService;

    /** @var StatsService */
    protected $statsService;

    public function __construct()
    {
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->resourceService = app(ResourceService::class);
        $this->statsService = app(StatsService::class);
    }

    /**
     * Returns the message for a improve action.
     *
     * @param string $resource
     * @param array $data
     * @param int $totalResourcesToInvest
     * @return string
     */
    protected function getReturnMessageString(string $resource, array $data, int $totalResourcesToInvest, Dominion $dominion): string
    {
        $investmentStringParts = [];

        foreach ($data as $improvementType => $amount) {
            if ($amount === 0) {
                continue;
            }

            $investmentStringParts[] = (number_format($amount) . ' ' . $improvementType);
        }

        $investmentString = generate_sentence_from_array($investmentStringParts);

        return sprintf(
            'You invest %s %s into %s.',
            number_format($totalResourcesToInvest),
            ($resource === 'gems') ? Str::plural('gem', $totalResourcesToInvest) : $resource,
            $investmentString
        );
    }

    public function improve(Dominion $dominion, string $resource, array $data): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        if(!$dominion->round->getSetting('improvements'))
        {
            throw new GameException('Improvements are disabled this round.');
        }

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot invest in improvements while you are in stasis.');
        }
        
        if($dominion->race->getPerkValue('cannot_improve'))
        {
            throw new GameException($dominion->race->name . ' cannot use improvements.');
        }       

        $data = array_map('\intval', $data);

        $totalResourcesToInvest = array_sum($data);

        $worth = $this->improvementCalculator->getResourceWorth($resource, $dominion);

        if ($totalResourcesToInvest <= 0)
        {
            throw new GameException('Investment aborted due to bad input.');
        }

        if(!array_key_exists($resource, $dominion->race->improvement_resources))
        {
            throw new GameException($dominion->race->name . ' cannot use ' . $resource .  ' for improvements.');
        }

        if ($totalResourcesToInvest > $dominion->{'resource_' . $resource})
        {
            throw new GameException("You do not have enough {$resource}. You have " . number_format($dominion->{'resource_' . $resource}) . ' ' . $resource . ' and tried to invest ' . number_format($totalResourcesToInvest) . '.');
        }
        

        foreach ($data as $improvementKey => $amount)
        {

            $improvement = Improvement::where('key', $improvementKey)->first();

            if ($amount === 0)
            {
                continue;
            }

            if ($amount < 0)
            {
                throw new GameException('Investment aborted due to bad input.');
            }

            if(!$this->improvementHelper->getImprovementsByRace($dominion->race)->where('key', $improvementKey))
            {
                throw new GameException('Improvement key ' . $improvement->name .  ' not available to ' . $dominion->race->name . '.');
            }

            $points = $amount * $worth;
            $data[$improvement->key] = $points;
            $returnData[$improvement->name] = $points;

        }

        $this->improvementCalculator->createOrIncrementImprovements($dominion, $data);

        $this->resourceService->update($dominion, [$resource => $totalResourcesToInvest*-1]);

        $dominion->most_recent_improvement_resource = $resource;

        $resourceNameForStats = $resource;

        $this->statsService->updateStat($dominion, ($resource . '_improvements'), $totalResourcesToInvest);

        $dominion->save(['event' => HistoryService::EVENT_ACTION_IMPROVE]);

        return [
            'message' => $this->getReturnMessageString($resource, $returnData, $totalResourcesToInvest, $dominion),
            'data' => [
                'totalResourcesInvested' => $totalResourcesToInvest,
                'resourceInvested' => $resource,
            ],
        ];
    }

}
