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
use OpenDominion\Models\InfoOp;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;

# ODA
use OpenDominion\Models\Spell;
use OpenDominion\Models\Tech;
use OpenDominion\Calculators\Dominion\SpellDamageCalculator;

class SpellActionService
{
    use DominionGuardsTrait;

    /**
     * @var float Hostile ops base success rate
     */
    protected const HOSTILE_MULTIPLIER_SUCCESS_RATE = 2;

    /**
     * @var float Info op base success rate
     */
    protected const INFO_MULTIPLIER_SUCCESS_RATE = 1.4;

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
        $this->spellCalculator = app(SpellCalculator::class);
        $this->spellHelper = app(SpellHelper::class);
        $this->spellDamageCalculator = app(SpellDamageCalculator::class);
    }

    public const BLACK_OPS_DAYS_AFTER_ROUND_START = 1;

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
    public function castSpell(Dominion $dominion, string $spellKey, ?Dominion $target = null): array
    {
        $this->guardLockedDominion($dominion);
        if ($target !== null) {
            $this->guardLockedDominion($target);
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

        if (!$this->spellCalculator->canCastSpell($dominion, $spell))
        {
            throw new GameException("You are not able to cast {$spell->name}.");
        }

        $wizardStrengthCost = $this->spellCalculator->getWizardStrengthCost($spell);

        if ($dominion->wizard_strength <= 0 or ($dominion->wizard_strength - $wizardStrengthCost) < 0)
        {
            throw new GameException("Your wizards to not have enough strength to cast {$spell->name}. You need {$wizardStrengthCost}% wizard strength to cast this spell.");
        }

        $manaCost = $this->spellCalculator->getManaCost($dominion, $spell->key);

        if ($dominion->resource_mana < $manaCost)
        {
            throw new GameException("You do not have enough mana to cast {$spell->name}. You need {$manaCost} mana to cast this spell.");
        }

        if ($spell->scope == 'hostile')
        {
            if ($target === null) {
                throw new GameException("You must select a target when casting offensive spell {$spell->name}");
            }

            if ($this->protectionService->isUnderProtection($dominion))
            {
                throw new GameException('You cannot cast offensive spells while under protection');
            }

            if ($this->protectionService->isUnderProtection($target))
            {
                throw new GameException('You cannot cast offensive spells on targets which are under protection');
            }

            if (!$this->rangeCalculator->isInRange($dominion, $target) and $spell->class !== 'invasion')
            {
                throw new GameException('You cannot cast offensive spells on targets outside of your range');
            }

            if ($dominion->round->id !== $target->round->id)
            {
                throw new GameException('Nice try, but you cannot cast spells cross-round');
            }
        }

        $result = null;

        DB::transaction(function () use ($dominion, $manaCost, $spell, &$result, $target, $wizardStrengthCost)
        {

            #$spell = Spell::where('key', $spellKey)->first();

            if ($spell->class == 'info')
            {
                $result = $this->castInfoSpell($dominion, $target, $spell, $wizardStrengthCost);
            }
            elseif ($spell->class == 'active')
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

            $dominion->stat_total_mana_cast += $manaCost;

            if($spell->class !== 'invasion')
            {
                $dominion->resource_mana -= $manaCost;

                $wizardStrengthCost = min($wizardStrengthCost, $dominion->wizard_strength);
                $dominion->wizard_strength -= $wizardStrengthCost;

                # XP Gained.
                if($result['success'] == True and isset($result['damage']))
                {
                  $xpGained = $this->calculateXpGain($dominion, $target, $result['damage']);
                  $dominion->resource_tech += $xpGained;
                }

                if ($spell->scope !== 'hostile')
                {
                    $dominion->stat_spell_success += 1;
                }
            }

            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                'action' => $spell->key
            ]);
        });

        if ($target !== null)
        {
            $this->rangeCalculator->checkGuardApplications($dominion, $target);
        }

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

    protected function castPassiveSpell(Dominion $caster, ?Dominion $target = null, Spell $spell, int $wizardStrengthCost): array
    {

        if ($spell->scope == 'hostile' and $caster->round->hasOffensiveActionsDisabled())
        {
            throw new GameException('Hostile spells have been disabled for the rest of the round.');
        }

        if ($spell->scope == 'hostile' and now()->diffInDays($caster->round->start_date) < self::BLACK_OPS_DAYS_AFTER_ROUND_START and !$isInvasionSpell)
        {
            throw new GameException('You cannot cast hostile spells during the first day of the round.');
        }

        # Self-spells self auras
        if($spell->scope == 'self')
        {
            if ($this->spellCalculator->isSpellActive($caster, $spell->key))
            {
                if($this->spellCalculator->getSpellDuration($caster, $spell->key) == $spell->duration)
                {
                    throw new GameException("{$spell->name} is already at maximum duration.");
                }

                DB::transaction(function () use ($caster, $spell)
                {
                    $dominionSpell = DominionSpell::where('dominion_id', $caster->id)->where('spell_id', $spell->id)
                    ->update(['duration' => $spell->duration]);

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
                        'dominion_id' => $caster->id,
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

            return [
                'success' => true,
                'message' => sprintf(
                    'Your wizards cast %s successfully, and it will continue to affect your dominion for the next %s ticks.',
                    $spell->name,
                    $spell->duration
                )
            ];
        }
        # Friendly spells, friendly auras
        elseif($spell->scope == 'friendly')
        {
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
        # Hostile aura
        elseif($spell->scope == 'hostile')
        {

            $selfWpa = min(10,$this->militaryCalculator->getWizardRatio($caster, 'offense'));
            $targetWpa = min(10,$this->militaryCalculator->getWizardRatio($target, 'defense'));

            # Are we successful?
            ## If yes
            if ($targetWpa == 0.0 or random_chance($this->opsHelper->blackOperationSuccessChance($selfWpa, $targetWpa)))
            {
                # Is the spell reflected?
                $spellReflected = false;
                if (random_chance($target->getSpellPerkMultiplier('chance_to_reflect_spells')) and $spell->class !== 'invasion')
                {
                    $spellReflected = true;
                    $reflectedBy = $target;
                    $target = $caster;
                    $caster = $reflectedBy;
                    $target->stat_spells_reflected += 1;
                }

                if ($this->spellCalculator->isSpellActive($target, $spell->key))
                {
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

                // Update statistics
                if (isset($caster->{"stat_{$spell->key}_hours"}))
                {
                    $caster->{"stat_{$spell->key}_hours"} += $spell->duration;
                }

                // Surreal Perception
                $sourceDominionId = null;
                if ($target->getSpellPerkValue('reveal_ops'))
                {
                    $sourceDominionId = $caster->id;
                }

                $this->notificationService
                    ->queueNotification('received_hostile_spell', [
                        'sourceDominionId' => $sourceDominionId,
                        'spellKey' => $spell->key,
                    ])
                    ->sendNotifications($target, 'irregular_dominion');

                if ($spellReflected)
                {
                  // Notification for Energy Mirror deflection
                   $this->notificationService
                       ->queueNotification('reflected_hostile_spell', [
                           'sourceDominionId' => $target->id,
                           'spellKey' => $spell->key,
                       ])
                       ->sendNotifications($caster, 'irregular_dominion');

                   return [
                       'success' => true,
                       'message' => sprintf(
                           'Your wizards cast the spell successfully, but it was reflected and it will now affect your dominion for the next %s ticks.',
                           $spell->duration
                       ),
                       'alert-type' => 'danger'
                   ];
               }
               else
               {
                   return [
                       'success' => true,
                       'message' => sprintf(
                           'Your wizards cast %s successfully, and it will continue to affect your target for the next %s ticks.',
                           $spell->name,
                           $spell->duration
                       )
                   ];
               }
            }
            # Are we successful?
            ## If no
            else
            {
                $wizardsKilledBasePercentage = 1;

                $wizardLossSpaRatio = ($targetWpa / $selfWpa);
                $wizardsKilledPercentage = clamp($wizardsKilledBasePercentage * $wizardLossSpaRatio, 0.5, 1.5);

                $unitsKilled = [];
                $wizardsKilled = (int)floor($caster->military_wizards * ($wizardsKilledPercentage / 100));

                // Check for immortal wizards
                if ($caster->race->getPerkValue('immortal_wizards') != 0)
                {
                    $wizardsKilled = 0;
                }

                if ($wizardsKilled > 0)
                {
                    $unitsKilled['wizards'] = $wizardsKilled;
                    $caster->military_wizards -= $wizardsKilled;
                    $caster->stat_total_wizards_lost += $wizardsKilled;
                }

                $wizardUnitsKilled = 0;
                foreach ($caster->race->units as $unit)
                {
                    if ($unit->getPerkValue('counts_as_wizard_offense'))
                    {
                        if($unit->getPerkValue('immortal_wizard'))
                        {
                          $unitKilled = 0;
                        }
                        else
                        {
                          $unitKilledMultiplier = ((float)$unit->getPerkValue('counts_as_wizard_offense') / 2) * ($wizardsKilledPercentage / 100);
                          $unitKilled = (int)floor($caster->{"military_unit{$unit->slot}"} * $unitKilledMultiplier);
                        }

                        if ($unitKilled > 0)
                        {
                            $unitsKilled[strtolower($unit->name)] = $unitKilled;
                            $caster->{"military_unit{$unit->slot}"} -= $unitKilled;
                            $caster->{'stat_total_unit' . $unit->slot . '_lost'} += $unitKilled;

                            $wizardUnitsKilled += $unitKilled;
                        }
                    }
                }

                if ($this->spellCalculator->isSpellActive($target, 'cogency'))
                {
                    $this->notificationService->queueNotification('cogency_occurred',['sourceDominionId' => $caster->id, 'saved' => ($wizardsKilled + $wizardUnitsKilled)]);
                    $this->queueService->queueResources('training', $target, ['military_wizards' => ($wizardsKilled + $wizardUnitsKilled)], 6);
                }

                $unitsKilledStringParts = [];
                foreach ($unitsKilled as $name => $amount)
                {
                    $amountLabel = number_format($amount);
                    $unitLabel = str_plural(str_singular($name), $amount);
                    $unitsKilledStringParts[] = "{$amountLabel} {$unitLabel}";
                }
                $unitsKilledString = generate_sentence_from_array($unitsKilledStringParts);

                // Inform target that they repelled a hostile spell
                $this->notificationService
                    ->queueNotification('repelled_hostile_spell', [
                        'sourceDominionId' => $caster->id,
                        'spellKey' => $spell->key,
                        'unitsKilled' => $unitsKilledString,
                    ])
                    ->sendNotifications($target, 'irregular_dominion');

                if ($unitsKilledString)
                {
                    $message = "The enemy wizards have repelled our {$spell->name} attempt and managed to kill $unitsKilledString.";
                }
                else
                {
                    $message = "The enemy wizards have repelled our {$spell->name} attempt.";
                }

                // Return here, thus completing the spell cast and reducing the caster's mana
                return [
                    'success' => false,
                    'message' => $message,
                    'wizardStrengthCost' => 2,
                    'alert-type' => 'warning',
                ];
            }
        }
    }

    protected function castActiveSpell(Dominion $caster, ?Dominion $target = null, Spell $spell, int $wizardStrengthCost): array
    {

        if ($spell->scope == 'hostile' and $caster->round->hasOffensiveActionsDisabled())
        {
            throw new GameException('Hostile spells have been disabled for the rest of the round.');
        }

        if ($spell->scope == 'hostile' and now()->diffInDays($caster->round->start_date) < self::BLACK_OPS_DAYS_AFTER_ROUND_START and !$isInvasionSpell)
        {
            throw new GameException('You cannot cast hostile spells during the first day of the round.');
        }

        # Self-spells self impact spells
        if($spell->scope == 'self')
        {
            $extraLine = '';

            foreach($spell->perks as $perk)
            {
                $spellPerkValues = $spell->getActiveSpellPerkValues($spell->key, $perk->key);

                # Resource conversion
                if($perk->key === 'resource_conversion')
                {
                    $from = $spellPerkValues[0];
                    $to = $spellPerkValues[1];
                    $ratio = $spellPerkValues[2] / 100;
                    $exchange = $spellPerkValues[3];

                    $amountRemoved = ceil($caster->{'resource_' . $from} * $ratio);
                    $amountAdded = floor($amountRemoved / $exchange);

                    $caster->{'resource_'.$from} -= $amountRemoved;
                    $caster->{'resource_'.$to} += $amountAdded;
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
                if($perk->key === 'summon_units_from_land_by_time')
                {
                    $unitSlots = (array)$spellPerkValues[0];
                    $basePerAcre = (float)$spellPerkValues[1];
                    $hourlyPercentIncrease = (float)$spellPerkValues[2] / 100;
                    $landType = (string)$spellPerkValues[3];

                    $hoursIntoTheRound = now()->startOfHour()->diffInHours(Carbon::parse($caster->round->start_date)->startOfHour());

                    $totalUnitsSummoned = 0;

                    foreach($unitSlots as $slot)
                    {
                        $amountPerAcre = $basePerAcre * (1 + $hoursIntoTheRound * $hourlyPercentIncrease);
                        $unitsSummoned = floor($amountPerAcre * $caster->{'land_' . $landType});
                        $caster->{'military_unit' . $slot} += $unitsSummoned;
                        $totalUnitsSummoned += $unitsSummoned;
                    }

                    $extraLine = ', summoning ' . number_format($totalUnitsSummoned) . ' new units to our military';
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
            }

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

            return [
                'success' => true,
                'message' => sprintf(
                    'Your wizards successfully cast %s on %s',
                    $spell->name,
                    $target->name
                )
            ];
        }
        # Hostile spells, hostile impact spells
        elseif($spell->scope == 'hostile')
        {
            $selfWpa = min(10,$this->militaryCalculator->getWizardRatio($caster, 'offense'));
            $targetWpa = min(10,$this->militaryCalculator->getWizardRatio($target, 'defense'));

            # Are we successful?
            ## If yes
            if ($targetWpa == 0.0 or random_chance($this->opsHelper->blackOperationSuccessChance($selfWpa, $targetWpa)))
            {
                # Is the spell reflected?
                $spellReflected = false;
                if (random_chance($this->spellCalculator->getPassiveSpellPerkMultiplier($target, 'chance_to_reflect_spells')) and !$isInvasionSpell)
                {
                    $spellReflected = true;
                    $reflectedBy = $target;
                    $target = $dominion;
                    $caster = $reflectedBy;
                    $caster->stat_spells_reflected += 1;
                }

                $damageDealt = [];

                foreach($spell->perks as $perk)
                {
                    $spellPerkValues = $spell->getActiveSpellPerkValues($spell->key, $perk->key);

                    if($perk->key === 'kills_peasants')
                    {
                        $attribute = 'peasants';
                        $baseDamage = (float)$spellPerkValues / 100;
                        $damageMultiplier = $this->spellDamageCalculator->getDominionHarmfulSpellDamageModifier($target, $caster, $spell, 'peasants');

                        $damage = min(round($target->peasants * $baseDamage * $damageMultiplier), $target->peasants);

                        $target->{$attribute} -= $damage;
                        $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                        // Update statistics
                        if (isset($dominion->{"stat_{$spell->key}_damage"}))
                        {
                            $dominion->{"stat_{$spell->key}_damage"} += $damage;
                        }

                        # For Empire, add burned peasants go to the crypt
                        if($target->realm->alignment === 'evil')
                        {
                            $target->realm->crypt += $damage;
                        }
                    }

                    if($perk->key === 'kills_draftees')
                    {
                        $attribute = 'military_draftees';
                        $baseDamage = (float)$spellPerkValues / 100;
                        $damageMultiplier = $this->spellDamageCalculator->getDominionHarmfulSpellDamageModifier($target, $caster, $spell, 'draftees');

                        $damage = min(round($target->military_draftees * $baseDamage * $damageMultiplier), $target->military_draftees);

                        $target->{$attribute} -= $damage;
                        $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                        // Update statistics
                        if (isset($dominion->{"stat_{$spell->key}_damage"}))
                        {
                            $dominion->{"stat_{$spell->key}_damage"} += $damage;
                        }

                        # For Empire, add burned peasants go to the crypt
                        if($target->realm->alignment === 'evil')
                        {
                            $target->realm->crypt += $damage;
                        }
                    }

                    if($perk->key === 'kills_faction_units_percentage')
                    {
                        $faction = $spellPerkValues[0];
                        $slot = (int)$spellPerkValues[1];
                        $ratio = (float)$spellPerkValues[2] / 100;

                        $attribute = 'military_unit'.$slot;

                        if($target->race->name !== $faction)
                        {
                            $ratio = 0;
                        }

                        $damageMultiplier = $this->spellDamageCalculator->getDominionHarmfulSpellDamageModifier($target, $caster, $spell, $attribute);

                        $damage = round($target->{'military_unit'.$slot} * $ratio);

                        $target->{$attribute} -= $damage;

                        $unit = $target->race->units->filter(function ($unit) use ($slot) {
                            return ($unit->slot == $slot);
                        })->first();

                        $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($unit->name, $damage));

                        $caster->stat_total_units_killed += $damage;
                        $target->{'stat_total_unit' . $slot . '_lost'} += $damage;
                    }

                    if($perk->key === 'disband_spies')
                    {
                        $attribute = 'military_spies';
                        $baseDamage = (float)$spellPerkValues / 100;
                        $damageMultiplier = $this->spellDamageCalculator->getDominionHarmfulSpellDamageModifier($target, $caster, $spell, 'spies');

                        $damage = min(round($target->military_spies * $baseDamage * $damageMultiplier), $target->military_spies);

                        $target->{$attribute} -= $damage;
                        $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));

                        // Update statistics
                        if (isset($dominion->{"stat_{$spell->key}_damage"}))
                        {
                            $dominion->{"stat_{$spell->key}_damage"} += round($damage);
                        }
                    }

                    # Increase morale
                    if($perk->key === 'decrease_morale')
                    {
                        $attribute = 'morale';
                        $baseDamage = (int)$spellPerkValues / 100;

                        $damageMultiplier = $this->spellDamageCalculator->getDominionHarmfulSpellDamageModifier($target, $caster, $spell, $attribute);

                        $damage = min(round($target->{$attribute} * $baseDamage * $damageMultiplier), $target->military_spies);

                        $target->{$attribute} -= $damage;
                        $damageDealt[] = sprintf('%s%% %s', number_format($damage), dominion_attr_display($attribute, $damage));

                    }

                    if($perk->key === 'destroys_resource')
                    {
                        $resource = $spellPerkValues[0];
                        $ratio = (float)$spellPerkValues[1] / 100;
                        $attribute = 'resource_'.$resource;

                        $damageMultiplier = $this->spellDamageCalculator->getDominionHarmfulSpellDamageModifier($target, $caster, $spell, $resource);
                        $damage = min(round($target->{'resource_'.$resource} * $ratio * $damageMultiplier), $target->{'resource_'.$resource});

                        $target->{$attribute} -= $damage;
                        $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attribute, $damage));
                    }

                    if($perk->key === 'improvements_damage')
                    {
                        $ratio = (float)$spellPerkValues / 100;

                        foreach($this->improvementHelper->getImprovementTypes($target) as $improvement)
                        {
                            $improvements[] = $improvement;
                        }

                        $improvementPoints = 0;
                        foreach($improvements as $improvement)
                        {
                            $improvementPoints += $target->{'improvement_'.$improvement};
                        }

                        $damageMultiplier = $this->spellDamageCalculator->getDominionHarmfulSpellDamageModifier($target, $caster, $spell, 'improvements');
                        $damage = ($improvementPoints * $ratio * $damageMultiplier);

                        $totalDamage = $damage;

                        if($damage > 0)
                        {
                            foreach($improvements as $improvement)
                            {
                                $target->{'improvement_'.$improvement} -= $damage * ($target->{'improvement_'.$improvement} / $improvementPoints);
                            }
                        }

                        $damageDealt = [sprintf('%s %s', number_format($totalDamage), dominion_attr_display('improvement', $totalDamage))];
                    }
                }

                $target->save([
                    'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                    'action' => $spell->key
                ]);

                // Surreal Perception
                $sourceDominionId = null;
                if ($target->getSpellPerkValue('reveal_ops'))
                {
                    $sourceDominionId = $caster->id;
                }

                $damageString = generate_sentence_from_array($damageDealt);

                $this->notificationService
                    ->queueNotification('received_hostile_spell', [
                        'sourceDominionId' => $sourceDominionId,
                        'spellKey' => $spell->key,
                        'damageString' => $damageString,
                    ])
                    ->sendNotifications($target, 'irregular_dominion');

                if ($spellReflected) {
                    return [
                        'success' => true,
                        'message' => sprintf(
                            'Your wizards cast the spell successfully, but it was deflected and your dominion lost %s.',
                            $damageString
                        ),
                        'alert-type' => 'danger'
                    ];
                } else {
                    return [
                        'success' => true,
                        'damage' => $damage,
                        'message' => sprintf(
                            'Your wizards cast the spell successfully, your target lost %s.',
                            $damageString
                        )
                    ];
                }


            }
            # Are we successful?
            ## If no
            else
            {
                $wizardsKilledBasePercentage = 1;

                $wizardLossSpaRatio = ($targetWpa / $selfWpa);
                $wizardsKilledPercentage = clamp($wizardsKilledBasePercentage * $wizardLossSpaRatio, 0.5, 1.5);

                $unitsKilled = [];
                $wizardsKilled = (int)floor($caster->military_wizards * ($wizardsKilledPercentage / 100));

                // Check for immortal wizards
                if ($caster->race->getPerkValue('immortal_wizards') != 0)
                {
                    $wizardsKilled = 0;
                }

                if ($wizardsKilled > 0)
                {
                    $unitsKilled['wizards'] = $wizardsKilled;
                    $caster->military_wizards -= $wizardsKilled;
                    $caster->stat_total_wizards_lost += $wizardsKilled;
                }

                $wizardUnitsKilled = 0;
                foreach ($caster->race->units as $unit)
                {
                    if ($unit->getPerkValue('counts_as_wizard_offense'))
                    {
                        if($unit->getPerkValue('immortal_wizard'))
                        {
                          $unitKilled = 0;
                        }
                        else
                        {
                          $unitKilledMultiplier = ((float)$unit->getPerkValue('counts_as_wizard_offense') / 2) * ($wizardsKilledPercentage / 100);
                          $unitKilled = (int)floor($caster->{"military_unit{$unit->slot}"} * $unitKilledMultiplier);
                        }

                        if ($unitKilled > 0)
                        {
                            $unitsKilled[strtolower($unit->name)] = $unitKilled;
                            $caster->{"military_unit{$unit->slot}"} -= $unitKilled;
                            $caster->{'stat_total_unit' . $unit->slot . '_lost'} += $unitKilled;

                            $wizardUnitsKilled += $unitKilled;
                        }
                    }
                }

                if ($this->spellCalculator->isSpellActive($target, 'cogency'))
                {
                    $this->notificationService->queueNotification('cogency_occurred',['sourceDominionId' => $caster->id, 'saved' => ($wizardsKilled + $wizardUnitsKilled)]);
                    $this->queueService->queueResources('training', $target, ['military_wizards' => ($wizardsKilled + $wizardUnitsKilled)], 6);
                }

                $unitsKilledStringParts = [];
                foreach ($unitsKilled as $name => $amount)
                {
                    $amountLabel = number_format($amount);
                    $unitLabel = str_plural(str_singular($name), $amount);
                    $unitsKilledStringParts[] = "{$amountLabel} {$unitLabel}";
                }
                $unitsKilledString = generate_sentence_from_array($unitsKilledStringParts);

                // Inform target that they repelled a hostile spell
                $this->notificationService
                    ->queueNotification('repelled_hostile_spell', [
                        'sourceDominionId' => $caster->id,
                        'spellKey' => $spell->key,
                        'unitsKilled' => $unitsKilledString,
                    ])
                    ->sendNotifications($target, 'irregular_dominion');

                if ($unitsKilledString)
                {
                    $message = "The enemy wizards have repelled our {$spell->name} attempt and managed to kill $unitsKilledString.";
                }
                else
                {
                    $message = "The enemy wizards have repelled our {$spell->name} attempt.";
                }

                // Return here, thus completing the spell cast and reducing the caster's mana
                return [
                    'success' => false,
                    'message' => $message,
                    'wizardStrengthCost' => 2,
                    'alert-type' => 'warning',
                ];
            }


        }
    }

    protected function castInvasionSpell(Dominion $caster, ?Dominion $target = null, Spell $spell, int $wizardStrengthCost): void
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
            if ($this->spellCalculator->isSpellActive($target, $spell->key))
            {

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

            // Update statistics
            if (isset($caster->{"stat_{$spell->key}_hours"}))
            {
                $caster->{"stat_{$spell->key}_hours"} += $spell->duration;
            }

            $this->notificationService
                ->queueNotification('received_hostile_spell', [
                    'sourceDominionId' => $caster->id,
                    'spellKey' => $spell->key,
                ])
                ->sendNotifications($target, 'irregular_dominion');
        }
    }

    protected function castInfoSpell(Dominion $caster, Dominion $target = null, Spell $spell, int $wizardStrengthCost): array
    {
        if(!$caster->round->hasStarted())
        {
            throw new GameException('You cannot cast info spells until the round has started.');
        }

        if ($spell->scope == 'hostile' and $caster->round->hasOffensiveActionsDisabled())
        {
            throw new GameException('Hostile spells have been disabled for the rest of the round.');
        }

        $selfWpa = min(10,$this->militaryCalculator->getWizardRatio($caster, 'offense'));
        $targetWpa = min(10,$this->militaryCalculator->getWizardRatio($target, 'defense'));

        if ($targetWpa == 0.0 or random_chance($this->opsHelper->operationSuccessChance($selfWpa, $targetWpa, static::INFO_MULTIPLIER_SUCCESS_RATE)))
        {
            # Is the spell reflected?
            $spellReflected = false;
            if (random_chance($target->getSpellPerkMultiplier('chance_to_reflect_spells')))
            {
                $spellReflected = true;
                $reflectedBy = $target;
                $caster = $reflectedBy;
                $target->stat_spells_reflected += 1;
            }

            $infoOp = new InfoOp([
                'source_realm_id' => $caster->realm->id,
                'target_realm_id' => $target->realm->id,
                'type' => $spell->key,
                'source_dominion_id' => $caster->id,
                'target_dominion_id' => $target->id,
            ]);

            switch ($spell->key)
            {
                case 'clear_sight':
                        $infoOp->data = [

                          'title' => $target->title->name,
                          'ruler_name' => $target->ruler_name,
                          'race_id' => $target->race->id,
                          'land' => $this->landCalculator->getTotalLand($target),
                          'peasants' => $target->peasants * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'employment' => $this->populationCalculator->getEmploymentPercentage($target),
                          'networth' => $this->networthCalculator->getDominionNetworth($target),
                          'prestige' => $target->prestige,
                          'victories' => $target->stat_attacking_success,
                          'net_victories' => $this->militaryCalculator->getNetVictories($target),

                          'resource_gold' => $target->resource_gold * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'resource_food' => $target->resource_food * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'resource_lumber' => $target->resource_lumber * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'resource_mana' => $target->resource_mana * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'resource_ore' => $target->resource_ore * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'resource_gems' => $target->resource_gems * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'resource_tech' => $target->resource_tech * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          #'resource_boats' => $target->resource_boats + $this->queueService->getInvasionQueueTotalByResource(
                          #        $target,
                          #        'resource_boats'
                          #    ) * $this->opsHelper->getInfoOpsAccuracyModifier($target),


                          'resource_champion' => $target->resource_champion,
                          'resource_soul' => $target->resource_soul,
                          'resource_blood' => $target->resource_blood,
                          'resource_wild_yeti' => $target->resource_wild_yeti,

                          'morale' => $target->morale,
                          'military_draftees' => $target->military_draftees * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'military_unit1' => $this->militaryCalculator->getTotalUnitsForSlot($target, 1) * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'military_unit2' => $this->militaryCalculator->getTotalUnitsForSlot($target, 2) * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'military_unit3' => $this->militaryCalculator->getTotalUnitsForSlot($target, 3) * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'military_unit4' => $this->militaryCalculator->getTotalUnitsForSlot($target, 4) * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'military_spies' => $target->military_spies * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'military_wizards' => $target->military_wizards * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                          'military_archmages' => $target->military_archmages * $this->opsHelper->getInfoOpsAccuracyModifier($target),

                          'recently_invaded_count' => $this->militaryCalculator->getRecentlyInvadedCount($target),

                        ];

                    break;

                case 'vision':

                    $advancements = [];
                    $techs = $target->techs->sortBy('key');
                    $techs = $techs->sortBy(function ($tech, $key)
                    {
                        return $tech['name'] . str_pad($tech['level'], 2, '0', STR_PAD_LEFT);
                    });

                    foreach($techs as $tech)
                    {
                        $advancement = $tech['name'];
                        $key = $tech['key'];
                        $level = (int)$tech['level'];
                        $advancements[$advancement] = [
                            'key' => $key,
                            'name' => $advancement,
                            'level' => (int)$level,
                            ];
                    }

                    $infoOp->data = [
                        #'techs' => $techs,#$target->techs->pluck('name', 'key')->all(),
                        'advancements' => $advancements,
                        'heroes' => []
                    ];
                    break;

                case 'revelation':
                    $infoOp->data = $this->spellCalculator->getActiveSpells($target);
                    break;

                default:
                    throw new LogicException("Unknown info op spell {$spell->key}");
            }

            // Surreal Perception
            if ($target->getSpellPerkValue('reveal_ops'))
            {
                $this->notificationService
                    ->queueNotification('received_hostile_spell', [
                        'sourceDominionId' => $caster->id,
                        'spellKey' => $spell->key,
                    ])
                    ->sendNotifications($target, 'irregular_dominion');
            }

            $infoOp->save();

            return [
                'success' => true,
                'message' => 'Your wizards cast the spell successfully, and a wealth of information appears before you.',
                'wizardStrengthCost' => $wizardStrengthCost,
                'redirect' => route('dominion.op-center.show', $target),
            ];
        }
        # Are we successful?
        ## If no
        else
        {

            // Inform target that they repelled a hostile spell
            $this->notificationService
                ->queueNotification('repelled_hostile_spell', [
                    'sourceDominionId' => $caster->id,
                    'spellKey' => $spell->key,
                    'unitsKilled' => null,
                ])
                ->sendNotifications($target, 'irregular_dominion');

            // Return here, thus completing the spell cast and reducing the caster's mana
            return [
                'success' => false,
                'message' => "The enemy wizards have repelled our {$spell->name} attempt.",
                'wizardStrengthCost' => 2,
                'alert-type' => 'warning',
            ];
        }

    }
    /*
    protected function castInfoOpSpell(Dominion $dominion, string $spellKey, Dominion $target, int $wizardStrengthCost): array
    {

        if(!$dominion->round->hasStarted())
        {
            throw new GameException('You cannot cast info spells until the round has started.');
        }

        $spellInfo = $this->spellHelper->getSpellInfo($spellKey, $dominion);

        $selfWpa = $this->militaryCalculator->getWizardRatio($dominion, 'offense');
        $targetWpa = $this->militaryCalculator->getWizardRatio($target, 'defense');

        // You need at least some positive WPA to cast info ops
        if ($selfWpa === 0.0)
        {
            // Don't reduce mana by throwing an exception here
            throw new GameException("Your wizard force is too weak to cast {$spellInfo['name']}. Please train more wizards.");
        }

        // 100% spell success if target has a WPA of 0
        if ($targetWpa !== 0.0) {
            $successRate = $this->opsHelper->operationSuccessChance($selfWpa, $targetWpa, static::INFO_MULTIPLIER_SUCCESS_RATE);

            if (!random_chance($successRate)) {
                // Inform target that they repelled a hostile spell
                $this->notificationService
                    ->queueNotification('repelled_hostile_spell', [
                        'sourceDominionId' => $dominion->id,
                        'spellKey' => $spellKey,
                        'unitsKilled' => '',
                    ])
                    ->sendNotifications($target, 'irregular_dominion');

                // Return here, thus completing the spell cast and reducing the caster's mana
                return [
                    'success' => false,
                    'message' => "The enemy wizards have repelled our {$spellInfo['name']} attempt.",
                    'wizardStrengthCost' => $wizardStrengthCost,
                    'alert-type' => 'warning',
                ];
            }
        }

        $infoOp = new InfoOp([
            'source_realm_id' => $dominion->realm->id,
            'target_realm_id' => $target->realm->id,
            'type' => $spellKey,
            'source_dominion_id' => $dominion->id,
            'target_dominion_id' => $target->id,
        ]);

        switch ($spellKey) {
            case 'clear_sight':
                    $infoOp->data = [

                      'title' => $target->title->name,
                      'ruler_name' => $target->ruler_name,
                      'race_id' => $target->race->id,
                      'land' => $this->landCalculator->getTotalLand($target),
                      'peasants' => $target->peasants * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'employment' => $this->populationCalculator->getEmploymentPercentage($target),
                      'networth' => $this->networthCalculator->getDominionNetworth($target),
                      'prestige' => $target->prestige,
                      'victories' => $target->stat_attacking_success,
                      'net_victories' => $this->militaryCalculator->getNetVictories($target),

                      'resource_gold' => $target->resource_gold * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'resource_food' => $target->resource_food * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'resource_lumber' => $target->resource_lumber * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'resource_mana' => $target->resource_mana * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'resource_ore' => $target->resource_ore * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'resource_gems' => $target->resource_gems * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'resource_tech' => $target->resource_tech * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'resource_boats' => $target->resource_boats + $this->queueService->getInvasionQueueTotalByResource(
                              $target,
                              'resource_boats'
                          ) * $this->opsHelper->getInfoOpsAccuracyModifier($target),


                      'resource_champion' => $target->resource_champion,
                      'resource_soul' => $target->resource_soul,
                      'resource_blood' => $target->resource_blood,
                      'resource_wild_yeti' => $target->resource_wild_yeti,

                      'morale' => $target->morale,
                      'military_draftees' => $target->military_draftees * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'military_unit1' => $this->militaryCalculator->getTotalUnitsForSlot($target, 1) * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'military_unit2' => $this->militaryCalculator->getTotalUnitsForSlot($target, 2) * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'military_unit3' => $this->militaryCalculator->getTotalUnitsForSlot($target, 3) * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'military_unit4' => $this->militaryCalculator->getTotalUnitsForSlot($target, 4) * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'military_spies' => $target->military_spies * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'military_wizards' => $target->military_wizards * $this->opsHelper->getInfoOpsAccuracyModifier($target),
                      'military_archmages' => $target->military_archmages * $this->opsHelper->getInfoOpsAccuracyModifier($target),

                      'recently_invaded_count' => $this->militaryCalculator->getRecentlyInvadedCount($target),

                    ];

                break;

            case 'vision':

                $advancements = [];
                $techs = $target->techs->sortBy('key');
                $techs = $techs->sortBy(function ($tech, $key)
                {
                    return $tech['name'] . str_pad($tech['level'], 2, '0', STR_PAD_LEFT);
                });

                foreach($techs as $tech)
                {
                    $advancement = $tech['name'];
                    $key = $tech['key'];
                    $level = (int)$tech['level'];
                    $advancements[$advancement] = [
                        'key' => $key,
                        'name' => $advancement,
                        'level' => (int)$level,
                        ];
                }

                $infoOp->data = [
                    #'techs' => $techs,#$target->techs->pluck('name', 'key')->all(),
                    'advancements' => $advancements,
                    'heroes' => []
                ];
                break;

            case 'revelation':
                $infoOp->data = $this->spellCalculator->getActiveSpells($target);
                break;

            case 'clairvoyance':
                $infoOp->data = [
                    'targetRealmId' => $target->realm->id
                ];
                break;

            default:
                throw new LogicException("Unknown info op spell {$spellKey}");
        }

        // Surreal Perception
        if ($target->getSpellPerkValue('reveal_ops'))
        {
            $this->notificationService
                ->queueNotification('received_hostile_spell', [
                    'sourceDominionId' => $dominion->id,
                    'spellKey' => $spellKey,
                ])
                ->sendNotifications($target, 'irregular_dominion');
        }

        $infoOp->save();

        $redirect = route('dominion.op-center.show', $target);
        if ($spellKey === 'clairvoyance') {
            $redirect = route('dominion.op-center.clairvoyance', $target->realm->number);
        }

        return [
            'success' => true,
            'message' => 'Your wizards cast the spell successfully, and a wealth of information appears before you.',
            'wizardStrengthCost' => $wizardStrengthCost,
            'redirect' => $redirect,
        ];
    }
    */

    public function breakSpell(Dominion $target, string $spellKey): array
    {;
        $spell = Spell::where('key', $spellKey)->first();
        $dominionSpell = DominionSpell::where('spell_id', $spell->id)->where('dominion_id', $target->id)->first();

        $caster = Dominion::findOrFail($dominionSpell->caster_id);

        if(!$this->spellCalculator->isSpellActive($target, $spellKey))
        {
            throw new GameException($spell->name . ' is not active.');
        }

        if($spell->class == 'invasion')
        {
            throw new GameException($spell->name . ' cannot be broken.');
        }

        $wizardStrengthCost = 5;#$this->spellCalculator->getWizardStrengthCost($spell);

        if ($target->wizard_strength <= 0 or ($target->wizard_strength - $wizardStrengthCost) < 0)
        {
            throw new GameException("Your wizards to not have enough strength to break {$spell->name}. You need {$wizardStrengthCost}% wizard strength to break this spell.");
        }
        else
        {
            $target->wizard_strength -= $wizardStrengthCost;
        }

        $manaCost = $this->spellCalculator->getManaCost($target, $spell->key);

        if ($target->resource_mana < $manaCost)
        {
            throw new GameException("You do not have enough mana to break {$spell->name}. You need {$manaCost} mana to break this spell.");
        }
        else
        {
            $target->resource_mana -= $manaCost;
        }

        $casterWpa = min(10,$this->militaryCalculator->getWizardRatio($caster, 'defense'));
        $targetWpa = min(10,$this->militaryCalculator->getWizardRatio($target, 'offense'));

        if ($casterWpa == 0.0 or random_chance($this->opsHelper->blackOperationSuccessChance($targetWpa, $casterWpa)))
        {
              DB::transaction(function () use ($target, $spell)
              {
                  DominionSpell::where('dominion_id', $target->id)
                      ->where('spell_id', $spell->id)
                      ->delete();

                  $target->save([
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
