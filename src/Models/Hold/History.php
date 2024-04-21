<?php

namespace OpenDominion\Models\Hold;

use OpenDominion\Models\AbstractModel;

/**
 * OpenDominion\Models\Dominion\History
 *
 * @property int $id
 * @property int $dominion_id
 * @property string $event
 * @property array $delta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\History newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\History newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\History query()
 * @mixin \Eloquent
 */
class History extends AbstractModel
{
    protected $table = 'hold_history';

    protected $casts = [
        'delta' => 'array',
        'tick' => 'integer',
    ];

    protected $guarded = ['id', 'created_at'];

    protected $dates = ['created_at'];

    const UPDATED_AT = null;
}
