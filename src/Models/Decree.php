<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Decree
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property array $prerequisites
 * @property int $level
 * @property int $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\DecreePerkType[] $perks
 */
class Decree extends AbstractModel
{
    protected $table = 'decrees';

    protected $casts = [
        'key' => 'text',
        'enabled' => 'integer',
        'cooldown' => 'integer',
        'default' => 'string',
        'excluded_races' => 'array',
        'exclusive_races' => 'array',
    ];

    public function states()
    {
        return $this->hasMany(DecreeState::class)
            ->where('enabled',1)
            ->orderBy('name');
    }

    public function deity()
    {
        return $this->belongsTo(Deity::class);
    }

    public function title()
    {
        return $this->belongsTo(Title::class);
    }
}
