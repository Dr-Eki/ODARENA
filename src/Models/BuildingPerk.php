<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\BuildingPerk
 *
 * @property int $id
 * @property int $building_id
 * @property int $building_perk_type_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Building $building
 * @property-read \OpenDominion\Models\BuildingPerkType $type
 */
class BuildingPerk extends AbstractModel
{
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function type()
    {
        return $this->belongsTo(BuildingPerkType::class, 'building_perk_type_id');
    }
}
