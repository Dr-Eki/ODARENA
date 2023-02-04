<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Carbon\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionDeity;
use OpenDominion\Models\Deity;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Calculators\Dominion\DeityCalculator;
use OpenDominion\Helpers\DeityHelper;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Models\GameEvent;

class DeityService
{
    public function __construct()
    {
        $this->deityHelper = app(DeityHelper::class);
        $this->deityCalculator = app(DeityCalculator::class);
        $this->queueService = app(QueueService::class);
    }

    public function submitToDeity(Dominion $dominion, string $deityKey): void
    {

        if(!$dominion->round->getSetting('deities'))
        {
            throw new GameException('Deities are disabled this round.');
        }

        $deity = Deity::where('key', $deityKey)->first();

        if(!$deity)
        {
            throw new GameException('Invalid deity.');
        }

        if($dominion->race->getPerkValue('cannot_submit_to_deity'))
        {
            throw new GameException('You cannot submit to a deity for a dominion that is locked or abandoned, or after a round has ended.');
        }

        if($dominion->isAbandoned() or $dominion->round->hasEnded() or $dominion->isLocked())
        {
            throw new GameException('You cannot submit to a deity for a dominion that is locked or abandoned, or after a round has ended.');
        }

        if($dominion->hasPendingDeitySubmission())
        {
            throw new GameException('You already have a submission to a deity in progress.');
        }

        if(!$this->deityCalculator->isDeityAvailableToDominion($dominion, $deity))
        {
            throw new GameException('The deity ' . $deity->name . ' does not accept devotion from ' . $dominion . ' and other ' . $dominion->race->name . ' dominions!');
        }

        $ticks = 48;

        $this->queueService->queueResources('deity', $dominion, [$deity->key => 1], $ticks);

        $dominion->save([
            'event' => HistoryService::EVENT_SUBMIT_TO_DEITY_BEGUN,
            'action' => $deity->name
        ]);

    }

  public function completeSubmissionToDeity(Dominion $dominion, Deity $deity): void
  {
      if(!$dominion->hasDeity())
      {
          DB::transaction(function () use ($dominion, $deity)
          {
              DominionDeity::create([
                  'dominion_id' => $dominion->id,
                  'deity_id' => $deity->id,
                  'duration' => 0
              ]);

              $dominion->save([
                  'event' => HistoryService::EVENT_SUBMIT_TO_DEITY_COMPLETED,
                  'action' => $deity->name
              ]);
          });
      }
  }

    public function renounceDeity(Dominion $dominion, Deity $deity): void
    {
        if(!$dominion->hasDeity())
        {
            throw new GameException('No deity to renounce.');
        }

        if($dominion->isAbandoned() or $dominion->round->hasEnded() or $dominion->isLocked())
        {
            throw new GameException('You cannot renounce a deity for a dominion that is locked or abandoned, or after a round has ended.');
        }

        if($dominion->getTechPerkValue('cannot_renounce_deity'))
        {
            throw new GameException('You cannot renounce your deity.');
        }

        if($dominion->race->getPerkValue('cannot_renounce_deity'))
        {
            throw new GameException($dominion->race->name . ' cannot renounce.');
        }

        DB::transaction(function () use ($dominion, $deity)
        {
            DB::table('dominion_deity')
                ->where('dominion_id', $dominion->id)
                ->where('deity_id', $deity->id)
                ->delete();

            $dominion->save([
                'event' => HistoryService::EVENT_RENOUNCE_DEITY,
                'action' => $deity->name
            ]);
        });

        $deityEvent = GameEvent::create([
            'round_id' => $dominion->round_id,
            'source_type' => Deity::class,
            'source_id' => $deity->id,
            'target_type' => Dominion::class,
            'target_id' => $dominion->id,
            'type' => 'deity_renounced',
            'data' => [],
            'tick' => $dominion->round->ticks
        ]);

    }

}
