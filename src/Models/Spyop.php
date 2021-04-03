<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Spyop
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property array $prerequisites
 * @property int $costMultiplier
 * @property int $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\SpyopPerkType[] $perks
 */
class Spyop extends AbstractModel
{
    protected $table = 'spyops';

    protected $casts = [
        'excluded_races' => 'array',
        'exclusive_races' => 'array',
        'scope' => 'string',
        'enabled' => 'integer',
        'wizard_strength' => 'integer',
    ];

    public function perks()
    {
        return $this->belongsToMany(
            SpyopPerkType::class,
            'spyop_perks',
            'spyop_id',
            'spyop_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value');
    }

    public function getPerkValue(string $key)
    {
        $perks = $this->perks->filter(static function (SpyopPerkType $spyopPerkType) use ($key) {
            return ($spyopPerkType->key === $key);
        });

        if ($perks->isEmpty()) {
            return 0; // todo: change to null instead, also add return type and docblock(s)
        }

        return $perks->first()->pivot->value;
    }

    public function getSpyopPerkValues(string $spyopKey, $spyopPerkTypes, $default = 0)
    {
        if (!is_array($spyopPerkTypes))
        {
            $spyopPerkTypes = [$spyopPerkTypes];
        }

        $spyopCollection = $this->where('key', $spyopKey);
        #if ($spyopCollection->isEmpty())
        #{
        #    return $default;
        #}

        $perkCollection = $spyopCollection->first()->perks->whereIn('key', $spyopPerkTypes);
        #if ($perkCollection->isEmpty())
        #{
        #    return $default;
        #}

        $perkValue = $perkCollection->first()->pivot->value;
        if (str_contains($perkValue, ','))
        {
            $perkValue = explode(',', $perkValue);

            foreach($perkValue as $key => $value)
            {
                if (!str_contains($value, ';'))
                {
                    continue;
                }

                $perkValue[$key] = explode(';', $value);
            }
        }

        return $perkValue;
    }


}
