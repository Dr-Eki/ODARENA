@extends('layouts.master')
@section('title', 'Confirm Trade')

@section('content')
    @php
        $holdId = $tradeDetails['hold'];
        $hold = \OpenDominion\Models\Hold::find($holdId);
        $soldResourceKey = $tradeDetails['sold_resource'];
        $soldResourceAmount = $tradeDetails['sold_resource_amount'];
        $boughtResourceKey = $tradeDetails['bought_resource'];

        $soldResource = \OpenDominion\Models\Resource::where('key', $soldResourceKey)->first();

        $boughtResource = \OpenDominion\Models\Resource::where('key', $boughtResourceKey)->first();

        $boughtResourceAmount = $tradeCalculator->getBoughtResourceAmount($selectedDominion, $hold, $soldResource, $soldResourceAmount, $boughtResource);
    @endphp

    @push('page-scripts')
        <script type="text/javascript">
            /* *    
            *       Time left is 20 seconds. It's 2,000 becauese the interval is 10ms. This makes sense, don't worry. Change to timeLeft = 20 and interval 1000 for updates every second
            * */
            var timeLeft = 2000;
            var progressBar = document.getElementById('progress');
        
            var countdown = setInterval(function() {
                timeLeft--;
                progressBar.style.width = (timeLeft / 2000) * 100 + '%';
        
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    window.location.href = "{{ route('dominion.trade.routes.clear-trade-route') }}";
                }
            }, 10);
        </script>
    @endpush

    <div class="row">
        <div class="col-md-4 col-md-offset-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa-solid fa-arrow-right-arrow-left fa-fw"></i> Confirm Trade with {{ $hold->name }}
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-6 col-sm-6">
                            <h5>
                                <i class="fa-solid fa-arrow-right fa-fw"></i> You Sell
                            </h5>
                            <p>
                                <small class="text-muted">Resource:</small> {{ $soldResource->name }}<br>
                                <small class="text-muted">Amount:</small> {{ number_format($soldResourceAmount) }} per tick *<br>
                            </p>
                        </div>
                        <div class="col-xs-6 col-sm-6">
                            <h5>
                                <i class="fa-solid fa-arrow-left fa-fw"></i> You Buy
                            </h5>
                            <p>
                                <small class="text-muted">Resource:</small> {{ $boughtResource->name }}<br>
                                <small class="text-muted">Amount:</small> {{ number_format($boughtResourceAmount) }} per tick **<br>
                            </p>
                            <p>
                                <small class="text-muted">
                                    
                                </small>
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <p>You have 20 seconds to confirm this trade. If you do not confirm, the trade offer will be cancelled. If you need more time, refresh the page.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <div id="progressBar">
                                <div id="progress" style="height: 20px; width: 100%; background-color: #4caf50;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <form action="{{ route('dominion.trade.routes.create-trade-route') }}" method="post">
                                @csrf
                                <input type="hidden" name="hold" value="{{ $holdId }}">
                                <input type="hidden" name="sold_resource" value="{{ $soldResourceKey }}">
                                <input type="hidden" name="sold_resource_amount" value="{{ $soldResourceAmount }}">
                                <input type="hidden" name="bought_resource" value="{{ $boughtResourceKey }}">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fa-solid fa-check fa-fw"></i> Confirm Trade
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <a href="{{ route('dominion.trade.routes.clear-trade-route') }}" class="btn btn-default btn-block">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <div class="col-xs-6 col-sm-6">
                        <small class="text-muted">
                            * If you do not have enough to trade, the trade route will be cancelled and the hold's sentiment about you may go down.
                        </small>
                    </div>
                    <div class="col-xs-6 col-sm-6">
                        <small class="text-muted">
                            ** Amount received may fluctuate with market movements. You agree to receive the amount determined by the hold.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
