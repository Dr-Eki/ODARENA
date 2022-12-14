<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Race
 *
 * @property int $id
 * @property string $name
 * @property string $alignment
 * @property string $home_land_type
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
          'key' => 'text',
          'resources' => 'array',
          'improvement_resources' => 'array',
          'land_improvements' => 'array',
          'peasants_production' => 'array',
          'home_land_type' => 'text',
          'peasants_alias' => 'text',
          'draftees_alias' => 'text',
          'construction_materials' => 'array',
          'round_modes' => 'array',
          'spies_cost' => 'text',
          'wizards_cost' => 'text',
          'archmages_cost' => 'text',

          'psionic_strength' => 'float',
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

    public function units()
    {
        return $this->hasMany(Unit::class)
            ->orderBy('slot');
    }
   
    public function getBuildings()
    {
        return Building::where(function ($query) {
            if (empty($this->exclusive_races) && empty($this->excluded_races)) {
                $query->where('exclusive_races', '=', null)
                    ->orWhere('exclusive_races', '=', [])
                    ->orWhere('exclusive_races', 'like', '%' . $this->name . '%')
                    ->where('excluded_races', '=', null)
                    ->orWhere('excluded_races', '=', [])
                    ->orWhereNotIn('excluded_races', [$this->name]);
            } else {
                $query->where('exclusive_races', 'like', '%' . $this->name . '%')
                    ->whereNotIn('excluded_races', [$this->name]);
            }
        })->get();
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
        if (str_contains($perkValue, ',')) {
            $perkValue = explode(',', $perkValue);

            foreach($perkValue as $key => $value) {
                if (!str_contains($value, ';')) {
                    continue;
                }

                $perkValue[$key] = explode(';', $value);
            }
        }

        return $perkValue;
    }

}
