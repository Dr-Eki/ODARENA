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
use OpenDominion\Http\Requests\Dominion\Actions\OffensiveOpsRequest;
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

class OffensiveOpsController extends AbstractDominionController
{
    public function getOffensiveOps()
    {
        $dominion = $this->getSelectedDominion();

        $hostileSpyops = Spyop::all()->where('scope','hostile')->where('enabled',1)->sortBy('name');
        $theftSpyops = Spyop::all()->where('scope','theft')->where('enabled',1)->sortBy('name');
        $spyops = $hostileSpyops->merge($theftSpyops);
        $hostileSpells = Spell::all()->where('scope','hostile')->whereIn('class',['active','passive'])->where('enabled',1)->sortBy('name');

        return view('pages.dominion.offensive-ops', [
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
            #'hostileSpyops' => $hostileSpyops,
            #'theftSpyops' => $theftSpyops,
            'spyops' => $spyops,
            'hostileSpells' => $hostileSpells
        ]);
    }

    public function postOffensiveOps(OffensiveOpsRequest $request)
    {
        if($request->type === 'spell')
        {
            $dominion = $this->getSelectedDominion();
            $spellActionService = app(SpellActionService::class);

            $spell = Spell::where('key', $request->get('operation'))->first();

            $target = Dominion::findOrFail($request->get('spell_dominion'));

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
                ->to($result['redirect'] ?? route('dominion.offensive-ops'))
                ->with('spell_dominion', $request->get('spell_dominion'));
        }
        elseif($request->type === 'espionage')
        {
            $dominion = $this->getSelectedDominion();
            $espionageActionService = app(EspionageActionService::class);

            try {
                $result = $espionageActionService->performOperation(
                    $dominion,
                    $request->get('operation'),
                    Dominion::findOrFail($request->get('espionage_dominion'))
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
                'espionage.perform',
                $result['data']['operation']
            ));

            $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);

            return redirect()
                ->to($result['redirect'] ?? route('dominion.offensive-ops'))
                ->with('espionage_dominion', $request->get('espionage_dominion'));
        }
        elseif($request->type === 'break_spell')
        {
            $dominion = $this->getSelectedDominion();
            $spellActionService = app(SpellActionService::class);

            $spell = Spell::where('key', $request->get('operation'))->first();

            try {
                $result = $spellActionService->breakSpell(
                    $dominion,
                    $spell->key
                );

            } catch (GameException $e) {
                return redirect()->back()
                    ->withInput($request->all())
                    ->withErrors([$e->getMessage()]);
            }

            $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);

            return redirect()
                ->to($result['redirect'] ?? route('dominion.offensive-ops'));
        }
        else
        {
            throw new GameException('Unknown offensive ops action.');
        }

    }
}
