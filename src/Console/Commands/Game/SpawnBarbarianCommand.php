<?php

namespace OpenDominion\Console\Commands\Game;

use DB;
use Illuminate\Console\Command;
use OpenDominion\Services\BarbarianService;
use OpenDominion\Models\Round;

class SpawnBarbarianCommand extends Command
{
    protected $signature = 'game:spawn:barbarian';
    protected $description = 'Spawns barbarian';

    private $barbarianService;

    public function __construct(BarbarianService $barbarianService)
    {
        parent::__construct();
        $this->barbarianService = $barbarianService;
    }

    public function handle()
    {
        $round = Round::active()->orderBy('number', 'desc')->first();
        $count = $this->ask('Amount of barbarians to spawn') ?? 1;

        DB::transaction(function () use ($round, $count) {
            for ($i = 0; $i < $count; $i++) {
                $hold = $this->barbarianService->createBarbarian($round);
                $this->info("Spawned Barbarian: {$hold->name} (ID {$hold->id})");
            }
        });

    }
}
