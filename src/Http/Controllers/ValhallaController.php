<?php

namespace OpenDominion\Http\Controllers;

use Illuminate\Http\Response;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;

use OpenDominion\Helpers\RealmHelper;

class ValhallaController extends AbstractController
{
    public function getIndex()
    {
        $rounds = Round::with('league')->orderBy('start_date', 'desc')->get();

        return view('pages.valhalla.index', [
            'rounds' => $rounds,
        ]);
    }

    public function getRound(Round $round)
    {
        if ($response = $this->guardAgainstActiveRound($round)) {
            return $response;
        }

        $races = $round->dominions
            ->sortBy('race.name')
            ->pluck('race.name', 'race.id')
            ->unique();

        return view('pages.valhalla.round', [
            'round' => $round,
            'races' => $races,
        ]);
    }

    public function getRoundType(Round $round, string $type)
    {
        if ($response = $this->guardAgainstActiveRound($round)) {
            return $response;
        }

        // todo: refactor

        $headers = [
            '#' => ['width' => 50, 'align-center' => true],
            'player' => ['width' => 150, 'align-center' => true],
            'players' => ['align-center' => true],
            'faction' => ['width' => 100, 'align-center' => true],
            'realm' => ['width' => 100, 'align-center' => true],
            'alignment' => ['width' => 100, 'align-center' => true],
            'number' => ['width' => 50, 'align-center' => true],
            'networth' => ['width' => 150, 'align-center' => true],
            'avg_networth' => ['width' => 150, 'align-center' => true],
            'land' => ['width' => 150, 'align-center' => true],
            'avg_land' => ['width' => 150, 'align-center' => true],
            'value' => ['width' => 100, 'align-center' => true],
        ];

        switch ($type) {
            case 'strongest-dominions': $data = $this->getStrongestDominions($round); break;
            case 'strongest-good-dominions': $data = $this->getStrongestDominions($round, null, 'good'); break;
            case 'strongest-evil-dominions': $data = $this->getStrongestDominions($round, null, 'evil'); break;
            case 'strongest-realms': $data = $this->getStrongestRealms($round); break;
            case 'strongest-good-realms': $data = $this->getStrongestRealms($round, 'good'); break;
            case 'strongest-evil-realms': $data = $this->getStrongestRealms($round, 'evil'); break;
            case 'strongest-packs': $data = $this->getStrongestPacks($round); break;
            case 'largest-dominions': $data = $this->getLargestDominions($round); break;
            case 'largest-good-dominions': $data = $this->getLargestDominions($round, null, 'good'); break;
            case 'largest-evil-dominions': $data = $this->getLargestDominions($round, null, 'evil'); break;
            case 'largest-realms': $data = $this->getLargestRealms($round); break;
            case 'largest-good-realms': $data = $this->getLargestRealms($round, 'good'); break;
            case 'largest-evil-realms': $data = $this->getLargestRealms($round, 'evil'); break;
            case 'largest-packs': $data = $this->getLargestPacks($round); break;
            case 'stat-prestige': $data = $this->getDominionsByStatistic($round, 'prestige'); break;
            case 'stat-attacking-success': $data = $this->getDominionsByStatistic($round, 'stat_attacking_success'); break;
            case 'stat-defending-success': $data = $this->getDominionsByStatistic($round, 'stat_defending_success'); break;
            case 'stat-espionage-success': $data = $this->getDominionsByStatistic($round, 'stat_espionage_success'); break;
            case 'stat-spell-success': $data = $this->getDominionsByStatistic($round, 'stat_spell_success'); break;
            case 'stat-total-gold-production': $data = $this->getDominionsByStatistic($round, 'stat_total_gold_production'); break;
            case 'stat-total-food-production': $data = $this->getDominionsByStatistic($round, 'stat_total_food_production'); break;
            case 'stat-total-lumber-production': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_production'); break;
            case 'stat-total-mana-production': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_production'); break;
            case 'stat-total-ore-production': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_production'); break;
            case 'stat-total-gem-production': $data = $this->getDominionsByStatistic($round, 'stat_total_gem_production'); break;
            case 'stat-total-tech-production': $data = $this->getDominionsByStatistic($round, 'stat_total_tech_production'); break;
            case 'stat-total-boat-production': $data = $this->getDominionsByStatistic($round, 'stat_total_boat_production'); break;
            case 'stat-total-land-explored': $data = $this->getDominionsByStatistic($round, 'stat_total_land_explored'); break;
            case 'stat-total-land-discovered': $data = $this->getDominionsByStatistic($round, 'stat_total_land_discovered'); break;
            case 'stat-total-land-conquered': $data = $this->getDominionsByStatistic($round, 'stat_total_land_conquered'); break;
            case 'stat-total-gold-stolen': $data = $this->getDominionsByStatistic($round, 'stat_total_gold_stolen'); break;
            case 'stat-total-food-stolen': $data = $this->getDominionsByStatistic($round, 'stat_total_food_stolen'); break;
            case 'stat-total-lumber-stolen': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_stolen'); break;
            case 'stat-total-mana-stolen': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_stolen'); break;
            case 'stat-total-ore-stolen': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_stolen'); break;
            case 'stat-total-gems-stolen': $data = $this->getDominionsByStatistic($round, 'stat_total_gems_stolen'); break;
            case 'stat-top-saboteurs': $data = $this->getDominionsByStatistic($round, 'stat_sabotage_boats_damage'); break;
            case 'stat-top-magical-assassins': $data = $this->getDominionsByStatistic($round, 'stat_assassinate_wizards_damage'); break;
            case 'stat-top-military-assassins': $data = $this->getDominionsByStatistic($round, 'stat_assassinate_draftees_damage'); break;
            case 'stat-top-snare-setters': $data = $this->getDominionsByStatistic($round, 'stat_magic_snare_damage'); break;
            case 'stat-masters-of-fire': $data = $this->getDominionsByStatistic($round, 'stat_fireball_damage'); break;
            case 'stat-masters-of-plague': $data = $this->getDominionsByStatistic($round, 'stat_plague_hours'); break;
            case 'stat-masters-of-swarm': $data = $this->getDominionsByStatistic($round, 'stat_insect_swarm_hours'); break;
            case 'stat-masters-of-lightning': $data = $this->getDominionsByStatistic($round, 'stat_lightning_bolt_damage'); break;
            case 'stat-masters-of-water': $data = $this->getDominionsByStatistic($round, 'stat_water_hours'); break;
            case 'stat-masters-of-earth': $data = $this->getDominionsByStatistic($round, 'stat_earthquake_hours'); break;
            case 'stat-top-spy-disbanders': $data = $this->getDominionsByStatistic($round, 'stat_disband_spies_damage'); break;

            # ODA NEW
            case 'stat-total-units-killed': $data = $this->getDominionsByStatistic($round, 'stat_total_units_killed'); break;
            case 'stat-total-units-converted': $data = $this->getDominionsByStatistic($round, 'stat_total_units_converted'); break;
            case 'stat-total-land-lost': $data = $this->getDominionsByStatistic($round, 'stat_total_land_lost'); break;


            case 'stat-total-gold-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_gold_spent_training'); break;
            case 'stat-total-gold-spent-building': $data = $this->getDominionsByStatistic($round, 'stat_total_gold_spent_building'); break;
            case 'stat-total-gold-spent-rezoning': $data = $this->getDominionsByStatistic($round, 'stat_total_gold_spent_rezoning'); break;
            case 'stat-total-gold-spent-exploring': $data = $this->getDominionsByStatistic($round, 'stat_total_gold_spent_exploring'); break;
            case 'stat-total-gold-spent-improving': $data = $this->getDominionsByStatistic($round, 'stat_total_gold_spent_improving'); break;
            case 'stat-total-gold-plundered': $data = $this->getDominionsByStatistic($round, 'stat_total_gold_plundered'); break;
            case 'stat-total-gold-salvaged': $data = $this->getDominionsByStatistic($round, 'stat_total_gold_salvaged'); break;
            case 'stat-total-gold-sold': $data = $this->getDominionsByStatistic($round, 'stat_total_gold_sold'); break;
            case 'stat-total-gold-bought': $data = $this->getDominionsByStatistic($round, 'stat_total_gold_bought'); break;

            case 'stat-total-food-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_food_spent_training'); break;
            case 'stat-total-food-spent-building': $data = $this->getDominionsByStatistic($round, 'stat_total_food_spent_building'); break;
            case 'stat-total-food-spent-rezoning': $data = $this->getDominionsByStatistic($round, 'stat_total_food_spent_rezoning'); break;
            case 'stat-total-food-spent-exploring': $data = $this->getDominionsByStatistic($round, 'stat_total_food_spent_exploring'); break;
            case 'stat-total-food-spent-improving': $data = $this->getDominionsByStatistic($round, 'stat_total_food_spent_improving'); break;
            case 'stat-total-food-plundered': $data = $this->getDominionsByStatistic($round, 'stat_total_food_plundered'); break;
            case 'stat-total-food-salvaged': $data = $this->getDominionsByStatistic($round, 'stat_total_food_salvaged'); break;
            case 'stat-total-food-decayed': $data = $this->getDominionsByStatistic($round, 'stat_total_food_decayed'); break;
            case 'stat-total-food-consumed': $data = $this->getDominionsByStatistic($round, 'stat_total_food_consumed'); break;
            case 'stat-total-food-sold': $data = $this->getDominionsByStatistic($round, 'stat_total_food_sold'); break;
            case 'stat-total-food-bought': $data = $this->getDominionsByStatistic($round, 'stat_total_food_bought'); break;

            case 'stat-total-ore-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_spent_training'); break;
            case 'stat-total-ore-spent-building': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_spent_building'); break;
            case 'stat-total-ore-spent-rezoning': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_spent_rezoning'); break;
            case 'stat-total-ore-spent-exploring': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_spent_exploring'); break;
            case 'stat-total-ore-spent-improving': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_spent_improving'); break;
            case 'stat-total-ore-plundered': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_plundered'); break;
            case 'stat-total-ore-salvaged': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_salvaged'); break;
            case 'stat-total-ore-sold': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_sold'); break;
            case 'stat-total-ore-bought': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_bought'); break;

            case 'stat-total-lumber-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_spent_training'); break;
            case 'stat-total-lumber-spent-building': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_spent_building'); break;
            case 'stat-total-lumber-spent-rezoning': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_spent_rezoning'); break;
            case 'stat-total-lumber-spent-exploring': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_spent_exploring'); break;
            case 'stat-total-lumber-spent-improving': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_spent_improving'); break;
            case 'stat-total-lumber-plundered': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_plundered'); break;
            case 'stat-total-lumber-salvaged': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_salvaged'); break;
            case 'stat-total-lumber-rotted': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_rotted'); break;
            case 'stat-total-lumber-sold': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_sold'); break;
            case 'stat-total-lumber-bought': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_bought'); break;

            case 'stat-total-gem-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_gem_spent_training'); break;
            case 'stat-total-gem-spent-building': $data = $this->getDominionsByStatistic($round, 'stat_total_gem_spent_building'); break;
            case 'stat-total-gem-spent-rezoning': $data = $this->getDominionsByStatistic($round, 'stat_total_gem_spent_rezoning'); break;
            case 'stat-total-gem-spent-exploring': $data = $this->getDominionsByStatistic($round, 'stat_total_gem_spent_exploring'); break;
            case 'stat-total-gem-spent-improving': $data = $this->getDominionsByStatistic($round, 'stat_total_gem_spent_improving'); break;
            case 'stat-total-gem-plundered': $data = $this->getDominionsByStatistic($round, 'stat_total_gems_plundered'); break;
            case 'stat-total-gem-salvaged': $data = $this->getDominionsByStatistic($round, 'stat_total_gem_salvaged'); break;
            case 'stat-total-gem-sold': $data = $this->getDominionsByStatistic($round, 'stat_total_gems_sold'); break;

            case 'stat-total-mana-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_spent_training'); break;
            case 'stat-total-mana-spent-building': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_spent_building'); break;
            case 'stat-total-mana-spent-rezoning': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_spent_rezoning'); break;
            case 'stat-total-mana-spent-exploring': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_spent_exploring'); break;
            case 'stat-total-mana-spent-improving': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_spent_improving'); break;
            case 'stat-total-mana-plundered': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_plundered'); break;
            #case 'stat-total-mana-salvaged': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_salvaged'); break;
            case 'stat-total-mana-drained': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_drained'); break;
            case 'stat-total-mana-cast': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_cast'); break;

            case 'stat-total-champion-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_champion_spent_training'); break;
            case 'stat-total-soul-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_soul_spent_training'); break;
            case 'stat-total-soul-spent-improving': $data = $this->getDominionsByStatistic($round, 'stat_total_soul_spent_improving'); break;

            case 'stat-total-unit1-trained': $data = $this->getDominionsByStatistic($round, 'stat_total_unit1_trained'); break;
            case 'stat-total-unit1-lost': $data = $this->getDominionsByStatistic($round, 'stat_total_unit1_lost'); break;
            case 'stat-total-unit1-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_unit1_spent_training'); break;

            case 'stat-total-unit2-trained': $data = $this->getDominionsByStatistic($round, 'stat_total_unit2_trained'); break;
            case 'stat-total-unit2-lost': $data = $this->getDominionsByStatistic($round, 'stat_total_unit2_lost'); break;
            case 'stat-total-unit2-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_unit2_spent_training'); break;

            case 'stat-total-unit3-trained': $data = $this->getDominionsByStatistic($round, 'stat_total_unit3_trained'); break;
            case 'stat-total-unit3-lost': $data = $this->getDominionsByStatistic($round, 'stat_total_unit3_lost'); break;
            case 'stat-total-unit3-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_unit3_spent_training'); break;

            case 'stat-total-unit4-trained': $data = $this->getDominionsByStatistic($round, 'stat_total_unit4_trained'); break;
            case 'stat-total-unit4-lost': $data = $this->getDominionsByStatistic($round, 'stat_total_unit4_lost'); break;
            case 'stat-total-unit4-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_unit4_spent_training'); break;

            case 'stat-total-spies-trained': $data = $this->getDominionsByStatistic($round, 'stat_total_spies_trained'); break;
            case 'stat-total-spies-lost': $data = $this->getDominionsByStatistic($round, 'stat_total_spies_lost'); break;
            case 'stat-total-spies-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_spies_spent_training'); break;

            case 'stat-total-wizards-trained': $data = $this->getDominionsByStatistic($round, 'stat_total_wizards_trained'); break;
            case 'stat-total-wizards-lost': $data = $this->getDominionsByStatistic($round, 'stat_total_wizards_lost'); break;
            case 'stat-total-wizards-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_wizards_spent_training'); break;

            case 'stat-total-archmages-trained': $data = $this->getDominionsByStatistic($round, 'stat_total_archmages_trained'); break;
            case 'stat-total-archmages-lost': $data = $this->getDominionsByStatistic($round, 'stat_total_archmages_lost'); break;
            case 'stat-total-archmages-spent-training': $data = $this->getDominionsByStatistic($round, 'stat_total_archmages_spent_training'); break;



            default:
                if (!preg_match('/(strongest|largest|stat)-([-\w]+)/', $type, $matches)) {
                    return redirect()->back()
                        ->withErrors(["Valhalla type '{$type}' not supported"]);
                }

                list(, $prefix, $raceName) = $matches;
                $raceName = ucwords(str_replace('-', ' ', $raceName));

                $race = Race::where('name', $raceName)->firstOrFail();

                if ($prefix === 'strongest') {
                    $data = $this->getStrongestDominions($round, $race);
                } else {
                    $data = $this->getLargestDominions($round, $race);
                }
                break;
        }

        $type = str_replace('stat-', '', $type);

        return view('pages.valhalla.round-type', compact(
            'round',
            'type',
            'headers',
            'data'
        ));
    }

    public function getUser(User $user)
    {
        // show valhalla of single user
    }

    // todo: search user

    /**
     * @param Round $round
     * @return Response|null
     */
    protected function guardAgainstActiveRound(Round $round)
    {
        if ($round->isActive() || !$round->hasStarted()) {
            return redirect()->back()
                ->withErrors(['Only ended rounds can be viewed in Valhalla']);
        }

        return null;
    }

    protected function getStrongestDominions(Round $round, Race $race = null, ?string $alignment = null)
    {
        $networthCalculator = app(NetworthCalculator::class);

        $builder = $round->dominions()
            ->with(['queues', 'realm', 'race.units.perks', 'user']);

        if ($alignment !== null) {
            $builder->whereHas('race', function ($builder) use ($alignment) {
                $builder->where('alignment', $alignment);
            });
        }

        if ($race !== null) {
            $builder->where('race_id', $race->id);
        }

        return $builder->get()
            ->map(function (Dominion $dominion) use ($networthCalculator, $race) {
                $data = [
                    '#' => null,
                    'dominion' => $dominion->name,
                    'player' => $dominion->isAbandoned() ? $dominion->ruler_name . ' (abandoned)' : $dominion->user->display_name,
                ];

                if ($race === null) {
                    $data += [
                        'faction' => $dominion->race->name,
                    ];
                }

                $data += [
                    'realm' => $dominion->realm->number,
                    'networth' => $networthCalculator->getDominionNetworth($dominion),
                ];

                return $data;
            })
            ->sortByDesc(function ($row) {
                return $row['networth'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['networth'] = number_format($row['networth']);
                return $row;
            });
    }

    protected function getStrongestRealms(Round $round, ?string $alignment = null)
    {
        $networthCalculator = app(NetworthCalculator::class);

        $builder = $round->realms()
            ->with(['dominions.queues', 'dominions.race.units', 'dominions.race.units.perks']);

        if ($alignment !== null) {
            $builder->where('alignment', $alignment);
        }

        return $builder->get()
            ->map(function (Realm $realm) use ($networthCalculator) {
                return [
                    '#' => null,
                    'realm name' => $realm->name,
                    'alignment' => ucfirst($realm->alignment),
                    'number' => $realm->number,
                    'networth' => $networthCalculator->getRealmNetworth($realm),
                ];
            })
            ->sortByDesc(function ($row) {
                return $row['networth'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['networth'] = number_format($row['networth']);
                return $row;
            });
    }

    protected function getStrongestPacks(Round $round)
    {
        $networthCalculator = app(NetworthCalculator::class);

        $builder = $round->packs()
            ->with(['dominions.user', 'realm', 'user']);

        $builder->has('dominions', '>', 1);

        return $builder->get()
            ->map(function (Pack $pack) use ($networthCalculator) {
                $data = [
                    '#' => null,
                    'pack' => $pack->name,
                    'players' => implode(', ', $pack->dominions
                        ->sortBy('user.display_name')
                        ->pluck('user.display_name')
                        ->all()),
                    'realm' => $pack->realm->number,
                    'avg_networth' => round($pack->dominions
                            ->map(function (Dominion $dominion) use ($networthCalculator) {
                                return $networthCalculator->getDominionNetworth($dominion);
                            })
                            ->reduce(function ($carry, $item) {
                                return ($carry + $item);
                            }) / $pack->dominions->count()),
                ];

                return $data;
            })
            ->sortByDesc(function ($row) {
                return $row['avg_networth'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['avg_networth'] = number_format($row['avg_networth']);
                return $row;
            });
    }

    protected function getLargestDominions(Round $round, Race $race = null, ?string $alignment = null)
    {
        $landCalculator = app(LandCalculator::class);

        $builder = $round->dominions()
            ->with(['realm', 'race.units', 'user']);

        if ($alignment !== null) {
            $builder->whereHas('race', function ($builder) use ($alignment) {
                $builder->where('alignment', $alignment);
            });
        }

        if ($race !== null) {
            $builder->where('race_id', $race->id);
        }

        return $builder->get()
            ->map(function (Dominion $dominion) use ($landCalculator, $race) {
                $data = [
                    '#' => null,
                    'dominion' => $dominion->name,
                    'player' => $dominion->isAbandoned() ? $dominion->ruler_name . ' (abandoned)' : $dominion->user->display_name,
                ];

                if ($race === null) {
                    $data += [
                        'faction' => $dominion->race->name,
                    ];
                }

                $data += [
                    'realm' => $dominion->realm->number,
                    'land' => $landCalculator->getTotalLand($dominion),
                ];

                return $data;
            })
            ->sortByDesc(function ($row) {
                return $row['land'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['land'] = number_format($row['land']);
                return $row;
            });
    }

    protected function getLargestRealms(Round $round, ?string $alignment = null)
    {
        $landCalculator = app(LandCalculator::class);

        $builder = $round->realms()
            ->with(['dominions.race.units']);

        if ($alignment !== null) {
            $builder->where('alignment', $alignment);
        }

        return $builder->get()
            ->map(function (Realm $realm) use ($landCalculator) {
                return [
                    '#' => null,
                    'realm name' => $realm->name,
                    'alignment' => ucfirst($realm->alignment),
                    'number' => $realm->number,
                    'land' => $realm->dominions->reduce(function ($carry, Dominion $dominion) use ($landCalculator) {
                        return ($carry + $landCalculator->getTotalLand($dominion));
                    }),
                ];
            })
            ->sortByDesc(function ($row) {
                return $row['land'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['land'] = number_format($row['land']);
                return $row;
            });
    }

    protected function getLargestPacks(Round $round)
    {
        $landCalculator = app(LandCalculator::class);

        $builder = $round->packs()
            ->with(['dominions.user', 'realm', 'user']);

        $builder->has('dominions', '>', 1);

        return $builder->get()
            ->map(function (Pack $pack) use ($landCalculator) {
                $data = [
                    '#' => null,
                    'pack' => $pack->name,
                    'players' => implode(', ', $pack->dominions
                        ->sortBy('user.display_name')
                        ->pluck('user.display_name')
                        ->all()),
                    'realm' => $pack->realm->number,
                    'avg_land' => round($pack->dominions
                            ->map(function (Dominion $dominion) use ($landCalculator) {
                                return $landCalculator->getTotalLand($dominion);
                            })
                            ->reduce(function ($carry, $item) {
                                return ($carry + $item);
                            }) / $pack->dominions->count()),
                ];

                return $data;
            })
            ->sortByDesc(function ($row) {
                return $row['avg_land'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['avg_land'] = number_format($row['avg_land']);
                return $row;
            });
    }

    protected function getDominionsByStatistic(Round $round, string $stat)
    {
        $builder = $round->dominions()
            ->with(['realm', 'race', 'user'])
            ->where($stat, '>', 0);

        return $builder->get()
            ->map(function (Dominion $dominion) use ($stat) {
                $data = [
                    '#' => null,
                    'dominion' => $dominion->name,
                    'player' => $dominion->isAbandoned() ? $dominion->ruler_name . ' (abandoned)' : $dominion->user->display_name,
                    'faction' => $dominion->race->name,
                    'realm' => $dominion->realm->number,
                    'value' => $dominion->{$stat},
                ];
                return $data;
            })
            ->sortByDesc(function ($row) {
                return $row['value'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['value'] = number_format($row['value']);
                return $row;
            });
    }
}
