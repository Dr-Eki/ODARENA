<?php

namespace OpenDominion\Models\TradeRoute;

use OpenDominion\Models\AbstractModel;

use OpenDominion\Models\Resource;

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
    protected $table = 'trade_route_queue';

    protected $guarded = ['created_at'];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    protected $dates = ['created_at'];

    const UPDATED_AT = null;
}
