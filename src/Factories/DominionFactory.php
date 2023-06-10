<?php

namespace OpenDominion\Factories;

use Auth;
use DB;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Advancement;
use OpenDominion\Models\Building;
use OpenDominion\Models\Decree;
use OpenDominion\Models\DecreeState;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionAdvancement;
use OpenDominion\Models\DominionDecreeState;
use OpenDominion\Models\DominionDeity;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\DominionTech;
use OpenDominion\Models\DominionTerrain;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Quickstart;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Tech;
use OpenDominion\Models\Terrain;
use OpenDominion\Models\Title;
use OpenDominion\Models\User;

use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\BarbarianCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

use OpenDominion\Services\Dominion\DeityService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\TerrainService;

class DominionFactory
{

    protected $landHelper;
    protected $raceHelper;
    protected $buildingCalculator;
    protected $barbarianCalculator;
    protected $improvementCalculator;
    protected $spellCalculator;

    protected $deityService;
    protected $resourceService;
    protected $queueService;
    protected $terrainService;

    public function __construct()
    {
        $this->landHelper = app(LandHelper::class);
        $this->raceHelper = app(RaceHelper::class);

        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->barbarianCalculator = app(BarbarianCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);

        $this->deityService = app(DeityService::class);
        $this->resourceService = app(ResourceService::class);
        $this->queueService = app(QueueService::class);
        $this->terrainService = app(TerrainService::class);
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
        Pack $pack = null
    ): Dominion {
        $this->guardAgainstCrossRoundRegistration($user, $realm->round);
        $this->guardAgainstMultipleDominionsInARound($user, $realm->round);
        $this->guardAgainstMismatchedAlignments($race, $realm, $realm->round);

        // Starting resources are based on this.
        $landBase = 1000;

        $startingParameters = [];
        $startingResources = [];

        $startingParameters['prestige'] = $landBase/2;
        $startingParameters['npc_modifier'] = 0;
        $startingParameters['protection_ticks'] = 96;

        foreach($race->units as $unit)
        {
            $startingParameters['unit' . $unit->slot] = 0;
        }

        if($race->alignment == 'npc' and $race->name == 'Barbarian')
        {
            $startingParameters['protection_ticks'] = 0;

            # NPC modifier is a number from 500 to 1000 (skewed toward higher).
            # It is to be used as a multiplier but stored as an int in database.
            $startingParameters['npc_modifier'] = min(rand(500,1200), 1000);

            # For usage in this function, divide npc_modifier by 1000 to create a multiplier.
            $npcModifier = $startingParameters['npc_modifier'] / 1000;

            $landBase *= $npcModifier;
        }

        $startingBuildings = $this->getStartingBuildings($race, $landBase);

        $startingTerrain = $this->getStartingTerrain($race, $landBase);

        # Late-joiner bonus:
        # Give +1.5% starting resources per hour late, max +150% (at 100 hours, mid-day 4).
        # Fix this for zero-starts?
        $lateJoinMultiplier = 1 + $realm->round->ticks * 0.004;

        $startingParameters['draftees'] = 0;

        foreach($race->units as $unit)
        {
            $startingParameters['unit' . $unit->slot] = 0;
        }
        
        $startingParameters['spies'] = 0;
        $startingParameters['wizards'] = 0;
        $startingParameters['archmages'] = 0;
        $startingResources['food'] = 0;

        if($race->name !== 'Barbarian')
        {
            # Override rulername choice
            $rulerName = Auth::user()->display_name;

            $startingParameters['draft_rate'] = 50;

            if(Auth::user()->display_name == $rulerName)
            {
                $startingParameters['prestige'] += 100;
            }

            if($race->name == 'Demon')
            {
                $startingParameters['unit4'] = 1;
            }

            if($race->name == 'Growth')
            {
                $startingParameters['draft_rate'] = 100;
            }

            if($race->name == 'Kerranad')
            {
                $startingResources['gems'] = 400000;
            }

            if($race->name == 'Legion')
            {
                $startingParameters['unit4'] += 1;
            }

            if($race->name == 'Marshling')
            {
                $startingResources['marshling'] = 1500;
            }

            if($race->name == 'Monster')
            {
                $startingParameters['unit1'] = 10;
                $startingParameters['unit2'] = 100;
                $startingParameters['unit3'] = 200;
                $startingParameters['unit4'] = 2;

                $startingParameters['prestige'] = 0;

                $startingParameters['protection_ticks'] = 0;

                $startingResources['strength'] = 25000;

                $startingParameters['draft_rate'] = 0;
            }

            if($race->name == 'Revenants')
            {
                $startingParameters['unit1'] = 4000;
                $startingResources['food'] += 40000;
            }
        }
        else
        {
              $startingParameters['peasants'] = $landBase * (rand(50,200)/100);

              $startingParameters['draft_rate'] = 0;

              # Starting units for Barbarians
              $dpaTarget = $this->barbarianCalculator->getDpaTarget(null, $realm->round, $startingParameters['npc_modifier']);
              $opaTarget = $this->barbarianCalculator->getOpaTarget(null, $realm->round, $startingParameters['npc_modifier']);

              $dpRequired = $landBase * $dpaTarget;
              $opRequired = $landBase * $opaTarget;

              $specsRatio = rand($this->barbarianCalculator->getSetting('SPECS_RATIO_MIN'), $this->barbarianCalculator->getSetting('SPECS_RATIO_MIN'))/100;
              $elitesRatio = 1-$specsRatio;
              $startingParameters['unit3'] = floor(($dpRequired * $elitesRatio)/5);
              $startingParameters['unit2'] = floor(($dpRequired * $specsRatio)/3);

              $specsRatio = rand($this->barbarianCalculator->getSetting('SPECS_RATIO_MIN'), $this->barbarianCalculator->getSetting('SPECS_RATIO_MIN'))/100;
              $elitesRatio = 1-$specsRatio;
              $startingParameters['unit1'] = floor(($opRequired * $specsRatio)/3);
              $startingParameters['unit4'] = floor(($opRequired * $elitesRatio)/5);

              $startingParameters['protection_ticks'] = 0;
        }

        $startingParameters['xp'] = $startingParameters['prestige'];
        $startingParameters['land'] = $landBase;


        # Peasants
        $housingPerBarren = 5;
        $housingPerBarren += $race->getPerkValue('extra_barren_housing');

        $popBonus = 1;
        $popBonus += $race->getPerkMultiplier('max_population');
        $popBonus *= 1 + $startingParameters['prestige']/10000;

        $startingParameters['peasants'] = floor($landBase * $housingPerBarren * $popBonus);

        if($race->getPerkValue('no_population'))
        {
            $startingParameters['peasants'] = 0;
        }

        if(!$race->getPerkValue('no_food_consumption'))
        {
            $startingResources['food'] += floor($startingParameters['peasants'] * 48 * 0.25 * (1 + $race->getPerkValue('food_consumption_raw')));
        }

        foreach($startingResources as $resourceKey => $amount)
        {
            $startingResources[$resourceKey] = $amount * $lateJoinMultiplier;
        }

        $dominion = Dominion::create([
            'user_id' => $user->id,
            'round_id' => $realm->round->id,
            'realm_id' => $realm->id,
            'race_id' => $race->id,
            'title_id' => $title->id,
            'pack_id' => $pack ? $pack->id : null,

            'ruler_name' => $rulerName,
            'name' => $dominionName,
            'prestige' => $startingParameters['prestige'],
            'xp' => $startingParameters['xp'],

            'peasants' => $startingParameters['peasants'],
            'peasants_last_hour' => 0,

            'draft_rate' => $startingParameters['draft_rate'],
            'morale' => 100,
            'spy_strength' => 100,
            'wizard_strength' => 100,

            'military_draftees' => $startingParameters['draftees'],
            'military_unit1' => $startingParameters['unit1'] ?? 0,
            'military_unit2' => $startingParameters['unit2'] ?? 0,
            'military_unit3' => $startingParameters['unit3'] ?? 0,
            'military_unit4' => $startingParameters['unit4'] ?? 0,
            'military_unit5' => $startingParameters['unit5'] ?? 0,
            'military_unit6' => $startingParameters['unit6'] ?? 0,
            'military_unit7' => $startingParameters['unit7'] ?? 0,
            'military_unit8' => $startingParameters['unit8'] ?? 0,
            'military_unit9' => $startingParameters['unit9'] ?? 0,
            'military_unit10' => $startingParameters['unit10'] ?? 0,
            'military_spies' => $startingParameters['spies'] ?? 0,
            'military_wizards' => $startingParameters['wizards'] ?? 0,
            'military_archmages' => $startingParameters['archmages'] ?? 0,

            'land' => $startingParameters['land'],
            'land_plain' => 0,
            'land_mountain' => 0,
            'land_swamp' => 0,
            'land_cavern' => 0,
            'land_forest' => 0,
            'land_hill' =>  0,
            'land_water' => 0,

            'npc_modifier' => $startingParameters['npc_modifier'],

            'protection_ticks' => $startingParameters['protection_ticks'],
        ]);

        $this->buildingCalculator->createOrIncrementBuildings($dominion, $startingBuildings);
        $this->resourceService->updateResources($dominion, $startingResources);
        $this->terrainService->update($dominion, $startingTerrain);

        if($race->name == 'Barbarian')
        {
            $deity = Deity::where('key','ib_tham')->first();
            $this->deityService->completeSubmissionToDeity($dominion, $deity);
        }

        if($race->getPerkValue('starts_devoted_to_azk_hurum'))
        {
            $deity = Deity::where('key','azk_hurum')->first();
            $this->deityService->completeSubmissionToDeity($dominion, $deity);
        }

        if($race->getPerkValue('starts_devoted_to_elskas'))
        {
            $deity = Deity::where('key','elskas')->first();
            $this->deityService->completeSubmissionToDeity($dominion, $deity);
        }

        if($race->getPerkValue('starts_devoted_to_tiranthael'))
        {
            $deity = Deity::where('key','tiranthael')->first();
            $this->deityService->completeSubmissionToDeity($dominion, $deity);
        }

        if($race->getPerkValue('starts_devoted_to_urugdakh'))
        {
            $deity = Deity::where('key','urugdakh')->first();
            $this->deityService->completeSubmissionToDeity($dominion, $deity);
        }

        if($race->getPerkValue('starts_devoted_to_glimj'))
        {
            $deity = Deity::where('key','glimj')->first();
            $this->deityService->completeSubmissionToDeity($dominion, $deity);
        }

        if($race->getPerkValue('starts_devoted_to_druva'))
        {
            $deity = Deity::where('key','druva')->first();
            $this->deityService->completeSubmissionToDeity($dominion, $deity);
        }
        
        # Starting spells on cooldown
        DB::transaction(function () use ($dominion)
        {
            DominionSpell::create([
                'dominion_id' => $dominion->id,
                'caster_id' => $dominion->id,
                'spell_id' => Spell::where('key','sazals_charge')->first()->id,
                'duration' => 0,
                'cooldown' => 192,
            ]);

            DominionSpell::create([
                'dominion_id' => $dominion->id,
                'caster_id' => $dominion->id,
                'spell_id' => Spell::where('key','sazals_fog')->first()->id,
                'duration' => 0,
                'cooldown' => 192,
            ]);
        });

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

        if ($dominionCount > 0)
        {
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
        if($race->alignment == 'npc' and $realm->alignment !== 'npc')
        {
            throw new GameException('Barbarian detected attempting to join non-NPC realm!');
        }

        if(($round->mode == 'standard' or $round->mode == 'standard-duration' or $round->mode == 'artefacts') and $race->alignment !== $realm->alignment)
        {
            throw new GameException('Faction and realm alignment do not match');
        }

        if(($round->mode == 'deathmatch' or $round->mode == 'deathmatch-duration') and ($realm->alignment !== 'players' and $race->alignment !== 'npc'))
        {
            throw new GameException('Faction and realm alignment do not match');
        }

        if(($round->mode == 'factions' or $round->mode == 'factions-duration') and $race->key !== $realm->alignment)
        {
            if($race->key !== 'barbarian')
            {
                throw new GameException('Faction and realm alignment do not match');
            }
            elseif($realm->alignment !== 'npc')
            {
                throw new GameException('Barbarian detected attempting to join non-NPC realm.');
            }
        }
    }

    /**
     * Get amount of buildings a new Dominion starts with.
     *
     * @return array
     */
    protected function getStartingBuildings($race, $landBase): array
    {
        # Default
        $startingBuildings = [];

        if($race->name == 'Kerranad')
        {
            $startingBuildings['farm'] = 50;
            $startingBuildings['smithy'] = 200;
            $startingBuildings['residence'] = 100;
            $startingBuildings['constabulary'] = 25;
            $startingBuildings['lumberyard'] = 50;
            $startingBuildings['ore_mine'] = 100;
            $startingBuildings['gem_mine'] = 300;
            $startingBuildings['tower'] = 50;
            $startingBuildings['wizard_guild'] = 25;
            $startingBuildings['temple'] = 50;
            $startingBuildings['dock'] = 50;
        }
        elseif($race->name == 'Growth')
        {
          $startingBuildings['tissue_swamp'] = $landBase;
        }
        elseif($race->name == 'Myconid')
        {
          $startingBuildings['mycelia'] = $landBase;
        }
        elseif($race->name == 'Barbarian')
        {
            $startingBuildings['farm'] = floor($landBase*0.10);
            $startingBuildings['smithy'] = floor($landBase*0.10);
            $startingBuildings['lumberyard'] = floor($landBase*0.06);
            $startingBuildings['constabulary'] = floor($landBase*0.06);
            $startingBuildings['ore_mine'] = floor($landBase*0.10);
            $startingBuildings['gem_mine'] = floor($landBase*0.10);
            $startingBuildings['barracks'] = floor($landBase*0.20);
            $startingBuildings['tower'] = floor($landBase*0.06);
            $startingBuildings['temple'] = floor($landBase*0.06);
            $startingBuildings['dock'] = floor($landBase*0.10);
        }

        return $startingBuildings;
    }

    public function getStartingTerrain(Race $race, int $landBase): array
    {
        $startingTerrain[$race->homeTerrain()->key] = $landBase;

        return $startingTerrain;
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
     * @param Quickstart $quickstart
     * @return Dominion
     * @throws GameException
     */
    public function createFromQuickstart(
        User $user,
        Realm $realm,
        Race $race,
        string $rulerName,
        string $dominionName,
        Quickstart $quickstart,
        Pack $pack
    ): Dominion {
        $this->guardAgainstCrossRoundRegistration($user, $realm->round);
        $this->guardAgainstMultipleDominionsInARound($user, $realm->round);
        $this->guardAgainstMismatchedAlignments($race, $realm, $realm->round);

        $dominion = Dominion::create([
            'user_id' => $user->id,
            'round_id' => $realm->round->id,
            'realm_id' => $realm->id,
            'race_id' => $race->id,
            'title_id' => $quickstart->title->id,
            'pack_id' => null,

            'ruler_name' => $rulerName,
            'name' => $dominionName,
            'prestige' => $quickstart->prestige,
            'xp' => $quickstart->xp,

            'peasants' => $quickstart->peasants,
            'peasants_last_hour' => 0,

            'draft_rate' => $quickstart->draft_rate,
            'morale' => $quickstart->morale,
            'spy_strength' => $quickstart->spy_strength,
            'wizard_strength' => $quickstart->wizard_strength,

            'military_draftees' => $quickstart->units['draftees'],
            'military_unit1' => $quickstart->units['unit1'] ?? 0,
            'military_unit2' => $quickstart->units['unit2'] ?? 0,
            'military_unit3' => $quickstart->units['unit3'] ?? 0,
            'military_unit4' => $quickstart->units['unit4'] ?? 0,
            'military_unit5' => $quickstart->units['unit5'] ?? 0,
            'military_unit6' => $quickstart->units['unit6'] ?? 0,
            'military_unit7' => $quickstart->units['unit7'] ?? 0,
            'military_unit8' => $quickstart->units['unit8'] ?? 0,
            'military_unit9' => $quickstart->units['unit9'] ?? 0,
            'military_unit10' => $quickstart->units['unit10'] ?? 0,
            'military_spies' => $quickstart->units['spies'],
            'military_wizards' => $quickstart->units['wizards'],
            'military_archmages' => $quickstart->units['archmages'],

            'land' => $quickstart->land,
            'land_plain' => 0,
            'land_mountain' => 0,
            'land_swamp' => 0,
            'land_cavern' => 0,
            'land_forest' => 0,
            'land_hill' => 0,
            'land_water' => 0,

            'npc_modifier' => 0,
            'protection_ticks' => $quickstart->protection_ticks,
        ]);

        $this->improvementCalculator->createOrIncrementImprovements($dominion, $quickstart->improvements);
        $this->buildingCalculator->createOrIncrementBuildings($dominion, $quickstart->buildings);
        $this->resourceService->updateResources($dominion, $quickstart->resources);

        if(isset($quickstart->deity))
        {
            $deity = $quickstart->deity;
            $devotion = $quickstart->devotion_ticks;

            DB::transaction(function () use ($dominion, $deity, $devotion)
            {
                DominionDeity::create([
                    'dominion_id' => $dominion->id,
                    'deity_id' => $deity->id,
                    'duration' => $devotion
                ]);
            });
        }

        # Starting spells active
        foreach($quickstart->spells as $spellKey => $durationData)
        {
            $durationData = explode(',', $durationData);
            $duration = $durationData[0];
            $cooldown = $durationData[1];

            DB::transaction(function () use ($dominion, $spellKey, $duration, $cooldown)
            {
                DominionSpell::create([
                    'dominion_id' => $dominion->id,
                    'caster_id' => $dominion->id,
                    'spell_id' => Spell::where('key', $spellKey)->first()->id,
                    'duration' => $duration,
                    'cooldown' => $cooldown,
                ]);
            });
        }

        foreach($quickstart->advancements as $advancementKey => $level)
        {
            DB::transaction(function () use ($dominion, $advancementKey, $level)
            {
                DominionAdvancement::create([
                    'dominion_id' => $dominion->id,
                    'advancement_id' => Advancement::where('key',$advancementKey)->first()->id,
                    'level' => $level,
                ]);
            });
        }

        foreach($quickstart->decree_states as $decreeState)
        {
            $decreeStateKeys = explode(',', $decreeState);

            $decree = Decree::where('key', $decreeStateKeys[0])->first();
            $decreeState = DecreeState::where('key', $decreeStateKeys[1])->first();

            DB::transaction(function () use ($dominion, $decree, $decreeState)
            {
                DominionDecreeState::create([
                    'dominion_id' => $dominion->id,
                    'decree_id' => $decree->id,
                    'decree_state_id' => $decreeState->id,
                    'tick' => $dominion->round->ticks,
                ]);
            });
        }

        foreach($quickstart->techs as $techKey)
        {
            DB::transaction(function () use ($dominion, $techKey)
            {
                DominionTech::create([
                    'dominion_id' => $dominion->id,
                    'tech_id' => Tech::where('key',$techKey)->first()->id
                ]);
            });
        }

        foreach($quickstart->terrains as $terrainKey => $amount)
        {
            DB::transaction(function () use ($dominion, $terrainKey, $amount)
            {
                DominionTerrain::create([
                    'dominion_id' => $dominion->id,
                    'terrain_id' => Terrain::where('key',$terrainKey)->first()->id,
                    'amount' => $amount
                ]);
            });
        }

        foreach($quickstart->queues as $queueRow)
        {
            $queueRow = explode(',', $queueRow);

            $source = $queueRow[0];
            $resource = $queueRow[1];
            $ticks = $queueRow[2];
            $amount = $queueRow[3];

            $this->queueService->queueResources($source, $dominion, [$resource => $amount], $ticks);
        }

        return $dominion;
    }

}