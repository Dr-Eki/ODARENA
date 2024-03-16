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


    $router->group(['prefix' => 'dominion', 'middleware' => ['api', 'auth', 'dominionselected'], 'as' => 'dominion.'], static function (Router $router)
    {
        $router->get('invasion')->uses('Dominion\APIController@calculateInvasion')->name('invasion');
        $router->get('artefact-attack')->uses('Dominion\APIController@calculateArtefactAttack')->name('artefact-attack');
        $router->get('expedition')->uses('Dominion\APIController@calculateExpedition')->name('expedition');
        $router->get('sorcery')->uses('Dominion\APIController@calculateSorcery')->name('sorcery');
        $router->get('desecration')->uses('Dominion\APIController@calculateDesecration')->name('desecration');
    });

    /*
    $router->group(['prefix' => 'calculator', 'middleware' => ['api', 'auth'], 'as' => 'calculator.'], static function (Router $router) {
        $router->get('defense')->uses('Dominion\APIController@calculateDefense')->name('defense');
        $router->get('offense')->uses('Dominion\APIController@calculateOffense')->name('offense');
    });
    */

    $router->group(['middleware' => 'throttle:90,1'], function () use ($router)
    {

        $router->get('/is-round-ticking')->uses('ApiController@isRoundTicking')->name('is-round-ticking');
        $router->get('/server-time')->uses('ApiController@getServerTime')->name('server-time');

    });

    $router->group(['middleware' => 'throttle:60,1'], function () use ($router)
    {

        $router->get('/latest-round')->uses('ApiController@getLatestRound')->name('latest-round');
        
        $router->get('/rounds')->uses('ApiController@getRounds')->name('rounds');

        $router->get('/round/{roundId}')->uses('ApiController@getRound')->name('round');
        $router->get('/round/{roundId}/dominions')->uses('ApiController@getRoundDominions')->name('round-dominions');
        $router->get('/round/{roundId}/realms')->uses('ApiController@getRoundRealms')->name('round-realms');

        $router->get('/realm/{realmId}/dominions')->uses('ApiController@getRealmDominions')->name('realm-dominions');   
        $router->get('/dominion/{dominionId}')->uses('ApiController@getDominion')->name('dominion');
        $router->get('/dominion/{dominionId}/queues')->uses('ApiController@getDominionQueues')->name('dominion-queues');
        $router->get('/dominion/{dominionId}/queues/{type}')->uses('ApiController@getDominionQueueByType')->name('dominion-queue-by-type');
        $router->get('/dominion/{dominionId}/{model}')->uses('ApiController@getDominionModelRelationship')->name('dominion-model-relationship');
         
        $router->get('/models')->uses('ApiController@getModels')->name('models');
        $router->get('/models/perk-types/{model}')->uses('ApiController@getModelPerkTypes')->name('model-perk-types');
        $router->get('/models/search/{model}/{key}')->uses('ApiController@searchModelKey')->name('model');
        $router->get('/models/{model}/{id}')->uses('ApiController@getModel')->name('model');
        $router->get('/models/{model}/{id}/perks')->uses('ApiController@getModelPerks')->name('model-perks');

        $router->get('/queue-types')->uses('ApiController@getQueueTypes')->name('queue-types');


    });



});