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
use OpenDominion\Http\Requests\Dominion\Actions\IntelligenceRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\Actions\EspionageActionService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\InfoOpService;

# ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\SpellDamageCalculator;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Spyop;

class IntelligenceController extends AbstractDominionController
{
    public function getIntelligence()
    {
        $dominion = $this->getSelectedDominion();

        $infoOps = Spyop::all()->where('scope','info')->where('enabled',1)->sortBy('name');
        $hostileInfos = Spell::all()->where('scope','hostile')->where('class','info')->where('enabled',1)->sortBy('name');

        $latestInfoOps = $dominion->realm->infoOps()
            ->with('sourceDominion')
            ->with('targetDominion')
            ->with('targetDominion.race')
            ->with('targetDominion.realm')
            ->where('type', '!=', 'clairvoyance')
            ->where('latest', '=', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('target_dominion_id');

        return view('pages.dominion.intelligence', [
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
            'infoOpService' => app(InfoOpService::class),
            'hostileInfos' => $hostileInfos,
            'infoOps' => $infoOps,
            'latestInfoOps' => $latestInfoOps,
        ]);
    }

    public function postIntelligence(IntelligenceRequest $request)
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
                ->to($result['redirect'] ?? route('dominion.intelligence'))
                ->with('spell_dominion', $request->get('spell_dominion'));
        }
        elseif($request->type === 'espionage')
        {
            $dominion = $this->getSelectedDominion();
            $espionageActionService = app(EspionageActionService::class);

            try {
                /** @noinspection PhpParamsInspection */
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
                ->to($result['redirect'] ?? route('dominion.intelligence'))
                ->with('espionage_dominion', $request->get('espionage_dominion'));
        }
        else
        {
            new GameException('Unknown action.');
        }

    }
}
