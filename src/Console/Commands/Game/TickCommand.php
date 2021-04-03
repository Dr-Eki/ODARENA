<?php

namespace OpenDominion\Console\Commands\Game;

use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Services\Dominion\TickService;

# ODA
#use Illuminate\Support\Carbon;
#use OpenDominion\Models\Round;

class TickCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:tick';

    /** @var string The console command description. */
    protected $description = 'Ticks the game';

    /** @var TickService */
    protected $tickService;

    /**
     * GameTickCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->tickService = app(TickService::class);
    }

    /**
     * {@inheritdoc}
     */
#    public function handle(): void
#    {
#        $this->tickService->tickHourly();
#
#        if (now()->hour === 0) {
#            $this->tickService->tickDaily();
#        }
#    }

    public function handle(): void
    {
        $this->tickService->tickHourly();
        #$hoursSinceRoundStarted = now()->startOfHour()->diffInHours(Carbon::parse($round->start_date)->startOfHour());
        #if ($hoursSinceRoundStarted % 24 === 0 && now()->minute < 15)
        if (now()->hour === 0 && now()->minute < 15)
        {
            $this->tickService->tickDaily();
        }
    }


}
