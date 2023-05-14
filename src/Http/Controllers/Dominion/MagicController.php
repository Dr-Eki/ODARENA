<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Helpers\SpellHelper;
#use OpenDominion\Http\Requests\Dominion\Actions\CastSpellRequest;
#use OpenDominion\Http\Requests\Dominion\Actions\PerformEspionageRequest;
use OpenDominion\Http\Requests\Dominion\Actions\MagicRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
#use OpenDominion\Services\Dominion\Actions\EspionageActionService;

use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Models\Spell;
#use OpenDominion\Models\Spyop;

class MagicController extends AbstractDominionController
{
    public function getMagic()
    {
        $dominion = $this->getSelectedDominion();

        #$selfSpells = Spell::all()->where('scope','self')->where('enabled',1)->sortBy('name');
        #$friendlySpells = Spell::all()->where('scope','friendly')->where('enabled',1)->sortBy('name');

        return view('pages.dominion.magic', [
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'magicCalculator' => app(MagicCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'resourceCalculator' => app(ResourceCalculator::class),
        ]);
    }

    public function postMagic(MagicRequest $request)
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

            $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);

            return redirect()
                ->to($result['redirect'] ?? route('dominion.magic'))
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
                ->to($result['redirect'] ?? route('dominion.magic'))
                ->with('friendly_dominion', $request->get('friendly_dominion'));
        }
        else
        {
            dd('Bugggg...');
            throw new GameException('Unknown friendly ops action.');
        }

    }
}
