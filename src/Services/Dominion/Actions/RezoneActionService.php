<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Terrain;
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

            if (($totalLand <= 0) or $totalLand !== array_sum($add) or $totalLand > $dominion->land or array_sum($remove) > $dominion->land or array_sum($add) > $dominion->land)
            {
                throw new GameException('Rezoning was not started due to bad input.');
            }

            foreach($remove as $terrainKey => $amount)
            {
                $terrain = Terrain::where('key', $terrainKey)->first();

                if(abs($amount) > $dominion->{'terrain_' . $terrainKey})
                {
                    throw new GameException('You do not have enough ' . $terrain->name . ' to rezone ' . number_format(abs($amount)) . ' acres.');
                }
            }

            $cost = $totalLand * $this->rezoningCalculator->getRezoningCost($dominion);
            $resource = $this->rezoningCalculator->getRezoningMaterial($dominion);

            if($cost > $dominion->{'resource_' . $resource})
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
            if($dominion->protection_ticks >= 96)
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
                $ticks -= $dominion->race->getPerkValue('increased_rezoning_speed');

                $ticks = max($ticks, 1);
    
                DB::transaction(function () use ($dominion, $terrainAdd, $terrainRemove, $resource, $cost, $ticks)
                {
                    # Update spending statistics.
                    $this->statsService->updateStat($dominion, ($resource . '_rezoning'), $cost);
    
                    # All fine, perform changes.
                    $this->resourceService->update($dominion, [$resource => $cost*-1]);
                    
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
