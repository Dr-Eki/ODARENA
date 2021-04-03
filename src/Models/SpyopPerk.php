<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\SpyopPerk
 *
 * @property int $id
 * @property int $spyop_id
 * @property int $spyop_perk_type_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Spyop $spyop
 * @property-read \OpenDominion\Models\SpyopPerkType $type
 */
class SpyopPerk extends AbstractModel
{
    public function spyop()
    {
        return $this->belongsTo(Spyop::class);
    }

    public function type()
    {
        return $this->belongsTo(SpyopPerkType::class, 'spyop_perk_type_id');
    }
}
