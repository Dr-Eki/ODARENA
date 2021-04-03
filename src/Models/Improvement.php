<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Improvement
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property array $prerequisites
 * @property int $costMultiplier
 * @property int $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\ImprovementPerkType[] $perks
 */
class Improvement extends AbstractModel
{
    protected $table = 'improvements';

    protected $casts = [
        'excluded_races' => 'array',
        'exclusive_races' => 'array',
        'coefficient' => 'string',
        'enabled' => 'integer',
        'wizard_strength' => 'integer',
    ];

    public function perks()
    {
        return $this->belongsToMany(
            ImprovementPerkType::class,
            'improvement_perks',
            'improvement_id',
            'improvement_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value');
    }

    public function getPerkValue(string $key)
    {
        $perks = $this->perks->filter(static function (ImprovementPerkType $improvementPerkType) use ($key) {
            return ($improvementPerkType->key === $key);
        });

        if ($perks->isEmpty()) {
            return 0;
        }

        return $perks->first()->pivot->value;
    }

    public function getImprovementPerkValues(string $improvementKey, $improvementPerkTypes, $default = 0)
    {
        if (!is_array($improvementPerkTypes))
        {
            $improvementPerkTypes = [$improvementPerkTypes];
        }

        $improvementCollection = $this->where('key', $improvementKey);

        $perkCollection = $improvementCollection->first()->perks->whereIn('key', $improvementPerkTypes);

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
