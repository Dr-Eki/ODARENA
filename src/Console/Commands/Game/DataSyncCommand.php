<?php

namespace OpenDominion\Console\Commands\Game;

use DB;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use OpenDominion\Console\Commands\CommandInterface;

use OpenDominion\Models\Advancement;
use OpenDominion\Models\AdvancementPerk;
use OpenDominion\Models\AdvancementPerkType;
use OpenDominion\Models\Artefact;
use OpenDominion\Models\ArtefactPerk;
use OpenDominion\Models\ArtefactPerkType;
use OpenDominion\Models\Building;
use OpenDominion\Models\BuildingPerk;
use OpenDominion\Models\BuildingPerkType;
use OpenDominion\Models\Decree;
use OpenDominion\Models\DecreeState;
use OpenDominion\Models\DecreeStatePerk;
use OpenDominion\Models\DecreeStatePerkType;
use OpenDominion\Models\Deity;
use OpenDominion\Models\DeityPerk;
use OpenDominion\Models\DeityPerkType;
use OpenDominion\Models\DominionAdvancement;
use OpenDominion\Models\DominionBuilding;
use OpenDominion\Models\DominionDecreeState;
use OpenDominion\Models\DominionDeity;
use OpenDominion\Models\DominionImprovement;
use OpenDominion\Models\DominionResource;
use OpenDominion\Models\DominionStat;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\DominionTech;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\ImprovementPerk;
use OpenDominion\Models\ImprovementPerkType;
use OpenDominion\Models\Quickstart;
use OpenDominion\Models\Race;
use OpenDominion\Models\RacePerk;
use OpenDominion\Models\RacePerkType;
use OpenDominion\Models\RealmArtefact;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Spell;
use OpenDominion\Models\SpellPerk;
use OpenDominion\Models\SpellPerkType;
use OpenDominion\Models\Spyop;
use OpenDominion\Models\SpyopPerk;
use OpenDominion\Models\SpyopPerkType;
use OpenDominion\Models\Stat;
use OpenDominion\Models\Tech;
use OpenDominion\Models\TechPerk;
use OpenDominion\Models\TechPerkType;
use OpenDominion\Models\Title;
use OpenDominion\Models\TitlePerk;
use OpenDominion\Models\TitlePerkType;
use OpenDominion\Models\Unit;
use OpenDominion\Models\UnitPerk;
use OpenDominion\Models\UnitPerkType;

class DataSyncCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:data:sync';

    /** @var string The console command description. */
    protected $description = 'Sync game data';

    /** @var Filesystem */
    protected $filesystem;

    /**
     * DataSyncCommand constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $start = now();
        DB::transaction(function () {
            $this->syncDeities();
            $this->syncRaces();
            $this->syncAdvancements();
            $this->syncTechs();
            $this->syncBuildings();
            $this->syncTitles();
            $this->syncSpells();
            $this->syncSpyops();
            $this->syncImprovements();
            $this->syncStats();
            $this->syncResources();
            $this->syncArtefacts();
            $this->syncDecrees();
            $this->syncQuickstarts();
        });

        $finish = now();

        $this->info('Game data synced in ' . number_format($finish->diffInMilliseconds($start)) . ' ms');
    }

    /**
     * Syncs race, unit and perk data from .yml files to the database.
     */
    protected function syncRaces()
    {
        $files = $this->filesystem->files(base_path('app/data/races'));

        foreach ($files as $file) {
            $data = Yaml::parse($file->getContents(), Yaml::PARSE_OBJECT_FOR_MAP);

            $defaultImprovementResources = [
                'gold' => 1,
                'ore' => 2,
                'lumber' => 2,
                'gems' => 12
            ];

            $defaultResources = [
                'gold',
                'food',
                'lumber',
                'ore',
                'gems',
                'mana'
            ];

            // Race
            $race = Race::firstOrNew(['name' => $data->name])
                ->fill([
                    'key' => $this->generateKeyFromNameString(object_get($data, 'name')),
                    'alignment' => object_get($data, 'alignment'),
                    'description' => object_get($data, 'description'),
                    'home_land_type' => object_get($data, 'home_land_type'),
                    'playable' => object_get($data, 'playable', 0),
                    'skill_level' => object_get($data, 'skill_level'),
                    'experimental' => object_get($data, 'experimental', 0),
                    'max_per_round' => object_get($data, 'max_per_round', NULL),
                    'minimum_rounds' => object_get($data, 'minimum_rounds', 0),
                    'psionic_strength' => object_get($data, 'psionic_strength', 1),
                    'resources' => object_get($data, 'resources', $defaultResources),
                    'improvement_resources' => object_get($data, 'improvement_resources', $defaultImprovementResources),
                    'land_improvements' => object_get($data, 'land_improvements', NULL),
                    'construction_materials' => object_get($data, 'construction_materials', ['gold','lumber']),
                    'peasants_production' => object_get($data, 'peasants_production', ['gold' => 2.7]),
                    'peasants_alias' => object_get($data, 'peasants_alias', null),
                    'draftees_alias' => object_get($data, 'draftees_alias', null),

                    'spies_cost' => object_get($data, 'spies_cost', '500,gold'),
                    'wizards_cost' => object_get($data, 'wizards_cost', '500,gold'),
                    'archmages_cost' => object_get($data, 'archmages_cost', '1000,gold'),
                ]);

            if (!$race->exists)
            {
                $this->info("Adding race {$data->name}");
            }
            else
            {
                $this->info("Processing race {$data->name}");

                $newValues = $race->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $race->getOriginal($key);

                    if(is_array($originalValue))
                    {
                    #    $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    #$this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $race->save();
            $race->refresh();

            // Race Perks
            $racePerksToSync = [];

            foreach (object_get($data, 'perks', []) as $perk => $value) {
                $value = (float)$value;

                $racePerkType = RacePerkType::firstOrCreate(['key' => $perk]);

                $racePerksToSync[$racePerkType->id] = ['value' => $value];

                $racePerk = RacePerk::query()
                    ->where('race_id', $race->id)
                    ->where('race_perk_type_id', $racePerkType->id)
                    ->first();

                if ($racePerk === null) {
                    $this->info("[Add Race Perk] {$perk}: {$value}");
                } elseif ($racePerk->value != $value) {
                    $this->info("[Change Race Perk] {$perk}: {$racePerk->value} -> {$value}");
                }
            }

            $race->perks()->sync($racePerksToSync);

            $currentSlots = range(1, $race->units->count());
            $toSyncSlots = range(1, count(object_get($data, 'units', [])));
            $slotsToDelete = array_diff($currentSlots, $toSyncSlots);

            // Units
            foreach (object_get($data, 'units', []) as $slot => $unitData)
            {

                $slot++; # Because arrays start at 0

                $unitName = object_get($unitData, 'name');

                $deityId = null;
                if($deityKey = object_get($unitData, 'deity'))
                {
                    $deityId = Deity::where('key', $deityKey)->first()->id;
                }

                $this->info("Unit {$slot}: {$unitName}", OutputInterface::VERBOSITY_VERBOSE);

                $where = [
                    'race_id' => $race->id,
                    'slot' => $slot,
                ];

                $unit = Unit::where($where)->first();

                if ($unit === null) {
                    $unit = Unit::make($where);
                }

                $unit->fill([
                    'name' => $unitName,
                    'type' => object_get($unitData, 'type'),
                    'cost' => object_get($unitData, 'cost', []),
                    'power_offense' => object_get($unitData, 'power.offense', 0),
                    'power_defense' => object_get($unitData, 'power.defense', 0),
                    'static_networth' => object_get($unitData, 'static_networth', 0),
                    'training_time' => object_get($unitData, 'training_time', 12),
                    'deity_id' => $deityId,
                ]);

                if ($unit->exists) {
                    $newValues = $unit->getDirty();
                    /*
                    foreach ($newValues as $key => $newValue)
                    {
                        $originalValue = $unit->getOriginal($key);

                        if(is_array($originalValue))
                        {
                            $originalValue = implode(',', $originalValue);
                        }
                        if(is_array($newValue))
                        {
                            $newValue = implode(',', $newValue);
                        }

                        $this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                    }
                    */
                }

                $unit->save();
                $unit->refresh();

                // Unit perks
                $unitPerksToSync = [];

                foreach (object_get($unitData, 'perks', []) as $perk => $value)
                {
                    $value = (string)$value; // Can have multiple values for a perk, comma separated. todo: Probably needs a refactor later to JSON

                    $unitPerkType = UnitPerkType::firstOrCreate(['key' => $perk]);

                    $unitPerksToSync[$unitPerkType->id] = ['value' => $value];

                    $unitPerk = UnitPerk::query()
                        ->where('unit_id', $unit->id)
                        ->where('unit_perk_type_id', $unitPerkType->id)
                        ->first();

                    if ($unitPerk === null)
                    {
                        $this->info("[Add Unit Perk] {$perk}: {$value}");
                    }
                    elseif ($unitPerk->value != $value)
                    {
                        $this->info("[Change Unit Perk] {$perk}: {$unitPerk->value} -> {$value}");
                    }
                }

                $unit->perks()->sync($unitPerksToSync);
            }

            // Delete units, unless this is a new race
            if($race->exists)
            {
                foreach($slotsToDelete as $slotToDelete)
                {
                    $unitToDelete = Unit::where('race_id', $race->id)->where('slot', $slotToDelete)->first();
                    if($unitToDelete)
                    {
                        $unitPerksToDelete = UnitPerk::where('unit_id', $unitToDelete->id)->get();
    
                        $this->info("[Delete Unit] {$unit->name}");
        
                        foreach($unitPerksToDelete as $unitPerkToDelete)
                        {
                            $this->info("[Deleting Unit Perks] ...");
                            $unitPerkToDelete->delete();
                        }
        
                        $unitToDelete->delete();
                    }
                }
            }
        }

        $this->info('Races and units synced.');
    }

    /**
     * Syncs tech and perk data from .yml file to the database.
     */
    protected function syncTechs()
    {
        $fileContents = $this->filesystem->get(base_path('app/data/techs.yml'));
        $techsToSync = [];

        $data = Yaml::parse($fileContents, Yaml::PARSE_OBJECT_FOR_MAP);

        foreach ($data as $techKey => $techData)
        {

            $techsToSync[] = $techData->name;

            // Tech
            $tech = Tech::firstOrNew(['key' => $techKey])
                ->fill([
                    'name' => $techData->name,
                    'prerequisites' => object_get($techData, 'requires', []),
                    'excluded_races' => object_get($techData, 'excluded_races', []),
                    'exclusive_races' => object_get($techData, 'exclusive_races', []),
                    'level' => $techData->level,
                    'enabled' => (int)object_get($techData, 'enabled', 1),
                ]);

            if (!$tech->exists) {
                $this->info("Adding tech {$techData->name}");
            } else {
                $this->info("Processing tech {$techData->name}");

                $newValues = $tech->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $tech->getOriginal($key);

                    if(is_array($originalValue))
                    {
                        $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    $this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $tech->save();
            $tech->refresh();

            // Tech Perks
            $techPerksToSync = [];

            foreach (object_get($techData, 'perks', []) as $perk => $value) {
                $value = (float)$value;

                $techPerkType = TechPerkType::firstOrCreate(['key' => $perk]);

                $techPerksToSync[$techPerkType->id] = ['value' => $value];

                $techPerk = TechPerk::query()
                    ->where('tech_id', $tech->id)
                    ->where('tech_perk_type_id', $techPerkType->id)
                    ->first();

                if ($techPerk === null) {
                    $this->info("[Add Tech Perk] {$perk}: {$value}");
                } elseif ($techPerk->value != $value) {
                    $this->info("[Change Tech Perk] {$perk}: {$techPerk->value} -> {$value}");
                }
            }

            $tech->perks()->sync($techPerksToSync);
        }

        foreach(Tech::all() as $tech)
        {
            if(!in_array($tech->name, $techsToSync))
            {
                $this->info(">> Deleting tech {$tech->name}");

                TechPerk::where('tech_id', $tech->id)->delete();
                #$tech->perks()->detach();

                DominionTech::where('tech_id', '=', $tech->id)->delete();

                $tech->delete();
            }
        }

        $this->info('Techs synced.');
    }

    /**
     * Syncs building and perk data from .yml file to the database.
     */
    protected function syncBuildings()
    {
        $fileContents = $this->filesystem->get(base_path('app/data/buildings.yml'));
        $buildingsToSync = [];

        $data = Yaml::parse($fileContents, Yaml::PARSE_OBJECT_FOR_MAP);

        foreach ($data as $buildingKey => $buildingData)
        {

            $buildingsToSync[] = $buildingKey;

            // Building
            $building = Building::firstOrNew(['key' => $buildingKey])
                ->fill([
                    'name' => $buildingData->name,
                    'land_type' => object_get($buildingData, 'land_type'),
                    'excluded_races' => object_get($buildingData, 'excluded_races', []),
                    'exclusive_races' => object_get($buildingData, 'exclusive_races', []),
                    'enabled' => (int)object_get($buildingData, 'enabled', 1),
                ]);


            if (!$building->exists) {
                $this->info("Adding building {$buildingData->name}");
            } else {
                $this->info("Processing building {$buildingData->name}");

                $newValues = $building->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $building->getOriginal($key);

                    if(is_array($originalValue))
                    {
                        $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    $this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $building->save();
            $building->refresh();

            // Building Perks
            $buildingPerksToSync = [];

            foreach (object_get($buildingData, 'perks', []) as $perk => $value)
            {
                $value = (string)$value;

                $buildingPerkType = BuildingPerkType::firstOrCreate(['key' => $perk]);

                $buildingPerksToSync[$buildingPerkType->id] = ['value' => $value];

                $buildingPerk = BuildingPerk::query()
                    ->where('building_id', $building->id)
                    ->where('building_perk_type_id', $buildingPerkType->id)
                    ->first();

                if ($buildingPerk === null)
                {
                    $this->info("[Add Building Perk] {$perk}: {$value}");
                }
                elseif ($buildingPerk->value != $value)
                {
                    $this->info("[Change Building Perk] {$perk}: {$buildingPerk->value} -> {$value}");
                }
            }

            $building->perks()->sync($buildingPerksToSync);
        }

        foreach(Building::all() as $building)
        {
            if(!in_array($building->key, $buildingsToSync))
            {
                $this->info(">> Deleting building {$building->name}");

                #$building->perks->detach();

                BuildingPerk::where('building_id', $building->id)->delete();
                
                DominionBuilding::where('building_id', '=', $building->id)->delete();
                
                #$building->delete();
            }
        }

        $this->info('Buildings synced.');
    }

    /**
     * Syncs titles and perk data from .yml file to the database.
     */
    protected function syncTitles()
    {
        $fileContents = $this->filesystem->get(base_path('app/data/titles.yml'));
        $titlesToSync = [];

        $data = Yaml::parse($fileContents, Yaml::PARSE_OBJECT_FOR_MAP);

        foreach ($data as $titleKey => $titleData)
        {

            $titlesToSync[] = $titleData->name;

            // Title
            $title = Title::firstOrNew(['key' => $titleKey])
                ->fill([
                    'name' => $titleData->name,
                    'enabled' => (int)object_get($titleData, 'enabled', 1),
                ]);

            if (!$title->exists) {
                $this->info("Adding title {$titleData->name}");
            } else {
                $this->info("Processing title {$titleData->name}");

                $newValues = $title->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $title->getOriginal($key);

                    if(is_array($originalValue))
                    {
                        $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    $this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $title->save();
            $title->refresh();

            // Title Perks
            $titlePerksToSync = [];

            foreach (object_get($titleData, 'perks', []) as $perk => $value)
            {
                $value = (string)$value;

                $titlePerkType = TitlePerkType::firstOrCreate(['key' => $perk]);

                $titlePerksToSync[$titlePerkType->id] = ['value' => $value];

                $titlePerk = TitlePerk::query()
                    ->where('title_id', $title->id)
                    ->where('title_perk_type_id', $titlePerkType->id)
                    ->first();

                if ($titlePerk === null)
                {
                    $this->info("[Add Title Perk] {$perk}: {$value}");
                }
                elseif ($titlePerk->value != $value)
                {
                    $this->info("[Change Title Perk] {$perk}: {$titlePerk->value} -> {$value}");
                }
            }

            $title->perks()->sync($titlePerksToSync);
        }

        foreach(Title::all() as $title)
        {
            if(!in_array($title->name, $titlesToSync))
            {
                $this->info(">> Deleting title {$title->name}");

                #TechPerk::where('tech_id', $title->id)->delete();
                #DominionTech::where('tech_id', '=', $title->id)->delete();

                # Add unset from dominion

                #$title->delete();
            }
        }

        $this->info('Titles synced.');
    }

    /**
     * Syncs spells and perk data from .yml file to the database.
     */
    protected function syncSpells()
    {
        $fileContents = $this->filesystem->get(base_path('app/data/spells.yml'));
        $spellsToSync = [];

        $data = Yaml::parse($fileContents, Yaml::PARSE_OBJECT_FOR_MAP);

        foreach ($data as $spellKey => $spellData)
        {

            $spellsToSync[] = $spellData->name;

            $deityId = null;
            if($deityKey = object_get($spellData, 'deity'))
            {
                $deityId = Deity::where('key', $deityKey)->first()->id;
            }

            // Spell
            $spell = Spell::firstOrNew(['key' => $spellKey])
                ->fill([
                    'name' => $spellData->name,
                    'scope' => object_get($spellData, 'scope'),
                    'class' => object_get($spellData, 'class'),
                    'cost' => object_get($spellData, 'cost', 1),
                    'duration' => (float)object_get($spellData, 'duration', 0),
                    'cooldown' => object_get($spellData, 'cooldown', 0),
                    'wizard_strength' => object_get($spellData, 'wizard_strength'),
                    'deity_id' => $deityId,
                    'enabled' => object_get($spellData, 'enabled', 1),
                    'excluded_races' => object_get($spellData, 'excluded_races', []),
                    'exclusive_races' => object_get($spellData, 'exclusive_races', []),
                ]);

            if (!$spell->exists) {
                $this->info("Adding spell {$spellData->name}");
            } else {
                $this->info("Processing spell {$spellData->name}");

                $newValues = $spell->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $spell->getOriginal($key);

                    if(is_array($originalValue))
                    {
                        $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    $this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $spell->save();
            $spell->refresh();

            // Spell Perks
            $spellPerksToSync = [];

            foreach (object_get($spellData, 'perks', []) as $perk => $value)
            {
                $value = (string)$value;

                $spellPerkType = SpellPerkType::firstOrCreate(['key' => $perk]);

                $spellPerksToSync[$spellPerkType->id] = ['value' => $value];

                $spellPerk = SpellPerk::query()
                    ->where('spell_id', $spell->id)
                    ->where('spell_perk_type_id', $spellPerkType->id)
                    ->first();

                if ($spellPerk === null)
                {
                    $this->info("[Add Spell Perk] {$perk}: {$value}");
                }
                elseif ($spellPerk->value != $value)
                {
                    $this->info("[Change Spell Perk] {$perk}: {$spellPerk->value} -> {$value}");
                }
            }

            $spell->perks()->sync($spellPerksToSync);
        }

        foreach(Spell::all() as $spell)
        {
            if(!in_array($spell->name, $spellsToSync))
            {
                $this->info(">> Deleting spell {$spell->name}");

                SpellPerk::where('spell_id', $spell->id)->delete();

                #$spell->perks->detach();

                DominionSpell::where('spell_id', '=', $spell->id)->delete();

                $spell->delete();
            }
        }

        $this->info('Spells synced.');
    }

    /**
     * Syncs spells and perk data from .yml file to the database.
     */
    protected function syncSpyops()
    {
        $fileContents = $this->filesystem->get(base_path('app/data/spyops.yml'));
        $spyopsToSync = [];

        $data = Yaml::parse($fileContents, Yaml::PARSE_OBJECT_FOR_MAP);

        foreach ($data as $spyopKey => $spyopData)
        {

            $spyopsToSync[] = $spyopData->name;
            
            // Spell
            $spyop = Spyop::firstOrNew(['key' => $spyopKey])
                ->fill([
                    'name' => $spyopData->name,
                    'scope' => object_get($spyopData, 'scope'),
                    'spy_strength' => object_get($spyopData, 'spy_strength'),
                    'enabled' => object_get($spyopData, 'enabled', 1),
                    'excluded_races' => object_get($spyopData, 'excluded_races', []),
                    'exclusive_races' => object_get($spyopData, 'exclusive_races', []),
                ]);

            if (!$spyop->exists) {
                $this->info("Adding spyop {$spyopData->name}");
            } else {
                $this->info("Processing spyop {$spyopData->name}");

                $newValues = $spyop->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $spyop->getOriginal($key);

                    if(is_array($originalValue))
                    {
                        $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    $this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $spyop->save();
            $spyop->refresh();

            // Spyop Perks
            $spyopPerksToSync = [];

            foreach (object_get($spyopData, 'perks', []) as $perk => $value)
            {
                $value = (string)$value;

                $spyopPerkType = SpyopPerkType::firstOrCreate(['key' => $perk]);

                $spyopPerksToSync[$spyopPerkType->id] = ['value' => $value];

                $spyopPerk = SpyopPerk::query()
                    ->where('spyop_id', $spyop->id)
                    ->where('spyop_perk_type_id', $spyopPerkType->id)
                    ->first();

                if ($spyopPerk === null)
                {
                    $this->info("[Add Spyop Perk] {$perk}: {$value}");
                }
                elseif ($spyopPerk->value != $value)
                {
                    $this->info("[Change Spyop Perk] {$perk}: {$spyopPerk->value} -> {$value}");
                }
            }

            $spyop->perks()->sync($spyopPerksToSync);
        }

        foreach(Spyop::all() as $spyop)
        {
            if(!in_array($spyop->name, $spyopsToSync))
            {
                $this->info(">> Deleting spyop {$spyop->name}");

                SpyopPerk::where('spyop_id', $spyop->id)->delete();
                $spyop->delete();
            }
        }

        $this->info('Spy-ops synced.');
    }

    /**
     * Syncs improvements and perk data from .yml file to the database.
     */
    protected function syncImprovements()
    {
        $fileContents = $this->filesystem->get(base_path('app/data/improvements.yml'));
        $improvementsToSync = [];

        $data = Yaml::parse($fileContents, Yaml::PARSE_OBJECT_FOR_MAP);

        foreach ($data as $improvementKey => $improvementData)
        {

            $improvementsToSync[] = $improvementData->name;

            // Spell
            $improvement = Improvement::firstOrNew(['key' => $improvementKey])
                ->fill([
                    'name' => $improvementData->name,
                    'enabled' => object_get($improvementData, 'enabled', 1),
                    'excluded_races' => object_get($improvementData, 'excluded_races', []),
                    'exclusive_races' => object_get($improvementData, 'exclusive_races', []),
                ]);

            if (!$improvement->exists) {
                $this->info("Adding improvement {$improvementData->name}");
            } else {
                $this->info("Processing improvement {$improvementData->name}");

                $newValues = $improvement->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $improvement->getOriginal($key);

                    if(is_array($originalValue))
                    {
                        $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    $this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $improvement->save();
            $improvement->refresh();

            // Spell Perks
            $improvementPerksToSync = [];

            foreach (object_get($improvementData, 'perks', []) as $perk => $value)
            {
                $value = (string)$value;

                $improvementPerkType = ImprovementPerkType::firstOrCreate(['key' => $perk]);

                $improvementPerksToSync[$improvementPerkType->id] = ['value' => $value];

                $improvementPerk = ImprovementPerk::query()
                    ->where('improvement_id', $improvement->id)
                    ->where('improvement_perk_type_id', $improvementPerkType->id)
                    ->first();

                if ($improvementPerk === null)
                {
                    $this->info("[Add Improvement Perk] {$perk}: {$value}");
                }
                elseif ($improvementPerk->value != $value)
                {
                    $this->info("[Change Improvement Perk] {$perk}: {$improvementPerk->value} -> {$value}");
                }
            }

            $improvement->perks()->sync($improvementPerksToSync);
        }

        foreach(Improvement::all() as $improvement)
        {
            if(!in_array($improvement->name, $improvementsToSync))
            {
                $this->info(">> Deleting spell {$improvement->name}");

                #$improvement->perks()->detach();
                ImprovementPerk::where('spell_id', $improvement->id)->delete();

                DominionImprovement::where('spell_id', '=', $improvement->id)->delete();

                $improvement->delete();
            }
        }

        $this->info('Improvements synced.');
    }

    /**
     * Syncs stats from .yml file to the database.
     */
    protected function syncStats()
    {
        $fileContents = $this->filesystem->get(base_path('app/data/stats.yml'));
        $statsToSync = [];

        $data = Yaml::parse($fileContents, Yaml::PARSE_OBJECT_FOR_MAP);

        foreach ($data as $statKey => $statData)
        {

            $statsToSync[] = $statData->name;

            // Spell
            $stat = Stat::firstOrNew(['key' => $statKey])
                ->fill([
                    'name' => $statData->name,
                    'enabled' => object_get($statData, 'enabled', 1)
                ]);

            if (!$stat->exists) {
                $this->info("Adding stat {$statData->name}");
            } else {
                $this->info("Processing stat {$statData->name}");

                $newValues = $stat->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $stat->getOriginal($key);

                    if(is_array($originalValue))
                    {
                        $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    $this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $stat->save();
            $stat->refresh();
        }

        foreach(Stat::all() as $stat)
        {

            if(!in_array($stat->name, $statsToSync))
            {
                $this->info(">> Deleting stat {$stat->name}");

                DominionStat::where('stat_id', '=', $stat->id)->delete();
                $stat->delete();
            }
        }

        $this->info('Stats synced.');
    }

    /**
     * Syncs resources from .yml file to the database.
     */
    protected function syncResources()
    {
        $fileContents = $this->filesystem->get(base_path('app/data/resources.yml'));
        $resourcesToSync = [];

        $data = Yaml::parse($fileContents, Yaml::PARSE_OBJECT_FOR_MAP);

        foreach ($data as $resourceKey => $resourceData)
        {

            $resourcesToSync[] = $resourceData->name;

            // Resource
            $resource = Resource::firstOrNew(['key' => $resourceKey])
                ->fill([
                    'name' => $resourceData->name,
                    'enabled' => object_get($resourceData, 'enabled', 1),
                    'buy' => object_get($resourceData, 'buy', null),
                    'sell' => object_get($resourceData, 'sell', null),
                    'description' => object_get($resourceData, 'description', 'No description'),
                    'excluded_races' => object_get($resourceData, 'excluded_races', []),
                    'exclusive_races' => object_get($resourceData, 'exclusive_races', []),
                ]);

            if (!$resource->exists) {
                $this->info("Adding resource {$resourceData->name}");
            } else {
                $this->info("Processing resource {$resourceData->name}");

                $newValues = $resource->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $resource->getOriginal($key);

                    if(is_array($originalValue))
                    {
                        $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    $this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $resource->save();
            $resource->refresh();
        }

        foreach(Resource::all() as $resource)
        {
            if(!in_array($resource->name, $resourcesToSync))
            {
                $this->info(">> Deleting resource {$resource->name}");

                DominionResource::where('resource_id', '=', $resource->id)->delete();

                $resource->delete();
            }
        }

        $this->info('Resources synced.');
    }

    /**
     * Syncs spells and perk data from .yml file to the database.
     */
    protected function syncDeities()
    {
        $fileContents = $this->filesystem->get(base_path('app/data/deities.yml'));
        $deitiesToSync = [];

        $data = Yaml::parse($fileContents, Yaml::PARSE_OBJECT_FOR_MAP);

        foreach ($data as $deityKey => $deityData)
        {

            $deitiesToSync[] = $deityData->name;

            // Deity
            $deity = Deity::firstOrNew(['key' => $deityKey])
                ->fill([
                    'name' => $deityData->name,
                    'enabled' => object_get($deityData, 'enabled', 1),
                    'range_multiplier' => object_get($deityData, 'range_multiplier', 0.75),
                    'excluded_races' => object_get($deityData, 'excluded_races', []),
                    'exclusive_races' => object_get($deityData, 'exclusive_races', []),
                ]);

            if (!$deity->exists) {
                $this->info("Adding deity {$deityData->name}");
            } else {
                $this->info("Processing deity {$deityData->name}");

                $newValues = $deity->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $deity->getOriginal($key);

                    if(is_array($originalValue))
                    {
                        $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    $this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $deity->save();
            $deity->refresh();

            // Deity Perks
            $deityPerksToSync = [];

            foreach (object_get($deityData, 'perks', []) as $perk => $value)
            {
                $value = (string)$value;

                $deityPerkType = DeityPerkType::firstOrCreate(['key' => $perk]);

                $deityPerksToSync[$deityPerkType->id] = ['value' => $value];

                $deityPerk = DeityPerk::query()
                    ->where('deity_id', $deity->id)
                    ->where('deity_perk_type_id', $deityPerkType->id)
                    ->first();

                if ($deityPerk === null)
                {
                    $this->info("[Add Deity Perk] {$perk}: {$value}");
                }
                elseif ($deityPerk->value != $value)
                {
                    $this->info("[Change Deity Perk] {$perk}: {$deityPerk->value} -> {$value}");
                }
            }

            $deity->perks()->sync($deityPerksToSync);
        }


        foreach(Deity::all() as $deity)
        {
            if(!in_array($deity->name, $deitiesToSync))
            {
                $this->info(">> Deleting deity {$deity->name}");

                DeityPerk::where('deity_id', '=', $deity->id)->delete();
                #$deity->perks()->detach();

                $deity->delete();
            }
        }

        $this->info('Deities synced.');
    }

    /**
     * Syncs spells and perk data from .yml file to the database.
     */
    protected function syncArtefacts()
    {
        $fileContents = $this->filesystem->get(base_path('app/data/artefacts.yml'));
        $artefactsToSync = [];

        $data = Yaml::parse($fileContents, Yaml::PARSE_OBJECT_FOR_MAP);

        foreach ($data as $artefactKey => $artefactData)
        {

            $artefactsToSync[] = $artefactData->name;

            $deityId = null;
            if($deityKey = object_get($artefactData, 'deity'))
            {
                $deityId = Deity::where('key', $deityKey)->first()->id;
            }

            // Artefact
            $artefact = Artefact::firstOrNew(['key' => $artefactKey])
                ->fill([
                    'name' => $artefactData->name,
                    'enabled' => object_get($artefactData, 'enabled', 1),
                    'description' => object_get($artefactData, 'description', ''),
                    'deity_id' => $deityId,
                    'base_power' => object_get($artefactData, 'base_power', 100000),
                    'excluded_races' => object_get($artefactData, 'excluded_races', []),
                    'exclusive_races' => object_get($artefactData, 'exclusive_races', []),
                ]);

            if (!$artefact->exists) {
                $this->info("Adding artefact {$artefactData->name}");
            } else {
                $this->info("Processing artefact {$artefactData->name}");

                $newValues = $artefact->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $artefact->getOriginal($key);

                    if(is_array($originalValue))
                    {
                        $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    $this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $artefact->save();
            $artefact->refresh();

            // Artefact Perks
            $artefactPerksToSync = [];

            foreach (object_get($artefactData, 'perks', []) as $perk => $value)
            {
                $value = (string)$value;

                $artefactPerkType = ArtefactPerkType::firstOrCreate(['key' => $perk]);

                $artefactPerksToSync[$artefactPerkType->id] = ['value' => $value];

                $artefactPerk = ArtefactPerk::query()
                    ->where('artefact_id', $artefact->id)
                    ->where('artefact_perk_type_id', $artefactPerkType->id)
                    ->first();

                if ($artefactPerk === null)
                {
                    $this->info("[Add Artefact Perk] {$perk}: {$value}");
                }
                elseif ($artefactPerk->value != $value)
                {
                    $this->info("[Change Artefact Perk] {$perk}: {$artefactPerk->value} -> {$value}");
                }
            }

            $artefact->perks()->sync($artefactPerksToSync);
        }

        foreach(Artefact::all() as $artefact)
        {
            if(!in_array($artefact->name, $artefactsToSync))
            {
                $this->info(">> Deleting artefact {$artefact->name}");

                #$artefact->perks()->detach();
                ArtefactPerk::where('artefact_id', '=', $artefact->id)->delete();

                RealmArtefact::where('artefact_id', '=', $artefact->id)->delete();

                $artefact->delete();
            }
        }

        $this->info('Artefacts synced.');
    }

    /**
     * Syncs race, unit and perk data from .yml files to the database.
     */
    protected function syncQuickstarts()
    {
        $files = $this->filesystem->files(base_path('app/data/quickstarts'));
        $quickstartsToSync = [];

        foreach ($files as $file)
        {
            $data = Yaml::parse($file->getContents(), Yaml::PARSE_OBJECT_FOR_MAP);

            $race = Race::where('name', $data->race)->first();

            $quickstartsToSync[] = $data->name;

            if(isset($data->deity))
            {
                $deity = Deity::where('name', $data->deity)->first();
            }
            else
            {
                $deity = null;
            }

            if(isset($data->title))
            {
                $title = Title::where('name', $data->title)->first();
            }

            // Quickstart
            $quickstart = Quickstart::firstOrNew(['name' => $data->name])
                ->fill([
                    'name' => $data->name ?: ($race->name . ' Quickstart'),
                    'description' => object_get($data, 'description'),
                    'race_id' => $race->id,
                    'deity_id' => isset($deity) ? $deity->id : null,
                    'title_id' => isset($title) ? $title->id : null,
                    'enabled' => object_get($data, 'enabled', 1),
                    'offensive_power' => object_get($data, 'offensive_power', 0),
                    'defensive_power' => object_get($data, 'defensive_power', 0),
                    
                    'draft_rate' => object_get($data, 'draft_rate', 50),
                    'devotion_ticks' => isset($deity) ? max(min(object_get($data, 'devotion_ticks', 0), 96),0) : 0,
                    'morale' => object_get($data, 'morale', 100),
                    'peasants' => object_get($data, 'peasants', 2000),
                    'prestige' => object_get($data, 'prestige', 400),
                    'protection_ticks' => max(min(object_get($data, 'protection_ticks', 0), 96),0),
                    'spy_strength' => object_get($data, 'spy_strength', 100),
                    'wizard_strength' => object_get($data, 'wizard_strength', 100),
                    'xp' => object_get($data, 'xp', 0),

                    'buildings' => object_get($data, 'buildings', []),
                    'improvements' => object_get($data, 'improvements', []),
                    'land' => object_get($data, 'land', []),
                    'resources' => object_get($data, 'resources', []),
                    'spells' => object_get($data, 'spells', []),
                    'advancements' => object_get($data, 'advancements', []),
                    'decree_states' => object_get($data, 'decree_states', []),
                    'techs' => object_get($data, 'techs', []),
                    'units' => object_get($data, 'units', []),
                    'queues' => object_get($data, 'queues', []),
                ]);

            if (!$quickstart->exists) {
                $this->info("Adding quickstart {$data->name}");
            } else {
                $this->info("Processing quickstart {$data->name}");

                $newValues = $race->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $race->getOriginal($key);

                    if(is_array($originalValue))
                    {
                    #    $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    #$this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $quickstart->save();
            $quickstart->refresh();
        }

        foreach(Quickstart::all() as $quickstart)
        {
            if(!in_array($quickstart->name, $quickstartsToSync))
            {
                $this->info(">> Deleting quickstart {$quickstart->name}");
                $quickstart->delete();
            }
        }

        $this->info('Quickstarts synced.');
    }



    /**
     * Syncs race, unit and perk data from .yml files to the database.
     */
    protected function syncDecrees()
    {
        $files = $this->filesystem->files(base_path('app/data/decrees'));
        $decreesToSync = [];

        foreach ($files as $file)
        {
            $data = Yaml::parse($file->getContents(), Yaml::PARSE_OBJECT_FOR_MAP);

            $decreesToSync[] = $data->name;

            $deityId = null;
            if($deityKey = object_get($data, 'deity'))
            {
                $deityId = Deity::where('key', $deityKey)->first()->id;
            }

            $titleId = null;
            if($titleKey = object_get($data, 'title'))
            {
                $titleId = Title::where('key', $titleKey)->first()->id;
            }

            // Race
            $decree = Decree::firstOrNew(['name' => $data->name])
                ->fill([
                    'name' => object_get($data, 'name'),
                    'key' => $this->generateKeyFromNameString(object_get($data, 'name')),
                    'enabled' => object_get($data, 'enabled', 1),
                    'cooldown' => object_get($data, 'cooldown', 96),
                    'deity' => $deityId,
                    'title' => $titleId,
                    'description' => object_get($data, 'description'),
                    'excluded_races' => object_get($data, 'excluded_races', []),
                    'exclusive_races' => object_get($data, 'exclusive_races', []),
                ]);

            if (!$decree->exists) {
                $this->info("Adding decree {$data->name}");
            } else {
                $this->info("Processing decree {$data->name}");

                $newValues = $decree->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $decree->getOriginal($key);

                    if(is_array($originalValue))
                    {
                    #    $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    #$this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $decree->save();
            $decree->refresh();

            // States
            foreach (object_get($data, 'states', []) as $state => $stateData)
            {
                $stateName = object_get($stateData, 'name');

                $this->info("State {$state}: {$stateName}", OutputInterface::VERBOSITY_VERBOSE);

                $where = [
                    'decree_id' => $decree->id,
                    'name' => $stateName,
                ];

                $decreeState = DecreeState::where($where)->first();

                if ($decreeState === null) {
                    $decreeState = DecreeState::make($where);
                }

                $decreeState->fill([
                    'name' => $stateName,
                    'key' => $this->generateKeyFromNameString($decree->name . '_' . $stateName),
                    'enabled' => object_get($stateData, 'enabled', 1),
                ]);

                if ($decreeState->exists)
                {
                    $newValues = $decreeState->getDirty();
                    
                    foreach ($newValues as $key => $newValue)
                    {
                        $originalValue = $decreeState->getOriginal($key);

                        if(is_array($originalValue))
                        {
                            $originalValue = implode(',', $originalValue);
                        }
                        if(is_array($newValue))
                        {
                            $newValue = implode(',', $newValue);
                        }

                        $this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                    }
                }

                $decreeState->save();
                $decreeState->refresh();

                // Decree state perks
                $decreeStatePerksToSync = [];

                foreach (object_get($stateData, 'perks', []) as $perk => $value)
                {
                    $value = (string)$value; // Can have multiple values for a perk, comma separated. todo: Probably needs a refactor later to JSON

                    $decreeStatePerkType = DecreeStatePerkType::firstOrCreate(['key' => $perk]);

                    $decreeStatePerksToSync[$decreeStatePerkType->id] = ['value' => $value];

                    $decreeStatePerk = DecreeStatePerk::query()
                        ->where('decree_state_id', $decreeState->id)
                        ->where('decree_state_perk_type_id', $decreeStatePerkType->id)
                        ->first();

                    if ($decreeStatePerk === null)
                    {
                        $this->info("[Add Decree State Perk] {$perk}: {$value}");
                    }
                    elseif ($decreeStatePerk->value != $value)
                    {
                        $this->info("[Change Decree State Perk] {$perk}: {$decreeStatePerk->value} -> {$value}");
                    }
                }

                $decreeState->perks()->sync($decreeStatePerksToSync);
            }
        }

        foreach(Decree::all() as $decree)
        {
            if(!in_array($decree->name, $decreesToSync))
            {
                $this->info(">> Deleting decree {$decree->name}");

                $decreeStates = DecreeState::all()->where('decree_id', '=', $decree->id);
                                
                foreach($decreeStates as $decreeState)
                {
                    DecreeStatePerk::where('decree_state_id', '=', $decreeState->id)->delete();
                    $this->info(">>>> Deleting decree state {$decreeState->name}");
                }

                DominionDecreeState::where('decree_id', '=', $decree->id)->delete();
                DecreeState::where('decree_id', '=', $decree->id)->delete();
                $decree->delete();
            }
        }

        $this->info('Decrees synced.');
    }

    /**
     * Syncs advancement and perk data from .yml file to the database.
     */
    protected function syncAdvancements()
    {
        $fileContents = $this->filesystem->get(base_path('app/data/advancements.yml'));
        $advancementsToSync = [];

        $data = Yaml::parse($fileContents, Yaml::PARSE_OBJECT_FOR_MAP);

        foreach ($data as $advancementKey => $advancementData)
        {

            $advancementsToSync[] = $advancementData->name;
            
            // Advancement
            $advancement = Advancement::firstOrNew(['key' => $advancementKey])
                ->fill([
                    'name' => $advancementData->name,
                    'enabled' => object_get($advancementData, 'enabled', 1),
                    'excluded_races' => object_get($advancementData, 'excluded_races', []),
                    'exclusive_races' => object_get($advancementData, 'exclusive_races', []),
                ]);

            if (!$advancement->exists) {
                $this->info("Adding advancement {$advancementData->name}");
            } else {
                $this->info("Processing advancement {$advancementData->name}");

                $newValues = $advancement->getDirty();

                foreach ($newValues as $key => $newValue)
                {
                    $originalValue = $advancement->getOriginal($key);

                    if(is_array($originalValue))
                    {
                        $originalValue = implode(',', $originalValue);
                    }
                    if(is_array($newValue))
                    {
                        $newValue = implode(',', $newValue);
                    }

                    $this->info("[Change] {$key}: {$originalValue} -> {$newValue}");
                }
            }

            $advancement->save();

            $advancement->refresh();

            // Advancement Perks
            $advancementPerksToSync = [];

            foreach (object_get($advancementData, 'perks', []) as $perk => $value) {
                $value = (float)$value;

                $advancementPerkType = AdvancementPerkType::firstOrCreate(['key' => $perk]);

                $advancementPerksToSync[$advancementPerkType->id] = ['value' => $value];

                $advancementPerk = AdvancementPerk::query()
                    ->where('advancement_id', $advancement->id)
                    ->where('advancement_perk_type_id', $advancementPerkType->id)
                    ->first();

                if ($advancementPerk === null) {
                    $this->info("[Add Advancement Perk] {$perk}: {$value}");
                } elseif ($advancementPerk->value != $value) {
                    $this->info("[Change Advancement Perk] {$perk}: {$advancementPerk->value} -> {$value}");
                }
            }

            $advancement->perks()->sync($advancementPerksToSync);
        }

        foreach(Advancement::all() as $advancement)
        {
            if(!in_array($advancement->name, $advancementsToSync))
            {
                $this->info(">> Deleting advancement {$advancement->name}");

                #$advancement->perks()->detach();
                AdvancementPerk::where('advancement_id', '=', $advancement->id)->delete();

                DominionAdvancement::where('advancement_id', '=', $advancement->id)->delete();

                $advancement->delete();
            }
        }

        $this->info('Advancements synced.');
    }

    protected function generateKeyFromNameString(string $name): string
    {
        return preg_replace("/[^a-zA-Z0-9\_]+/", "",str_replace(' ', '_', strtolower($name)));
    }

}
