<?php

use Illuminate\Routing\Router;
use Spatie\Honeypot\ProtectAgainstSpam;

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
        $router->post('register')->uses('Auth\RegisterController@register')->middleware(ProtectAgainstSpam::class);
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

    // Profile
    // todo

    // Dashboard
    $router->get('dashboard')->uses('DashboardController@getIndex')->name('dashboard');

    // Settings
    $router->get('settings')->uses('SettingsController@getIndex')->name('settings');
    $router->post('settings')->uses('SettingsController@postIndex');

    // Settings
    $router->get('patreon')->uses('PatreonController@getPatreonAccessToken')->name('patreon');
    $router->get('patreon/pledge')->uses('PatreonController@getPatreonPledge')->name('patreon/pledge');

    // Round Register
    $router->get('round/{round}/register')->uses('RoundController@getRegister')->name('round.register');
    $router->post('round/{round}/register')->uses('RoundController@postRegister');

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

            # Resources
            $router->get('resources')->uses('Dominion\ResourcesController@getResources')->name('resources');
            $router->post('resources')->uses('Dominion\ResourcesController@postResources');

            // Advisors
            $router->get('advisors')->uses('Dominion\AdvisorsController@getAdvisors')->name('advisors');
            $router->get('advisors/production')->uses('Dominion\AdvisorsController@getAdvisorsProduction')->name('advisors.production');
            $router->get('advisors/statistics')->uses('Dominion\AdvisorsController@getAdvisorsStatistics')->name('advisors.statistics');
            $router->get('advisors/history')->uses('Dominion\AdvisorsController@getHistory')->name('advisors.history');

            // Mentor
            $router->get('mentor')->uses('Dominion\MentorController@getMentor')->name('mentor');
            $router->get('mentor/general')->uses('Dominion\MentorController@getMentorGeneral')->name('mentor.general');
            $router->get('mentor/advancements')->uses('Dominion\MentorController@getMentorAdvancements')->name('mentor.advancements');
            $router->get('mentor/buildings')->uses('Dominion\MentorController@getMentorBuildings')->name('mentor.buildings');
            $router->get('mentor/espionage')->uses('Dominion\MentorController@getMentorEspionage')->name('mentor.espionage');
            $router->get('mentor/explore')->uses('Dominion\MentorController@getMentorExplore')->name('mentor.explore');
            $router->get('mentor/invade')->uses('Dominion\MentorController@getMentorInvade')->name('mentor.invade');
            $router->get('mentor/magic')->uses('Dominion\MentorController@getMentorMagic')->name('mentor.magic');
            $router->get('mentor/military')->uses('Dominion\MentorController@getMentorMilitary')->name('mentor.military');

            # Buildings
            $router->get('buildings')->uses('Dominion\BuildingController@getBuildings')->name('buildings');
            $router->post('buildings')->uses('Dominion\BuildingController@postBuildings');
            $router->get('demolish')->uses('Dominion\BuildingController@getDemolish')->name('demolish');
            $router->post('demolish')->uses('Dominion\BuildingController@postDemolish');

            # Land
            $router->get('land')->uses('Dominion\LandController@getLand')->name('land');
            $router->post('land')->uses('Dominion\LandController@postLand');

            // Improvements
            $router->get('improvements')->uses('Dominion\ImprovementController@getImprovements')->name('improvements');
            $router->post('improvements')->uses('Dominion\ImprovementController@postImprovements');

            // Techs
            $router->get('advancements')->uses('Dominion\TechController@getTechs')->name('advancements');
            $router->post('advancements')->uses('Dominion\TechController@postTechs');

            // Military
            $router->get('military')->uses('Dominion\MilitaryController@getMilitary')->name('military');
            $router->post('military/change-draft-rate')->uses('Dominion\MilitaryController@postChangeDraftRate')->name('military.change-draft-rate');
            $router->post('military/train')->uses('Dominion\MilitaryController@postTrain')->name('military.train');
            $router->get('military/release')->uses('Dominion\MilitaryController@getRelease')->name('military.release');
            $router->post('military/release')->uses('Dominion\MilitaryController@postRelease');

            // Invade
            $router->get('invade')->uses('Dominion\InvasionController@getInvade')->name('invade');
            $router->post('invade')->uses('Dominion\InvasionController@postInvade');

            // Expedition
            $router->get('expedition')->uses('Dominion\ExpeditionController@getExpedition')->name('expedition');
            $router->post('expedition')->uses('Dominion\ExpeditionController@postExpedition');

            // Event result
            $router->get('event/{uuid}')->uses('Dominion\EventController@index')->name('event');

            // Calculations
            $router->get('calculations')->uses('Dominion\CalculationsController@getIndex')->name('calculations');

            // Hostile Ops
            $router->get('offensive-ops')->uses('Dominion\OffensiveOpsController@getOffensiveOps')->name('offensive-ops');
            $router->post('offensive-ops')->uses('Dominion\OffensiveOpsController@postOffensiveOps');

            // Theft
            $router->get('theft')->uses('Dominion\TheftController@getTheft')->name('theft');
            $router->post('theft')->uses('Dominion\TheftController@postTheft');

            // Friendly Magic
            $router->get('friendly-ops')->uses('Dominion\FriendlyOpsController@getFriendlyOps')->name('friendly-ops');
            $router->post('friendly-ops')->uses('Dominion\FriendlyOpsController@postFriendlyOps');

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
            $router->get('insight')->uses('Dominion\InsightController@getIndex')->name('insight');
            $router->get('insight/{dominion}')->uses('Dominion\InsightController@getDominion')->name('insight.show');
            $router->get('insight/{dominion}/archive')->uses('Dominion\InsightController@getDominionInsightArchive')->name('insight.archive');
            $router->post('insight/{dominion}/archive')->uses('Dominion\InsightController@postCaptureDominionInsight');

            // Government
            $router->get('government')->uses('Dominion\GovernmentController@getIndex')->name('government');
            $router->post('government/monarch')->uses('Dominion\GovernmentController@postMonarch')->name('government.monarch');
            $router->post('government/deity')->uses('Dominion\GovernmentController@postDeity')->name('government.deity');
            $router->post('government/renounce')->uses('Dominion\GovernmentController@postRenounce')->name('government.renounce');
            $router->post('government/realm')->uses('Dominion\GovernmentController@postRealm')->name('government.realm');
            $router->post('government/royal-guard/join')->uses('Dominion\GovernmentController@postJoinRoyalGuard')->name('government.royal-guard.join');
            $router->post('government/elite-guard/join')->uses('Dominion\GovernmentController@postJoinEliteGuard')->name('government.elite-guard.join');
            $router->post('government/royal-guard/leave')->uses('Dominion\GovernmentController@postLeaveRoyalGuard')->name('government.royal-guard.leave');
            $router->post('government/elite-guard/leave')->uses('Dominion\GovernmentController@postLeaveEliteGuard')->name('government.elite-guard.leave');
            $router->post('government/war/declare')->uses('Dominion\GovernmentController@postDeclareWar')->name('government.war.declare');
            $router->post('government/war/cancel')->uses('Dominion\GovernmentController@postCancelWar')->name('government.war.cancel');

            // Realm
            $router->get('realm/{realmNumber?}')->uses('Dominion\RealmController@getRealm')->name('realm');
            $router->post('realm/change-realm')->uses('Dominion\RealmController@postChangeRealm')->name('realm.change-realm');

            // Town Crier
            $router->get('world-news/{realmNumber?}')->uses('Dominion\WorldNewsController@getIndex')->name('world-news');

            // Notes
            $router->get('notes')->uses('Dominion\NotesController@getNotes')->name('notes');
            $router->post('notes')->uses('Dominion\NotesController@postNotes');

            // Misc
            $router->post('misc/clear-notifications')->uses('Dominion\MiscController@postClearNotifications')->name('misc.clear-notifications');
            $router->post('misc/close-pack')->uses('Dominion\MiscController@postClosePack')->name('misc.close-pack');
            $router->post('misc/delete')->uses('Dominion\MiscController@postDeleteDominion')->name('misc.delete');

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
  $router->get('termsandconditions')->uses('LegalController@getTermsAndConditions')->name('termsandconditions');
  $router->get('privacypolicy')->uses('LegalController@getPrivacyPolicy')->name('privacypolicy');
});

// About
$router->group(['prefix' => 'about', 'as' => 'about.'], static function (Router $router)
{
  $router->get('/')->uses('AboutController@getIndex')->name('index');
});


// Scribes
$router->group(['prefix' => 'scribes', 'as' => 'scribes.'], static function (Router $router) {
    $router->get('factions')->uses('ScribesController@getRaces')->name('factions');
    $router->get('buildings')->uses('ScribesController@getBuildings')->name('buildings');
    $router->get('espionage')->uses('ScribesController@getEspionage')->name('espionage');
    $router->get('titles')->uses('ScribesController@getTitles')->name('titles');
    $router->get('advancements')->uses('ScribesController@getAdvancements')->name('advancements');
    $router->get('spells')->uses('ScribesController@getSpells')->name('spells');
    $router->get('spy-ops')->uses('ScribesController@getSpyops')->name('spy-ops');
    $router->get('improvements')->uses('ScribesController@getImprovements')->name('improvements');
    $router->get('deities')->uses('ScribesController@getDeities')->name('deities');

    $router->get('{race}')->uses('ScribesController@getRace')->name('faction');
});



// Valhalla
/*
$router->group(['prefix' => 'valhalla', 'as' => 'valhalla.'], static function (Router $router) {

    $router->get('/')->uses('ValhallaController@getIndex')->name('index');
    $router->get('round/{round}')->uses('ValhallaController@getRound')->name('round');
    $router->get('round/{round}/{type}')->uses('ValhallaController@getRoundType')->name('round.type');
    $router->get('user/{user}')->uses('ValhallaController@getUser')->name('user');

});
*/
// Chronicles

$router->group(['prefix' => 'chronicles', 'as' => 'chronicles.'], static function (Router $router) {

    $router->get('/')->uses('ChroniclesController@getIndex')->name('index');
    $router->get('round/{round}')->uses('ChroniclesController@getRound')->name('round');
    $router->get('round/{round}/{type}')->uses('ChroniclesController@getRoundType')->name('round.type');
    $router->get('ruler/{user}')->uses('ChroniclesController@getRuler')->name('user');

});


// Donate

// Contact

// Links

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
