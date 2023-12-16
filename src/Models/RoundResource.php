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
class RoundResource extends AbstractModel
{
    protected $table = 'round_resources';

    public function realm()
    {
        return $this->belongsTo(Round::class, 'round_id');
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }
}
