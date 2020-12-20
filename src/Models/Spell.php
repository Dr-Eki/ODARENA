<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Spell
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property array $prerequisites
 * @property int $costMultiplier
 * @property int $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\SpellPerkType[] $perks
 */
class Spell extends AbstractModel
{
    protected $table = 'spells';

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
            SpellPerkType::class,
            'spell_perks',
            'spell_id',
            'spell_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value');
    }

    public function getPerkValue(string $key)
    {
        $perks = $this->perks->filter(static function (SpellPerkType $spellPerkType) use ($key) {
            return ($spellPerkType->key === $key);
        });

        if ($perks->isEmpty()) {
            return 0; // todo: change to null instead, also add return type and docblock(s)
        }

        return $perks->first()->pivot->value;
    }

    /**
     * Try to get a unit perk value with provided key for a specific slot.
     *
     * @param int $slot
     * @param string|string[] $unitPerkTypes
     * @param mixed $default
     * @return int|int[]
     */
    public function getActiveSpellPerkValues(string $spellKey, $spellPerkTypes, $default = 0)
    {
        if (!is_array($spellPerkTypes))
        {
            $spellPerkTypes = [$spellPerkTypes];
        }

        $spellCollection = $this->where('key', $spellKey);
        #if ($spellCollection->isEmpty())
        #{
        #    return $default;
        #}

        $perkCollection = $spellCollection->first()->perks->whereIn('key', $spellPerkTypes);
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
