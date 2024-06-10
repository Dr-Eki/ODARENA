<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use DB;
use Illuminate\Http\Request;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Round;
use OpenDominion\Models\Quickstart;
use OpenDominion\Services\Dominion\QuickstartService;
use OpenDominion\Services\Dominion\RoundService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\PackService;
use OpenDominion\Services\UserService;

use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\RoundHelper;

class DashboardController extends AbstractController
{
    public function getIndex()
    {
        $selectorService = app(SelectorService::class);
        $selectorService->tryAutoSelectDominionForAuthUser();

        $networthCalculator = app(NetworthCalculator::class);

        $dominions = Dominion::with(['round', 'realm', 'race'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        $rounds = Round::with('league')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.dashboard.index', [
            'dominions' => $dominions,
            'rounds' => $rounds,
            'networthCalculator' => $networthCalculator,
            'packService' => app(PackService::class),
            'roundHelper' => app(RoundHelper::class),
            'user' => Auth::user(),

            'roundService' => app(RoundService::class),

            # Socials
            'url_youtube' => 'https://www.youtube.com/channel/UCGR9htOHUFzIfiPUsZapHhw',
            'url_facebook' => 'https://www.facebook.com/odarenagame/',
            'url_instagram' => 'https://instagram.com/OD_Arena',
            'url_twitter' => 'https://twitter.com/OD_Arena',
        ]);
    }

    public function postDeletePack(Pack $pack)
    {
        $packService = app(PackService::class);

        if (!$packService->canDeletePack(Auth::user(), $pack)) {
            return redirect()->back();
        }

        DB::transaction(function () use ($pack)
        {
            $pack->delete();
            DB::table('realm_history')->where('realm_id', '=', $pack->realm->id)->delete();
            DB::table('realms')->where('id', '=', $pack->realm->id)->delete();
        });

        return redirect()->back();
    }

    public function getQuickstarts()
    {
        $user = Auth::user();

        return view('pages.dashboard.quickstarts', [
            'user' => $user
        ]);
    }

    public function getQuickstartsExport()
    {
        $user = Auth::user();

        return view('pages.dashboard.quickstarts.export', [
            'user' => $user
        ]);
    }

    public function postQuickstartsExport()
    {
        $user = Auth::user();

        $userService = app(UserService::class);

        try {
            $userService->generateApiKey($user);
        } catch (\Exception $e) {
            return redirect()->route('dashboard.quickstarts.export')->with('alert-danger', 'Failed to generate API key');
        }

        return redirect()->route('dashboard.quickstarts.export')->with('alert-success', 'API key generated');
    }

    public function getQuickstartsImport()
    {
        $user = Auth::user();

        return view('pages.dashboard.quickstarts.import', [
            'user' => $user
        ]);
    }

    public function postQuickstartsImport(Request $request)
    {
        $quickstartId = $request->get('quickstart_id');
        $apiKey = $request->get('api_key');
        $source = $request->get('source');

        if(!is_numeric($quickstartId) or !is_string($apiKey) or !is_string($source) or $quickstartId < 1 or strlen($apiKey) < 60 or !in_array($source, ['sim', 'game']))
        {
            return redirect()->route('dashboard.quickstarts.import')->with('alert-danger', 'Invalid input. Please check all parameters and try again.');
        }

        try {
            app(QuickstartService::class)->importQuickstart($source, $quickstartId, $apiKey);
        } catch (\Exception $e) {
            xtLog("[QS{$quickstartId}] Failed to import quickstart: {$e->getMessage()}", 'error');
            return redirect()->route('dashboard.quickstarts.import')->with('alert-danger', 'Failed to import quickstart. Make sure quickstart ID and API key are correct for the source.');
        }

        return redirect()->route('dashboard.quickstarts')->with('alert-success', 'Quickstart imported successfully!');
    }

    public function postQuickstartsSave(Request $request)
    {
        $quickstartService = app(QuickstartService::class);

        $dominion = Dominion::findOrFail($request->get('dominion_id'));

        try {
            $quickstart = $quickstartService->saveQuickstart(
                $dominion,
                (string)$request->get('name'),
                (int)$request->get('offensive_power'),
                (int)$request->get('defensive_power'),
                (string)$request->get('description'),
                (bool)$request->get('is_public')
            );
        } catch (\Exception $e) {
            xtLog("[{$dominion->id}] Failed to save quickstart: {$e->getMessage()}", 'error');
            return redirect()->route('dominion.quickstart')->with('alert-danger', "Could not save quickstart for dominion {$dominion->id}.");
        }

        return redirect()->route('dashboard.quickstarts')->with('alert-success', "Quickstart saved with ID {$quickstart->id}.");
    }

    public function postQuickstartsToggleEnabled(Quickstart $quickstart)
    {
        $quickstart->enabled = !$quickstart->enabled;
        $quickstart->save();

        return redirect()->route('dashboard.quickstarts');
    }

    public function postQuickstartsToggleAvailability(Quickstart $quickstart)
    {
        $quickstart->is_public = !$quickstart->is_public;
        $quickstart->save();

        return redirect()->route('dashboard.quickstarts');
    }

    public function postQuickstartsDelete(Quickstart $quickstart)
    {
        $user = Auth::user();

        if($quickstart->user_id !== $user->id)
        {
            return redirect()->route('dashboard.quickstarts')->with('alert-danger', 'You can only delete your own quickstarts.');
        }

        $quickstart->delete();

        return redirect()->route('dashboard.quickstarts')->with('alert-success', 'Quickstart deleted');
    }

    public function getQuickstartViaApi(int $quickstartId, string $apiKey)
    {
        $quickstart = Quickstart::find($quickstartId);

        if($quickstart === null)
        {
            return response()->json(['error' => 'Quickstart not found'], 404);
        }

        if(!$apiKey)
        {
            return response()->json(['error' => 'API key required'], 403);
        }

        if(!isset($quickstart->user))
        {
            return response()->json(['error' => 'Quickstart is not available via API'], 403);
        }

        if($quickstart->user->api_key !== $apiKey)
        {
            return response()->json(['error' => 'Invalid API key'], 403);
        }

        # Remove the user from the quickstart
        $quickstart->makeHidden('user', 'user_id');


        return response()->json($quickstart);
    }
}
