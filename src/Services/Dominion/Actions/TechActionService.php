<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionTech;
use OpenDominion\Models\Tech;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;


use OpenDominion\Calculators\Dominion\SpellCalculator;

class TechActionService
{
    use DominionGuardsTrait;

    /** @var TechCalculator */
    protected $techCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /**
     * TechActionService constructor.
     *
     * @param TechCalculator $techCalculator
     */
    public function __construct(
        TechCalculator $techCalculator,
        SpellCalculator $spellCalculator
      )
    {
        $this->techCalculator = $techCalculator;
        $this->spellCalculator = $spellCalculator;
    }

    /**
     * Does a tech unlock action for a Dominion.
     *
     * @param Dominion $dominion
     * @param string $key
     * @return array
     * @throws LogicException
     * @throws GameException
     */
    public function unlock(Dominion $dominion, string $key): array
    {
        $this->guardLockedDominion($dominion);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot level up advancements while you are in stasis..');
        }

        // Get the tech information
        $techToUnlock = Tech::where('key', $key)->first();
        if ($techToUnlock == null) {
            throw new LogicException('Failed to find advancement: ' . $key);
        }

        // Check prerequisites
        if (!$this->techCalculator->hasPrerequisites($dominion, $techToUnlock)) {
            throw new GameException('You do not meet the requirements to level up this advancement.');
        }

        // Check if enabled
        if ($techToUnlock->enabled !== 1)
        {
            throw new GameException('This tech is not enabled.');
        }

        // Check experience point
        $techCost = $this->techCalculator->getTechCost($dominion, $techToUnlock);
        if ($dominion->resource_tech < $techCost) {
            throw new GameException(sprintf(
                'You do not have the required %s experience points to level up this advancement.',
                number_format($techCost)
            ));
        }

        // Check if faction can unlock advancements
        if($dominion->race->getPerkValue('cannot_tech'))
        {
            throw new GameException($dominion->race->name . ' cannot use advancements.');
        }

        DB::transaction(function () use ($dominion, $techToUnlock, $techCost) {
            DominionTech::create([
                'dominion_id' => $dominion->id,
                'tech_id' => $techToUnlock->id
            ]);

            $dominion->resource_tech -= $techCost;
            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_TECH,
                'action' => $techToUnlock->key
            ]);
        });

        return [
            'message' => sprintf(
                'You have levelled up %s.',
                $techToUnlock->name
            )
        ];
    }
}
