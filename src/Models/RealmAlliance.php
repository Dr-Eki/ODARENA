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
        'established'
    ];
}
