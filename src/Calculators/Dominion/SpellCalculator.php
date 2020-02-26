<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;


class SpellCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var SpellHelper */
    protected $spellHelper;

    /** @var array */
    protected $activeSpells = [];

    /**
     * SpellCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     * @param SpellHelper $spellHelper
     */
    public function __construct(
          LandCalculator $landCalculator,
          SpellHelper $spellHelper)
    {
        $this->landCalculator = $landCalculator;
        $this->spellHelper = $spellHelper;
    }

    /**
     * Returns the mana cost of a particular spell for $dominion.
     *
     * @param Dominion $dominion
     * @param string $spell
     * @return int
     */
    public function getManaCost(Dominion $dominion, string $spell, bool $isInvasionSpell = false): int
    {
        if($isInvasionSpell)
        {
          return 0;
        }

        $spellInfo = $this->spellHelper->getSpellInfo($spell, $dominion);
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        // Cost reduction from wizard guilds (2x ratio, max 40%)
        $wizardGuildRatio = ($dominion->building_wizard_guild / $totalLand);
        $spellCostMultiplier = (1 - clamp(2 * $wizardGuildRatio, 0, 0.4));
        $spellCostMultiplier += $dominion->getTechPerkMultiplier('spell_cost');

        return round($spellInfo['mana_cost'] * $totalLand * $spellCostMultiplier);
    }

    /**
     * Returns whether $dominion can currently cast spell $type.
     *
     * Spells require mana and enough wizard strength to be cast.
     *
     * @param Dominion $dominion
     * @param string $spell
     * @return bool
     */
    public function canCast(Dominion $dominion, string $spell): bool
    {
        return (
            ($dominion->resource_mana >= $this->getManaCost($dominion, $spell)) &&
            ($dominion->wizard_strength > 0)
        );
    }

    /**
     * Returns whether spell $type for $dominion is on cooldown.
     *
     * @param Dominion $dominion
     * @param string $spell
     * @return bool
     */
    public function isOnCooldown(Dominion $dominion, string $spell, bool $isInvasionSpell = false): bool
    {
        if ($this->getSpellCooldown($dominion, $spell, $isInvasionSpell) > 0)
        {
            return true;
        }
        return false;
    }

    /**
     * Returns the number of hours before spell $type for $dominion can be cast.
     *
     * @param Dominion $dominion
     * @param string $spell
     * @return bool
     */
    public function getSpellCooldown(Dominion $dominion, string $spell, bool $isInvasionSpell = false): int
    {
        $spellInfo = $this->spellHelper->getSpellInfo($spell, $dominion, $isInvasionSpell);

        if (isset($spellInfo['cooldown'])) {
            $spellLastCast = DB::table('dominion_history')
                ->where('dominion_id', $dominion->id)
                ->where('event', 'cast spell')
                ->where('delta', 'like', "%{$spell}%")
                ->orderby('created_at', 'desc')
                ->take(1)
                ->first();
            if ($spellLastCast) {
                $hoursSinceCast = now()->startOfHour()->diffInHours(Carbon::parse($spellLastCast->created_at)->startOfHour());
                if ($hoursSinceCast < $spellInfo['cooldown']) {
                    return $spellInfo['cooldown'] - $hoursSinceCast;
                }
            }
        }

        return 0;
    }

    /**
     * Returns a list of spells currently affecting $dominion.
     *
     * @param Dominion $dominion
     * @param bool $forceRefresh
     * @return Collection
     */
    public function getActiveSpells(Dominion $dominion, bool $forceRefresh = false): Collection
    {
        $cacheKey = $dominion->id;

        if (!$forceRefresh && array_has($this->activeSpells, $cacheKey)) {
            return collect(array_get($this->activeSpells, $cacheKey));
        }

        $data = DB::table('active_spells')
            ->join('dominions', 'dominions.id', '=', 'cast_by_dominion_id')
            ->join('realms', 'realms.id', '=', 'dominions.realm_id')
            ->where('dominion_id', $dominion->id)
            ->where('duration', '>', 0)
            ->orderBy('duration', 'desc')
            ->orderBy('created_at')
            ->get([
                'active_spells.*',
                'dominions.name AS cast_by_dominion_name',
                'realms.number AS cast_by_dominion_realm_number',
            ]);

        array_set($this->activeSpells, $cacheKey, $data->toArray());

        return $data;
    }

    /**
     * Returns whether a particular spell is affecting $dominion right now.
     *
     * @param Dominion $dominion
     * @param string $spell
     * @return bool
     */
    public function isSpellActive(Dominion $dominion, string $spell): bool
    {
        return $this->getActiveSpells($dominion)->contains(function ($value) use ($spell) {
            return ($value->spell === $spell);
        });
    }


    /**
     * Returns the cast of a spell.
     *
     * @param Dominion $dominion
     * @param string $spell
     * @return Dominion
     */
    public function getCaster(Dominion $dominion, string $spell): Dominion
    {
        $spell = $this->getActiveSpells($dominion)->filter(function ($value) use ($spell) {
            return ($value->spell === $spell);
        })->first();

        return Dominion::findOrFail($spell->cast_by_dominion_id);
    }


    /**
     * Returns the remaining duration (in ticks) of a spell affecting $dominion.
     *
     * @todo Rename to getSpellRemainingDuration for clarity
     * @param Dominion $dominion
     * @param string $spell
     * @return int|null
     */
    public function getSpellDuration(Dominion $dominion, string $spell): ?int
    {
        if (!$this->isSpellActive($dominion, $spell)) {
            return null;
        }

        $spell = $this->getActiveSpells($dominion)->filter(function ($value) use ($spell) {
            return ($value->spell === $spell);
        })->first();

        return $spell->duration;
    }


}
