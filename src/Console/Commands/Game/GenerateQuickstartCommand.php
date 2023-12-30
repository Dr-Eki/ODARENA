<?php

namespace OpenDominion\Console\Commands\Game;

use Log;
use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Services\Dominion\QuickstartService;

use OpenDominion\Models\Dominion;

class GenerateQuickstartCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:generate:quickstart {--dominionId= : dominion ID}';

    /** @var string The console command description. */
    protected $description = 'Generate quickstart a specific dominion';

    /** @var GameEventService */
    protected $quickstartService;

    /**
     * GameTickCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->quickstartService = app(QuickstartService::class);
    }

    public function handle(): void
    {
        $dominion = Dominion::findOrFail($this->option('dominionId'));
        $quickstart = $this->quickstartService->generateQuickstartFile($dominion);

        $this->info($quickstart);
    }


}
