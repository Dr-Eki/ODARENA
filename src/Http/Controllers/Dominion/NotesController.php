<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Http\Requests\Dominion\Actions\NotesActionRequest;
use OpenDominion\Services\Dominion\HistoryService;

use OpenDominion\Models\Dominion;

class NotesController extends AbstractDominionController
{



    public function getNotes()
    {
        $dominion = $this->getSelectedDominion();
        return view('pages.dominion.notes', [
            'notes' => $dominion->notes,
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
