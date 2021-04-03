<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\BuildingPerkType
 *
 * @property int $id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Building[] $buildingss
 */
class BuildingPerkType extends AbstractModel
{
    public function buildings()
    {
        return $this->belongsToMany(
            Building::class,
            'building_perks',
            'building_perk_type_id',
            'building_id'
        )
            ->withTimestamps();
    }
}
