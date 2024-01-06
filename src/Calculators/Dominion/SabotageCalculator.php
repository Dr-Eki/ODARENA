<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Spyop;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Helpers\SabotageHelper;

use OpenDominion\Calculators\Dominion\ConversionCalculator;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;

class SabotageCalculator
{
    protected $conversionCalculator;
    protected $espionageCalculator;
    protected $landCalculator;
    protected $militaryCalculator;
    protected $resourceCalculator;

    protected $buildingHelper;
    protected $sabotageHelper;
    protected $unitHelper;

    public function __construct(
            BuildingHelper $buildingHelper,
            SabotageHelper $sabotageHelper,
            UnitHelper $unitHelper,

            ConversionCalculator $conversionCalculator,
            EspionageCalculator $espionageCalculator,
            LandCalculator $landCalculator,
            MilitaryCalculator $militaryCalculator,
            ResourceCalculator $resourceCalculator
        )
    {
        $this->buildingHelper = $buildingHelper;
        $this->sabotageHelper = $sabotageHelper;
        $this->unitHelper = $unitHelper;

        $this->conversionCalculator = $conversionCalculator;
        $this->espionageCalculator = $espionageCalculator;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->resourceCalculator = $resourceCalculator;
    }

    public function getRatioMultiplier(Dominion $saboteur, Dominion $target, Spyop $spyop, string $attribute, array $units, bool $forCalculator = false): float
    {
        if($forCalculator and $target->getSpellPerkValue('fog_of_war'))
        {
            return 0;
        }

        $saboteurSpa = max($this->militaryCalculator->getSpyRatio($saboteur, 'offense'), 0.0001);
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');
        $spaSpaRatio = max(min((1-(($targetSpa / $saboteurSpa) * 0.5)),1),0);

        return $spaSpaRatio;
    }

    public function getTargetDamageMultiplier(Dominion $target, string $attribute): float
    {
        $multiplier = 1;

        $multiplier += $target->getBuildingPerkMultiplier('sabotage_damage_suffered');
        $multiplier += $target->getBuildingPerkMultiplier($attribute . '_sabotage_damage_suffered');

        $multiplier += $target->getImprovementPerkMultiplier('sabotage_damage_suffered');
        $multiplier += $target->getImprovementPerkMultiplier($attribute . '_sabotage_damage_suffered');

        $multiplier += $target->getSpellPerkMultiplier('sabotage_damage_suffered');
        $multiplier += $target->getSpellPerkMultiplier($attribute . '_sabotage_damage_suffered');

        $multiplier += $target->getAdvancementPerkMultiplier('sabotage_damage_suffered');
        $multiplier += $target->getAdvancementPerkMultiplier($attribute . '_sabotage_damage_suffered');

        $multiplier += $target->race->getPerkMultiplier('sabotage_damage_suffered');
        $multiplier += $target->race->getPerkMultiplier($attribute . '_sabotage_damage_suffered');

        $multiplier += $target->realm->getArtefactPerkMultiplier('sabotage_damage_suffered');
        $multiplier += $target->realm->getArtefactPerkMultiplier($attribute . '_sabotage_damage_suffered');

        $multiplier += $target->title->getPerkMultiplier('sabotage_damage_suffered') * $target->getTitlePerkMultiplier();
        $multiplier += $target->title->getPerkMultiplier($attribute . '_sabotage_damage_suffered') * $target->getTitlePerkMultiplier();

        return $multiplier;
    }

    public function getSaboteurDamageMultiplier(Dominion $saboteur, string $attribute): float
    {
        $multiplier = 1;

        $multiplier += $saboteur->getBuildingPerkMultiplier('sabotage_damage_dealt');
        $multiplier += $saboteur->getBuildingPerkMultiplier($attribute . '_sabotage_damage_dealt');

        $multiplier += $saboteur->getImprovementPerkMultiplier('sabotage_damage_dealt');
        $multiplier += $saboteur->getImprovementPerkMultiplier($attribute . '_sabotage_damage_dealt');

        $multiplier += $saboteur->getSpellPerkMultiplier('sabotage_damage_dealt');
        $multiplier += $saboteur->getSpellPerkMultiplier($attribute . '_sabotage_damage_dealt');

        $multiplier += $saboteur->getAdvancementPerkMultiplier('sabotage_damage_dealt');
        $multiplier += $saboteur->getAdvancementPerkMultiplier($attribute . '_sabotage_damage_dealt');

        $multiplier += $saboteur->race->getPerkMultiplier('sabotage_damage_dealt');
        $multiplier += $saboteur->race->getPerkMultiplier($attribute . '_sabotage_damage_dealt');

        $multiplier += $saboteur->realm->getArtefactPerkMultiplier('sabotage_damage_dealt');
        $multiplier += $saboteur->realm->getArtefactPerkMultiplier($attribute . '_sabotage_damage_dealt');

        $multiplier += $saboteur->title->getPerkMultiplier('sabotage_damage_dealt') * $saboteur->getTitlePerkMultiplier();
        $multiplier += $saboteur->title->getPerkMultiplier($attribute . '_sabotage_damage_dealt') * $saboteur->getTitlePerkMultiplier();

        return $multiplier;
    }

    public function getSabotageDamage(Dominion $saboteur, Dominion $target, Spyop $spyop, array $units, int $spyStrength): array
    {
        $damage = [];
        $totalSabotagePowerSent = $this->militaryCalculator->getUnitsSabotagePower($saboteur, $units);

        foreach($spyop->perks as $perk)
        {   
            $spyopPerkValues = $spyop->getSpyopPerkValues($spyop->key, $perk->key);

            $sabotages = [];

            if(!is_array($spyopPerkValues))
            {
                $sabotages[] = [$perk->key, $spyopPerkValues];
            }
            elseif(count($spyopPerkValues) == count($spyopPerkValues, COUNT_RECURSIVE))
            {
                $sabotages[] = [$spyopPerkValues[0], $spyopPerkValues[1]];
            }
            else
            {
                $sabotages = $spyopPerkValues;
            }

            # Handle building and buildings
            if($perk->key == 'buildings' or $perk->key == 'building')
            {
                $damageType = 'buildings';
                $damage[$damageType] = ['raw' => [], 'mod' => []];

                foreach($sabotages as $sabotage)
                {
                    $buildingKey = $sabotage[0];
                    $baseDamage = $sabotage[1];

                    # Get target buildings
                    $targetBuildings = $target->buildings->all();

                    # Look for building key in target buildings
                    foreach($targetBuildings as $targetBuilding)
                    {
                        if($targetBuilding->key == $buildingKey)
                        {
                            $damage[$damageType]['raw'][$buildingKey] = $this->getRawDamage($saboteur, $target, $baseDamage, $totalSabotagePowerSent, $spyStrength);
                            $damage[$damageType]['mod'][$buildingKey] = $damage[$damageType]['raw'][$buildingKey] * $this->getSaboteurDamageMultiplier($saboteur, $damageType) * $this->getTargetDamageMultiplier($target, $damageType);
                        }
                    }

                }
            }

            # Handle improvement and improvements
            if($perk->key == 'improvements' or $perk->key == 'improvement')
            {
                $damageType = 'improvements';
                $damage[$damageType] = ['raw' => [], 'mod' => []];

                foreach($sabotages as $sabotage)
                {
                    $improvementKey = $sabotage[0];
                    $baseDamage = $sabotage[1];

                    # Get target buildings
                    $targetImprovements = $target->improvements->all();

                    # Look for building key in target buildings
                    foreach($targetImprovements as $targetImprovement)
                    {
                        if($targetImprovement->key == $improvementKey)
                        {
                            $damage[$damageType]['raw'][$improvementKey] = $this->getRawDamage($saboteur, $target, $baseDamage, $totalSabotagePowerSent, $spyStrength);
                            $damage[$damageType]['mod'][$improvementKey] = $damage[$damageType]['raw'][$improvementKey] * $this->getSaboteurDamageMultiplier($saboteur, $damageType) * $this->getTargetDamageMultiplier($target, $damageType);
                        }
                    }
                }
            }

            # Handle resource and resources
            if($perk->key == 'resources' or $perk->key == 'resource')
            {
                $damageType = 'resources';
                $damage[$damageType] = ['raw' => [], 'mod' => []];

                foreach($sabotages as $sabotage)
                {
                    $resourceKey = $sabotage[0];
                    $baseDamage = $sabotage[1];

                    # Get target buildings
                    $targetResources = $target->resources->all();

                    # Look for building key in target buildings
                    foreach($targetResources as $targetResource)
                    {
                        if($targetResource->key == $resourceKey)
                        {
                            $damage[$damageType]['raw'][$resourceKey] = $this->getRawDamage($saboteur, $target, $baseDamage, $totalSabotagePowerSent, $spyStrength);
                            $damage[$damageType]['mod'][$resourceKey] = $damage[$damageType]['raw'][$resourceKey] * $this->getSaboteurDamageMultiplier($saboteur, $damageType) * $this->getTargetDamageMultiplier($target, $damageType);
                        }
                    }
                }
            }

            # Handle peasants, draftees, morale, spy strength, wizard strength, and construction (unfinished buildings)
            if($perk->key == 'peasants' || $perk->key == 'military_draftees' || $perk->key == 'morale' || $perk->key == 'spy_strength' || $perk->key == 'wizard_strength' || $perk->key == 'construction')
            {
                $damageType = $perk->key;
                $damage[$damageType] = ['raw' => [], 'mod' => []];

                foreach($sabotages as $sabotage)
                {
                    $attribute = $sabotage[0];
                    $baseDamage = $sabotage[1];

                    #dump('Base damage is: ' . $baseDamage);

                    $damage[$damageType]['raw'][$damageType] = $this->getRawDamage($saboteur, $target, $baseDamage, $totalSabotagePowerSent, $spyStrength);
                    $damage[$damageType]['mod'][$damageType] = $damage[$damageType]['raw'][$damageType] * $this->getSaboteurDamageMultiplier($saboteur, $damageType) * $this->getTargetDamageMultiplier($target, $damageType);
                }
            }

            # Handle convert_peasants_to_vampires_unit1
            if($perk->key == 'convert_peasants_to_vampires_unit1')
            {
                $damageType = $perk->key;
                $damage[$damageType] = ['raw' => [], 'mod' => []];

                foreach($sabotages as $sabotage)
                {
                    $attribute = $sabotage[0];
                    $baseDamage = $sabotage[1];

                    $damage[$damageType]['raw'][$damageType] = 1;
                    $damage[$damageType]['mod'][$damageType] = $damage[$damageType]['raw'][$damageType] * $this->conversionCalculator->getConversionReductionMultiplier($target);
                }
            }

        }

        return $damage;
    }

    public function getRawDamage(Dominion $saboteur, Dominion $target, float $baseDamage, int $totalSabotagePowerSent): float
    {
        $rawDamage = ($baseDamage / 100);

        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense') * 10;
        $rawDamage *= ($totalSabotagePowerSent / $target->land);

        if($targetSpa != 0)
        {
             $rawDamage /= $targetSpa;
        }
        
        $rawDamage = min($rawDamage, ($baseDamage / 100)*1.5);

        return $rawDamage;
    }

    public function getUnitsKilled(Dominion $saboteur, Dominion $target, array $units): array
    {

        $killedUnits = [];

        $baseCasualties = 0.025;

        $saboteurSpa = $this->militaryCalculator->getSpyRatio($saboteur, 'offense');
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');
        $spaRatio = max($targetSpa / $saboteurSpa, 0.001);

        # If SPA/SPA is 0.33 or less, there is a random chance spies are immortal.
        if($spaRatio <= 0.33 and random_chance(1 / $spaRatio))
        {
            $baseCasualties = 0;
        }

        $baseCasualties *= (1 + $spaRatio);

        $casualties = $baseCasualties * $this->getSpyLossesReductionMultiplier($saboteur);

        foreach($units as $slot => $amount)
        {
            if($slot == 'spies')
            {
                if
                (
                    $saboteur->getSpellPerkValue('immortal_spies') or
                    $saboteur->race->getPerkValue('immortal_spies') or
                    $saboteur->realm->getArtefactPerkMultiplier('immortal_spies') or
                    $target->race->getPerkValue('does_not_kill') or
                    ($target->getSpellPerkValue('blind_to_reptilian_spies_on_sabotage') and $saboteur->race->name == 'Reptilians')
                )
                {
                    $killedUnits[$slot] = 0;
                }
            }
            else
            {
                if(
                    ($target->getSpellPerkValue('blind_to_reptilian_spies_on_sabotage') and $saboteur->race->name == 'Reptilians') or
                    $saboteur->race->getUnitPerkValueForUnitSlot($slot,'immortal_on_sabotage')
                )
                {
                    $killedUnits[$slot] = 0;
                }
                else
                {
                    $killedUnits[$slot] = (int)min(ceil($amount * $casualties), $units[$slot]);
                }
            }

        }

        return $killedUnits;
    }

    public function getSpyStrengthCost(Dominion $dominion, array $units): int
    {
        $cost = 0;

        $spyUnits = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'spies');
        foreach ($dominion->race->units as $unit)
        {
            if($this->unitHelper->isUnitOffensiveSpy($unit))
            {
                $spyUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $unit->slot);
            }
        }

        $cost = (int)ceil(array_sum($units) / $spyUnits * 100);

        return $cost;
    }

    protected function getSpyLossesReductionMultiplier(Dominion $dominion): float
    {
        $spiesKilledMultiplier = 1;

        // Advancements
        $spiesKilledMultiplier -= $dominion->getAdvancementPerkMultiplier('spy_losses');

        // Buildings
        $spiesKilledMultiplier -= $dominion->getBuildingPerkMultiplier('spy_losses');

        // Techs
        $spiesKilledMultiplier += $dominion->getTechPerkMultiplier('spy_losses');

        // Improvements
        $spiesKilledMultiplier += $dominion->getImprovementPerkMultiplier('spy_losses');

        # Cap at 10% losses (-90%)
        $spiesKilledMultiplier = max(0.10, $spiesKilledMultiplier);

        return $spiesKilledMultiplier;
    }

    public function canPerformSpyop(Dominion $dominion, Spyop $spyop): bool
    {
        if(
          # Must be available to the dominion's faction (race)
          !$this->espionageCalculator->isSpyopAvailableToDominion($dominion, $spyop)

          # Cannot cast disabled spells
          or $spyop->enabled !== 1

          # Round must have started
          or !$dominion->round->hasStarted()

          # Dominion must not be in protection
          or $dominion->isUnderProtection()
        )
        {
            return false;
        }

        return true;
    }

}
