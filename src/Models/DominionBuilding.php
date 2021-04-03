<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\DominionBuilding
 *
 * @property int $dominion_id
 * @property int $tech_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\Tech $tech
 */
class DominionBuilding extends AbstractModel
{
    protected $table = 'dominion_buildings';

    public function dominion()
    {
        return $this->belongsTo(Dominion::class, 'dominion_id');
    }

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }
}
