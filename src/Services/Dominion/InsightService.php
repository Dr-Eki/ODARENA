<?php

namespace OpenDominion\Services\Dominion;


use DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;

use OpenDominion\Exceptions\GameException;

use OpenDominion\Models\Advancement;
use OpenDominion\Models\Decree;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionDecreeState;
use OpenDominion\Models\DominionInsight;
use OpenDominion\Models\Terrain;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\TerrainHelper;
use OpenDominion\Helpers\TitleHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\DecreeCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\StatsService;


class InsightService
{
    protected $buildingHelper;
    protected $improvementHelper;
    protected $landHelper;
    protected $raceHelper;
    protected $terrainHelper;
    protected $titleHelper;
    protected $unitHelper;

    protected $buildingCalculator;
    protected $casualtiesCalculator;
    protected $decreeCalculator;
    protected $improvementCalculator;
    protected $landCalculator;
    protected $magicCalculator;
    protected $militaryCalculator;
    protected $networthCalculator;
    protected $populationCalculator;
    protected $resourceCalculator;
    protected $spellCalculator;
    
    protected $statsService;
    protected $protectionService;
    protected $queueService;

    public function __construct()
    {
        $this->buildingHelper = app(BuildingHelper::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->landHelper = app(LandHelper::class);
        $this->raceHelper = app(RaceHelper::class);
        $this->terrainHelper = app(TerrainHelper::class);
        $this->titleHelper = app(TitleHelper::class);
        $this->unitHelper = app(UnitHelper::class);

        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->casualtiesCalculator = app(CasualtiesCalculator::class);
        $this->decreeCalculator = app(DecreeCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->magicCalculator = app(MagicCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);

        $this->statsService = app(StatsService::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
    }

    # $target = the dominion for whom Insight is being captured
    # $source = the dominion (if any) which is capturing the Insight
    public function captureDominionInsight(Dominion $target, Dominion $source = null, bool $returnInsightData = false): array
    {

        if($this->protectionService->isUnderProtection($target))
        {
            throw new GameException('You cannot capture insight for a dominion that is under protection.');
        }

        if(!$target->round->hasStarted())
        {
            throw new GameException('You cannot capture insight before the round has started.');
        }

        if($target->getSpellPerkValue('fog_of_war'))
        {
            throw new GameException('This dominion is temporarily hidden from insight.');
        }

        $dominionInsight = new DominionInsight([
            'dominion_id' => $target->id,
            'source_dominion_id' => $source ? $source->id : null,
            'source_realm_id' => $source ? $source->realm->id : null,
            'round_tick' => $target->round->ticks,
        ]);

        $data = [
            # CS: Overview
            'title' => $target->title->name,
            'title_perks' => $this->titleHelper->getRulerTitlePerksForDominion($target),
            'ruler' => $target->ruler_name,
            'race' => $target->race->name,
            'total_land' => $target->land,
            'peasants' => $target->peasants,
            'employment' => $this->populationCalculator->getEmploymentPercentage($target),
            'networth' => $this->networthCalculator->getDominionNetworth($target),
            'prestige' => $target->prestige,
            'victories' => $this->statsService->getStat($target, 'invasion_victories'),
            'net_victories' => $this->militaryCalculator->getNetVictories($target),

            # CS: Resources
            'xp' => $target->xp,

            # CS: Military
            'morale' => $target->morale,
            'military_draftees' => $this->militaryCalculator->getTotalUnitsForSlot($target, 'draftees'),
            'military_spies' => $this->militaryCalculator->getTotalUnitsForSlot($target, 'spies'),
            'military_wizards' => $this->militaryCalculator->getTotalUnitsForSlot($target, 'wizards'),
            'military_archmages' => $this->militaryCalculator->getTotalUnitsForSlot($target, 'archmages'),

            # Deity
            'deity' => $target->hasDeity() ? $target->deity->name : NULL,
            'deity_devotion' => $target->hasDeity() ? $target->devotion->duration : NULL,
            'deity_perk' => NULL,

            # Annexation
            'is_annexed' => $this->spellCalculator->isAnnexed($target),
            'annexed_military_power_provided' => $this->militaryCalculator->getRawMilitaryPowerFromAnnexedDominion($target),
            'has_annexed' => $this->spellCalculator->hasAnnexedDominions($target),
            'military_power_from_annexed_dominions' => $this->militaryCalculator->getRawMilitaryPowerFromAnnexedDominions($target),
        ];

        foreach($target->race->units as $unit)
        {
            $data['military_unit' . $unit->slot] = $this->militaryCalculator->getTotalUnitsForSlot($target, $unit->slot);
        }

        # Military mods
        $data['mods'] = [];
        $data['mods']['offense'] =
            [
                'power' => $this->militaryCalculator->getOffensivePowerMultiplier($target),
                'enemy_modifiers' => $this->militaryCalculator->getOffensiveMultiplierReduction($target),
                'own_casualties' => $this->casualtiesCalculator->getBasicCasualtiesPerkMultipliers($target, 'offense'),
                'spy_ratio' => $this->militaryCalculator->getSpyRatio($target, 'offense'),
                'wizard_ratio' => $this->magicCalculator->getWizardRatio($target, 'offense'),
            ];
        $data['mods']['defense'] =
            [
                'power' => $this->militaryCalculator->getDefensivePowerMultiplier($target),
                'enemy_modifiers' => $this->militaryCalculator->getDefensiveMultiplierReduction($target),
                'own_casualties' => $this->casualtiesCalculator->getBasicCasualtiesPerkMultipliers($target, 'defense'),
                'spy_ratio' => $this->militaryCalculator->getSpyRatio($target, 'defense'),
                'wizard_ratio' => $this->magicCalculator->getWizardRatio($target, 'defense'),
            ];

        # Units
        $data['units'] = [];

        $data['units']['training'] =
            [
                'spies' => array_fill(1, 12, 0),
                'wizards' => array_fill(1, 12, 0),
                'archmages' => array_fill(1, 12, 0),
            ];

        $data['units']['returning'] =
            [
                'spies' => array_fill(1, 12, 0),
                'wizards' => array_fill(1, 12, 0),
                'archmages' => array_fill(1, 12, 0),
                'draftees' => array_fill(1, 12, 0),
            ];

        foreach($target->race->units as $unit)
        {
            $data['units']['training']['unit'. $unit->slot] = array_fill(1, 12, 0);
            $data['units']['returning']['unit'. $unit->slot] = array_fill(1, 12, 0);
        }

        $data['units']['home'] =
            [
                'draftees' => $target->military_draftees,
                'spies' => $target->military_spies,
                'wizards' => $target->military_wizards,
                'archmages' => $target->military_archmages,
          ];

        $data['units']['power'] =
        [
            'peasants' => ['offense' => 0, 'defense' => ($target->race->getPerkValue('peasant_dp') + $target->getTechPerkValue('peasant_dp')) ?: 0],
            'draftees' => ['offense' => 0, 'defense' => ($target->race->getPerkValue('draftee_dp') + $target->getTechPerkValue('draftee_dp')) ?: 1],
        #    'unit1' => ['offense' => $this->militaryCalculator->getUnitPowerWithPerks($target, null, null, $this->unitHelper->getUnitFromRaceUnitType($target->race, 'unit1'), 'offense'), 'defense' => $this->militaryCalculator->getUnitPowerWithPerks($target, null, null, $this->unitHelper->getUnitFromRaceUnitType($target->race, 'unit1'), 'defense')],
        #    'unit2' => ['offense' => $this->militaryCalculator->getUnitPowerWithPerks($target, null, null, $this->unitHelper->getUnitFromRaceUnitType($target->race, 'unit2'), 'offense'), 'defense' => $this->militaryCalculator->getUnitPowerWithPerks($target, null, null, $this->unitHelper->getUnitFromRaceUnitType($target->race, 'unit2'), 'defense')],
        #    'unit3' => ['offense' => $this->militaryCalculator->getUnitPowerWithPerks($target, null, null, $this->unitHelper->getUnitFromRaceUnitType($target->race, 'unit3'), 'offense'), 'defense' => $this->militaryCalculator->getUnitPowerWithPerks($target, null, null, $this->unitHelper->getUnitFromRaceUnitType($target->race, 'unit3'), 'defense')],
        #    'unit4' => ['offense' => $this->militaryCalculator->getUnitPowerWithPerks($target, null, null, $this->unitHelper->getUnitFromRaceUnitType($target->race, 'unit4'), 'offense'), 'defense' => $this->militaryCalculator->getUnitPowerWithPerks($target, null, null, $this->unitHelper->getUnitFromRaceUnitType($target->race, 'unit4'), 'defense')],
        ];

        foreach($target->race->units as $unit)
        {
            $data['units']['home']['unit' . $unit->slot] = $target->{'military_unit'. $unit->slot};
            $data['units']['power']['unit' . $unit->slot] = [
                'offense' => $this->militaryCalculator->getUnitPowerWithPerks($target, null, null, $unit, 'offense'),
                'defense' => $this->militaryCalculator->getUnitPowerWithPerks($target, null, null, $unit, 'defense')
            ];
        }

        foreach($target->race->resources as $resourceKey)
        {
            $data['resource_'.$resourceKey] = $this->resourceCalculator->getAmount($target, $resourceKey);
        }

        // Units returning from invasion
        $this->queueService->getInvasionQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'military_')) {
                $unitType = str_replace('military_', '', $row->resource);
                $data['units']['returning'][$unitType][$row->hours] += $row->amount;
            }
        });

        // Units returning from expedition
        $this->queueService->getExpeditionQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'military_')) {
                $unitType = str_replace('military_', '', $row->resource);
                $data['units']['returning'][$unitType][$row->hours] += $row->amount;
            }
        });

        // Units returning from theft
        $this->queueService->getTheftQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'military_')) {
                $unitType = str_replace('military_', '', $row->resource);
                $data['units']['returning'][$unitType][$row->hours] += $row->amount;
            }
        });

        // Units returning from sabotage
        $this->queueService->getSabotageQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'military_')) {
                $unitType = str_replace('military_', '', $row->resource);
                $data['units']['returning'][$unitType][$row->hours] += $row->amount;
            }
        });

        // Units returning from desecration
        $this->queueService->getDesecrationQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'military_')) {
                $unitType = str_replace('military_', '', $row->resource);
                $data['units']['returning'][$unitType][$row->hours] += $row->amount;
            }
        });

        // Units returning from stun
        $this->queueService->getStunQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'military_')) {
                $unitType = str_replace('military_', '', $row->resource);
                $data['units']['returning'][$unitType][$row->hours] += $row->amount;
            }
        });

        // Units returning from artefacts
        $this->queueService->getArtefactattackQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'military_')) {
                $unitType = str_replace('military_', '', $row->resource);
                $data['units']['returning'][$unitType][$row->hours] += $row->amount;
            }
        });

        // Units training
        $this->queueService->getTrainingQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'military_'))
            {
                $unitType = str_replace('military_', '', $row->resource);
                $data['units']['training'][$unitType][$row->hours] += $row->amount;
            }
        });
        $this->queueService->getSummoningQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'military_'))
            {
                $unitType = str_replace('military_', '', $row->resource);
                $data['units']['training'][$unitType][$row->hours] += $row->amount;
            }
        });
        $this->queueService->getEvolutionQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'military_'))
            {
                $unitType = str_replace('military_', '', $row->resource);
                $data['units']['training'][$unitType][$row->hours] += $row->amount;
            }
        });

        // Spells
        $data['spells'] = [];

        foreach($this->spellCalculator->getActiveSpells($target) as $dominionSpell)
        {
            $data['spells'][$dominionSpell->spell->key]['duration'] = $dominionSpell->spell->duration;
            $data['spells'][$dominionSpell->spell->key]['remaining'] = $dominionSpell->duration;
            $data['spells'][$dominionSpell->spell->key]['caster_name'] = Dominion::findOrFail($dominionSpell->caster_id)->name;
            $data['spells'][$dominionSpell->spell->key]['caster_realm'] = Dominion::findOrFail($dominionSpell->caster_id)->realm->number;
        }

        # Buildings
        $data['buildings'] = [];
        $data['buildings']['constructed'] = [];
        $data['buildings']['constructing'] = [];

        foreach ($this->buildingCalculator->getDominionBuildingsAvailableAndOwned($target) as $building)
        {
            $data['buildings']['constructed'][$building->key] = $this->buildingCalculator->getBuildingAmountOwned($target, $building);
            $data['buildings']['constructing'][$building->key] = array_fill(1, 12, 0);
        }

        $totalConstructingLand = 0;

        $this->queueService->getConstructionQueue($target)->each(static function ($row) use (&$data, &$totalConstructingLand) {
            $buildingKey = str_replace('building_', '', $row->resource);
            if(isset($data['buildings']['constructing'][$buildingKey][$row->hours]))
            {
                $data['buildings']['constructing'][$buildingKey][$row->hours] += $row->amount;
            }
            $totalConstructingLand += (int)$row->amount;
        });

        $this->queueService->getRepairQueue($target)->each(static function ($row) use (&$data, &$totalConstructingLand) {
            # Units returning from sabotage should not count as land. Unsure why it's strpos() and not !strpos().
            if(strpos('military_unit', $row->resource))
            {
                $buildingKey = str_replace('building_', '', $row->resource);
                $data['buildings']['constructing'][$buildingKey][$row->hours] += $row->amount;
                $totalConstructingLand += (int)$row->amount;
            }
        });

        $data['barren_land'] = $this->landCalculator->getTotalBarrenLand($target);
        $data['constructing_land'] = $totalConstructingLand;

        # Improvements
        $data['improvements'] = [];

        foreach($this->improvementHelper->getImprovementsByRace($target->race) as $improvement)
        {
            foreach($improvement->perks as $perk)
            {
                  $data['improvements'][$improvement->key]['points'] = $this->improvementCalculator->getDominionImprovementAmountInvested($target, $improvement);
                  $data['improvements'][$perk->key]['rating'] = $target->getImprovementPerkMultiplier($perk->key);
            }
        }

        # Improvements
        $data['decrees'] = [];

        $i = 0;
        foreach(DominionDecreeState::where('dominion_id', $target->id)->get() as $dominionDecreeState)
        {
            $decree = Decree::findOrFail($dominionDecreeState->decree_id);
            $data['decrees'][$i] =
                [
                    'decree_id' => $dominionDecreeState->decree_id,
                    'decree_state_id' => $dominionDecreeState->decree_state_id,
                    'cooldown' => $this->decreeCalculator->getTicksUntilDominionCanRevokeDecree($target, $decree),
                ];
            
            $i++;
        }

        # Advancements
        $data['advancements'] = [];

        foreach($target->advancements as $dominionAdvancement)
        {
            $advancement = Advancement::findOrFail($dominionAdvancement->pivot->advancement_id);

            $data['advancements'][$advancement->id] =
                [
                    'name' => $advancement->name,
                    'level' => $dominionAdvancement->pivot->level,
                ];
                
            foreach($advancement->perks as $perk)
            {
                $data['advancements'][$advancement->id]['perks'] = [$perk->key => $target->getAdvancementPerkMultiplier($perk->key)];
            }
        }

        # Research
        $data['research'] = [];

        foreach($target->techs as $dominionTech)
        {
            $data['research'][] = $dominionTech->key;
        }

        # Terrain
        $data['terrain'] = [];

        foreach(Terrain::all() as $terrain)
        {
            $amount = $target->{'terrain_' . $terrain->key};
            $data['terrain'][$terrain->key]['amount'] = $amount;
            $data['terrain'][$terrain->key]['percentage'] = ($amount / $target->land) * 100;
            $data['terrain']['incoming'][$terrain->key] = array_fill(1, 12, 0);
        }


        $data['terrain_perks'] = [];
        foreach($target->race->raceTerrains as $raceTerrain)
        {
            if($raceTerrain->perks->count())
            {
                foreach($raceTerrain->perks as $perk)
                {
                    $perkValue = $target->getTerrainPerkValue($perk->key);
                    if($this->terrainHelper->getPerkType($perk->key) == 'mod')
                    {
                        $perkValue = $target->getTerrainPerkMultiplier($perk->key);
                    }

                    $data['terrain_perks'][$raceTerrain->terrain->key][$perk->key] = $perkValue;
                }
            }            
        }

        $this->queueService->getExplorationQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'terrain_'))
            {
                $landType = str_replace('terrain_', '', $row->resource);
                $data['terrain']['incoming'][$landType][$row->hours] += $row->amount;
            }
        });

        $this->queueService->getInvasionQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'terrain_'))
            {
                $landType = str_replace('terrain_', '', $row->resource);
                $data['terrain']['incoming'][$landType][$row->hours] += $row->amount;
            }
        });

        $this->queueService->getExpeditionQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'terrain_'))
            {
                $landType = str_replace('terrain_', '', $row->resource);
                $data['terrain']['incoming'][$landType][$row->hours] += $row->amount;
            }
        });

        # Land
        $data['land'] = [];

        foreach ($this->landHelper->getLandTypes() as $landType)
        {
            $amount = $target->{'land_' . $landType};
            $data['land'][$landType]['amount'] = $amount;
            $data['land'][$landType]['percentage'] = ($amount / $target->land) * 100;
            $data['land'][$landType]['barren'] = $this->landCalculator->getTotalBarrenLandByLandType($target, $landType);
            $data['land'][$landType]['landtype_defense'] = 0;#$this->militaryCalculator->getDefensivePowerModifierFromLandType($target, $landType);

            $data['land']['incoming'][$landType] = array_fill(1, 12, 0);
        }


        $this->queueService->getExplorationQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'land_'))
            {
                $landType = str_replace('land_', '', $row->resource);
                $data['land']['incoming'][$landType][$row->hours] += $row->amount;
            }
        });

        $this->queueService->getInvasionQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'land_'))
            {
                $landType = str_replace('land_', '', $row->resource);
                $data['land']['incoming'][$landType][$row->hours] += $row->amount;
            }
        });

        $this->queueService->getExpeditionQueue($target)->each(static function ($row) use (&$data)
        {
            if (Str::startsWith($row->resource, 'land_'))
            {
                $landType = str_replace('land_', '', $row->resource);
                $data['land']['incoming'][$landType][$row->hours] += $row->amount;
            }
        });

        if($returnInsightData)
        {
            return $data;
        }

        $data = json_encode($data);

        $dominionInsight->data = $data;

        $dominionInsight->save();

        return [
            'alert-type' => 'success',
            'redirect' => route('dominion.insight.archive', $target),
            'message' => 'Insight successfully archived.',
        ];
    }


    public function getDominionInsight(Dominion $dominion, Dominion $source): Collection
    {
        return DominionInsight::where('dominion_id', $dominion->id)->get();
    }
}
