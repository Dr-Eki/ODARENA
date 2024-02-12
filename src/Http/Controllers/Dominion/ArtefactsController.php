<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\ArtefactCalculator;
use OpenDominion\Models\Artefact;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmArtefact;
use OpenDominion\Models\Spell;

use OpenDominion\Helpers\ArtefactHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\ArtefactActionRequest;
use OpenDominion\Services\Dominion\Actions\ArtefactActionService;
use OpenDominion\Services\Dominion\ProtectionService;

class ArtefactsController extends AbstractDominionController
{

    public function getArtefacts()
    {
        $dominion = $this->getSelectedDominion();

        $otherRealmArtefacts = RealmArtefact::where('realm_id', '!=', $dominion->realm->id)->get();
        $ownRealmArtefacts = RealmArtefact::where('realm_id', $dominion->realm->id)->get();
        
        # Merge the two collections
        $realmArtefacts = $ownRealmArtefacts->merge($otherRealmArtefacts);

        $spells = Spell::where('enabled',1)->where('scope', 'artefact')->get();

        return view('pages.dominion.artefacts', [

            'artefactHelper' => app(ArtefactHelper::class),
            'artefactCalculator' => app(ArtefactCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            
            'protectionService' => app(ProtectionService::class),

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

        $realmArtefact = RealmArtefact::findOrFail($request->get('target_artefact'));

        if($request->get('action_type') == 'military')
        {
            try
            {
                $result = $artefactActionService->militaryAttack(
                    $dominion,
                    $realmArtefact->realm,
                    $realmArtefact->artefact,
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

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()->to($result['redirect'] ?? route('dominion.expedition'));
    }
}
