<?php

namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Collection;
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
    public function getResearchTime(Dominion $dominion): int
    {
        $ticks = 96;
        $ticks *= $this->getResearchTimeMultiplier($dominion);

        return ceil($ticks);
    }


    public function getResearchTimeMultiplier(Dominion $dominion)
    {
        $multiplier = 1;

        $multiplier -= $dominion->race->getPerkMultiplier('research_time');
        $multiplier -= $dominion->getImprovementPerkMultiplier('research_time');
        $multiplier -= $dominion->getSpellPerkMultiplier('research_time');
        $multiplier += $dominion->title->getPerkMultiplier('research_time') * $dominion->getTitlePerkMultiplier();

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
        return ($this->getFreeResearchSlots($dominion) > 0);
    }

    public function hasTech(Dominion $dominion, Tech $tech): bool
    {
        $unlockedTechs = $dominion->techs->pluck('key')->all();

        return in_array($tech->key, $unlockedTechs);
    }

    public function getResearchSlots(Dominion $dominion): int
    {
        if($dominion->race->getPerkValue('cannot_research'))
        {
            return 0;
        }

        $slots = 1;

        $slots += $dominion->race->getPerkValue('extra_research_slots');
        $slots += $dominion->getTechPerkValue('research_slots');
        $slots += $dominion->getAdvancementPerkValue('research_slots') / 100;

        return floor($slots);
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

    public function getTechsLeadTo(Tech $tech): Collection
    {
        # Get techs where this tech is a prerequisite
        return Tech::whereRaw("JSON_CONTAINS(prerequisites, '[\"$tech->key\"]')")->get();
    }

    public function getTechsRequired(Tech $tech): Collection
    {
        return Tech::all()->where('enabled',1)->whereIn('key',$tech->prerequisites)->keyBy('key')->sortBy('name');
    }

    public function isBeingResearched(Dominion $dominion, Tech $tech): bool
    {
        return $this->queueService->getResearchQueue($dominion)->where('resource',$tech->key)->count() > 0;
    }

    public function getTicksRemainingOfResearch(Dominion $dominion, Tech $tech): int
    {
        $researchQueue = $this->queueService->getResearchQueue($dominion)->where('resource',$tech->key)->first();

        if($researchQueue)
        {
            return $researchQueue->hours;
        }

        return 0;
    }

    public function getTicksUntilNextResearchCompleted(Dominion $dominion): int
    {
        $researchQueue = $this->queueService->getResearchQueue($dominion)->sortBy('hours')->first();

        if($researchQueue)
        {
            return $researchQueue->hours;
        }

        return 0;
    }

}
