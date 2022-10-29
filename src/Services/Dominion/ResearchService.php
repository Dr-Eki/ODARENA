<?php

namespace OpenDominion\Services\Dominion;

use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionTech;
use OpenDominion\Models\Tech;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Calculators\Dominion\ResearchCalculator;
use OpenDominion\Helpers\ResearchHelper;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Models\GameEvent;

class ResearchService
{
    public function __construct()
    {
        $this->researchHelper = app(ResearchHelper::class);
        $this->researchCalculator = app(ResearchCalculator::class);
        $this->queueService = app(QueueService::class);
    }

    public function beginResearch(Dominion $dominion, Tech $tech): void
    {

        if($dominion->race->getPerkValue('cannot_research'))
        {
            throw new GameException($dominion->race->name .  ' dominions cannot research.');
        }

        if($dominion->isAbandoned() or $dominion->round->hasEnded() or $dominion->isLocked())
        {
            throw new GameException('You cannot submit to a deity for a dominion that is locked or abandoned, or after a round has ended.');
        }


        $ticks = 48;

        $this->queueService->queueResources('research', $dominion, [$tech->key => 1], $ticks);

        $dominion->save([
            'event' => HistoryService::EVENT_RESEARCH_BEGIN,
            'action' => $tech->name
        ]);

    }

  public function completeResearch(Dominion $dominion, Tech $tech): void
  {
      if(!$dominion->hasDeity())
      {
          DB::transaction(function () use ($dominion, $tech)
          {
              DominionTech::create([
                  'dominion_id' => $dominion->id,
                  'tech_id' => $tech->id
              ]);

              $dominion->save([
                  'event' => HistoryService::EVENT_RESEARCH_COMPLETE,
                  'action' => $tech->name
              ]);
          });
      }
  }

}
