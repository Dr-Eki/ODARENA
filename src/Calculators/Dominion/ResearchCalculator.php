<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\ResearchHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Tech;

use OpenDominion\Services\Dominion\QueueService;

class ResearchCalculator
{

    public function __construct()
    {
        $this->researchHelper = app(ResearchHelper::class);
        $this->queueService = app(QueueService::class);
    }

    /**
     * Returns the Dominion's current experience point cost to unlock a new tech.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getResearchDuration(Dominion $dominion, Tech $tech): int
    {
        $ticks = 96;
        $ticks *= $this->getResearchDurationMultiplier($dominion);

        return $ticks;
    }


    public function getResearchDurationMultiplier(Dominion $dominion)
    {
        $multiplier = 0;

        $multiplier += $dominion->race->getPerkMultiplier('research_time');
        $multiplier += $dominion->getImprovementPerkMultiplier('research_time');
        $multiplier += $dominion->getSpellPerkMultiplier('research_time');

        return $multiplier;
    }

    /**
     * Determine if the Dominion meets the requirements to unlock a new tech.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function hasPrerequisites(Dominion $dominion, Tech $tech): bool
    {
        $unlockedTechs = $dominion->techs->pluck('key')->all();

        return count(array_diff($tech->prerequisites, $unlockedTechs)) == 0;
    }

    public function canResearchTech(Dominion $dominion, Tech $tech): bool
    {
        return (
                $this->hasPrerequisites($dominion, $tech)
                and $this->researchHelper->getTechsByRace($dominion->race)->contains($tech)
                and $this->canBeginNewResearch($dominion)
                and !$this->hasTech($dominion, $tech)
            );
    }

    public function canBeginNewResearch(Dominion $dominion): bool
    {
        # Check if has free research slots
        return ($this->getFreeResearchSlots($dominion) > 0);
    }

    public function hasTech(Dominion $dominion, Tech $tech): bool
    {
        $unlockedTechs = $dominion->techs->pluck('key')->all();

        return in_array($tech->key, $unlockedTechs);
    }

    public function getResearchSlots(Dominion $dominion): int
    {
        if($dominion->race->getPerkValue('cannot_reseearch'))
        {
            return 0;
        }

        $slots = 1;

        $slots *= $dominion->getAdvancementPerkMultiplier('research_slots');

        $slots += $dominion->race->getPerkValue('extra_research_slots');
        $slots += $dominion->getTechPerkValue('research_slots');

        return $slots;
    }

    public function getOngoingResearchCount(Dominion $dominion): int
    {
        return $this->queueService->getResearchQueue($dominion)->count();
    }

    public function getCurrentResearchTechs(Dominion $dominion): array
    {
        return $this->queueService->getResearchQueue($dominion)->pluck('resource')->all();
    }

    public function getFreeResearchSlots(Dominion $dominion): int
    {
        return max($this->getResearchSlots($dominion) - $this->getOngoingResearchCount($dominion), 0);
    }

    public function getTechsLeadTo(Tech $tech)
    {
        return Tech::all()->where('enabled',1)->where('prerequisites','like','%'.$tech->key.'%')->keyBy('key')->sortBy('name');
    }

    public function getTechsRequired(Tech $tech)
    {
        return Tech::all()->where('enabled',1)->whereIn('key',$tech->prerequisites)->keyBy('key')->sortBy('name');
    }

}
