<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\SelectorService;

#ODA
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;

class DashboardController extends AbstractController
{
    public function getIndex()
    {
        $selectorService = app(SelectorService::class);
        $selectorService->tryAutoSelectDominionForAuthUser();

        $landCalculator = app(LandCalculator::class);
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
            'landCalculator' => $landCalculator,
            'networthCalculator' => $networthCalculator,

            # Socials
            'url_youtube' => 'https://www.youtube.com/channel/UCGR9htOHUFzIfiPUsZapHhw',
            'url_facebook' => 'https://www.facebook.com/odarenagame/',
            'url_instagram' => 'https://instagram.com/OD_Arena',
            'url_twitter' => 'https://twitter.com/OD_Arena',
        ]);
    }
}
