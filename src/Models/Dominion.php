<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\SelectorService;
#use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Services\Dominion\QueueService;
use Illuminate\Support\Carbon;

/**
 * OpenDominion\Models\Dominion
 *
 * @property int $id
 * @property int $user_id
 * @property int $round_id
 * @property int $realm_id
 * @property int $race_id
 * @property string $name
 * @property string|null $ruler_name
 * @property int $prestige
 * @property int $peasants
 * @property int $peasants_last_hour
 * @property int $draft_rate
 * @property int $morale
 * @property float $spy_strength
 * @property float $wizard_strength
 * @property bool $daily_gold
 * @property bool $daily_land
 * @property int $resource_gold
 * @property int $resource_food
 * @property int $resource_lumber
 * @property int $resource_mana
 * @property int $resource_ore
 * @property int $resource_gems
 * @property float $resource_boats
 * @property int $resource_champion
 * @property int $resource_soul
 * @property int $resource_blood
 * @property int $improvement_science
 * @property int $improvement_keep
 * @property int $improvement_towers
 * @property int $improvement_forges
 * @property int $improvement_walls
 * @property int $improvement_harbor
 * @property int $improvement_armory
 * @property int $improvement_infirmary
 * @property int $improvement_tissue
 * @property int $military_draftees
 * @property int $military_unit1
 * @property int $military_unit2
 * @property int $military_unit3
 * @property int $military_unit4
 * @property int $military_spies
 * @property int $military_wizards
 * @property int $military_archmages
 * @property int $land_plain
 * @property int $land_mountain
 * @property int $land_swamp
 * @property int $land_cavern
 * @property int $land_forest
 * @property int $land_hill
 * @property int $land_water
 * @property int $discounted_land
 * @property int $building_home
 * @property int $building_alchemy
 * @property int $building_farm
 * @property int $building_smithy
 * @property int $building_masonry
 * @property int $building_ore_mine
 * @property int $building_gryphon_nest
 * @property int $building_tower
 * @property int $building_wizard_guild
 * @property int $building_temple
 * @property int $building_gem_mine
 * @property int $building_school
 * @property int $building_lumberyard
 * @property int $building_forest_haven
 * @property int $building_factory
 * @property int $building_guard_tower
 * @property int $building_shrine
 * @property int $building_barracks
 * @property int $building_dock
 * @property \Illuminate\Support\Carbon|null $council_last_read
 * @property \Illuminate\Support\Carbon|null $royal_guard
 * @property \Illuminate\Support\Carbon|null $elite_guard
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $pack_id
 * @property int|null $monarch_dominion_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Council\Thread[] $councilThreads
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\GameEvent[] $gameEventsSource
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\GameEvent[] $gameEventsTarget
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion\History[] $history
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read \OpenDominion\Models\Pack|null $pack
 * @property-read \OpenDominion\Models\Race $race
 * @property-read \OpenDominion\Models\Realm $realm
 * @property-read \OpenDominion\Models\Round $round
 * @property-read \OpenDominion\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion active()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion query()
 * @mixin \Eloquent
 */
class Dominion extends AbstractModel
{
    use Notifiable;

    protected $casts = [
        'prestige' => 'float',
        'xp' => 'integer',
        'peasants' => 'integer',
        'peasants_last_hour' => 'integer',
        'draft_rate' => 'integer',
        'morale' => 'integer',
        'spy_strength' => 'float',
        'wizard_strength' => 'float',
        'prestige' => 'float',

        'military_draftees' => 'integer',
        'military_unit1' => 'integer',
        'military_unit2' => 'integer',
        'military_unit3' => 'integer',
        'military_unit4' => 'integer',
        'military_unit5' => 'integer',
        'military_unit6' => 'integer',
        'military_unit7' => 'integer',
        'military_unit8' => 'integer',
        'military_unit9' => 'integer',
        'military_unit10' => 'integer',
        'military_spies' => 'integer',
        'military_wizards' => 'integer',
        'military_archmages' => 'integer',

        'land' => 'integer',
        'land_plain' => 'integer',
        'land_mountain' => 'integer',
        'land_swamp' => 'integer',
        'land_cavern' => 'integer',
        'land_forest' => 'integer',
        'land_hill' => 'integer',
        'land_water' => 'integer',

        'daily_gold' => 'boolean',
        'daily_land' => 'boolean',

        'royal_guard_active_at' => 'datetime',
        'eltie_guard_active_at' => 'datetime',

        'is_locked' => 'integer',

        'most_recent_improvement_resource' => 'string',
        'most_recent_exchange_from' => 'string',
        'most_recent_exchange_to' => 'string',
        'most_recent_theft_resource' => 'string',

        'npc_modifier' => 'integer',

        'protection_ticks' => 'integer',

        'notes' => 'text',
    ];

    // Relations

    public function councilThreads()
    {
        return $this->hasMany(Council\Thread::class);
    }

    public function gameEventsSource()
    {
        return $this->morphMany(GameEvent::class, 'source');
    }

    public function gameEventsTarget()
    {
        return $this->morphMany(GameEvent::class, 'target');
    }

    public function history()
    {
        return $this->hasMany(Dominion\History::class);
    }

    public function stats()
    {
        return $this->hasMany(DominionStat::class);
    }

    public function pack()
    {
        return $this->belongsTo(Pack::class);
    }

    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    public function title()
    {
        return $this->belongsTo(Title::class);
    }

    public function realm()
    {
        return $this->belongsTo(Realm::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function techs()
    {
        return $this->hasManyThrough(
            Tech::class,
            DominionTech::class,
            'dominion_id',
            'id',
            'id',
            'tech_id'
        );
    }

    public function watchedDominions()
    {
        return $this->hasManyThrough(
            Dominion::class,
            WatchedDominion::class,
            'watcher_id',
            'id',
            'id',
            'dominion_id'
        );
    }

    public function buildings()
    {
        return $this->belongsToMany(
            Building::class,
            'dominion_buildings',
            'dominion_id',
            'building_id'
        )
            ->withTimestamps()
            ->withPivot('owned');
    }

    public function improvements()
    {
        return $this->belongsToMany(
            Improvement::class,
            'dominion_improvements',
            'dominion_id',
            'improvement_id'
        )
            ->withTimestamps()
            ->withPivot('invested');
    }

    public function spells()
    {
        return $this->hasManyThrough(
            Spell::class,
            DominionSpell::class,
            'dominion_id',
            'id',
            'id',
            'spell_id'
        );
    }

    public function resources()
    {
        return $this->belongsToMany(
            Resource::class,
            'dominion_resources',
            'dominion_id',
            'resource_id'
        )
            ->withPivot('amount');
    }

    public function deity()
    {
        return $this->hasOneThrough(
            Deity::class,
            DominionDeity::class,
            'dominion_id',
            'id',
            'id',
            'deity_id'
        );
    }

    public function devotion() # basically $this->dominionDeity() but not really
    {
        return $this->hasOne(DominionDeity::class);
    }

    public function decreeStates()
    {
        return $this->hasManyThrough(
            DecreeState::class,
            DominionDecreeState::class,
            'dominion_id',
            'id',
            'id',
            'decree_state_id'
        );
    }

    public function advancements()
    {
        return $this->belongsToMany(
            Advancement::class,
            'dominion_advancements',
            'dominion_id',
            'advancement_id'
        )
            ->withTimestamps()
            ->withPivot('level');
    }

    public function terrains()
    {
        return $this->belongsToMany(
            Terrain::class,
            'dominion_terrains',
            'dominion_id',
            'terrain_id'
        )
            ->orderBy('order')
            ->withPivot('amount');
    }

    # This code enables the following syntax:
    # $dominion->{'terrain_' . $terrainKey}

    public function __get($key)
    {
        if (preg_match('/^terrain_(\w+)$/', $key, $matches)) {
            return $this->getTerrainAmount($matches[1]);
        }
    
        if (preg_match('/^resource_(\w+)$/', $key, $matches)) {
            return $this->getResourceAmount($matches[1]);
        }
    
        return parent::__get($key);
    }
    
    protected function getTerrainAmount($terrainKey)
    {
        $terrainKey = strtolower($terrainKey);
    
        $terrain = $this->terrains()
            ->where('terrains.key', $terrainKey)
            ->first();
    
        if ($terrain) {
            return $terrain->pivot->amount;
        }
    
        return 0;
    }

    protected function getResourceAmount($resourceKey)
    {
        $resourceKey = strtolower($resourceKey);
    
        $resource = $this->resources()
            ->where('resources.key', $resourceKey)
            ->first();
    
        if ($resource) {
            return $resource->pivot->amount;
        }
    
        return 0;
    }
    
    # Cool, huh?

    public function states()
    {
        return $this->hasMany(DominionState::class);
    }

    public function queues()
    {
        return $this->hasMany(Dominion\Queue::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tick()
    {
        return $this->hasOne(Dominion\Tick::class);
    }

    // PROTECTORSHIP STUFF
    public function protector()
    {
        return $this->hasOneThrough(
            Dominion::class,
            Protectorship::class,
            'protected_id',
            'id',
            'id',
            'protector_id'
        );
    }

    public function protectedDominion()
    {
        return $this->hasOneThrough(
            Dominion::class,
            Protectorship::class,
            'protector_id',
            'id',
            'id',
            'protected_id'
        );
    }

    public function hasProtector()
    {
        return $this->protector ? true : false;
    }

    public function isProtector()
    {
        return $this->protectedDominion ? true : false;
    }

    public function protectorshipOffers()
    {
        return $this->hasMany(ProtectorshipOffer::class, 'protected_id', 'id');
        /*
        return $this->hasManyThrough(
            Dominion::class,
            ProtectorshipOffer::class,
            'protected_id',
            'id',
            'id',
            'protector_id'
        );
        */
    }

    public function protectorshipOffered()
    {
        return $this->hasMany(ProtectorshipOffer::class, 'protector_id', 'id');

        /*
        return $this->hasOneThrough(
            Dominion::class,
            ProtectorshipOffer::class,
            'protector_id',
            'id',
            'id',
            'protected_id'
        );
        */
    }

    // END PROTECTORSHIP STUFF

    // Eloquent Query Scopes

    public function scopeActive(Builder $query)
    {
        return $query->whereHas('round', function (Builder $query)
        {
            $query->whereRaw('start_date <= NOW()
                                and (end_date IS NULL or end_date > NOW())
                                and (end_tick IS NULL or end_tick > ticks)');
        });
    }

    // Methods

    // todo: move to eloquent events, see $dispatchesEvents
    public function save(array $options = [])
    {
        $recordChanges = isset($options['event']);

        // Verify tick hasn't happened during this request
        if ($this->exists && $this->last_tick_at != $this->fresh()->last_tick_at)
        {
            throw new GameException('The World Spinner is spinning the world. Your request was discarded. Try again soon, little one.');
        }

        $saved = parent::save($options);

        if ($saved && $recordChanges)
        {
            $dominionHistoryService = app(HistoryService::class);
            $deltaAttributes = $dominionHistoryService->getDeltaAttributes($this);
            if (isset($options['action'])) {
                $deltaAttributes['action'] = $options['action'];
            }
            /** @noinspection PhpUndefinedVariableInspection */
            $dominionHistoryService->record($this, $deltaAttributes, $options['event']);
        }

        // Recalculate next tick
        $tickService = app(\OpenDominion\Services\Dominion\TickService::class);
        $tickService->precalculateTick($this);

        return $saved;
    }

    public function getDirty()
    {
        $dirty = parent::getDirty();

        $query = $this->newModelQuery();

        $dominionHistoryService = app(HistoryService::class);
        $deltaAttributes = $dominionHistoryService->getDeltaAttributes($this);

        foreach ($deltaAttributes as $attr => $value) {
            if (gettype($this->getAttribute($attr)) != 'boolean' and gettype($this->getAttribute($attr)) != 'string') {
                $wrapped = $query->toBase()->grammar->wrap($attr);
                $dirty[$attr] = $query->toBase()->raw("$wrapped + $value");
            }
        }

        return $dirty;
    }

    /**
     * Route notifications for the mail channel.
     *
     * @return string
     */
    public function routeNotificationForMail(): string
    {
        if($this->isAbandoned())
        {
            return "abandoned-{$dominion->id}@odarena.com";
        }
        return $this->user->email;
    }

    /**
     * Returns whether this Dominion instance is selected by the logged in user.
     *
     * @return bool
     */
    public function isSelectedByAuthUser()
    {
        // todo: move to SelectorService
        $dominionSelectorService = app(SelectorService::class);

        $selectedDominion = $dominionSelectorService->getUserSelectedDominion();

        if ($selectedDominion === null) {
            return false;
        }

        return ($this->id === $selectedDominion->id);
    }

    /**
     * Returns whether this Dominion is locked due to the round having ended or administrative action.
     *
     * Locked Dominions cannot perform actions and are read-only.
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->is_locked || $this->round->hasEnded();
    }

    public function isUnderProtection(): bool
    {
       return $this->protection_ticks;
    }

    public function isAbandoned(): bool
    {
        return $this->user_id ? false : true;
    }

    public function getLockedReason(int $reason): string
    {
        switch ($reason)
        {
            case 2:
                return "Player's request.";

            case 3:
                return "Rule violation.";

            case 4:
                return "Experimental faction deemed overpowered or for other reason taken out of play.";

            case 5:
                return "Unfortunately, a restart required due to pre-round balance changes having taken place.";

            default:
                return 'Round ended.';
        }
    }

    /**
     * Returns whether this Dominion is the monarch for its realm.
     *
     * @return bool
     */
    public function isMonarch()
    {
        $monarch = $this->realm->monarch;
        return ($monarch !== null && $this->id == $monarch->id);
    }

    /**
     * Returns the choice for monarch of a Dominion.
     *
     * @return Dominion
     */
    public function monarchVote()
    {
        return $this->hasOne(static::class, 'id', 'monarchy_vote_for_dominion_id');
    }

    /**
     * Returns the unit production bonus for a specific resource type (across all eligible units) for this dominion.
     *
     * @param string $resourceType
     * @return float
     */
    public function getUnitPerkProductionBonus(string $resourceType): float
    {
        $bonus = 0;

        foreach ($this->race->units as $unit) {
            $perkValue = $unit->getPerkValue($resourceType);

            if ($perkValue !== 0) {
                $bonus += ($this->{'military_unit' . $unit->slot} * (float)$perkValue);
            }
        }

        return $bonus;
    }

    /**
     * Returns the unit production bonus for a specific resource type (across all eligible units) for this dominion.
     *
     * @param string $resourceType
     * @return float
     */
    public function getUnitPerkProductionBonusFromTitle(string $resourceType): float
    {
        $bonus = 0;

        foreach ($this->race->units as $unit)
        {
            $titlePerkData = $this->race->getUnitPerkValueForUnitSlot($unit->slot, 'production_from_title', null);

            if($titlePerkData)
            {
                $titleKey = $titlePerkData[0];
                $perkResource = $titlePerkData[1];
                $perkAmount = $titlePerkData[2];

                if($resourceType === $perkResource and $this->title->key === $titleKey)
                {
                    $bonus += ($this->{'military_unit' . $unit->slot} * (float)$perkAmount);
                }
            }
        }

        return $bonus;
    }

    # TECHS

    protected function getTechPerks()
    {
        return $this->techs->flatMap(
            function ($tech) {
                return $tech->perks;
            }
        );
    }

    /**
     * @param string $key
     * @return float
     */
    public function getTechPerkValue(string $perkKey): float
    {
        
        $perks = $this->getTechPerks()->groupBy('key');
        if (isset($perks[$perkKey])) {
            $max = (float)$perks[$perkKey]->max('pivot.value');
            if ($max < 0) {
                return (float)$perks[$perkKey]->min('pivot.value');
            }
            return $max;
        }
        return 0;

        /*
        $perk = 0;

        foreach ($this->techs as $tech)
        {
            if($perkValueString = $tech->getPerkValue($perkKey))
            {
                #$level = $this->techs()->where('tech_id', $tech->id)->first()->pivot->level;
                #$levelMultiplier = $this->getAdvancementLevelMultiplier($level);

                $perk += $perkValueString;# * $levelMultiplier;
             }
        }

        return $perk;
        */

    }

    /**
     * @param string $key
     * @return float
     */
    public function getTechPerkMultiplier(string $key): float
    {
        return ($this->getTechPerkValue($key) / 100);
    }

    # BUILDINGS
    protected function getBuildingPerks()
    {
        return $this->buildings->flatMap(
            function ($building)
            {
                return $building->perks;
            }
        );
    }

    public function getBuildingPerkValue(string $perkKey)
    {
        $landSize = $this->land;#$this->land_plain + $this->land_mountain + $this->land_swamp + $this->land_forest + $this->land_hill + $this->land_water;
        $perk = 0;

        foreach ($this->buildings as $building)
        {
            $perkValueString = $building->getPerkValue($perkKey);

            $perkValueString = is_numeric($perkValueString) ? (float)$perkValueString : $perkValueString;
            
            if(in_array($perkKey, ['housing','jobs']))
            {
                if(is_numeric($perkValueString))
                {
                    $perk += $perkValueString * $building->pivot->owned;
                }
                else
                {
                    $defaultPerkValueString = ($perkKey == 'housing') ? 15 : 20;
                    $perk += $defaultPerkValueString * $building->pivot->owned;
                }
            }
            elseif(!in_array($perkKey,['jobs','housing']) and $perkValueString)
            {
                # Basic production and other single-value perks
                $singleValuePerks = [
                    'gold_production_raw',
                    'food_production_raw',
                    'ore_production_raw',
                    'lumber_production_raw',
                    'mana_production_raw',
                    'gems_production_raw',
                    'blood_production_raw',
                    'soul_production_raw',
                    'pearls_production_raw',
                    'horse_production_raw',
                    'mud_production_raw',
                    'swamp_gas_production_raw',
                    'marshling_production_raw',
                    'thunderstone_production_raw',
                    'miasma_production_raw',
                    'yak_production_raw',
                    'kelp_production_raw',
                    'gunpowder_production_raw',
                    'magma_production_raw',
                    'obsidian_production_raw',
                
                    'gunpowder_storage_raw',
                    'sapling_storage_raw',
                
                    'gold_upkeep_raw',
                    'food_upkeep_raw',
                    'ore_upkeep_raw',
                    'lumber_upkeep_raw',
                    'mana_upkeep_raw',
                    'blood_upkeep_raw',
                    'soul_upkeep_raw',
                    'pearls_upkeep_raw',
                    'prisoner_upkeep_raw',
                
                    'gold_theft_protection',
                    'food_theft_protection',
                    'ore_theft_protection',
                    'lumber_theft_protection',
                    'mana_theft_protection',
                    'gems_theft_protection',
                    'blood_theft_protection',
                    'soul_theft_protection',
                    'pearls_theft_protection',
                
                    'xp_generation_raw',
                
                    // Building-specific housing
                    'artillery_unit1_housing',
                    'afflicted_unit1_housing',
                    'aurei_unit1_housing',
                    'dwarg_unit1_housing',
                    'cires_unit1_housing',
                    'cires_unit2_housing',
                    'norse_unit1_housing',
                    'sacred_order_unit2_housing',
                    'sacred_order_unit3_housing',
                    'sacred_order_unit4_housing',
                    'snow_elf_unit1_housing',
                    'troll_unit2_housing',
                    'troll_unit4_housing',
                    'vampires_unit1_housing',
                    'revenants_unit1_housing',
                    'revenants_unit2_housing',
                    'revenants_unit3_housing',
                
                    'spy_housing',
                    'wizard_housing',
                    'military_housing',
                    'draftee_housing',
                
                    'ammunition_units_housing',
                
                    // Military
                    'raw_defense',
                    'dimensionalists_unit1_production_raw',
                    'dimensionalists_unit2_production_raw',
                    'dimensionalists_unit3_production_raw',
                    'dimensionalists_unit4_production_raw',
                
                    'snow_elf_unit4_production_raw',
                
                    'unit_send_capacity',
                
                    // Uncategorised
                    'crypt_bodies_decay_protection',
                    'faster_returning_units',
                ];

                $ratioMultiplierMaxPerks = [
                    // OP/DP mods
                    'defensive_power',
                    'offensive_power',
                    'attacker_offensive_power_mod',
                    'target_defensive_power_mod',
                    'casualties_on_offense',
                    'casualties_on_defense',
                    'increases_enemy_casualties_on_offense',
                    'increases_enemy_casualties_on_defense',
                    'casualties',
                    'morale_gains',
                    'prestige_gains',
                    'base_morale',
                    'faster_return',
                
                    // Production and Resources mods
                    'gold_production_mod',
                    'food_production_mod',
                    'lumber_production_mod',
                    'ore_production_mod',
                    'gems_production_mod',
                    'mana_production_mod',
                    'xp_generation_mod',
                    'pearls_production_mod',
                    'blood_production_mod',
                    'mud_production_mod',
                    'swamp_gas_production_mod',
                    'miasma_production_mod',
                    'exchange_rate',

                    'blood_resource_conversion_mod',
                
                    // Unit costs
                    'unit_gold_costs',
                    'unit_ore_costs',
                    'unit_lumber_costs',
                    'unit_mana_costs',
                    'unit_food_costs',
                    'unit_blood_costs',
                
                    // Unit training
                    'extra_units_trained',
                    'drafting',
                    'snow_elf_unit4_production_mod',
                    'training_time_mod',
                    'spy_training_time_mod',
                    'wizards_training_time_mod',
                
                    'dimensionalists_unit1_production_mod',
                    'dimensionalists_unit2_production_mod',
                    'dimensionalists_unit3_production_mod',
                    'dimensionalists_unit4_production_mod',
                
                    // Spy/wizard
                    'spell_cost',
                    'spy_losses',
                    'spy_strength_recovery',
                    'wizard_losses',
                    'wizard_strength',
                    'wizard_strength_recovery',
                    'wizard_cost',
                
                    // Construction/Rezoning and Land
                    'construction_cost',
                    'rezone_cost',
                    'land_discovered',
                    'construction_time',
                
                    // Espionage
                    'gold_theft_reduction',
                    'gems_theft_reduction',
                    'lumber_theft_reduction',
                    'ore_theft_reduction',
                    'food_theft_reduction',
                    'horse_theft_reduction',
                    'magma_theft_reduction',
                    'obsidian_theft_reduction',
                
                    // Improvements
                    'improvements_capped',
                    'improvements_interest',
                    'invest_bonus',
                    'gold_invest_bonus',
                    'food_invest_bonus',
                    'ore_invest_bonus',
                    'lumber_invest_bonus',
                    'mana_invest_bonus',
                    'blood_invest_bonus',
                    'soul_invest_bonus',
                    'obsidian_invest_bonus',
                
                    // Other/special
                    'deity_power',
                    'population_capped',
                    'population_growth_capped',
                ];

                if(in_array($perkKey, $singleValuePerks))
                {
                    $perk += $perkValueString * $building->pivot->owned;
                }

                # Mods with ratio, multiplier, and max
                elseif(in_array($perkKey, $ratioMultiplierMaxPerks))
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);
                    $ratio = (float)$perkValues[0];
                    $multiplier = (float)$perkValues[1];
                    $max = (float)$perkValues[2] / 100;
                    $owned = $building->pivot->owned;

                    if($multiplier < 0)
                    {
                        $perk += max($owned / $landSize * $ratio * $multiplier, $max*-1) * 100;
                    }
                    else
                    {
                        $perk += min($owned / $landSize * $ratio * $multiplier, $max) * 100;
                    }

                }
                # Mods with ratio, multiplier, and no max
                elseif(
                        # OP/DP mods
                        $perkKey == 'improvements'
                        or $perkKey == 'damage_from_lightning_bolt'
                        or $perkKey == 'damage_from_fireball'
                        or $perkKey == 'population_growth'
                        or $perkKey == 'reduces_conversions'
                        or $perkKey == 'reduces_attrition'
                        or $perkKey == 'unit_pairing'

                        # Spy/wizard
                        or $perkKey == 'wizard_strength'
                        or $perkKey == 'spy_strength'
                        or $perkKey == 'wizard_strength_on_defense'
                        or $perkKey == 'spy_strength_on_defense'
                        or $perkKey == 'wizard_strength_on_offense'
                        or $perkKey == 'spy_strength_on_offense'

                        # Other/special
                        or $perkKey == 'population_uncapped'
                    )
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);
                    $ratio = (float)$perkValues[0];
                    $multiplier = (float)$perkValues[1];
                    $owned = $building->pivot->owned;

                    $perk += ($owned / $landSize * $ratio * $multiplier) * 100;
                }
                # Production depleting
                elseif(
                        # OP/DP mods
                        $perkKey == 'gold_production_depleting_raw'
                        or $perkKey == 'gems_production_depleting_raw'
                        or $perkKey == 'ore_production_depleting_raw'
                        or $perkKey == 'mana_production_depleting_raw'
                        or $perkKey == 'lumber_production_depleting_raw'
                        or $perkKey == 'food_production_depleting_raw'
                    )
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);
                    $baseProduction = (float)$perkValues[0];
                    $ticklyReduction = (float)$perkValues[1];
                    $ticks = $this->round->ticks;
                    $buildingOwned = $building->pivot->owned;

                    $perk += $buildingOwned * max(0, ($baseProduction - ($ticklyReduction * $ticks)));
                }
                # Production/housing increasing
                elseif(
                        # OP/DP mods
                        $perkKey == 'gold_production_increasing_raw'
                        or $perkKey == 'gems_production_increasing_raw'
                        or $perkKey == 'ore_production_increasing_raw'
                        or $perkKey == 'mana_production_increasing_raw'
                        or $perkKey == 'lumber_production_increasing_raw'
                        or $perkKey == 'food_production_increasing_raw'

                        or $perkKey == 'housing_increasing'
                        or $perkKey == 'military_housing_increasing'
                        or $perkKey == 'faster_returning_units_increasing'
                    )
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);
                    $baseValue = (float)$perkValues[0];
                    $ticklyIncrease = (float)$perkValues[1];
                    $ticks = $this->round->ticks;
                    $buildingOwned = $building->pivot->owned;

                    $perk += $buildingOwned * ($baseValue + ($ticklyIncrease * $ticks));

                }
                # Resource conversion
                elseif($perkKey == 'resource_conversion')
                {

                    $resourceCalculator = app(ResourceCalculator::class);
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);

                    $sourceAmount = (float)$perkValues[0];
                    $sourceResourceKey = (string)$perkValues[1];
                    $targetAmount = (float)$perkValues[2];
                    $targetResourceKey = (string)$perkValues[3];
                    $buildingOwned = $building->pivot->owned;

                    $maxAmountConverted = min($resourceCalculator->getAmount($this, $sourceResourceKey), $buildingOwned * $sourceAmount);
                    $amountCreated = $maxAmountConverted / ($sourceAmount / $targetAmount);

                    return ['from' => [$sourceResourceKey => $maxAmountConverted], 'to' => [$targetResourceKey => $amountCreated]];

                }

                # Peasants conversion (single resource)
                elseif($perkKey == 'peasants_conversion')
                {

                    $availablePeasants = max($this->peasants-1000, 0); #min($this->peasants, max(1000, $this->peasants-1000));
                    $resourceCalculator = app(ResourceCalculator::class);
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);

                    $sourceAmount = (float)$perkValues[0];
                    $sourceResourceAmount = $availablePeasants;
                    $targetAmount = (float)$perkValues[1];
                    $targetResourceKey = (string)$perkValues[2];
                    $buildingOwned = $building->pivot->owned;

                    $maxAmountConverted = min($sourceResourceAmount, $buildingOwned * $sourceAmount);
                    $amountCreated = $maxAmountConverted / ($sourceAmount / $targetAmount);

                    return ['from' => ['peasants' => $maxAmountConverted], 'to' => [$targetResourceKey => $amountCreated]];
                }

                # Peasants conversion (multiple resources)
                elseif($perkKey == 'peasants_conversions')
                {

                    $availablePeasants = max($this->peasants-1000, 0); #min($this->peasants, max(1000, $this->peasants-1000));
                    $resourceCalculator = app(ResourceCalculator::class);
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);

                    $sourceAmount = (float)$perkValues[0];
                    $sourceResourceAmount = $availablePeasants;
                    $buildingOwned = $building->pivot->owned;
                    $maxAmountConverted = min($sourceResourceAmount, $buildingOwned * $sourceAmount);

                    $result['from']['peasants'] = $maxAmountConverted;

                    foreach($perkValues as $perkValue)
                    {
                        if(is_array($perkValue))
                        {
                            $targetAmount = (float)$perkValue[0];
                            $targetResourceKey = (string)$perkValue[1];
                            $amountCreated = $maxAmountConverted / ($sourceAmount / $targetAmount);
                            $result['to'][$targetResourceKey] = $amountCreated;
                        }
                    }

                    return $result;
                }
                # Dark Elven slave workers
                elseif(
                          $perkKey == 'ore_production_raw_from_prisoner' or
                          $perkKey == 'gold_production_raw_from_prisoner' or
                          $perkKey == 'gems_production_raw_from_prisoner'
                      )
                {
                    $resourceCalculator = app(ResourceCalculator::class);
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);

                    $prisoners = $resourceCalculator->getAmount($this, 'prisoner');
                    $productionPerPrisoner = (float)$perkValues[0];
                    $maxResourcePerBuilding = (float)$perkValues[1];
                    $buildingOwned = $building->pivot->owned;

                    $maxPrisonersWorking = $buildingOwned * $maxResourcePerBuilding;

                    $prisonersWorking = min($maxPrisonersWorking, $prisoners);

                    $perk += floor($prisonersWorking * $productionPerPrisoner);
                }
                elseif(
                          $perkKey == 'thunderstone_production_raw_random'
                      )
                {
                    $randomlyGenerated = 0;
                    $randomChance = (float)$perkValueString / 100;
                    $buildingOwned = $building->pivot->owned;

                    for ($trials = 1; $trials <= $buildingOwned; $trials++)
                    {
                        if(random_chance($randomChance))
                        {
                            $randomlyGenerated += 1;
                        }
                    }

                    $perk += $randomlyGenerated;
                }
                elseif(
                          $perkKey == 'dimensionalists_unit1_production_raw_capped' or
                          $perkKey == 'dimensionalists_unit2_production_raw_capped' or
                          $perkKey == 'dimensionalists_unit3_production_raw_capped' or
                          $perkKey == 'dimensionalists_unit4_production_raw_capped' or
                          $perkKey == 'snow_elf_unit4_production_raw_capped' or
                          $perkKey == 'aurei_unit2_production_raw_capped'
                      )
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);

                    $unitPerBuilding = (float)$perkValues[0];
                    $maxBuildingRatio = (float)$perkValues[1] / 100;

                    $availableBuildings = min($building->pivot->owned, floor($landSize * $maxBuildingRatio));

                    $perk += $availableBuildings * $unitPerBuilding;
                }
                # Buildings where we only ever want a single value
                elseif(
                          $perkKey == 'unit_production_from_wizard_ratio' or
                          $perkKey == 'unit_production_from_spy_ratio' # Unused
                      )
                {
                    $perk = (float)$perkValueString;
                }
                elseif($perkKey == 'attrition_protection')
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);
                    $amount = (float)$perkValues[0];
                    $slot = (int)$perkValues[1];
                    $raceName = (string)$perkValues[2];

                    if($this->race->name == $raceName)
                    {
                        return [$building->pivot->owned * $amount, $slot];
                    }
                }

                # Building self-destruction
                elseif($perkKey == 'destroys_itself_and_land')
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);

                    $amountToDestroyPerBuilding = (float)$perkValues[0];
                    $landTypeToDestroy = (string)$perkValues[1];
                    $buildingOwned = $building->pivot->owned;

                    $amountToDestroy = $buildingOwned * $amountToDestroyPerBuilding;
                    #$amountToDestroy = intval($amountToDestroy) + (rand()/getrandmax() < fmod($amountToDestroy, 1) ? 1 : 0);
                    $amountToDestroy = (int)floor($amountToDestroy);

                    $result = ['building_key' => $building->key, 'amount' => $amountToDestroy, 'land_type' => $landTypeToDestroy];

                    return $result;
                }

                # Building self-destruction
                elseif($perkKey == 'destroys_itself')
                {
                    $perkValues = (float)$perkValueString;#$this->extractBuildingPerkValues($perkValueString);

                    $amountToDestroyPerBuilding = $perkValues;
                    $buildingOwned = $building->pivot->owned;

                    $amountToDestroy = $buildingOwned * $amountToDestroyPerBuilding;
                    $amountToDestroy = (int)floor($amountToDestroy);

                    $result = ['building_key' => $building->key, 'amount' => $amountToDestroy];

                    return $result;
                }

                # Time-based production (which is always on during protection but at half speed)
                elseif($perkKey == 'light_production_raw_from_time')
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);
                    $amountProduced = (float)$perkValues[2];
                    $hourFrom = $perkValues[0];
                    $hourTo = $perkValues[1];
    
                    if($this->isUnderProtection())
                    {
                        $perk += ($amountProduced * $building->pivot->owned) / 2;
                    }
                    elseif (
                        (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                        (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                    )
                    {
                        $perk += $amountProduced * $building->pivot->owned;
                    }
                }

                elseif($perkKey == ($this->race->key . '_unit_housing'))
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);
                    $buildingsOwned = $building->pivot->owned;

                    $result = [];

                    if(!is_array($perkValues[0]))
                    {
                        $perkValues[0] = [$perkValues[0], $perkValues[1]];
                        unset($perkValues[1]);
                    }

                    foreach($perkValues as $key => $perkValue)
                    {
                        $unitSlot = (int)$perkValue[0];
                        $amountHoused = (float)$perkValue[1];
                            
                        $amountHousable = $amountHoused * $buildingsOwned;
                        $amountHousable = intval($amountHousable);

                        $result[$unitSlot] = (isset($result[$unitSlot]) ? $result[$unitSlot] + $amountHousable : $amountHousable);
                    }

                    return $result;
                }

                elseif($perkKey !== 'jobs' and $perkKey !== 'housing')
                {
                    dd("[Error] Undefined building perk key (\$perkKey): $perkKey");
                }

                # Build-specific perks
                $buildingSpecificMultiplier = 1;

                if($perkKey == 'gold_production_raw')
                {
                    $buildingSpecificMultiplier += $this->getDecreePerkMultiplier('building_' . $building->key . '_production_mod');
                    $buildingSpecificMultiplier += $this->getSpellPerkMultiplier('building_' . $building->key . '_production_mod');
                }

                if($perkKey == 'extra_units_trained' or $perkKey == 'improvements')
                {
                    $buildingSpecificMultiplier += $this->getDecreePerkMultiplier('building_' . $building->key . '_perk_mod');
                    $buildingSpecificMultiplier += $this->getSpellPerkMultiplier('building_' . $building->key . '_perk_mod');
                    $buildingSpecificMultiplier += $this->getTechPerkMultiplier('building_' . $building->key . '_perk_mod');
                }
            }

            $perk *= $buildingSpecificMultiplier ?? 1;
        }

        return $perk;
    }

    /**
     * @param string $key
     * @return float
     */
    public function getBuildingPerkMultiplier(string $key): float
    {
        return ($this->getBuildingPerkValue($key) / 100);
    }

    public function extractBuildingPerkValues(string $perkValue)
    {
        if (str_contains($perkValue, ',')) {
            $perkValues = explode(',', $perkValue);

            foreach($perkValues as $key => $value) {
                if (!str_contains($value, ';')) {
                    continue;
                }

                $perkValues[$key] = explode(';', $value);
            }
        }
        else
        {
            $perkValues = $perkValue;
        }

        return $perkValues;
    }

    # SPELLS

    public function isSpellActive(string $spellKey): bool
    {
        return $this->spells()
            ->where('key', $spellKey)
            ->where('duration', '>', 0)
            ->exists();
    }
    
    public function hasSpellCast(string $spellKey): bool
    {
        $spell = Spell::where('key', $spellKey)->firstOrFail();

        return $this->spellsCast()
            ->where('spell_id', $spell->id)
            ->where('dominion_id', '!=', $this->id)
            ->exists();
    }

    public function spellsCast()
    {
        return $this->hasMany(DominionSpell::class, 'caster_id');
    }


    protected function getSpellPerks()
    {
      return $this->spells->flatMap(
          function ($spell) {
              return $spell->perks;
          }
      );
    }
    /**
    * @param string $key
    * @return float
    */

    public function getSpellPerkValue(string $perkKey): float
    {
        $deityKey = $this->hasDeity() ? $this->deity->key : null;
        $perk = 0;

        # Check each spell
        foreach ($this->spells as $spell)
        {
            # Get the dominion spell object

            $dominionSpell = DominionSpell::where('spell_id',$spell->id)->where(function($query) {
                    $query->where('caster_id','=',$this->id)
                          ->orWhere('dominion_id','=',$this->id);
            })
            ->first();

            $perkValueString = $spell->getPerkValue($perkKey);

            if($dominionSpell and $spell->perks->filter(static function (SpellPerkType $spellPerkType) use ($perkKey) { return ($spellPerkType->key === $perkKey); }) and $dominionSpell->duration > 0 and $perkValueString !== 0)
            {
                if(is_numeric($perkValueString))
                {
                    $perk += (float)$perkValueString;
                }
                # Deity spells (no max): deityKey, perk, max
                elseif(in_array($perkKey, ['offensive_power_from_devotion', 'defense_from_devotion']))
                {
                    $perkValueArray = $spell->getActiveSpellPerkValues($spell->key, $perkKey);

                    $deityKey = $perkValueArray[0];
                    $perTick = (float)$perkValueArray[1];
                    $max = (int)$perkValueArray[2];

                    if($this->hasDeity() and $this->deity->key == $deityKey)
                    {
                        $perk += min($this->devotion->duration * $perTick, $max);
                    }
                }
                elseif($perkKey == 'defense_from_resource')
                {
                    $resourceCalculator = app(ResourceCalculator::class);

                    $perkValueArray = $spell->getActiveSpellPerkValues($spell->key, $perkKey);

                    $dpPerResource = (float)$perkValueArray[0];
                    $resourceKey = (string)$perkValueArray[1];

                    $perk = $resourceCalculator->getAmount($this, $resourceKey) * $dpPerResource;
                }
                elseif($perkKey == 'resource_lost_on_invasion')
                {
                    return True;
                }
                elseif($perkKey == 'elk_production_raw_from_land')
                {
                    $perkValueArray = $spell->getActiveSpellPerkValues($spell->key, $perkKey);

                    $perAcre = (float)$perkValueArray[0];
                    $landType = (string)$perkValueArray[1];

                    $perk += floor($perAcre * $this->{'land_' . $landType});
                }
                elseif($perkKey == 'training_time_raw_from_morale')
                {
                    $perkValueArray = $spell->getActiveSpellPerkValues($spell->key, $perkKey);

                    $perMoraleChunk = (float)$perkValueArray[0];
                    $moraleChunk = (int)$perkValueArray[1];
                    $max = (int)$perkValueArray[2];

                    $reduction = floor($this->morale / $moraleChunk) * $perMoraleChunk;

                    $perk += max($reduction, $max);
                }
                else
                {
                    dd("[Error] Undefined spell perk type:", $perkKey, $perkValueString);
                }
            }

            if(isset($spell->deity))
            {
                if(!$this->hasDeity() or $spell->deity->id !== $this->deity->id)
                {
                    $perk = 0;
                }
            }

            if(($spellDamageSufferedPerk = $this->getTechPerkMultiplier($spell->key . '_spell_damage_suffered')))
            {
                $perk *= (1 + $spellDamageSufferedPerk);
            }
        }

        return $perk;
    }

    /**
    * @param string $key
    * @return float
    */
    public function getSpellPerkMultiplier(string $key): float
    {
        return ($this->getSpellPerkValue($key) / 100);
    }

    # TITLE
    public function getTitlePerkMultiplier(): float
    {
        if($this->race->getPerkValue('no_ruler_title_perks'))
        {
            return 0;
        }

        $multiplier = 1;
        $multiplier += (1 - exp(-pi()*$this->xp / 100000));
        $multiplier += $this->getImprovementPerkMultiplier('title_bonus');
        $multiplier += $this->getBuildingPerkMultiplier('title_bonus');
        $multiplier += $this->race->getPerkMultiplier('title_bonus');

        return $multiplier;
    }

    # IMPROVEMENTS

    protected function getImprovementPerks()
    {
        return $this->improvements->flatMap(
            function ($improvement)
            {
                return $improvement->perks;
            }
        );
    }

   /**
    * @param string $key
    * @return float
    */
    public function getImprovementPerkValue(string $perkKey): float
    {
        $perk = 0;

        foreach ($this->improvements as $improvement)
        {
            if($perkValueString = $improvement->getPerkValue($perkKey))
            {
                $perkValues = $this->extractImprovementPerkValues($perkValueString);
                $max = (float)$perkValues[0];
                $coefficient = (float)$perkValues[1];
                $invested = (float)$improvement->pivot->invested;

                $perk += $max * (1 - exp(-$invested / ($coefficient * $this->land + 15000)));
            }
        }

        $perk *= $this->getImprovementsMod();

        return $perk;
    }

    public function getImprovementsMod(string $perkKey = null): float
    {
        $multiplier = 1;
        $multiplier += $this->getBuildingPerkMultiplier('improvements');
        $multiplier += $this->getBuildingPerkMultiplier('improvements_capped');
        $multiplier += $this->getSpellPerkMultiplier('improvements');
        $multiplier += $this->getAdvancementPerkMultiplier('improvements');
        $multiplier += $this->getTechPerkMultiplier('improvements');
        #$multiplier += $this->getDeityPerkMultiplier('improvements'); # Breaks
        $multiplier += $this->race->getPerkMultiplier('improvements_max');
        $multiplier += $this->realm->getArtefactPerkMultiplier('improvements');
        $multiplier += $this->getDecreePerkMultiplier('improvements'); 

        if($this->race->getPerkValue('improvements_from_souls'))
        {
            $resourceCalculator = app(ResourceCalculator::class);
            $multiplier += $resourceCalculator->getAmount($this, 'soul') / ($this->land * 1000);
        }

        if($improvementsPerVictoryPerk = $this->race->getPerkValue('improvements_per_net_victory'))
        {
            $militaryCalculator = app(MilitaryCalculator::class);
            $multiplier += (max($militaryCalculator->getNetVictories($this),0) * $improvementsPerVictoryPerk) / 100;
        }

        $multiplier = max(0, $multiplier);

        return $multiplier;
    }

    /**
     * @param string $key
     * @return float
     */
    public function getImprovementPerkMultiplier(string $key): float
    {
        return ($this->getImprovementPerkValue($key) / 100);
    }

    public function extractImprovementPerkValues(string $perkValue)
    {
        if (str_contains($perkValue, ','))
        {
            $perkValues = explode(',', $perkValue);

            foreach($perkValues as $key => $value)
            {
                if (!str_contains($value, ';'))
                {
                    continue;
                }

                $perkValues[$key] = explode(';', $value);
            }
        }

        return $perkValues;
    }

    # DEITY

    public function hasDeity()
    {
        return $this->deity ? true : false;
    }

    public function hasPendingDeitySubmission(): bool
    {
        $queueService = app(QueueService::class);
        return $queueService->getDeityQueue($this)->count();
    }

    public function getPendingDeitySubmission()
    {
        if($this->hasPendingDeitySubmission())
        {
            $queueService = app(QueueService::class);

            foreach($queueService->getDeityQueue($this) as $row)
            {
                $deityKey = $row['resource'];
            }

            return Deity::where('key', $deityKey)->first();
        }

        return false;
    }

    public function getPendingDeitySubmissionTicksLeft(): int
    {
        if(!$this->hasPendingDeitySubmission())
        {
            return 0;
        }

        $queueService = app(QueueService::class);

        foreach($queueService->getDeityQueue($this) as $row)
        {
            $ticksLeft = $row['hours'];
        }

        return $ticksLeft;
    }

    public function getDominionDeity(): DominionDeity
    {
        return DominionDeity::where('deity_id', $this->deity->id)
                            ->where('dominion_id', $this->id)
                            ->first();
    }

    /**
    * @param string $key
    * @return float
    */
    public function getDeityPerkValue(string $perkKey): float
    {
        if(!$this->deity)
        {
            return 0;
        }

        $multiplier = 1;
        $multiplier += $this->getBuildingPerkMultiplier('deity_power');
        $multiplier += $this->race->getPerkMultiplier('deity_power');
        $multiplier += $this->title->getPerkMultiplier('deity_power') * $this->getTitlePerkMultiplier();
        $multiplier += $this->getDecreePerkMultiplier('deity_power');
        
        $devotionDurationMultiplier = 1 + min($this->devotion->duration * 0.1 / 100, 1);

        return (float)$this->deity->getPerkValue($perkKey) * $multiplier * $devotionDurationMultiplier;
    }

    /**
     * @param string $key
     * @return float
     */
    public function getDeityPerkMultiplier(string $key): float
    {
        return ($this->getDeityPerkValue($key) / 100);
    }

    /**
     * Returns the unit production bonus for a specific resource type (across all eligible units) for this dominion.
     *
     * @param string $resourceType
     * @return float
     */
    public function getUnitPerkProductionBonusFromDeity(string $resourceType): float
    {
        $bonus = 0;

        foreach ($this->race->units as $unit)
        {
            $titlePerkData = $this->race->getUnitPerkValueForUnitSlot($unit->slot, 'production_from_deity', null);

            if($titlePerkData)
            {
                $deityKey = $titlePerkData[0];
                $perkResource = $titlePerkData[1];
                $perkAmount = $titlePerkData[2];

                if($resourceType === $perkResource and $this->deity->key === $deityKey)
                {
                    $bonus += ($this->{'military_unit' . $unit->slot} * (float)$perkAmount);
                }
            }
        }

        return $bonus;
    }

    # Land improvements 2.0

    public function getLandImprovementPerkValue(string $perkKey): float
    {
        $landHelper = app(LandHelper::class);

        $perk = 0;

        foreach($landHelper->getLandTypes($this->race) as $landType)
        {
            if(isset($this->race->land_improvements[$landType][$perkKey]))
            {
                #echo "<pre>$perkKey from $landType: " . $this->race->land_improvements[$landType][$perkKey] . " </pre>";
                $perk += $this->race->land_improvements[$landType][$perkKey] * $this->{'land_' . $landType};
            }
        }
        return $perk;
    }

    public function getLandImprovementPerkMultiplier(string $perkKey): float
    {
        $landCalculator = app(LandCalculator::class);
        $landHelper = app(LandHelper::class);

        $perk = 0;

        foreach($landHelper->getLandTypes($this->race) as $landType)
        {
            if(isset($this->race->land_improvements[$landType][$perkKey]))
            {
                $perk += $this->race->land_improvements[$landType][$perkKey] * ($this->{'land_' . $landType} / $this->land);
            }
        }
        return $perk;
    }

    # Race Terrain Perks 2.0 - finish this with AI
    public function getRaceTerrainPerks()
    {
        return $this->race->raceTerrains->flatMap(
            function ($raceTerrain)
            {
                return $raceTerrain->perks;
            }
        );
    }

    public function getTerrainPerkValue(string $perkKey): float
    {
        dd($this->getRaceTerrainPerks());
    }

    public function getTerrainPerkMultiplier(string $perkKey): float
    {
        $landCalculator = app(LandCalculator::class);
        $landHelper = app(LandHelper::class);

        $perk = 0;

        foreach(RaceTerrain::where('race_id', $this->race->id)->get() as $raceTerrain)
        {
            dd($raceTerrain->perks());
        }

        return $perk;
    }


    # DECREES

    protected function getDecreeStatePerks()
    {
        return $this->decreeStates->flatMap(
            function ($decreeState) {
                return $decreeState->perks;
            }
        );
    }

    public function getDecreePerkValue(string $key)
    {
        $perks = $this->getDecreeStatePerks()->groupBy('key');

        $buildingGenerationPerks = [
                'generate_building_forest',
                'generate_building_hill',
                'generate_building_mountain',
                'generate_building_plain',
                'generate_building_swamp',
                'generate_building_water',
            ];

        $unitGenerationPerks = [
                'undead_unit1_production_raw',
                'undead_unit2_production_raw',
                'undead_unit3_production_raw',
                'undead_unit4_production_raw',
                'undead_unit1_production_raw_from_crypt',
                'undead_unit2_production_raw_from_crypt',
                'undead_unit3_production_raw_from_crypt',
                'undead_unit4_production_raw_from_crypt',
            ];
   
        if (isset($perks[$key]))
        {
            if(in_array($key, $buildingGenerationPerks))
            {
                return $perks[$key]->pluck('pivot.value')->first();
            }
            elseif(in_array($key, $unitGenerationPerks))
            {
                return $perks[$key]->pluck('pivot.value')->first();
            }
            else
            {
                return $perks[$key]->sum('pivot.value');
            }
        }

        return 0;
    }

    public function getDecreePerkMultiplier(string $key): float
    {
        return ($this->getDecreePerkValue($key) / 100);
    }


    protected function getDecreePerks()
    {
        return $this->decrees->flatMap(
            function ($decree) {
                return $decree->perks;
            }
        );
    }

    # TECHS

    # IMPROVEMENTS

    protected function getAdvancementPerks()
    {
        return $this->advancements->flatMap(
            function ($advancement)
            {
                return $advancement->perks;
            }
        );
    }

   /**
    * @param string $key
    * @return float
    */
    public function getAdvancementPerkValue(string $perkKey): float
    {
        $perk = 0;

        foreach ($this->advancements as $advancement)
        {
            if($perkValueString = $advancement->getPerkValue($perkKey))
            {
                $level = $this->advancements()->where('advancement_id', $advancement->id)->first()->pivot->level;
                $levelMultiplier = $this->getAdvancementLevelMultiplier($level);

                $perk += $perkValueString * $levelMultiplier;
             }
        }

        return $perk;
    }

    /**
     * @param string $key
     * @return float
     */
    public function getAdvancementPerkMultiplier(string $key): float
    {
        return ($this->getAdvancementPerkValue($key) / 100);
    }

    public function extractAdvancementPerkValues(string $perkValue)
    {
        return $perkValue;
    }

    public function getAdvancementLevelMultiplier(int $level): float
    {
        
        if($level <= 6)
        {
            return $level;
        }
        elseif($level <= 10)
        {
            return ($level - 6)/2 + 6;
        }
        else
        {
            return ($level - 10)/3 + 10;
        }
    }

    public function isWatchingDominion(Dominion $dominion): bool
    {
        return $this->watchedDominions()->get()->contains($dominion);
    }

    public function isWatchingAnyDominion(): bool
    {
        return $this->watchedDominions()->get()->count() > 0;
    }
}
