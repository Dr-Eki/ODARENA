<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Unit
 *
 * @property int $id
 * @property int $race_id
 * @property int $slot
 * @property string $name
 * @property int $cost_gold
 * @property int $cost_ore
 * @property float $power_offense
 * @property float $power_defense
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\UnitPerkType[] $perks
 * @property-read \OpenDominion\Models\Race $race
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Unit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Unit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Unit query()
 * @mixin \Eloquent
 */
class DecreeState extends AbstractModel
{
    protected $casts = [
        'key' => 'text',
        'enabled' => 'integer',
    ];

    public function perks()
    {
        return $this->belongsToMany(
            DecreeStatePerkType::class,
            'decree_state_perks',
            'decree_state_id',
            'decree_state_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value')
            ->orderBy('decree_state_perk_types.key');
    }

    public function decree()
    {
        return $this->hasOne(Decree::class);
    }

    public function getPerkValue(string $key)
    {
        $perks = $this->perks->filter(static function (DecreeStatePerkType $decreeStatePerkType) use ($key) {
            return ($decreeStatePerkType->key === $key);
        });

        if ($perks->isEmpty()) {
            return 0; // todo: change to null instead, also add return type and docblock(s)
        }

        return $perks->first()->pivot->value;
    }
}
