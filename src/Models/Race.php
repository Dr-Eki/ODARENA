<?php

namespace OpenDominion\Models;

use Illuminate\Support\Str;

/**
 * OpenDominion\Models\Race
 *
 * @property int $id
 * @property string $name
 * @property string $alignment
 * @property int $playable
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion[] $dominions
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\RacePerkType[] $perks
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Unit[] $units
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Race newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Race newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Race query()
 * @mixin \Eloquent
 */
class Race extends AbstractModel
{

    protected $casts = [
        'key' => 'string',
        'resources' => 'array',
        'improvement_resources' => 'array',
        'peasants_production' => 'array',
        'home_terrain' => 'string',
        'peasants_alias' => 'string',
        'draftees_alias' => 'string',
        'construction_materials' => 'array',
        'round_modes' => 'array',
        'spies_cost' => 'string',
        'wizards_cost' => 'string',
        'archmages_cost' => 'string',

        'psionic_strength' => 'float',
        'magic_level' => 'integer',
    ];

    public function dominions()
    {
        return $this->hasMany(Dominion::class);
    }

    public function perks()
    {
        return $this->belongsToMany(
            RacePerkType::class,
            'race_perks',
            'race_id',
            'race_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value')
            ->orderBy('race_perk_types.key');
    }

    public function terrainPerks()
    {
        return $this->belongsToMany(
            RaceTerrainPerkType::class,
            'race_terrain_perks',
            'race_terrain_id',
            'race_terrain_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value')
            ->orderBy('race_terrain_perk_types.key');
    }


    public function raceTerrains()
    {
        return $this->hasMany(RaceTerrain::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class)
            ->orderBy('slot');
    }



    # Get race home terrain
    public function homeTerrain()
    {
        return Terrain::where('id', $this->home_terrain_id)->first();
    }

    public function getBuildings()
    {
        return Building::where(function ($query) {
            $query->whereNull('exclusive_races')
                ->orWhere('exclusive_races', 'like', '%' . $this->name . '%');
        })
        ->where(function ($query) {
            $query->whereNull('excluded_races')
                ->orWhere('excluded_races', 'not like', '%' . $this->name . '%');
        })
        ->get();
    }
    
    
    public function getImprovements()
    {
        return Improvement::where(function ($query) {
            $query->whereNull('exclusive_races')
                ->orWhere('exclusive_races', 'like', '%' . $this->name . '%');
        })
        ->where(function ($query) {
            $query->whereNull('excluded_races')
                ->orWhere('excluded_races', 'not like', '%' . $this->name . '%');
        })
        ->get();
    }

    public function getSpyops()
    {
        return Spyop::where(function ($query) {
            $query->whereNull('exclusive_races')
                ->orWhere('exclusive_races', 'like', '%' . $this->name . '%');
        })
        ->where(function ($query) {
            $query->whereNull('excluded_races')
                ->orWhere('excluded_races', 'not like', '%' . $this->name . '%');
        })
        ->get();
    }

    public function getSpells()
    {
        return Spell::where(function ($query) {
            $query->whereNull('exclusive_races')
                ->orWhere('exclusive_races', 'like', '%' . $this->name . '%');
        })
        ->where(function ($query) {
            $query->whereNull('excluded_races')
                ->orWhere('excluded_races', 'not like', '%' . $this->name . '%');
        })
        ->get();
    }

    /**
     * Gets a Race's perk multiplier.
     *
     * @param string $key
     * @return float
     */
    public function getPerkMultiplier(string $key): float
    {
        return ($this->getPerkValue($key) / 100);
    }

    /**
     * @param string $key
     * @return float
     */
    public function getPerkValue(string $key): float
    {
        $perks = $this->perks->filter(function (RacePerkType $racePerkType) use ($key) {
            return ($racePerkType->key === $key);
        });

        if ($perks->isEmpty())
        {
            return 0;
        }

        return (float)$perks->first()->pivot->value;
    }

    /**
     * Try to get a unit perk value with provided key for a specific slot.
     *
     * @param int $slot
     * @param string|string[] $unitPerkTypes
     * @param mixed $default
     * @return int|int[]
     */
    public function getUnitPerkValueForUnitSlot(int $slot, $unitPerkTypes, $default = 0)
    {
        if (!is_array($unitPerkTypes)) {
            $unitPerkTypes = [$unitPerkTypes];
        }

        $unitCollection = $this->units->where('slot', '=', $slot);
        if ($unitCollection->isEmpty()) {
            return $default;
        }

        $perkCollection = $unitCollection->first()->perks->whereIn('key', $unitPerkTypes);
        if ($perkCollection->isEmpty()) {
            return $default;
        }

        $perkValue = $perkCollection->first()->pivot->value;
        if (Str::contains($perkValue, ',')) {
            $perkValue = explode(',', $perkValue);

            foreach($perkValue as $key => $value) {
                if (!Str::contains($value, ';')) {
                    continue;
                }

                $perkValue[$key] = explode(';', $value);
            }
        }

        return $perkValue;
    }

}
