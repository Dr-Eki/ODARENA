<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\SpyopPerkType
 *
 * @property int $id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Spyop[] $spyops
 */
class SpyopPerkType extends AbstractModel
{
    public function spyops()
    {
        return $this->belongsToMany(
            Spyop::class,
            'spyop_perks',
            'spyop_perk_type_id',
            'spyop_id'
        )
            ->withTimestamps();
    }
}
