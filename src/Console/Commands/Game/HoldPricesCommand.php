<?php

namespace OpenDominion\Console\Commands\Game;

use DB;
use Illuminate\Console\Command;
use OpenDominion\Services\HoldService;
use OpenDominion\Models\Round;

class HoldPricesCommand extends Command
{
    protected $signature = 'game:hold:prices';
    protected $description = 'Update hold prices';

    private $holdService;

    public function __construct(HoldService $holdService)
    {
        parent::__construct();
        $this->holdService = $holdService;
    }

    public function handle()
    {
        $round = Round::active()->orderBy('number', 'desc')->first();
        
        DB::transaction(function () use ($round) {
            foreach($round->holds as $hold)
            {
                $this->info("Updating hold prices for {$hold->name} (ID {$hold->id})");
                $this->holdService->setHoldPrices($hold, $round->ticks);
            }
        });
    }
}
