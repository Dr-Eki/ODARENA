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
use OpenDominion\Models\Quickstart;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\Title;
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

    public function getQuickstart(Round $round)
    {
        try {
            $this->guardAgainstUserAlreadyHavingDominionInRound($round);
        } catch (GameException $e) {
            return redirect()
                ->route('dashboard')
                ->withErrors([$e->getMessage()]);
        }

        $quickstarts = Quickstart::query()
            ->orderBy('name')
            ->get();

        $titles = Title::query()
            ->with(['perks'])
            ->where('enabled',1)
            ->orderBy('name')
            ->get();


        return view('pages.round.quickstart', [
            'raceHelper' => app(RaceHelper::class),
            'titleHelper' => app(TitleHelper::class),
            'round' => $round,
            'quickstarts' => $quickstarts,
            'titles' => $titles
        ]);
    }

    public function postQuickstart(Request $request, Round $round)
    {
        try {
            $this->guardAgainstUserAlreadyHavingDominionInRound($round);
        } catch (GameException $e) {
            return redirect()
                ->route('dashboard')
                ->withErrors([$e->getMessage()]);
        }

        $this->validate($request, [
            'dominion_name' => 'required|string|min:3|max:50',
            'ruler_name' => 'nullable|string|max:50',
            'title' => 'required|exists:titles,id',
            'quickstart' => 'required|exists:quickstarts,id',
        ]);

        $quickstart = Quickstart::where('id', $request['quickstart'])->first();

        $race = $quickstart->race;

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

                $eventData = [
                    'quickstart' => true
                ];
        
                try {
                    DB::transaction(function () use ($request, $round, &$realm, &$dominion, &$dominionName, $roundsPlayed, $countRaces, $eventData, $quickstart) {
                        $realmFinderService = app(RealmFinderService::class);
                        $realmFactory = app(RealmFactory::class);
        
                        /** @var User $user */
                        $user = Auth::user();
                        $race = $quickstart->race;
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
        
                        $realm = $realmFinderService->findRealm($round, $race);
        
                        if (!$realm)
                        {
                            $realm = $realmFactory->create($round, $race->alignment);
                        }
        
                        $dominionName = $request->get('dominion_name');
        
                        if(!$this->isAllowedDominionName($dominionName))
                        {
                            throw new GameException($dominionName . ' is not a permitted dominion name.');
                        }
        
                        $dominion = $this->dominionFactory->createFromQuickstart(
                            $user,
                            $realm,
                            $race,
                            ($request->get('ruler_name') ?: $user->display_name),
                            $dominionName,
                            $quickstart
                        );
        
                        $this->newDominionEvent = GameEvent::create([
                            'round_id' => $dominion->round_id,
                            'source_type' => Dominion::class,
                            'source_id' => $dominion->id,
                            'target_type' => Realm::class,
                            'target_id' => $dominion->realm_id,
                            'type' => 'new_dominion',
                            'data' => $eventData,
                            'tick' => $dominion->round->ticks
                        ]);
                    });
        
                } catch (QueryException $e) {
        
                    # Useful for debugging.
                    if(request()->getHost() === 'odarena.local' or request()->getHost() === 'sim.odarena.com')
                    {
                        dd($e->getMessage());
                    }
        
                    return redirect()->back()
                        ->withInput($request->all())
                        ->withErrors(["Someone already registered a dominion with the name '{$dominionName}' for this round, or another error occurred. Please note that emojis are not considered unique characters, so to ensure uniqueness, normal characters or number of emojis must be unique."]);
        
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
                    ("You have successfully registered to round {$round->number} ({$round->name})! You have joined realm {$realm->number} ({$realm->name}) with " . ($realm->dominions()->count() - 1) . ' other ' . str_plural('dominion', ($realm->dominions()->count() - 1)) . '.')
                );
        
                return redirect()->route('dominion.status');

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

        $eventData = [
            'random_faction' => false,
            'real_ruler_name' => false
        ];

        if(in_array($request['race'], ['random_any', 'random_evil', 'random_good', 'random_independent']))
        {

            $this->validate($request, [
                'dominion_name' => 'required|string|min:3|max:50',
                'ruler_name' => 'nullable|string|max:50',
                'title' => 'required|exists:titles,id',
            ]);

            $alignment = str_replace('random_', '', $request['race']);
            $alignment = str_replace('npc', 'any', $alignment);
            $alignment = str_replace('any', '%', $alignment);

            $races = DB::table('races')
                      ->where('alignment', 'like', $alignment)
                      ->where('playable', 1)
                      ->pluck('id')->all();

            $request['race'] = $races[array_rand($races)];

            $eventData['random_faction'] = true;
        }
        else
        {
            $this->validate($request, [
                'dominion_name' => 'required|string|min:3|max:50',
                'ruler_name' => 'nullable|string|max:50',
                'race' => 'required|exists:races,id',
                'title' => 'required|exists:titles,id',
            ]);
        }

        if($request['ruler_name'] == Auth::user()->display_name)
        {
            $eventData['real_ruler_name'] = true;
        }

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
            DB::transaction(function () use ($request, $round, &$realm, &$dominion, &$dominionName, $roundsPlayed, $countRaces, $eventData) {
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

                $realm = $realmFinderService->findRealm($round, $race);

                if (!$realm)
                {
                    $realm = $realmFactory->create($round, $race->alignment);
                }

                $dominionName = $request->get('dominion_name');

                if(!$this->isAllowedDominionName($dominionName))
                {
                    throw new GameException($dominionName . ' is not a permitted dominion name.');
                }

                $dominion = $this->dominionFactory->create(
                    $user,
                    $realm,
                    $race,
                    $title,
                    ($request->get('ruler_name') ?: $user->display_name),
                    $dominionName
                );

                $this->newDominionEvent = GameEvent::create([
                    'round_id' => $dominion->round_id,
                    'source_type' => Dominion::class,
                    'source_id' => $dominion->id,
                    'target_type' => Realm::class,
                    'target_id' => $dominion->realm_id,
                    'type' => 'new_dominion',
                    'data' => $eventData,
                    'tick' => $dominion->round->ticks
                ]);
            });

        } catch (QueryException $e) {

            # Useful for debugging.
            if(request()->getHost() === 'odarena.local' or request()->getHost() === 'sim.odarena.com')
            {
                dd($e->getMessage());
            }

            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(["Someone already registered a dominion with the name '{$dominionName}' for this round, or another error occurred. Please note that emojis are not considered unique characters, so to ensure uniqueness, normal characters or number of emojis must be unique."]);

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
            ("You have successfully registered to round {$round->number} ({$round->name})! You have joined realm {$realm->number} ({$realm->name}) with " . ($realm->dominions()->count() - 1) . ' other ' . str_plural('dominion', ($realm->dominions()->count() - 1)) . '.')
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

    protected function isAllowedDominionName(string $dominionName): bool
    {
        $barbarianUsers = DB::table('users')
            ->where('users.email', 'like', 'barbarian%@odarena.com')
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
