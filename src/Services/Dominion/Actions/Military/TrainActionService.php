<?php

namespace OpenDominion\Services\Dominion\Actions\Military;

use DB;
use Throwable;

use Illuminate\Support\Facades\Cache;

use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Advancement;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Resource;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Traits\DominionGuardsTrait;
use OpenDominion\Calculators\Dominion\AdvancementCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\ResourceHelper;
use OpenDominion\Helpers\SpellHelper;

class TrainActionService
{
    use DominionGuardsTrait;

    protected $queueService;
    protected $trainingCalculator;
    protected $unitHelper;
    protected $raceHelper;
    protected $resourceHelper;
    protected $buildingCalculator;
    protected $resourceCalculator;
    protected $resourceService;
    protected $statsService;
    protected $advancementCalculator;
    protected $improvementCalculator;
    protected $spellCalculator;
    protected $militaryCalculator;
    protected $landCalculator;
    protected $populationCalculator;
    protected $spellHelper;

    public function __construct(
        QueueService $queueService,
        TrainingCalculator $trainingCalculator,
        UnitHelper $unitHelper,
        RaceHelper $raceHelper,
        SpellHelper $spellHelper,
        ResourceHelper $resourceHelper,
        BuildingCalculator $buildingCalculator,
        ResourceCalculator $resourceCalculator,
        ResourceService $resourceService,
        StatsService $statsService,
        AdvancementCalculator $advancementCalculator,
        ImprovementCalculator $improvementCalculator,
        SpellCalculator $spellCalculator,
        MilitaryCalculator $militaryCalculator,
        LandCalculator $landCalculator,
        PopulationCalculator $populationCalculator
    )
    {
        $this->queueService = $queueService;
        $this->trainingCalculator = $trainingCalculator;
        $this->unitHelper = $unitHelper;
        $this->raceHelper = $raceHelper;
        $this->spellHelper = $spellHelper;
        $this->resourceHelper = $resourceHelper;
        $this->buildingCalculator = $buildingCalculator;
        $this->resourceCalculator = $resourceCalculator;
        $this->resourceService = $resourceService;
        $this->statsService = $statsService;

        $this->advancementCalculator = $advancementCalculator;
        $this->improvementCalculator = $improvementCalculator;
        $this->spellCalculator = $spellCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->landCalculator = $landCalculator;
        $this->populationCalculator = $populationCalculator;
    }

    /**
     * Does a military train action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws Throwable
     */
    public function train(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        Cache::forget("dominion.{$dominion->id}.trainingCostsPerUnit");

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot train while you are in stasis.');
        }

        $data = array_only($data, array_map(function ($value) {
            return "military_{$value}";
        }, $this->unitHelper->getUnitTypes($dominion->race)));

        $data = array_map('\intval', $data);

        $totalUnitsToTrain = array_sum($data);

        if ($totalUnitsToTrain <= 0) {
            throw new GameException('Training aborted due to bad input.');
        }

        # Poorly tested.
        if ($dominion->race->getPerkValue('cannot_train_spies') == 1 and isset($data['spies']) and $data['spies'] > 0)
        {
            throw new GameException($dominion->race->name . ' cannot train spies.');
        }
        if ($dominion->race->getPerkValue('cannot_train_wizards') == 1 and isset($data['wizards']) and $data['wizards'] > 0)
        {
            throw new GameException($dominion->race->name . ' cannot train wizards.');
        }
        if ($dominion->race->getPerkValue('cannot_train_archmages') == 1 and isset($data['archmages']) and $data['archmages'] > 0)
        {
            throw new GameException($dominion->race->name . ' Cannot train Archmages.');
        }

        # Non-resource costs
        $totalCosts = [
            'draftees' => 0,
            'spy' => 0,
            'wizard' => 0,
            'wizards' => 0,
            'archmage' => 0,
            'prestige' => 0,
            'morale' => 0,
            'wizard_strength' => 0,
            'spy_strength' => 0,
            'peasant' => 0,
            'unit1' => 0,
            'unit2' => 0,
            'unit3' => 0,
            'unit4' => 0,
            'unit5' => 0,
            'unit6' => 0,
            'unit7' => 0,
            'unit8' => 0,
            'unit9' => 0,
            'unit10' => 0,
            'crypt_body' => 0,
        ];

        # Resource costs
        foreach($dominion->race->resources as $resourceKey)
        {
            $totalCosts[$resourceKey] = 0;
        }

        $unitsToTrain = [];

        $trainingCostsPerUnit = $this->trainingCalculator->getTrainingCostsPerUnit($dominion);

        foreach ($data as $unitType => $amountToTrain)
        {
            if (!$amountToTrain || $amountToTrain == 0)
            {
                continue;
            }

            if ($amountToTrain < 0)
            {
                throw new GameException('Training aborted due to bad input.');
            }

            $unitType = str_replace('military_', '', $unitType);

            $costs = $trainingCostsPerUnit[$unitType];

            foreach ($costs as $costType => $costAmount)
            {
                if($costType === 'draftees')
                {
                    $totalCosts[$costType] += ceil($amountToTrain * $costAmount);
                }
                else
                {
                    $totalCosts[$costType] += ($amountToTrain * $costAmount);
                }
            }

            $unitsToTrain[$unitType] = $amountToTrain;
        }

        foreach($unitsToTrain as $unitType => $amountToTrain)
        {
            if (!$amountToTrain)
            {
                continue;
            }

            $unitSlot = intval(str_replace('unit', '', $unitType));

            $unitToTrain = $dominion->race->units->filter(function ($unit) use ($unitSlot) {
                return ($unit->slot === $unitSlot);
            })->first();

            # Cannot be trained
            if($dominion->race->getUnitPerkValueForUnitSlot($unitSlot,'cannot_be_trained') and $amountToTrain > 0)
            {
              throw new GameException('This unit cannot be trained.');
            }

            # OK, unit can be trained. Let's check for pairing limits.
            if(!$this->unitHelper->checkUnitLimitForTraining($dominion, $unitSlot, $amountToTrain))
            {
                $unit = $dominion->race->units->filter(function ($unit) use ($unitSlot) {
                    return ($unit->slot === $unitSlot);
                })->first();

                throw new GameException('You can at most control ' . number_format($this->unitHelper->getUnitMaxCapacity($dominion, $unitSlot)) . ' ' . str_plural($unit->name) . '. To control more, you need to first have more of their superior unit.');
            }

            # Check for minimum WPA to train.
            $minimumWpaToTrain = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'minimum_wpa_to_train');
            if($minimumWpaToTrain)
            {
                if($this->militaryCalculator->getWizardRatio($dominion, 'offense') < $minimumWpaToTrain)
                {
                  throw new GameException('You need at least ' . $minimumWpaToTrain . ' wizard ratio (on offense) to train this unit. You only have ' . $this->militaryCalculator->getWizardRatio($dominion) . '.');
                }
            }
            # Minimum WPA check complete.

            # Check for advancements required limit.
            $advancementsLimit = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot,'advancements_required_to_train');
            if($advancementsLimit)
            {
                foreach ($advancementsLimit as $index => $advancementLevel)
                {
                    $advancementKey = (string)$advancementLevel[0];
                    $levelRequired = (int)$advancementLevel[1];

                    $advancement = Advancement::where('key', $advancementKey)->firstOrFail();
                    if(!$this->advancementCalculator->hasAdvancementLevel($dominion, $advancement, $levelRequired))
                    {
                        throw new GameException('You do not have the required advancements to train this unit.');
                    }                    
                }
            }
            # Advancements check complete.
        }

      foreach($totalCosts as $resourceKey => $amount)
      {
          if(in_array($resourceKey, $dominion->race->resources))
          {
              $resource = Resource::where('key', $resourceKey)->first();
              if($totalCosts[$resourceKey] > $this->resourceCalculator->getAmount($dominion, $resourceKey))
              {
                  throw new GameException('Training failed due to insufficient ' . $resource->name . '. You tried to spend ' . number_format($totalCosts[$resourceKey]) .  ' but only have ' . number_format($this->resourceCalculator->getAmount($dominion, $resourceKey)) . '.');
              }
          }

          foreach($dominion->race->units as $unit)
          {
            if($totalCosts['unit' . $unit->slot] > $dominion->{'military_unit' . $unit->slot})
            {
                throw new GameException('Insufficient ' . str_plural($unit->name) .  ' to train ' . number_format($amountToTrain) . ' ' . str_plural($unitToTrain->name, $amountToTrain) . '.');
            }
          }

          if($totalCosts['spy'] > $dominion->military_spies)
          {
            throw new GameException('Training failed due to insufficient spies.');
          }

          if($totalCosts['wizard'] > $dominion->military_wizards or $totalCosts['wizards'] > $dominion->military_wizards)
          {
            throw new GameException('Training failed due to insufficient wizards available.');
          }

          if($totalCosts['archmage'] > $dominion->military_archmages)
          {
            throw new GameException('Training failed due to insufficient Arch Mages.');
          }

          if ($totalCosts['draftees'] > $dominion->military_draftees)
          {
              throw new GameException('Training aborted due to lack of ' . str_plural($this->raceHelper->getDrafteesTerm($dominion->race)) . '.');
          }

          if($totalCosts['spy_strength'] > $dominion->spy_strength)
          {
            throw new GameException('Training failed due to insufficient spy strength.');
          }

          if($totalCosts['wizard_strength'] > $dominion->wizard_strength)
          {
            throw new GameException('Training failed due to insufficient wizard strength.');
          }

          if($totalCosts['crypt_body'] > $this->resourceCalculator->getRealmAmount($dominion->realm, 'body'))
          {
              throw new GameException('Insufficient bodies in the crypt to train ' . number_format($amountToTrain) . ' ' . str_plural($unitToTrain->name, $amountToTrain) . '.');
          }
      }

        $newDraftelessUnitsToHouse = 0;
        foreach($unitsToTrain as $unitSlot => $unitAmountToTrain)
        {
            $unitSlot = intval(str_replace('unit','',$unitSlot));
            # If a unit counts towards population, add to $unitsToTrainNeedingHousingWithoutDraftees
            if (
                  !$dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'does_not_count_as_population') and
                  $dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'no_draftee')
              )
            {
                $newDraftelessUnitsToHouse += $unitAmountToTrain;
            }

        }

        if (($dominion->race->name !== 'Cult' and $dominion->race->name !== 'Yeti') and ($newDraftelessUnitsToHouse > 0) and ($newDraftelessUnitsToHouse + $this->populationCalculator->getPopulationMilitary($dominion)) > $this->populationCalculator->getMaxPopulation($dominion))
        {
            throw new GameException('Training failed as training would exceed your max population. You need ' . number_format($newDraftelessUnitsToHouse) . ' additional housing, but only have ' . number_format($this->populationCalculator->getMaxPopulation($dominion) - ($newDraftelessUnitsToHouse + $this->populationCalculator->getPopulationMilitary($dominion))) . ' available.');
        }

        DB::transaction(function () use ($dominion, $data, $totalCosts, $unitSlot, $unitAmountToTrain) {
            $dominion->military_draftees -= $totalCosts['draftees'];
            $dominion->military_wizards -= $totalCosts['wizards'];
            $dominion->prestige -= $totalCosts['prestige'];
            $dominion->morale = max(0, ($dominion->morale - $totalCosts['morale']));
            $dominion->peasants -= $totalCosts['peasant'];
            $dominion->military_unit1 -= $totalCosts['unit1'];
            $dominion->military_unit2 -= $totalCosts['unit2'];
            $dominion->military_unit3 -= $totalCosts['unit3'];
            $dominion->military_unit4 -= $totalCosts['unit4'];
            $dominion->military_spies -= $totalCosts['spy'];
            $dominion->military_wizards -= $totalCosts['wizard'];
            $dominion->military_archmages -= $totalCosts['archmage'];
            $dominion->spy_strength -= $totalCosts['spy_strength'];
            $dominion->wizard_strength -= $totalCosts['wizard_strength'];

            if($totalCosts['crypt_body'] > 0)
            {
                $this->resourceService->updateRealmResources($dominion->realm, ['body' => (-$totalCosts['crypt_body'])]);
            }

            # Update spending statistics.
            foreach($totalCosts as $resource => $amount)
            {
                if($amount > 0)
                {
                    $resourceString = $resource;

                    if($resourceString == 'peasant')
                    {
                        $resourceString = 'peasants';
                    }
                    if($resourceString == 'spy')
                    {
                        $resourceString = 'spies';
                    }
                    if($resourceString == 'wizard')
                    {
                        $resourceString = 'wizards';
                    }
                    if($resourceString == 'archmage')
                    {
                        $resourceString = 'archmages';
                    }

                    $this->statsService->updateStat($dominion, ($resourceString . '_training'), abs($totalCosts[$resource]));
                }
            }

            # Resources 2.0
            $resourceCosts = [];
            foreach($totalCosts as $resourceKey => $cost)
            {
                if(in_array($resourceKey, $dominion->race->resources))
                {
                    $resourceCosts[$resourceKey] = $cost*-1;
                }
            }
            $this->resourceService->updateResources($dominion, $resourceCosts);

            foreach($data as $unitType => $amountToTrain)
            {
                if($amountToTrain > 0)
                {
                    $unitStatsName = str_replace('military_','',$unitType);
                    $slot = (int)str_replace('military_unit','',$unitType);

                    $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();

                    $instantTraining = false;

                    if(isset($unit))
                    {
                        $ticks = $unit->training_time;
                        if($unit->training_time === 0)
                        {
                            $instantTraining = true;
                        }
                    }
                    else
                    {
                        $ticks = 12; # WTF?
                    }

                    if($unitType == 'military_spies' and $dominion->race->getPerkValue('spies_training_time'))
                    {
                        $ticks = $dominion->race->getPerkValue('spies_training_time');
                    }

                    if($unitType == 'military_wizards' and $dominion->race->getPerkValue('wizards_training_time'))
                    {
                        $ticks = $dominion->race->getPerkValue('wizards_training_time');
                    }

                    if($unitType == 'military_archmages' and $dominion->race->getPerkValue('archmages_training_time'))
                    {
                        $ticks = $dominion->race->getPerkValue('archmages_training_time');
                    }

                    // Spell
                    $ticks += $dominion->getSpellPerkValue('training_time_raw');
                    $ticks += $dominion->realm->getArtefactPerkValue('training_time_raw');
                    $ticks += $dominion->getSpellPerkValue('training_time_raw_from_morale');
                    $ticks += ceil($dominion->title->getPerkValue('training_time_raw') * $dominion->getTitlePerkMultiplier());

                    // Spell: Spawning Pool (increase units trained, for free)
                    if ($this->spellCalculator->isSpellActive($dominion, 'spawning_pool') and $unitType == 'military_unit1')
                    {
                        $amountToTrainMultiplier = ($dominion->land_swamp / $dominion->land);
                        $amountToTrain = floor($amountToTrain * (1 + $amountToTrainMultiplier));
                    }

                    if(in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
                    {
                        $amountToTrain *= (1 + $dominion->getBuildingPerkMultiplier('extra_units_trained'));
                    }


                    # Multiplier
                    $ticksMultiplier = 1;
                    $ticksMultiplier += $dominion->getImprovementPerkMultiplier('training_time_mod');
                    $ticksMultiplier += $dominion->getBuildingPerkMultiplier('training_time_mod');
                    $ticksMultiplier += $dominion->getDecreePerkMultiplier('training_time_mod');

                    $ticks = (int)ceil($ticks * $ticksMultiplier);

                    $this->statsService->updateStat($dominion, ($unitStatsName . '_trained'), $amountToTrain);

                    // Look for instant training.
                    if(($ticks === 0 and $instantTraining) and $amountToTrain > 0)
                    {
                        $dominion->{"$unitType"} += $amountToTrain;
                        $dominion->save(['event' => HistoryService::EVENT_ACTION_TRAIN]);
                    }
                    // If not instant training, queue resource.
                    else
                    {
                        # Default state
                        $data = array($unitType => $amountToTrain);

                        // $hours must always be at least 1.
                        $ticks = max($ticks,1);

                        $this->queueService->queueResources('training', $dominion, $data, $ticks);

                        $dominion->save(['event' => HistoryService::EVENT_ACTION_TRAIN]);
                    }
                }
            }
        });

        return [
            'message' => $this->getReturnMessageString($dominion, $unitsToTrain, $totalCosts),
            'data' => [
                'totalCosts' => $totalCosts,
            ],
        ];
    }

    /**
     * Returns the message for a train action.
     *
     * @param Dominion $dominion
     * @param array $unitsToTrain
     * @param array $totalCosts
     * @return string
     */
    protected function getReturnMessageString(Dominion $dominion, array $unitsToTrain, array $totalCosts): string
    {
        $unitsToTrainStringParts = [];

        foreach ($unitsToTrain as $unitType => $amount) {
            if ($amount > 0) {
                $unitName = strtolower($this->unitHelper->getUnitName($unitType, $dominion->race));

                // str_plural() isn't perfect for certain unit names. This array
                // serves as an override to use (see issue #607)
                // todo: Might move this to UnitHelper, especially if more
                //       locations need unit name overrides
                $overridePluralUnitNames = [
                    'shaman' => 'shamans',
                    'abscess' => 'abscesses',
                    'werewolf' => 'werewolves',
                    'snow witch' => 'snow witches',
                    'lich' => 'liches',
                    'fallen' => 'fallen',
                    'goat witch' => 'goat witches',
                    'phoenix' => 'phoenix',
                    'master thief' => 'master thieves',
                    'cavalry' => 'cavalries',
                    'pikeman' => 'pikemen',
                    'berserk' => 'berserkir',
                    'norn' => 'nornir',
                    'valkyrja' => 'valkyrjur',
                    'einherjar' => 'einherjar',
                    'huskarl' => 'huskarlar',
                    'jötunn' => 'jötnar',
                    'nix' => 'hex',
                    'hex' => 'hex',
                    'vex' => 'vex',
                    'pax' => 'pax',
                ];

                $amountLabel = number_format($amount);

                if (array_key_exists($unitName, $overridePluralUnitNames)) {
                    if ($amount === 1) {
                        $unitLabel = $unitName;
                    } else {
                        $unitLabel = $overridePluralUnitNames[$unitName];
                    }
                } else {
                    $unitLabel = str_plural(str_singular($unitName), $amount);
                }

                $unitsToTrainStringParts[] = "{$amountLabel} {$unitLabel}";
            }
        }

        $unitsToTrainString = generate_sentence_from_array($unitsToTrainStringParts);

        $trainingCostsStringParts = [];
        foreach ($totalCosts as $costType => $cost)
        {
            if ($cost === 0)
            {
                continue;
            }

            #$costType = str_singular($costType);

            if(in_array($costType, ['unit1','unit2','unit3','unit4','unit5','unit6','unit7','unit8','unit9','unit10']))
            {
                $slot = (int)str_replace('unit', '', $costType);

                $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $costType = str_plural($unit->name, $cost);
            }

            $costWord = $costType;
            if($this->resourceHelper->isResource($costType))
            {
                $costResource = Resource::where('key', $costType)->first();
                $costWord = $costResource->name;
            }

            if (!in_array($costType, ['gold', 'ore', 'food', 'mana', 'gems', 'lumber', 'prestige', 'champion', 'soul', 'blood', 'morale', 'peasant', 'swamp_gas', 'lumber'], true))
            {
                $costWord = str_plural($costWord, $cost);
            }

            if($costType == 'peasant' or $costType == 'peasants')
            {
                $costWord = $this->raceHelper->getPeasantsTerm($dominion->race);
            }

            if($costType == 'draftee' or $costType == 'draftees')
            {
                $costWord = $this->raceHelper->getDrafteesTerm($dominion->race);
            }

            $trainingCostsStringParts[] = (number_format($cost) . ' ' . $costWord);

        }

        $trainingCostsString = generate_sentence_from_array($trainingCostsStringParts);

        # Clean up formatting
        $unitsToTrainString = ucwords($unitsToTrainString);
        $unitsToTrainString = str_replace('And', 'and', $unitsToTrainString);

        $trainingCostsString = ucwords($trainingCostsString);
        $trainingCostsString = str_replace(' Spy_strengths', '% Spy Strength', $trainingCostsString);
        $trainingCostsString = str_replace(' Wizard_strengths', '% Wizard Strength', $trainingCostsString);
        $trainingCostsString = str_replace(' Crypt_bodies', ' bodies from the crypt', $trainingCostsString);
        $trainingCostsString = str_replace(' Morale', '% Morale', $trainingCostsString);
        $trainingCostsString = str_replace('And', 'and', $trainingCostsString);

        $message = sprintf(
            'Training of %s begun at a cost of %s.',
            $unitsToTrainString,
            $trainingCostsString
        );

        return $message;
    }
}
