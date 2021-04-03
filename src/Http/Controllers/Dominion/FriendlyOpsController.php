<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Http\Requests\Dominion\Actions\CastSpellRequest;
use OpenDominion\Http\Requests\Dominion\Actions\PerformEspionageRequest;
use OpenDominion\Http\Requests\Dominion\Actions\FriendlyOpsRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\Actions\EspionageActionService;
use OpenDominion\Services\Dominion\ProtectionService;

# ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\SpellDamageCalculator;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Spyop;

class FriendlyOpsController extends AbstractDominionController
{
    public function getFriendlyOps()
    {
        $dominion = $this->getSelectedDominion();

        $selfSpells = Spell::all()->where('scope','self')->where('enabled',1)->sortBy('name');
        $friendlySpells = Spell::all()->where('scope','friendly')->where('enabled',1)->sortBy('name');

        return view('pages.dominion.friendly-ops', [
            'landCalculator' => app(LandCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            'espionageCalculator' => app(EspionageCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'espionageHelper' => app(EspionageHelper::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'spellDamageCalculator' => app(SpellDamageCalculator::class),
            'selfSpells' => $selfSpells,
            'friendlySpells' => $friendlySpells
        ]);
    }

    public function postFriendlyOps(FriendlyOpsRequest $request)
    {
        if($request->type === 'self_spell')
        {
            $dominion = $this->getSelectedDominion();
            $spellActionService = app(SpellActionService::class);

            $spell = Spell::where('key', $request->get('spell'))->first();

            $target = null;

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
                ->to($result['redirect'] ?? route('dominion.friendly-ops'))
                ->with('spell_dominion', $request->get('spell_dominion'));
        }
        elseif($request->type === 'friendly_spell')
        {
            $dominion = $this->getSelectedDominion();
            $spellActionService = app(SpellActionService::class);

            $spell = Spell::where('key', $request->get('spell'))->first();

            $target = Dominion::findOrFail($request->get('friendly_dominion'));

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
                ->to($result['redirect'] ?? route('dominion.friendly-ops'))
                ->with('friendly_dominion', $request->get('friendly_dominion'));
        }
        else
        {
            dd('Bugggg...');
            throw new GameException('Unknown friendly ops action.');
        }

    }
}
