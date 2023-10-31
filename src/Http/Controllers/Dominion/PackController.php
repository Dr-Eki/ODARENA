<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Auth;
use DB;

use Illuminate\Http\Request;
use LogicException;
use OpenDominion\Services\PackService;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;

use OpenDominion\Models\Pack;

class PackController extends AbstractDominionController
{
    public function getPack()
    {
        return view('pages.dominion.pack', 
            [
                'militaryCalculator' => app(MilitaryCalculator::class),
                'packService' => app(PackService::class),
            ]);
    }

    public function changeStatus(Request $request)
    {
        $status = $request->input('status');

        $packService = app(PackService::class);

        $user = Auth::user();

        $pack = Pack::findOrFail($request->input('pack_id'));

        DB::transaction(function () use ($user, $pack, $packService, $status) {
            
            if(!$packService->canEditPack($user, $pack))
            {
                throw new LogicException('You cannot edit this pack.');
            }

            if(!in_array($status,[0,1,2]))
            {
                throw new LogicException('Invalid pack status.');
            }

            $packService->changePackStatus($pack, $status);
        });

        return redirect()->route('dominion.pack');
    }
}
