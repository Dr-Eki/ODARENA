<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\DominionBuilding
 *
 * @property int $dominion_id
 * @property int $tech_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\Tech $tech
 */
class DominionUnit extends AbstractModel
{
    protected $table = 'dominion_units';

    protected $casts = [
        'amount' => 'integer',
        'state' => 'integer',
    ];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class, 'dominion_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function race()
    {
        return $this->hasOneThrough(
            Race::class,
            Unit::class,
            'id', // Foreign key on the Unit table...
            'id', // Foreign key on the Race table...
            'unit_id', // Local key on the DominionUnit table...
            'race_id' // Local key on the Unit table...
        );
    }


}
