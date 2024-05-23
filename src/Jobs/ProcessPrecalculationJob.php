<?php

namespace OpenDominion\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use OpenDominion\Calculators\Dominion\TickCalculator;

class ProcessPrecalculationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $dominion;
    protected $tickCalculator;
    
    public function __construct($dominion)
    {
        $this->dominion = $dominion;
        $this->tickCalculator = app(TickCalculator::class);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        xtLog("[{$this->dominion->id}] ** Precalculating dominion {$this->dominion->name}");
        $this->tickCalculator->precalculateTick($this->dominion, true);
        xtLog("[{$this->dominion->id}] ** Precalculationg done");
    }
}