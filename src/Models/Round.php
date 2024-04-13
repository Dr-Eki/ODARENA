<?php

namespace OpenDominion\Models;

use DB;
use Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\Round
 *
 * @property int $id
 * @property int $round_league_id
 * @property int $number
 * @property string $name
 * @property int $realm_size
 * @property int $pack_size
 * @property int $players_per_race
 * @property bool $mixed_alignment
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property \Illuminate\Support\Carbon $offensive_actions_prohibited_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion[] $dominions
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\GameEvent[] $gameEvents
 * @property-read \OpenDominion\Models\RoundLeague $league
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Pack[] $packs
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Realm[] $realms
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Round active()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Round newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Round newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Round query()
 * @mixin \Eloquent
 */
class Round extends AbstractModel
{
    protected $dates = [
        'start_date',
        'end_date',
        'offensive_actions_prohibited_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'settings' => 'array',
        'mode' => 'string',
        'goal' => 'integer',
        'is_ticking' => 'boolean',
        'has_ended' => 'boolean',

        'start_date' => 'datetime',
        'end_date' => 'datetime',
        
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Eloquent Relations

    public function dominions()
    {
        return $this->hasManyThrough(Dominion::class, Realm::class);
    }

    public function activeDominions()
    {
        return $this->dominions()->where('is_locked', false);
    }

    public function gameEvents()
    {
        return $this->hasMany(GameEvent::class);
    }

    public function league()
    {
        return $this->hasOne(RoundLeague::class, 'id', 'round_league_id');
    }

    public function packs()
    {
        return $this->hasMany(Pack::class);
    }

    public function realms()
    {
        return $this->hasMany(Realm::class);
    }
    
    public function winners()
    {
        return $this->hasMany(RoundWinner::class);
    }

    public function resources()
    {
        return $this->belongsToMany(
            Resource::class,
            'round_resources',
            'round_id',
            'resource_id'
        )
            ->withPivot('amount');
    }
    # This code enables the following syntax:
    # $round->{'resource_' . $terrainKey} and similar

    public function __get($key)
    {
    
        if (preg_match('/^resource_(\w+)$/', $key, $matches)) {
            return $this->getResourceAmount($matches[1]);
        }
        
        return parent::__get($key);
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

    // Query Scopes

    /**
     * Scope a query to include only active rounds.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('has_ended', false)->where('start_date', '<=', now());
        /*
        $currentTickCount = $this->ticks;
    
        return $query->where('start_date', '<=', now())
                     ->where(function ($query) {
                         $query->whereNull('end_date')
                               ->orWhere('end_date', '>', now());
                     })
                     ->where(function ($query) use ($currentTickCount) {
                         if ($currentTickCount !== null) {
                             $query->whereNull('end_tick')
                                   ->orWhere('end_tick', '>', $currentTickCount);
                         } else {
                             $query->whereNull('end_tick');
                         }
                     });
        */
    }

    /**
     * Returns whether a user can register to this round.
     *
     * @return bool
     */
    public function openForRegistration()
    {
        return !$this->hasEnded();
    }

    public function userAlreadyRegistered(User $user)
    {
        $results = DB::table('dominions')
            ->where('user_id', $user->id)
            ->where('round_id', $this->id)
            ->limit(1)
            ->get();

        return (\count($results) === 1);
    }

    /**
     * Returns whether a round has started.
     *
     * @return bool
     */
    public function hasStarted()
    {
        return ($this->start_date <= now());
    }

    /**
     * Returns whether a round has ended.
     *
     * @return bool
     */
    public function hasCountdown()
    {
        $countdown = GameEvent::where('round_id', $this->id)
        ->where(function($query) {
            $query->where('type','round_countdown')
                ->orWhere('type','round_countdown_duration')
                ->orWhere('type','round_countdown_artefacts');
        })
        ->orderBy('created_at', 'desc')
        ->first();

        # Check if there is a more recent round_countdown_artefacts_cancelled game event
        if($countdown && $countdown->type == 'round_countdown_artefacts')
        {
            $cancelled = GameEvent::where('round_id', $this->id)
                ->where('type','round_countdown_artefacts_cancelled')
                ->where('created_at','>',$countdown->created_at)
                ->first();

            if($cancelled)
            {
                return false;
            }
        }

        return $countdown ? true : false;
    }

    public function ticksUntilEnd()
    {
        if($this->hasEnded())
        {
            return 0;
        }

        if(isset($this->end_tick))
        {
            return ($this->end_tick - $this->ticks);
        }

        return -1;
    }

    /**
     * Returns whether a round has ended.
     *
     * @return bool
     */
    public function hasEnded()
    {
        return $this->has_ended;

        /*
        if(isset($this->end_tick))
        {
            return ($this->ticks >= $this->end_tick);
        }

        if(isset($this->end_date))
        {
            return ($this->end_date <= now());
        }

        return false;
        */
    }

    /**
     * Returns whether a round is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return ($this->hasStarted() && !$this->hasEnded());
    }

    /**
     * Returns the amount in days until the round starts.
     *
     * @return int
     */
    public function daysUntilStart()
    {
        return today()->diffInDays($this->start_date);
    }

    /**
     * Returns the amount in days until the round starts.
     *
     * @return int
     */
    public function hoursUntilStart()
    {
        return now()->diffInHours($this->start_date);
    }

    /**
     * Returns the amount in days until the round starts.
     *
     * @return int
     */
    public function minutesUntilStart()
    {
        #return $this->start_date->diffInMinutes(now());
        return now()->diffInMinutes($this->start_date);
    }

    public function getSetting(string $key, $default = false)
    {
        return $this->settings[$key] ?? $default;
    }

    # Get the nth largest dominion in the round
    public function getNthLargestDominion(int $n): Dominion
    {
        return $this->dominions()->orderBy('land', 'desc')->skip($n - 1)->first();
    }

}
