<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Exception;
use LogicException;
use Illuminate\Support\Carbon;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SorceryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Spell;

use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;

class SorceryActionService
{

    use DominionGuardsTrait;
        
    protected $sorcery = [
        'class' => '',
        'spell_key' => '',
        'caster' => [],
        'target' => [],
        'damage' => []
    ];

    protected $sorceryEvent;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /** @var ResourceCalculator */
    protected $resourceCalculator;

    /** @var ResourceService */
    protected $resourceService;

    /** @var SorceryCalculator */
    protected $sorceryCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var StatsService */
    protected $statsService;

    /**
     * SorceryActionService constructor.
     */
    public function __construct()
    {
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->resourceService = app(ResourceService::class);
        $this->sorceryCalculator = app(SorceryCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->statsService = app(StatsService::class);
    }

    public function performSorcery(Dominion $caster, Dominion $target, Spell $spell, int $wizardStrength, Resource $enhancementResource = null, int $enhancementAmount = 0): array
    {

        $this->guardActionsDuringTick($caster);
        $this->guardActionsDuringTick($target);

        DB::transaction(function () use ($caster, $target, $spell, $wizardStrength, $enhancementResource, $enhancementAmount)
        {

            if(!$caster->round->getSetting('sorcery'))
            {
                throw new GameException('Sorcery is disabled this round.');
            }

            # BEGIN VALIDATION

            if ($caster->race->getPerkValue('cannot_perform_sorcery'))
            {
                throw new GameException($caster->race->name . ' cannot perform sorcery.');
            }

            if ($caster->getSpellPerkValue('cannot_perform_sorcery'))
            {
                throw new GameException('A spell of silence is preventing you from performing sorcery.');
            }

            if(!$this->sorceryCalculator->canPerformSorcery($caster))
            {
                throw new GameException('Your wizards are too weak to perform sorcery.');
            }

            if(isset($target) and $target->getSpellPerkValue('stasis'))
            {
                throw new GameException('A magical stasis surrounds the Qurrian lands, making it impossible for your wizards to cast spells on them.');
            }
            
            if($caster->getSpellPerkValue('stasis'))
            {
                throw new GameException('You cannot cast spells while you are in stasis.');
            }

            if (!$spell->enabled)
            {
                throw new LogicException("Spell {$spell->name} is not enabled.");
            }

            if (!$this->spellCalculator->canCastSpell($caster, $spell, $this->resourceCalculator->getAmount($caster, 'mana')))
            {
                throw new GameException("You are not able to cast {$spell->name}.");
            }

            if (($caster->wizard_strength - $wizardStrength) < 0)
            {
                throw new GameException("Your wizards are too weak to perform such sorcery. You would need {$wizardStrength}% wizard strength but only have {$caster->wizard_strength}%.");
            }

            if ($caster->wizard_strength < 4)
            {
                throw new GameException("You must have at least 4% Wizard Strength to perform sorcery.");
            }

            if ($this->militaryCalculator->getWizardRatio($caster, 'offense') < 0.10)
            {
                throw new GameException("You must have at least 0.10 Wizard Ratio to perform sorcery.");
            }

            if ($wizardStrength < 4)
            {
                throw new GameException("You spend at least 4% Wizard Strength.");
            }

            $manaCost = $this->sorceryCalculator->getSpellManaCost($caster, $spell, $wizardStrength);
            $casterManaAmount = $this->resourceCalculator->getAmount($caster, 'mana');

            if ($manaCost > $casterManaAmount)
            {
                throw new GameException("You do not have enough mana to perform such sorcery. You would need " . number_format($manaCost) . " mana but only have " . number_format($casterManaAmount) . ".");
            }

            if ($target === null)
            {
                throw new GameException("You must select a target when performing sorcery.");
            }

            if ($caster->protection_ticks != 0)
            {
                throw new GameException("You cannot perform sorcery while under protection");
            }

            if ($target->protection_ticks != 0)
            {
                throw new GameException("You cannot perform sorcery against targets under protection");
            }

            if (!$this->rangeCalculator->isInRange($caster, $target) and $spell->class !== 'invasion')
            {
                throw new GameException("You cannot cast spells on targets not in your range");
            }

            if ($caster->id === $target->id)
            {
                throw new GameException("You cannot perform sorcery on yourself");
            }

            if ($caster->realm->id === $target->realm->id and ($caster->round->mode == 'standard' or $caster->round->mode == 'standard-duration'))
            {
                throw new GameException("You cannot perform sorcery on other dominions in your realm in standard rounds");
            }

            if ($caster->realm->getAllies()->contains($target->realm))
            {
                throw new GameException('You cannot perform sorcery on dominions in allied realms.');
            }

            if ($caster->round->id !== $target->round->id)
            {
                throw new GameException('Nice try, but you cannot cast spells cross-round');
            }

            # END VALIDATION

            $this->sorcery = [
                'class' => $spell->class,
                'spell_key' => $spell->key,
                'caster' => [
                        'enhancement_resource' => $enhancementResource,
                        'enhancement_amount' => $enhancementResource,
                        'fog' => $caster->getSpellPerkValue('fog_of_war') ? true : false,
                        'mana_cost' => $manaCost,
                        'mana_current' => $casterManaAmount,
                        'wizard_strength_current' => $caster->wizard_strength,
                        'wizard_strength_spent' => $wizardStrength,
                        'wizard_ratio' => $this->militaryCalculator->getWizardRatio($caster, 'offense')
                    ],
                'target' => [
                        'crypt_bodies' => 0,
                        'fog' => $target->getSpellPerkValue('fog_of_war') ? true : false,
                        'reveal_ops' => $target->getSpellPerkValue('reveal_ops') ? true : false,
                        'wizard_strength_current' => $target->wizard_strength,
                        'wizard_ratio' => $this->militaryCalculator->getWizardRatio($target, 'defense')
                    ],
                'damage' => [],
            ];

            if($spell->class == 'passive')
            {
                $duration = $this->sorceryCalculator->getSpellDuration($caster, $target, $spell, $wizardStrength, $enhancementResource, $enhancementAmount);

                $this->sorcery['damage']['duration'] = $duration;

                $this->statsService->updateStat($caster, 'sorcery_duration', $duration);

                if ($this->spellCalculator->isSpellActive($target, $spell->key))
                {
                    DB::transaction(function () use ($caster, $target, $spell, $duration)
                    {
                        $dominionSpell = DominionSpell::where('dominion_id', $target->id)->where('spell_id', $spell->id)
                        ->increment('duration', $duration);

                        $target->save([
                            'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                            'action' => $spell->key
                        ]);
                    });
                }
                else
                {
                    DB::transaction(function () use ($caster, $target, $spell, $duration)
                    {
                        DominionSpell::create([
                            'dominion_id' => $target->id,
                            'caster_id' => $caster->id,
                            'spell_id' => $spell->id,
                            'duration' => $duration
                        ]);

                        $caster->save([
                            'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                            'action' => $spell->key
                        ]);
                    });
                }
            }
            elseif($spell->class == 'active')
            {
                foreach($spell->perks as $perk)
                {
                    $spellPerkValues = $spell->getActiveSpellPerkValues($spell->key, $perk->key);

                    if($perk->key === 'kill_peasants')
                    {
                        $baseDamage = (float)$spellPerkValues / 100;

                        $multipliers = $this->sorceryCalculator->getMultipliers($caster, $target, $spell, $wizardStrength, $enhancementResource, $enhancementAmount, $perk->key, 'peasants');

                        $sorcerySpellDamageMultiplier = $multipliers['sorcerySpellDamageMultiplier'];
                        $spellDamageMultiplier =  $multipliers['spellDamageMultiplier'];

                        $damage = $baseDamage * $sorcerySpellDamageMultiplier * $spellDamageMultiplier;

                        $damageDealt = min($target->peasants * $damage, $target->peasants);
                        $damageDealt = floor($damageDealt);

                        $peasantsBefore = $target->peasants;
                        $target->peasants -= $damageDealt;
                        $peasantsAfter = $peasantsBefore - $damageDealt;

                        $this->statsService->updateStat($caster, 'sorcery_peasants_killed', abs($damageDealt));
                        $this->statsService->updateStat($target, 'sorcery_peasants_lost', abs($damageDealt));

                        # For Empire, add killed draftees go in the crypt
                        if($target->realm->alignment === 'evil')
                        {
                            $this->resourceService->updateRealmResources($target->realm, ['body' => $damageDealt]);
                            $this->sorcery['target']['crypt_bodies'] += $damageDealt;
                        }

                        $this->sorcery['damage'][$perk->key] = [
                            'sorcery_spell_damage_multiplier' => $sorcerySpellDamageMultiplier,
                            'spell_damage_multiplier' => $spellDamageMultiplier,
                            'damage' => $damage,
                            'damage_dealt' => $damageDealt,
                            'peasants_before' => $peasantsBefore,
                            'peasants_after' => $peasantsAfter,
                        ];
                    }

                    if($perk->key === 'kill_draftees')
                    {
                        $baseDamage = (float)$spellPerkValues / 100;

                        $multipliers = $this->sorceryCalculator->getMultipliers($caster, $target, $spell, $wizardStrength, $enhancementResource, $enhancementAmount, $perk->key, 'draftees');

                        $sorcerySpellDamageMultiplier = $multipliers['sorcerySpellDamageMultiplier'];
                        $spellDamageMultiplier =  $multipliers['spellDamageMultiplier'];

                        $damage = $baseDamage * $sorcerySpellDamageMultiplier * $spellDamageMultiplier;

                        $damageDealt = min($target->peasants * $damage, $target->peasants);
                        $damageDealt = floor($damageDealt);

                        $target->peasants -= $damageDealt;

                        $this->statsService->updateStat($caster, 'sorcery_draftees_killed', $damage);
                        $this->statsService->updateStat($target, 'sorcery_draftees_lost', $damageDealt);

                        # For Empire, add killed draftees go in the crypt
                        if($target->realm->alignment === 'evil')
                        {
                            $this->resourceService->updateRealmResources($target->realm, ['body' => $damageDealt]);
                            $this->sorcery['target']['crypt_bodies'] += $damageDealt;
                        }

                        $this->sorcery['damage'][$perk->key] = [
                            'sorcery_spell_damage_multiplier' => $sorcerySpellDamageMultiplier,
                            'spell_damage_multiplier' => $spellDamageMultiplier,
                            'damage' => $damage,
                            'damage_dealt' => $damageDealt,
                            'military_draftees' => $target->military_draftees,
                        ];
                    }

                    if($perk->key === 'disband_spies')
                    {
                        $baseDamage = (float)$spellPerkValues / 100;

                        $multipliers = $this->sorceryCalculator->getMultipliers($caster, $target, $spell, $wizardStrength, $enhancementResource, $enhancementAmount, $perk->key, 'spies');

                        $sorcerySpellDamageMultiplier = $multipliers['sorcerySpellDamageMultiplier'];
                        $spellDamageMultiplier =  $multipliers['spellDamageMultiplier'];

                        $damage = $baseDamage * $sorcerySpellDamageMultiplier * $spellDamageMultiplier;

                        $damageDealt = min($target->military_spies * $damage, $target->military_spies);
                        $damageDealt = floor($damageDealt);

                        $target->military_spies -= $damageDealt;

                        $this->statsService->updateStat($caster, 'sorcery_spies_killed', $damage);
                        $this->statsService->updateStat($target, 'sorcery_spies_lost', $damage);
                        $this->statsService->updateStat($target, 'spies_lost', $damage);

                        # For Empire, add killed draftees go in the crypt
                        if($target->realm->alignment === 'evil')
                        {
                            $this->resourceService->updateRealmResources($target->realm, ['body' => $damageDealt]);
                            $this->sorcery['target']['crypt_bodies'] += $damageDealt;
                        }

                        $this->sorcery['damage'][$perk->key] = [
                            'sorcery_spell_damage_multiplier' => $sorcerySpellDamageMultiplier,
                            'spell_damage_multiplier' => $spellDamageMultiplier,
                            'damage' => $damage,
                            'damage_dealt' => $damageDealt,
                            'military_spies' => $target->military_spies,
                        ];
                    }

                    if($perk->key === 'destroy_resource')
                    {
                        $resourceKey = $spellPerkValues[0];
                        $resource = Resource::where('key', $resourceKey)->firstOrFail();
                        $baseDamage = (float)$spellPerkValues[1] / 100;

                        $multipliers = $this->sorceryCalculator->getMultipliers($caster, $target, $spell, $wizardStrength, $enhancementResource, $enhancementAmount, $perk->key, ('resource_'.$resourceKey));

                        $sorcerySpellDamageMultiplier = $multipliers['sorcerySpellDamageMultiplier'];
                        $spellDamageMultiplier =  $multipliers['spellDamageMultiplier'];

                        $damage = $baseDamage * $sorcerySpellDamageMultiplier * $spellDamageMultiplier;

                        $targetResourceAmount = $this->resourceCalculator->getAmount($target, $resourceKey);

                        $damageDealt = min($targetResourceAmount * $damage, $targetResourceAmount);
                        $damageDealt = floor($damageDealt);

                        $this->resourceService->updateResources($target, [$resourceKey => $damageDealt*-1]);

                        $this->statsService->updateStat($caster, ('sorcery_' . $resourceKey . '_destroyed'), $damage);

                        $this->statsService->updateStat($target, ('sorcery_' . $resourceKey . '_lost'), $damage);

                        $this->sorcery['damage'][$perk->key] = [
                            'sorcery_spell_damage_multiplier' => $sorcerySpellDamageMultiplier,
                            'spell_damage_multiplier' => $spellDamageMultiplier,
                            'damage' => $damage,
                            'damage_dealt' => $damageDealt,
                            'resource_key' => $resourceKey,
                            'resource_name' => $resource->name,
                            'target_resource_amount' => $targetResourceAmount,
                        ];

                        $verb = 'destroys';
                    }

                    if($perk->key === 'kill_faction_units_percentage')
                    {
                        $faction = $spellPerkValues[0];
                        $slot = (int)$spellPerkValues[1];
                        $baseDamage = (float)$spellPerkValues[2] / 100;

                        if($target->race->name !== $faction)
                        {
                            $baseDamage = 0;
                        }

                        $multipliers = $this->sorceryCalculator->getMultipliers($caster, $target, $spell, $wizardStrength, $enhancementResource, $enhancementAmount, $perk->key, ('resource_'.$resourceKey));

                        $sorcerySpellDamageMultiplier = $multipliers['sorcerySpellDamageMultiplier'];
                        $spellDamageMultiplier =  $multipliers['spellDamageMultiplier'];

                        $damage = $baseDamage * $sorcerySpellDamageMultiplier * $spellDamageMultiplier;

                        $damageDealt = min($target->{'military_unit'.$slot} * $damage * $this->resourceCalculator->getAmount($target, $resourceKey));
                        $damageDealt = floor($damageDealt);

                        $targetUnitAmount = $target->{'military_unit'.$slot};
                        $target->{'military_unit'.$slot} -= $damageDealt;

                        $unit = $target->race->units->filter(function ($unit) use ($slot) {
                            return ($unit->slot == $slot);
                        })->first();

                        $this->statsService->updateStat($caster, 'units_killed', $damageDealt);
                        $this->statsService->updateStat($caster, 'sorcery_units_killed', $damageDealt);
                        $this->statsService->updateStat($target, ('unit' . $slot . '_lost'), $damageDealt);
                        $this->statsService->updateStat($target, 'sorcery_units_lost', $damageDealt);

                        $this->sorcery['damage'][$perk->key] = [
                            'sorcery_spell_damage_multiplier' => $sorcerySpellDamageMultiplier,
                            'spell_damage_multiplier' => $spellDamageMultiplier,
                            'damage' => $damage,
                            'damage_dealt' => $damageDealt,
                            'unit_name' => $unit->name,
                            'target_unit_amount' => $targetUnitAmount,
                        ];

                    }

                    # Decrease morale
                    if($perk->key === 'decrease_morale')
                    {
                        $baseDamage = (float)$spellPerkValues / 100;

                        $multipliers = $this->sorceryCalculator->getMultipliers($caster, $target, $spell, $wizardStrength, $enhancementResource, $enhancementAmount, $perk->key, ('resource_'.$resourceKey));

                        $sorcerySpellDamageMultiplier = $multipliers['sorcerySpellDamageMultiplier'];
                        $spellDamageMultiplier =  $multipliers['spellDamageMultiplier'];

                        $damage = $baseDamage * $sorcerySpellDamageMultiplier * $spellDamageMultiplier;

                        $damageDealt = min($target->morale * $damage, $target->morale);
                        $damageDealt = floor($damageDealt);
                        $moraleBefore = $target->morale;
                        $target->morale -= $damageDealt;
                        $moraleAfter = $target->morale;

                        $this->statsService->updateStat($caster, 'sorcery_damage_morale', $damage);

                        $this->sorcery['damage'][$perk->key] = [
                            'sorcery_spell_damage_multiplier' => $sorcerySpellDamageMultiplier,
                            'spell_damage_multiplier' => $spellDamageMultiplier,
                            'damage' => $damage,
                            'damage_dealt' => $damageDealt,
                            'target_morale_before' => $moraleBefore,
                            'target_morale_after' => $moraleAfter,
                        ];
                    }

                    if($perk->key === 'improvements_damage')
                    {
                        $baseDamage = (float)$spellPerkValues / 100;

                        $totalImprovementPoints = $this->improvementCalculator->getDominionImprovementTotalAmountInvested($target);
                        $targetImprovements = $this->improvementCalculator->getDominionImprovements($target);

                        $multipliers = $this->sorceryCalculator->getMultipliers($caster, $target, $spell, $wizardStrength, $enhancementResource, $enhancementAmount, $perk->key, 'improvements');

                        $sorcerySpellDamageMultiplier = $multipliers['sorcerySpellDamageMultiplier'];
                        $spellDamageMultiplier =  $multipliers['spellDamageMultiplier'];

                        $damage = $baseDamage * $sorcerySpellDamageMultiplier * $spellDamageMultiplier;

                        $damageDealt = min($totalImprovementPoints * $damage, $totalImprovementPoints);
                        $damageDealt = floor($damageDealt);

                        if($damageDealt > 0)
                        {
                            foreach($targetImprovements as $targetImprovement)
                            {
                                $improvement = Improvement::where('id', $targetImprovement->improvement_id)->first();
                                $improvementDamage[$improvement->key] = floor($damageDealt * ($this->improvementCalculator->getDominionImprovementAmountInvested($target, $improvement) / $totalImprovementPoints));
                            }

                            $this->improvementCalculator->decreaseImprovements($target, $improvementDamage);
                        }

                        $this->statsService->updateStat($caster, 'sorcery_damage_improvements', $damageDealt);

                        #$result[] = sprintf('%s %s', number_format($damageDealt), dominion_attr_display('improvement', $damage));

                        $this->sorcery['damage'][$perk->key] = [
                            'sorcery_spell_damage_multiplier' => $sorcerySpellDamageMultiplier,
                            'spell_damage_multiplier' => $spellDamageMultiplier,
                            'damage' => $damage,
                            'damage_dealt' => $damageDealt,
                            'total_improvement_points' => $totalImprovementPoints
                        ];
                    }

                    if($perk->key === 'resource_theft')
                    {
                        $resourceKey = $spellPerkValues[0];
                        $resource = Resource::where('key', $resourceKey)->first();

                        $sorcerySpellDamageMultiplier = $this->sorceryCalculator->getSpellDamageMultiplier($caster, $target, $spell, $wizardStrength, $enhancementResource, $enhancementAmount, $perk->key);
                        $spellDamageMultiplier = $this->sorceryCalculator->getDominionHarmfulSpellDamageModifier($target, $caster, $spell, 'theft');

                        $baseDamage = (float)$spellPerkValues[1] / 100;
                        $theftRatio = $baseDamage * $sorcerySpellDamageMultiplier * $spellDamageMultiplier;

                        $damageDealt = $this->getTheftAmount($caster, $target, $spell, $resourceKey, $theftRatio);

                        $this->resourceService->updateResources($target, [$resourceKey => $damageDealt*-1]);
                        $this->resourceService->updateResources($caster, [$resourceKey => $damageDealt]);

                        $this->statsService->updateStat($caster, ($resourceKey .  '_stolen'), $damageDealt);
                        $this->statsService->updateStat($target, ($resourceKey . '_lost'), $damageDealt);

                        $this->sorcery['damage'][$perk->key] = [
                            'sorcery_spell_damage_multiplier' => $sorcerySpellDamageMultiplier,
                            'spell_damage_multiplier' => $spellDamageMultiplier,
                            'damage' => $theftRatio,
                            'theft_ratio' => $theftRatio,
                            'damage_dealt' => $damageDealt,
                            'resource_key' => $resourceKey,
                            'resource_name' => $resource->name
                        ];
                    }
                }
                #END PERK FOREACH

                # BEGIN COOLDOWN
                if($spell->cooldown > 0)
                {
                    # But has it already been cast and is sitting at zero-tick cooldown?
                    if(DominionSpell::where(['dominion_id' => $caster->id, 'spell_id' => $spell->id, 'cooldown' => 0])->get()->count())
                    {
                        DB::transaction(function () use ($caster, $target, $spell)
                        {
                          DominionSpell::where('dominion_id', $caster->id)->where('spell_id', $spell->id)
                          ->update(['cooldown' => $spell->cooldown]);
                        });
                    }
                    else
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
                        });
                    }
                }
                # END COOLDOWN
            }

            // Update stats
            $this->statsService->updateStat($caster, 'sorcery_cast', 1);
            $this->statsService->updateStat($caster, 'sorcery_mana', $manaCost);
            $this->statsService->updateStat($caster, 'sorcery_wizard_strength', $wizardStrength);

            // Remove mana
            $this->resourceService->updateResources($caster, ['mana' => $manaCost*-1]);
            $this->statsService->updateStat($caster, 'mana_cast', $manaCost);

            // Remove wizard strength
            $caster->wizard_strength -= $wizardStrength;

            ldd($this->sorcery);

            // Create event
            $this->sorceryEvent = GameEvent::create([
                'round_id' => $caster->round_id,
                'source_type' => Dominion::class,
                'source_id' => $caster->id,
                'target_type' => Dominion::class,
                'target_id' => $target->id,
                'type' => 'sorcery',
                'data' => $this->sorcery,
                'tick' => $caster->round->ticks
            ]);

            // Queue up notifications
            $this->notificationService->queueNotification('sorcery', [
                '_routeParams' => [(string)$this->sorceryEvent->id],
                'caster_dominion_id' => $caster->id,
                'data' => $this->sorcery,
            ]);

            // Unclear if necessary
            $target->save([
                'event' => HistoryService::EVENT_ACTION_SORCERY,
                'action' => $spell->key
            ]);

            $caster->save([
                'event' => HistoryService::EVENT_ACTION_SORCERY,
                'action' => $spell->key
            ]);
        });

        $this->notificationService->sendNotifications($target, 'irregular_dominion');

        $message = sprintf(
            'You perform %s sorcery on %s (#%s)!',
            $spell->name,
            $target->name,
            $target->realm->number
        );

        $alertType = 'success';

        return [
            'message' => $message,
            'alert-type' => $alertType,
            'redirect' => route('dominion.event', [$this->sorceryEvent->id])
        ];

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

    protected function getTheftAmount(Dominion $dominion, Dominion $target, Spell $spell, string $resourceKey, float $ratio): int
    {
        if($spell->scope !== 'hostile')
        {
            return 0;
        }

        if($resourceKey == 'draftees')
        {
            $resourceString = 'military_draftees';
            $availableResource = $target->military_draftees;
        }
        elseif($resourceKey == 'peasants')
        {
            $resourceString = 'peasants';
            $availableResource = $target->peasants;
        }
        else
        {
            $resourceString = 'resource_'.$resourceKey;
            $availableResource = $this->resourceCalculator->getAmount($target, $resourceKey);
        }

        // Unit theft protection
        for ($slot = 1; $slot <= $target->race->units->count(); $slot++)
        {
            if($theftProtection = $target->race->getUnitPerkValueForUnitSlot($slot, 'protects_resource_from_theft'))
            {
                if($theftProtection[0] == $resourceKey)
                {
                    $availableResource -= $target->{'military_unit'.$slot} * $theftProtection[1];
                }
            }
        }

        $theftAmount = min($availableResource * $ratio, $availableResource);

        return max(0, $theftAmount);
    }

  }
