<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\ImprovementPerkType
 *
 * @property int $id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Improvement[] $improvements
 */
class ImprovementPerkType extends AbstractModel
{
    public function improvements()
    {
        return $this->belongsToMany(
            Improvement::class,
            'improvement_perks',
            'improvement_perk_type_id',
            'improvement_id'
        )
            ->withTimestamps();
    }
}
