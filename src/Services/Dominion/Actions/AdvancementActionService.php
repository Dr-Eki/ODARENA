<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\AdvancementCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Advancement;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionAdvancement;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;


use OpenDominion\Calculators\Dominion\SpellCalculator;

class AdvancementActionService
{
    use DominionGuardsTrait;

    /** @var AdvancementCalculator */
    protected $advancementCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /**
     * AdvancementActionService constructor.
     *
     * @param AdvancementCalculator $advancementCalculator
     */
    public function __construct(
        AdvancementCalculator $advancementCalculator,
        SpellCalculator $spellCalculator
      )
    {
        $this->advancementCalculator = $advancementCalculator;
        $this->spellCalculator = $spellCalculator;
    }

    /**
     * Does a advancement unlock action for a Dominion.
     *
     * @param Dominion $dominion
     * @param string $key
     * @return array
     * @throws LogicException
     * @throws GameException
     */
    public function levelUp(Dominion $dominion, Advancement $advancement): array
    {
        $this->guardLockedDominion($dominion);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot level up advancements while you are in stasis..');
        }

        // Check if enabled
        if (!$advancement->enabled)
        {
            throw new GameException('This advancement is not enabled.');
        }

        // Check if faction can level up advancements
        if($dominion->race->getPerkValue('cannot_tech'))
        {
            throw new GameException($dominion->race->name . ' cannot use advancements.');
        }

        // Check if can level up
        if (!$this->advancementCalculator->canLevelUp($dominion, $advancement))
        {
            throw new GameException('You cannot level up this advancement.');
        }

        // Check if available to dominion
        if(!$this->advancementCalculator->isAdvancementAvailableToDominion($dominion, $advancement))
        {
            throw new GameException('The advancement ' . $advancement->name . ' is not available to you.');
        }

        # Null if not previously levelled up:
        $dominionAdvancement = DominionAdvancement::where('dominion_id', $dominion->id)
            ->where('advancement_id', $advancement->id)
            ->first();
        
        $advancementCost = $this->advancementCalculator->getLevelUpCost($dominion, $dominionAdvancement);

        // Check experience point
        if ($dominion->xp < $advancementCost) {
            throw new GameException(sprintf(
                'You do not have the required %s XP to level up this advancement.',
                number_format($advancementCost)
            ));
        }

        # Are we creating a DominionAdvancement or updating?
        if($dominionAdvancement)
        {
            $newLevel = $dominionAdvancement->level + 1;
            $newLevel = min($newLevel, $this->advancementCalculator->getDominionMaxLevel($dominion));

            if($newLevel == $this->advancementCalculator->getCurrentLevel($dominion, $advancement))
            {
                throw new GameException('Level up failed due to bad input.');
            }

            DB::transaction(function () use ($dominion, $advancement, $advancementCost, $newLevel) {
                DominionAdvancement::where([
                    'dominion_id' => $dominion->id,
                    'advancement_id' => $advancement->id,
                ])
                ->update(['level' => $newLevel]);
    
                $dominion->xp -= $advancementCost;
                $dominion->save([
                    'event' => HistoryService::EVENT_ACTION_TECH,
                    'action' => $advancement->key
                ]);
            });
        }
        else
        {
            $newLevel = 1;

            DB::transaction(function () use ($dominion, $advancement, $advancementCost, $newLevel) {
                DominionAdvancement::create([
                    'dominion_id' => $dominion->id,
                    'advancement_id' => $advancement->id,
                    'level' => $newLevel
                ]);
    
                $dominion->xp -= $advancementCost;
                $dominion->save([
                    'event' => HistoryService::EVENT_ACTION_TECH,
                    'action' => $advancement->key
                ]);
            });
        }

        return [
            'message' => sprintf(
                'You have levelled up %s to level %s.',
                $advancement->name,
                $newLevel
            )
        ];
    }
}
