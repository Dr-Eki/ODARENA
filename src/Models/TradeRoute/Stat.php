<?php

namespace OpenDominion\Models\Hold;

use OpenDominion\Models\AbstractModel;

/**
 * OpenDominion\Models\DominionStat
 *
 * @property int $dominion_id
 * @property int $stat_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\Stat $stat
 */
class Stat extends AbstractModel
{
    protected $table = 'hold_stats';

    public function hold()
    {
        return $this->belongsTo(\OpenDominion\Models\Hold::class, 'hold_id');
    }

    public function stat()
    {
        return $this->belongsTo(\OpenDominion\Models\Hold::class, 'stat_id');
    }
}
