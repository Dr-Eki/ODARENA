<?php

# 1000-acre factory

namespace OpenDominion\Factories;

use Auth;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Title;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;

use Illuminate\Support\Carbon;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Calculators\Dominion\BuildingCalculator;

class DominionFactory
{


    public function __construct(
        RaceHelper $raceHelper
    )
    {
        $this->raceHelper = app(RaceHelper::class);
        $this->buildingCalculator = app(BuildingCalculator::class);
    }

    /**
     * Creates and returns a new Dominion instance.
     *
     * @param User $user
     * @param Realm $realm
     * @param Race $race
     * @param Title $title
     * @param string $rulerName
     * @param string $dominionName
     * @param Pack|null $pack
     * @return Dominion
     * @throws GameException
     */
    public function create(
        User $user,
        Realm $realm,
        Race $race,
        Title $title,
        string $rulerName,
        string $dominionName,
        ?Pack $pack = null
    ): Dominion {
        $this->guardAgainstCrossRoundRegistration($user, $realm->round);
        $this->guardAgainstMultipleDominionsInARound($user, $realm->round);
        $this->guardAgainstMismatchedAlignments($race, $realm, $realm->round);

        // Starting resources are based on this.
        $acresBase = 1000;
        $startingResources['npc_modifier'] = 0;
        if($race->alignment == 'npc' and $race->name == 'Barbarian')
        {
            # NPC modifier is a number from 500 to 1000 (skewed toward higher).
            # It is to be used as a multiplier but stored as an int in database.
            $startingResources['npc_modifier'] = min(rand(500,1200), 1000);

            # For usage in this function, divide npc_modifier by 1000 to create a multiplier.
            $npcModifier = $startingResources['npc_modifier'] / 1000;

            $acresBase *= $npcModifier;
        }

        $startingBuildings = $this->getStartingBuildings($race, $acresBase);

        $startingLand = $this->getStartingLand(
            $race,
            $this->getStartingBarrenLand($race, $acresBase),
            $startingBuildings
        );

        # Late-joiner bonus:
        # Give +1.5% starting resources per hour late, max +150% (at 100 hours, mid-day 4).
        $hoursSinceRoundStarted = 0;
        if($realm->round->hasStarted() and request()->getHost() !== 'sim.odarena.com' and request()->getHost() !== 'odarena.local')
        {
            $hoursSinceRoundStarted = now()->startOfHour()->diffInHours(Carbon::parse($realm->round->start_date)->startOfHour());
        }

        $startingResourcesMultiplier = 1 + min(1.00, $hoursSinceRoundStarted*0.015);

        // These are starting resources which are or maybe
        // modified for specific races. These are the default
        // values, and then deviating values are set below.

        $startingResources['protection_ticks'] = 96;

        /*  ROUND 17: New Protection:

            1. Unit Costs
            --------------------------------------
            Average cost of 20 DPA at 1000 acres:
            Human: 2000*3 + 2000*7 = 20,000 DP -- 2000 * 275 + 2000 * 1000 = 2,550,000 gold
            Goblin: 6666*3 = 19,999 DP -- 6666 * 350 = 2,333,100 gold
            Simian: 2000*3 + 2000*7 = 20,000 DP -- 2000 * 200 + 2000 * 1200 = 2,800,000 gold

            Average = (2,550,000 + 2,333,100 + 2,800,000) = 2,561,033
            Max smithies = 2,561,033 * 0.6 = 1,536,619

            Average cost of 20 OPA at 1000 acres:
            Human: 2500 * 7 * (1+5%+10%) = 20,125 OP -- 2500 * 1250 = 3,125,000 gold
            Goblin: (2100 * 4 + 1800 * 5) * (1+5%+10%) = 20,010 OP -- 2100 * 600 + 1800 * 700 = 2,520,000 gold
            Simian: 2750 * 7 * (1+5%) = 20,212.5 OP -- 2750 * 1200 = 3,300,000 gold

            Average = (3,125,000 + 2,520,000 + 3,300,000) = 2,981,666
            Max smithies = 2,981,666 * 0.6 = 1,788,999

            Total with max Smithies = 1,536,619 + 1,788,999 = 3,325,618

            Gold for troops = 3,000,000

            2. Construction costs
            --------------------------------------
            Cost of building 1,000 acres:
            Gold: 1000 * (250+(1000*1.5)) = 1,750,000
            Lumber: 1000 * (100+(1000-250)*(3.14/10)) = 335,500

            3. Rezoning costs
            --------------------------------------
            Cost of building 1,000 acres:
            Gold: 1000 * (1000-250*0.06+250) = 1,235,000

        */

        # RESOURCES
        $startingResources['gold'] = 2000000; # Unit training costs
        $startingResources['gold'] += 500000; # Construction
        $startingResources['gold'] += 500000; # Rezoning
        $startingResources['ore'] = intval(2000000 * 0.05); # For troops: 5% of plat for troops in ore

        $startingResources['gems'] = 20000;

        $startingResources['lumber'] = 200000 * 1.6; # For buildings | Round 40: 1.5x

        $startingResources['food'] = 50000 * 2.5; # 1000*15*0.25*24 = 90,000 + 8% Farms - Growth gets more later. | Round 40: 2.5x
        $startingResources['mana'] = 20000; # Harmony+Midas, twice: 1000*2.5*2*2 = 10000

        $startingResources['boats'] = 100;

        $startingResources['soul'] = 0;
        $startingResources['blood'] = 0;
        $startingResources['wild_yeti'] = 0;

        $startingResources['tech'] = 400 * $hoursSinceRoundStarted;

        $startingResources['morale'] = 100;

        $startingResources['prestige'] = intval($acresBase/2);

        if($race->name !== 'Barbarian')
        {
            if(Auth::user()->display_name == $rulerName)
            {
                $startingResources['prestige'] += 100;
            }
        }

        $startingResources['barbarian_guard_active_at'] = NULL;

        # POPULATION AND MILITARY
        $startingResources['peasants'] = intval(1000 * 5 * (1 + $race->getPerkMultiplier('max_population')) * (1 + ($acresBase/2)/10000)); # 1000 * 15 * Racial * Prestige
        $startingResources['draftees'] = intval($startingResources['peasants'] * 0.30);
        $startingResources['peasants'] -= intval($startingResources['draftees']);
        $startingResources['draft_rate'] = 40;

        $startingResources['unit1'] = 0;
        $startingResources['unit2'] = 0;
        $startingResources['unit3'] = 0;
        $startingResources['unit4'] = 0;
        $startingResources['spies'] = 0;
        $startingResources['wizards'] = 0;
        $startingResources['archmages'] = 0;

        $startingResources['improvement_markets'] = 0;
        $startingResources['improvement_keep'] = 0;
        $startingResources['improvement_forges'] = 0;
        $startingResources['improvement_walls'] = 0;
        $startingResources['improvement_armory'] = 0;
        $startingResources['improvement_observatory'] = 0;
        $startingResources['improvement_harbor'] = 0;

        # FACTION SPECIFIC RESOURCES

        // Ore-free races: no ore
        $oreFreeRaces = array('Ants','Elementals','Firewalker','Lux','Merfolk','Myconid','Sylvan','Spirit','Swarm','Wood Elf','Demon','Dimensionalists','Growth','Reptilians','Nox','Undead','Marshling','Qur','Simian','Vampires','Void','Weres');
        if(in_array($race->name, $oreFreeRaces))
        {
            $startingResources['ore'] = 0;
        }

        // Food-free races: no food
        if($race->getPerkValue('no_food_consumption'))
        {
            $startingResources['food'] = 0;
        }

        // Boat-free races: no boats
        $startingResources['boats'] = 0;

        // Mana-cost races: triple Mana
        $manaCostRaces = array('Elementals','Demon','Dimensionalists','Lux','Norse','Snow Elf','Nox','Undead','Void','Icekin','Marshling');
        if(in_array($race->name, $manaCostRaces))
        {
            $startingResources['mana'] = $startingResources['mana']*3;
        }

        // For cannot_improve_castle races: replace Gems with Gold.
        if((bool)$race->getPerkValue('cannot_improve_castle'))
        {
            $startingResources['gold'] += $startingResources['gems'] * 2;
            $startingResources['gems'] = 0;
        }

        // For cannot_construct races: replace half of Lumber with Gold.
        // Still gets plat for troops.
        if((bool)$race->getPerkValue('cannot_construct'))
        {
            $startingResources['gold'] += $startingResources['lumber'] / 2;
            $startingResources['lumber'] = 0;
        }

        # Check construction materials
        $constructionMaterials = $this->raceHelper->getConstructionMaterials($race);

        // If primary resource isn't plat, give 1/10 of plat as primary resource.
        if($constructionMaterials[0] !== 'gold')
        {
            $startingResources[$constructionMaterials[0]] += $startingResources['gold'] / 10;
            $startingResources['gold'] = 0;
        }

        // If secondary is set but isn't lumber, give lumber into second resource (typically ore for Gnome, IG, and Icekin)
        if(isset($constructionMaterials[1]) and $constructionMaterials[1] !== 'lumber')
        {
            $startingResources[$constructionMaterials[1]] += $startingResources['lumber'];
            $startingResources['lumber'] = 0;
        }

        // Growth: extra food, no gold, no gems, no lumber, and higher draft rate.
        if($race->name == 'Growth')
        {
            $startingResources['gold'] = 0;
            $startingResources['lumber'] = 0;
            $startingResources['gems'] = 0;
            $startingResources['food'] = $acresBase * 400;
            $startingResources['draft_rate'] = 100;
        }

        // Myconid: extra food, no gold; and gets enough Psilocybe for mana production equivalent to 40 Towers
        if($race->name == 'Myconid')
        {
            $startingResources['gold'] = 0;
            $startingResources['lumber'] = 0;
            $startingResources['food'] = $acresBase * 40;
        }

        // Demon: extra morale.
        if($race->name == 'Demon')
        {
            $startingResources['blood'] = 140000;
            $startingResources['unit4'] = 1;
        }

        // Void: gets acres * 4000 mana (as of round 20)
        if($race->name == 'Void')
        {
            $startingResources['mana'] = 175 * $acresBase;
            $startingResources['gold'] = 0;
            $startingResources['mana'] = $acresBase * 3500;
            $startingResources['lumber'] = 0;
            $startingResources['gems'] = 0;
        }

        // Elementals: remove lumber
        if($race->name == 'Elementals')
        {
            $startingResources['lumber'] = 0;
        }

        // Lux: double starting mana.
        if($race->name == 'Lux')
        {
            $startingResources['mana'] *= 2;
        }

        // Dimensionalists: starts with 33 Summoners and double mana (which has already been tripled before).
        if($race->name == 'Dimensionalists')
        {
            $startingResources['unit1'] = 400;
            $startingResources['mana'] *= 2;
        }

        // Yeti: starting yetis.
        if($race->name == 'Yeti')
        {
            $startingResources['food'] *= 2;
            $startingResources['ore'] += $startingResources['gold'] / 2;
            $startingResources['food'] += $startingResources['gold'] / 2;
            $startingResources['lumber'] += $startingResources['gold'] / 2;
            $startingResources['gold'] = 0;

            $startingResources['draft_rate'] = 100;
        }

        // Kerranad: starting imps.
        if($race->name == 'Kerranad')
        {
            $startingResources['improvement_markets'] = 422500;
            $startingResources['improvement_keep'] = 535000;
            $startingResources['improvement_forges'] = 791000;
            $startingResources['improvement_walls'] = 791000;
            $startingResources['improvement_armory'] = 895000;
            $startingResources['improvement_observatory'] = 528000;
            $startingResources['improvement_harbor'] = 591000;
        }

        // Spirit: give back gold.
        if($race->name == 'Spirit')
        {
            $startingResources['gold'] = 2000000; # Unit training costs
        }

        // Monster: no one lives here.
        if($race->name == 'Monster')
        {
            $startingResources['draftees'] = 0;
            $startingResources['peasants'] = 0;

            $startingResources['gold'] = 0;
            $startingResources['mana'] = 0;
            $startingResources['ore'] = 0;
            $startingResources['lumber'] = 0;
            $startingResources['gems'] = 0;
            $startingResources['boats'] = 0;
            $startingResources['tech'] = 50000;

            $startingResources['unit1'] = 333;
            $startingResources['unit2'] = 6;
            $startingResources['unit3'] = 2;
            $startingResources['unit4'] = 1;

            $startingResources['draft_rate'] = 0;

            $startingResources['protection_ticks'] = 1;
        }

        if($race->alignment == 'npc')
        {
            if($race->name == 'Barbarian')
            {
                $startingResources['peasants'] = $acresBase * (rand(50,200)/100);
                $startingResources['draftees'] = 0;

                $startingResources['draft_rate'] = 0;
                $startingResources['peasants'] = 0;
                $startingResources['gold'] = 0;
                $startingResources['ore'] = 0;
                $startingResources['gems'] = 0;
                $startingResources['lumber'] = 0;
                $startingResources['food'] = 0;
                $startingResources['mana'] = 0;
                $startingResources['boats'] = 0;

                # Starting units for Barbarians
                $dpaTarget = 25;
                $dpaTargetSpecsRatio = rand(50,100)/100;
                $dpaTargetElitesRatio = 1-$dpaTargetSpecsRatio;
                $dpRequired = $acresBase * $dpaTarget;

                $opaTarget = $dpaTarget * 0.75;
                $opaTargetSpecsRatio = rand(50,100)/100;
                $opaTargetElitesRatio = 1-$opaTargetSpecsRatio;
                $opRequired = $acresBase * $opaTarget;

                $startingResources['unit1'] = floor(($opRequired * $opaTargetSpecsRatio)/3);
                $startingResources['unit2'] = floor(($dpRequired * $dpaTargetSpecsRatio)/3);
                $startingResources['unit3'] = floor(($dpRequired * $dpaTargetElitesRatio)/5);
                $startingResources['unit4'] = floor(($opRequired * $opaTargetElitesRatio)/5);

                $startingResources['protection_ticks'] = 0;
                $startingResources['barbarian_guard_active_at'] = now();
            }
        }

        # Round 41: reduce gold, ore, and lumber by 50%
        $startingResources['gold'] /= 2;
        $startingResources['ore'] /= 2;
        $startingResources['lumber'] /= 2;

        $dominion = Dominion::create([
            'user_id' => $user->id,
            'round_id' => $realm->round->id,
            'realm_id' => $realm->id,
            'race_id' => $race->id,
            'title_id' => $title->id,
            'pack_id' => $pack->id ?? null,

            'ruler_name' => $rulerName,
            'name' => $dominionName,
            'prestige' => $startingResources['prestige'],

            'peasants' => intval($startingResources['peasants']),
            'peasants_last_hour' => 0,

            'draft_rate' => $startingResources['draft_rate'],
            'morale' => $startingResources['morale'],
            'spy_strength' => 100,
            'wizard_strength' => 100,

            'resource_gold' => intval($startingResources['gold'] * $startingResourcesMultiplier),
            'resource_food' =>  intval($startingResources['food'] * $startingResourcesMultiplier),
            'resource_lumber' => intval($startingResources['lumber'] * $startingResourcesMultiplier),
            'resource_mana' => intval($startingResources['mana'] * $startingResourcesMultiplier),
            'resource_ore' => intval($startingResources['ore'] * $startingResourcesMultiplier),
            'resource_gems' => intval($startingResources['gems'] * $startingResourcesMultiplier),
            'resource_tech' => intval($startingResources['tech'] * $startingResourcesMultiplier),
            'resource_boats' => 0,
            'resource_champion' => 0,
            'resource_soul' => intval($startingResources['soul'] * $startingResourcesMultiplier),
            'resource_wild_yeti' => intval($startingResources['wild_yeti'] * $startingResourcesMultiplier),
            'resource_blood' => intval($startingResources['blood'] * $startingResourcesMultiplier),
            # End new resources

            'improvement_markets' => $startingResources['improvement_markets'],
            'improvement_keep' => $startingResources['improvement_keep'],
            'improvement_forges' => $startingResources['improvement_forges'],
            'improvement_walls' => $startingResources['improvement_walls'],
            'improvement_armory' => $startingResources['improvement_armory'],
            'improvement_infirmary' => 0,
            'improvement_workshops' => 0,
            'improvement_observatory' => $startingResources['improvement_observatory'],
            'improvement_cartography' => 0,
            'improvement_towers' => 0,
            'improvement_spires' => 0,
            'improvement_hideouts' => 0,
            'improvement_granaries' => 0,
            'improvement_harbor' => $startingResources['improvement_harbor'],
            'improvement_forestry' => 0,
            'improvement_refinery' => 0,
            'improvement_tissue' => 0,

            'military_draftees' => intval($startingResources['draftees'] * $startingResourcesMultiplier),
            'military_unit1' => intval($startingResources['unit1']),
            'military_unit2' => intval($startingResources['unit2']),
            'military_unit3' => intval($startingResources['unit3']),
            'military_unit4' => intval($startingResources['unit4']),
            'military_spies' => intval($startingResources['spies'] * $startingResourcesMultiplier),
            'military_wizards' => intval($startingResources['wizards'] * $startingResourcesMultiplier),
            'military_archmages' => intval($startingResources['archmages'] * $startingResourcesMultiplier),

            'land_plain' => $startingLand['plain'],
            'land_mountain' => $startingLand['mountain'],
            'land_swamp' => $startingLand['swamp'],
            'land_cavern' => $startingLand['cavern'],
            'land_forest' => $startingLand['forest'],
            'land_hill' => $startingLand['hill'],
            'land_water' => $startingLand['water'],


            'building_home' => 0,
            'building_alchemy' => 0,
            'building_farm' => 0,
            'building_smithy' => 0,
            'building_masonry' => 0,
            'building_ore_mine' => 0,
            'building_gryphon_nest' => 0,
            'building_tower' => 0,
            'building_wizard_guild' => 0,
            'building_temple' => 0,
            'building_gem_mine' => 0,
            'building_school' => 0,
            'building_lumberyard' => 0,
            'building_forest_haven' => 0,
            'building_factory' => 0,
            'building_guard_tower' => 0,
            'building_shrine' => 0,
            'building_barracks' => 0,
            'building_dock' => 0,
            'building_tissue' => 0,
            'building_mycelia' => 0,
            'building_ziggurat' => 0,

            'npc_modifier' => $startingResources['npc_modifier'],

            'protection_ticks' => $startingResources['protection_ticks'],

            'barbarian_guard_active_at' => $startingResources['barbarian_guard_active_at'],
        ]);

        $this->buildingCalculator->createOrIncrementBuildings($dominion, $startingBuildings);

        return $dominion;

    }

    /**
     * @param User $user
     * @param Round $round
     * @throws GameException
     */
    protected function guardAgainstCrossRoundRegistration(User $user, Round $round): void
    {
        if($round->hasEnded())
        {
            throw new GameException('You cannot register for a round that has ended.');
        }
    }

    /**
     * @param User $user
     * @param Round $round
     * @throws GameException
     */
    protected function guardAgainstMultipleDominionsInARound(User $user, Round $round): void
    {
        $dominionCount = Dominion::query()
            ->where([
                'user_id' => $user->id,
                'round_id' => $round->id,
            ])
            ->count();

        if ($dominionCount > 0) {
            throw new GameException('User already has a dominion in this round');
        }
    }

    /**
     * @param Race $race
     * @param Realm $realm
     * @param Round $round
     * @throws GameException
     */
    protected function guardAgainstMismatchedAlignments(Race $race, Realm $realm, Round $round): void
    {
        if (!$round->mixed_alignment && $race->alignment !== $realm->alignment /*and $race->alignment !== 'independent'*/)
        {
            throw new GameException('Race and realm alignment do not match');
        }
    }

    /**
     * Get amount of barren land a new Dominion starts with.
     *
     * @return array
     */
    protected function getStartingBarrenLand($race, $acresBase): array
    {
        # Change this to just look at home land type?
        # Special treatment for Void, Growth, Myconid, Merfolk, and Swarm
        if($race->name == 'Void')
        {
          return [
              'plain' => 100,
              'mountain' => 500,
              'swamp' => 200,
              'cavern' => 0,
              'forest' => 0,
              'hill' => 200,
              'water' => 0,
          ];
        }
        elseif($race->name == 'Growth')
        {
          return [
              'plain' => 0,
              'mountain' => 0,
              'swamp' => 0,
              'cavern' => 0,
              'forest' => 0,
              'hill' => 0,
              'water' => 0,
          ];
        }
        elseif($race->name == 'Dragon')
        {
          return [
              'plain' => 0,
              'mountain' => $acresBase,
              'swamp' => 0,
              'cavern' => 0,
              'forest' => 0,
              'hill' => 0,
              'water' => 0,
          ];
        }
        elseif($race->name == 'Myconid')
        {
          return [
              'plain' => 0,
              'mountain' => 0,
              'swamp' => 0,
              'cavern' => 0,
              'forest' => 0,
              'hill' => 0,
              'water' => 0,
          ];
        }
        elseif($race->name == 'Merfolk')
        {
          return [
              'plain' => 0,
              'mountain' => 0,
              'swamp' => 0,
              'cavern' => 0,
              'forest' => 0,
              'hill' => 0,
              'water' => $acresBase,
          ];
        }
        elseif($race->name == 'Swarm')
        {
          return [
              'plain' => $acresBase,
              'mountain' => 0,
              'swamp' => 0,
              'cavern' => 0,
              'forest' => 0,
              'hill' => 0,
              'water' => 0,
          ];
        }
        elseif($race->name == 'Elementals')
        {
          return [
              'plain' => 170,
              'mountain' => 166,
              'swamp' => 166,
              'cavern' => 0,
              'forest' => 166,
              'hill' => 166,
              'water' => 166,
          ];
        }
        elseif($race->name == 'Kerranad')
        {
          return [
              'plain' => 0,
              'mountain' => 0,
              'swamp' => 0,
              'cavern' => 0,
              'forest' => 0,
              'hill' => 0,
              'water' => 0,
          ];
        }
        elseif($race->name == 'Barbarian')
        {
          return [
              'plain' => 10,
              'mountain' => 10,
              'swamp' => 10,
              'cavern' => 0,
              'forest' => 10,
              'hill' => 10,
              'water' => 10,
          ];
        }
        else
        {
            return [
                'plain' => 175,
                'mountain' => 175,
                'swamp' => 175,
                'cavern' => 0,
                'forest' => 175,
                'hill' => 150,
                'water' => 150,
            ];
        }
    }

    /**
     * Get amount of buildings a new Dominion starts with.
     *
     * @return array
     */
    protected function getStartingBuildings($race, $acresBase): array
    {
        # Default
        $startingBuildings = [
            'farm' => 0,
            'smithy' => 0,
            'residence' => 0,
            'lumberyard' => 0,
            'forest_haven' => 0,
            'ore_mine' => 0,
            'gem_mine' => 0,
            'barracks' => 0,
            'tower' => 0,
            'wizard_guild' => 0,
            'temple' => 0,
            'dock' => 0,
            'shed' => 0,

            'tissue_swamp' => 0,
            'mycelia' => 0,
            'ziggurat' => 0,
        ];

        if($race->name == 'Kerranad')
        {
            $startingBuildings['farm'] = 50;
            $startingBuildings['smithy'] = 200;
            $startingBuildings['residence'] = 100;
            $startingBuildings['lumberyard'] = 50;
            $startingBuildings['forest_haven'] = 25;
            $startingBuildings['ore_mine'] = 100;
            $startingBuildings['gem_mine'] = 300;
            $startingBuildings['tower'] = 50;
            $startingBuildings['wizard_guild'] = 25;
            $startingBuildings['temple'] = 50;
            $startingBuildings['dock'] = 50;
        }
        elseif($race->name == 'Growth')
        {
          $startingBuildings['tissue_swamp'] = $acresBase;
        }
        elseif($race->name == 'Myconid')
        {
          $startingBuildings['mycelia'] = $acresBase;
        }
        elseif($race->name == 'Barbarian')
        {
            $startingBuildings['farm'] = floor($acresBase*0.10);
            $startingBuildings['smithy'] = floor($acresBase*0.10);
            $startingBuildings['lumberyard'] = floor($acresBase*0.06);
            $startingBuildings['forest_haven'] = floor($acresBase*0.06);
            $startingBuildings['ore_mine'] = floor($acresBase*0.10);
            $startingBuildings['gem_mine'] = floor($acresBase*0.10);
            $startingBuildings['barracks'] = floor($acresBase*0.20);
            $startingBuildings['tower'] = floor($acresBase*0.06);
            $startingBuildings['temple'] = floor($acresBase*0.06);
            $startingBuildings['dock'] = floor($acresBase*0.10);
        }

        return $startingBuildings;
    }

    /**
     * Get amount of total starting land a new Dominion starts with, factoring
     * in both buildings and barren land.
     *
     * @param Race $race
     * @param array $startingBarrenLand
     * @param array $startingBuildings
     * @return array
     */
    protected function getStartingLand(Race $race, array $startingBarrenLand, array $startingBuildings): array
    {
        $startingLand = [
            'plain' => $startingBarrenLand['plain'] + $startingBuildings['farm'] + $startingBuildings['smithy'] + $startingBuildings['residence'],
            'mountain' => $startingBarrenLand['mountain'] + $startingBuildings['ore_mine'] + $startingBuildings['gem_mine'],
            'swamp' => $startingBarrenLand['swamp'] + $startingBuildings['tower'] + $startingBuildings['wizard_guild'] + $startingBuildings['temple'] + $startingBuildings['tissue_swamp'],
            'cavern' => 0,
            'forest' => $startingBarrenLand['forest'] + $startingBuildings['lumberyard'] + $startingBuildings['forest_haven'] + $startingBuildings['mycelia'],
            'hill' => $startingBarrenLand['hill'] + $startingBuildings['barracks'],
            'water' => $startingBarrenLand['water'] + $startingBuildings['dock'],
        ];

        $startingLand[$race->home_land_type] += $startingBuildings['shed'];

        return $startingLand;
    }
}
