<?php

namespace OpenDominion\Providers;

use Bugsnag;
use Cache;
use Illuminate\Pagination\Paginator;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;

use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Calculators\Dominion\BarbarianCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\ConversionCalculator;
use OpenDominion\Calculators\Dominion\DeityCalculator;
use OpenDominion\Calculators\Dominion\ExpeditionCalculator;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SorceryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\TheftCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\RealmCalculator;

use OpenDominion\Services\Activity\ActivityService;
use OpenDominion\Services\Analytics\AnalyticsService;

use OpenDominion\Services\CouncilService;

use OpenDominion\Services\Dominion\Actions\BankActionService;
use OpenDominion\Services\Dominion\Actions\DailyBonusesActionService;
use OpenDominion\Services\Dominion\Actions\DestroyActionService;
use OpenDominion\Services\Dominion\Actions\EspionageActionService;
use OpenDominion\Services\Dominion\Actions\ExploreActionService;
use OpenDominion\Services\Dominion\Actions\ImproveActionService;
use OpenDominion\Services\Dominion\Actions\InvadeActionService;
use OpenDominion\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;
use OpenDominion\Services\Dominion\Actions\ReleaseActionService;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\BarbarianService;
use OpenDominion\Services\Dominion\DeityService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\InfoOpService;
use OpenDominion\Services\Dominion\InsightService;
use OpenDominion\Services\Dominion\OpenAIService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\RealmFinderService;
use Schema;

class AppServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        Paginator::useBootstrapThree();
        Schema::defaultStringLength(191);

        // Set Bugsnag app version
        if (($appVersion = Cache::get('version')) !== null) {
            Bugsnag::getConfig()->setAppVersion($appVersion);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        if ($this->app->environment() === 'local')
        {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }

        $this->registerCalculators();
        $this->registerHelpers();
        $this->registerServices();
    }

    protected function registerCalculators()
    {
        // Generic Calculators
        $this->app->singleton(NetworthCalculator::class);
        $this->app->singleton(RealmCalculator::class);

        // Dominion Calculators
        $this->app->singleton(BuildingCalculator::class);
        $this->app->singleton(CasualtiesCalculator::class);
        $this->app->singleton(ConversionCalculator::class);
        $this->app->singleton(DeityCalculator::class);
        $this->app->singleton(ExpeditionCalculator::class);
        $this->app->singleton(EspionageCalculator::class);
        $this->app->singleton(ImprovementCalculator::class);
        $this->app->singleton(LandCalculator::class);
        $this->app->singleton(MilitaryCalculator::class);
        $this->app->singleton(PopulationCalculator::class);
        $this->app->singleton(PrestigeCalculator::class);
        $this->app->singleton(ProductionCalculator::class);
        $this->app->singleton(ResourceCalculator::class);
        $this->app->singleton(RangeCalculator::class);
        $this->app->singleton(SorceryCalculator::class);
        $this->app->singleton(SpellCalculator::class);
        $this->app->singleton(TheftCalculator::class);

        // Dominion Action Calculators
        $this->app->singleton(ConstructionCalculator::class);
        $this->app->singleton(ExplorationCalculator::class);
        $this->app->singleton(RezoningCalculator::class);
        $this->app->singleton(TechCalculator::class);
        $this->app->singleton(TrainingCalculator::class);
    }

    protected function registerServices()
    {
        // Services
        $this->app->singleton(ActivityService::class);
        $this->app->singleton(AnalyticsService::class);
        $this->app->singleton(CouncilService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(RealmFinderService::class);
        $this->app->singleton(OpenAIService::class);

        // Dominion Services
        $this->app->singleton(BarbarianService::class);
        $this->app->singleton(DeityService::class);
        $this->app->singleton(HistoryService::class);
        $this->app->singleton(InfoOpService::class);
        $this->app->singleton(InsightService::class);
        $this->app->singleton(ProtectionService::class);
        $this->app->singleton(QueueService::class);
        $this->app->singleton(ResourceService::class);
        $this->app->singleton(SelectorService::class);
        $this->app->singleton(StatsService::class);
        $this->app->singleton(TickService::class);

        // Dominion Action Services
        $this->app->singleton(ChangeDraftRateActionService::class);
        $this->app->singleton(TrainActionService::class);
        $this->app->singleton(BankActionService::class);
        $this->app->singleton(DailyBonusesActionService::class);
        $this->app->singleton(DestroyActionService::class);
        $this->app->singleton(EspionageActionService::class);
        $this->app->singleton(ExploreActionService::class);
        $this->app->singleton(ImproveActionService::class);
        $this->app->singleton(InvadeActionService::class);
        $this->app->singleton(ReleaseActionService::class);
        $this->app->singleton(RezoneActionService::class);
        $this->app->singleton(SpellActionService::class);
    }

    protected function registerHelpers()
    {
        $this->app->singleton(RaceHelper::class);
        $this->app->singleton(SpellHelper::class);
        $this->app->singleton(UnitHelper::class);
    }

}
