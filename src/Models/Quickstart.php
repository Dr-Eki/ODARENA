<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Race
 *
 * @property int $id
 * @property string $name
 * @property string $alignment
 * @property int $playable
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Race[] $dominions
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Race newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Race newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Race query()
 * @mixin \Eloquent
 */
class Quickstart extends AbstractModel
{

    protected $casts = [
        'enabled' => 'integer',
        'land' => 'integer',
        'devotion_ticks' => 'integer',
        'draft_rate' => 'integer',
        'morale' => 'integer',
        'peasants' => 'integer',
        'prestige' => 'integer',
        'spy_strength' => 'integer',
        'protection_ticks' => 'integer',
        'wizard_strength' => 'integer',
        'xp' => 'integer',
        'buildings' => 'array',
        'cooldown' => 'array',
        'improvements' => 'array',
        'resources' => 'array',
        'spells' => 'array',
        'advancements' => 'array',
        'decree_states' => 'array',
        'techs' => 'array',
        'terrains' => 'array',
        'units' => 'array',
        'queues' => 'array',
    ];

    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    public function title()
    {
        return $this->belongsTo(Title::class);
    }

    public function deity()
    {
        return $this->belongsTo(Deity::class);
    }

}
