<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\SpellPerkType
 *
 * @property int $id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Spell[] $spells
 */
class RaceTerrainPerkType extends AbstractModel
{
    public function raceTerrains()
    {
        return $this->belongsToMany(
            RaceTerrain::class,
            'race_terrain_perks',
            'race_terrain_perk_type_id',
            'race_terrain_id'
        )
            ->withTimestamps();
    }
}
