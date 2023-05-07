<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Spell
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property array $prerequisites
 * @property int $costMultiplier
 * @property int $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\SpellPerkType[] $perks
 */
class RaceTerrain extends AbstractModel
{
    protected $table = 'race_terrains';

    protected $casts = [
        'name' => 'string',
        'description' => 'string',
        'order' => 'integer'
    ];

    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    public function terrain()
    {
        return $this->belongsTo(Terrain::class);
    }

    public function perks()
    {
        return $this->belongsToMany(
            RaceTerrainPerkType::class,
            'race_terrain_perks',
            'race_terrain_id',
            'race_terrain_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value');
    }
    
    public function getPerkValue(string $key)
    {
        $perks = $this->perks->filter(static function (RaceTerrainPerkType $raceTerrainPerkType) use ($key)
        {
            return ($raceTerrainPerkType->key === $key);
        });

        if ($perks->isEmpty())
        {
            return 0;
        }

        return $perks->first()->pivot->value;
    }
}
