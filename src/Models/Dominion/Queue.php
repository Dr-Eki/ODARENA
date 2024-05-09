<?php

namespace OpenDominion\Models\Dominion;

use OpenDominion\Models\AbstractModel;

/**
 * OpenDominion\Models\Dominion\Queue
 *
 * @property int $dominion_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\Queue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\Queue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\Queue query()
 * @mixin \Eloquent
 */
class Queue extends AbstractModel
{
    protected $table = 'dominion_queue';

    protected $fillable = [
        'dominion_id',
        'source',
        'resource',
        'hours',
        'amount',
    ];

    protected $guarded = ['created_at', 'updated_at'];

    protected $dates = ['created_at', 'updated_at'];

    #const UPDATED_AT = null;
}
