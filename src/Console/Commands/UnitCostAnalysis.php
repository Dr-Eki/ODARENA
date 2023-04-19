<?php

namespace OpenDominion\Console\Commands;

use DB;
use Carbon\Carbon;
use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Factories\RoundFactory;
use OpenDominion\Models\Race;
use OpenDominion\Helpers\UnitCostHelper;
use RuntimeException;

class UnitCostAnalysis extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'cost';

    /** @var string The console command description. */
    protected $description = 'Creates a new round and prompts for conditions and settings';

    /** @var UnitCostHelper */
    protected $unitCostHelper;

    /**
     * RoundOpenCommand constructor.
     *
     * @param RoundFactory $roundFactory
     * @param RealmFactory $realmFactory
     */
    public function __construct(
        UnitCostHelper $unitCostHelper
    ) {
        parent::__construct();

        $this->unitCostHelper = $unitCostHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $races = Race::all()->where('playable',1);

        foreach($races as $race)
        {
            $this->info('------------------------');
            $this->info('Analysing ' . $race->name);

            foreach($race->units as $unit)
            {
                $this->info($unit->name);
            }

        }

    }
}
