<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Models\Artefact;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Spell;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\API\ArtefactAttackCalculationRequest;
use OpenDominion\Http\Requests\Dominion\API\ExpeditionCalculationRequest;
use OpenDominion\Http\Requests\Dominion\API\InvadeCalculationRequest;
use OpenDominion\Http\Requests\Dominion\API\SorceryCalculationRequest;

use OpenDominion\Services\Dominion\API\ArtefactAttackCalculationService;
use OpenDominion\Services\Dominion\API\DefenseCalculationService;
use OpenDominion\Services\Dominion\API\ExpeditionCalculationService;
use OpenDominion\Services\Dominion\API\DesecrationCalculationService;
use OpenDominion\Services\Dominion\API\InvadeCalculationService;
use OpenDominion\Services\Dominion\API\OffenseCalculationService;
use OpenDominion\Services\Dominion\API\SorceryCalculationService;

class APIController extends AbstractDominionController
{
    public function calculateInvasion(InvadeCalculationRequest $request): array
    {
        $dominion = $this->getSelectedDominion();
        $invadeCalculationService = app(InvadeCalculationService::class);

        try {
            $result = $invadeCalculationService->calculate(
                $dominion,
                Dominion::find($request->get('target_dominion')),
                $request->get('unit'),
                $request->get('calc')
            );
        } catch (GameException $e) {
            return [
                'result' => 'error',
                'errors' => [$e->getMessage()]
            ];
        }

        return $result;
    }

    public function calculateArtefactAttack(ArtefactAttackCalculationRequest $request): array
    {
        $dominion = $this->getSelectedDominion();
        $artefactAttackeCalculationService = app(ArtefactAttackCalculationService::class);

        try {
            $result = $artefactAttackeCalculationService->calculate(
                $dominion,
                Artefact::find($request->get('target_artefact')),
                $request->get('unit'),
                $request->get('calc'),
            );
        } catch (GameException $e) {
            return [
                'result' => 'error',
                'errors' => [$e->getMessage()]
            ];
        }

        return $result;
    }

    public function calculateExpedition(ExpeditionCalculationRequest $request): array
    {
        $dominion = $this->getSelectedDominion();
        $expeditionCalculationService = app(ExpeditionCalculationService::class);

        try {
            $result = $expeditionCalculationService->calculate(
                $dominion,
                $request->get('unit'),
                $request->get('calc')
            );
        } catch (GameException $e) {
            return [
                'result' => 'error',
                'errors' => [$e->getMessage()]
            ];
        }

        return $result;
    }

    public function calculateSorcery(SorceryCalculationRequest $request): array
    {
        $caster = $this->getSelectedDominion();
        $sorceryCalculationService = app(SorceryCalculationService::class);
        $spell = Spell::where('id',($request->get('spell')))->firstOrFail();
        $wizardStrength = (int)$request->get('wizard_strength');

        try {
            $result = $sorceryCalculationService->calculate(
                $caster,
                $spell,
                $wizardStrength
            );
        } catch (GameException $e) {
            return [
                'result' => 'error',
                'errors' => [$e->getMessage()]
            ];
        }

        return $result;
    }

    public function calculateDesecration(ExpeditionCalculationRequest $request): array
    {
        $dominion = $this->getSelectedDominion();
        $desecrationCalculationService = app(DesecrationCalculationService::class);

        try {
            $result = $desecrationCalculationService->calculate(
                $dominion,
                $request->get('unit'),
                $request->get('calc')
            );
        } catch (GameException $e) {
            return [
                'result' => 'error',
                'errors' => [$e->getMessage()]
            ];
        }

        return $result;
    }

    public function calculateDefense(InvadeCalculationRequest $request): array
    {
        $calculatingDominion = $this->getSelectedDominion();

        $calc = $request->get('calc');
        $units = array_fill(0, 5, 0);

        // Calculate units home and away
        if (isset($calc['draftees']))
        {
            $units[0] = $calc['draftees'];
        }
        elseif (isset($calc['draftees_home']))
        {
            $units[0] = (int) ($calc['draftees_home'] / 0.85);
        }

        foreach(range(1, $selectedDominion->race->units->count()) as $slot) {
            if (isset($calc["unit{$slot}"])) {
                $units[$slot] = $calc["unit{$slot}"];
                if (isset($calc["unit{$slot}_away"])) {
                    $unitsAway = (int) ($calc["unit{$slot}_away"] * 0.85);
                    $units[$slot] = max($units[$slot] - $unitsAway, 0);
                    if (isset($calc["unit{$slot}_home"]) && $unitsAway > 0) {
                        $unitsHome = (int) ($calc["unit{$slot}_home"] / 0.85);
                        if ($unitsHome < $units[$slot]) {
                            $units[$slot] = $unitsHome;
                        }
                    }
                }
            } elseif (isset($calc["unit{$slot}_home"])) {
                $units[$slot] = (int) ($calc["unit{$slot}_home"] / 0.85);
            }
        }

        $dominion = new Dominion([
            'race_id' => $request->get('race'),
            'prestige' => isset($calc['prestige']) ? $calc['prestige'] : 600,
            'morale' => isset($calc['morale']) ? $calc['morale'] : 100,

            'military_draftees' => $units[0],
            'military_unit1' => $units[1],
            'military_unit2' => $units[2],
            'military_unit3' => $units[3],
            'military_unit4' => $units[4],

            'land_plain' => isset($calc['land']) ? $calc['land'] : 1000,
            'land_mountain' => 0,
            'land_swamp' => 0,
            'land_cavern' => 0,
            'land_forest' => 0,
            'land_hill' => 0,
            'land_water' => 0
        ]);

        $dominion->round = $calculatingDominion->round;

        $dominion->title_id = $calc['title'];

        if(!isset($calc['realm']) or $calc['realm'] == 0)
        {
            $dominion->realm = $calculatingDominion->realm;
        }
        else
        {
            $dominion->realm = Realm::findOrFail($calc['realm']);
        }

        $defenseCalculationService = app(DefenseCalculationService::class);

        try {
            $result = $defenseCalculationService->calculate(
                $dominion,
                $request->get('calc')
            );
        } catch (GameException $e) {
            return [
                'result' => 'error',
                'errors' => [$e->getMessage()]
            ];
        }

        return $result;
    }

    public function calculateOffense(InvadeCalculationRequest $request): array
    {
        $calc = $request->get('calc');
        $units = array_fill(0, 5, 0);

        // Check for target information
        $target = null;
        $landRatio = null;
        if (isset($calc['target_race'])) {
            $target = new Dominion(['race_id' => $calc['target_race']]);
        }
        if (isset($calc['target_land'])) {
            if (isset($calc['land']) && $calc['land'] > 0) {
                $land = $calc['land'];
            } else {
                $land = 1;
            }
            $landRatio = $calc['target_land'] / $land;
        }

        // Calculate unit totals
        foreach(range(1, 4) as $slot) {
            $units[$slot] = $calc["unit{$slot}"] + $calc["unit{$slot}_inc"];
        }

        $dominion = new Dominion([
            'race_id' => $request->get('race'),
            'prestige' => isset($calc['prestige']) ? $calc['prestige'] : 250,
            'morale' => isset($calc['morale']) ? $calc['morale'] : 100,

            'military_draftees' => 0,
            'military_unit1' => $units[1],
            'military_unit2' => $units[2],
            'military_unit3' => $units[3],
            'military_unit4' => $units[4],

            'land_plain' => isset($calc['land']) ? $calc['land'] : 250,
            'land_mountain' => 0,
            'land_swamp' => 0,
            'land_cavern' => 0,
            'land_forest' => 0,
            'land_hill' => 0,
            'land_water' => 0,

            /*
            'building_home' => 0,
            'building_alchemy' => 0,
            'building_farm' => 0,
            'building_smithy' => 0,
            'building_masonry' => 0,
            'building_ore_mine' => 0,
            'building_gryphon_nest' => 0,
            'building_tower' => 0,
            'building_wizard_guild' => 0,
            'building_temple' => 0,
            'building_gem_mine' => 0,
            'building_school' => 0,
            'building_lumberyard' => 0,
            'building_forest_haven' => 0,
            'building_factory' => 0,
            'building_guard_tower' => 0,
            'building_shrine' => 0,
            'building_barracks' => 0,
            'building_dock' => 0,
            */
        ]);
        $offenseCalculationService = app(OffenseCalculationService::class);

        try {
            $result = $offenseCalculationService->calculate(
                $dominion,
                $request->get('calc'),
                $target,
                $landRatio
            );
        } catch (GameException $e) {
            return [
                'result' => 'error',
                'errors' => [$e->getMessage()]
            ];
        }

        return $result;
    }


}
