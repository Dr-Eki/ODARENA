<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use LogicException;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\TitleHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Title;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\PackService;
use OpenDominion\Services\RealmFinderService;

# ODA
use OpenDominion\Models\GameEvent;

class RoundController extends AbstractController
{
    /** @var DominionFactory */
    protected $dominionFactory;

    /** @var PackService */
    protected $packService;

    /** @var GameEvent */
    protected $newDominionEvent;
    /**
     * RoundController constructor.
     *
     * @param DominionFactory $dominionFactory
     * @param PackService $packService
     */
    public function __construct(DominionFactory $dominionFactory, PackService $packService)
    {
        $this->dominionFactory = $dominionFactory;
        $this->packService = $packService;
        #$this->newDominionEvent = $newDominionEvent;
    }

    public function getRegister(Round $round)
    {
        try {
            $this->guardAgainstUserAlreadyHavingDominionInRound($round);
        } catch (GameException $e) {
            return redirect()
                ->route('dashboard')
                ->withErrors([$e->getMessage()]);
        }

        $races = Race::query()
            ->with(['perks'])
            ->orderBy('name')
            ->get();

        $countAlignment = DB::table('dominions')
                            ->join('races', 'dominions.race_id', '=', 'races.id')
                            ->join('realms', 'realms.id', '=', 'dominions.realm_id')
                            ->select('realms.alignment as alignment', DB::raw('count(distinct dominions.id) as dominions'))
                            ->where('dominions.round_id', '=', $round->id)
                            ->groupBy('realms.alignment')
                            ->pluck('dominions', 'alignment')->all();


        $countRaces = DB::table('dominions')
                            ->join('races', 'dominions.race_id', '=', 'races.id')
                            ->join('realms', 'realms.id', '=', 'dominions.realm_id')
                            ->select('races.name as race', DB::raw('count(distinct dominions.id) as dominions'))
                            ->where('dominions.round_id', '=', $round->id)
                            ->groupBy('races.name')
                            ->pluck('dominions', 'race')->all();

        $roundsPlayed = DB::table('dominions')
                            ->where('dominions.user_id', '=', Auth::user()->id)
                            ->where('dominions.protection_ticks', '=', 0)
                            ->count();

        $titles = Title::query()
            ->with(['perks'])
            ->where('enabled',1)
            ->orderBy('name')
            ->get();

        $races = Race::query()
            ->with(['perks'])
            ->orderBy('name')
            ->get();

        return view('pages.round.register', [
            'raceHelper' => app(RaceHelper::class),
            'titleHelper' => app(TitleHelper::class),
            'round' => $round,
            'races' => $races,
            'countAlignment' => $countAlignment,
            'countRaces' => $countRaces,
            'titles' => $titles,
            'roundsPlayed' => $roundsPlayed,
            #'countEmpire' => $countEmpire,
            #'countCommonwealth' => $countCommonwealth,
            #'alignmentCounter' => $alignmentCounter,
        ]);
    }

    public function postRegister(Request $request, Round $round)
    {
        try {
            $this->guardAgainstUserAlreadyHavingDominionInRound($round);
        } catch (GameException $e) {
            return redirect()
                ->route('dashboard')
                ->withErrors([$e->getMessage()]);
        }

        // todo: make this its own FormRequest class? Might be hard due to depending on $round->pack_size, needs investigating
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->validate($request, [
            'dominion_name' => 'required|string|min:3|max:50',
            'ruler_name' => 'nullable|string|max:50',
            'race' => 'required|exists:races,id',
            'title' => 'required|exists:titles,id',
            'realm_type' => 'in:random,join_pack,create_pack',
            'pack_name' => ('string|min:3|max:50|' . ($request->get('realm_type') !== 'random' ? 'required_if:realm,join_pack,create_pack' : 'nullable')),
            'pack_password' => ('string|min:3|max:50|' . ($request->get('realm_type') !== 'random' ? 'required_if:realm,join_pack,create_pack' : 'nullable')),
            'pack_size' => "integer|min:2|max:{$round->pack_size}|required_if:realm,create_pack",
        ]);

        $roundsPlayed = DB::table('dominions')
                            ->where('dominions.user_id', '=', Auth::user()->id)
                            ->where('dominions.protection_ticks', '=', 0)
                            ->count();

        $countRaces = DB::table('dominions')
                            ->join('races', 'dominions.race_id', '=', 'races.id')
                            ->join('realms', 'realms.id', '=', 'dominions.realm_id')
                            ->select('races.name as race', DB::raw('count(distinct dominions.id) as dominions'))
                            ->where('dominions.round_id', '=', $round->id)
                            ->groupBy('races.name')
                            ->pluck('dominions', 'race')->all();

        /** @var Realm $realm */
        $realm = null;

        /** @var Dominion $dominion */
        $dominion = null;

        /** @var string $dominionName */
        $dominionName = null;

        try {
            DB::transaction(function () use ($request, $round, &$realm, &$dominion, &$dominionName, $roundsPlayed, $countRaces) {
                $realmFinderService = app(RealmFinderService::class);
                $realmFactory = app(RealmFactory::class);

                /** @var User $user */
                $user = Auth::user();
                $race = Race::findOrFail($request->get('race'));
                $title = Title::findOrFail($request->get('title'));
                $pack = null;

                if (!$race->playable and $race->alignment !== 'npc')
                {
                    throw new GameException('Invalid race selection');
                }

                if(request()->getHost() !== 'sim.odarena.com' and request()->getHost() !== 'odarena.local')
                {
                    if ($roundsPlayed < $race->getPerkValue('min_rounds_played'))
                    {
                        throw new GameException('You must have played at least ' . number_format($race->getPerkValue('min_rounds_played')) .  ' rounds to play ' . $race->name . '.');
                    }

                    if ($race->getPerkValue('max_per_round') and isset($countRaces[$race->name]))
                    {
                        if($countRaces[$race->name] >= $race->getPerkValue('max_per_round'))
                        {
                            throw new GameException('There can only be ' . number_format($race->getPerkValue('max_per_round')) . ' of this faction per round.');
                        }
                    }
                }

                /*
                switch ($request->get('realm_type')) {
                    case 'random':
                        $realm = $realmFinderService->findRandomRealm($round, $race);
                        break;

                    case 'join_pack':
                        $pack = $this->packService->getPack(
                            $round,
                            $request->get('pack_name'),
                            $request->get('pack_password'),
                            $race
                        );

                        $realm = $pack->realm;
                        break;

                    case 'create_pack':
                        $realm = $realmFinderService->findRandomRealm(
                            $round,
                            $race,
                            $request->get('pack_size'),
                            true
                        );
                        break;

                    default:
                        throw new LogicException('Unsupported realm type');
                }
                */

                $realm = $realmFinderService->findRandomRealm($round, $race);

                if (!$realm) {
                    $realm = $realmFactory->create($round, $race->alignment);
                }

                $dominionName = $request->get('dominion_name');


                if(!$this->allowedDominionName($dominionName))
                {
                    throw new GameException('To avoid confusion, ' . $dominionName . ' is not a permitted dominion name. It contains a name reserved for Barbarians.');
                }

                $dominion = $this->dominionFactory->create(
                    $user,
                    $realm,
                    $race,
                    $title,
                    ($request->get('ruler_name') ?: $user->display_name),
                    $dominionName,
                    $pack,
                    $title
                );


                $this->newDominionEvent = GameEvent::create([
                    'round_id' => $dominion->round_id,
                    'source_type' => Dominion::class,
                    'source_id' => $dominion->id,
                    'target_type' => Realm::class,
                    'target_id' => $dominion->realm_id,
                    'type' => 'new_dominion',
                    'data' => NULL,
                ]);

                if ($request->get('realm_type') === 'create_pack') {
                    $pack = $this->packService->createPack(
                        $dominion,
                        $request->get('pack_name'),
                        $request->get('pack_password'),
                        $request->get('pack_size')
                    );

                    $dominion->pack_id = $pack->id;
                    $dominion->save();

                    $pack->realm_id = $realm->id;
                    $pack->save();
                }
            });

        } catch (QueryException $e) {

            # Useful for debugging.
            if(request()->getHost() === 'odarena.local')
            {
                dd($e->getMessage());
            }


            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(["Someone already registered a dominion with the name '{$dominionName}' for this round."]);

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        if ($round->isActive()) {
            $dominionSelectorService = app(SelectorService::class);
            $dominionSelectorService->selectUserDominion($dominion);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'round',
            'register',
            (string)$round->number
        ));

        $request->session()->flash(
            'alert-success',
            ("You have successfully registered to round {$round->number} ({$round->name})! You have been placed in realm {$realm->number} ({$realm->name}) with " . ($realm->dominions()->count() - 1) . ' other ' . str_plural('dominion', ($realm->dominions()->count() - 1)) . '.')
        );

        return redirect()->route('dominion.status');
    }

    /**
     * Throws exception if logged in user already has a dominion a round.
     *
     * @param Round $round
     * @throws GameException
     */
    protected function guardAgainstUserAlreadyHavingDominionInRound(Round $round): void
    {
        // todo: make this a route middleware instead

        $dominions = Dominion::where([
            'user_id' => Auth::user()->id,
            'round_id' => $round->id,
        ])->get();

        if (!$dominions->isEmpty()) {
            throw new GameException("You already have a dominion in round {$round->number}");
        }
    }

    protected function allowedDominionName(string $dominionName): bool
    {
        $barbarianUsers = DB::table('users')
            ->where('users.email', 'like', 'bandit%@lykanthropos.com')
            ->pluck('users.id')
            ->toArray();

        foreach($barbarianUsers as $barbarianUserId)
        {
            $barbarianUser = User::findorfail($barbarianUserId);

            if(stristr($dominionName, $barbarianUser->display_name))
            {
                return false;
            }

        }

        return true;
    }

}
