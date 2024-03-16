<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Deity
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property array $prerequisites
 * @property int $costMultiplier
 * @property int $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\DeityPerkType[] $perks
 */
class Deity extends AbstractModel
{
    protected $table = 'deities';

    protected $casts = [
        'excluded_races' => 'array',
        'exclusive_races' => 'array',
        'scope' => 'string',
        'class' => 'string',
        'cost' => 'float',
        'duration' => 'integer',
        'cooldown' => 'integer',
        'enabled' => 'integer',
        'wizard_strength' => 'integer',
    ];

    public function perks()
    {
        return $this->belongsToMany(
            DeityPerkType::class,
            'deity_perks',
            'deity_id',
            'deity_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value');
    }

    public function getPerkValue(string $key)
    {
        $perks = $this->perks->filter(static function (DeityPerkType $deityPerkType) use ($key) {
            return ($deityPerkType->key === $key);
        });

        if ($perks->isEmpty()) {
            return 0;
        }

        return $perks->first()->pivot->value;
    }

    public function getActiveDeityPerkValues(string $deityKey, $deityPerkTypes, $default = 0)
    {
        if (!is_array($deityPerkTypes))
        {
            $deityPerkTypes = [$deityPerkTypes];
        }

        $deityCollection = $this->where('key', $deityKey);

        $perkCollection = $deityCollection->first()->perks->whereIn('key', $deityPerkTypes);

        $perkValue = $perkCollection->first()->pivot->value;
        if (Str::contains($perkValue, ','))
        {
            $perkValue = explode(',', $perkValue);

            foreach($perkValue as $key => $value)
            {
                if (!Str::contains($value, ';'))
                {
                    continue;
                }

                $perkValue[$key] = explode(';', $value);
            }
        }

        return $perkValue;
    }

}
