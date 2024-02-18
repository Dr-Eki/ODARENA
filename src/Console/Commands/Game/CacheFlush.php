<?php

namespace OpenDominion\Console\Commands\Game;

use Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Services\Dominion\QuickstartService;

use OpenDominion\Models\Dominion;

class GenerateQuickstartCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:cache:flush';

    /** @var string The console command description. */
    protected $description = 'Flush all game caches';

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
        $this->info('Flushing game cache...');
        if(Cache::flush())
        {
            $this->info('Game caches flushed');
        }
        else
        {
            $this->error('Failed to flush game caches');
        }
    }


}
