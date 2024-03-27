<?php

namespace OpenDominion\Console\Commands\Game;

use Log;
use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Models\Round;

class PrecalculateCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:precalculate';

    /** @var string The console command description. */
    protected $description = 'Precalculate the tick';

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

    public function handle(): void
    {
        $round = Round::latest()->first();

        if($round->hasEnded())
        {
            Log::info('Round ' . $round->number . ' has ended.');
            return;
        }

        if(!$round->hasStarted())
        {
            Log::info('Round ' . $round->number . ' has not started yet.');
            return;
        }

        #foreach ($activeRounds as $round)
        #{
            $dominions = $round->activeDominions()->get();
            foreach($dominions as $dominion)
            {
                $this->info("[Round {$round->number}, Tick {$round->ticks}] Precalculating {$dominion->name}");
                $this->tickService->precalculateTick($dominion);
            }
        #}
    }


}
