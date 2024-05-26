<?php

namespace OpenDominion\Console\Commands\Game;

use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Services\Dominion\TickChangeService;

class TickCommitCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:tick:commit';

    /** @var string The console command description. */
    protected $description = 'Ticks the game (all active rounds)';

    /** @var TickChangeService */
    protected $tickChangeService;

    /**
     * GameTickCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->tickChangeService = app(TickChangeService::class);
    }

    /**
     * {@inheritdoc}
     */

    public function handle(): void
    {
        $this->tickChangeService->commit();
    }


}
