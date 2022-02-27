<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Exceptions\GameException;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

use OpenDominion\Helpers\SorceryHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Http\Requests\Dominion\Actions\CastSpellRequest;
use OpenDominion\Http\Requests\Dominion\Actions\PerformEspionageRequest;
use OpenDominion\Http\Requests\Dominion\Actions\OffensiveOpsRequest;
use OpenDominion\Http\Requests\Dominion\Actions\SorceryRequest;

use OpenDominion\Models\Dominion;

use OpenDominion\Services\Dominion\Actions\SorceryActionService;
use OpenDominion\Services\Dominion\Actions\EspionageActionService;
use OpenDominion\Services\Dominion\ProtectionService;

# ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\SpellDamageCalculator;
use OpenDominion\Models\Spell;

class SorceryController extends AbstractDominionController
{
    public function getSorcery()
    {
        $dominion = $this->getSelectedDominion();
        $sorceryHelper = app(SorceryHelper::class);

        $spells = $sorceryHelper->getSorcerySpellsForRace($dominion->race) ;#Spell::all()->where('scope','hostile')->whereIn('class',['active'/*,'passive'*/])->where('enabled',1)->sortBy('name');

        return view('pages.dominion.sorcery', [
            'spells' => $spells,
            'icon' => '',
            'boxClass' => '',

            'spellHelper' => app(SpellHelper::class),
            'sorceryHelper' => $sorceryHelper,
            'unitHelper' => app(UnitHelper::class),

            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'resourceCalculator' => app(ResourceCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            'spellDamageCalculator' => app(SpellDamageCalculator::class),
        ]);
    }

    public function postSorcery(SorceryRequest $request)
    {

        $caster = $this->getSelectedDominion();
        $sorceryActionService = app(SorceryActionService::class);
        $resourceCalculator = app(ResourceCalculator::class);

        $spell = Spell::where('id', $request->get('spell'))->firstOrFail();
        $enhancementResource = null;
        $enhancementAmount = 0;

        if($request->get('enhancement'))
        {
            $enhancementResource = Resource::where('id', $request->get('enhancement'))->firstOrFail();
            $enhancementAmount = 0;
        }

        $target = Dominion::findOrFail($request->get('target_dominion'));

        $wizardStrength = $request->get('wizard_strength');

        try
        {
            $result = $sorceryActionService->performSorcery(
                $caster,
                $target,
                $spell,
                $wizardStrength,
                $enhancementResource,
                $enhancementAmount
            );
        }
        catch (GameException $e)
        {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()->to($result['redirect'] ?? route('dominion.sorcery'));

    }
}
