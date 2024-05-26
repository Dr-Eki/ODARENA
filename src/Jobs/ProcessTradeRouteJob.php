<?php

namespace OpenDominion\Jobs;

use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use OpenDominion\Services\TradeRoute\QueueService;
use OpenDominion\Services\TradeService;

class ProcessTradeRouteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tradeRoute;

    protected $queueService;
    protected $tradeService;
    
    public function __construct($tradeRoute)
    {
        $this->tradeRoute = $tradeRoute;

        $this->queueService = app(QueueService::class);
        $this->tradeService = app(TradeService::class);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        xtLog("[TR{$this->tradeRoute->id}] *** Advancing and finishing trade route queues");
        #DB::transaction(function () {
            $this->queueService->finishTradeRouteQueues($this->tradeRoute);
            $this->queueService->advanceTradeRouteQueues($this->tradeRoute);
        #});
        
        xtLog("[TR{$this->tradeRoute->id}] *** Checking trade route and queueing new trades");
        #DB::transaction(function () {
            $this->tradeService->handleTradeRoute($this->tradeRoute);
        #});
    }
}