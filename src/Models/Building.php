<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Building
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property array $prerequisites
 * @property int $costMultiplier
 * @property int $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\BuildingPerkType[] $perks
 */
class Building extends AbstractModel
{
    protected $table = 'buildings';

    protected $casts = [
        'excluded_races' => 'array',
        'exclusive_races' => 'array',
        'category' => 'string',
        'round_modes' => 'array',
        'enabled' => 'integer',
    ];

    public function deity()
    {
        return $this->belongsTo(Deity::class);
    }

    public function perks()
    {
        return $this->belongsToMany(
            BuildingPerkType::class,
            'building_perks',
            'building_id',
            'building_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value');
    }

    public function getPerkValue(string $key)
    {
        $perks = $this->perks->filter(static function (BuildingPerkType $buildingPerkType) use ($key) {
            return ($buildingPerkType->key === $key);
        });

        if ($perks->isEmpty()) {
            return null; // todo: change to null instead, also add return type and docblock(s)
        }

        return $perks->first()->pivot->value;
    }

    public function extractPerkValues(string $key)
    {

        # Get building perk with key
        $perk = $this->perks->filter(static function (BuildingPerkType $buildingPerkType) use ($key) {
            return ($buildingPerkType->key === $key);
        });

        if($perk->count() == 0)
        {
            return 0;
        }

        $perkValue = $perk->first()->pivot->value;
        if (str_contains($perkValue, ','))
        {
            $perkValue = explode(',', $perkValue);

            foreach($perkValue as $key => $value)
            {
                if (!str_contains($value, ';'))
                {
                    continue;
                }

                $perkValue[$key] = explode(';', $value);
            }
        }

        return $perkValue;
    }

    # Return the terrain of the building
    public function terrain()
    {
        return $this->belongsTo(Terrain::class);
    }

}
