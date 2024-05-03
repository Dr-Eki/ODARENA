<?php

namespace OpenDominion\Console\Commands\Game;

use DB;
use Illuminate\Console\Command;
use OpenDominion\Factories\HoldFactory;
use OpenDominion\Models\Round;
use OpenDominion\Services\HoldService;

class SpawnHoldCommand extends Command
{
    protected $signature = 'game:hold:discover';
    protected $description = 'Discover holds';

    private $holdFactory;
    private $holdService;

    public function __construct()
    {
        parent::__construct();
        $this->holdFactory = app(HoldFactory::class);
        $this->holdService = app(HoldService::class);
    }

    public function handle()
    {
        $round = Round::active()->orderBy('number', 'desc')->first();

        if(!$round)
        {
            $this->error('No active round found');
            dd($round);
            return;
        }

        $count = $this->ask('Amount of holds to discover [default 1]') ?? 1;

        DB::transaction(function () use ($round, $count) {
            for ($i = 0; $i < $count; $i++) {
                if($hold = $this->holdService->discoverHold($round, null))
                {
                    $this->holdService->setHoldPrices($hold, $round->ticks);
                    $this->info("Discovered hold: {$hold->name} (ID {$hold->id})");
                }
                else
                {
                    $this->warn('Could not discover hold. Are all holds in play already?');
                }
            }
        });
    }
}
