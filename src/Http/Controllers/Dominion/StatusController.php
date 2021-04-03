<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Services\Dominion\QueueService;

# ODA
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\TickActionRequest;
use OpenDominion\Services\Dominion\Actions\TickActionService;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\TitleHelper;

class StatusController extends AbstractDominionController
{
    public function getStatus()
    {
        $resultsPerPage = 25;
        $selectedDominion = $this->getSelectedDominion();

        $notifications = $selectedDominion->notifications()->paginate($resultsPerPage);

        return view('pages.dominion.status', [
            'dominionProtectionService' => app(ProtectionService::class),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'notificationHelper' => app(NotificationHelper::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'prestigeCalculator' => app(PrestigeCalculator::class),
            'queueService' => app(QueueService::class),
            'unitHelper' => app(UnitHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'titleHelper' => app(TitleHelper::class),
            'notifications' => $notifications
        ]);
    }

    public function postTick(TickActionRequest $request)
    {
        $ticks = intval($request->ticks);
        $ticks = min($ticks, 84);
        $ticks = max($ticks, 0);
        $dominion = $this->getSelectedDominion();
        $tickActionService = app(TickActionService::class);

        try
        {
            for ($tick = 1; $tick <= $ticks; $tick++)
            {
                $result = $tickActionService->tickDominion($dominion);
                usleep(rand(100000,100000));
            }
        }
        catch (GameException $e)
        {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()->to(route($request->returnTo));

    }

}
