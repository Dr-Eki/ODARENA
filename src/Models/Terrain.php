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
class Terrain extends AbstractModel
{
    protected $table = 'terrains';

    protected $casts = [
        'name' => 'string',
        'description' => 'string',
        'order' => 'integer'
    ];
}
