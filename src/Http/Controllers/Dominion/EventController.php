<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Log;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\DesecrationHelper;
use OpenDominion\Helpers\EventHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\SabotageHelper;
use OpenDominion\Helpers\SorceryHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;

class EventController extends AbstractDominionController
{
    public function index(string $eventUuid)
    {
        $viewer = $this->getSelectedDominion();
        $eventHelper = app(EventHelper::class);

        $query = GameEvent::query()
            ->with([
                'source',
                'source.race',
                'source.race.units',
                'source.race.units.perks',
                'source.realm',
                'target',
                'target.race',
                'target.race.units',
                'target.race.units.perks',
                'target.realm',
            ])
            ->where('id', $eventUuid);

        $event = $query->firstOrFail();

        if(!$eventHelper->canViewEvent($event, $viewer))
        {
            return redirect()->back()
                ->withErrors(['You cannot view this event.']);

            abort(403);
        }

        try {
            return view("pages.dominion.event.{$event->type}", [
                'event' => $event,
                'unitHelper' => app(UnitHelper::class),
                'militaryCalculator' => app(MilitaryCalculator::class),
                'desecrationHelper' => app(DesecrationHelper::class),
                'buildingHelper' => app(BuildingHelper::class),
                'landHelper' => app(LandHelper::class),
                'raceHelper' => app(RaceHelper::class),
                'sabotageHelper' => app(SabotageHelper::class),
                'sorceryHelper' => app(SorceryHelper::class),
                'unitHelper' => app(UnitHelper::class),
                'canViewSource' => $eventHelper->canViewEventDetails($event, $viewer, 'source'),
                'canViewTarget' => $eventHelper->canViewEventDetails($event, $viewer, 'target'),
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            throw $e; // Re-throw the exception so it can be handled by the framework
        }
    }


}
