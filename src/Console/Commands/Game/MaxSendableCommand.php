<?php

namespace OpenDominion\Console\Commands\Game;

use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Models\Dominion;

class MaxSendableCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:maxsendable {--dominionId= : dominion ID}';

    /** @var string The console command description. */
    protected $description = 'Get max 4:3 of a dominion';


    /**
     * GameTickCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->militaryCalculator = app(MilitaryCalculator::class);
    }

    public function handle(): void
    {
        $this->info('Started calculation at ' . now());

        $dominion = Dominion::findOrFail($this->option('dominionId'));
        $this->info('Found dominion: ' . $dominion->name . ' (# ' . $dominion->realm->number . ')');

        $this->info('Calculating...');

        $result = $this->militaryCalculator->estimateMaxSendable($dominion);

        $this->info('Finished calculation at ' . now());

        #dump($result);
    }


}
