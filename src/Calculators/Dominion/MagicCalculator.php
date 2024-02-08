<?php

namespace OpenDominion\Calculators\Dominion;

use Log;

use Illuminate\Support\Collection;

use OpenDominion\Services\Dominion\QueueService;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Spell;

class MagicCalculator
{

    /** @var QueueService */
    protected $queueService;

    public function __construct()
    {
        $this->queueService = app(QueueService::class);
    }

    /**
     * Returns the Dominion's wizard ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatio(Dominion $dominion, string $type = 'offense'): float
    {
        return ($this->getWizardRatioRaw($dominion, $type) * $this->getWizardRatioMultiplier($dominion, $type) * (0.9 + $dominion->wizard_strength / 1000));
    }

    /**
     * Returns the Dominion's raw wizard ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatioRaw(Dominion $dominion, string $type = 'offense'): float
    {
        $wizards = $this->getTotalUnitsForSlot($dominion, 'wizards');
        $wizards += $this->getTotalUnitsForSlot($dominion, 'archmages') * 2;

        $wizards += $dominion->race->getPerkValue('draftees_count_as_wizards') * $dominion->military_draftees;

        // Add units which count as (partial) spies (Dark Elf Adept)
        foreach ($dominion->race->units as $unit)
        {
            if ($type === 'offense' && $countsAsWizardOffensePerk = $unit->getPerkValue('counts_as_wizard_offense'))
            {
                $wizards += floor($dominion->{"military_unit{$unit->slot}"} * $countsAsWizardOffensePerk);
            }

            if ($type === 'defense' && $countsAsWizardDefensePerk = $unit->getPerkValue('counts_as_wizard_defense'))
            {
                $wizards += floor($dominion->{"military_unit{$unit->slot}"} * $countsAsWizardDefensePerk);
            }

            if ($countsAsWizardPerk = $unit->getPerkValue('counts_as_wizard'))
            {
                $wizards += floor($dominion->{"military_unit{$unit->slot}"} * $countsAsWizardPerk);
            }

            if ($timePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, ("counts_as_wizard_" . $type . "_from_time"), null))
            {
                $powerFromTime = (float)$timePerkData[2];
                $hourFrom = $timePerkData[0];
                $hourTo = $timePerkData[1];
                if (
                    (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                    (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                )
                {
                    $wizards += floor($dominion->{"military_unit{$unit->slot}"} * $powerFromTime);
                }
            }

            if ($landPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, ("counts_as_wizard_from_terrain"), null))
            {
                $power = (float)$landPerkData[0];
                $ratio = (float)$landPerkData[1];
                $terrainKey = (string)$landPerkData[2];

                $wizards += $dominion->{"military_unit{$unit->slot}"} * (((($dominion->{'terrain_' . $terrainKey} / $dominion->land) * 100) / $ratio) * $power);
            }

            if ($terrainPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, ("counts_as_wizard_on_" . $type ."_from_terrain"), null))
            {
                $power = (float)$terrainPerkData[0];
                $ratio = (float)$terrainPerkData[1];
                $terrainKey = (string)$terrainPerkData[2];


                $wizards += $dominion->{"military_unit{$unit->slot}"} * (((($dominion->{'terrain_' . $terrainKey} / $dominion->land) * 100) / $ratio) * $power);
            }

            # Check for wizard_from_title
            $titlePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "wizard_from_title", null);
            if($titlePerkData)
            {
                $titleKey = $titlePerkData[0];
                $titlePower = $titlePerkData[1];
                if($dominion->title->key == $titleKey)
                {
                    $wizards += floor($dominion->{"military_unit{$unit->slot}"} * (float) $titlePower);
                }
            }
        }

        return ($wizards / $dominion->land);
    }

    /**
     * Returns the Dominion's wizard ratio multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatioMultiplier(Dominion $dominion, string $type = 'offense'): float
    {
        $multiplier = 0;

        // Deity
        $multiplier += $dominion->getDeityPerkMultiplier('wizard_strength');

        // Decree
        $multiplier += $dominion->getDecreePerkMultiplier('wizard_strength');

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('wizard_strength');

        // Improvements
        $multiplier += $dominion->getImprovementPerkMultiplier('wizard_strength');

        // Advancement
        $multiplier += $dominion->getAdvancementPerkMultiplier('wizard_strength');

        // Tech
        $multiplier += $dominion->getTechPerkMultiplier('wizard_strength');
        $multiplier += $dominion->getTechPerkMultiplier('wizard_strength_on_' . $type);

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('wizard_strength');
        $multiplier += $dominion->getBuildingPerkMultiplier('wizard_strength_on_' . $type);

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('wizard_strength');
        $multiplier += $dominion->getBuildingPerkMultiplier('wizard_strength_on_' . $type);

        // Title
        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('wizard_strength') * $dominion->getTitlePerkMultiplier();
        }

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's wizard strength regeneration.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardStrengthRegen(Dominion $dominion): float
    {
        $regen = 5;

        // todo: Master of Magi / Dark Artistry tech
        // todo: check if this needs to be a float

        return (float)$regen;
    }

    /**
     * Returns the Dominion's raw wizard ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardPoints(Dominion $dominion, string $type = 'offense'): float
    {
        $wizardPoints = $dominion->military_wizards + ($dominion->military_archmages * 2);

        // Add units which count as (partial) spies (Dark Elf Adept)
        foreach ($dominion->race->units as $unit)
        {
            if ($type === 'offense' && $unit->getPerkValue('counts_as_wizard_offense'))
            {
                $wizardPoints += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_wizard_offense'));
            }

            if ($type === 'defense' && $unit->getPerkValue('counts_as_wizard_defense'))
            {
                $wizardPoints += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_wizard_defense'));
            }

            if ($unit->getPerkValue('counts_as_wizard'))
            {
                $wizardPoints += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_wizard'));
            }

            if ($timePerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, ("counts_as_wizard_" . $type . "_from_time"), null))
            {
                $powerFromTime = (float)$timePerkData[2];
                $hourFrom = $timePerkData[0];
                $hourTo = $timePerkData[1];
                if (
                    (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                    (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                )
                {
                    $wizardPoints += floor($dominion->{"military_unit{$unit->slot}"} * $powerFromTime);
                }
            }
        }

        return $wizardPoints * $this->getWizardRatioMultiplier($dominion, $type);
    }

    public function getMagicLevel(Dominion $dominion): int
    {

        $magicLevel[] = $dominion->race->magic_level;
        $magicLevel[] = $dominion->getAdvancementPerkValue('magic_level');
        $magicLevel[] = $dominion->getTechPerkValue('magic_level');
        $magicLevel[] = $dominion->getDecreePerkValue('magic_level_extra');

        $magicLevel = max($magicLevel);

        # Additive perks
        $magicLevel += $dominion->getSpellPerkMultiplier('magic_level_extra');
        $magicLevel += $dominion->getTechPerkValue('magic_level_extra');
        $magicLevel += $dominion->getAdvancementPerkValue('magic_level_extra');
        $magicLevel += $dominion->getDecreePerkValue('magic_level_extra');

        return max(0, $magicLevel);
    }

    public function getLevelSpells(Dominion $dominion, int $level = 0)
    {

        $spells = Spell::where('enabled', 1)
        ->where('scope', 'self')
        ->where('magic_level', $level)
        ->get()
        ->filter(function ($spell) use ($dominion) {
            // Check excluded_races
            if (!empty($spell->excluded_races) && in_array($dominion->race->name, $spell->excluded_races))
            {
                return false;
            }
    
            // Check exclusive_races
            if (!empty($spell->exclusive_races) && !in_array($dominion->race->name, $spell->exclusive_races))
            {
                return false;
            }
    
            // Check deity
            if ($spell->deity && (!$dominion->hasDeity() or $spell->deity->key !== $dominion->deity->key))
            {
                return false;
            }
    
            return true;
        })
        ->sortBy('name');

        return $spells;

    }

    public function getSpells(Dominion $dominion)
    {
        $spells = new Collection();

        for ($i = 0; $i <= $this->getMagicLevel($dominion); $i++)
        {
            $spells = $spells->merge($this->getLevelSpells($dominion, $i));
        }

        return $spells;    
    }

    public function getTotalUnitsForSlot(Dominion $dominion, $slot): int
    {
        if(is_int($slot))
        {
            return (
                $dominion->{'military_unit' . $slot} +
                $this->queueService->getInvasionQueueTotalByResource($dominion, "military_unit{$slot}") +
                $this->queueService->getExpeditionQueueTotalByResource($dominion, "military_unit{$slot}") +
                $this->queueService->getTheftQueueTotalByResource($dominion, "military_unit{$slot}") +
                $this->queueService->getDesecrationQueueTotalByResource($dominion, "military_unit{$slot}") +
                $this->queueService->getStunQueueTotalByResource($dominion, "military_unit{$slot}") +
                $this->queueService->getSabotageQueueTotalByResource($dominion, "military_unit{$slot}") +
                $this->queueService->getArtefactQueueTotalByResource($dominion, "military_unit{$slot}")
            );
        }
        elseif(in_array($slot, ['draftees', 'spies', 'wizards', 'archmages']))
        {
            return (
                $dominion->{'military_' . $slot} +
                $this->queueService->getInvasionQueueTotalByResource($dominion, "military_{$slot}") +
                $this->queueService->getExpeditionQueueTotalByResource($dominion, "military_{$slot}") +
                $this->queueService->getTheftQueueTotalByResource($dominion, "military_{$slot}") +
                $this->queueService->getDesecrationQueueTotalByResource($dominion, "military_{$slot}") +
                $this->queueService->getStunQueueTotalByResource($dominion, "military_{$slot}") +
                $this->queueService->getSabotageQueueTotalByResource($dominion, "military_{$slot}") +
                $this->queueService->getArtefactQueueTotalByResource($dominion, "military_{$slot}")
            );
        }
        else
        {
            return 0;
        }
    }

    public function getTimesSpellCastByDominion(Dominion $dominion, Spell $spell): int
    {
        return $dominion->history
            ->filter(function ($historyItem) use ($spell) {
                if ($historyItem->event !== 'cast spell') {
                    return false;
                }
    
                // Assuming $historyItem->delta is already an array
                $delta = $historyItem->delta;
                return isset($delta['action']) && $delta['action'] === $spell->key;
            })
            ->count();
    }
    
    

}
