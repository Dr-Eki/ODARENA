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

    public function realm()
    {
        return $this->belongsTo(Realm::class, 'realm_id');
    }

    public function allies()
    {
        # Also include allies of allies
        return $this->hasManyThrough(Realm::class, RealmAlliance::class, 'realm_id', 'id', 'realm_id', 'allied_realm_id');
    }
}
