<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\AdvancementActionRequest;

use OpenDominion\Calculators\Dominion\AdvancementCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;

use OpenDominion\Helpers\AdvancementHelper;

use OpenDominion\Models\Advancement;

use OpenDominion\Services\Dominion\Actions\AdvancementActionService;

class AdvancementController extends AbstractDominionController
{
    public function getAdvancements()
    {
        $dominion = $this->getSelectedDominion();
        $advancementCalculator = app(AdvancementCalculator::class);
        $advancementHelper = app(AdvancementHelper::class);

        return view('pages.dominion.advancements', [
            'advancements' => $advancementHelper->getAdvancementsByRace($dominion->race), #Advancement::all()->where('enabled',1)->keyBy('key')->sortBy('name'),
            'advancementHelper' => app(AdvancementHelper::class),
            'advancementCalculator' => $advancementCalculator,
            'productionCalculator' => app(ProductionCalculator::class),
        ]);
    }

    public function postAdvancements(AdvancementActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $advancementActionService = app(AdvancementActionService::class);
        $advancement = Advancement::findOrFail($request->get('advancement_id'));

        try {
            $result = $advancementActionService->levelUp($dominion, $advancement);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }


        return redirect()->route('dominion.advancements');
    }
}
