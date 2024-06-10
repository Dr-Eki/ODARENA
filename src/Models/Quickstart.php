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
        'name' => 'string',
        'description' => 'string',
        'offensive_power' => 'integer',
        'defensive_power' => 'integer',
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

    protected $fillable = [
        'race_id',
        'title_id',
        'deity_id',
        'user_id',
        'is_public',
        'enabled',

        'name',
        'description',
        'offensive_power',
        'defensive_power',
        
        'land',
        'devotion_ticks',
        'draft_rate',
        'morale',
        'peasants',
        'prestige',
        'spy_strength',
        'protection_ticks',
        'wizard_strength',
        'xp',
        'buildings',
        'cooldown',
        'improvements',
        'resources',
        'spells',
        'advancements',
        'decree_states',
        'techs',
        'terrains',
        'units',
        'queues',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
