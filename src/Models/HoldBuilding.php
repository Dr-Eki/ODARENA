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
class HoldBuilding extends AbstractModel
{
    protected $table = 'hold_buildings';

    public function hold()
    {
        return $this->belongsTo(Hold::class, 'hold_id');
    }

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function getAmountAttribute($value)
    {
        return (int)$value;
    }

    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = (int)$value;
    }
}
