<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Race;

class CalculationsController extends AbstractDominionController
{
    public function getIndex(Request $request)
    {
        $targetDominionId = $request->input('dominion');
        $targetDominion= null;
        $targetInfoOps = null;

        if ($targetDominionId !== null) {
            $dominion = $this->getSelectedDominion();
            $targetDominion = Dominion::find($targetDominionId);
            if ($targetDominion !== null) {
                $targetInfoOps = InfoOp::query()
                    ->where('target_dominion_id', $targetDominionId)
                    ->where('source_realm_id', $dominion->realm->id)
                    ->where('latest', true)
                    ->get()
                    ->filter(function($infoOp) {
                        if ($infoOp->type == 'barracks_spy') {
                            $hourTaken = $infoOp->created_at->startOfHour();
                            if ($hourTaken->diffInHours(now()) > 11) {
                                return false;
                            }
                        }
                        return true;
                    })
                    ->keyBy('type');
            }
        }

        $races = Race::with(['units', 'units.perks'])->where('playable',1)->orderBy('name')->get();

        $barbarian = Race::with(['units', 'units.perks'])->where('name','Barbarian')->get();

        $allRaces = $races->merge($barbarian)->sortBy('name');

        #dd($allRaces);

        return view('pages.dominion.calculations', [
            'landCalculator' => app(LandCalculator::class),
            'targetDominion' => $targetDominion,
            'targetInfoOps' => $targetInfoOps,
            'races' => $allRaces->all(),
            'raceHelper' => app(RaceHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }
}
