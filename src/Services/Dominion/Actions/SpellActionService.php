<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Exception;
use LogicException;
use Illuminate\Support\Carbon;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SorceryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\OpsHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\InfoOp;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;

use OpenDominion\Models\Resource;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Tech;
use OpenDominion\Calculators\Dominion\SpellDamageCalculator;

class SpellActionService
{
    use DominionGuardsTrait;

    /**
     * SpellActionService constructor.
     */
    public function __construct()
    {
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->landHelper = app(LandHelper::class);
        $this->opsHelper = app(OpsHelper::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
        $this->raceHelper = app(RaceHelper::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->resourceService = app(ResourceService::class);
        $this->sorceryCalculator = app(SorceryCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->spellHelper = app(SpellHelper::class);
        $this->spellDamageCalculator = app(SpellDamageCalculator::class);
        $this->statsService = app(StatsService::class);
    }

    /**
     * Casts a magic spell for a dominion, optionally aimed at another dominion.
     *
     * @param Dominion $dominion
     * @param string $spellKey
     * @param null|Dominion $target
     * @return array
     * @throws GameException
     * @throws LogicException
     */
    public function castSpell(Dominion $dominion, string $spellKey, Dominion $target = null, bool $isInvasionSpell = false): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        if ($target !== null)
        {
            $this->guardLockedDominion($target);
            $this->guardActionsDuringTick($target);
        }

        if(!$dominion->round->getSetting('spells'))
        {
            throw new GameException('Spells are disabled this round.');
        }
        
        // Qur: Statis
        if(isset($target) and $this->spellCalculator->getPassiveSpellPerkValue($target, 'stasis'))
        {
            throw new GameException('A magical stasis surrounds the Qurrian lands, making it impossible for your wizards to cast spells on them.');
        }
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot cast spells while you are in stasis.');
        }
        if($spellKey === 'stasis' and $dominion->protection_ticks !== 0)
        {
            throw new GameException('You cannot enter stasis while you are under protection.');
        }

        $spell = Spell::where('key', $spellKey)->first();

        if (!$spell)
        {
            throw new LogicException("Cannot cast unknown spell '{$spellKey}'");
        }

        if ($spell->enabled !== 1)
        {
            throw new LogicException("Spell {$spell->name} is not enabled.");
        }

        $wizardStrengthCost = $this->spellCalculator->getWizardStrengthCost($spell);

        if ($dominion->wizard_strength <= 0 or ($dominion->wizard_strength - $wizardStrengthCost) < 0)
        {
            throw new GameException("Your wizards to not have enough strength to cast {$spell->name}. You need {$wizardStrengthCost}% wizard strength to cast this spell.");
        }

        $manaCost = $this->spellCalculator->getManaCost($dominion, $spell->key);

        if ($this->resourceCalculator->getAmount($dominion, 'mana') < $manaCost)
        {
            throw new GameException("You do not have enough mana to cast {$spell->name}. You need {$manaCost} mana to cast this spell.");
        }

        if (!$this->spellCalculator->canCastSpell($dominion, $spell, $this->resourceCalculator->getAmount($dominion, 'mana')))
        {
            throw new GameException("You are not able to cast {$spell->name}.");
        }

        if ($spell->scope == 'hostile' and $spell->class !== 'invasion')
        {
            throw new GameException('You cannot cast that spell this way. [1]');
        }

        $result = null;

        DB::transaction(function () use ($dominion, $manaCost, $spell, &$result, $target, $wizardStrengthCost)
        {

            #$spell = Spell::where('key', $spellKey)->first();

            if ($spell->class == 'active')
            {
                $result = $this->castActiveSpell($dominion, $target, $spell, $wizardStrengthCost);
            }
            elseif ($spell->class == 'passive')
            {
                $result = $this->castPassiveSpell($dominion, $target, $spell, $wizardStrengthCost);
            }
            elseif ($spell->class == 'invasion')
            {
                $this->castInvasionSpell($dominion, $target, $spell, $wizardStrengthCost);
            }

            $this->statsService->updateStat($dominion, 'mana_cast', $manaCost);

            if($spell->class !== 'invasion')
            {
                $this->resourceService->updateResources($dominion, ['mana' => $manaCost*-1]);

                $wizardStrengthCost = min($wizardStrengthCost, $dominion->wizard_strength);
                $dominion->wizard_strength -= $wizardStrengthCost;

                # XP Gained.
                if($result['success'] == True and isset($result['damage']))
                {
                    $xpGained = $this->calculateXpGain($dominion, $target, $result['damage']);
                    $dominion->xp += $xpGained;
                }
            }

            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                'action' => $spell->key
            ]);
        });

        if($spell->class !== 'invasion')
        {
            return [
                    'message' => $result['message'],
                    'data' => [
                        'spell' => $spell->key,
                        'manaCost' => $manaCost,
                    ],
                    'redirect' =>
                            ($spell->class == 'info') && $result['success']
                            ? $result['redirect']
                            : null,
                ] + $result;
        }
        else
        {
            return [];
        }
    }

    protected function castPassiveSpell(Dominion $caster, Dominion $target = null, Spell $spell): array
    {

        if ($spell->scope == 'hostile')
        {
            throw new GameException('You cannot cast that spell this way. [2]');
        }

        $cooldown = isset($spell->cooldown) ? $spell->cooldown : 0;

        # Self-spells self auras
        if($spell->scope == 'self')
        {
            $this->statsService->updateStat($caster, 'magic_self_success', 1);

            if ($this->spellCalculator->isSpellActive($caster, $spell->key) or $this->spellCalculator->isSpellCooldownRecentlyReset($caster, $spell->key))
            {
                if($this->spellCalculator->getSpellDuration($caster, $spell->key) == $spell->duration)
                {
                    throw new GameException("{$spell->name} is already at maximum duration.");
                }

                DB::transaction(function () use ($caster, $spell, $cooldown)
                {
                    $dominionSpell = DominionSpell::where('dominion_id', $caster->id)->where('spell_id', $spell->id)
                    ->update(['duration' => $spell->duration, 'cooldown' => $cooldown]);

                    $caster->save([
                        'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                        'action' => $spell->key
                    ]);
                });
            }
            else
            {
                DB::transaction(function () use ($caster, $target, $spell, $cooldown)
                {
                    DominionSpell::create([
                        'dominion_id' => $caster->id,
                        'caster_id' => $caster->id,
                        'spell_id' => $spell->id,
                        'duration' => $spell->duration,
                        'cooldown' => $cooldown
                    ]);

                    $caster->save([
                        'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                        'action' => $spell->key
                    ]);
                });
            }

            return [
                'success' => true,
                'message' => sprintf(
                    'Your wizards cast %s successfully, and it will continue to affect your dominion for the next %s ticks.',
                    $spell->name,
                    $spell->duration
                )
            ];
        }
        # Friendly spells
        elseif($spell->scope == 'friendly')
        {
            $this->statsService->updateStat($caster, 'magic_friendly_success', 1);
            if ($this->spellCalculator->isSpellActive($target, $spell->key))
            {
                if($this->spellCalculator->getSpellDuration($target, $spell->key) == $spell->duration)
                {
                    throw new GameException("{$spell->name} is already at maximum duration.");
                }

                DB::transaction(function () use ($caster, $target, $spell)
                {
                    $dominionSpell = DominionSpell::where('dominion_id', $target->id)->where('spell_id', $spell->id)
                    ->update(['duration' => $spell->duration]);

                    $target->save([
                        'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                        'action' => $spell->key
                    ]);
                });
            }
            else
            {
                DB::transaction(function () use ($caster, $target, $spell)
                {
                    DominionSpell::create([
                        'dominion_id' => $target->id,
                        'caster_id' => $caster->id,
                        'spell_id' => $spell->id,
                        'duration' => $spell->duration
                    ]);

                    $caster->save([
                        'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                        'action' => $spell->key
                    ]);
                });
            }

            $this->notificationService
                ->queueNotification('received_friendly_spell', [
                    'sourceDominionId' => $caster->id,
                    'spellKey' => $spell->key
                ])
                ->sendNotifications($target, 'irregular_dominion');

            return [
                'success' => true,
                'message' => sprintf(
                    'Your wizards cast %s successfully, and it will continue to affect ' . $target->name . ' for the next %s ticks.',
                    $spell->name,
                    $spell->duration
                )
            ];
        }
    }

    protected function castActiveSpell(Dominion $caster, ?Dominion $target = null, Spell $spell, int $wizardStrengthCost): array
    {

        if ($spell->scope == 'hostile')
        {
            throw new GameException('You cannot cast that spell this way. [3]');
        }

        # Self-spells self impact spells
        if($spell->scope == 'self')
        {
            $target = $caster;
            $this->statsService->updateStat($caster, 'magic_self_success', 1);
            $extraLine = '';

            foreach($spell->perks as $perk)
            {
                $spellPerkValues = $spell->getActiveSpellPerkValues($spell->key, $perk->key);

                # Unit conversion
                if($perk->key === ($caster->race->key . '_unit_conversion'))
                {
                    $fromSlot = (int)$spellPerkValues[0];
                    $toSlot = (int)$spellPerkValues[1];
                    $amount = (float)$spellPerkValues[2];

                    $availableFrom = $caster->{'military_unit' . $fromSlot};

                    $newToSlotUnits = (int)min($amount, $availableFrom);

                    $caster->{'military_unit' . $fromSlot} -= $newToSlotUnits;
                    $caster->{'military_unit' . $toSlot} += $newToSlotUnits;

                    $fromUnit = $caster->race->units->filter(static function ($unit) use ($fromSlot)
                        {
                            return ($unit->slot === $fromSlot);
                        })->first();

                    $toUnit = $caster->race->units->filter(static function ($unit) use ($toSlot)
                        {
                            return ($unit->slot === $toSlot);
                        })->first();

                    $extraLine = ', phasing ' . number_format($newToSlotUnits) . ' ' . str_plural($fromUnit->name, $newToSlotUnits) . ' into ' . str_plural($toUnit->name, $newToSlotUnits);
                }
                if($perk->key === ($caster->race->key . '_unit_conversion_ratio'))
                {
                    $fromSlot = (int)$spellPerkValues[0];
                    $toSlot = (int)$spellPerkValues[1];
                    $fromAmount = (float)$spellPerkValues[2];
                    $toAmount = (float)$spellPerkValues[3];

                    $ratio = $toAmount/$fromAmount;

                    $unitsConvertedFrom = min($fromAmount, $caster->{'military_unit' . $fromSlot});

                    $unitsConvertedTo = floor($unitsConvertedFrom * $ratio);

                    $caster->{'military_unit' . $fromSlot} -= $unitsConvertedFrom;
                    $caster->{'military_unit' . $toSlot} += $unitsConvertedTo;

                    $fromUnit = $caster->race->units->filter(static function ($unit) use ($fromSlot)
                        {
                            return ($unit->slot === $fromSlot);
                        })->first();

                    $toUnit = $caster->race->units->filter(static function ($unit) use ($toSlot)
                        {
                            return ($unit->slot === $toSlot);
                        })->first();

                    $extraLine = ', mending ' . number_format($unitsConvertedFrom) . ' ' . str_plural($fromUnit->name, $unitsConvertedFrom) . ' into ' . number_format($unitsConvertedTo) . ' '. str_plural($toUnit->name, $unitsConvertedTo);
                }

                # Resource conversion
                if($perk->key === 'resource_conversion')
                {
                    $sourceResourceKey = $spellPerkValues[0];
                    $targetResourceKey = $spellPerkValues[1];
                    $ratio = $spellPerkValues[2] / 100;
                    $exchangeRate = $spellPerkValues[3];

                    $amountRemoved = ceil($this->resourceCalculator->getAmount($caster, $sourceResourceKey) * $ratio);
                    $amountAdded = floor($amountRemoved / $exchangeRate);

                    $this->resourceService->updateResources($caster, [$sourceResourceKey => $amountRemoved*-1]);
                    $this->resourceService->updateResources($caster, [$targetResourceKey => $amountAdded]);
                }

                # Resource conversion capped
                if($perk->key === 'resource_conversion_capped')
                {
                    $sourceResourceKey = $spellPerkValues[0];
                    $targetResourceKey = $spellPerkValues[1];
                    $ratio = $spellPerkValues[2] / 100;
                    $exchangeRate = $spellPerkValues[3];
                    $sourceMax = $spellPerkValues[4];

                    $amountRemoved = ceil(min($this->resourceCalculator->getAmount($caster, $sourceResourceKey) * $ratio, $sourceMax));
                    $amountAdded = floor($amountRemoved / $exchangeRate);

                    $this->resourceService->updateResources($caster, [$sourceResourceKey => $amountRemoved*-1]);
                    $this->resourceService->updateResources($caster, [$targetResourceKey => $amountAdded]);
                }

                # Resource conversion
                if($perk->key === 'peasant_to_resources_conversion')
                {
                    $ratio = (float)$spellPerkValues[0] / 100;
                    $peasantsSacrificed = $caster->peasants * $ratio;
                    unset($spellPerkValues[0]);

                    $newResources = [];

                    foreach($spellPerkValues as $resourcePair)
                    {
                        $resourceProductionMultiplier = 1;
                        $resourceProductionMultiplier += $caster->getBuildingPerkMultiplier($resourcePair[1] . '_production_from_sacrifice_mod');
                        $resourceProductionMultiplier += $caster->getSpellPerkMultiplier($resourcePair[1] . '_production_from_sacrifice_mod');
                        $resourceProductionMultiplier += $caster->getImprovementPerkMultiplier($resourcePair[1] . '_production_from_sacrifice_mod');
                        $resourceProductionMultiplier += $caster->getLandImprovementPerkMultiplier($resourcePair[1] . '_production_from_sacrifice_mod');

                        $newResources[$resourcePair[1]] = intval($peasantsSacrificed * $resourcePair[0]);
                    }

                    $caster->peasants -= $peasantsSacrificed;

                    $this->resourceService->updateResources($caster, $newResources);
                }

                # Peasants to prestige conversion
                if($perk->key === 'convert_peasants_to_prestige')
                {
                    $peasantsChunk = (int)$spellPerkValues[0];
                    $prestige = (float)$spellPerkValues[1];

                    $peasantsSacrificed = min($caster->peasants - 1000, $peasantsChunk);
                    $prestigeGained = intval(($peasantsSacrificed / $peasantsChunk) * $prestige);

                    $caster->peasants -= $peasantsSacrificed;
                    $caster->prestige += $prestigeGained;

                    $extraLine = ', gaining ' . number_format($prestigeGained) . ' prestige for sacrificing ' . number_format($peasantsSacrificed) . ' peasants.';
                }

                # Summon units
                if($perk->key === 'summon_units_from_land')
                {
                    $unitSlots = (array)$spellPerkValues[0];
                    $maxPerAcre = (float)$spellPerkValues[1];
                    $landType = (string)$spellPerkValues[2];

                    $totalUnitsSummoned = 0;

                    foreach($unitSlots as $slot)
                    {
                        $amountPerAcre = rand(1, $maxPerAcre);
                        $unitsSummoned = floor($amountPerAcre * $caster->{'land_' . $landType});
                        $caster->{'military_unit' . $slot} += $unitsSummoned;
                        $totalUnitsSummoned += $unitsSummoned;
                    }

                    $extraLine = ', summoning ' . number_format($totalUnitsSummoned) . ' new units to our military';
                }

                # Summon units (increased hourly)
                if($perk->key === 'summon_weres_units_from_land_increasing')
                {
                    $unitSlots = (array)$spellPerkValues[0];
                    $basePerAcre = (float)$spellPerkValues[1];
                    $ticklyPercentIncrease = (float)$spellPerkValues[2] / 100;
                    $landType = (string)$spellPerkValues[3];

                    $totalUnitsSummoned = 0;

                    foreach($unitSlots as $slot)
                    {
                        $amountPerAcre = $basePerAcre * (1 + $caster->round->ticks * $ticklyPercentIncrease);
                        $unitsSummoned = floor($amountPerAcre * $caster->{'land_' . $landType});
                        $caster->{'military_unit' . $slot} += $unitsSummoned;
                        $totalUnitsSummoned += $unitsSummoned;
                    }

                    $extraLine = ', summoning ' . number_format($totalUnitsSummoned) . ' new units to our military';
                }

                # Summon units (increased hourly)
                if($perk->key === 'marshling_random_resource_to_units_conversion')
                {

                    $ratioPerWpa = (float)$spellPerkValues[0] / 100;
                    $maxRatio = (float)$spellPerkValues[1] / 100;
                    $resourceKey = (string)$spellPerkValues[2];
                    $unitSlots = (array)$spellPerkValues[3];

                    $resourceRatioTaken = min($this->militaryCalculator->getWizardRatio($caster) * $ratioPerWpa, $maxRatio);
                    $resourceAmountOwned = $this->resourceCalculator->getAmount($caster, $resourceKey);
                    $resourceAmountConverted = floor($resourceAmountOwned * $resourceRatioTaken);

                    $resourceAmountConverted = min($resourceAmountConverted, ($this->populationCalculator->getMaxPopulation($caster) - $this->populationCalculator->getPopulationMilitary($caster)/* - 1000*/));

                    $resource = Resource::where('key', $resourceKey)->firstOrFail();
                    $resourceAmountOwned = $this->resourceService->updateResources($caster, [$resourceKey => ($resourceAmountConverted * -1)]);

                    $unitSlots = (array)$spellPerkValues[2];
                    $newUnitSlots = array_fill(1, $caster->race->units->count(), 0);
                    $randomNumbers = [];

                    for ($randomNumber = 1; $randomNumber <= $caster->race->units->count(); $randomNumber++)
                    {
                        $randomNumbers[$randomNumber] = mt_rand(0, 100);
                    }

                    foreach($newUnitSlots as $slot => $amount)
                    {
                        $ratio = ($randomNumbers[$slot] / array_sum($randomNumbers));
                        #dump('Slot' . $slot . ' ratio: ' . $ratio);
                        $newUnitSlots[$slot] = (int)round($ratio * $resourceAmountConverted);
                    }

                    # Solve for rounding errors.
                    $newUnitSlots[array_search(max($newUnitSlots), $newUnitSlots)] += (int)round($resourceAmountConverted - array_sum($newUnitSlots));

                    foreach($newUnitSlots as $slot => $amountConjured)
                    {
                        $caster->{'military_unit' . $slot} += max(0, $amountConjured);
                        $unit = $target->race->units->filter(function ($unit) use ($slot) {
                            return ($unit->slot == $slot);
                        })->first();

                        $units[] = number_format($amountConjured) . ' ' . str_plural($unit->name, $amountConjured);
                    }

                    $unitsString = generate_sentence_from_array($units);

                    $extraLine = ', turning ' . number_format($resourceAmountConverted) . ' of your ' . str_plural($resource->name) . ' into ' . $unitsString;

                }

                # Increase morale from net victories
                if($perk->key === 'increase_morale_from_net_victories')
                {
                    $moralePerNetVictory = (int)$spellPerkValues[0];

                    $caster->morale += max(0, $moralePerNetVictory * $this->militaryCalculator->getNetVictories($caster));
                }
                

                # Rezone all land
                if($perk->key === 'rezone_all_land')
                {
                    $toLandType = (string)$spellPerkValues[1];
                    $ratio = $spellPerkValues[0] / 100;

                    $acresRezoned = 0;

                    foreach($this->landHelper->getLandTypes() as $landType)
                    {
                        if($landType !== $toLandType)
                        {
                            $toRezone = floor($caster->{'land_' . $landType} * $ratio);
                            $caster->{'land_' . $landType} -= $toRezone;
                            $acresRezoned += $toRezone;
                        }
                    }

                    $caster->{'land_' . $toLandType} += $acresRezoned;
                }

                # Reset spell cooldowns
                if($perk->key === 'reset_spell_cooldowns')
                {
                    $spellsOnCoolDown = DominionSpell::where('cooldown','>',0)->where('caster_id','=', $caster->id)->get();

                    foreach($spellsOnCoolDown as $dominionSpellOnCooldown)
                    {
                        $dominionSpellOnCooldown->update(['cooldown' => 0]);
                    }
                }
            }

            if($spell->cooldown > 0)
            {
                # But has it already been cast and is sitting at zero-tick cooldown?
                if(DominionSpell::where(['dominion_id' => $caster->id, 'spell_id' => $spell->id, 'cooldown' => 0])->get()->count())
                {
                    #dd('this', $active);
                    DB::transaction(function () use ($caster, $target, $spell)
                    {
                      DominionSpell::where('dominion_id', $caster->id)->where('spell_id', $spell->id)
                      ->update(['cooldown' => $spell->cooldown]);
                    });
                }
                else
                {
                    #dd('that');
                    DB::transaction(function () use ($caster, $target, $spell)
                    {
                        DominionSpell::create([
                            'dominion_id' => $caster->id,
                            'caster_id' => $target->id,
                            'spell_id' => $spell->id,
                            'duration' => 0,
                            'cooldown' => $spell->cooldown
                        ]);
                    });
                }
            }


            $caster->save([
                'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                'action' => $spell->key
            ]);

            return [
                'success' => true,
                'message' => sprintf(
                    'Your wizards cast %s successfully%s',
                    $spell->name,
                    $extraLine
                )
            ];
        }
        # Friendly spells, friendly impact spells
        elseif($spell->scope == 'friendly')
        {
            $this->statsService->updateStat($caster, 'magic_friendly_success', 1);

            foreach($spell->perks as $perk)
            {
                $spellPerkValues = $spell->getActiveSpellPerkValues($spell->key, $perk->key);

                # Increase morale
                if($perk->key === 'increase_morale')
                {
                    $moraleAdded = (int)$spellPerkValues;

                    if($target->morale >= 100)
                    {
                          throw new GameException($target->name . ' already has 100% morale.');
                    }
                    else
                    {
                        $target->morale = min(($target->morale + $moraleAdded), 100);
                        $target->save();
                    }

                    $this->notificationService
                        ->queueNotification('received_friendly_spell', [
                            'sourceDominionId' => $caster->id,
                            'spellKey' => $spell->key
                        ])
                        ->sendNotifications($target, 'irregular_dominion');
                }
            }

            if($spell->cooldown > 0)
            {
                DB::transaction(function () use ($caster, $target, $spell)
                {
                    DominionSpell::create([
                        'dominion_id' => $caster->id,
                        'caster_id' => $target->id,
                        'spell_id' => $spell->id,
                        'duration' => 0,
                        'cooldown' => $spell->cooldown
                    ]);

                    $caster->save([
                        'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                        'action' => $spell->key
                    ]);
                });
            }

            return [
                'success' => true,
                'message' => sprintf(
                    'Your wizards successfully cast %s on %s',
                    $spell->name,
                    $target->name
                )
            ];
        }
    }

    protected function castInvasionSpell(Dominion $caster, ?Dominion $target = null, Spell $spell): void
    {
        # Self-spells self auras - Unused
        if($spell->scope == 'self')
        {
            # Is it already active?
            if ($this->spellCalculator->isSpellActive($caster, $spell->key))
            {
                DominionSpell::where('dominion_id', $caster->id)->where('spell_id', $spell->id)
                ->update(['duration' => $spell->duration]);
            }
            else
            {
                DB::transaction(function () use ($caster, $target, $spell)
                {
                    DominionSpell::create([
                        'dominion_id' => $caster->id,
                        'caster_id' => $caster->id,
                        'spell_id' => $spell->id,
                        'duration' => $spell->duration
                    ]);
                });
            }

            $this->notificationService
                ->queueNotification('received_hostile_spell', [
                    'sourceDominionId' => $sourceDominionId,
                    'spellKey' => $spell->key,
                ])
                ->sendNotifications($target, 'irregular_dominion');

        }
        # Hostile aura - Afflicted
        elseif($spell->scope == 'hostile')
        {
            $this->statsService->updateStat($caster, 'magic_invasion_success', 1);
            $this->statsService->updateStat($caster, 'magic_invasion_duration', $spell->duration);
            if ($this->spellCalculator->isSpellActive($target, $spell->key))
            {
                DB::transaction(function () use ($caster, $target, $spell)
                {
                    DominionSpell::where('dominion_id', $target->id)->where('spell_id', $spell->id)
                    ->update(['duration' => $spell->duration, 'caster_id' => $caster->id]);

                    $caster->save([
                        'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                        'action' => $spell->key
                    ]);
                });
            }
            else
            {
                DB::transaction(function () use ($caster, $target, $spell)
                {
                    DominionSpell::create([
                        'dominion_id' => $target->id,
                        'caster_id' => $caster->id,
                        'spell_id' => $spell->id,
                        'duration' => $spell->duration
                    ]);

                    $caster->save([
                        'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                        'action' => $spell->key
                    ]);
                });
            }
            $this->notificationService
                ->queueNotification('received_hostile_spell', [
                    'sourceDominionId' => $caster->id,
                    'spellKey' => $spell->key,
                ])
                ->sendNotifications($target, 'irregular_dominion');
        }
    }

    public function breakSpell(Dominion $breaker, Spell $spell, bool $isLiberation = false): array
    {
        $dominionSpell = DominionSpell::where('spell_id', $spell->id)->where('dominion_id', $breaker->id)->first();

        $caster = Dominion::findOrFail($dominionSpell->caster_id);

        if(!$this->spellCalculator->isSpellActive($breaker, $spell->key))
        {
            throw new GameException($spell->name . ' is not active.');
        }

        if($spell->class == 'invasion' and !$isLiberation)
        {
            throw new GameException($spell->name . ' cannot be broken.');
        }

        if(!$isLiberation)
        {
            $wizardStrengthCost = 5;

            if ($breaker->wizard_strength <= 0 or ($breaker->wizard_strength - $wizardStrengthCost) < 0)
            {
                throw new GameException("Your wizards to not have enough strength to break {$spell->name}. You need {$wizardStrengthCost}% wizard strength to break this spell.");
            }
            else
            {
                $breaker->wizard_strength -= $wizardStrengthCost;
            }

            $manaCost = $this->spellCalculator->getManaCost($breaker, $spell->key) * 2;

            if ($this->resourceCalculator->getAmount($breaker, 'mana') < $manaCost)
            {
                throw new GameException("You do not have enough mana to break {$spell->name}. You need {$manaCost} mana to break this spell.");
            }
            else
            {
                $this->resourceService->updateResources($breaker, ['mana' => $manaCost*-1]);
            }
        }

        $casterWpa = min(10,$this->militaryCalculator->getWizardRatio($caster, 'defense'));
        $breakerWpa = min(10,$this->militaryCalculator->getWizardRatio($breaker, 'offense'));

        if ($casterWpa == 0.0 or $isLiberation or random_chance($this->opsHelper->blackOperationSuccessChance($breakerWpa, $casterWpa)))
        {
              $this->statsService->updateStat($breaker, 'magic_broken', 1);

              DB::transaction(function () use ($breaker, $spell)
              {
                  DominionSpell::where('dominion_id', $breaker->id)
                      ->where('spell_id', $spell->id)
                      ->delete();

                  $breaker->save([
                      'event' => HistoryService::EVENT_ACTION_BREAK_SPELL,
                      'action' => $spell->key
                  ]);
              });

              return [
                  'success' => true,
                  'message' => sprintf(
                      'You successfully break %s and it no longer affects your dominion.',
                      $spell->name)
              ];
        }
        else
        {
            $breaker->save();

            return [
                'success' => false,
                'message' => sprintf(
                    'You fail to break %s.',
                    $spell->name),
                'wizardStrengthCost' => $wizardStrengthCost,
                'alert-type' => 'warning',
            ];
        }
    }

    protected function getReturnMessageString(Dominion $dominion): string
    {
        $wizards = $dominion->military_wizards;
        $archmages = $dominion->military_archmages;
        $spies = $dominion->military_spies;

        if (($wizards === 0) && ($archmages === 0)) {
            return 'You cast %s at a cost of %s mana.';
        }

        if ($wizards === 0) {
            if ($archmages > 1) {
                return 'Your archmages successfully cast %s at a cost of %s mana.';
            }

            $thoughts = [
                'mumbles something about being the most powerful sorceress in the dominion is a lonely job, "but somebody\'s got to do it"',
                'mumbles something about the food being quite delicious',
                'feels like a higher spiritual entity is watching her',
                'winks at you',
            ];

            if ($this->queueService->getTrainingQueueTotalByResource($dominion, 'military_wizards') > 0) {
                $thoughts[] = 'carefully observes the trainee wizards';
            } else {
                $thoughts[] = 'mumbles something about the lack of student wizards to teach';
            }

            if ($this->queueService->getTrainingQueueTotalByResource($dominion, 'military_archmages') > 0) {
                $thoughts[] = 'mumbles something about being a bit sad because she probably won\'t be the single most powerful sorceress in the dominion anymore';
                $thoughts[] = 'mumbles something about looking forward to discuss the secrets of arcane knowledge with her future peers';
            } else {
                $thoughts[] = 'mumbles something about not having enough peers to properly conduct her studies';
                $thoughts[] = 'mumbles something about feeling a bit lonely';
            }

            return ('Your archmage successfully casts %s at a cost of %s mana. In addition, she ' . $thoughts[array_rand($thoughts)] . '.');
        }

        if ($archmages === 0) {
            if ($wizards > 1) {
                return 'Your wizards successfully cast %s at a cost of %s mana.';
            }

            $thoughts = [
                'mumbles something about the food being very tasty',
                'has the feeling that an omnipotent being is watching him',
            ];

            if ($this->queueService->getTrainingQueueTotalByResource($dominion, 'military_wizards') > 0) {
                $thoughts[] = 'mumbles something about being delighted by the new wizard trainees so he won\'t be lonely anymore';
            } else {
                $thoughts[] = 'mumbles something about not having enough peers to properly conduct his studies';
                $thoughts[] = 'mumbles something about feeling a bit lonely';
            }

            if ($this->queueService->getTrainingQueueTotalByResource($dominion, 'military_archmages') > 0) {
                $thoughts[] = 'mumbles something about looking forward to his future teacher';
            } else {
                $thoughts[] = 'mumbles something about not having an archmage master to study under';
            }

            if ($spies === 1) {
                $thoughts[] = 'mumbles something about fancying that spy lady';
            } elseif ($spies > 1) {
                $thoughts[] = 'mumbles something about thinking your spies are complotting against him';
            }

            return ('Your wizard successfully casts %s at a cost of %s mana. In addition, he ' . $thoughts[array_rand($thoughts)] . '.');
        }

        if (($wizards === 1) && ($archmages === 1)) {
            $strings = [
                'Your wizards successfully cast %s at a cost of %s mana.',
                'Your wizard and archmage successfully cast %s together in harmony at a cost of %s mana. It was glorious to behold.',
                'Your wizard watches in awe while his teacher archmage blissfully casts %s at a cost of %s mana.',
                'Your archmage facepalms as she observes her wizard student almost failing to cast %s at a cost of %s mana.',
                'Your wizard successfully casts %s at a cost of %s mana, while his teacher archmage watches him with pride.',
            ];

            return $strings[array_rand($strings)];
        }

        if (($wizards === 1) && ($archmages > 1)) {
            $strings = [
                'Your wizards successfully cast %s at a cost of %s mana.',
                'Your wizard was sleeping, so your archmages successfully cast %s at a cost of %s mana.',
                'Your wizard watches carefully while your archmages successfully cast %s at a cost of %s mana.',
            ];

            return $strings[array_rand($strings)];
        }

        if (($wizards > 1) && ($archmages === 1)) {
            $strings = [
                'Your wizards successfully cast %s at a cost of %s mana.',
                'Your archmage found herself lost in her study books, so your wizards successfully cast %s at a cost of %s mana.',
            ];

            return $strings[array_rand($strings)];
        }

        return 'Your wizards successfully cast %s at a cost of %s mana.';
    }

    protected function calculateXpGain(Dominion $dominion, Dominion $target, int $damage): int
    {
        if($damage === 0 or $damage === NULL)
        {
            return 0;
        }
        else
        {
            $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;
            $base = 30;

            return $base * $landRatio;
        }
    }

  }
