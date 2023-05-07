<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Traits\DominionGuardsTrait;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\TerrainService;

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

    /** @var ResourceCalculator */
    protected $resourceCalculator;

    /** @var ResourceService */
    protected $resourceService;

    /** @var StatsService */
    protected $statsService;

    /** @var TerrainService */
    protected $terrainService;

    /** @var QueueService */
    protected $queueService;

    /**
     * RezoneActionService constructor.
     *
     * @param LandCalculator $landCalculator
     * @param RezoningCalculator $rezoningCalculator
     */
    public function __construct()
    {
        $this->rezoningCalculator = app(RezoningCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->resourceService = app(ResourceService::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->statsService = app(StatsService::class);
        $this->terrainService = app(TerrainService::class);
        $this->queueService = app(QueueService::class);
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
            $this->guardActionsDuringTick($dominion);

            if(!$dominion->round->getSetting('rezoning'))
            {
                throw new GameException('Rezoning is disabled this round.');
            }

            // Qur: Statis
            if($dominion->getSpellPerkValue('stasis'))
            {
                throw new GameException('You cannot rezone land while you are in stasis.');
            }

            if ($dominion->race->getPerkValue('cannot_rezone'))
            {
                throw new GameException($dominion->race->name . ' cannot rezone land.');
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

            if($cost > $this->resourceCalculator->getAmount($dominion,$resource))
            {
                throw new GameException("You do not have enough $resource to re-zone {$totalLand} acres of land.");
            }

            # All fine, perform changes.
            $this->resourceService->updateResources($dominion, [$resource => $cost*-1]);

            # Update spending statistics.
            $this->statsService->updateStat($dominion, ($resource . '_rezoning'), $cost);
            DB::transaction(function () use ($dominion, $remove, $add)
            {
                foreach ($remove as $landType => $amount) {
                    $dominion->{'land_' . $landType} -= $amount;
                }
                foreach ($add as $landType => $amount) {
                    $dominion->{'land_' . $landType} += $amount;
                }

            });

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

    /**
     * Does a rezone action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $remove Land to remove
     * @param array $add Land to add.
     * @return array
     * @throws GameException
     */
    public function rezoneTerrain(Dominion $dominion, array $remove, array $add): array
    {
        
            $this->guardLockedDominion($dominion);
            $this->guardActionsDuringTick($dominion);

            if(!$dominion->round->getSetting('rezoning'))
            {
                throw new GameException('Rezoning is disabled this round.');
            }

            // Qur: Statis
            if($dominion->getSpellPerkValue('stasis'))
            {
                throw new GameException('You cannot rezone land while you are in stasis.');
            }

            if ($dominion->race->getPerkValue('cannot_rezone'))
            {
                throw new GameException($dominion->race->name . ' cannot rezone land.');
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
                throw new GameException('Rezoning was not started due to bad input.');
            }

            $cost = $totalLand * $this->rezoningCalculator->getRezoningCost($dominion);
            $resource = $this->rezoningCalculator->getRezoningMaterial($dominion);

            if($cost > $this->resourceCalculator->getAmount($dominion,$resource))
            {
                throw new GameException("You do not have enough $resource to rezone {$totalLand} acres of land.");
            }

            $terrainAdd = $add;
            $terrainRemove = array_map(function($value) {
                return -$value;
            }, $remove);

            if((array_sum($terrainAdd) + array_sum($terrainRemove)) !== 0)
            {
                throw new GameException('Rezoning was not started due to bad input.');
            }

            # Instant rezone in 96 protection ticks
            if($dominion->protection_ticks === 96)
            {
                # Queue the rezoning.
                foreach($terrainAdd as $terrain => $amount)
                {
                    $this->terrainService->update($dominion, [$terrain => $amount]);
                }

                # Remove the terrain
                foreach($terrainRemove as $terrain => $amount)
                {
                    $this->terrainService->update($dominion, [$terrain => $amount]);
                }
            }   
            else
            {
                $ticks = 12;
    
                DB::transaction(function () use ($dominion, $terrainAdd, $terrainRemove, $resource, $cost, $ticks)
                {
                    # Update spending statistics.
                    $this->statsService->updateStat($dominion, ($resource . '_rezoning'), $cost);
    
                    # All fine, perform changes.
                    $this->resourceService->updateResources($dominion, [$resource => $cost*-1]);
                    
                    # Queue the rezoning.
                    foreach($terrainAdd as $terrain => $amount)
                    {
                        $this->queueService->queueResources('rezoning', $dominion, [('terrain_' . $terrain) => $amount], $ticks);
                    }
    
                    # Remove the terrain
                    foreach($terrainRemove as $terrain => $amount)
                    {
                        $this->terrainService->update($dominion, [$terrain => $amount]);
                    }
                });

            }      

        $dominion->save(['event' => HistoryService::EVENT_ACTION_REZONE]);

        return [
            'message' => sprintf(
                'Your rezoning has begun at a cost of %1$s %2$s.',
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
