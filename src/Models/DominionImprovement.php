<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\DominionImprovement
 *
 * @property int $dominion_id
 * @property int $tech_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\Improvement $tech
 */
class DominionImprovement extends AbstractModel
{
    protected $table = 'dominion_improvements';

    protected $casts = [
        'invested' => 'float',
    ];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class, 'dominion_id');
    }

    public function improvement()
    {
        return $this->belongsTo(Improvement::class, 'improvement_id');
    }
}
