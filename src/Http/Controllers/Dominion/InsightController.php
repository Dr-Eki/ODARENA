<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Exceptions\GameException;

use OpenDominion\Http\Requests\Dominion\Actions\InsightActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\WatchDominionActionRequest;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionInsight;
use OpenDominion\Models\DominionDecreeState;

use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\DecreeCalculator;
use OpenDominion\Calculators\Dominion\DominionCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\LandImprovementCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\MoraleCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

use OpenDominion\Helpers\AdvancementHelper;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\DecreeHelper;
use OpenDominion\Helpers\DeityHelper;
use OpenDominion\Helpers\DominionHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\InsightHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\LandImprovementHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\ResearchHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Helpers\TerrainHelper;
use OpenDominion\Helpers\TitleHelper;
use OpenDominion\Helpers\UnitHelper;

#use OpenDominion\Services\GameEventService;
use OpenDominion\Services\Dominion\InsightService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Services\Dominion\Actions\WatchDominionActionService;





class InsightController extends AbstractDominionController
{
    /*
    public function getIndex()
    {
        $protectionService = app(ProtectionService::class);
        $landCalculator = app(LandCalculator::class);
        $self = $this->getSelectedDominion();

        $dominions = $self->round->activeDominions()
            ->with(['realm', 'round'])
            ->get()
            ->filter(function ($dominion) use ($self, $protectionService) {
                return (
                    # Is not in protection
                    !$protectionService->isUnderProtection($dominion) and

                    # Round has started
                    $dominion->round->hasStarted() and

                    # Does not have fog of war
                    !$dominion->getSpellPerkValue('fog_of_war')
                );
            })
            ->sortByDesc(function ($dominion) use($landCalculator) {
                return $dominion->land;
            })
            ->values();


        return view('pages.dominion.insight.index', [
            'dominions' => $dominions,
            'landCalculator' => $landCalculator,
            'protectionService' => $protectionService,
            'networthCalculator' => app(NetworthCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
        ]);
    }
    */

    public function getWatchedDominions()
    {
        $selectedDominion = $this->getSelectedDominion();

        return view('pages.dominion.insight.watched-dominions', [
            'landCalculator' => app(LandCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'selectedDominion' => $selectedDominion,
        ]);
    }

    public function watchDominion(WatchDominionActionRequest $request)
    {
        $watcher = $this->getSelectedDominion();
        $watchDominionActionService = app(WatchDominionActionService::class);
        $dominion = Dominion::findOrFail($request->get('dominion_id'));

        try {
            $result = $watchDominionActionService->watchDominion($watcher, $dominion);

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()->to($result['redirect'] ?? route('dominion.insight.show', $dominion));
    }

    public function unwatchDominion(WatchDominionActionRequest $request)
    {
        $watcher = $this->getSelectedDominion();
        $watchDominionActionService = app(WatchDominionActionService::class);
        $dominion = Dominion::findOrFail($request->get('dominion_id'));

        try {
            $result = $watchDominionActionService->unwatchDominion($watcher, $dominion);

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()->to($result['redirect'] ?? route('dominion.insight.show', $dominion));
    }

    public function getDominion(Dominion $dominion)
    {

        $dominionAdvancements = $dominion->advancements()->get()->sortBy('name');
        $dominionTechs = $dominion->techs()->get()->sortBy('name');

        return view('pages.dominion.insight.show', [
            'dominion' => $dominion,
            'dominionAdvancements' => $dominionAdvancements,
            'dominionTechs' => $dominionTechs,
            'dominionDecreeStates' => DominionDecreeState::where('dominion_id', $dominion->id)->get(),

            'advancementHelper' => app(AdvancementHelper::class),
            'buildingHelper' => app(BuildingHelper::class),
            'decreeHelper' => app(DecreeHelper::class),
            'deityHelper' => app(DeityHelper::class),
            'dominionHelper' => app(DominionHelper::class),
            'insightHelper' => app(InsightHelper::class),
            'improvementHelper' => app(ImprovementHelper::class),
            'landHelper' => app(LandHelper::class),
            'landImprovementHelper' => app(LandImprovementHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'researchHelper' => app(ResearchHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'techHelper' => app(TechHelper::class),
            'terrainHelper' => app(TerrainHelper::class),
            'titleHelper' => app(TitleHelper::class),
            'unitHelper' => app(UnitHelper::class),

            'buildingCalculator' => app(BuildingCalculator::class),
            'casualtiesCalculator' => app(CasualtiesCalculator::class),
            'decreeCalculator' => app(DecreeCalculator::class),
            'dominionCalculator' => app(DominionCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'improvementCalculator' => app(ImprovementCalculator::class),
            'landImprovementCalculator' => app(LandImprovementCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'moraleCalculator' => app(MoraleCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'prestigeCalculator' => app(PrestigeCalculator::class),
            'productionCalculator' => app(ProductionCalculator::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'resourceCalculator' => app(ResourceCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),

            'protectionService' => app(ProtectionService::class),
            'statsService' => app(StatsService::class),
            'queueService' => app(QueueService::class),

            'insightService' => app(InsightService::class),
        ]);
    }

    public function postCaptureDominionInsight(InsightActionRequest $request)
    {
        $insightService = app(InsightService::class);
        #$protectionService = app(ProtectionService::class);

        $target = Dominion::findOrFail($request->get('target_dominion_id'));
        $source = $this->getSelectedDominion();

        try
        {
            $result = $insightService->captureDominionInsight($target, $source);
        }
        catch (GameException $e)
        {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()->route('dominion.insight.archive', $target);

    }

    public function getDominionInsightArchive(Dominion $dominion)
    {
        #$insightService = app(InsightService::class);
        $target = $dominion;
        $source = $this->getSelectedDominion();

        #$dominionInsight = $insightService->getDominionInsight($target, $source);

        $dominionInsights = DominionInsight::where('dominion_id',$dominion->id)->where(function($query) use($source)
        {
            $query->where('source_realm_id', $source->realm->id)
                  ->orWhere('source_realm_id', NULL);
        })
        ->orderBy('created_at','desc')
        ->paginate(1);

        return view('pages.dominion.insight.archive', [
            'dominion' => $target,
            'dominionInsights' => $dominionInsights,

            'advancementHelper' => app(AdvancementHelper::class),
            'buildingHelper' => app(BuildingHelper::class),
            'decreeHelper' => app(DecreeHelper::class),
            'deityHelper' => app(DeityHelper::class),
            'improvementHelper' => app(ImprovementHelper::class),
            'insightHelper' => app(InsightHelper::class),
            'landHelper' => app(LandHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'researchHelper' => app(ResearchHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'techHelper' => app(TechHelper::class),
            'titleHelper' => app(TitleHelper::class),
            'unitHelper' => app(UnitHelper::class),

            'buildingCalculator' => app(BuildingCalculator::class),
            'dominionCalculator' => app(DominionCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'improvementCalculator' => app(ImprovementCalculator::class),
            'landImprovementCalculator' => app(LandImprovementCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'productionCalculator' => app(ProductionCalculator::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'resourceCalculator' => app(ResourceCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),

            'protectionService' => app(ProtectionService::class),
            'statsService' => app(StatsService::class),
            'queueService' => app(QueueService::class),
        ]);
    }





}
