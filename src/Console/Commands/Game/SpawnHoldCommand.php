<?php

namespace OpenDominion\Console\Commands\Game;

use DB;
use Illuminate\Console\Command;
use OpenDominion\Factories\HoldFactory;
use OpenDominion\Models\Round;

class SpawnHoldCommand extends Command
{
    protected $signature = 'game:spawn:hold';
    protected $description = 'Spawns holds';

    private $holdFactory;

    public function __construct(HoldFactory $holdFactory)
    {
        parent::__construct();
        $this->holdFactory = $holdFactory;
    }

    public function handle()
    {
        $round = Round::active()->orderBy('number', 'desc')->first();
        $count = $this->ask('Amount of holds to spawn [default 1]') ?? 1;

        DB::transaction(function () use ($round, $count) {
            for ($i = 0; $i < $count; $i++) {
                if($hold = $this->holdFactory->create($round))
                {
                    $this->info("Spawned hold: {$hold->name} (ID {$hold->id})");
                }
                else
                {
                    $this->warn('Could not spawn hold. Are all holds in play already?');
                }
            }
        });
    }
}
