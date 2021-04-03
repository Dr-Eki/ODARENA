<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Title
 *
 * @property int $id
 * @property string $name
 * @property string $alignment
 * @property string $home_land_type
 * @property int $playable
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion[] $dominions
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\TitlePerkType[] $perks
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Unit[] $units
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Title newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Title newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Title query()
 * @mixin \Eloquent
 */
class Title extends AbstractModel
{

    public function perks()
    {
        return $this->belongsToMany(
            TitlePerkType::class,
            'title_perks',
            'title_id',
            'title_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value');
    }

    /**
     * Gets a Title's perk multiplier.
     *
     * @param string $key
     * @return float
     */
    public function getPerkMultiplier(string $key): float
    {
        return ($this->getPerkValue($key) / 100);
    }

    /**
     * Gets a Title's perk XP Bonus.
     *
     * @param string $key
     * @return float
     */
    public function getPerkBonus(Dominion $dominion): float
    {
        $bonus = 0;
        $bonus += (1 - exp(-pi()*$dominion->resource_tech / 100000));
        $bonus += $dominion->race->getPerkMultiplier('title_bonus');
        return 1 + $bonus;
    }

    /**
     * @param string $key
     * @return float
     */
    public function getPerkValue(string $key): float
    {
        $perks = $this->perks->filter(function (TitlePerkType $titlePerkType) use ($key)
        {
            return ($titlePerkType->key === $key);
        });

        if ($perks->isEmpty())
        {
            return 0;
        }

        return (float)$perks->first()->pivot->value;
    }
}
