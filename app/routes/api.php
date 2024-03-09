<?php

use Illuminate\Routing\Router;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;

/** @var Router $router */
$router->group(['prefix' => 'v1', 'as' => 'api.'], static function (Router $router) {


    $router->group(['middleware' => ['bindings', 'throttle:60,1']], static function (Router $router) {
        $router->get('pbbg')->uses('ApiController@getPbbg');
    });

    $router->group(['prefix' => 'dominion', 'middleware' => ['api', 'auth', 'dominionselected'], 'as' => 'dominion.'], static function (Router $router) {
        $router->get('invasion')->uses('Dominion\APIController@calculateInvasion')->name('invasion');
        $router->get('artefact-attack')->uses('Dominion\APIController@calculateArtefactAttack')->name('artefact-attack');
        $router->get('expedition')->uses('Dominion\APIController@calculateExpedition')->name('expedition');
        $router->get('sorcery')->uses('Dominion\APIController@calculateSorcery')->name('sorcery');
        $router->get('desecration')->uses('Dominion\APIController@calculateDesecration')->name('desecration');
    });

    $router->group(['prefix' => 'calculator', 'middleware' => ['api', 'auth'], 'as' => 'calculator.'], static function (Router $router) {
        $router->get('defense')->uses('Dominion\APIController@calculateDefense')->name('defense');
        $router->get('offense')->uses('Dominion\APIController@calculateOffense')->name('offense');
    });


    $router->group(['middleware' => 'throttle:90,1'], function () use ($router)
    {
        /**
         * @OA\Get(
         *     path="/api/v1/is-round-ticking",
         *     @OA\Response(response="200", description="Check if the round is ticking")
         * )
         */
        $router->get('/is-round-ticking', function () {
            $round = Round::latest()->first();
        
            return response()->json([
                'is_ticking' => $round->is_ticking,
            ]);
        });
    
        /**
         * @OA\Get(
         *     path="/api/v1/server-time",
         *     @OA\Response(response="200", description="Get the server time")
         * )
         */
        $router->get('/server-time', function () {
            return response()->json(['time' => now()->toIso8601String()]);
        });
    });

    $router->group(['middleware' => 'throttle:60,1'], function () use ($router)
    {
        /**
         * @OA\Get(
         *     path="/api/v1/latest-round",
         *     @OA\Response(response="200", description="Get the latest round ID")
         * )
         */
        $router->get('/latest-round', function () {
            return response()->json([Round::latest()->first()->id]);
        });

        /**
         * @OA\Get(
         *     path="/api/v1/rounds",
         *     @OA\Response(response="200", description="Get all round IDs")
         * )
         */
        $router->get('/rounds', function () {
            return response()->json([Round::all()->pluck('id')]);
        });

        /**
         * @OA\Get(
         *     path="/api/v1/round/{roundId}",
         *     @OA\Response(response="200", description="Get the round by ID")
         * )
         */
        $router->get('/round/{roundId}', function ($roundId) {

            $round = Round::find($roundId);

            if (!$round) {
                return response()->json(['error' => 'Round not found, try another round ID'], 404);
            }

            return response()->json([$round]);
        });

        /**
         * @OA\Get(
         *     path="/api/v1/round/{roundId}/dominions",
         *     @OA\Response(response="200", description="Get the dominions for a round")
         * )
         */
        $router->get('/round/{roundId}/dominions', function ($roundId) {

            $round = Round::find($roundId);

            if (!$round) {
                return response()->json(['error' => 'Round not found, try another round ID'], 404);
            }

            return response()->json([$round->dominions->pluck('id')]);
        });

        /**
         * @OA\Get(
         *     path="/api/v1/round/{roundId}/realms",
         *     @OA\Response(response="200", description="Get the realms for a round")
         * )
         */
        $router->get('/round/{roundId}/realms', function ($roundId) {

            $round = Round::find($roundId);

            if (!$round) {
                return response()->json(['error' => 'Round not found, try another round ID'], 404);
            }

            return response()->json([$round->realms->pluck('id')]);
        });

        /**
         * @OA\Get(
         *     path="/api/v1/realm/{realmId}",
         *     @OA\Response(response="200", description="Get the realm by ID")
         * )
         */
        $router->get('/realm/{realmId}/dominions', function ($realmId) {

            $realm = Realm::find($realmId);

            if (!$realm) {
                return response()->json(['error' => 'Realm not found, try another realm ID'], 404);
            }

            return response()->json([$realm->dominions->pluck('id')]);
        });
    
        /**
         * @OA\Get(
         *     path="/api/v1/realm/{realmId}/dominions",
         *     @OA\Response(response="200", description="Get the dominions for a realm")
         * )
         */
        $router->get('/dominion/{dominionId}', function ($dominionId) {
            $dominion = Dominion::find($dominionId);
    
            if (!$dominion) {
                return response()->json(['error' => 'Dominion not found'], 404);
            }
    
            if(!$dominion->round->hasEnded())
            {
                return response()->json(['error' => 'Round has not ended'], 403);
            }
    
            return response()->json([$dominion]);
        });
    
        /**
         * @OA\Get(
         *     path="/api/v1/domains",
         *     @OA\Response(response="200", description="Get all allowed models")
         * )
         */
        $router->get('/models', function () {
            return response()->json([config('api.models_allowed')]);
        });

        /** Return the model class as a JSON object, if found, otherwise return 404. */
        $models = config('api.models_allowed');
        foreach ($models as $model) {

            /**
             * @OA\Get(
             *    path="/api/v1/{model}/{modelId}",
             *    @OA\Response(response="200", description="Get an instance of a model by model ID")
             * )
             */
            $router->get(strtolower($model) . '/{' . strtolower($model) . 'Id}', function ($id) use ($model) {
                $class = "OpenDominion\Models\\$model";
                $instance = $class::find($id);
        
                if (!$instance) {
                    return response()->json(['error' => "Model '$model' not found with ID '$id'"], 404);
                }
        
                return response()->json([$instance]);
            });
        }

        /**
         * @OA\Get(
         *    path="/api/v1/{model}/{id}/perks",
         *   @OA\Response(response="200", description="Get the perks for a specific model")
         * )
         */
        $router->get('/{model}/{id}/perks', function ($model, $id) {
            $class = "OpenDominion\Models\\$model";
            $instance = $class::find($id);
    
            if (!$instance) {
                return response()->json(['error' => "Perks for '$model' with ID '$id' not found"], 404);
            }
    
            return response()->json([$instance->perks]);
        });
               
        /**
         * @OA\Get(
         *    path="/api/v1/{model}/perktypes",
         *   @OA\Response(response="200", description="Get the perk types for a specific model")
         * )
         */
        $router->get('/perktypes/{model}', function ($model) {

            if(!in_array($model, config('api.models_allowed')))
            {
                return response()->json(['error' => "Model '$model' not permitted"], 403);
            }
        
            $modelUpper = ucfirst($model);
            $class = "OpenDominion\Models\\{$modelUpper}PerkType";
        
            if (!class_exists($class)) {
                return response()->json(['error' => "Perk types for '$model' not found"], 404);
            }
        
            $instance = $class::all();
        
            if ($instance->isEmpty()) {
                return response()->json(['error' => "Perk types for '$model' not found"], 404);
            }
        
            return response()->json([$instance]);
        });


    });



});