<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Race
 *
 * @property int $id
 * @property string $name
 * @property string $alignment
 * @property string $home_land_type
 * @property int $playable
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Race[] $dominions
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Race newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Race newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Race query()
 * @mixin \Eloquent
 */
class DominionState extends AbstractModel
{
    protected $table = 'dominion_states';

    protected $casts = [
        'daily_land' => 'integer',
        'daily_gold' => 'integer',
        'monarchy_vote_for_dominion_id' => 'integer',
        'tick_voted' => 'integer',
        'most_recent_improvement_resource' => 'text',
        'most_recent_exchange_from' => 'text',
        'most_recent_exchange_to' => 'text',
        'notes' => 'text',
        'deity' => 'text',
        'devotion_ticks' => 'integer',
        'draft_rate' => 'integer',
        'morale' => 'integer',
        'peasants' => 'integer',
        'peasants_last_hour' => 'integer',
        'prestige' => 'float',
        'xp' => 'integer',
        'spy_strength' => 'integer',
        'wizard_strength' => 'integer',
        'protection_ticks' => 'integer',
        'ticks' => 'integer',

        'buildings' => 'array',
        'improvements' => 'array',
        'land' => 'array',
        'resources' => 'array',
        'spells' => 'array',
        'advancements' => 'array',
        'techs' => 'array',
        'terrains' => 'array',
        'decree_states' => 'array',
        'units' => 'array',
        'queues' => 'array',
    ];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class);
    }

}
