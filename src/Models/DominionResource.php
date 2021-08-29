<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\DominionTech
 *
 * @property int $dominion_id
 * @property int $tech_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\Spell $tech
 */
class DominionResource extends AbstractModel
{
    protected $table = 'dominion_resources';

    public function dominion()
    {
        return $this->belongsTo(Dominion::class, 'dominion_id');
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }
}
