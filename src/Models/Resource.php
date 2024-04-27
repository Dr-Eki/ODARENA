<?php

namespace OpenDominion\Models;

class Resource extends AbstractModel
{
    protected $table = 'resources';

    protected $casts = [
        'excluded_races' => 'array',
        'exclusive_races' => 'array',
        'buy_value' => 'float',
        'sell_value' => 'float',
        'improvement_points' => 'float',
        'trade' => 'array',
    ];

    public function getTradeAttribute($value)
    {
        return json_decode($value);
    }

    public static function fromKey($key)
    {
        return self::where('key', $key)->first();
    }
}