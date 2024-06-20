<?php

namespace OpenDominion\Models;

use Webpatser\Uuid\Uuid;

/**
 * OpenDominion\Models\GameEvent
 *
 * @property string $id
 * @property int $round_id
 * @property string $source_type
 * @property int $source_id
 * @property string|null $target_type
 * @property int|null $target_id
 * @property string $type
 * @property array|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Round $round
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $source
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $target
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\GameEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\GameEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\GameEvent query()
 * @mixin \Eloquent
 */
class GameEvent extends AbstractModel
{
    public $incrementing = false;

    protected $casts = [
        'data' => 'array',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function target()
    {
        return $this->morphTo();
    }

    public function story()
    {
        return $this->hasOne(GameEventStory::class, 'game_event_id');
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->id = Uuid::generate();
        });
    }

    public function isInWorldNewsUserSettings(Dominion $viewer, array $userWorldNewsSettings)
    {
        $scope = $this->isOwnRealmEvent($viewer) ? 'own' : 'other';
    
        return in_array($this->type, $userWorldNewsSettings[$scope]);
    }
    
    public function isInWorldNewsRealm(Realm $realm = null)
    {
        return $realm === null || $this->isInRealm($realm);
    }

    public function isInRealm(Realm $realm)
    {
        if ($this->source_type === Dominion::class && $this->source->realm_id === $realm->id) {
            return true;
        }

        if ($this->target_type === Dominion::class && $this->target->realm_id === $realm->id) {
            return true;
        }

        if ($this->source_type === Realm::class && $this->source_id === $realm->id) {
            return true;
        }

        if ($this->target_type === Realm::class && $this->target_id === $realm->id) {
            return true;
        }

        return false;
    }

    public function isOwnRealmEvent(Dominion $viewer)
    {
        return $this->isInRealm($viewer->realm);
    }
}
