<?php

namespace OpenDominion\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use OpenDominion\Services\Hold\QueueService;
use OpenDominion\Services\HoldService;

// handleTradeRoutesTick()

class ProcessHoldJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $hold;

    protected $queueService;
    protected $holdService;
    
    public function __construct($hold)
    {
        $this->hold = $hold;

        $this->queueService = app(QueueService::class);
        $this->holdService = app(HoldService::class);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        # Handle queues
        xtLog("*** [HL{$this->hold->id}] Handling queues");
        $this->queueService->handleHoldQueues($this->hold);

        # Handle resource production
        xtLog("*** [HL{$this->hold->id}] Handling resource production");
        $this->holdService->handleHoldResourceProduction($this->hold);

        # Handle building construction
        xtLog("*** [HL{$this->hold->id}] Handling construction");
        $this->holdService->handleHoldConstruction($this->hold);

        # Update prices
        xtLog("*** [HL{$this->hold->id}] Setting prices");
        $this->holdService->setHoldPrices($this->hold);

        # Update hold land
        xtLog("*** [HL{$this->hold->id}] Updating land construction");
        $this->holdService->updateHoldLand($this->hold);

    }
}