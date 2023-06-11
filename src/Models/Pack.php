<?php

namespace OpenDominion\Models;

use Carbon\Carbon;

/**
 * OpenDominion\Models\Pack
 *
 * @property int $id
 * @property int $round_id
 * @property int|null $realm_id
 * @property int $creator_dominion_id
 * @property string $name
 * @property string $password
 * @property int $size
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion[] $dominions
 * @property-read \OpenDominion\Models\Realm|null $realm
 * @property-read \OpenDominion\Models\Round $round
 * @property-read \OpenDominion\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Pack newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Pack newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Pack query()
 * @mixin \Eloquent
 */
class Pack extends AbstractModel
{
    protected $table = 'packs';

    protected $casts = [
        'password' => 'string',
        'status' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dominions()
    {
        return $this->hasMany(Dominion::class);
    }

    public function realm()
    {
        return $this->belongsTo(Realm::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }
}
