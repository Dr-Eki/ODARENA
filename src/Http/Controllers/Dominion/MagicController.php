<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Http\Requests\Dominion\Actions\MagicRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;

use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
#use OpenDominion\Models\Spyop;

class MagicController extends AbstractDominionController
{
    public function getMagic()
    {
        $dominion = $this->getSelectedDominion();

        $pestilence = Spell::where('key', 'pestilence')->first();
        $lesserPestilence = Spell::where('key', 'lesser_pestilence')->first();

        $pestilences = DominionSpell::whereIn('spell_id', [$pestilence->id, $lesserPestilence->id])->where('caster_id', $dominion->id)->get()->sortByDesc('created_at');

        return view('pages.dominion.magic', [
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'magicCalculator' => app(MagicCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'resourceCalculator' => app(ResourceCalculator::class),
            'pestilences' => $pestilences,
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
