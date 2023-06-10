<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\RoundService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\PackService;

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

        return view('pages.dashboard', [
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

        $pack->delete();

        return redirect()->back();
    }
}
