<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\ImprovementPerk
 *
 * @property int $id
 * @property int $improvement_id
 * @property int $improvement_perk_type_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Improvement $improvement
 * @property-read \OpenDominion\Models\ImprovementPerkType $type
 */
class ImprovementPerk extends AbstractModel
{
    public function improvement()
    {
        return $this->belongsTo(Improvement::class);
    }

    public function type()
    {
        return $this->belongsTo(ImprovementPerkType::class, 'improvement_perk_type_id');
    }
}
