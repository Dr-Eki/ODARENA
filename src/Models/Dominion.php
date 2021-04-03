<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\SelectorService;

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
 * @property int $resource_tech
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
        'prestige' => 'integer',
        'peasants' => 'integer',
        'peasants_last_hour' => 'integer',
        'draft_rate' => 'integer',
        'morale' => 'integer',
        'spy_strength' => 'float',
        'wizard_strength' => 'float',

        'resource_gold' => 'integer',
        'resource_food' => 'integer',
        'resource_lumber' => 'integer',
        'resource_mana' => 'integer',
        'resource_ore' => 'integer',
        'resource_gems' => 'integer',
        'resource_tech' => 'integer',
        'resource_boats' => 'float',
        'resource_champion' => 'integer',
        'resource_soul' => 'integer',
        'resource_blood' => 'integer',
        'resource_wild_yeti' => 'integer',

        'improvement_markets' => 'integer',
        'improvement_keep' => 'integer',
        'improvement_towers' => 'integer',
        'improvement_spires' => 'integer',
        'improvement_forges' => 'integer',
        'improvement_walls' => 'integer',
        'improvement_harbor' => 'integer',
        'improvement_armory' => 'integer',
        'improvement_infirmary' => 'integer',
        'improvement_workshops' => 'integer',
        'improvement_observatory' => 'integer',
        'improvement_cartography' => 'integer',
        'improvement_hideouts' => 'integer',
        'improvement_forestry' => 'integer',
        'improvement_refinery' => 'integer',
        'improvement_granaries' => 'integer',

        'military_draftees' => 'integer',
        'military_unit1' => 'integer',
        'military_unit2' => 'integer',
        'military_unit3' => 'integer',
        'military_unit4' => 'integer',
        'military_spies' => 'integer',
        'military_wizards' => 'integer',
        'military_archmages' => 'integer',

        'land_plain' => 'integer',
        'land_mountain' => 'integer',
        'land_swamp' => 'integer',
        'land_cavern' => 'integer',
        'land_forest' => 'integer',
        'land_hill' => 'integer',
        'land_water' => 'integer',
        /*
        'building_home' => 'integer',
        'building_alchemy' => 'integer',
        'building_farm' => 'integer',
        'building_smithy' => 'integer',
        'building_masonry' => 'integer',
        'building_ore_mine' => 'integer',
        'building_gryphon_nest' => 'integer',
        'building_tower' => 'integer',
        'building_wizard_guild' => 'integer',
        'building_temple' => 'integer',
        'building_gem_mine' => 'integer',
        'building_school' => 'integer',
        'building_lumberyard' => 'integer',
        'building_forest_haven' => 'integer',
        'building_factory' => 'integer',
        'building_guard_tower' => 'integer',
        'building_shrine' => 'integer',
        'building_barracks' => 'integer',
        'building_dock' => 'integer',
        */

        'daily_gold' => 'boolean',
        'daily_land' => 'boolean',

        'royal_guard_active_at' => 'datetime',
        'eltie_guard_active_at' => 'datetime',

        'is_locked' => 'integer',

        'most_recent_improvement_resource' => 'string',
        'most_recent_exchange_from' => 'string',
        'most_recent_exchange_to' => 'string',

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

    // Eloquent Query Scopes

    public function scopeActive(Builder $query)
    {
        return $query->whereHas('round', function (Builder $query) {
            $query->where('start_date', '<=', now())
                ->where('end_date', '>', now());
        });
    }

    // Methods

    // todo: move to eloquent events, see $dispatchesEvents
    public function save(array $options = [])
    {
        $recordChanges = isset($options['event']);

        // Verify tick hasn't happened during this request
        if ($this->exists && $this->last_tick_at != $this->fresh()->last_tick_at) {
            throw new GameException('The tax man is collecting taxes. Your request cannot be fulfilled right now. Try again in a few seconds.');
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

    public function isAbandoned(): bool
    {
        if($this->user_id === null)
        {
            return true;
        }

        return false;
    }

    public function getLockedReason(int $reason): string
    {
        switch ($reason)
        {
            case 2:
                return "Player's request.";

            case 3:
                return "Rule violation.";

            default:
                return 'None.';
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
    public function getTechPerkValue(string $key): float
    {
        $perks = $this->getTechPerks()->groupBy('key');
        if (isset($perks[$key])) {
            $max = (float)$perks[$key]->max('pivot.value');
            if ($max < 0) {
                return (float)$perks[$key]->min('pivot.value');
            }
            return $max;
        }
        return 0;
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

    /**
     * @param string $key
     * @return float
     */
     public function getBuildingProduction(string $resourceType): float
     {
         $production = 0;

         foreach ($this->buildings as $building)
         {
             $perkValue = $building->getPerkValue($resourceType);

             if ($perkValue !== 0)
             {
                 $production = $building->pivot->owned * $perkValue;
             }
         }

         return $production;
     }

     /**
      * @param string $key
      * @return float
      */
      public function getBuildingPerkValue(string $perkKey): float
      {
          $landSize = $this->land_plain + $this->land_mountain + $this->land_swamp + $this->land_forest + $this->land_hill + $this->land_water;
          $perk = 0;

          foreach ($this->buildings as $building)
          {
              $perkValueString = $building->getPerkValue($perkKey);

              if(is_numeric($perkValueString))
              {
                  $perkValueString = (float)$perkValueString;
              }

              # Default value for housing.
              if($perkKey == 'housing' and !is_numeric($perkValueString))
              {
                  $perkValueString = 15;
              }

              # Default value for jobs.
              if($perkKey == 'jobs' and !is_numeric($perkValueString))
              {
                  $perkValueString = 20;
              }

              if($perkValueString)
              {
                  # Basic production
                  if(
                          $perkKey == 'gold_production'
                          or $perkKey == 'food_production'
                          or $perkKey == 'ore_production'
                          or $perkKey == 'lumber_production'
                          or $perkKey == 'mana_production'
                          or $perkKey == 'boat_production'
                          or $perkKey == 'tech_production'
                      )
                  {
                      $perk += (float)$perkValueString;
                  }
                  # Mods with ratio, multiplier, and max
                  elseif(
                          # OP/DP mods
                          $perkKey == 'defensive_power'
                          or $perkKey == 'offensive_power'
                          or $perkKey == 'defensive_modifier_reduction'
                          or $perkKey == 'defensive_casualties'
                          or $perkKey == 'offensive_casualties'

                          or $perkKey == 'morale_gains'
                          or $perkKey == 'base_morale'

                          # Production mods
                          or $perkKey == 'gold_production_modifier'
                          or $perkKey == 'food_production_modifier'
                          or $perkKey == 'lumber_production_modifier'
                          or $perkKey == 'ore_production_modifier'
                          or $perkKey == 'gem_production_modifier'
                          or $perkKey == 'mana_production_modifier'
                          or $perkKey == 'tech_production_modifier'
                          or $perkKey == 'exchange_rate'

                          # Unit costs
                          or $perkKey == 'unit_gold_costs'
                          or $perkKey == 'unit_ore_costs'
                          or $perkKey == 'unit_lumber_costs'
                          or $perkKey == 'unit_mana_costs'
                          or $perkKey == 'unit_food_costs'

                          # Unit training
                          or $perkKey == 'extra_units_trained'

                          # Spy/wizard
                          or $perkKey == 'spy_losses'
                          or $perkKey == 'wizard_losses'
                          or $perkKey == 'wizard_strength_recovery'
                          or $perkKey == 'wizard_cost'
                          or $perkKey == 'spell_cost'

                          # Construction/Rezoning
                          or $perkKey == 'construction_cost'
                          or $perkKey == 'rezone_cost'

                          # Espionage
                          or $perkKey == 'gold_theft_reduction'
                      )
                  {
                      $perkValues = $this->extractBuildingPerkValues($perkValueString);
                      $ratio = (float)$perkValues[0];
                      $multiplier = (float)$perkValues[1];
                      $max = (float)$perkValues[2] / 100;
                      $owned = $building->pivot->owned;

                      $effect = min($owned / $landSize * $ratio * $multiplier, $max);
                  }
                  # Mods with ratio, multiplier, and no max
                  elseif(
                          # OP/DP mods
                          $perkKey == 'improvements'
                          or $perkKey == 'lightning_bolt_damage'
                          or $perkKey == 'fireball_damage'
                          or $perkKey == 'population_growth'
                      )
                  {
                      $perkValues = $this->extractBuildingPerkValues($perkValueString);
                      $ratio = (float)$perkValues[0];
                      $multiplier = (float)$perkValues[1];
                      $owned = $building->pivot->owned;

                      $effect = $owned / $landSize * $ratio * $multiplier;
                  }
              }

              if (!isset($effect))
              {
                  $perk += $building->pivot->owned * $perkValueString;
              }
              else
              {
                  $perk = $effect * 100;
              }
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

        return $perkValues;
    }

    # SPELLS

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
     /*
    public function getSpellPerkValue(string $key): float
    {
        $value = 0;
        $perks = $this->getSpellPerks()->groupBy('key');

        if (isset($perks[$key]))
        {
            $max = (float)$perks[$key]->max('pivot.value');
            if ($max < 0)
            {
                return (float)$perks[$key]->min('pivot.value');
            }
            return $max;
        }
        return 0;
    }*/

    /**
     * @param string $key
     * @return float
     */

     public function getSpellPerkValue(string $perkKey): float
     {
         $landSize = $this->land_plain + $this->land_mountain + $this->land_swamp + $this->land_forest + $this->land_hill + $this->land_water;
         $perk = 0;

         foreach ($this->spells as $spell)
         {
             $perkValueString = $spell->getPerkValue($perkKey);

             if($perkValueString and (is_numeric($perkValueString) and !is_array($perkValueString)))
             {
                 # Single value numeric perks
                 if(
                         $perkKey == 'gold_production'
                         or $perkKey == 'food_production'
                         or $perkKey == 'ore_production'
                         or $perkKey == 'lumber_production'
                         or $perkKey == 'mana_production'
                         or $perkKey == 'tech_production'

                         or $perkKey == 'no_gold_production'
                         or $perkKey == 'no_food_production'
                         or $perkKey == 'no_ore_production'
                         or $perkKey == 'no_lumber_production'
                         or $perkKey == 'no_mana_production'
                         or $perkKey == 'no_tech_production'

                         or $perkKey == 'unit_gold_cost'
                         or $perkKey == 'unit_ore_cost'
                         or $perkKey == 'unit_lumber_cost'

                         or $perkKey == 'food_production_raw'

                         or $perkKey == 'population_growth'
                         or $perkKey == 'improvements'

                         or $perkKey == 'cannot_build'
                         or $perkKey == 'cannot_invade'
                         or $perkKey == 'cannot_explore'
                         or $perkKey == 'no_drafting'

                         or $perkKey == 'gold_theft'
                         or $perkKey == 'gems_theft'
                         or $perkKey == 'ore_theft'
                         or $perkKey == 'mana_theft'
                         or $perkKey == 'all_theft'

                         or $perkKey == 'reveal_ops'
                         or $perkKey == 'chance_to_reflect_spells'
                         or $perkKey == 'damage_from_spells'
                         or $perkKey == 'damage_from_fireballs'
                         or $perkKey == 'damage_from_lightning_bolts'


                         or $perkKey == 'offensive_power'
                         or $perkKey == 'defensive_power'
                         or $perkKey == 'drafting'
                         or $perkKey == 'can_kill_immortal'
                         or $perkKey == 'defensive_power_from_peasants'
                         or $perkKey == 'faster_return'
                         or $perkKey == 'training_time'

                         or $perkKey == 'casualties'
                         or $perkKey == 'offensive_casualties'
                         or $perkKey == 'defensive_casualties'
                         or $perkKey == 'increases_casualties_on_defense'
                         or $perkKey == 'increases_casualties_on_offense'
                         or $perkKey == 'increases_enemy_draftee_casualties'

                         or $perkKey == 'no_conversions'
                         or $perkKey == 'convert_enemy_casualties_to_food'
                         or $perkKey == 'convert_peasants_to_champions'

                         or $perkKey == 'increases_casualties_on_offense_from_wizard_ratio'
                         or $perkKey == 'offensive_power_on_retaliation'
                         or $perkKey == 'immune_to_temples'

                         or $perkKey == 'spy_strength'
                         or $perkKey == 'wizard_strength'
                         or $perkKey == 'immortal_spies'
                         or $perkKey == 'immortal_wizards'

                         or $perkKey == 'land_discovered'
                         or $perkKey == 'buildings_destroyed'
                         or $perkKey == 'barren_land_rezoned'

                         or $perkKey == 'opens_portal'
                         or $perkKey == 'stop_land_generation'
                         or $perkKey == 'defensive_power_vs_insect_swarm'

                         or $perkKey == 'stasis'

                         # Cult
                         or $perkKey == 'mind_control'
                         or $perkKey == 'enthralling'
                         or $perkKey == 'cogency'
                         or $perkKey == 'persuasion'
                     )
                 {
                     $perk += (float)$perkValueString;
                 }
                 elseif($perkValueString and (!is_numeric($perkValueString) and !is_array($perkValueString)))
                 {
                    $perk = (string)$perkValueString;
                 }
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
}
