<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Illuminate\Support\Str;
use OpenDominion\Models\Decree;
use OpenDominion\Models\DecreeState;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionDecreeState;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Calculators\Dominion\DecreeCalculator;
use OpenDominion\Helpers\DecreeHelper;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Models\GameEvent;

class DecreeService
{

    /** @var DecreeHelper */
    protected $decreeHelper;

    /** @var DecreeCalculator */
    protected $decreeCalculator;

    /** @var QueueService */
    protected $queueService;

    public function __construct()
    {
        $this->decreeHelper = app(DecreeHelper::class);
        $this->decreeCalculator = app(DecreeCalculator::class);
        $this->queueService = app(QueueService::class);
    }

    public function issueDominionDecree(Dominion $dominion, Decree $decree, DecreeState $decreeState): void
    {
        if($this->decreeHelper->isDominionDecreeIssued($dominion, $decree))
        {
            throw new GameException('You have already issue this decree. To change your decree, you must first revoke it.');
        }

        if($dominion->isAbandoned() or $dominion->round->hasEnded() or $dominion->isLocked())
        {
            throw new GameException('You cannot issue a decree for a dominion that is locked or abandoned, or after a round has ended.');
        }

        if(!$this->decreeCalculator->isDecreeAvailableToDominion($dominion, $decree))
        {
            throw new GameException('The decree ' . $decree->name . ' is not available to you.');
        }

        if($dominion->race->getPerkValue('cannot_issue_decrees'))
        {

            throw new GameException($dominion->race->name . ' cannot issue decrees.');
        }

        if(!$decree->enabled)
        {

            throw new GameException('Decree ' . $decree->name . ' has been disabled.');
        }

        if(!$decreeState->enabled)
        {

            throw new GameException('State ' . $decreeState->name . ' for decree ' . $decree->name . ' has been disabled.');
        }

        # Create the dominion decree state
        DB::transaction(function () use ($dominion, $decree, $decreeState)
        {
            DominionDecreeState::create([
                'dominion_id' => $dominion->id,
                'decree_id' => $decree->id,
                'decree_state_id' => $decreeState->id,
                'tick' => $dominion->round->ticks
            ]);

            $dominion->save([
                'event' => HistoryService::EVENT_ISSUE_DECREE,
                'action' => $decree->name
            ]);

            GameEvent::create([
                'round_id' => $dominion->round_id,
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'target_type' => Decree::class,
                'target_id' => $decree->id,
                'type' => 'decree_issued',
                'data' => ['decree_state_id' => $decreeState->id],
                'tick' => $dominion->round->ticks
            ]);
        });
    }


    public function revokeDominionDecree(Dominion $dominion, Decree $decree): void
    {
        if(!$this->decreeHelper->isDominionDecreeIssued($dominion, $decree))
        {
            throw new GameException('No decree to revoke.');
        }
        
        if(!$this->decreeCalculator->canDominionRevokeDecree($dominion, $decree))
        {
            $ticksUntilCanRevoke = $this->decreeCalculator->getTicksUntilDominionCanRevokeDecree($dominion, $decree);
            throw new GameException('You cannot revoke this decree for another ' . $ticksUntilCanRevoke . ' ' . Str::plural('tick', $ticksUntilCanRevoke) . '.');
        }

        if($dominion->isAbandoned() or $dominion->round->hasEnded() or $dominion->isLocked())
        {
            throw new GameException('You cannot revoke a decree for a dominion that is locked or abandoned, or after a round has ended.');
        }

        $dominionDecreeState = $this->decreeHelper->getDominionDecreeState($dominion, $decree);

        DB::transaction(function () use ($dominion, $decree, $dominionDecreeState)
        {
            $decreeState = DecreeState::findOrFail($dominionDecreeState->decree_state_id);
            $dominionDecreeState->delete();

            $dominion->save([
                'event' => HistoryService::EVENT_REVOKE_DECREE,
                'action' => $decree->name
            ]);

            GameEvent::create([
                'round_id' => $dominion->round_id,
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'target_type' => Decree::class,
                'target_id' => $decree->id,
                'type' => 'decree_revoked',
                'data' => ['decree_state_id' => $decreeState->id],
                'tick' => $dominion->round->ticks
            ]);
        });

    }

}
