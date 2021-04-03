<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Models\Race;
use OpenDominion\Models\Spyop;

class EspionageHelper
{
    public function getOperationInfo(string $operationKey): array
    {
        return $this->getOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->first();
    }

    public function isInfoGatheringOperation(string $operationKey): bool
    {
        return $this->getInfoGatheringOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->isNotEmpty();
    }

    public function isResourceTheftOperation(string $operationKey): bool
    {
        return $this->getResourceTheftOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->isNotEmpty();
    }

    public function isHostileOperation(string $operationKey): bool
    {
        return $this->getHostileOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->isNotEmpty();
    }

    public function isBlackOperation(string $operationKey): bool
    {
        return $this->getBlackOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->isNotEmpty();
    }

    public function getOperations(): Collection
    {
        return $this->getInfoGatheringOperations()
            ->merge($this->getResourceTheftOperations())
            ->merge($this->getBlackOperations());
    }

    public function getInfoGatheringOperations(): Collection
    {
        return collect([
            [
                'name' => 'Barracks Spy',
                'description' => 'Reveal estimate units',
                'key' => 'barracks_spy',
            ],
            [
                'name' => 'Castle Spy',
                'description' => 'Reveal castle improvements',
                'key' => 'castle_spy',
            ],
            [
                'name' => 'Survey Dominion',
                'description' => 'Reveal buildings',
                'key' => 'survey_dominion',
            ],
            [
                'name' => 'Land Spy',
                'description' => 'Reveal land',
                'key' => 'land_spy',
            ],
        ]);
    }

    public function getResourceTheftOperations(): Collection
    {
        return collect([
            [
                'name' => 'Steal Gold',
                'description' => 'Steal gold from target',
                'key' => 'steal_gold',
            ],
            [
                'name' => 'Steal Food',
                'description' => 'Steal food from target',
                'key' => 'steal_food',
            ],
            [
                'name' => 'Steal Lumber',
                'description' => 'Steal lumber from target',
                'key' => 'steal_lumber',
            ],
            [
                'name' => 'Steal Mana',
                'description' => 'Steal mana from target',
                'key' => 'steal_mana',
            ],
            [
                'name' => 'Steal Ore',
                'description' => 'Steal ore from target',
                'key' => 'steal_ore',
            ],
            [
                'name' => 'Steal Gems',
                'description' => 'Steal gems from target',
                'key' => 'steal_gems',
            ],
            [
                'name' => 'Abduct Draftees',
                'description' => 'Abduct draftees from target',
                'key' => 'abduct_draftees',
            ],
            [
                'name' => 'Abduct Peasants',
                'description' => 'Abduct peasants from target',
                'key' => 'abduct_peasants',
            ],
        ]);
    }

    public function getHostileOperations(): Collection
    {
        return $this->getBlackOperations();
    }

    public function getBlackOperations(): Collection
    {
        return collect([
            [
                'name' => 'Assassinate Draftees',
                'description' => 'Kills untrained draftees',
                'key' => 'assassinate_draftees',
                'decreases' => ['military_draftees'],
                'percentage' => 2,
            ],
            [
                'name' => 'Assassinate Wizards',
                'description' => 'Kills wizards (2% base damage).',
                'key' => 'assassinate_wizards',
                'decreases' => ['military_wizards'],
                'percentage' => 2,
            ],
            [
                'name' => 'Magic Snare',
                'description' => 'Reduces wizard strength (2% base damage).',
                'key' => 'magic_snare',
                'decreases' => ['wizard_strength'],
                'percentage' => 2,
            ],
            [
                'name' => 'Sabotage Boats',
                'description' => 'Destroys boats (2% base damage).',
                'key' => 'sabotage_boats',
                'decreases' => ['resource_boats'],
                'percentage' => 2,
            ],
        ]);
    }

    # ROUND 37


    public function getSpyopScope(Spyop $spyop)
    {
        $scopes = [
            'self'      => 'Self',
            'friendly'  => 'Friendly',
            'hostile'   => 'Hostile'
        ];

        return $scopes[$$spyop->scope];
    }

    public function getSpyopEffectsString(Spyop $spyop): array
    {

        $effectStrings = [];

        $spyopEffects = [
            // Info
            'barracks_spy' => 'Reveal units at home, in training, and returning',
            'castle_spy' => 'Reveal improvements',
            'land_spy' => 'Reveal current and incoming lands',
            'survey_dominion' => 'Reveal current and incoming buildings',

            // Theft
            'resource_theft' => 'Steal %1$s (base %2$s%%, max %3$s per spy).',
            'abduct_draftees' => 'Abduct draftees (base %1$s%%, max %2$s per spy).',
            'abduct_peasants' => 'Abduct peasants (base %1$s%%, max %2$s per spy).',
            'seize_boats' => 'Seize boats (base %1$s%%, max %2$s per spy).',

            // Hostile
            'kill_draftees' => 'Assassinate draftees (base %s%% killed).',
            'kill_wizards' => 'Assassinate wizards (base %s%% killed).',
            'reduce_wizard_strength' => 'Reduce wizard strength (base %s%% killed).',
            'sabotage_boats' => 'Sink boats (base %s%% sunk).',
            'sabotage_buildings' => 'Sabotage buildings under construction (base %1$sx[Your SPA]).',
            'destroy_buildings' => 'Destroy  buildings (base %1$sx[Your SPA]).',

            'sabotage_improvement' => 'Sabotage %1$s improvements (base %2$s%%).',

            'slaughter_draftees' => 'Kill %1$s%% of the target\'s draftees and convert each one to %2$s halms of food.',
            'slaughter_peasants' => 'Kill %1$s%% of the target\'s peasants and convert each one to %2$s halms of food.',

            'butcher_peasants' => 'Kill %1$s%% of the target\'s peasants and convert each one to %2$s soul, %3$s blood, and %4$s food.',
            'butcher_draftees' => 'Kill %1$s%% of the target\'s peasants and convert each one to %2$s soul, %3$s blood, and %4$s food.',
            'butcher_wizards' => 'Kill %1$s%% of the target\'s peasants and convert each one to %2$s soul, %3$s blood, and %4$s food.',
        ];

        foreach ($spyop->perks as $perk)
        {
            if (!array_key_exists($perk->key, $spyopEffects))
            {
                //\Debugbar::warning("Missing perk help text for unit perk '{$perk->key}'' on unit '{$unit->name}''.");
                continue;
            }

            $perkValue = $perk->pivot->value;

            // Handle array-based perks
            $nestedArrays = false;

            // todo: refactor all of this
            // partially copied from Race::getUnitPerkValueForUnitSlot
            if (str_contains($perkValue, ','))
            {
                $perkValue = explode(',', $perkValue);

                foreach ($perkValue as $key => $value)
                {
                    if (!str_contains($value, ';'))
                    {
                        continue;
                    }

                    $nestedArrays = true;
                    $perkValue[$key] = explode(';', $value);
                }
            }

            // Special case for pairings
            if ($perk->key === 'defense_from_pairing' || $perk->key === 'offense_from_pairing' || $perk->key === 'pairing_limit')
            {
                $slot = (int)$perkValue[0];
                $pairedUnit = $race->units->filter(static function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $perkValue[0] = $pairedUnit->name;
                if (isset($perkValue[2]) && $perkValue[2] > 0)
                {
                    $perkValue[0] = str_plural($perkValue[0]);
                }
                else
                {
                    $perkValue[2] = 1;
                }
            }

            // Special case for returns faster if pairings
            if ($perk->key === 'faster_return_if_paired')
            {
                $slot = (int)$perkValue[0];
                $pairedUnit = $race->units->filter(static function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $perkValue[0] = $pairedUnit->name;
                if (isset($perkValue[2]) && $perkValue[2] > 0)
                {
                    $perkValue[0] = str_plural($perkValue[0]);
                }
                else
                {
                    $perkValue[2] = 1;
                }
            }

            // Special case for pairing_limit_increasable
            if ($perk->key === 'pairing_limit_increasable')
            {
                $slot = (int)$perkValue[0];
                $pairedUnit = $race->units->filter(static function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $perkValue[0] = $pairedUnit->name;
            }

            // Special case for conversions
            if ($perk->key === 'conversion' or $perk->key === 'displaced_peasants_conversion' or $perk->key === 'casualties_conversion')
            {
                $unitSlotsToConvertTo = array_map('intval', str_split($perkValue));
                $unitNamesToConvertTo = [];

                foreach ($unitSlotsToConvertTo as $slot) {
                    $unitToConvertTo = $race->units->filter(static function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();

                    $unitNamesToConvertTo[] = str_plural($unitToConvertTo->name);
                }

                $perkValue = generate_sentence_from_array($unitNamesToConvertTo);
            }
            if($perk->key === 'staggered_conversion')
            {
                foreach ($perkValue as $index => $conversion) {
                    [$convertAboveLandRatio, $slots] = $conversion;

                    $unitSlotsToConvertTo = array_map('intval', str_split($slots));
                    $unitNamesToConvertTo = [];

                    foreach ($unitSlotsToConvertTo as $slot) {
                        $unitToConvertTo = $race->units->filter(static function ($unit) use ($slot) {
                            return ($unit->slot === $slot);
                        })->first();

                        $unitNamesToConvertTo[] = str_plural($unitToConvertTo->name);
                    }

                    $perkValue[$index][1] = generate_sentence_from_array($unitNamesToConvertTo);
                }
            }
            if($perk->key === 'strength_conversion')
            {
                $limit = (float)$perkValue[0];
                $under = (int)$perkValue[1];
                $over = (int)$perkValue[2];

                $underLimitUnit = $race->units->filter(static function ($unit) use ($under)
                    {
                        return ($unit->slot === $under);
                    })->first();

                $overLimitUnit = $race->units->filter(static function ($unit) use ($over)
                    {
                        return ($unit->slot === $over);
                    })->first();

                $perkValue = [$limit, str_plural($underLimitUnit->name), str_plural($overLimitUnit->name)];
            }
            if($perk->key === 'passive_conversion')
            {
                $slotFrom = (int)$perkValue[0];
                $slotTo = (int)$perkValue[1];
                $rate = (float)$perkValue[2];
                $building = (string)$perkValue[3];

                $unitFrom = $race->units->filter(static function ($unit) use ($slotFrom)
                    {
                        return ($unit->slot === $slotFrom);
                    })->first();

                $unitTo = $race->units->filter(static function ($unit) use ($slotTo)
                    {
                        return ($unit->slot === $slotTo);
                    })->first();

                $perkValue = [$unitFrom->name, $unitTo->name, $rate, $building];
            }
            if($perk->key === 'value_conversion')
            {
                $multiplier = (float)$perkValue[0];
                $convertToSlot = (int)$perkValue[1];

                $unitToConvertTo = $race->units->filter(static function ($unit) use ($convertToSlot)
                    {
                        return ($unit->slot === $convertToSlot);
                    })->first();

                $perkValue = [$multiplier, str_plural($unitToConvertTo->name)];
            }

            if($perk->key === 'plunders')
            {
                foreach ($perkValue as $index => $plunder) {
                    [$resource, $amount] = $plunder;

                    $perkValue[$index][1] = generate_sentence_from_array([$amount]);
                }
            }

            // Special case for dies_into, wins_into ("change_into"), fends_off_into
            if ($perk->key === 'dies_into' or $perk->key === 'wins_into' or $perk->key === 'fends_off_into')
            {
                $unitSlotsToConvertTo = array_map('intval', str_split($perkValue));
                $unitNamesToConvertTo = [];

                foreach ($unitSlotsToConvertTo as $slot) {
                    $unitToConvertTo = $race->units->filter(static function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();

                    $unitNamesToConvertTo[] = $unitToConvertTo->name;
                }

                $perkValue = generate_sentence_from_array($unitNamesToConvertTo);
            }

            // Special case for returns faster if pairings
            if ($perk->key === 'dies_into_multiple')
            {
                $slot = (int)$perkValue[0];
                $pairedUnit = $race->units->filter(static function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $amount = (int)$perkValue[1];

                $perkValue[0] = $pairedUnit->name;
                if (isset($perkValue[1]) && $perkValue[1] > 0)
                {
                    $perkValue[0] = str_plural($perkValue[0]);
                }
                else
                {
                    $perkValue[1] = 1;
                }
            }

            // Special case for unit_production
            if ($perk->key === 'unit_production')
            {
                $unitSlotToProduce = intval($perkValue[0]);

                $unitToProduce = $race->units->filter(static function ($unit) use ($unitSlotToProduce) {
                    return ($unit->slot === $unitSlotToProduce);
                })->first();

                $unitNameToProduce[] = str_plural($unitToProduce->name);

                $perkValue = generate_sentence_from_array($unitNameToProduce);
            }

            if (is_array($perkValue))
            {
                if ($nestedArrays)
                {
                    foreach ($perkValue as $nestedKey => $nestedValue)
                    {
                        foreach($nestedValue as $key => $value)
                        {
                            $nestedValue[$key] = ucwords(str_replace('level','level ',str_replace('_', ' ',$value)));
                        }
                        $effectStrings[] = vsprintf($spyopEffects[$perk->key], $nestedValue);
                    }
                }
                else
                {
                    #var_dump($perkValue);
                    foreach($perkValue as $key => $value)
                    {
                        $perkValue[$key] = ucwords(str_replace('_', ' ',$value));
                    }
                    $effectStrings[] = vsprintf($spyopEffects[$perk->key], $perkValue);
                }
            }
            else
            {
                $perkValue = str_replace('_', ' ',ucwords($perkValue));
                $effectStrings[] = sprintf($spyopEffects[$perk->key], $perkValue);
            }
        }

        return $effectStrings;
    }

    public function getExclusivityString(Spyop $spyop): string
    {

        $exclusivityString = '<br><small class="text-muted"><em>';

        if($exclusives = count($spyop->exclusive_races))
        {
            foreach($spyop->exclusive_races as $raceName)
            {
                $exclusivityString .= $raceName;
                if($exclusives > 1)
                {
                    $exclusivityString .= ', ';
                }
                $exclusives--;
            }

            $exclusivityString .= ' only';
        }
        elseif($excludes = count($spyop->excluded_races))
        {
            $exclusivityString .= 'All factions except ';
            foreach($spyop->excluded_races as $raceName)
            {
                $exclusivityString .= $raceName;
                if($excludes > 1)
                {
                    $exclusivityString .= ', ';
                }
                $excludes--;
            }
        }
        else
        {
            $exclusivityString .= 'All factions';
        }

        $exclusivityString .= '</em></small>';

        return $exclusivityString;

    }


}
