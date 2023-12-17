<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\RealmArtefact;
use OpenDominion\Models\Spell;


use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\SorceryHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\ArtefactActionRequest;
use OpenDominion\Services\Dominion\Actions\ArtefactActionService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;

class ArtefactsController extends AbstractDominionController
{

    public function getArtefacts()
    {
        $dominion = $this->getSelectedDominion();

        foreach($dominion->round->realms as $realm)
        {
            $realmIds[] = $realm->id;

            if($realm->id == $dominion->realm->id)
            {
                $ownRealmArtefacts = RealmArtefact::where('realm_id', '=', $realm->id)->get();
            }
            else
            {
                $otherRealmArtefacts = RealmArtefact::where('realm_id', '!=', $realm->id)->get();
            }
        }

        $realmArtefacts = RealmArtefact::whereIn('realm_id', $realmIds)->get();

        $spells = Spell::where('enabled',1)->where('scope', 'artefact')->get();

        return view('pages.dominion.artefacts', [
            'governmentService' => app(GovernmentService::class),
            'protectionService' => app(ProtectionService::class),

            'casualtiesCalculator' => app(CasualtiesCalculator::class),
            'improvementCalculator' => app(ImprovementCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'prestigeCalculator' => app(PrestigeCalculator::class),
            'queueService' => app(QueueService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'resourceCalculator' => app(ResourceCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),

            'raceHelper' => app(RaceHelper::class),
            'sorceryHelper' => app(SorceryHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'unitHelper' => app(UnitHelper::class),

            'realmArtefacts' => $realmArtefacts,
            'ownRealmArtefacts' => $ownRealmArtefacts,
            'otherRealmArtefacts' => $otherRealmArtefacts,
            'spells' => $spells,
        ]);
    }

    public function postArtefacts(ArtefactActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $artefactActionService = app(ArtefactActionService::class);



        if($request->get('action_type') == 'attack')
        {
            try
            {
                $result = $artefactActionService->attack(
                    $dominion,
                    Realm::findOrFail($request->get('realm')),
                    Artefact::findOrFail($request->get('artefact')),
                    $request->get('unit')
                );

            }
            catch (GameException $e)
            {
                return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
            }
        }

        if($request->get('action_type') == 'spell')
        {
            try
            {
                $result = $artefactActionService->attack(
                    $dominion,
                    Realm::findOrFail($request->get('realm')),
                    Artefact::findOrFail($request->get('artefact')),
                    Spell::findOrFail($request->get('spell'))
                );

            }
            catch (GameException $e)
            {
                return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
            }
        }

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()->to($result['redirect'] ?? route('dominion.artefact'));
    }
}
