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
 * @property bool $daily_platinum
 * @property bool $daily_land
 * @property int $resource_platinum
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

        'resource_platinum' => 'integer',
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

        'daily_platinum' => 'boolean',
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

    // todo: info op target/source?

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
        return $this->hasManyThrough(
            Building::class,
            DominionBuilding::class,
            'dominion_id',
            'id',
            'id',
            'building_id'
        );
    }

    public function spells()
    {
        return $this->hasManyThrough(
            Spell::class,
            NULL,
            'dominion_id',
            'id',
            'id',
            'building_id'
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

        if ($saved && $recordChanges) {
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
        // todo: test this
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
        return $this->is_locked || (now() >= $this->round->end_date);
    }

    public function isAbandoned(): bool
    {
        if($this->user_id === null)
        {
            return true;
        }
        else
        {
            return false;
        }
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

    protected function getTechPerks() {
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

    # SPELLS

    protected function getSpellPerks() {
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
    public function getSpellPerkValue(string $key): float
    {
        $perks = $this->getSpellPerks()->groupBy('key');
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
    public function getSpellPerkMultiplier(string $key): float
    {
        return ($this->getSpellPerkValue($key) / 100);
    }
}
