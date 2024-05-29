<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Http\Requests\Dominion\Actions\NotesActionRequest;
use OpenDominion\Services\Dominion\HistoryService;


use OpenDominion\Services\Dominion\TickChangeService;

use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;

class NotesController extends AbstractDominionController
{
    public function getNotes()
    {
        $dominion = $this->getSelectedDominion();
        return view('pages.dominion.notes', [
            'notes' => $dominion->notes,
            'networthCalculator' => app(NetworthCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),

            'tickChangeService' => app(TickChangeService::class),
        ]);
    }

    public function postNotes(NotesActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $notes = $request->get('notes');

        $dominion->fill([
          'notes' => $notes,
        ])->save(['event' => HistoryService::EVENT_ACTION_NOTE]);

        return view('pages.dominion.notes', [
            'notes' => $dominion->notes,
        ]);
    }
}
