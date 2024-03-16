<?php
namespace OpenDominion\Http\Controllers;

use Cache;
use Illuminate\Routing\Controller;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use Illuminate\Http\JsonResponse;

use OpenDominion\Services\Dominion\QueueService;

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
    public function getLatestRound(): JsonResponse
    {
        $round = Round::latest()->first();

        return response()->json([
            'round_id' => $round->id
        ]);
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
    public function getRounds(): JsonResponse
    {
        return response()->json([
            'rounds' => Round::all()->pluck('id')
        ]);
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
    public function getRound(): JsonResponse
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
    public function getRoundDominions(): JsonResponse
    {
        $round = Round::find(request('roundId'));

        if ($round === null) {
            return response()->json(['error' => 'Round not found, try another round ID'], 404);
        }

        return response()->json([
            'dominion_ids' => $round->dominions->pluck('id')
        ]);
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
    public function getRoundRealms(): JsonResponse
    {
        $round = Round::find(request('roundId'));

        if ($round === null) {
            return response()->json(['error' => 'Round not found, try another round ID'], 404);
        }

        return response()->json([
            'realm_ids' => $round->realms->pluck('id')
        ]);
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
    public function getRealmDominions(): JsonResponse
    {
        $realm = Realm::find(request('realmId'));

        if ($realm === null) {
            return response()->json(['error' => 'Realm not found, try another realm ID'], 404);
        }

        return response()->json([
            'dominion_ids' => $realm->dominions->pluck('id')
        ]);
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
    public function getDominion(): JsonResponse
    {
        $dominion = Dominion::find(request('dominionId'));

        if ($dominion === null) {
            return response()->json(['error' => 'Dominion not found'], 404);
        }

        if (!$dominion->round->hasEnded()) {
            return response()->json(['error' => 'Round has not ended'], 403);
        }

        return response()->json($dominion);
    }

    # $router->get('/dominion/{dominionId}/queues')->uses('ApiController@getDominionQueues')->name('dominion-queues');
    /**
     * @OA\Get(
     *     path="/api/v1/dominion/{dominionId}/queues",
     *     @OA\Parameter(
     *         name="dominionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the dominion"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns the queues for a dominion as a JSON array",
     *         @OA\JsonContent(
     *           @OA\Property(property="queues", type="array", @OA\Items(type="object"))
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
    public function getDominionQueues($dominionId): JsonResponse
    {
        $dominion = Dominion::find($dominionId);

        if ($dominion === null) {
            return response()->json(['error' => 'Dominion not found'], 404);
        }

        if (!$dominion->round->hasEnded()) {
            return response()->json(['error' => 'Round has not ended'], 403);
        }

        return response()->json($dominion->queues);
    }

    # $router->get('/dominion/{dominionId}/queues/{type}')->uses('ApiController@getDominionQueueByType')->name('dominion-queue-by-type');
    /**
     * @OA\Get(
     *     path="/api/v1/dominion/{dominionId}/{type}",
     *     @OA\Parameter(
     *         name="dominionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the dominion"
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="The type of the queue"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns the queue for a dominion by type as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="queue", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Queue not found, try another queue type or dominion ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Round has not ended or queue type not permitted",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getDominionQueueByType($dominionId, $type): JsonResponse
    {
        $dominion = Dominion::find($dominionId);

        if ($dominion === null) {
            return response()->json(['error' => 'Dominion not found'], 404);
        }

        if (!$dominion->round->hasEnded()) {
            return response()->json(['error' => 'Round has not ended'], 403);
        }

        if(!in_array($type, config('queues.types'))) {
            return response()->json(['error' => "Queue type '$type' not permitted"], 403);
        }

        $queueService = app(QueueService::class);
        $queue = $queueService->getQueue($type, $dominion);

        if ($queue === null) {
            return response()->json(['error' => "Queue not found with type '$type'"], 404);
        }

        return response()->json($queue);
    }


    /**
     * @OA\Get(
     *     path="/api/v1/dominion/{dominionId}/{model}",
     *     @OA\Parameter(
     *         name="dominionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the dominion"
     *     ),
     *     @OA\Parameter(
     *         name="model",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="The model name"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns the model relationship for a dominion as a JSON object",
     *         @OA\JsonContent(
     *           @OA\Property(property="model", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Model not found, try another model name or dominion ID",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Model not permitted",
     *         @OA\JsonContent(
     *           @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getDominionModelRelationship($dominionId, $model): JsonResponse
    {
        $dominion = Dominion::find($dominionId);

        if ($dominion === null) {
            return response()->json(['error' => 'Dominion not found'], 404);
        }

        if(!$dominion->round->hasEnded()) {
            return response()->json(['error' => 'Round has not ended'], 403);
        }

        if(!in_array($model, config('api.models_allowed'))) {
            return response()->json(['error' => "Model '$model' not permitted"], 403);
        }


        $dominion = Dominion::find(request('dominionId'));
        $model = ucfirst($model);
        $class = "OpenDominion\Models\\Dominion$model";
        $instances = $class::where('dominion_id', $dominion->id)->get();

        return response()->json($instances);
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
    public function getModels(): JsonResponse
    {
        return config('api.models_allowed');
    }

    # $router->get('/queue-types')->uses('ApiController@getQueueTypes')->name('queue-types');
    /**
     * @OA\Get(
     *     path="/api/v1/queue-types",
     *     @OA\Response(
     *         response="200",
     *         description="Returns the queue types as a JSON array",
     *         @OA\JsonContent(
     *           @OA\Property(property="queue_types", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function getQueueTypes(): JsonResponse
    {
        return config('queues.types');
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
    public function getModel($model, $id): JsonResponse
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
    public function getModelPerks($model, $id): JsonResponse
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
    public function getModelPerkTypes($model): JsonResponse
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
    public function searchModelByKey($model, $key): JsonResponse
    {
        $class = "OpenDominion\Models\\$model";
        $instance = $class::where('key', $key)->first();

        if (!$instance) {
            return response()->json(['error' => "Model '$model' not found with key '$key'"], 404);
        }

        return response()->json([$instance]);
    }
    


}
