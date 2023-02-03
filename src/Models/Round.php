<?php

namespace OpenDominion\Models;

use Carbon\Carbon;
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

    // Query Scopes

    /**
     * Scope a query to include only active rounds.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereRaw('start_date <= NOW()
                                and (end_date IS NULL or end_date > NOW())
                                and (end_tick IS NULL or end_tick > ticks)');
    }

    /**
     * Returns whether a user can register to this round.
     *
     * @return bool
     */
    public function openForRegistration()
    {
        return $this->hasEnded() ? false : true;
    }

    /**
     * Returns the amount in days until registration opens.
     *
     * @return int
     */
    public function daysUntilRegistration()
    {
        return $this->start_date->diffInDays(new Carbon('+30 days midnight'));
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

    public function mode(): string
    {
        return $this->mode;
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
        $countdown = GameEvent::where('round_id', $this->id)->where(function($query)
        {
      			$query->where('type','round_countdown')
          				->orWhere('type','round_countdown_duration');
        })->first();

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
        if(isset($this->end_tick))
        {
            return ($this->ticks >= $this->end_tick);
        }

        if(isset($this->end_date))
        {
            return ($this->end_date <= now());
        }

        return false;
    }

    /**
     * Returns whether offensive actions (and exploration) are disabled for the
     * rest of the round.
     *
     * Actions like these are disabled near the end of the round to prevent
     * suicides and whatnot.
     *
     * @return bool
     */
    public function hasOffensiveActionsDisabled(): bool
    {
        if ($this->offensive_actions_prohibited_at === null) {
            return false;
        }

        return ($this->offensive_actions_prohibited_at <= now());
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
        return $this->start_date->diffInDays(today());
    }

    /**
     * Returns the amount in days until the round starts.
     *
     * @return int
     */
    public function hoursUntilStart()
    {
        return $this->start_date->diffInHours(now());
    }

    /**
     * Returns the amount in days until the round starts.
     *
     * @return int
     */
    public function minutesUntilStart()
    {
        return $this->start_date->diffInMinutes(now());
    }

    /**
     * Returns the amount in days until the round ends.
     *
     * @return int
     */
    public function daysUntilEnd(): int
    {
        if($this->end_date !== Null)
        {
            return $this->end_date->diffInDays(today());
        }
        return 0;
    }

    /**
     * Returns the amount in days until the round ends.
     *
     * @return int
     */
    public function hoursUntilEnd(): int
    {
        if($this->end_date !== Null)
        {
            return $this->end_date->diffInHours(now());
        }
        return 0;
    }

    /**
     * Returns the round duration in days.
     *
     * @return int
     */
    public function durationInDays()
    {
        return $this->start_date->diffInDays($this->end_date);
    }
}
