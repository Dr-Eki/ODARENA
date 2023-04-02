<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\RealmResource
 *
 * @property int $dominion_id
 * @property int $tech_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\Spell $tech
 */
class RealmAlliance extends AbstractModel
{
    protected $table = 'realm_alliances';

    protected $fillable = [
        'realm_id',
        'allied_realm_id',
        'established_tick'
    ];

    public function realm()
    {
        return $this->belongsTo(Realm::class, 'realm_id');
    }

    public function ally()
    {
        return $this->belongsTo(Realm::class, 'allied_realm_id');
    }

    # Return a collection of both realm and ally
    public function getRealms()
    {
        return collect([$this->realm, $this->ally]);
    }

}
