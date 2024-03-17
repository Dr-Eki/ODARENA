<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Log;
use Illuminate\Support\Str;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Traits\DominionGuardsTrait;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Building;
use OpenDominion\Models\GameEvent;

use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Calculators\Dominion\ArtefactCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ExpeditionCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\TerrainCalculator;
use OpenDominion\Calculators\Dominion\UnitCalculator;

use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Dominion\ArtefactService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;

class ExpeditionActionService
{
    use DominionGuardsTrait;

    protected const MIN_MORALE = 50;

    protected $artefactCalculator;
    protected $buildingCalculator;
    protected $expeditionCalculator;
    protected $landCalculator;
    protected $magicCalculator;
    protected $militaryCalculator;
    protected $spellCalculator;
    protected $terrainCalculator;
    protected $unitCalculator;

    protected $landHelper;
    protected $raceHelper;
    protected $spellHelper;
    protected $unitHelper;

    protected $artefactService;
    protected $notificationService;
    protected $protectionService;
    protected $statsService;
    protected $queueService;


    /** @var array Invasion result array. todo: Should probably be refactored later to its own class */
    protected $expedition = [];

    public function __construct()
    {
        $this->artefactCalculator = app(ArtefactCalculator::class);
        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->expeditionCalculator = app(ExpeditionCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->magicCalculator = app(MagicCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);

        $this->landHelper = app(LandHelper::class);
        $this->spellHelper = app(SpellHelper::class);
        $this->raceHelper = app(RaceHelper::class);
        $this->unitHelper = app(UnitHelper::class);

        $this->artefactService = app(ArtefactService::class);
        $this->notificationService = app(NotificationService::class);
        $this->protectionService = app(ProtectionService::class);
        $this->statsService = app(StatsService::class);
        $this->queueService = app(QueueService::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->terrainCalculator = app(TerrainCalculator::class);
        $this->unitCalculator = app(UnitCalculator::class);
    }

    /**
     * Invades dominion $target from $dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return array
     * @throws GameException
     */
    public function send(Dominion $dominion, array $units): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        DB::transaction(function () use ($dominion, $units)
        {
            
            // Check if this is a resource gathering expedition
            $this->expedition['is_resource_gathering_expedition'] = false;
            $resourceFindingPerks = [
                'finds_resource_on_expedition',
                'finds_resources_on_expedition',
                'finds_resource_on_expedition_random',
                'finds_resources_on_expedition_random',
            ];
        
            foreach ($units as $slot => $amount) {
                $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
                    return $unit->slot === $slot;
                })->first();
        
                if ($this->unitHelper->checkUnitHasPerks($dominion, $unit, $resourceFindingPerks)) {
                    $this->expedition['is_resource_gathering_expedition'] = true;
                }
            }

            if(!$dominion->round->getSetting('expeditions'))
            {
                throw new GameException('Expeditions are disabled this round.');
            }

            if ($this->protectionService->isUnderProtection($dominion))
            {
                throw new GameException('You cannot send out an expedition while under protection');
            }

            // Sanitize input
            $units = array_map('intval', array_filter($units));

            if (!$this->hasAnyOP($dominion, $units) and !$this->expedition['is_resource_gathering_expedition'])
            {
                throw new GameException('You need to send at least some units with offensive power.');
            }

            if (!$this->allUnitsHaveOP($dominion, $units) and !$this->expedition['is_resource_gathering_expedition'])
            {
                throw new GameException('You cannot send units that have no offensive power.');
            }

            if (!$this->hasEnoughUnitsAtHome($dominion, $units))
            {
                throw new GameException('You don\'t have enough units at home to send this many units.');
            }

            if ($dominion->morale < static::MIN_MORALE)
            {
                throw new GameException('You do not have enough morale to send out units on an expedition.');
            }

            if (!$this->passes43RatioRule($dominion, $units))
            {
                throw new GameException('You are sending out too much OP, based on your new home DP (4:3 rule).');
            }

            if (!$this->passesUnitSendableCapacityCheck($dominion, $units))
            {
                throw new GameException('You do not have enough caverns to send out this many units.');
            }

            foreach($units as $slot => $amount)
            {
                if($amount < 0)
                {
                    throw new GameException('Expedition was canceled due to bad input.');
                }

                $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
                return ($unit->slot === $slot);
                })->first();

                if(!$this->unitCalculator->isUnitSendableByDominion($unit, $dominion))
                {
                    throw new GameException('You cannot send ' . $unit->name . ' on expeditions.');
                }
            }

            if ($dominion->race->getPerkValue('cannot_send_expeditions'))
            {
                throw new GameException($dominion->race->name . ' cannot send out expeditions.');
            }

            if ($dominion->getDeityPerkValue('cannot_send_expeditions'))
            {
                throw new GameException('Your deity prohibits sending expeditions.');
            }

            if ($dominion->getSpellPerkValue('cannot_send_expeditions'))
            {
                throw new GameException('A spell is preventing you from sending expeditions.');
            }

            if ($dominion->getDecreePerkValue('cannot_send_expeditions'))
            {
                throw new GameException('A decree has been issued which forbids expeditions.');
            }

            // Spell: Rainy Season (cannot invade)
            if ($this->spellCalculator->isSpellActive($dominion, 'rainy_season'))
            {
                throw new GameException('You cannot send out expeditions during the Rainy Season.');
            }

            if ($dominion->getSpellPerkValue('cannot_invade') or $dominion->getSpellPerkValue('cannot_send_expeditions'))
            {
                throw new GameException('A spell is preventing from you sending out expeditions.');
            }

            $disallowedUnitAttributes = [
                'ammunition',
                'immobile'
              ];
            // Check building_limit and unit attributes
            foreach($units as $unitSlot => $amount)
            {
                if($buildingLimit = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot,'building_limit'))
                {
                    $buildingKeyLimitedTo = $buildingLimit[0]; # Land type
                    $unitsPerBuilding = (float)$buildingLimit[1]; # Units per building
                    $unitsPerBuilding *= (1 + $dominion->getImprovementPerkMultiplier('unit_pairing'));

                    $building = Building::where('key', $buildingKeyLimitedTo)->first();
                    $dominionBuildings = $this->buildingCalculator->getDominionBuildings($dominion);
                    $amountOfLimitingBuilding = $dominionBuildings->where('building_id', $building->id)->first()->owned;

                    $maxSendableOfThisUnit = $amountOfLimitingBuilding * $unitsPerBuilding;

                    if($amount > $maxSendableOfThisUnit)
                    {
                        throw new GameException('You can at most send ' . number_format($upperLimit) . ' ' . Str::plural($this->unitHelper->getUnitName($unitSlot, $dominion->race), $upperLimit) . '. To send more, you must build more '. ucwords(Str::plural($buildingLimit[0], 2)) .' or invest more in unit pairing improvements.');
                    }
                }

                # Get the $unit
                $unit = $dominion->race->units->filter(function ($unit) use ($unitSlot) {
                        return ($unit->slot == $unitSlot);
                    })->first();

                # Get the unit attributes
                $unitAttributes = $this->unitHelper->getUnitAttributes($unit);

                if (count(array_intersect($disallowedUnitAttributes, $unitAttributes)) !== 0)
                {
                    throw new GameException('Ammunition and immobile units cannot be used for expeditions.');
                }

                # Disallow units with fixed casualties perk
                if ($fixedCasualtiesPerk = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'fixed_casualties'))
                {
                    throw new GameException('Units with fixed casualties cannot be sent on expeditions.');
                }
            }

            // Cannot invade until round has started.
            if(!$dominion->round->hasStarted())
            {
                throw new GameException('You cannot send out expeditions until the round has started.');
            }

            // Qur: Statis cannot invade.
            if($dominion->getSpellPerkValue('stasis'))
            {
                throw new GameException('You cannot send out expeditions while you are in stasis.');
            }

            $this->expedition['units_sent'] = $units;
            $this->expedition['land_size'] = $dominion->land;

            $this->expedition['op_sent'] = $this->militaryCalculator->getOffensivePower($dominion, null, null, $units);
            $this->expedition['op_raw'] = $this->militaryCalculator->getOffensivePowerRaw($dominion, null, null, $units, [], true);

            $this->expedition['land_discovered'] = $this->expeditionCalculator->getLandDiscoveredAmount($dominion, $this->expedition['op_sent']);
            

            if($this->expedition['land_discovered'] <= 0 and !$this->expedition['is_resource_gathering_expedition'])
            {
                throw new GameException('Expeditions must discover at least some land.');
            }

            if(!$this->expedition['is_resource_gathering_expedition'])
            {
                $this->queueService->queueResources(
                    'expedition',
                    $dominion,
                    ['land' => $this->expedition['land_discovered']]
                );

                foreach($this->terrainCalculator->getTerrainDiscovered($dominion, $this->expedition['land_discovered']) as $terrainKey => $amount)
                {
                    $this->expedition['terrain_discovered']['terrain_' . $terrainKey] = $amount;
                }

                $this->queueService->queueResources(
                    'expedition',
                    $dominion,
                    $this->expedition['terrain_discovered']
                );
            }

            $this->handlePrestigeChanges($dominion, $this->expedition['land_discovered'], $this->expedition['land_size'], $units);
            $this->handleXp($dominion, $this->expedition['land_discovered']);
            $this->handleResourceFinding($dominion, $units);
            $this->handleArtefactsDiscovery($dominion);
            $this->handleReturningUnits($dominion, $units);

            $this->statsService->updateStat($dominion, 'land_discovered', $this->expedition['land_discovered']);
            $this->statsService->updateStat($dominion, 'expeditions', 1);

            # Debug before saving:
            #$this->expedition); #dd('Safety!');

            $this->expedition = GameEvent::create([
                'round_id' => $dominion->round_id,
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'target_type' => NULL,
                'target_id' => NULL,
                'type' => 'expedition',
                'data' => $this->expedition,
                'tick' => $dominion->round->ticks
            ]);

            $dominion->save(['event' => HistoryService::EVENT_ACTION_EXPEDITION]);
        });

        $message = sprintf(
                'Your units are sent out on an expedition and discover %s acres of land!',
                number_format($this->expedition['land_discovered'])
            );
            $alertType = 'success';

        return [
            'message' => $message,
            'alert-type' => $alertType,
            'redirect' => route('dominion.event', [$this->expedition->id])
        ];
    }

    protected function handlePrestigeChanges(Dominion $dominion, int $landDiscovered, int $landSize, array $units): void
    {
        $prestigeChange = intval($landDiscovered / $dominion->land * 400);

        $prestigeChangeMultiplier = 1;
        $prestigeChangeMultiplier += $dominion->race->getPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $this->militaryCalculator->getPrestigeGainsPerk($dominion, $units);
        $prestigeChangeMultiplier += $dominion->getAdvancementPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $dominion->getBuildingPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $dominion->getImprovementPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $dominion->getSpellPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $dominion->getDeityPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $dominion->realm->getArtefactPerkMultiplier('prestige_gains');
        $prestigeChangeMultiplier += $dominion->title->getPerkMultiplier('prestige_gains') * $dominion->getTitlePerkMultiplier();
        $prestigeChangeMultiplier += $dominion->title->getPerkMultiplier('expedition_prestige_gains') * $dominion->getTitlePerkMultiplier();

        $prestigeChange *= $prestigeChangeMultiplier;

        $this->queueService->queueResources(
            'expedition',
            $dominion,
            ['prestige' => $prestigeChange],
            12
        );

        $this->expedition['prestige_change'] = $prestigeChange;
    }

    /**
     * Handles experience point (research point) generation for attacker.
     *
     * @param Dominion $dominion
     * @param array $units
     */
    protected function handleXp(Dominion $dominion, int $landDiscovered): void
    {

        $xpPerAcreMultiplier = 1;
        $xpPerAcreMultiplier += $dominion->race->getPerkMultiplier('xp_gains');
        $xpPerAcreMultiplier += $dominion->getImprovementPerkMultiplier('xp_gains');
        $xpPerAcreMultiplier += $dominion->getBuildingPerkMultiplier('xp_gains');
        $xpPerAcreMultiplier += $dominion->getSpellPerkMultiplier('xp_gains');
        $xpPerAcreMultiplier += $dominion->getDeityPerkMultiplier('xp_gains');

        $xpGained = intval(33 * $xpPerAcreMultiplier * $landDiscovered);

        $this->queueService->queueResources(
            'expedition',
            $dominion,
            ['xp' => $xpGained],
            12
        );

        $this->expedition['xp'] = $xpGained;

    }

    protected function handleArtefactsDiscovery($dominion): void
    {
        $this->expedition['artefact']['found'] = false;

        if(!in_array($dominion->round->mode, ['artefacts', 'artefacts-packs']))
        {
            return;
        }

        $this->expedition['artefact']['chance_to_find'] = $this->artefactCalculator->getChanceToDiscoverArtefactOnExpedition($dominion, $this->expedition);
     
        if(random_chance($this->artefactCalculator->getChanceToDiscoverArtefactOnExpedition($dominion, $this->expedition)))
        {
            if($artefact = $this->artefactService->getRandomUndiscoveredArtefact($dominion->round))
            {
                $this->expedition['artefact']['found'] = true;

                $this->queueService->queueResources(
                    'artefact',
                    $dominion,
                    [$artefact->key => 1],
                    12
                );
                $this->expedition['artefact']['found'] = true;
                $this->expedition['artefact']['id'] = $artefact->id;
                $this->expedition['artefact']['key'] = $artefact->key;
                $this->expedition['artefact']['name'] = $artefact->name;
    
                $this->statsService->updateStat($dominion, 'artefacts_discovered', 1);
            }
            else
            {
                Log::info('Artefact was discovered but no random artefact could be found. Are all in play already?');
            }
        }
    }

    protected function handleResourceFinding($dominion, $units): void
    {
        $this->expedition['resources_found'] = [];

        $resourcesFound = [];

        $resourcesFound = $this->expeditionCalculator->getResourcesFound($dominion, $units);
        $this->expedition['resources_found'] = $resourcesFound;

        foreach($resourcesFound as $resourceKey => $amount)
        {
            $this->queueService->queueResources(
                'expedition',
                $dominion,
                [('resource_' . $resourceKey) => $amount],
                12
            );

            $this->statsService->updateStat($dominion, ($resourceKey . '_found'), $amount);
        }
    }

    # Unit Return 2.0
    protected function handleReturningUnits(Dominion $dominion, array $units): void
    {
        # If instant return
        if(random_chance($dominion->getImprovementPerkMultiplier('chance_of_instant_return')) or $dominion->race->getPerkValue('instant_return') or $dominion->getSpellPerkValue('instant_return'))
        {
            $this->expedition['attacker']['instantReturn'] = true;
        }
        # Normal return
        else
        {
            $returningUnits = [
                'military_spies' => array_fill(1, 12, 0),
                'military_wizards' => array_fill(1, 12, 0),
                'military_archmages' => array_fill(1, 12, 0),
            ];

            foreach($dominion->race->units as $unit)
            {
                $returningUnits['military_unit' . $unit->slot] = array_fill(1, 12, 0);
            }

            $someWinIntoUnits = array_fill(1, $dominion->race->units->count(), 0);
            $someWinIntoUnits = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

            foreach($returningUnits as $unitKey => $values)
            {
                $slot = (int)str_replace('military_unit', '', $unitKey);
                $amountReturning = 0;

                $returningUnitKey = $unitKey;

                # Remove the units from attacker and add them to $amountReturning.
                if (array_key_exists($slot, $units))
                {
                    $dominion->{$unitKey} -= $units[$slot];
                    $amountReturning += $units[$slot];
                }
                
                # Default return time is 12 ticks.
                $ticks = $this->getUnitReturnTicksForSlot($dominion, $slot);

                # Default all returners to tick 12
                $returningUnits[$returningUnitKey][$ticks] += $amountReturning;

                # Look for dies_into and variations amongst the dead attacking units.
                if(isset($this->expedition['units_lost'][$slot]))
                {
                    $casualties = $this->expedition['attacker']['units_lost'][$slot];

                    if($diesIntoPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into'))
                    {
                        # Which unit do they die into?
                        $newUnitSlot = $diesIntoPerk[0];
                        $newUnitKey = "military_unit{$newUnitSlot}";
                        $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($dominion, $newUnitSlot);

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += $casualties;
                    }

                    if($diesIntoPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_on_offense'))
                    {
                        # Which unit do they die into?
                        $newUnitSlot = $diesIntoPerk[0];
                        $newUnitKey = "military_unit{$newUnitSlot}";
                        $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($dominion, $newUnitSlot);

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += $casualties;
                    }

                    if($diesIntoMultiplePerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple'))
                    {
                        # Which unit do they die into?
                        $newUnitSlot = $diesIntoMultiplePerk[0];
                        $newUnitAmount = (float)$diesIntoMultiplePerk[1];
                        $newUnitKey = "military_unit{$newUnitSlot}";
                        $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($dominion, $newUnitSlot);

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                    }

                    if($diesIntoMultiplePerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_offense'))
                    {
                        # Which unit do they die into?
                        $newUnitSlot = $diesIntoMultiplePerk[0];
                        $newUnitAmount = (float)$diesIntoMultiplePerk[1];
                        $newUnitKey = "military_unit{$newUnitSlot}";
                        $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($dominion, $newUnitSlot);

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                    }

                    if($this->expedition['result']['success'] and $diesIntoMultiplePerkOnVictory = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_victory'))
                    {
                        # Which unit do they die into?
                        $newUnitSlot = $diesIntoMultiplePerkOnVictory[0];
                        $newUnitAmount = (float)$diesIntoMultiplePerkOnVictory[1];
                        $newUnitKey = "military_unit{$newUnitSlot}";
                        $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($dominion, $newUnitSlot);

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                    }

                    if(!$this->expedition['result']['success'] and $diesIntoMultiplePerkOnVictory = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_victory'))
                    {
                        # Which unit do they die into?
                        $newUnitSlot = $diesIntoMultiplePerkOnVictory[0];
                        $newUnitAmount = $diesIntoMultiplePerkOnVictory[2];
                        $newUnitKey = "military_unit{$newUnitSlot}";
                        $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($dominion, $newUnitSlot);

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                    }
                }

                # Check for faster_return_if_paired
                foreach($units as $slot => $amount)
                {
                    if($fasterReturnIfPairedPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_if_paired'))
                    {
                        $pairedUnitSlot = (int)$fasterReturnIfPairedPerk[0];
                        $pairedUnitKey = 'military_unit'.$pairedUnitSlot;
                        $ticksFaster = (int)$fasterReturnIfPairedPerk[1];
                        $pairedUnitKeyReturning = array_sum($returningUnits[$pairedUnitKey]);

                        # Determine new return speed
                        $fasterReturningTicks = min(max($ticks - $ticksFaster, 1), 12);

                        # How many of $slot should return faster?
                        $unitsWithFasterReturnTime = min($pairedUnitKeyReturning, $amountReturning);
                        $unitsWithRegularReturnTime = max(0, $amount - $unitsWithFasterReturnTime);

                        $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                        $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                    }
                }

                # Check for faster_return from buildings
                if($buildingFasterReturnPerk = $dominion->getBuildingPerkMultiplier('faster_return'))
                {
                    $fasterReturn = min(max(0, $buildingFasterReturnPerk), 1);
                    $normalReturn = 1 - $fasterReturn;
                    $ticksFaster = 6;

                    $fasterReturningTicks = min(max(1, ($ticks - $ticksFaster), 12));

                    $unitsWithFasterReturnTime = round($amountReturning * $buildingFasterReturnPerk);
                    $unitsWithRegularReturnTime = round($amountReturning - $amountWithFasterReturn);

                    $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                    $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                }

                # Check for faster_return_units and faster_return_units_increasing from buildings
                if($buildingFasterReturnPerk = $dominion->getBuildingPerkValue('faster_returning_units') or $buildingFasterReturnPerk = $dominion->getBuildingPerkValue('faster_returning_units_increasing'))
                {
                    $fasterReturn = min(max(0, $buildingFasterReturnPerk), 1);
                    $normalReturn = 1 - $fasterReturn;
                    $ticksFaster = 4;

                    $fasterReturningTicks = min(max(1, ($ticks - $ticksFaster)), 12);

                    $unitsWithFasterReturnTime = min($buildingFasterReturnPerk, $amountReturning);
                    $unitsWithRegularReturnTime = round($amountReturning - $unitsWithFasterReturnTime);

                    $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                    $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                }
            }

            foreach($returningUnits as $unitKey => $unitKeyTicks)
            {
                foreach($unitKeyTicks as $unitTypeTick => $amount)
                {
                    if($amount > 0)
                    {
                        $this->queueService->queueResources(
                            'expedition',
                            $dominion,
                            [$unitKey => $amount],
                            $unitTypeTick
                        );
                    }
                }
                $slot = str_replace('military_unit', '', $unitKey);
                $this->expedition['units_returning'][$slot] = array_sum($unitKeyTicks);
            }

            $dominion->save();
        }
    }

    /**
     * Check if dominion is sending out at least *some* OP.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function hasAnyOP(Dominion $dominion, array $units): bool
    {
        return ($this->militaryCalculator->getOffensivePower($dominion, null, null, $units) > 0);
    }

    /**
     * Check if all units being sent have positive OP.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function allUnitsHaveOP(Dominion $dominion, array $units): bool
    {
        foreach ($dominion->race->units as $unit)
        {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0))
            {
                continue;
            }

            if (
                    $this->militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unit, 'offense', null, $units, null) === 0.0 and
                    !$unit->getPerkValue('sendable_with_zero_op') and
                    !$unit->getPerkValue('sendable_on_expeditions_with_zero_op')
                )
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if dominion has enough units at home to send out.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function hasEnoughUnitsAtHome(Dominion $dominion, array $units): bool
    {
        foreach ($dominion->race->units as $unit)
        {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0))
            {
                continue;
            }

            if ($units[$unit->slot] > $dominion->{'military_unit' . $unit->slot})
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if an invasion passes the 4:3-rule.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function passes43RatioRule(Dominion $dominion, array $units): bool
    {
        $unitsHome = [
            0 => $dominion->military_draftees,
        ];

        foreach($dominion->race->units as $unit)
        {
            $unitsHome[] = $dominion->{'military_unit'.$unit->slot} - (isset($units[$unit->slot]) ? $units[$unit->slot] : 0);
        }
        $attackingForceOP = $this->militaryCalculator->getOffensivePower($dominion, null, null, $units);
        $newHomeForcesDP = $this->militaryCalculator->getDefensivePower($dominion, null, null, $unitsHome, 0, false, false, null, true); # The "true" at the end excludes raw DP from annexed dominions

        $attackingForceMaxOP = (int)ceil($newHomeForcesDP * (4/3));

        return ($attackingForceOP <= $attackingForceMaxOP);
    }

    protected function passesUnitSendableCapacityCheck(Dominion $attacker, array $units): bool
    {
        if(!$attacker->race->getPerkValue('caverns_required_to_send_units'))
        {
            return true;
        }

        $maxSendableUnits = $this->militaryCalculator->getMaxSendableUnits($attacker);

        return (array_sum($units) <= $maxSendableUnits);
    }

    protected function passesWizardPointsCheck(Dominion $attacker, array $units): bool
    {
        return ($this->magicCalculator->getWizardPoints($attacker) >= $this->magicCalculator->getWizardPointsRequiredToSendUnits($attacker, $units));
    }

    protected function getUnitReturnTicksForSlot(Dominion $dominion, int $slot): int
    {

        $ticks = 12;

        if(!in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
        {
            return $ticks;
        }

        $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
            return ($unit->slot === $slot);
        })->first();

        $ticks -= (int)$unit->getPerkValue('faster_return');
        $ticks -= (int)$dominion->getSpellPerkValue('faster_return');
        $ticks -= (int)$dominion->getAdvancementPerkValue('faster_return');
        $ticks -= (int)$dominion->realm->getArtefactPerkValue('faster_return');

        return min(max(1, $ticks), 12);
    }

}
