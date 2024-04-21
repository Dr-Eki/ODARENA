<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Spell
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property array $prerequisites
 * @property int $costMultiplier
 * @property int $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\SpellPerkType[] $perks
 */
class Resource extends AbstractModel
{
    protected $table = 'resources';

    protected $casts = [
        'excluded_races' => 'array',
        'exclusive_races' => 'array',
        'buy_value' => 'float',
        'sell_value' => 'float',
        'improvement_points' => 'float',
    ];

    public static function fromKey($key)
    {
        return self::where('key', $key)->first();
    }

}
