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
            </div>           
            <div class="box-body table-responsive box-border">

                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Hold</th>
                            <th>Resource Sold</th>
                            <th>Amount Sold Per Trade</th>
                            <th>Resource Bought</th>
                            <th>Current Price</th>
                            <th>Next Trade Amount</th>
                            <th>Trades</th>
                            <th>Total Bought</th>
                            <th>Total Sold</th>
                            <th colspan="2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($selectedDominion->tradeRoutes() as $tradeRoute)
                            <tr>
                                <td>{{ $tradeRoute->hold->name }}</td>
                                <td>{{ $tradeRoute->resourceSold->name }}</td>
                                <td>{{ $tradeRoute->amount }}</td>
                                <td>{{ $tradeRoute->hold->prices }}</td>
                                <td>x</td>
                                <td>{{ $tradeRoute->trades }}</td>
                                <td>{{ $tradeRoute->total_bought }}</td>
                                <td>{{ $tradeRoute->total_sold }}</td>
                                <td>
                                    <a href="{{ route('dominion.trade/cancel-route', $tradeRoute->id) }}" class="btn btn-xs btn-danger">Cancel</a>
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
                <h3 class="box-title"><i class="fa-solid fa-arrow-right-arrow-left fa-fw"></i> Trade In Progress</h3>
            </div>           
            <div class="box-body table-responsive no-padding">

                <table class="table table-hover">
                    <!-- 
                        Tables showing units returning and units sent
                    -->
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
                            <form method="post" action="{{ route('dominion.trade.routes.confirm-trade-route') }}">
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
                                        <th><em class="text-muted">Base prices</em></th>
                                        <th>Buy Price</th>
                                        <th>Sell Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hold->resourceKeys() as $resourceKey)
                                        @php 
                                            $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                            $buyPrice = $hold->buyPrice($resourceKey);
                                            $sellPrice = $hold->sellPrice($resourceKey);
                                        @endphp
                                        <tr>
                                            <td>{{ $resource->name }}</td>
                                            <td>{!! $buyPrice ? number_format($buyPrice, 4) : '&mdash;' !!}</td>
                                            <td>{!! $sellPrice ? number_format($sellPrice, 4) : '&mdash;' !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-2">
                            <h5>Resource Sold</h5>
                            <div class="input-group">
                                <div class="input-group">
                                    <select name="sold_resource" class="form-control" style="min-width:10em; width:100%;">
                                        @foreach($hold->desired_resources as $resourceKey)
                                            @php 
                                                $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                            @endphp
                                            <option value="{{ $resource->key }}">{{ $resource->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="input-group-btn">
                                        <input type="number" name="sold_resource_amount" class="form-control text-center" placeholder="0" min="0" size="8" style="min-width:10em; width:100%;" value="{{ old('offer.' . $hold->key . '.' . $resource->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <h5>Resource Bought</h5>
                            <div class="input-group">
                                <div class="input-group">
                                    <select name="bought_resource" class="form-control" style="min-width:10em; width:100%;">
                                        @foreach($hold->sold_resources as $resourceKey)
                                            @php 
                                                $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                            @endphp
                                            <option value="{{ $resource->key }}">{{ $resource->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="input-group-btn">
                                        <input type="number" name="bought_resource_amount" class="form-control text-center" placeholder="0" min="0" size="8" style="min-width:10em; width:100%;" value="{{ old('offer.' . $hold->key . '.' . $resource->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 text-center">
                            <h5>&nbsp;</h5>
                            @if($canTradeWithHold)
                                <span data-toggle="tooltip" data-placement="top" title="Submit trade offer with {{ $hold->name }}">
                            @else
                                <span data-toggle="tooltip" data-placement="top" title="You cannot trade with {{ $hold->name }}">
                            @endif
                                <button type="submit" class="btn btn-block btn-primary" {{ !$canTradeWithHold ? 'disabled' : null  }}>
                                    Offer Trade
                                </button>
                            </span>
                        </div>
                    </div>

                    @if($canTradeWithHold)
                        </form>
                    @endif
                @endforeach
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

                // Add event listeners
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
                            // Access the bought_resource_amount from the nested original object
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
