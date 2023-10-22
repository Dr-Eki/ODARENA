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
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\Title;
use OpenDominion\Models\User;

use OpenDominion\Helpers\DominionHelper;
use OpenDominion\Helpers\RoundHelper;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Services\Dominion\DominionStateService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\RealmFinderService;

# ODA
use OpenDominion\Models\GameEvent;

class RoundController extends AbstractController
{

    /** @var DominionFactory */
    protected $dominionFactory;

    /** @var DominionHelper */
    protected $dominionHelper;

    /** @var RoundHelper */
    protected $roundHelper;


    /**
     * RoundController constructor.
     *
     * @param DominionFactory $dominionFactory
     */
    public function __construct()
    {
        $this->dominionFactory = app(DominionFactory::class);
        $this->dominionHelper = app(DominionHelper::class);
        $this->roundHelper = app(RoundHelper::class);
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

        $races =$this->roundHelper->getRoundRaces($round)->sortBy('name');
        
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


        $countTitles = DB::table('dominions')
                            ->join('titles', 'dominions.title_id', '=', 'titles.id')
                            #->join('realms', 'realms.id', '=', 'dominions.realm_id')
                            ->select('titles.key as title', DB::raw('count(distinct dominions.id) as dominions'))
                            ->where('dominions.round_id', '=', $round->id)
                            ->groupBy('titles.key')
                            ->pluck('dominions', 'title')->all();

        $roundsPlayed = DB::table('dominions')
                            ->where('dominions.user_id', '=', Auth::user()->id)
                            ->where('dominions.protection_ticks', '=', 0)
                            ->count();

        $titles = Title::query()
            ->with(['perks'])
            ->where('enabled',1)
            ->orderBy('name')
            ->get();

        # Get packs for this round
        $packs = Pack::where('round_id', $round->id)->get();

        return view('pages.round.register', [
            'raceHelper' => app(RaceHelper::class),
            'roundHelper' => app(RoundHelper::class),
            'titleHelper' => app(TitleHelper::class),
            'round' => $round,
            'races' => $races,
            'countAlignment' => $countAlignment,
            'countRaces' => $countRaces,
            'countTitles' => $countTitles,
            'titles' => $titles,
            'roundsPlayed' => $roundsPlayed,
            'packs' => $packs,
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

        # Get packs for this round
        $packs = Pack::where('round_id', $round->id)->get();


        return view('pages.round.quickstart', [
            'raceHelper' => app(RaceHelper::class),
            'titleHelper' => app(TitleHelper::class),
            'round' => $round,
            'quickstarts' => $quickstarts,
            #'titles' => $titles,
            'packs' => $packs
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
            'quickstart' => 'required|exists:quickstarts,id',
        ]);

        $pack = null;
        if(in_array($round->mode, ['packs','packs-duration']))
        {
            # If pack is "random", get a random pack
            if($request['pack'] == 'random_public')
            {
                $pack = Pack::where('round_id', $round->id)
                    ->where('status', 1)
                    ->inRandomOrder()
                    ->first();
            }
            else
            {
                $this->validate($request, [
                    'pack' => 'required|exists:packs,id'
                ]);
    
                $pack = Pack::where('id', $request['pack'])->where('round_id', $round->id)->first();
            }

            # No pack found
            if(!$pack)
            {
                return redirect()->back()
                    ->withInput($request->all())
                    ->withErrors(['The pack you selected does not exist.']);
            }

            # Pack closed
            if($pack->status === 2)
            {
                return redirect()->back()
                    ->withInput($request->all())
                    ->withErrors(['The pack you selected is closed.']);
            }

            # Pack is private, so password must match
            if($pack->status === 0 and $pack->password != $request['pack_password'])
            {
                $this->validate($request, [
                    'pack_password' => 'required'
                ]);

                return redirect()->back()
                    ->withInput($request->all())
                    ->withErrors(['The password you entered for the pack is incorrect.']);
            }
        }

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
                    DB::transaction(function () use ($request, $round, &$realm, &$dominion, &$dominionName, $roundsPlayed, $countRaces, $eventData, $quickstart, $pack) {
                        $realmFinderService = app(RealmFinderService::class);
                        $realmFactory = app(RealmFactory::class);
        
                        /** @var User $user */
                        $user = Auth::user();
                        $race = $quickstart->race;
        
                        if (!$race->playable and $race->alignment !== 'npc')
                        {
                            throw new GameException('Invalid faction selection.');
                        }
        
                        if(!in_array(request()->getHost(), ['sim.odarena.com', 'odarena.local', 'odarena.virtual']))
                        {
                            if ($roundsPlayed < $race->minimum_rounds)
                            {
                                throw new GameException('You must have played at least ' . number_format($race->minimum_rounds) .  ' rounds to play ' . $race->name . '.');
                            }
        
                            if ($race->max_per_round and isset($countRaces[$race->name]))
                            {
                                if($countRaces[$race->name] >= $race->max_per_round)
                                {
                                    throw new GameException('There can only be ' . number_format($race->max_per_round) . ' of this faction per round.');
                                }
                            }
                
                            if(!$this->checkRaceRoundModes($race, $round))
                            {
                                throw new GameException($race->name . ' is not available in this round.');
                            }

                            # Check if that race is playable
                            if(!$race->playable)
                            {
                                throw new GameException($race->name . ' is not playable.');
                            }
                        }
        
                        $realm = $realmFinderService->findRealm($round, $race, $pack);
        
                        if (!$realm)
                        {
                            $realm = $realmFactory->create($round, $race->alignment);
                        }
        
                        $dominionName = $request->get('dominion_name');
        
                        if(!$this->dominionHelper->isAllowedDominionName($dominionName))
                        {
                            throw new GameException($dominionName . ' is not a permitted dominion name.');
                        }
        
                        $dominion = $this->dominionFactory->createFromQuickstart(
                            $user,
                            $realm,
                            $race,
                            ($request->get('ruler_name') ?: $user->display_name),
                            $dominionName,
                            $quickstart,
                            $pack
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
                    if(in_array(request()->getHost(), ['sim.odarena.com', 'odarena.local', 'odarena.virtual']))
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
        
                $request->session()->flash(
                    'alert-success',
                    ("You have successfully registered to round {$round->number} ({$round->name})! You have joined realm {$realm->number} ({$realm->name}) with " . ($realm->dominions()->count() - 1) . ' other ' . str_plural('dominion', ($realm->dominions()->count() - 1)) . '.')
                );

                $dominionStateService = app(DominionStateService::class);
                $dominionStateService->saveDominionState($dominion);
        
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

            
            if(in_array($round->mode,['factions','factions-duration']))
            {
                $races = $races =$this->roundHelper->getRoundRaces($round)
                    ->where('playable', 1)
                    ->pluck('id')->all();
            }
            elseif(in_array($round->mode,['packs','packs-duration']))
            {
                $races = $races =$this->roundHelper->getRoundRaces($round)
                    ->where('playable', 1)
                    ->pluck('id')->all();
            }
            else
            {
                $races = $races =$this->roundHelper->getRoundRaces($round)
                    ->where('alignment', 'like', $alignment)
                    ->where('playable', 1)
                    ->pluck('id')->all();
            }
            
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

        $pack = null;
        if(in_array($round->mode, ['packs','packs-duration']))
        {
            # If pack is "random", get a random pack
            if($request['pack'] == 'random_public')
            {
                $pack = Pack::where('round_id', $round->id)
                    ->where('status', 1)
                    ->inRandomOrder()
                    ->first();
            }
            else
            {
                $this->validate($request, [
                    'pack' => 'required|exists:packs,id'
                ]);
    
                $pack = Pack::where('id', $request['pack'])->where('round_id', $round->id)->first();
            }

            # No pack found
            if(!$pack)
            {
                return redirect()->back()
                    ->withInput($request->all())
                    ->withErrors(['The pack you selected does not exist.']);
            }

            # Pack closed
            if($pack->status === 2)
            {
                return redirect()->back()
                    ->withInput($request->all())
                    ->withErrors(['The pack you selected is closed.']);
            }

            # Pack is private, so password must match
            if($pack->status === 0 and $pack->password != $request['pack_password'])
            {
                $this->validate($request, [
                    'pack_password' => 'required'
                ]);

                return redirect()->back()
                    ->withInput($request->all())
                    ->withErrors(['The password you entered for the pack is incorrect.']);
            }
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
            DB::transaction(function () use ($request, $round, &$realm, &$dominion, &$dominionName, $roundsPlayed, $countRaces, $eventData, $pack) {
                $realmFinderService = app(RealmFinderService::class);
                $realmFactory = app(RealmFactory::class);

                /** @var User $user */
                $user = Auth::user();
                $race = Race::findOrFail($request->get('race'));
                $title = Title::findOrFail($request->get('title'));

                if (!$race->playable and $race->alignment !== 'npc')
                {
                    throw new GameException('Invalid race selection');
                }

                if(!in_array(request()->getHost(), ['sim.odarena.com', 'odarena.local', 'odarena.virtual']))
                {
                    if ($roundsPlayed < $race->rounds_played)
                    {
                        throw new GameException('You must have played at least ' . number_format($race->rounds_played) .  ' rounds to play ' . $race->name . '.');
                    }

                    if ($race->max_per_round and isset($countRaces[$race->name]))
                    {
                        if($countRaces[$race->name] >= $race->max_per_round)
                        {
                            throw new GameException('There can only be ' . number_format($race->max_per_round) . ' of this faction per round.');
                        }
                    }
                
                    if(!$this->checkRaceRoundModes($race, $round))
                    {
                        throw new GameException($race->name . ' is not available in this round.');
                    }
                }

                $realm = $realmFinderService->findRealm($round, $race, $pack);

                #if (!$realm)
                #{
                #    $realm = $realmFactory->create($round, $race->alignment);
                #}

                $dominionName = $request->get('dominion_name');

                if(!$this->dominionHelper->isAllowedDominionName($dominionName))
                {
                    throw new GameException($dominionName . ' is not a permitted dominion name.');
                }

                $dominion = $this->dominionFactory->create(
                    $user,
                    $realm,
                    $race,
                    $title,
                    ($request->get('ruler_name') ?: $user->display_name),
                    $dominionName,
                    $pack
                );

                # Get max pop
                $populationCalculator = app(PopulationCalculator::class);
                $startingPeasants = $populationCalculator->getMaxPopulation($dominion) - $populationCalculator->getPopulationMilitary($dominion);

                $dominion->save([
                    'peasants' => $startingPeasants,
                ]);

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
            if(in_array(request()->getHost(), ['sim.odarena.com', 'odarena.local', 'odarena.virtual']))
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

        $request->session()->flash(
            'alert-success',
            ("You have successfully registered to round {$round->number} ({$round->name})! You have joined realm {$realm->number} ({$realm->name}) with " . ($realm->dominions()->count() - 1) . ' other ' . str_plural('dominion', ($realm->dominions()->count() - 1)) . '.')
        );

        $dominionStateService = app(DominionStateService::class);
        $dominionStateService->saveDominionState($dominion);

        return redirect()->route('dominion.status');
    }

    public function getCreatePack(Round $round)
    {
        try {
            $this->guardAgainstUserAlreadyHavingDominionInRound($round);
            $this->guardAgainstUserAlreadyHavingCreatedAPack($round);
        } catch (GameException $e) {
            return redirect()
                ->route('round.register', $round)
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.round.create-pack', [
            'roundHelper' => app(RoundHelper::class),
            'round' => $round,
            'user' => Auth::user(),
        ]);
    }

    public function postCreatePack(Request $request, Round $round)
    {

        $password = $request->get('pack_password');
        $status = intval($request->get('status')) ?? 0;

        try {
            if(empty($password) and $status !== 1)
            {
                throw new GameException("Password is required if pack status is not Public.");
            }

            if(!in_array($status, [0,1,2]))
            {
                throw new GameException("Invalid pack status.");
            }

            $this->guardAgainstUserAlreadyHavingDominionInRound($round);
            $this->guardAgainstUserAlreadyHavingCreatedAPack($round);
        } catch (GameException $e) {
            return redirect()
                ->route('dashboard')
                ->withErrors([$e->getMessage()]);
        }

        $realmFactory = app(RealmFactory::class);

        $user = Auth::user();

        $realm = $realmFactory->create($round, 'pack');

        Pack::create([
            'round_id' => $round->id,
            'user_id' => $user->id,
            'realm_id' => $realm->id,
            'password' => $password,
            'status' => $status,
        ]);

        $realmName = $user->display_name . ($user->display_name[strlen($user->display_name) - 1] == 's' ? "'" : "'s" ) . ' Pack';
        $realm->update([
            'name' => $realmName,
        ]);

        $request->session()->flash(
            'alert-success',
            ('Your pack has been created.')
        );

        return redirect()->route('round.register', $round);
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

    /**
     * Throws exception if logged in user already has a pack this round.
     *
     * @param Round $round
     * @throws GameException
     */
    protected function guardAgainstUserAlreadyHavingCreatedAPack(Round $round): void
    {
        $dominions = Pack::where([
            'user_id' => Auth::user()->id,
            'round_id' => $round->id,
        ])->get();

        if (!$dominions->isEmpty()) {
            throw new GameException("You have already created a pack in round {$round->number}");
        }
    }

    /**
     * Throws exception if logged in user already has a pack this round.
     *
     * @param Round $round
     * @throws GameException
     */
    protected function guardAgainstUserHavingCreatedAPackButJoiningAnother(Round $round): void
    {
        $dominions = Pack::where([
            'user_id' => Auth::user()->id,
            'round_id' => $round->id,
        ])->get();

        if (!$dominions->isEmpty()) {
            throw new GameException("You have already created a pack in round {$round->number}");
        }
    }


    protected function checkRaceRoundModes(Race $race, Round $round): bool
    {
        if(env('APP_ENV') == 'local' or request()->getHost() == 'sim.odarena.com')
        {
            return true;
        }

        return in_array($round->mode, $race->round_modes);
    }

}
