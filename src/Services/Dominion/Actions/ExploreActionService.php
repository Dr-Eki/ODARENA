<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Traits\DominionGuardsTrait;


use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\ResourceService;

class ExploreActionService
{

    use DominionGuardsTrait;

    /** @var ExplorationCalculator */
    protected $explorationCalculator;

    /** @var LandHelper */
    protected $landHelper;

    /** @var QueueService */
    protected $queueService;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /**
     * @var int The minimum morale required to explore
     */
    protected const MIN_MORALE = 0;

    /**
     * ExplorationActionService constructor.
     */
    public function __construct(
          ImprovementCalculator $improvementCalculator,
          SpellCalculator $spellCalculator,
          LandCalculator $landCalculator,
          ResourceService $resourceService,
          StatsService $statsService,
          ProtectionService $protectionService
      )
    {
        $this->explorationCalculator = app(ExplorationCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->queueService = app(QueueService::class);
        $this->spellCalculator = $spellCalculator;
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->protectionService = $protectionService;
        $this->resourceService = $resourceService;
        $this->statsService = $statsService;
    }

    /**
     * Does an explore action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws GameException
     */
    public function explore(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);


        throw new GameException('Exploration has been permanently disabled.');


        if($dominion->getDeityPerkValue('cannot_explore'))
        {
            throw new GameException('Your deity prohibits exploring.');
        }

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot explore while you are in stasis.');
        }

        $data = array_only($data, array_map(function ($value) {
            return "land_{$value}";
        }, $this->landHelper->getLandTypes()));

        $data = array_map('\intval', $data);

        $totalLandToExplore = array_sum($data);

        if ($totalLandToExplore <= 0) {
            throw new GameException('Exploration was not begun due to bad input.');
        }

        foreach($data as $amount) {
            if ($amount < 0) {
                throw new GameException('Exploration was not completed due to bad input.');
            }
        }

        if ($dominion->race->getPerkValue('cannot_explore') == 1)
        {
            throw new GameException('Your faction is unable to explore.');
        }

        if ($totalLandToExplore > $this->explorationCalculator->getMaxAfford($dominion))
        {
            throw new GameException('You do not have enough gold and/or draftees to explore for ' . number_format($totalLandToExplore) . ' acres.');
        }

        $maxAllowed = $dominion->land * 1.5;
        if($totalLandToExplore > $maxAllowed)
        {
            throw new GameException('You cannot explore more than ' . number_format($maxAllowed) . ' acres.');
        }

        # ODA
        // Spell: Rainy Season (cannot explore)
        if ($this->spellCalculator->isSpellActive($dominion, 'rainy_season'))
        {
            throw new GameException('You cannot explore during Rainy Season.');
        }

        if($dominion->getSpellPerkMultiplier('cannot_explore'))
        {
              throw new GameException('A spell is preventing you from exploring.');
        }

        if ($dominion->morale <= static::MIN_MORALE)
        {
            throw new GameException('You do not have enough morale to explore.');
        }

        $moraleDrop = $this->explorationCalculator->getMoraleDrop($dominion, $totalLandToExplore);
        if($moraleDrop > $dominion->morale)
        {
            throw new GameException('Exploring that much land would lower your morale by ' . $moraleDrop . '%. You currently have ' . $dominion->morale . '% morale.');
        }

        $newMorale = $dominion->morale - $moraleDrop;

        $goldCost = ($this->explorationCalculator->getGoldCost($dominion) * $totalLandToExplore);

        $drafteeCost = ($this->explorationCalculator->getDrafteeCost($dominion) * $totalLandToExplore);
        $newDraftees = ($dominion->military_draftees - $drafteeCost);

        $researchPointsPerAcre = 10;

        // Improvements
        $researchPointsPerAcreMultiplier = 1;
        $researchPointsPerAcreMultiplier += $dominion->getImprovementPerkMultiplier('tech_gains');
        $researchPointsPerAcreMultiplier += $dominion->getSpellPerkMultiplier('tech_gains');
        $researchPointsPerAcreMultiplier += $dominion->getBuildingPerkMultiplier('tech_gains');
        $researchPointsPerAcreMultiplier += $dominion->getDeityPerkMultiplier('tech_gains');
        $researchPointsPerAcreMultiplier += $dominion->race->getPerkMultiplier('tech_gains');
        $researchPointsPerAcre *= ($researchPointsPerAcreMultiplier);
        $researchPointsGained = $researchPointsPerAcre * $totalLandToExplore;

        # Pathfinder
        $ticks = $this->explorationCalculator->getExploreTime($dominion);

        $this->statsService->updateStat($dominion, 'land_explored', $totalLandToExplore);
        $this->statsService->updateStat($dominion, 'gold_exploring', $goldCost);

        DB::transaction(function () use ($dominion, $data, $newMorale, $newDraftees, $totalLandToExplore, $researchPointsGained, $goldCost, $ticks) {
            $this->queueService->queueResources('exploration', $dominion, $data, $ticks);
            $this->queueService->queueResources('exploration',$dominion,['xp' => $researchPointsGained], $ticks);

            if($explorePrestigePerk = $dominion->title->getPerkValue('explore_prestige_per_acre'))
            {
                $this->queueService->queueResources('exploration',$dominion,['prestige' => intval($totalLandToExplore * $explorePrestigePerk)], $ticks);
            }

            $dominion->fill([
                'morale' => $newMorale,
                'military_draftees' => $newDraftees
            ])->save(['event' => HistoryService::EVENT_ACTION_EXPLORE]);

            $this->resourceService->updateResources($dominion, ['gold' => $goldCost*-1]);
        });

        return [
            'message' => sprintf(
                'Exploration begun at a cost of %s gold and %s %s. When exploration is completed, you will earn %s XP. Your orders for exploration disheartens the military, and morale drops by %d%%.',
                number_format($goldCost),
                number_format($drafteeCost),
                str_plural('draftee', $drafteeCost),
                number_format($researchPointsGained),
                $moraleDrop
            ),
            'data' => [
                'goldCost' => $goldCost,
                'drafteeCost' => $drafteeCost,
                'moraleDrop' => $moraleDrop,
            ]
        ];
    }
}
