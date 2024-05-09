<?php

// We want strict types here.
declare(strict_types=1);

namespace OpenDominion\Calculators\Dominion;

use DB;
use Illuminate\Support\Str;
use OpenDominion\Models\Dominion\Tick;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Spell;

use OpenDominion\Calculators\Dominion\ConversionCalculator;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\MoraleCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SorceryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\UnitCalculator;

use OpenDominion\Services\Dominion\HistoryService;


class TickCalculator
{
    protected $conversionCalculator;
    protected $espionageCalculator;
    protected $moraleCalculator;
    protected $populationCalculator;
    protected $productionCalculator;
    protected $resourceCalculator;
    protected $sorceryCalculator;
    protected $spellCalculator;
    protected $unitCalculator;


    public function __construct()
    {
        $this->conversionCalculator = app(ConversionCalculator::class);
        $this->espionageCalculator = app(EspionageCalculator::class);
        $this->moraleCalculator = app(MoraleCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->productionCalculator = app(ProductionCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->sorceryCalculator = app(SorceryCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->unitCalculator = app(UnitCalculator::class);
    }

    public function precalculateTick(Dominion $dominion, ?bool $saveHistory = false): void
    {
        /** @var Tick $tick */
        $tick = Tick::firstOrCreate(['dominion_id' => $dominion->id]);

        if ($saveHistory)
        {
            // Save a dominion history record
            $dominionHistoryService = app(HistoryService::class);

            $changes = array_filter($tick->getAttributes(), static function ($value, $key)
            {
                return (
                    !in_array($key, [
                        'id',
                        'dominion_id',
                        'created_at',
                        'updated_at'
                    ], true) &&
                    ($value != 0) // todo: strict type checking?
                );
            }, ARRAY_FILTER_USE_BOTH);

            $dominionHistoryService->record($dominion, $changes, HistoryService::EVENT_TICK);
        }

        // Reset tick values — I don't understand this. WaveHack magic. Leave (mostly) intact, only adapt, don't refactor.
        foreach ($tick->getAttributes() as $attr => $value)
        {
            # Values that become 0
            $zeroArray = [
                'id',
                'dominion_id',
                'updated_at',
                'pestilence_units',
                'generated_land',
                'generated_unit1',
                'generated_unit2',
                'generated_unit3',
                'generated_unit4',
                'generated_unit5',
                'generated_unit6',
                'generated_unit7',
                'generated_unit8',
                'generated_unit9',
                'generated_unit10',
            ];

            # Values that become []
            $emptyArray = [
                'starvation_casualties',
                'pestilence_units',
                'generated_land',
                'generated_unit1',
                'generated_unit2',
                'generated_unit3',
                'generated_unit4',
                'generated_unit5',
                'generated_unit6',
                'generated_unit4',
                'generated_unit7',
                'generated_unit8',
                'generated_unit9',
                'generated_unit10',
                'buildings_destroyed',
            ];

            #if (!in_array($attr, ['id', 'dominion_id', 'updated_at', 'pestilence_units', 'generated_land', 'generated_unit1', 'generated_unit2', 'generated_unit3', 'generated_unit4'], true))
            if (!in_array($attr, $zeroArray, true))
            {
                  $tick->{$attr} = 0;
            }
            #elseif (in_array($attr, ['starvation_casualties', 'pestilence_units', 'generated_land', 'generated_unit1', 'generated_unit2', 'generated_unit3', 'generated_unit4'], true))
            elseif (in_array($attr, $emptyArray, true))
            {
                  $tick->{$attr} = [];
            }
        }

        // Hacky refresh for dominion
        $dominion->refresh();

        // Define the excluded sources
        $excludedSources = ['construction', 'repair', 'restore', 'deity', 'artefact', 'research', 'rezoning'];

        // Get the incoming queue
        $incomingQueue = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->whereNotIn('source', $excludedSources)
            ->where('hours', '=', 1)
            ->get();

        foreach ($incomingQueue as $row)
        {
            // Check if the resource is not a 'resource_' or 'terrain_'
            if (!Str::startsWith($row->resource, ['resource_', 'terrain_'])) {
                $tick->{$row->resource} += $row->amount;
                // Temporarily add next hour's resources for accurate calculations
                $dominion->{$row->resource} += $row->amount;
            }
        }

        /*
        // Queues
        $incomingQueue = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('source', '!=', 'construction')
            ->where('source', '!=', 'repair')
            ->where('source', '!=', 'restore')
            ->where('hours', '=', 1)
            ->get();

        foreach ($incomingQueue as $row)
        {
            if(
                    $row->source !== 'deity'
                    and $row->source !== 'artefact'
                    and $row->source !== 'research'
                    and $row->source !== 'rezoning'
                    and substr($row->resource, 0, strlen('resource_')) !== 'resource_'
                    and substr($row->resource, 0, strlen('terrain_')) !== 'terrain_'
            )
            {
                $tick->{$row->resource} += $row->amount;
                // Temporarily add next hour's resources for accurate calculations
                $dominion->{$row->resource} += $row->amount;
            }
        }
        */

        $tick->protection_ticks = 0;
        // Tick
        if($dominion->protection_ticks > 0)
        {
            $tick->protection_ticks += -1;
        }

        // Population
        $drafteesGrowthRate = $this->populationCalculator->getPopulationDrafteeGrowth($dominion);
        $populationPeasantGrowth = $this->populationCalculator->getPopulationPeasantGrowth($dominion);

        if ($dominion->hasSpell('pestilence')) {
            $populationPeasantGrowth -= $this->handlePestilenceEffect($dominion, 'pestilence');
        } elseif ($dominion->hasSpell('lesser_pestilence')) {
            $populationPeasantGrowth -= $this->handlePestilenceEffect($dominion, 'lesser_pestilence');
        }

        # Check for peasants_conversion
        if($peasantConversionData = $dominion->getBuildingPerkValue('peasants_conversion'))
        {
            $multiplier = 1;
            $multiplier += $dominion->getSpellPerkMultiplier('peasants_converted');
            $multiplier += $dominion->getBuildingPerkMultiplier('peasants_converted');
            $multiplier += $dominion->getImprovementPerkMultiplier('peasants_converted');

            $populationPeasantGrowth -= $peasantConversionData['from']['peasants'];
        }
        # Check for peasants_conversions
        if($peasantConversionsData = $dominion->getBuildingPerkValue('peasants_conversions'))
        {
            $multiplier = 1;
            $multiplier += $dominion->getSpellPerkMultiplier('peasants_converted');
            $multiplier += $dominion->getBuildingPerkMultiplier('peasants_converted');
            $multiplier += $dominion->getImprovementPerkMultiplier('peasants_converted');

            $populationPeasantGrowth -= $peasantConversionsData['from']['peasants'];
        }
        # Check for units with peasants_conversions
        $peasantsConvertedByUnits = 0;
        foreach($dominion->race->units as $unit)
        {
            if($unitPeasantsConversionPerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'peasants_conversions'))
            {
                $multiplier = 1;
                $multiplier += $dominion->getSpellPerkMultiplier('peasants_converted');
                $multiplier += $dominion->getBuildingPerkMultiplier('peasants_converted');
                $multiplier += $dominion->getImprovementPerkMultiplier('peasants_converted');

                $peasantsConvertedByUnits += $unitPeasantsConversionPerk[0] * $dominion->{'military_unit' . $unit->slot} * $multiplier;
            }

            if($unitPeasantsConversionPerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'peasants_to_unit_conversions'))
            {
                $peasantsConvertedByUnits += $unitPeasantsConversionPerk[0] * $dominion->{'military_unit' . $unit->slot};
            }


        }
        $populationPeasantGrowth -= (int)round($peasantsConvertedByUnits);

        if(($dominion->peasants + $tick->peasants) <= 0)
        {
            $tick->peasants = ($dominion->peasants)*-1;
        }

        $tick->peasants = $populationPeasantGrowth;

        $tick->peasants_sacrificed = 0;

        $tick->military_draftees = $drafteesGrowthRate;

        // Production/generation
        $tick->xp += $this->productionCalculator->getXpGeneration($dominion);
        $tick->prestige += $this->productionCalculator->getPrestigeInterest($dominion);

        // Starvation
        $tick->starvation_casualties = false;

        if($this->resourceCalculator->canStarve($dominion->race))
        {
            #$foodProduction = $this->resourceCalculator->getProduction($dominion, 'food');
            $foodConsumed = $this->resourceCalculator->getConsumption($dominion, 'food');
            #$foodNetChange = $foodProduction - $foodConsumed;
            $foodNetChange = $this->resourceCalculator->getNetProduction($dominion, 'food');
            $foodOwned = $dominion->resource_food;


            if($foodConsumed > 0 and ($foodOwned + $foodNetChange) < 0)
            {
                $dominion->tick->starvation_casualties = true;
            }
        }

        // Morale
        $baseMorale = $this->moraleCalculator->getBaseMorale($dominion);
        $moraleChangeModifier = $this->moraleCalculator->moraleChangeModifier($dominion);

        if(($tick->starvation_casualties or $dominion->tick->starvation_casualties) and $this->resourceCalculator->canStarve($dominion->race))
        {
            $starvationMoraleChange = min(10, $dominion->morale)*-1;
            $tick->morale += $starvationMoraleChange;
        }
        else
        {
            if ($dominion->morale < 35)
            {
                $tick->morale = 7;
            }
            elseif ($dominion->morale < 70)
            {
                $tick->morale = 6;
            }
            elseif ($dominion->morale < $baseMorale)
            {
                $tick->morale = min(3, $baseMorale - $dominion->morale);
            }
            elseif($dominion->morale > $baseMorale)
            {
                $tick->morale -= min(2 * $moraleChangeModifier, $dominion->morale - $baseMorale);
            }
        }

        $spyStrengthBase = $this->espionageCalculator->getSpyStrengthBase($dominion);
        $wizardStrengthBase = $this->spellCalculator->getWizardStrengthBase($dominion);

        // Spy Strength
        if ($dominion->spy_strength < $spyStrengthBase)
        {
            $tick->spy_strength =  min($this->espionageCalculator->getSpyStrengthRecoveryAmount($dominion), $spyStrengthBase - $dominion->spy_strength);
        }

        // Wizard Strength
        if ($dominion->wizard_strength < $wizardStrengthBase)
        {
            $tick->wizard_strength =  min($this->spellCalculator->getWizardStrengthRecoveryAmount($dominion), $wizardStrengthBase - $dominion->wizard_strength);
        }

        # Tickly unit perks
        $generatedLand = $this->unitCalculator->getUnitLandGeneration($dominion);

        # Imperial Crypt: Rites of Zidur, Rites of Kinthys
        $tick->crypt_bodies_spent = 0;
        
        $unitsGenerated = $this->unitCalculator->getUnitsGenerated($dominion);
        $unitsAttrited = $this->unitCalculator->getUnitsAttrited($dominion);

        # Passive conversions
        $passiveConversions = $this->conversionCalculator->getPassiveConversions($dominion);
        if((array_sum($passiveConversions['units_converted']) + array_sum($passiveConversions['units_removed'])) > 0)
        {
            $unitsConverted = $passiveConversions['units_converted'];
            $unitsRemoved = $passiveConversions['units_removed'];

            foreach($dominion->race->units as $unit)
            {
                $unitsGenerated[$unit->slot] += $unitsConverted[$unit->slot];
                $unitsAttrited[$unit->slot] += $unitsRemoved[$unit->slot];
            }
        }
        
        # Use decimals as probability to round up
        $tick->generated_land += intval($generatedLand) + (rand()/getrandmax() < fmod($generatedLand, 1) ? 1 : 0);

        foreach($dominion->race->units as $unit)
        {
            $tick->{'generated_unit' . $unit->slot} += intval($unitsGenerated[$unit->slot]) + (rand()/getrandmax() < fmod($unitsGenerated[$unit->slot], 1) ? 1 : 0);
            $tick->{'attrition_unit' . $unit->slot} += intval($unitsAttrited[$unit->slot]);
        }

        # Handle building self-destruct
        if($selfDestruction = $dominion->getBuildingPerkValue('destroys_itself_and_land'))
        {
            $buildingKey = (string)$selfDestruction['building_key'];
            $amountToDestroy = (int)$selfDestruction['amount'];
            $landType = (string)$selfDestruction['land_type'];

            if($amountToDestroy > 0)
            {
                $tick->{'land_'.$landType} -= min($amountToDestroy, $dominion->{'land_'.$landType});
                $tick->buildings_destroyed = [$buildingKey => ['builtBuildingsToDestroy' => $amountToDestroy]];
            }
        }
        if($selfDestruction = $dominion->getBuildingPerkValue('destroys_itself'))
        {
            $buildingKey = (string)$selfDestruction['building_key'];
            $amountToDestroy = (int)$selfDestruction['amount'];

            if($amountToDestroy > 0)
            {
                $tick->buildings_destroyed = [$buildingKey => ['builtBuildingsToDestroy' => $amountToDestroy]];
            }
        }

        foreach ($incomingQueue as $row)
        {
            if(
                $row->source !== 'deity'
                and $row->source !== 'research'
                and $row->source !== 'artefact'
                and substr($row->resource, 0, strlen('resource_')) !== 'resource_'
                and substr($row->resource, 0, strlen('terrain_')) !== 'terrain_'
            )
                
            {
                // Reset current resources in case object is saved later
                $dominion->{$row->resource} -= $row->amount;
            }
        }

        $tick->save();
    }

    private function handlePestilenceEffect(Dominion $dominion, string $spellKey): int
    {
        $spell = Spell::fromKey($spellKey);
        $pestilence = $spell->getActiveSpellPerkValues($spellKey, 'kill_peasants_and_converts_for_caster_unit');
        $ratio = $pestilence[0] / 100;
    
        $amountToDie = $dominion->peasants * $ratio * $this->sorceryCalculator->getDominionHarmfulSpellDamageModifier($dominion, null, $spell, null);
        $amountToDie *= $this->conversionCalculator->getConversionReductionMultiplier($dominion);
        $amountToDie = (int)floor($amountToDie);
    
        return $amountToDie;
    }

}
