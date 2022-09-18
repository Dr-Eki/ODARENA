<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;

class DestroyActionService
{
    use DominionGuardsTrait;

        /** @var SpellCalculator */
        protected $spellCalculator;

        public function __construct()
        {
            $this->spellCalculator = app(SpellCalculator::class);
            $this->buildingCalculator = app(BuildingCalculator::class);
        }

    /**
     * Does a destroy buildings action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws GameException
     */
    public function destroy(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot destroy buildings while you are in stasis.');
        }

        $data = array_map('\intval', $data);

        $totalBuildingsToDestroy = array_sum($data);

        if ($totalBuildingsToDestroy < 0) {
            throw new GameException('The destruction was not completed due to bad input.');
        }

        foreach ($data as $buildingType => $amount)
        {
            if ($amount === 0) {
                continue;
            }

            if ($amount < 0) {
                throw new GameException('Destruction was not completed due to bad input.');
            }

            if ($amount > $dominion->{'building_' . $buildingType}) {
                throw new GameException('The destruction was not completed due to bad input.');
            }
        }

        # BV2
        $this->buildingCalculator->removeBuildings($dominion, $data);

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
