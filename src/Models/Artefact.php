<?php

namespace OpenDominion\Models;

use Illuminate\Support\Str;

/**
 * OpenDominion\Models\Artefact
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property array $prerequisites
 * @property int $costMultiplier
 * @property int $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\ArtefactPerkType[] $perks
 */
class Artefact extends AbstractModel
{
    protected $table = 'artefacts';

    protected $casts = [
        'excluded_races' => 'array',
        'exclusive_races' => 'array',
        'deity_key' => 'string',
        'base_power' => 'integer',
        'enabled' => 'integer',
    ];

    public function perks()
    {
        return $this->belongsToMany(
            ArtefactPerkType::class,
            'artefact_perks',
            'artefact_id',
            'artefact_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value');
    }

    public function deity()
    {
        return $this->belongsTo(Deity::class);
    }

    public function getPerkValue(string $key)
    {
        $perks = $this->perks->filter(static function (ArtefactPerkType $artefactPerkType) use ($key) {
            return ($artefactPerkType->key === $key);
        });

        if ($perks->isEmpty()) {
            return 0;
        }

        return $perks->first()->pivot->value;
    }

    public function getActiveArtefactPerkValues(string $artefactKey, $artefactPerkTypes, $default = 0)
    {
        if (!is_array($artefactPerkTypes))
        {
            $artefactPerkTypes = [$artefactPerkTypes];
        }

        $artefactCollection = $this->where('key', $artefactKey);

        $perkCollection = $artefactCollection->first()->perks->whereIn('key', $artefactPerkTypes);

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
