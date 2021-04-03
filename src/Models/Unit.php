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
 * @property bool $need_boat
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\UnitPerkType[] $perks
 * @property-read \OpenDominion\Models\Race $race
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Unit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Unit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Unit query()
 * @mixin \Eloquent
 */
class Unit extends AbstractModel
{
    protected $casts = [
        'slot' => 'integer',
        'cost_gold' => 'integer',
        'cost_ore' => 'integer',
        'power_offense' => 'float',
        'power_defense' => 'float',
        'need_boat' => 'boolean',
        'type' => 'array',
        'cost_food' => 'integer',
        'cost_mana' => 'integer',
        'cost_gem' => 'integer',
        'cost_lumber' => 'integer',
        'cost_prestige' => 'integer',
        'cost_boat' => 'integer',
        'cost_champion' => 'integer',
        'cost_soul' => 'integer',
        'cost_blood' => 'integer',
        'cost_unit1' => 'integer',
        'cost_unit2' => 'integer',
        'cost_unit3' => 'integer',
        'cost_unit4' => 'integer',
        'cost_morale' => 'integer',
        'cost_peasant' => 'integer',
        'cost_wild_yeti' => 'integer',
        'cost_spy' => 'integer',
        'cost_wizard' => 'integer',
        'cost_archmage' => 'integer',
        'static_networth' => 'integer',
    ];

    public function perks()
    {
        return $this->belongsToMany(
            UnitPerkType::class,
            'unit_perks',
            'unit_id',
            'unit_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value');
    }

    public function race()
    {
        return $this->hasOne(Race::class);
    }

    public function getPerkValue(string $key)
    {
        $perks = $this->perks->filter(static function (UnitPerkType $unitPerkType) use ($key) {
            return ($unitPerkType->key === $key);
        });

        if ($perks->isEmpty()) {
            return 0; // todo: change to null instead, also add return type and docblock(s)
        }

        return $perks->first()->pivot->value;
    }
}
