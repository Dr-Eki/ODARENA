<?php

use Illuminate\Routing\Router;
#use Spatie\Honeypot\ProtectAgainstSpam;

/** @var Router $router */
$router->get('/')->uses('HomeController@getIndex')->name('home');

// Authentication

$router->group(['prefix' => 'auth', 'as' => 'auth.'], static function (Router $router) {

    $router->group(['middleware' => 'guest'], static function (Router $router) {

        // Authentication
        $router->get('login')->uses('Auth\LoginController@showLoginForm')->name('login');
        $router->post('login')->uses('Auth\LoginController@login');

        // Registration
        $router->get('register')->uses('Auth\RegisterController@showRegistrationForm')->name('register');
        $router->post('register')->uses('Auth\RegisterController@register');#->middleware(ProtectAgainstSpam::class);
        $router->get('activate/{activation_code}')->uses('Auth\RegisterController@activate')->name('activate');

        // Password Reset
        $router->get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
        $router->post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
        $router->get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
        $router->post('password/reset', 'Auth\ResetPasswordController@reset');

    });

    $router->group(['middleware' => 'auth'], static function (Router $router) {

        // Logout
        $router->post('logout')->uses('Auth\LoginController@logout')->name('logout');

    });

});

// Gameplay

$router->group(['middleware' => 'auth'], static function (Router $router) {

    // Dashboard
    $router->get('dashboard')->uses('DashboardController@getIndex')->name('dashboard');
    $router->post('dashboard/delete-pack/{pack}')->uses('DashboardController@postDeletePack')->name('dashboard.delete-pack');

    // Settings
    $router->get('settings')->uses('SettingsController@getIndex')->name('settings');
    $router->post('settings')->uses('SettingsController@postIndex');
    $router->get('settings/delete-avatar')->uses('SettingsController@getDeleteAvatar')->name('settings.delete-avatar');
    $router->get('settings/generate-avatar')->uses('SettingsController@getGenerateAvatar')->name('settings.generate-avatar');

    // Round Register
    $router->get('round/{round}/register')->uses('RoundController@getRegister')->name('round.register');
    $router->post('round/{round}/register')->uses('RoundController@postRegister');

    // Round Create Pack
    $router->get('round/{round}/create-pack')->uses('RoundController@getCreatePack')->name('round.create-pack');
    $router->post('round/{round}/create-pack')->uses('RoundController@postCreatePack');

    $router->get('round/{round}/quickstart')->uses('RoundController@getQuickstart')->name('round.quickstart');
    $router->post('round/{round}/quickstart')->uses('RoundController@postQuickstart');

    $router->group(['prefix' => 'dominion', 'as' => 'dominion.'], static function (Router $router) {

        // Dominion Select
//        $router->get('{dominion}/select')->uses(function () { return redirect()->route('dashboard'); });
        $router->post('{dominion}/select')->uses('Dominion\SelectController@postSelect')->name('select');
        $router->post('{dominion}/abandon')->uses('Dominion\MiscController@postAbandonDominion')->name('abandon');

        // Dominion
        $router->group(['middleware' => 'dominionselected'], static function (Router $router) {

            $router->get('/')->uses('Dominion\IndexController@getIndex');

            // Status
            $router->get('status')->uses('Dominion\StatusController@getStatus')->name('status');
            $router->post('status')->uses('Dominion\StatusController@postTick');
            $router->post('status/change-title')->uses('Dominion\StatusController@postChangeTitle')->name('status.change-title');
            $router->post('status/change-name')->uses('Dominion\StatusController@postChangeName')->name('status.change-name');

            # Resources
            $router->get('resources')->uses('Dominion\ResourcesController@getResources')->name('resources');
            $router->post('resources')->uses('Dominion\ResourcesController@postResources');

            // Advisors
            $router->get('advisors')->uses('Dominion\AdvisorsController@getAdvisors')->name('advisors');
            $router->get('advisors/production')->uses('Dominion\AdvisorsController@getAdvisorsProduction')->name('advisors.production');
            $router->get('advisors/statistics')->uses('Dominion\AdvisorsController@getAdvisorsStatistics')->name('advisors.statistics');
            $router->get('advisors/military')->uses('Dominion\AdvisorsController@getAdvisorsMilitary')->name('advisors.military');
            $router->get('advisors/history')->uses('Dominion\AdvisorsController@getHistory')->name('advisors.history');

            # Buildings
            $router->get('buildings')->uses('Dominion\BuildingController@getBuildings')->name('buildings');
            $router->post('buildings')->uses('Dominion\BuildingController@postBuildings');
            $router->get('demolish')->uses('Dominion\BuildingController@getDemolish')->name('demolish');
            $router->post('demolish')->uses('Dominion\BuildingController@postDemolish');

            # Land
            $router->get('land')->uses('Dominion\LandController@getLand')->name('land');
            $router->post('land/rezone')->uses('Dominion\LandController@postRezone')->name('land.rezone');
            $router->post('land/daily-bonus')->uses('Dominion\LandController@postDailyBonus')->name('land.daily-bonus');
            $router->post('land/repair-terrain')->uses('Dominion\LandController@postRepairTerrain')->name('land.repair-terrain');

            # Trade Routes
            $router->redirect('trade', 'dominion/trade/routes');
            $router->get('trade/ledger')->uses('Dominion\TradeController@getLedger')->name('trade.ledger');
            $router->get('trade/routes')->uses('Dominion\TradeController@getTradeRoutes')->name('trade.routes');
            $router->get('trade/trades-in-progress')->uses('Dominion\TradeController@getTradesInProgress')->name('trade.trades-in-progress');
            $router->get('trade/holds')->uses('Dominion\TradeController@getHolds')->name('trade.holds');
            $router->get('trade/hold/{hold}')->uses('Dominion\TradeController@getHold')->name('trade.hold');
            $router->get('trade/hold/{hold}/sentiment')->uses('Dominion\TradeController@getHoldSentiment')->name('trade.hold-sentiment');
            $router->get('trade/routes/confirm-trade-route')->uses('Dominion\TradeController@getConfirmTradeRoute')->name('trade.routes.confirm-trade-route');
            $router->get('trade/routes/clear-trade-route')->uses('Dominion\TradeController@clearTradeDetails')->name('trade.routes.clear-trade-route');
            $router->get('trade/routes/edit/{tradeRoute}')->uses('Dominion\TradeController@getEditTradeRoute')->name('trade.routes.edit');
            $router->post('trade/routes/edit/{tradeRoute}')->uses('Dominion\TradeController@postEditTradeRoute')->name('trade.routes.edit');
            $router->post('trade/routes/store-trade-details')->uses('Dominion\TradeController@storeTradeDetails')->name('trade.routes.store-trade-details');
            $router->post('trade/routes/create-trade-route')->uses('Dominion\TradeController@postCreateTradeRoute')->name('trade.routes.create-trade-route');
            $router->post('trade/routes/attack-trade-route')->uses('Dominion\TradeController@postAttackTradeRoute')->name('trade.routes.attack-trade-route');
            $router->post('trade/routes/delete-trade-route')->uses('Dominion\TradeController@postDeleteTradeRoute')->name('trade.routes.delete-trade-route');
            $router->post('trade/routes/calculate-trade-route')->uses('Dominion\TradeController@calculateTradeRoute')->name('trade.routes.calculate-trade-route');      


            // Improvements
            $router->get('improvements')->uses('Dominion\ImprovementController@getImprovements')->name('improvements');
            $router->post('improvements')->uses('Dominion\ImprovementController@postImprovements');

            // Advancements 
            $router->get('advancements')->uses('Dominion\AdvancementController@getAdvancements')->name('advancements');
            $router->post('advancements')->uses('Dominion\AdvancementController@postAdvancements');

            // Research 
            $router->get('research')->uses('Dominion\ResearchController@getResearch')->name('research');
            $router->post('research')->uses('Dominion\ResearchController@postResearch');

            // Military
            $router->get('military')->uses('Dominion\MilitaryController@getMilitary')->name('military');
            $router->post('military/change-draft-rate')->uses('Dominion\MilitaryController@postChangeDraftRate')->name('military.change-draft-rate');
            $router->post('military/release-draftees')->uses('Dominion\MilitaryController@postReleaseDraftees')->name('military.release-draftees');
            $router->post('military/train')->uses('Dominion\MilitaryController@postTrain')->name('military.train');
            $router->get('military/release')->uses('Dominion\MilitaryController@getRelease')->name('military.release');
            $router->post('military/release')->uses('Dominion\MilitaryController@postRelease');

            // Invade
            $router->get('invade')->uses('Dominion\InvasionController@getInvade')->name('invade');
            $router->post('invade')->uses('Dominion\InvasionController@postInvade');

            // Desecrate
            $router->get('desecrate')->uses('Dominion\DesecrationController@getDesecrate')->name('desecrate');
            $router->post('desecrate')->uses('Dominion\DesecrationController@postDesecrate');

            // Expedition
            $router->get('expedition')->uses('Dominion\ExpeditionController@getExpedition')->name('expedition');
            $router->post('expedition')->uses('Dominion\ExpeditionController@postExpedition');

            // Artefacts
            $router->get('artefacts')->uses('Dominion\ArtefactsController@getArtefacts')->name('artefacts');
            $router->post('artefacts')->uses('Dominion\ArtefactsController@postArtefacts');

            // Event result
            $router->get('event/{uuid}')->uses('Dominion\EventController@index')->name('event');

            // Calculations
            $router->get('calculations')->uses('Dominion\CalculationsController@getIndex')->name('calculations');

            // Sabotage
            $router->get('sabotage')->uses('Dominion\SabotageController@getSabotage')->name('sabotage');
            $router->post('sabotage')->uses('Dominion\SabotageController@postSabotage');

            // Sorcery
            $router->get('sorcery')->uses('Dominion\SorceryController@getSorcery')->name('sorcery');
            $router->post('sorcery')->uses('Dominion\SorceryController@postSorcery');

            // Theft
            $router->get('theft')->uses('Dominion\TheftController@getTheft')->name('theft');
            $router->post('theft')->uses('Dominion\TheftController@postTheft');

            // Friendly Magic
            $router->get('magic')->uses('Dominion\MagicController@getMagic')->name('magic');
            $router->post('magic')->uses('Dominion\MagicController@postMagic');

            // Search
            $router->get('search')->uses('Dominion\SearchController@getSearch')->name('search');

            // Council
            $router->get('council')->uses('Dominion\CouncilController@getIndex')->name('council');
            $router->get('council/create')->uses('Dominion\CouncilController@getCreate')->name('council.create');
            $router->post('council/create')->uses('Dominion\CouncilController@postCreate');
            $router->get('council/{thread}')->uses('Dominion\CouncilController@getThread')->name('council.thread');
            $router->post('council/{thread}/reply')->uses('Dominion\CouncilController@postReply')->name('council.reply');
            $router->get('council/{thread}/delete')->uses('Dominion\CouncilController@getDeleteThread')->name('council.delete.thread');
            $router->post('council/{thread}/delete')->uses('Dominion\CouncilController@postDeleteThread');
            $router->get('council/post/{post}/delete')->uses('Dominion\CouncilController@getDeletePost')->name('council.delete.post');
            $router->post('council/post/{post}/delete')->uses('Dominion\CouncilController@postDeletePost');

            // Insight
            #$router->get('insight', redirect()->route('dominion.status'));#uses('Dominion\InsightController@getIndex')->name('insight');
            $router->get('insight/watched-dominions')->uses('Dominion\InsightController@getWatchedDominions')->name('insight.watched-dominions');
            $router->get('insight/{dominion}')->uses('Dominion\InsightController@getDominion')->name('insight.show');
            $router->get('insight/{dominion}/archive')->uses('Dominion\InsightController@getDominionInsightArchive')->name('insight.archive');
            $router->post('insight/{dominion}/archive')->uses('Dominion\InsightController@postCaptureDominionInsight');
            $router->post('insight/watch-dominion/{dominion}')->uses('Dominion\InsightController@watchDominion')->name('insight.watch-dominion');
            $router->post('insight/unwatch-dominion/{dominion}')->uses('Dominion\InsightController@unwatchDominion')->name('insight.unwatch-dominion');

            // Government
            $router->get('government')->uses('Dominion\GovernmentController@getIndex')->name('government');
            $router->post('government/monarch')->uses('Dominion\GovernmentController@postMonarch')->name('government.monarch');
            $router->post('government/realm')->uses('Dominion\GovernmentController@postRealm')->name('government.realm');
            
                $router->post('government/offer-protectorship')->uses('Dominion\GovernmentController@postOfferProtectorship')->name('government.offer-protectorship');
                $router->post('government/answer-protectorship-offer')->uses('Dominion\GovernmentController@postAnswerProtectorshipOffer')->name('government.answer-protectorship-offer');
                $router->post('government/rescind-protectorship-offer')->uses('Dominion\GovernmentController@postRescindProtectorshipOffer')->name('government.rescind-protectorship-offer');

                $router->post('government/offer-alliance')->uses('Dominion\GovernmentController@postOfferAlliance')->name('government.offer-alliance');
                $router->post('government/answer-alliance-offer')->uses('Dominion\GovernmentController@postAnswerAllianceOffer')->name('government.answer-alliance-offer');
                $router->post('government/rescind-alliance-offer')->uses('Dominion\GovernmentController@postRescindAllianceOffer')->name('government.rescind-alliance-offer');
                $router->post('government/break-alliance')->uses('Dominion\GovernmentController@postBreakAlliance')->name('government.break-alliance');

            // Deity
            $router->get('deity')->uses('Dominion\DeityController@getIndex')->name('deity');
            $router->post('deity/deity')->uses('Dominion\DeityController@postDeity')->name('deity.deity');
            $router->post('deity/renounce')->uses('Dominion\DeityController@postRenounce')->name('deity.renounce');

            // Decrees
            $router->get('decrees')->uses('Dominion\DecreesController@getIndex')->name('decrees');
            $router->post('decrees/issue-decree')->uses('Dominion\DecreesController@postIssueDecree')->name('decrees.issue-decree');
            $router->post('decrees/revoke-decree')->uses('Dominion\DecreesController@postRevokeDecree')->name('decrees.revoke-decree');

            // Realm
            $router->get('realm/all')->uses('Dominion\RealmController@getAllRealms')->name('realm.all');
            $router->get('realm/{realmNumber?}')->uses('Dominion\RealmController@getRealm')->name('realm');

            // Town Crier
            $router->get('world-news/{realmNumber?}')->uses('Dominion\WorldNewsController@getIndex')->name('world-news');

            // Notes
            $router->get('notes')->uses('Dominion\NotesController@getNotes')->name('notes');
            $router->post('notes')->uses('Dominion\NotesController@postNotes');

            // Pack
            $router->get('pack')->uses('Dominion\PackController@getPack')->name('pack');
            $router->post('pack/change-status')->uses('Dominion\PackController@changeStatus')->name('pack.change-status');

            // Quickstart
            $router->get('quickstart')->uses('Dominion\QuickstartController@getQuickstart')->name('quickstart');

            // Misc
            $router->post('misc/clear-notifications')->uses('Dominion\MiscController@postClearNotifications')->name('misc.clear-notifications');
            $router->post('misc/delete')->uses('Dominion\MiscController@postDeleteDominion')->name('misc.delete');
            $router->post('misc/restore-dominion-state')->uses('Dominion\MiscController@restoreDominionState')->name('misc.restore-dominion-state');

            // Debug
            // todo: remove me later
            $router->get('debug')->uses('DebugController@getIndex');
            $router->get('debug/dump')->uses('DebugController@getDump');


        });

    });

});

// Legal Terms and Conditions
$router->group(['prefix' => 'legal', 'as' => 'legal.'], static function (Router $router)
{
  $router->get('/')->uses('LegalController@getIndex')->name('index');
  $router->get('terms-and-conditions')->uses('LegalController@getTermsAndConditions')->name('terms-and-conditions');
  $router->get('privacy-policy')->uses('LegalController@getPrivacyPolicy')->name('privacy-policy');
});

// About
$router->group(['prefix' => 'about', 'as' => 'about.'], static function (Router $router)
{
  $router->get('/')->uses('AboutController@getIndex')->name('index');
});


// Scribes
$router->group(['prefix' => 'scribes', 'as' => 'scribes.'], static function (Router $router) {
    $router->get('advancements')->uses('ScribesController@getAdvancements')->name('advancements');
    $router->get('artefacts')->uses('ScribesController@getArtefacts')->name('artefacts');
    $router->get('buildings')->uses('ScribesController@getBuildings')->name('buildings');
    $router->get('decrees')->uses('ScribesController@getDecrees')->name('decrees');
    $router->get('deities')->uses('ScribesController@getDeities')->name('deities');
    $router->get('espionage')->uses('ScribesController@getEspionage')->name('espionage');
    $router->get('factions')->uses('ScribesController@getRaces')->name('factions');
    $router->get('general')->uses('ScribesController@getGeneral')->name('general');
    $router->get('improvements')->uses('ScribesController@getImprovements')->name('improvements');
    $router->get('research')->uses('ScribesController@getResearch')->name('research');
    $router->get('resources')->uses('ScribesController@getResources')->name('resources');
    $router->get('sabotage')->uses('ScribesController@getSabotage')->name('sabotage');
    $router->get('spells')->uses('ScribesController@getSpells')->name('spells');
    $router->get('terrain')->uses('ScribesController@getTerrain')->name('terrain');
    $router->get('titles')->uses('ScribesController@getTitles')->name('titles');

    $router->get('quickstarts')->uses('ScribesController@getQuickstarts')->name('quickstarts');
    $router->get('quickstart/{quickstart}')->uses('ScribesController@getQuickstart')->name('quickstart');

    $router->get('{race}')->uses('ScribesController@getRace')->name('faction');
});

// Chronicles
$router->group(['prefix' => 'chronicles', 'as' => 'chronicles.'], static function (Router $router) {

    $router->get('/')->uses('ChroniclesController@getRounds')->name('index');

    $router->get('/rounds')->uses('ChroniclesController@getRounds')->name('rounds');
    $router->get('/rulers')->uses('ChroniclesController@getRulers')->name('rulers');
    $router->get('/factions')->uses('ChroniclesController@getFactions')->name('factions');

    $router->get('round/{round}')->uses('ChroniclesController@getRound')->name('round');
    $router->get('round/{round}/rankings')->uses('ChroniclesController@getRoundRankings')->name('round.rankings');
    $router->get('round/{round}/{statKey}')->uses('ChroniclesController@getRoundStat')->name('round.stat');

    $router->get('ruler/{ruler}')->uses('ChroniclesController@getRuler')->name('ruler');
    $router->get('dominion/{dominion}')->uses('ChroniclesController@getDominion')->name('dominion');
    $router->get('faction/{faction}')->uses('ChroniclesController@getFaction')->name('faction');

});

// Staff

$router->group(['middleware' => ['auth', 'role:Developer|Administrator|Moderator'], 'prefix' => 'staff', 'as' => 'staff.'], static function (Router $router) {

    $router->get('/')->uses('Staff\StaffController@getIndex')->name('index');

    // Developer

//    $router->group(['middleware' => 'role:Developer', 'prefix' => 'developer', 'as' => 'developer.'], function (Router $router) {
//
//        $router->get('/')->uses('Staff\DeveloperController@getIndex')->name('index');
//
//        // simulate dominion by state string
//        // take over dominion & traverse state history
//        // set dominion state/attributes?
//
//    });

    // Administrator

    $router->group(['middleware' => 'role:Administrator', 'prefix' => 'administrator', 'as' => 'administrator.'], static function (Router $router) {

        $router->resource('dominions', 'Staff\Administrator\DominionController');

        $router->get('users/{user}/take-over', 'Staff\Administrator\UserController@takeOver')->name('users.take-over');
        $router->resource('users', 'Staff\Administrator\UserController');

        // view all users
        // view all council boards

    });

    // Moderator

    // todo
    // view flagged posts

});

// Misc
