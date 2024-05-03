<?php

namespace OpenDominion\Console\Commands\Game;

use DB;
use Illuminate\Console\Command;
use OpenDominion\Services\HoldService;
use OpenDominion\Models\Round;

class HoldPricesCommand extends Command
{
    protected $signature = 'game:hold:prices {roundId?}';
    protected $description = 'Update hold prices';

    private $holdService;

    public function __construct(HoldService $holdService)
    {
        parent::__construct();
        $this->holdService = $holdService;
    }

    public function handle()
    {
        $roundId = $this->argument('roundId');

        if($roundId and $round = Round::find($roundId))
        {
            $this->info("Using round {$round->number} (ID {$round->id} / name {$round->name})");
        }
        else
        {
            $this->info('No round specified, using active round');

            $round = Round::active()->orderBy('number', 'desc')->first();

            if(!$round)
            {
                $this->error('No active round found');
                dd($round);
                return;
            }
        }
        
        DB::transaction(function () use ($round) {
            foreach($round->holds as $hold)
            {
                $this->info("Updating hold prices for {$hold->name} (ID {$hold->id})");
                $this->holdService->setHoldPrices($hold, $round->ticks);
            }
        });
    }
}
