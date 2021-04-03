<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Http\Requests\Dominion\Actions\CastSpellRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\ProtectionService;

# ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\SpellDamageCalculator;
use OpenDominion\Models\Spell;

class MagicController extends AbstractDominionController
{
    public function getMagic()
    {

        $friendlyAuras = Spell::all()->where('scope','friendly')->where('class','passive')->where('enabled',1)->sortBy('key');
        $hostileAuras = Spell::all()->where('scope','hostile')->where('class','passive')->where('enabled',1)->sortBy('key');
        $selfAuras = Spell::all()->where('scope','self')->where('class','passive')->where('enabled',1)->sortBy('key');

        $friendlyImpacts = Spell::all()->where('scope','friendly')->where('class','active')->where('enabled',1)->sortBy('key');
        $hostileImpacts = Spell::all()->where('scope','hostile')->where('class','active')->where('enabled',1)->sortBy('key');
        $selfImpacts = Spell::all()->where('scope','self')->where('class','active')->where('enabled',1)->sortBy('key');

        $hostileInfos = Spell::all()->where('scope','hostile')->where('class','info')->where('enabled',1)->sortBy('key');

        return view('pages.dominion.magic', [
            'landCalculator' => app(LandCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'spellDamageCalculator' => app(SpellDamageCalculator::class),
            'friendlyAuras' => $friendlyAuras,
            'hostileAuras' => $hostileAuras,
            'selfAuras' => $selfAuras,
            'friendlyImpacts' => $friendlyImpacts,
            'hostileImpacts' => $hostileImpacts,
            'selfImpacts' => $selfImpacts,
            'hostileInfos' => $hostileInfos,
        ]);
    }

    public function postMagic(CastSpellRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $spellActionService = app(SpellActionService::class);

        $spell = Spell::where('key', $request->get('spell'))->first();

        $target = null;

        if($spell->scope == 'hostile' and $request->has('target_dominion'))
        {
            $target = Dominion::findOrFail($request->get('target_dominion'));
        }
        elseif($spell->scope == 'friendly' and $request->has('friendly_dominion'))
        {
            $target = Dominion::findOrFail($request->get('friendly_dominion'));
        }

        try {
            $result = $spellActionService->castSpell(
                $dominion,
                $spell->key,
                $target
            );

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'magic.cast',
            $result['data']['spell'],
            $result['data']['manaCost']
        ));

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);

        return redirect()
            ->to($result['redirect'] ?? route('dominion.magic'))
            ->with('target_dominion', $request->get('target_dominion'));
    }
}
