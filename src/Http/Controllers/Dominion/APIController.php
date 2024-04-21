<?php
/**
 * @OA\Info(
 *     title="ODARENA API",
 *     version="1.0.0",
 *     description="API for various endpoints for ODARENA",
 *     @OA\Contact(
 *         email="dreki@odarena.com"
 *     )
 * )
 */

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\TradeCalculator;
use OpenDominion\Models\Artefact;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\API\ArtefactAttackCalculationRequest;
use OpenDominion\Http\Requests\Dominion\API\ExpeditionCalculationRequest;
use OpenDominion\Http\Requests\Dominion\API\InvadeCalculationRequest;
use OpenDominion\Http\Requests\Dominion\API\SorceryCalculationRequest;
use OpenDominion\Http\Requests\Dominion\API\TradeCalculationRequest;

use OpenDominion\Services\Dominion\API\ArtefactAttackCalculationService;
use OpenDominion\Services\Dominion\API\DefenseCalculationService;
use OpenDominion\Services\Dominion\API\ExpeditionCalculationService;
use OpenDominion\Services\Dominion\API\DesecrationCalculationService;
use OpenDominion\Services\Dominion\API\InvadeCalculationService;
use OpenDominion\Services\Dominion\API\OffenseCalculationService;
use OpenDominion\Services\Dominion\API\SorceryCalculationService;

class APIController extends AbstractDominionController
{

    /**
     * @OA\Get(
     *     path="/api/v1/dominion/invasion",
     *     tags={"Calculators"},
     *     @OA\Response(
     *         response="200",
     *         description="Returns an array with data for invasion",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="number"))
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/dominion/artefact-attack",
     *     tags={"Calculators"},
     *     @OA\Response(
     *         response="200",
     *         description="Returns an array with data for artefact attack",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="number"))
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/dominion/expedition",
     *     tags={"Calculators"},
     *     @OA\Response(
     *         response="200",
     *         description="Returns an array with data for expedition",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="number"))
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/dominion/sorcery",
     *     tags={"Calculators"},
     *     @OA\Response(
     *         response="200",
     *         description="Returns an array with data for sorcery",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="number"))
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/dominion/desecration",
     *     tags={"Calculators"},
     *     @OA\Response(
     *         response="200",
     *         description="Returns an array with data for desecration",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="number"))
     *         )
     *     )
     * )
     */
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

}
