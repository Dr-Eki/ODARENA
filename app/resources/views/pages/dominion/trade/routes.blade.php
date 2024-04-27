@extends('layouts.master')
@section('title', 'Trade Routes')

@section('content')

@push('styles')
<style>
    .hold-row {
        border-bottom: 1px solid #ddd;
        margin-bottom: 10px;
        margin-left: 0;
        margin-right: 0;
        box-sizing: border-box;
    }
</style>
@endpush

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa-solid fa-arrow-right-arrow-left fa-fw"></i> Trade Routes</h3>
                <a class="pull-right btn btn-primary" href="{{ route('dominion.trade.trades-in-progress') }}">View Trades In Progress</a>
            </div>
            <div class="box-body table-responsive box-border">

                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Hold</th>
                            <th>Resource Sold</th>
                            <th>
                                <span data-toggle="tooltip" data-placement="top" title="How much of the sold resource you are selling each tick.">
                                    Amount Sold per Trade
                                </span>
                            </th>
                            <th>Resource Bought</th>
                            <th>
                                <span data-toggle="tooltip" data-placement="top" title="Actual amount is calculated each tick, using the prevailing market price and any modifiers such as sentiment and trade perks.">
                                    Est. Amount Bought
                                </span>
                            </th>
                            <th>Trades</th>
                            <th>Total Bought</th>
                            <th>Total Sold</th>
                            <th class="text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($selectedDominion->tradeRoutes as $tradeRoute)
                            <tr>
                                <td><a href="{{ route('dominion.trade.hold', $tradeRoute->hold->key) }}"><strong>{{ $tradeRoute->hold->name }}</strong></a></td>
                                <td>
                                    <span data-toggle="tooltip" data-placement="top" title='<span class="text-muted">Market sell price: </span>{{ number_format($tradeRoute->hold->buyPrice($tradeRoute->soldResource->key), config('trade.price_decimals')) }}'>
                                        {{ $tradeRoute->soldResource->name }}
                                    </span>
                                </td>
                                <td>{{ number_format($tradeRoute->source_amount) }}</td>
                                <td>
                                    <span data-toggle="tooltip" data-placement="top" title='<span class="text-muted">Market buy price: </span>{{ number_format($tradeRoute->hold->sellPrice($tradeRoute->boughtResource->key), config('trade.price_decimals')) }}'>
                                        {{ $tradeRoute->boughtResource->name }}
                                    </span>
                                </td>
                                <td>{{ number_format($tradeRoute->source_amount * $tradeRoute->hold->buyPrice($tradeRoute->soldResource->key) * $tradeRoute->hold->sellPrice($tradeRoute->boughtResource->key)) }}</td>
                                <td>{{ number_format($tradeRoute->trades) }}</td>
                                <td>{{ number_format($tradeRoute->total_bought) }}</td>
                                <td>{{ number_format($tradeRoute->total_sold) }}</td>
                                <td>
                                    <a href="{{ route('dominion.trade.routes.edit', [$tradeRoute->hold->key, $tradeRoute->soldResource->key]) }}" class="btn btn-xs btn-primary">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa-solid fa-arrow-right-from-bracket"></i> Establish Trade Route</h3>
            </div>

            <div class="box-body">
                @foreach($selectedDominion->round->holds->sortBy('id') as $hold)
                    @php
                        $sentiment = optional($hold->sentiments->where('target_id', $selectedDominion->id)->first())->sentiment ?? 0;
                        $sentimentDescription = $holdHelper->getSentimentDescription($sentiment);
                        $sentimentClass = $holdHelper->getSentimentClass($sentimentDescription);
                        $canTradeWithHold = $tradeCalculator->canDominionTradeWithHold($selectedDominion, $hold);
                        $user = Auth::user();
                    @endphp

                    @if($canTradeWithHold)
                        @if(isset($user->settings['skip_trade_confirmation']) and $user->settings['skip_trade_confirmation'])
                            <form method="post" action="{{ route('dominion.trade.routes.create-trade-route') }}">
                        @else
                            <form method="post" action="{{ route('dominion.trade.routes.store-trade-details') }}">
                        @endif
                        @csrf
                    @endif

                    <div class="row hold-row">
                        <div class="col-md-2">
                            <h5><a href="{{ route('dominion.trade.hold', $hold->key) }}"><strong>{{ $hold->name }}</strong></a></h5>
                            <small class="text-muted">Ruler:</small> <em>{{ $hold->title->name }}</em> {{ $hold->ruler_name }}<br>
                            <small class="text-muted">Faction:</small> {{ $hold->race->name }}<br>
                            <small class="text-muted">Sentiment:</small> <span data-toggle="tooltip" data-placement="top" title='<span class="text-muted">Sentiment:</span>&nbsp;{{ number_format($sentiment) }}'>
                                    <span class="label label-{{ $sentimentClass }}">{{ ucwords($sentimentDescription) }}</span>
                                </span><br>
                            <small class="text-muted">Trade routes:</small> {{ number_format($hold->tradeRoutes->count()) }} ({{ number_format($hold->tradeRoutes->where('dominion_id', $selectedDominion->id)->count()) }})
                            <input type="hidden" name="hold" value="{{ $hold->id }}">
                        </div>
                        <div class="col-md-4">
                            <table class="table table-responsive table-hover">
                                <colgroup>
                                    <col width="100">
                                    <col width="100">
                                    <col width="100">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th><em class="text-muted">Hold prices</em></th>
                                        <th>Buy Price</th>
                                        <th>Sell Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hold->resourceKeys() as $resourceKey)
                                        @php 
                                            $resource = OpenDominion\Models\Resource::fromKey($resourceKey);
                                            $buyPrice = $hold->buyPrice($resourceKey);
                                            $sellPrice = $hold->sellPrice($resourceKey);
                                        @endphp
                                        <tr>
                                            <td>{{ $resource->name }}</td>
                                            <td>{!! $buyPrice ? number_format($buyPrice, config('trade.price_decimals')) : '&mdash;' !!}</td>
                                            <td>{!! $sellPrice ? number_format($sellPrice, config('trade.price_decimals')) : '&mdash;' !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(!$canTradeWithHold)
                            <div class="col-md-6">
                                <p>You cannot trade with this hold. You do not have any resources the hold is interested in buying.</p>
                            </div>
                        @else
                            <div class="col-md-2">
                                <h5 data-toggle="tooltip" data-placement="top" title="Do not exceed current net production or current stockpile (whichever is highest).">You Sell</h5>
                                <div class="input-group">
                                    <select name="sold_resource" class="input-group form-control" style="min-width:10em; width:100%;">
                                        @foreach($hold->desired_resources as $resourceKey)
                                            @php 
                                                $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                                $canTradeResource = $tradeCalculator->canDominionTradeResource($selectedDominion, $resource);
                                            @endphp

                                            @if($tradeCalculator->canDominionTradeResource($selectedDominion, $resource))
                                                <option value="{{ $resource->key }}">{{ $resource->name }}</option>
                                            @else
                                                <option value="{{ $resource->key }}" disabled>{{ $resource->name }} (cannot trade)</option>
                                            @endif

                                        @endforeach
                                    </select>
                                    <span class="input-group-btn">
                                        <input type="number" name="sold_resource_amount" class="form-control text-center" placeholder="0" min="1" size="5" style="min-width:5em; width:100%;" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <h5>You Buy</h5>
                                <div class="input-group">
                                    <select name="bought_resource" class="form-control" style="min-width:10em; width:100%;">
                                        @foreach($hold->sold_resources as $resourceKey)
                                            @php 
                                                $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                            @endphp

                                            @if($tradeCalculator->canDominionTradeResource($selectedDominion, $resource))
                                                <option value="{{ $resource->key }}">{{ $resource->name }}</option>
                                            @else
                                                <option value="{{ $resource->key }}" disabled>{{ $resource->name }} (cannot trade)</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <span class="input-group-btn">
                                        <input type="number" name="bought_resource_amount" class="form-control text-center" placeholder="0" min="0" size="5" style="min-width:5em; width:100%;" disabled>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <h5>&nbsp;</h5>
                                <span data-toggle="tooltip" data-placement="top" title="Submit trade offer to {{ $hold->name }}">
                                <button type="submit" class="btn btn-block btn-primary">
                                    Offer Trade
                                </button>
                                </span>
                            </div>
                        @endif
                    </div>

                    @if($canTradeWithHold)
                        </form>
                    @endif
                @endforeach
            </div>            
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa-solid fa-book"></i> Trade Ledger</h3>
            </div>
            <div class="box-body table-responsive box-border">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Hold</th>
                            <th>Tick</th>
                            <th>Resource Sold</th>
                            <th>Resource Bought</th>
                            <th>Amount Sold</th>
                            <th>Amount Bought</th>
                            <th>Return Tick</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($selectedDominion->tradeLedger->sortByDesc('created_at')->take(10) as $tradeLedgerEntry)
                            <tr>
                                <td><a href="{{ route('dominion.trade.hold', $tradeLedgerEntry->hold->key) }}"><strong>{{ $tradeLedgerEntry->hold->name }}</strong></a></td>
                                <td>{{ number_format($tradeLedgerEntry->tick) }}</td>
                                <td>{{ $tradeLedgerEntry->soldResource->name }}</td>
                                <td>{{ $tradeLedgerEntry->boughtResource->name }}</td>
                                <td>{{ number_format($tradeLedgerEntry->source_amount) }}</td>
                                <td>{{ number_format($tradeLedgerEntry->target_amount) }}</td>
                                <td>{{ number_format($tradeLedgerEntry->return_tick) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="col-md-2 text-left">
                    <small class="text-muted">Showing last 10 entries. </small>
                </div>
                <div class="col-md-10 text-right">
                    <a href="{{ route('dominion.trade.ledger') }}" class="btn btn-primary">View Full Ledger</a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('page-scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('.box-primary form');

        forms.forEach(form => {
            const soldResourceSelect = form.querySelector('select[name="sold_resource"]');
            const boughtResourceSelect = form.querySelector('select[name="bought_resource"]');
            const soldResourceAmountInput = form.querySelector('input[name="sold_resource_amount"]');
            const boughtResourceAmountInput = form.querySelector('input[name="bought_resource_amount"]');

            // Check if elements exist before adding event listeners
            if (soldResourceSelect && boughtResourceSelect && soldResourceAmountInput && boughtResourceAmountInput) {
                [soldResourceSelect, boughtResourceSelect, soldResourceAmountInput].forEach(input => {
                    input.addEventListener('change', function() {
                        const data = {
                            hold: form.querySelector('input[name="hold"]').value,
                            sold_resource: soldResourceSelect.value,
                            sold_resource_amount: soldResourceAmountInput.value,
                            bought_resource: boughtResourceSelect.value,
                        };

                        fetch('/dominion/trade/routes/calculate-trade-route', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(data)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.original && data.original.bought_resource_amount) {
                                boughtResourceAmountInput.value = data.original.bought_resource_amount;
                            } else {
                                console.error('Invalid response structure:', data);
                                boughtResourceAmountInput.value = 'Error';
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    });
                });
            }
        });
    });
    </script>
@endpush

@push('page-scripts')
    <script type="text/javascript">
    $("form").submit(function () {
        // prevent duplicate form submissions
        $(this).find(":submit").attr('disabled', 'disabled');
    });
    </script>
@endpush
