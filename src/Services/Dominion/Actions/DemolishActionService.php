<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Building;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Helpers\BuildingHelper;

class DemolishActionService
{
    use DominionGuardsTrait;

        /** @var SpellCalculator */
        protected $spellCalculator;

        public function __construct()
        {
            $this->spellCalculator = app(SpellCalculator::class);
            $this->buildingCalculator = app(BuildingCalculator::class);
            $this->buildingHelper = app(BuildingHelper::class);
        }

    /**
     * Does a destroy buildings action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws GameException
     */
    public function demolish(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);
        $dominionBuildings = $this->buildingCalculator->getDominionBuildings($dominion);
        $demolishData = [];

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot demolish buildings while you are in stasis.');
        }

        $data = array_map('\intval', $data);

        $totalBuildingsToDestroy = array_sum($data);

        if ($totalBuildingsToDestroy < 0)
        {
            throw new GameException('Demolition was not completed due to bad input.');
        }

        foreach ($data as $buildingType => $amount)
        {
            $buildingKey = str_replace('building_', '', $buildingType);
            $building = Building::where('key', $buildingKey)->first();

            if ($amount === 0)
            {
                continue;
            }

            if ($amount < 0)
            {
                throw new GameException('Demolition was not completed due to bad input.');
            }

            if ($amount > $dominionBuildings->where('building_id', $building->id)->first()->owned)
            {
                throw new GameException('Amount demolished exceeds owned.');
            }

            $demolishData[$building->key] = ['builtBuildingsToDestroy' => $amount];
        }

        # BV2
        $this->buildingCalculator->removeBuildings($dominion, $demolishData);

        $dominion->save(['event' => HistoryService::EVENT_ACTION_DESTROY]);

        return [
            'message' => sprintf(
                'Destruction of %s %s is complete.',
                number_format($totalBuildingsToDestroy),
                str_plural('building', $totalBuildingsToDestroy)
            ),
            'data' => [
                'totalBuildingsDestroyed' => $totalBuildingsToDestroy,
            ],
        ];
    }
}
