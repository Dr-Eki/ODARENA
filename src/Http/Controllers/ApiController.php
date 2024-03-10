<?php
namespace OpenDominion\Http\Controllers;

use Cache;
use Illuminate\Routing\Controller;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;

use OpenApi\Attributes as OA;


class ApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/pbbg",
     *     tags={"External Services"},
     *     @OA\Response(
     *         response="200",
     *         description="Returns an array with data for PBBG",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Data not found",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getPbbg()
    {
        return [
            'name' => 'ODARENA',
            'version' => (Cache::get('version') ?? 'unknown'),
            'description' => 'A text-based, persistent browser-based strategy game (PBBG) in a fantasy war setting',
            'tags' => ['fantasy', 'multiplayer', 'strategy'],
            'status' => 'up',
            'dates' => [
                'born' => '2013-02-04',
                'updated' => (Cache::has('version-date') ? carbon(Cache::get('version-date'))->format('Y-m-d') : null),
            ],
            'players' => [
                'registered' => User::whereActivated(true)->count(),
                'active' => Dominion::whereHas('round', static function ($q) {
                    $q->where('start_date', '<=', now())
                        ->where('end_date', '>', now());
                })->count(),
            ],
            'links' => [
                'beta' => 'https://odarena.com',
                'github' => 'https://github.com/Dr-Eki/ODARENA',
            ],
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dominion/is-ticking",
     *     tags={"Dominion"},
     *     @OA\Response(
     *         response="200",
     *         description="Returns an array with data for is ticking",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_ticking", type="boolean")
     *         )
     *     )
     * )
     */
    public function isRoundTicking(): array
    {
        $round = Round::latest()->first();

        return [
            'is_ticking' => $round->is_ticking
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/server-time",
     *     @OA\Response(
     *         response="200",
     *         description="Returns the current server time in ISO8601 format",
     *         @OA\JsonContent(
     *            @OA\Property(property="time", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function getServerTime(): array
    {
        return [
            'time' => now()->toIso8601String()
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/latest-round",
     *     @OA\Response(
     *         response="200",
     *         description="Returns the latest round ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="round_id", type="integer")
     *         )
     *     )
     * )
     */
    public function getLatestRound(): array
    {
        $round = Round::latest()->first();

        return [
            'round_id' => $round->id
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/rounds",
     *     @OA\Response(
     *         response="200",
     *         description="Returns an array with all round IDs",
     *         @OA\JsonContent(
     *             @OA\Property(property="rounds", type="array", @OA\Items(type="integer"))
     *         )
     *     )
     * )
     */
    public function getRounds(): array
    {
        return [
            'rounds' => Round::all()->pluck('id')
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/round/{roundId}",
     *     @OA\Parameter(
     *         name="roundId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the round"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns the round by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="round", type="object")
     *         )
     *    ),
     *    @OA\Response(
     *        response="404",
     *       description="Round not found, try another round ID",
     *      @OA\JsonContent(
     *       @OA\Property(property="error", type="string")
     *     )
     * )
     */
    public function getRound(): array
    {
        $round = Round::find(request('roundId'));

        if ($round === null) {
            return response()->json(['error' => 'Round not found, try another round ID'], 404);
        }

        return [
            'round' => $round
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/round/{roundId}/dominions",
     *     @OA\Parameter(
     *         name="roundId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the round"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns the dominion IDs for a round as a JSON array",
     *         @OA\JsonContent(
     *           @OA\Property(property="dominion_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Round not found, try another round ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getRoundDominions(): array
    {
        $round = Round::find(request('roundId'));

        if ($round === null) {
            return response()->json(['error' => 'Round not found, try another round ID'], 404);
        }

        return [
            'dominion_ids' => $round->dominions->pluck('id')
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/round/{roundId}/realms",
     *     @OA\Parameter(
     *         name="roundId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the round"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns the realm IDs for a round as a JSON array",
     *         @OA\JsonContent(
     *           @OA\Property(property="realm_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Round not found, try another round ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getRoundRealms(): array
    {
        $round = Round::find(request('roundId'));

        if ($round === null) {
            return response()->json(['error' => 'Round not found, try another round ID'], 404);
        }

        return [
            'realm_ids' => $round->realms->pluck('id')
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/realm/{realmId}/dominions",
     *     @OA\Parameter(
     *         name="realmId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the realm"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns the dominion IDs for a realm as a JSON array",
     *         @OA\JsonContent(
     *           @OA\Property(property="dominion_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Realm not found, try another realm ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getRealmDominions(): array
    {
        $realm = Realm::find(request('realmId'));

        if ($realm === null) {
            return response()->json(['error' => 'Realm not found, try another realm ID'], 404);
        }

        return [
            'dominion_ids' => $realm->dominions->pluck('id')
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dominion/{dominionId}",
     *     @OA\Parameter(
     *         name="dominionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the dominion"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns the dominion by ID as a JSON object (only if the round has ended)",
     *         @OA\JsonContent(
     *           @OA\Property(property="dominion", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Dominion not found, try another dominion ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Round has not ended",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getDominion(): array
    {
        $dominion = Dominion::find(request('dominionId'));

        if ($dominion === null) {
            return response()->json(['error' => 'Dominion not found'], 404);
        }

        if (!$dominion->round->hasEnded()) {
            return response()->json(['error' => 'Round has not ended'], 403);
        }

        return [
            'dominion' => $dominion
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/models",
     *     @OA\Response(
     *         response="200",
     *         description="Returns the models allowed as a JSON array",
     *         @OA\JsonContent(
     *           @OA\Property(property="models_allowed", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function getModels(): array
    {
        return config('api.models_allowed');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/advancement/{advancementId}",
     *     @OA\Parameter(
     *         name="advancementId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the advancement instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns an instance of the advancement by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="advancement", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Advancement instance not found, try another ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getAdvancement($id)
    {
        $advancement = \OpenDominion\Models\Advancement::find($id);

        if (!$advancement) {
            return response()->json(['error' => "Advancement not found with ID '$id'"], 404);
        }

        return response()->json([$advancement]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/artefact/{artefactId}",
     *     @OA\Parameter(
     *         name="artefactId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the artefact instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns an instance of the artefact by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="artefact", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Artefact instance not found, try another ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getArtefact($id)
    {
        $artefact = \OpenDominion\Models\Artefact::find($id);

        if (!$artefact) {
            return response()->json(['error' => "Artefact not found with ID '$id'"], 404);
        }

        return response()->json([$artefact]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/building/{buildingId}",
     *     @OA\Parameter(
     *         name="buildingId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the building instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns an instance of the building by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="building", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Building instance not found, try another ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getBuilding($id)
    {
        $building = \OpenDominion\Models\Building::find($id);

        if (!$building) {
            return response()->json(['error' => "Building not found with ID '$id'"], 404);
        }

        return response()->json([$building]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/decree/{decreeId}",
     *     @OA\Parameter(
     *         name="decreeId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the decree instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns an instance of the decree by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="decree", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Decree instance not found, try another ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getDecree($id)
    {
        $decree = \OpenDominion\Models\Decree::find($id);

        if (!$decree) {
            return response()->json(['error' => "Decree not found with ID '$id'"], 404);
        }

        return response()->json([$decree]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/deity/{deityId}",
     *     @OA\Parameter(
     *         name="deityId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the deity instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns an instance of the deity by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="deity", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Deity instance not found, try another ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getDeity($id)
    {
        $deity = \OpenDominion\Models\Deity::find($id);

        if (!$deity) {
            return response()->json(['error' => "Deity not found with ID '$id'"], 404);
        }

        return response()->json([$deity]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/improvement/{improvementId}",
     *     @OA\Parameter(
     *         name="improvementId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the improvement instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns an instance of the improvement by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="improvement", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Improvement instance not found, try another ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getImprovement($id)
    {
        $improvement = \OpenDominion\Models\Improvement::find($id);

        if (!$improvement) {
            return response()->json(['error' => "Improvement not found with ID '$id'"], 404);
        }

        return response()->json([$improvement]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/race/{raceId}",
     *     @OA\Parameter(
     *         name="raceId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the race instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns an instance of the race by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="race", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Race instance not found, try another ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getRace($id)
    {
        $race = \OpenDominion\Models\Race::find($id);

        if (!$race) {
            return response()->json(['error' => "Race not found with ID '$id'"], 404);
        }

        return response()->json([$race]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/realm/{realmId}",
     *     @OA\Parameter(
     *         name="realmId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the realm instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns an instance of the realm by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="realm", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Realm instance not found, try another ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getRealm($id)
    {
        $realm = \OpenDominion\Models\Realm::find($id);

        if (!$realm) {
            return response()->json(['error' => "Realm not found with ID '$id'"], 404);
        }

        return response()->json([$realm]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/spell/{spellId}",
     *     @OA\Parameter(
     *         name="spellId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the spell instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns an instance of the spell by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="spell", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Spell instance not found, try another ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getSpell($id)
    {
        $spell = \OpenDominion\Models\Spell::find($id);

        if (!$spell) {
            return response()->json(['error' => "Spell not found with ID '$id'"], 404);
        }

        return response()->json([$spell]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tech/{techId}",
     *     @OA\Parameter(
     *         name="techId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the tech instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns an instance of the tech by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="tech", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Tech instance not found, try another ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getTech($id)
    {
        $tech = \OpenDominion\Models\Tech::find($id);

        if (!$tech) {
            return response()->json(['error' => "Tech not found with ID '$id'"], 404);
        }

        return response()->json([$tech]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/title/{titleId}",
     *     @OA\Parameter(
     *         name="titleId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the title instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns an instance of the title by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="title", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Title instance not found, try another ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getTitle($id)
    {
        $title = \OpenDominion\Models\Title::find($id);

        if (!$title) {
            return response()->json(['error' => "Title not found with ID '$id'"], 404);
        }

        return response()->json([$title]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/unit/{unitId}",
     *     @OA\Parameter(
     *         name="unitId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the unit instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns an instance of the unit by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="unit", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Unit instance not found, try another ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getUnit($id)
    {
        $unit = \OpenDominion\Models\Unit::find($id);

        if (!$unit) {
            return response()->json(['error' => "Unit not found with ID '$id'"], 404);
        }

        return response()->json([$unit]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{model}/{modelId}",
     *     @OA\Parameter(
     *         name="model",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="The model name"
     *     ),
     *     @OA\Parameter(
     *         name="modelId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the model instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns an instance of the model by ID as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="model", type="object")
     *         )
     *     ),
     *    @OA\Response(
     *       response="403",
     *      description="Model not permitted",
     *      @OA\JsonContent(
     *      @OA\Property(property="error", type="string")
     *     )
     *   ),
     *     @OA\Response(
     *         response="404",
     *         description="Model instance not found, try another model name or ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getModel($model, $id)
    {
        $class = "OpenDominion\Models\\$model";
        $instance = $class::find($id);

        if(!in_array($model, config('api.models_allowed'))) {
            return response()->json(['error' => "Model '$model' not permitted"], 403);
        }

        if (!$instance) {
            return response()->json(['error' => "Model '$model' not found with ID '$id'"], 404);
        }

        return response()->json([$instance]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{model}/{id}/perks",
     *     @OA\Parameter(
     *         name="model",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="The model name"
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the model instance"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns the perks for a specific model as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="perks", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Perks for model instance not found, try another model name or ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getModelPerks($model, $id)
    {
        $class = "OpenDominion\Models\\$model";
        $instance = $class::find($id);

        if (!$instance) {
            return response()->json(['error' => "Perks for '$model' with ID '$id' not found"], 404);
        }

        return response()->json([$instance->perks]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/models/perk-types/{model}",
     *     @OA\Parameter(
     *         name="model",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="The model name"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns the perk types as keys for a specific model as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="perk_types", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Perk types for model not found, try another model name"
     *     )
     * )
     */
    public function getModelPerkTypes($model)
    {
        $upperModel = ucfirst($model);
        $class = "OpenDominion\Models\\{$upperModel}PerkType";
        $instances = $class::all()->unique('key')->sortBy('key');

        if (!$instances) {
            return response()->json(['error' => "Perk types for '$model' not found"], 404);
        }

        return response()->json([$instances->pluck('key')]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{model}/search/{key}",
     *     @OA\Parameter(
     *         name="model",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="The model name"
     *     ),
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="The key to search for"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns the instance of the model by key as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="model", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Model instance not found, try another model name or key",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function searchModelByKey($model, $key)
    {
        $class = "OpenDominion\Models\\$model";
        $instance = $class::where('key', $key)->first();

        if (!$instance) {
            return response()->json(['error' => "Model '$model' not found with key '$key'"], 404);
        }

        return response()->json([$instance]);
    }
    


}
