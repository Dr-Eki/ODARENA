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
class DominionTickState extends AbstractModel
{

    protected $casts = [
        'tick' => 'integer',
        'daily_land' => 'integer',
        'daily_gold' => 'integer',
        'monarchy_vote_for_dominion_id' => 'integer',
        'tick_voted' => 'integer',
        'most_recent_improvement_resource' => 'text',
        'most_recent_exchange_from' => 'text',
        'most_recent_exchange_to' => 'text',
        'notes' => 'text',
        'devotion_ticks' => 'integer',
        'draft_rate' => 'integer',
        'morale' => 'integer',
        'peasants' => 'integer',
        'peasants_last_hour' => 'integer',
        'prestige' => 'float',
        'spy_strength' => 'integer',
        'protection_ticks' => 'integer',
        'wizard_strength' => 'integer',
        'xp' => 'integer',
        'buildings' => 'array',
        'improvements' => 'array',
        'land' => 'array',
        'resources' => 'array',
        'spells' => 'array',
        'advancements' => 'array',
        'decree_states' => 'array',
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
