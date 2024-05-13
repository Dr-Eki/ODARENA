<?php

namespace OpenDominion\Models;

use DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Services\Hold\HistoryService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Services\Dominion\QueueService;

class Hold extends AbstractModel
{
    use Notifiable;

    protected $casts = [
        'key' => 'string',
        'name' => 'string',
        'ruler_name' => 'string',
        'description' => 'string',
        'peasants' => 'integer',
        'land' => 'integer',
        'morale' => 'integer',
        'peasants_last_hour' => 'integer',
        'status' => 'integer',
        'desired_resources' => 'array',
        'sold_resources' => 'array',
        'tick_discovered' => 'integer',
        'ticks' => 'integer',
        'status' => 'integer',
    ];

    protected $fillable = [
        'round_id',
        'title_id',
        'race_id',
        'name',
        'key',
        'ruler_name',
        'description',
        'peasants',
        'land',
        'morale',
        'peasants_last_hour',
        'status',
        'desired_resources',
        'sold_resources',
        'tick_discovered',
        'ticks',
        'status',
    ];

    // Relations
    public function tradeLedger()
    {
        return $this->hasMany(TradeLedger::class);
    }

    public function tradeRoutes()
    {
        return $this->hasMany(TradeRoute::class);
    }

    public function sentiments()
    {
        return $this->hasMany(HoldSentiment::class, 'hold_id');
    }

    public function sentimentEvents()
    {
        return $this->hasMany(HoldSentimentEvent::class, 'hold_id');
    }

    public function prices()
    {
        return $this->hasMany(HoldPrice::class);
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
        return $this->hasMany(Hold\History::class);
    }

    public function race()
    {
        return $this->belongsTo(Race::class)->withDefault([
            'name' => 'Various',
            'key' => 'various',
        ]);
    }

    public function resources()
    {
        return $this->belongsToMany(
            Resource::class,
            'hold_resources',
            'hold_id',
            'resource_id'
        )
            ->withPivot('amount');
    }

    public function stats()
    {
        return $this->hasMany(Hold\Stat::class);
    }

    public function title()
    {
        return $this->belongsTo(Title::class);
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

    public function units()
    {
        return $this->hasMany(HoldUnit::class);
    }

    public function buildings()
    {
        return $this->hasMany(HoldBuilding::class);
    }

    # Get units by state
    public function getUnitsByState(int $state)
    {
        return $this->units->where('state', $state);
    }

    public function resourceKeys(): array
    {
        $holdResourceKeys = $this->resources->pluck('key')->toArray();
        $holdDesiredResourceKeys = $this->desired_resources;
        $holdSoldResourceKeys = $this->sold_resources;

        $merged = array_merge($holdResourceKeys, $holdDesiredResourceKeys, $holdSoldResourceKeys);
      
        return array_unique($merged);
    }
   

    public function buyPrice(string $resourceKey): float
    {
        $resource = Resource::where('key', $resourceKey)->firstOrFail();
    
        $sql = "SELECT
            `price`
        FROM
            `hold_prices`
        WHERE
            `hold_id` = :hold_id AND
            `resource_id` = :resource_id AND
            `action` = :action AND
            `tick` >= :tick
        ORDER BY
            tick DESC
        LIMIT 1";
    
        $bindings = [
            'hold_id' => $this->id,
            'resource_id' => $resource->id,
            'action' => 'buy',
            'tick' => $this->round->ticks - 1
        ];
    
        $result = DB::select($sql, $bindings);
    
        return (float)$result[0]->price ?? 0;
    }

    public function sellPrice(string $resourceKey): float
    {
        $resource = Resource::where('key', $resourceKey)->firstOrFail();
    
        $sql = "SELECT
            `price`
        FROM
            `hold_prices`
        WHERE
            `hold_id` = :hold_id AND
            `resource_id` = :resource_id AND
            `action` = :action AND
            `tick` >= :tick
        ORDER BY
            tick DESC
        LIMIT 1";
    
        $bindings = [
            'hold_id' => $this->id,
            'resource_id' => $resource->id,
            'action' => 'sell',
            'tick' => $this->round->ticks - 1
        ];
    
        $result = DB::select($sql, $bindings);
    
        return (float)$result[0]->price ?? 0;
    }

#
#    public function sellPrice(string $resourceKey): float
#    {
#        $resource = Resource::where('key', $resourceKey)->firstOrFail();
#        return $this->prices->where('resource_id', $resource->id)->where('action','sell')->where('tick','>=',$this->round->ticks)->sortByDesc('id')->first()->price ?? 0;
#    }


    # This code enables the following syntax:
    # $dominion->{'terrain_' . $terrainKey} and similar

    public function __get($key)
    {
        if (preg_match('/^terrain_(\w+)$/', $key, $matches)) {
            return $this->getTerrainAmount($matches[1]);
        }
    
        if (preg_match('/^resource_(\w+)$/', $key, $matches)) {
            return $this->getResourceAmount($matches[1]);
        }
    
        if (preg_match('/^building_(\w+)$/', $key, $matches)) {
            return $this->getBuildingAmount($matches[1]);
        }
    
        return parent::__get($key);
    }
    
    protected function getTerrainAmount($terrainKey)
    {
        $terrainKey = strtolower($terrainKey);
    
        $dominionTerrain = $this->terrains()
            ->where('terrains.key', $terrainKey)
            ->first();
    
        if ($dominionTerrain) {
            return $dominionTerrain->pivot->amount;
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

    public function getBuildingAmount(string $buildingKey)
    {
        $buildingKey = strtolower($buildingKey);

        $building = Building::fromKey($buildingKey);

        $holdBuilding = $this->buildings->where('building_id', $building->id)->first();
    
        if ($holdBuilding) {
            return $holdBuilding->amount;
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
        return $this->hasMany(Hold\Queue::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tick()
    {
        return $this->hasOne(Hold\Tick::class);
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
    }

    public function protectorshipOffered()
    {
        return $this->hasMany(ProtectorshipOffer::class, 'protector_id', 'id');
    }

    // END PROTECTORSHIP STUFF

    public function activeSpells()
    {
        return $this->hasMany(DominionSpell::class)
            ->where('duration', '>', 0);
    }

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
        #$tickService = app(\OpenDominion\Services\Dominion\TickService::class);
        #$tickService->precalculateTick($this);

        return $saved;
    }

    public function getDirty()
    {
        $dirty = parent::getDirty();

        $query = $this->newModelQuery();

        $holdHistoryService = app(HistoryService::class);
        $deltaAttributes = $holdHistoryService->getDeltaAttributes($this);

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
            return "abandoned-{$this->id}@odarena.com";
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

    public function getMoraleMultiplier(): float
    {
        return 0.90 + $this->morale / 1000;
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
        return true;
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
        #return Cache::remember("dominion.{$this->id}.techPerkValue.{$perkKey}", 5, function () use ($perkKey)
        #{
            $perks = $this->getTechPerks()->groupBy('key');
            if (isset($perks[$perkKey])) {
                $max = (float)$perks[$perkKey]->max('pivot.value');
                if ($max < 0) {
                    return (float)$perks[$perkKey]->min('pivot.value');
                }
                return $max;
            }
            return 0;
        #});
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
        $perk = 0;

        if(in_array($perkKey, ['housing','jobs']))
        {
            $defaultValue = ($perkKey == 'housing') ? 15 : 20;

            # Grab buildings with the perk
            $perkedBuildings = $this->buildings->filter(function ($building) use ($perkKey) {
                return $building->perks->contains('key', $perkKey);
            });

            # Grab buildings without the perk
            $unperkedBuildings = $this->buildings->filter(function ($building) use ($perkKey) {
                return !$building->perks->contains('key', $perkKey);
            });

            # Add the default value for each building without the perk
            $perk += $unperkedBuildings->sum('amount') * $defaultValue;

            # Add the perk value for each building with the perk
            foreach($perkedBuildings as $building)
            {
                $perk += $building->pivot->owned * $building->getPerkValue($perkKey);
            }
            
            return $perk;
        }

        // Filter out buildings that require a deity unless the dominion has a deity, and buildings that are disabled
        $holdBuildings = $this->buildings->filter(function ($holdBuilding) use ($perkKey) {
            return (!isset($holdBuilding->building->deity) || ($this->hasDeity() && $this->deity->id === $holdBuilding->building->deity->id)) 
                #&& $building->enabled 
                && $holdBuilding->building->perks->contains('key', $perkKey);
        });
        
        foreach ($holdBuildings as $holdBuilding)
        {
            $buildingOwned = $holdBuilding->amount;

            if($maxBuildingRatio = $holdBuilding->building->getPerkValue('max_effective_building_ratio'))
            {
                $buildingOwned = min($buildingOwned, $this->land * ($maxBuildingRatio / 100));
            }

            # Check if building has perk pairing_limit
            if ($pairingLimit = $holdBuilding->building->getPerkValue('pairing_limit')) {
                $pairingLimit = explode(',', $pairingLimit);
        
                $chunkSize = (int)$pairingLimit[0];
                $buildingKey = (string)$pairingLimit[1];

                // Get amount owned of $pairedBuilding
                $pairedBuildingOwned = $this->{'building_' . $buildingKey};# buildings->firstWhere('id', $pairedBuilding->id)->pivot->owned;
        
                // $buildingOwned is the minimum of the two
                $buildingOwned = min($buildingOwned, floor($pairedBuildingOwned / $chunkSize));
        
                $buildingOwned = intval($buildingOwned);
            }

            # Check if building has perk pairing_limit
            # SAMPLE: multiple_pairing_limit: 50,wall;bastion # amount, buildings
            if($multiplePairingLimit = $holdBuilding->building->getPerkValue('multiple_pairing_limit'))
            {
                $multiplePairingLimit = explode(',', $multiplePairingLimit);
                $pairedBuildingKeys = explode(';', $multiplePairingLimit[1]);
            
                $pairedBuildingsOwned = 0;
                $chunkSize = (int)$multiplePairingLimit[0];
            
                // Convert buildings to a key-value pair array
                $buildingsOwned = $this->buildings->pluck('pivot.owned', 'key');
            
                foreach($pairedBuildingKeys as $buildingKey)
                {
                    $pairedBuildingsOwned += $buildingsOwned[$buildingKey] ?? 0;
                }
            
                $buildingOwned = min($buildingOwned, floor($pairedBuildingsOwned / $chunkSize));
            
                $buildingOwned = intval($buildingOwned);
            }

            $perkValueString = $holdBuilding->building->getPerkValue($perkKey);

            $perkValueString = is_numeric($perkValueString) ? (float)$perkValueString : $perkValueString;
            
            #elseif(!in_array($perkKey,['jobs','housing']) and $perkValueString)
            #{
                # Basic production and other single-value perks
                $singleValuePerks = config('buildings.single_value_perks');
                $ratioMultiplierMaxPerks = config('buildings.ratio_multiplier_max_perks');
                $ratioMultiplierUncappedPerks = config('buildings.ratio_multiplier_uncapped_perks');

                if(in_array($perkKey, $singleValuePerks))
                {
                    $perk += $perkValueString * $buildingOwned;
                }

                # Mods with ratio, multiplier, and max
                elseif(in_array($perkKey, $ratioMultiplierMaxPerks))
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);
                    $ratio = (float)$perkValues[0];
                    $multiplier = (float)$perkValues[1];
                    $max = (float)$perkValues[2] / 100;
                    $owned = $buildingOwned;

                    if($multiplier < 0)
                    {
                        $perk += max($owned / $this->land * $ratio * $multiplier, $max*-1) * 100;
                    }
                    else
                    {
                        $perk += min($owned / $this->land * $ratio * $multiplier, $max) * 100;
                    }

                }
                # Mods with ratio, multiplier, and no max
                elseif(in_array($perkKey, $ratioMultiplierUncappedPerks))
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);
                    $ratio = (float)$perkValues[0];
                    $multiplier = (float)$perkValues[1];
                    $owned = $buildingOwned;

                    $perk += ($owned / $this->land * $ratio * $multiplier) * 100;
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
                    $buildingOwned = $buildingOwned;

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
                    $buildingOwned = $buildingOwned;

                    $perk += $buildingOwned * ($baseValue + ($ticklyIncrease * $ticks));

                }
                # Resource conversion
                elseif($perkKey == 'resource_conversion')
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);

                    $sourceAmount = (float)$perkValues[0];
                    $sourceResourceKey = (string)$perkValues[1];
                    $targetAmount = (float)$perkValues[2];
                    $targetResourceKey = (string)$perkValues[3];
                    $buildingOwned = $buildingOwned;

                    $maxAmountConverted = min($this->{'resource_' . $sourceResourceKey}, $buildingOwned * $sourceAmount);
                    $amountCreated = $maxAmountConverted / ($sourceAmount / $targetAmount);

                    return ['from' => [$sourceResourceKey => $maxAmountConverted], 'to' => [$targetResourceKey => $amountCreated]];

                }

                # Peasants conversion (single resource)
                elseif($perkKey == 'peasants_conversion')
                {

                    $availablePeasants = max($this->peasants-1000, 0);
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);

                    $sourceAmount = (float)$perkValues[0];
                    $sourceResourceAmount = $availablePeasants;
                    $targetAmount = (float)$perkValues[1];
                    $targetResourceKey = (string)$perkValues[2];
                    $buildingOwned = $buildingOwned;

                    $maxAmountConverted = min($sourceResourceAmount, $buildingOwned * $sourceAmount);
                    $amountCreated = $maxAmountConverted / ($sourceAmount / $targetAmount);

                    return ['from' => ['peasants' => $maxAmountConverted], 'to' => [$targetResourceKey => $amountCreated]];
                }

                # Peasants conversion (multiple resources)
                elseif($perkKey == 'peasants_conversions')
                {

                    $availablePeasants = max($this->peasants-1000, 0); #min($this->peasants, max(1000, $this->peasants-1000));
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);

                    $sourceAmount = (float)$perkValues[0];
                    $sourceResourceAmount = $availablePeasants;
                    $buildingOwned = $buildingOwned;
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
                elseif(in_array($perkKey, ['ore_production_raw_from_prisoner', 'gold_production_raw_from_prisoner', 'gems_production_raw_from_prisoner']))
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);

                    $prisoners = $this->{'resource_prisoner'};
                    $productionPerPrisoner = (float)$perkValues[0];
                    $maxResourcePerBuilding = (float)$perkValues[1];
                    $buildingOwned = $buildingOwned;

                    $maxPrisonersWorking = $buildingOwned * $maxResourcePerBuilding;

                    $prisonersWorking = min($maxPrisonersWorking, $prisoners);

                    $perk += floor($prisonersWorking * $productionPerPrisoner);
                }
                elseif($perkKey == 'thunderstone_production_raw_random')
                {
                    $randomlyGenerated = 0;
                    $randomChance = (float)$perkValueString / 100;
                    $buildingOwned = $buildingOwned;

                    for ($trials = 1; $trials <= $buildingOwned; $trials++)
                    {
                        if(random_chance($randomChance))
                        {
                            $randomlyGenerated += 1;
                        }
                    }

                    $perk += $randomlyGenerated;
                }
                elseif(in_array($perkKey, [
                        'dimensionalists_unit1_production_raw_capped',
                        'dimensionalists_unit2_production_raw_capped',
                        'dimensionalists_unit3_production_raw_capped',
                        'dimensionalists_unit4_production_raw_capped',
                        'snow_elf_unit4_production_raw_capped',
                        'aurei_unit2_production_raw_capped'
                    ]))
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);

                    $unitPerBuilding = (float)$perkValues[0];
                    $maxBuildingRatio = (float)$perkValues[1] / 100;

                    $availableBuildings = (int)min($buildingOwned, floor($this->land * $maxBuildingRatio));

                    $unitsGenerated = $availableBuildings * $unitPerBuilding;
                    $unitsGeneratedInt = (int)$unitsGenerated;
                    $unitsGeneratedFloat = $unitsGenerated - $unitsGeneratedInt;
                    $unitsGeneratedInt = $unitsGeneratedInt + (random_chance($unitsGeneratedFloat) ? 1 : 0);

                    $perk += (int)$unitsGeneratedInt;
                }

                # Attrition protection
                elseif($perkKey == 'attrition_protection')
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);
                    $amount = (float)$perkValues[0];
                    $slot = (int)$perkValues[1];
                    $raceName = (string)$perkValues[2];

                    if($this->race->name == $raceName)
                    {
                        return [$buildingOwned * $amount, $slot];
                    }
                }

                # Building self-destruction
                elseif($perkKey == 'destroys_itself_and_land')
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);

                    $amountToDestroyPerBuilding = (float)$perkValues[0];
                    $landTypeToDestroy = (string)$perkValues[1];
                    $buildingOwned = $buildingOwned;

                    $amountToDestroy = $buildingOwned * $amountToDestroyPerBuilding;
                    #$amountToDestroy = intval($amountToDestroy) + (rand()/getrandmax() < fmod($amountToDestroy, 1) ? 1 : 0);
                    $amountToDestroy = (int)floor($amountToDestroy);

                    $result = ['building_key' => $holdBuilding->building->key, 'amount' => $amountToDestroy, 'land_type' => $landTypeToDestroy];

                    return $result;
                }

                # Building self-destruction
                elseif($perkKey == 'destroys_itself')
                {
                    $perkValues = (float)$perkValueString;#$this->extractBuildingPerkValues($perkValueString);

                    $amountToDestroyPerBuilding = $perkValues;
                    $buildingOwned = $buildingOwned;

                    $amountToDestroy = $buildingOwned * $amountToDestroyPerBuilding;
                    $amountToDestroy = (int)floor($amountToDestroy);

                    $result = ['building_key' => $holdBuilding->building->key, 'amount' => $amountToDestroy];

                    return $result;
                }

                # Time-based production (which is always on during protection but at half speed)
                elseif(in_array($perkKey, ['gloom_production_raw_from_time','light_production_raw_from_time','mana_production_raw_from_time']))
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);
                    $amountProduced = (float)$perkValues[2];
                    $hourFrom = $perkValues[0];
                    $hourTo = $perkValues[1];
    
                    if($this->isUnderProtection())
                    {
                        $perk += ($amountProduced * $buildingOwned) / 2;
                    }
                    elseif (
                        (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                        (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                    )
                    {
                        $perk += $amountProduced * $buildingOwned;
                    }
                }

                # Mana production based on WPA
                elseif(in_array($perkKey, ['mana_production_raw_from_wizard_ratio']))
                {
                    #$magicCalculator = app(MagicCalculator::class);
                    $wizardRatio = 0;#$magicCalculator->getWizardRatio($this, 'defense');
                    $perk += $wizardRatio * $perkValueString * $buildingOwned;
                }

                elseif($perkKey == ($this->race->key . '_unit_housing'))
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);
                    $buildingsOwned = $buildingOwned;

                    $result = [];

                    if(!is_array($perk))
                    {
                        $perk = [];
                    }

                    if(!is_array($perkValues[0]))
                    {

                        #dd($building->name, $perkValues);
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

                    $perk[] = $result;
                }

                elseif($perkKey == ($this->race->key . '_units_production'))
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);

                    #if(!is_array($perkValues[0]))
                    #{
                    #    $perkValues[0] = [$perkValues[0]];
                    #}

                    $data[$holdBuilding->building->key] = [
                        'buildings_amount' => $buildingOwned,
                        'generated_unit_slots' => (array)$perkValues[0],
                        'amount_per_building' => (float)$perkValues[1]
                    ];

                    if(!is_array($perk))
                    {
                        $perk = [];
                    }

                    $perk[] = $data;
                    unset($data);
                }

                elseif($perkKey == 'quadratic_improvements_mod')
                {
                    $perkValues = $this->extractBuildingPerkValues($perkValueString);
                
                    $perkRatio = (float)$perkValues[0];
                    $perkMultiplier = (float)$perkValues[1];
                    $perkMax = isset($perkValues[2]) ? (float)$perkValues[2] / 100 : null;
                
                    $buildingRatio = $buildingOwned / $this->land;
                
                    $effectFromPerk = $buildingRatio * $perkRatio * ($perkMultiplier + $buildingRatio) * 100;
                
                    $perk += min($effectFromPerk, $perkMax ?? $perk);
                }

                elseif($perkKey == 'artefact_aegis_restoration')
                {
                    $buildingOwnedForThisPerk = $buildingOwned;

                    $perk += $perkValueString * $buildingOwnedForThisPerk;
                }

                else
                {
                    dd("[Error] Undefined building perk key (\$perkKey): $perkKey");
                }

                # Build-specific perks
                $buildingSpecificMultiplier = 1;
                #$originalPerk = $perk;

                if($perkKey == 'gold_production_raw')
                {
                    #isset($iterations) ? $iterations += 1 : $iterations = 1;
                    $buildingSpecificMultiplier += $this->getDecreePerkMultiplier('building_' . $holdBuilding->building->key . '_production_mod');
                    $buildingSpecificMultiplier += $this->getSpellPerkMultiplier('building_' . $holdBuilding->building->key . '_production_mod');
                }

                if($perkKey == 'extra_units_trained' or $perkKey == 'improvements')
                {
                    $buildingSpecificMultiplier += $this->getDecreePerkMultiplier('building_' . $holdBuilding->building->key . '_perk_mod');
                    $buildingSpecificMultiplier += $this->getSpellPerkMultiplier('building_' . $holdBuilding->building->key . '_perk_mod');
                    $buildingSpecificMultiplier += $this->getTechPerkMultiplier('building_' . $holdBuilding->building->key . '_perk_mod');
                }

            #}

            if(is_numeric($perk))
            {
                $perk *= $buildingSpecificMultiplier ?? 1;
            }

            $buildingSpecificMultiplier = null;
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
        if (Str::contains($perkValue, ',')) {
            $perkValues = explode(',', $perkValue);

            foreach($perkValues as $key => $value) {
                if (!Str::contains($value, ';')) {
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
            ->where('dominion_spells.duration', '>', 0)
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

    protected function getRacePerkValue(string $perkKey): float
    {
        return $this->race->getPerkValue($perkKey);
    }

    protected function getRacePerkMultiplier(string $perkKey): float
    {
        return ($this->getRacePerkValue($perkKey) / 100);
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
        #return Cache::remember("dominion.{$this->id}.spellPerkValue.{$perkKey}", 5, function () use ($perkKey)
        #{
            $deityKey = $this->hasDeity() ? $this->deity->key : null;
            $perk = 0;

            $dominionSpells = DominionSpell::where('caster_id', '=', $this->id)
                    ->orWhere('dominion_id', '=', $this->id)
                    ->get()
                    ->keyBy('spell_id');
            
            $spells = $this->spells()->with(['perks', 'deity'])->get();

            # We're only interested in spells that have the perk we're looking for
            /*
            $spells = $spells->filter(function (Spell $spell) use ($perkKey) {
                return $spell->perks->contains(function (SpellPerkType $spellPerkType) use ($perkKey) {
                    return $spellPerkType->key === $perkKey;
                });
            });
            */

            // Filter out spells that don't contain the perk key
            $spells = $this->spells->filter(function ($spell) use ($perkKey) {
                return $spell->perks->contains('key', $perkKey);
            });

            # Check each spell
            foreach ($spells as $spell)
            {
                $dominionSpell = $dominionSpells->get($spell->id);
                $perkValueString = $spell->getPerkValue($perkKey);

                # Ignore spells that are not active or have perk value equal to 0
                if($dominionSpell->duration <= 0 or $perkValueString === 0)
                {
                    continue;
                }

                # Ignore spells that require a deity unless the dominion has a deity
                if(isset($spell->deity) && (!$this->hasDeity() || $spell->deity->id !== $this->deity->id))
                {
                    continue;
                }

                # Basic spells with just a numeric value
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
                # Deity spells (no max): deityKey, perk, max
                elseif(in_array($perkKey, ['defensive_power_from_terrain', 'offensive_power_from_terrain']))
                {
                    $perkValueArray = $spell->getActiveSpellPerkValues($spell->key, $perkKey);

                    $terrainKey = (string)$perkValueArray[0];
                    $ratio = (float)$perkValueArray[1];
                    $terrainRatio = $this->{'terrain_' . $terrainKey} / $this->land;
                    $terrainRatio *= 100;

                    $perk += $terrainRatio * $ratio;

                }
                elseif($perkKey == 'defense_from_resource')
                {

                    $perkValueArray = $spell->getActiveSpellPerkValues($spell->key, $perkKey);

                    $dpPerResource = (float)$perkValueArray[0];
                    $resourceKey = (string)$perkValueArray[1];

                    $perk = $this->{'resource_'.$resourceKey} * $dpPerResource;
                }
                elseif($perkKey == 'resource_lost_on_invasion')
                {
                    return True;
                }
                elseif($perkKey == 'elk_production_raw_from_terrain')
                {
                    $perkValueArray = $spell->getActiveSpellPerkValues($spell->key, $perkKey);

                    $perAcre = (float)$perkValueArray[0];
                    $terrainKey = (string)$perkValueArray[1];

                    $perk += floor($perAcre * min($this->{'terrain_' . $terrainKey}, $this->land));
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

                if(($spellDamageSufferedPerk = $this->getTechPerkMultiplier($spell->key . '_spell_damage_suffered')))
                {
                    $perk *= (1 + $spellDamageSufferedPerk);
                }
            }

            if (is_numeric($perk))
            {
                #$perk *= 1 + $this->realm->getArtefactPerkMultiplier('spell_perks_mod');
            }

            return $perk;
        #});
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
        #return Cache::remember("dominion.{$this->id}.improvementPerkValue.{$perkKey}", 5, function () use ($perkKey)
        #{
            $perk = 0;

            foreach ($this->improvements as $improvement)
            {
                if($perkValueString = $improvement->getPerkValue($perkKey))
                {
                    $perkValues = $this->extractImprovementPerkValues($perkValueString);
                    $max = (float)$perkValues[0];

                    $max += $this->race->getPerkValue('improvements_max');

                    $coefficient = (float)$perkValues[1];
                    $invested = (float)$improvement->pivot->invested;

                    $perk += $max * (1 - exp(-$invested / ($coefficient * $this->land + 15000)));
                }
            }

            $perk *= $this->getImprovementsMod();

            return $perk;
        #});
    }

    public function getImprovementsMod(): float
    {
        $multiplier = 1;
        $multiplier += $this->getBuildingPerkMultiplier('improvements');
        $multiplier += $this->getBuildingPerkMultiplier('quadratic_improvements_mod');
        $multiplier += $this->getBuildingPerkMultiplier('improvements_capped');
        $multiplier += $this->getSpellPerkMultiplier('improvements');
        $multiplier += $this->getAdvancementPerkMultiplier('improvements');
        $multiplier += $this->getTechPerkMultiplier('improvements');
        #$multiplier += $this->getDeityPerkMultiplier('improvements'); # Breaks
        $multiplier += $this->race->getPerkMultiplier('improvements');
        #$multiplier += $this->realm->getArtefactPerkMultiplier('improvements');
        $multiplier += $this->getDecreePerkMultiplier('improvements'); 

        if($this->race->getPerkValue('improvements_from_souls'))
        {
            $multiplier += $this->resource_soul / ($this->land * 1000);
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
        if (Str::contains($perkValue, ','))
        {
            $perkValues = explode(',', $perkValue);

            foreach($perkValues as $key => $value)
            {
                if (!Str::contains($value, ';'))
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

        #return Cache::remember("dominion.{$this->id}.deityPerkValue.{$perkKey}", 5, function () use ($perkKey)
        #{
            $multiplier = 1;
            $multiplier += $this->getBuildingPerkMultiplier('deity_power');
            $multiplier += $this->race->getPerkMultiplier('deity_power');
            $multiplier += $this->title->getPerkMultiplier('deity_power') * $this->getTitlePerkMultiplier();
            $multiplier += $this->getDecreePerkMultiplier('deity_power');
            #$multiplier += $this->realm->getArtefactPerkMultiplier('deity_power_mod');
            
            $devotionDurationMultiplier = 1 + min($this->devotion->duration * 0.1 / 100, 1);

            return (float)$this->deity->getPerkValue($perkKey) * $multiplier * $devotionDurationMultiplier;
        #});
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
        #return Cache::remember("dominion.{$this->id}.terrainPerkValue.{$perkKey}", 5, function () use ($perkKey)
        #{
            return $this->race->raceTerrains->sum(function ($raceTerrain) use ($perkKey)
            {
                $terrainPerk = $raceTerrain->perks()->where('key', $perkKey)->first();
                return $terrainPerk ? $terrainPerk->pivot->value * $this->{'terrain_' . $raceTerrain->terrain->key} : 0;
            });
        #});
    }
    
    public function getTerrainPerkMultiplier(string $perkKey): float
    {

        #return Cache::remember("dominion.{$this->id}.terrainPerkMultiplier.{$perkKey}", 5, function () use ($perkKey)
        #{
            return $this->race->raceTerrains->sum(function ($raceTerrain) use ($perkKey)
            {
                $terrainPerk = $raceTerrain->perks()->where('key', $perkKey)->first();
                return $terrainPerk ? ($terrainPerk->pivot->value * $this->{'terrain_' . $raceTerrain->terrain->key}) / $this->land : 0;
            });
        #});
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
        #return Cache::remember("dominion.{$this->id}.advancementPerkValue.{$perkKey}", 5, function () use ($perkKey)
        #{
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
        #});
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
