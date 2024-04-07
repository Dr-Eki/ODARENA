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
class DominionTerrain extends AbstractModel
{
    protected $table = 'dominion_terrains';

    protected $casts = [
        'amount' => 'integer',
    ];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class, 'dominion_id');
    }

    public function terrain()
    {
        return $this->belongsTo(Terrain::class, 'terrain_id');
    }
}
