<?php

# 1000-acre factory

namespace OpenDominion\Factories;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;

# ODA
use Illuminate\Support\Carbon;

class DominionFactory
{
    /**
     * Creates and returns a new Dominion instance.
     *
     * @param User $user
     * @param Realm $realm
     * @param Race $race
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
        string $rulerName,
        string $dominionName,
        ?Pack $pack = null
    ): Dominion {
        $this->guardAgainstMultipleDominionsInARound($user, $realm->round);
        $this->guardAgainstMismatchedAlignments($race, $realm, $realm->round);


        // Starting resources are based on this.
        $acresBase = 1000;
        $npcModifier = 0;
        if($race->alignment == 'npc' and $race->name == 'Barbarian')
        {
          # Barbarians start between 400 and 900 acres, randomly.
          # Skewed towards smaller.
          $acresBase *= (max(400,rand(300,900))/1000);
        }

        $startingBuildings = $this->getStartingBuildings($race, $acresBase);

        $startingLand = $this->getStartingLand(
            $race,
            $this->getStartingBarrenLand($race, $acresBase),
            $startingBuildings
        );



        // Give +1% starting resources per hour late, max +100% (at 100 hours, mid-day 4).
        #$hourSinceRoundStarted = ($realm->round->start_date)->diffInHours(now());
        if($realm->round->hasStarted())
        {
          $hoursSinceRoundStarted = now()->startOfHour()->diffInHours(Carbon::parse($realm->round->start_date)->startOfHour());
        }
        else
        {
          $hoursSinceRoundStarted = 0;
        }

        $startingResourcesMultiplier = 1 + min(1.00, $hoursSinceRoundStarted*0.01);

        // These are starting resources which are or maybe
        // modified for specific races. These are the default
        // values, and then deviating values are set below.

        # RESOURCES
        $platForTroops = 2000 * $acresBase; # For troops: 800000/600x1000=1,333,333 - Assuming people aiming for 800,000 plat hour 61 at 600 acres in OD
        $startingResources['platinum'] = 2000 * $acresBase * 0.75; # For buildings: (850+(1000-250)*1.53)*1000=1,997,500 - As of round 11, reduced by 25%
        $startingResources['platinum'] += 350 * $acresBase; # For rezoning: ((1000 - 250) * 0.6 + 250)*500 = 350,000
        $startingResources['platinum'] += $platForTroops;
        $startingResources['ore'] = intval($platForTroops * 0.15); # For troops: 15% of plat for troops in ore

        $startingResources['gems'] = 20 * $acresBase;

        $startingResources['lumber'] = 355 * $acresBase * 0.75; # For buildings: (88+(1000-250)*0.35)*1000 = 350,500 - As of round 11, reduced by 25%

        $startingResources['food'] = 50 * $acresBase; # 1000*15*0.25*24 = 90,000 + 8% Farms - Growth gets more later.
        $startingResources['mana'] = 20 * $acresBase; # Harmony+Midas, twice: 1000*2.5*2*2 = 10000

        $startingResources['boats'] = 0.2 * $acresBase;

        $startingResources['soul'] = 0;

        $startingResources['wild_yeti'] = 0;

        $startingResources['morale'] = 100;

        $startingResources['npc_modifier'] = 0;

        $startingResources['prestige'] = intval($acresBase/2);

        # POPULATION AND MILITARY
        $startingResources['peasants'] = intval(1000 * 15 * (1 + $race->getPerkMultiplier('max_population')) * (1 + ($acresBase/2)/10000)); # 1000 * 15 * Racial * Prestige
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

        # RACE/FACTION SPECIFIC RESOURCES

        // Gnome and Imperial Gnome: triple the ore and remove 1/4 of platinum
        if($race->name == 'Gnome' or $race->name == 'Imperial Gnome')
        {
          $startingResources['ore'] = intval($startingResources['ore'] * 3);
          $startingResources['platinum'] -= intval($startingResources['platinum'] * (1/4));
        }

        // Ore-free races: no ore
        $oreFreeRaces = array('Ants','Elementals','Firewalker','Lux','Merfolk','Myconid','Sylvan','Spirit','Swarm','Wood Elf','Demon','Dimensionalists','Growth','Lizardfolk','Nox','Undead','Void');
        if(in_array($race->name, $oreFreeRaces))
        {
          $startingResources['ore'] = 0;
        }

        // Food-free races: no food
        if($race->getPerkMultiplier('food_consumption') == -1)
        {
          $startingResources['food'] = 0;
        }

        // Boat-free races: no boats
        $boatFreeRaces = array('Lux','Merfolk','Myconid','Spirit','Swarm','Dimensionalists','Growth','Lizardfolk','Undead','Void');
        if(in_array($race->name, $boatFreeRaces))
        {
          $startingResources['boats'] = 0;
        }

        // Mana-cost races: triple Mana
        $manaCostRaces = array('Elementals','Demon','Dimensionalists','Lux','Norse','Snow Elf','Nox','Undead','Void','Icekin');
        if(in_array($race->name, $manaCostRaces))
        {
          $startingResources['mana'] = $startingResources['mana']*3;
        }

        // Lumber-free races: no lumber or Lumberyards
        if($race->getPerkMultiplier('construction_cost_only_platinum') or $race->getPerkMultiplier('construction_cost_only_mana') or $race->getPerkMultiplier('construction_cost_only_food'))
        {
          $startingResources['lumber'] = 0;
          $startingBuildings['lumberyard'] = 0;
        }

        // For cannot_improve_castle races: replace Gems with Platinum.
        if((bool)$race->getPerkValue('cannot_improve_castle'))
        {
          $startingResources['platinum'] += $startingResources['gems'] * 2;
          $startingResources['gems'] = 0;
        }

        // For cannot_construct races: replace half of Lumber with Platinum.
        // Still gets plat for troops.
        if((bool)$race->getPerkValue('cannot_construct'))
        {
          $startingResources['platinum'] = $platForTroops;
          $startingResources['platinum'] += $startingResources['lumber'] / 2;
          $startingResources['lumber'] = 0;
        }

        // Growth: extra food, no platinum, no gems, no lumber, and higher draft rate.
        if($race->name == 'Growth')
        {
          $startingResources['platinum'] = 0;
          $startingResources['lumber'] = 0;
          $startingResources['gems'] = 0;
          $startingResources['food'] = $acresBase * 4000; #1000 * 4 * 96;
          $startingResources['draft_rate'] = 100;
        }

        // Myconid: extra food, no platinum; and gets enough Psilocybe for mana production equivalent to 40 Towers
        if($race->name == 'Myconid')
        {
          $startingResources['platinum'] = 0;
          $startingResources['lumber'] = 0;
          $startingResources['food'] = $acresBase * 400;
        }

        // Demon: extra morale.
        if($race->name == 'Demon')
        {
          $startingResources['soul'] = 3000;
          $startingResources['mana'] = 500000;
        }

        // Void: gets half of plat for troops as mana, gets lumber as mana (then lumber to 0).
        if($race->name == 'Void')
        {
          $startingResources['mana'] = 1000 * $acresBase;
          $startingResources['platinum'] = 1000 * $acresBase;
          $startingResources['mana'] += $startingResources['lumber'];
          $startingResources['lumber'] = 0;
          $startingResources['gems'] = 0;
        }

        // Lux: double starting mana.
        if($race->name == 'Lux')
        {
          $startingResources['mana'] *= 2;
        }

        // Dimensionalists: starts with 333 Summoners and extra mana.
        if($race->name == 'Dimensionalists')
        {
          $startingResources['unit1'] = 333;
          $startingResources['mana'] = 400 * $acresBase;
        }

        // Snow Elf: starting yetis.
        if($race->name == 'Snow Elf')
        {
          $startingResources['wild_yeti'] = 1500;
        }

        if($race->alignment == 'npc')
        {
          if($race->name == 'Barbarian')
          {

            $startingResources['peasants'] = $acresBase * (rand(50,200)/100);
            $startingResources['draftees'] = 0;

            $startingResources['prestige'] = intval($acresBase * 0.25);
            $startingResources['draft_rate'] = 0;
            $startingResources['peasants'] = 0;
            $startingResources['platinum'] = 0;
            $startingResources['ore'] = 0;
            $startingResources['gems'] = 0;
            $startingResources['lumber'] = 0;
            $startingResources['food'] = 0;
            $startingResources['mana'] = 0;
            $startingResources['boats'] = 0;

            $startingResources['npc_modifier'] = min(1000,rand(400,1200));
          }
        }

        return Dominion::create([
            'user_id' => $user->id,
            'round_id' => $realm->round->id,
            'realm_id' => $realm->id,
            'race_id' => $race->id,
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

            'resource_platinum' => intval($startingResources['platinum'] * $startingResourcesMultiplier),
            'resource_food' =>  intval($startingResources['food'] * $startingResourcesMultiplier),
            'resource_lumber' => intval($startingResources['lumber'] * $startingResourcesMultiplier),
            'resource_mana' => intval($startingResources['mana'] * $startingResourcesMultiplier),
            'resource_ore' => intval($startingResources['ore'] * $startingResourcesMultiplier),
            'resource_gems' => intval($startingResources['gems'] * $startingResourcesMultiplier),
            'resource_tech' => intval(0 * $startingResourcesMultiplier),
            'resource_boats' => intval($startingResources['boats'] * $startingResourcesMultiplier),

            # New resources
            'resource_champion' => 0,
            'resource_soul' => intval($startingResources['soul'] * $startingResourcesMultiplier),
            'resource_wild_yeti' => intval($startingResources['wild_yeti'] * $startingResourcesMultiplier),
            # End new resources

            'improvement_science' => 0,
            'improvement_keep' => 0,
            'improvement_towers' => 0,
            'improvement_forges' => 0,
            'improvement_walls' => 0,
            'improvement_harbor' => 0,
            'improvement_armory' => 0,
            'improvement_infirmary' => 0,
            'improvement_tissue' => 0,

            'military_draftees' => intval($startingResources['draftees'] * $startingResourcesMultiplier),
            'military_unit1' => intval($startingResources['unit1'] * $startingResourcesMultiplier),
            'military_unit2' => intval($startingResources['unit2'] * $startingResourcesMultiplier),
            'military_unit3' => intval($startingResources['unit3'] * $startingResourcesMultiplier),
            'military_unit4' => intval($startingResources['unit4'] * $startingResourcesMultiplier),
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
            'building_farm' => $startingBuildings['farm'],
            'building_smithy' => 0,
            'building_masonry' => 0,
            'building_ore_mine' => 0,
            'building_gryphon_nest' => 0,
            'building_tower' => $startingBuildings['tower'],
            'building_wizard_guild' => 0,
            'building_temple' => 0,
            'building_diamond_mine' => 0,
            'building_school' => 0,
            'building_lumberyard' => $startingBuildings['lumberyard'],
            'building_forest_haven' => 0,
            'building_factory' => 0,
            'building_guard_tower' => 0,
            'building_shrine' => 0,
            'building_barracks' => 0,
            'building_dock' => 0,

            'building_ziggurat' => $startingBuildings['ziggurat'],
            'building_tissue' => $startingBuildings['tissue'],
            'building_mycelia' => $startingBuildings['mycelia'],

            'npc_modifier' => $startingResources['npc_modifier'],

            'protection_ticks' => 7*4,
        ]);
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
        if (!$round->mixed_alignment && $race->alignment !== $realm->alignment and $race->alignment !== 'independent')
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
              'plain' => 0,
              'mountain' => intval($acresBase-$acresBase/2),
              'swamp' => 0,
              'cavern' => 0,
              'forest' => 0,
              'hill' => 0,
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
              'water' => intval($acresBase-($acresBase*0.08)-($acresBase*0.05)),
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
        else
        {
            return [
                'plain' => intval($acresBase*0.2-$acresBase*0.08),
                'mountain' => intval($acresBase*0.2),
                'swamp' => intval($acresBase*0.15-$acresBase*0.05),
                'cavern' => 0,
                'forest' => intval($acresBase*0.150-$acresBase*0.05),
                'hill' => intval($acresBase*0.2),
                'water' => intval($acresBase*0.1),
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
        # Non-construction races (Swarm?)
        if($race->getPerkValue('cannot_construct'))
        {
            $startingBuildings = [
                'tower' => 0,
                'farm' => 0,
                'lumberyard' => 0,
                'ziggurat' => 0,
                'tissue' => 0,
                'mycelia' => 0,
            ];
        }
        # Void
        elseif($race->getPerkValue('can_only_build_ziggurat'))
        {
          $startingBuildings = [
              'tower' => 0,
              'farm' => 0,
              'lumberyard' => 0,
              'ziggurat' => intval($acresBase/2),
              'tissue' => 0,
              'mycelia' => 0,
          ];
        }
        # Growth
        elseif($race->getPerkValue('can_only_build_tissue'))
        {
          $startingBuildings = [
              'tower' => 0,
              'farm' => 0,
              'lumberyard' => 0,
              'ziggurat' => 0,
              'tissue' => $acresBase,
              'mycelia' => 0,
          ];
        }
        # Myconid
        elseif($race->getPerkValue('can_only_build_mycelia'))
        {
          $startingBuildings = [
              'tower' => 0,
              'farm' => 0,
              'lumberyard' => 0,
              'ziggurat' => 0,
              'tissue' => 0,
              'mycelia' => $acresBase,
          ];
        }
        # Merfolk
        elseif($race->name == 'Merfolk')
        {
          $startingBuildings = [
              'tower' => intval($acresBase*0.05),
              'farm' => intval($acresBase*0.08),
              'lumberyard' => 0,
              'ziggurat' => 0,
              'tissue' => 0,
              'mycelia' => 0,
          ];
        }
        # Default
        else
        {
          $startingBuildings = [
              'tower' => intval($acresBase*0.05),
              'farm' => intval($acresBase*0.08),
              'lumberyard' => intval($acresBase*0.05),
              'ziggurat' => 0,
              'tissue' => 0,
              'mycelia' => 0,
          ];
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
            'plain' => $startingBarrenLand['plain'] + $startingBuildings['farm'],
            'mountain' => $startingBarrenLand['mountain'] + $startingBuildings['ziggurat'],
            'swamp' => $startingBarrenLand['swamp'] + + $startingBuildings['tower'] + $startingBuildings['tissue'],
            'cavern' => $startingBarrenLand['cavern'],
            'forest' => $startingBarrenLand['forest'] + $startingBuildings['lumberyard'] + $startingBuildings['mycelia'],
            'hill' => $startingBarrenLand['hill'],
            'water' => $startingBarrenLand['water'],
        ];

        #$startingLand[$race->home_land_type] += $startingBuildings['home'];

        return $startingLand;
    }
}
