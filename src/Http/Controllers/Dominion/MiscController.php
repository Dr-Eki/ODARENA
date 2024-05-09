<?php

namespace OpenDominion\Http\Controllers\Dominion;

use LogicException;

# ODA
use DB;
use Auth;
use Log;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Services\Dominion\DominionStateService;
use OpenDominion\Services\Dominion\SelectorService;


use OpenDominion\Http\Requests\Dominion\Actions\RestoreDominionStateRequest;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionState;
use OpenDominion\Models\GameEvent;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Calculators\Dominion\TickCalculator;

use OpenDominion\Traits\DominionGuardsTrait;

// misc functions, probably could use a refactor later
class MiscController extends AbstractDominionController
{
    use DominionGuardsTrait;
    
    /** @var SelectorService */
    protected $dominionSelectorService;

    /** @var DominionStateService */
    protected $dominionStateService;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var TickCalculator */
    protected $tickCalculator;

    /**
     * MiscController constructor.
     *
     * @param SelectorService $dominionSelectorService
     */
    public function __construct(
        DominionStateService $dominionStateService,
        SelectorService $dominionSelectorService,
        MilitaryCalculator $militaryCalculator,
        QueueService $queueService,
        TickCalculator $tickCalculator
        )
    {
        $this->dominionSelectorService = $dominionSelectorService;
        $this->dominionStateService = $dominionStateService;
        $this->militaryCalculator = $militaryCalculator;
        $this->queueService = $queueService;
        $this->tickCalculator = $tickCalculator;
    }

    public function postClearNotifications()
    {
        $this->getSelectedDominion()->notifications->markAsRead();
        return redirect()->back();
    }

    public function postClosePack()
    {
        $dominion = $this->getSelectedDominion();
        $pack = $dominion->pack;

        // Only pack creator can manually close it
        if ($pack->creator_dominion_id !== $dominion->id) {
            throw new LogicException('Pack may only be closed by the creator');
        }

        $pack->closed_at = now();
        $pack->save();

        return redirect()->back();
    }

    public function postDeleteDominion()
    {

        /*
        *   Conditions for allowing deleting:
        *   - The dominion belongs to the logged in user.
        *   - If the round has started, only allow deleting if protection ticks > 0.
        *   - If the round hasn't started, always allow.
        */

        $dominion = $this->getSelectedDominion();
        $dominionName = $dominion->name;
        $dominionId = $dominion->id;
        $dominionRaceName = $dominion->race->name;

        DB::transaction(function () use ($dominion) {
            
            # Can only delete your own dominion.
            if($dominion->user_id !== Auth::user()->id)
            {
                throw new LogicException('You cannot delete other dominions than your own.');
            }

            # If the round has started, can only delete if protection ticks > 0.
            if($dominion->round->hasStarted() and $dominion->protection_ticks <= 0 and request()->getHost() !== 'sim.odarena.com')
            {
                throw new LogicException('You cannot delete your dominion because the round has already started.');
            }

            # If the round has ended or offensive actions are disabled, do not allow delete.
            if($dominion->round->hasEnded())
            {
                throw new LogicException('You cannot delete your dominion because the round has ended.');
            }

            # Delete all traces of the dominion.
            DB::table('dominions')->where('monarchy_vote_for_dominion_id', '=', $dominion->id)->update(['monarchy_vote_for_dominion_id' => null]);
            DB::table('realms')->where('monarch_dominion_id', '=', $dominion->id)->update(['monarch_dominion_id' => null]);

            // Council...
            DB::table('council_posts')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('council_threads')->where('dominion_id', '=', $dominion->id)->delete();

            // Dominion...
            DB::table('dominion_advancements')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_buildings')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_decree_states')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_deity')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_history')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_improvements')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_insight')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_insight')->where('source_dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_queue')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_resources')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_spells')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_spells')->where('caster_id', '=', $dominion->id)->delete();
            DB::table('dominion_stats')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_states')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_techs')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_terrains')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_tick')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_units')->where('dominion_id', '=', $dominion->id)->delete();

            // Protectorship...
            DB::table('protectorships')->where('protector_id', '=', $dominion->id)->delete();
            DB::table('protectorships')->where('protected_id', '=', $dominion->id)->delete();
            DB::table('protectorship_offers')->where('protector_id', '=', $dominion->id)->delete();
            DB::table('protectorship_offers')->where('protected_id', '=', $dominion->id)->delete();

            // Realm history...
            DB::table('realm_history')->where('dominion_id', '=', $dominion->id)->delete();

            // Holds...
            DB::table('hold_sentiments')->where('target_id', '=', $dominion->id)->delete();
            DB::table('hold_sentiment_events')->where('target_id', '=', $dominion->id)->delete();

            // Trade...
            DB::table('trade_ledger')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('trade_routes')->where('dominion_id', '=', $dominion->id)->delete();

            // Watched dominions...
            DB::table('watched_dominions')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('watched_dominions')->where('watcher_id', '=', $dominion->id)->delete();

            // Game event stories...
            DB::table('game_event_stories')
                ->whereIn('game_event_id', function ($query) use ($dominion) {
                    $query->select('id')
                        ->from('game_events')
                        ->where('source_id', $dominion->id)
                        ->orWhere('target_id', $dominion->id);
                })
                ->delete();

            DB::table('game_events')->where('source_id', '=', $dominion->id)->delete();
            DB::table('game_events')->where('target_id', '=', $dominion->id)->delete();

            # Delete the dominion.

            // Temporary (:eyeroll:) fix for foreign key constraint violation between trade_routes and dominion->id
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('dominions')->where('id', '=', $dominion->id)->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        });


        $this->dominionSelectorService->unsetUserSelectedDominion();

        Log::info(sprintf(
            'The %s dominion %s (ID %s) was deleted by user %s (ID %s).',
            $dominionRaceName,
            $dominionName,
            $dominionId,
            Auth::user()->display_name,
            Auth::user()->id
        ));
        

        return redirect()
            ->to(route('dashboard'))
            ->with(
                'alert-success',
                'Your dominion has been deleted.'
            );
    }

    public function postAbandonDominion()
    {

        /*
        *   Conditions for allowing abandoning:
        *   - Round must be active
        *   - The dominion belongs to the logged in user.
        *   - Must have zero protection ticks
        */

        $dominion = $this->getSelectedDominion();
        
        $this->guardActionsDuringTick($dominion);

        # Can only delete your own dominion.
        if($dominion->protection_ticks !== 0)
        {
            throw new LogicException('You cannot abandon a dominion which is still under protection.');
        }

        # Can only delete your own dominion.
        if($dominion->isLocked())
        {
            throw new LogicException('You cannot abandon a dominion that is locked or after a round is over.');
        }

        # Can only delete your own dominion.
        if($dominion->user_id !== Auth::user()->id)
        {
            throw new LogicException('You cannot abandon other dominions than your own.');
        }

        # Cannot release if units returning from invasion.
        $totalUnitsReturning = 0;
        for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
        {
            $totalUnitsReturning += $this->queueService->getInvasionQueueTotalByResource($dominion, "military_unit{$slot}");
        }
        if ($totalUnitsReturning !== 0)
        {
            throw new GameException('You cannot abandon your dominion while you have units returning from battle.');
        }

        $data = [
            'ruler_name' => $dominion->ruler_name,
            'ruler_title' => $dominion->title->name
        ];

        # Remove votes
        DB::table('dominions')->where('monarchy_vote_for_dominion_id', '=', $dominion->id)->update(['monarchy_vote_for_dominion_id' => null]);
        DB::table('realms')->where('monarch_dominion_id', '=', $dominion->id)->update(['monarch_dominion_id' => null]);

        # Change the ruler title
        DB::table('dominions')->where('id', '=', $dominion->id)->where('user_id', '=', Auth::user()->id)->update(['ruler_name' => ('Formerly ' . $dominion->ruler_name)]);
        DB::table('dominions')->where('id', '=', $dominion->id)->where('user_id', '=', Auth::user()->id)->update(['user_id' => null, 'former_user_id' => Auth::user()->id]);

        $this->dominionSelectorService->unsetUserSelectedDominion();

        # Abandon the dominion.
        $abandonDominionEvent = GameEvent::create([
            'round_id' => $dominion->round_id,
            'source_type' => Dominion::class,
            'source_id' => $dominion->id,
            'target_type' => NULL,
            'target_id' => NULL,
            'type' => 'abandon_dominion',
            'data' => $data,
            'tick' => $dominion->round->ticks
        ]);

        #$dominion->save(['event' => HistoryService::EVENT_ACTION_ABANDON]);

        Log::info(sprintf(
            'The dominion %s (ID %s) was abandoned by user %s (ID %s).',
            $dominion->name,
            $dominion->id,
            Auth::user()->display_name,
            Auth::user()->id
        ));

        return redirect()
            ->to(route('dashboard'))
            ->with(
                'alert-success',
                'Your dominion has been abandoned.'
            );
    }

    public function restoreDominionState(RestoreDominionStateRequest $request)
    {
        $dominion = $this->getSelectedDominion();

        $dominionState = DominionState::findOrFail($request->get('dominion_state'));

        $dominionStateService = app(DominionStateService::class);

        try {
            $result = $dominionStateService->restoreDominionState($dominion, $dominionState);
            $this->tickCalculator->precalculateTick($dominion);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        return redirect()->to($result['redirect'] ?? route('dominion.status'));

    }

}
