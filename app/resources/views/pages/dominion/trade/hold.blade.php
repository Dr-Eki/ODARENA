@extends('layouts.master')
@section('title', "Hold | $hold->name")

@section('content')

@if($selectedDominion->protection_ticks > 0 or $hold->status == 0)
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-eye-slash"></i> Hold not available</h3>
            </div>
            <div class="box-body">
                <p>You cannot currently view this hold.</p>
            </div>
        </div>
    </div>
</div>
@else
<div class="row">
    <div class="col-sm-12 col-md-9">
        @component('partials.dominion.insight.box')
            @slot('title', ('The Hold of ' . $hold->name))
            @slot('titleIconClass', 'fa fa-solid fa-dungeon')
            @slot('tableResponsive', false)
            @slot('noPadding', true)

            <div class="row">
                <div class="col-xs-9 col-sm-6">
                    <table class="table">
                        <colgroup>
                            <col width="50%">
                            <col width="50%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th colspan="2">Overview</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Ruler:</td>
                                <td><em>{{ $hold->title->name }}</em> {{ $hold->ruler_name }}</td>
                            </tr>
                            <tr>
                                <td>Faction:</td>
                                <td>{{ $hold->race->name }}</td>
                            </tr>
                            <tr>
                                <td>Land:</td>
                                <td>{{ number_format($hold->land) }}</td> 
                            </tr>
                            <tr>
                                <td>{{-- Str::plural($raceHelper->getPeasantsTerm($hold->race)) --}}Population:</td>
                                <td>{{ number_format($hold->peasants) }}</td>
                            </tr>
                            <tr>
                                <td colspan="2">{{ $hold->description }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-xs-12 col-sm-6">
                    <table class="table">
                        <colgroup>
                            <col width="50%">
                            <col width="50%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th colspan="2">Military</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Morale:</td>
                                <td>{{ number_format($hold->morale) }}</td>
                            </tr>
                            <tr>
                                <td>Defensive Power:</td>
                                <td>{{ number_format($hold->defensive_power) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endcomponent
    </div>

    <div class="col-sm-12 col-md-3">
        @component('partials.dominion.insight.box')

            @slot('title', 'Sentiments')
            @slot('titleIconClass', 'fa-solid fa-handshake')
            @slot('tableResponsive', false)
            @slot('noPadding', true)

            <table class="table">
                <colgroup>
                    <col width="200">
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th>Dominion</th>
                        <th>Relationship</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hold->sentiments->sortByDesc('sentiment') as $holdSentiment)
                        @php
                            $sentiment = $holdSentiment->sentiment;
                            $sentimentDescription = $holdHelper->getSentimentDescription($sentiment);
                            $sentimentClass = $holdHelper->getSentimentClass($sentimentDescription);

                        @endphp
                        <tr>
                            <td>
                                {{ optional($holdSentiment->target)->name ?? 'Unknown' }}
                                (# {{ optional($holdSentiment->target)->realm->number ?? 'Unknown' }})
                            </td>
                            <td>
                                <span data-toggle="tooltip" data-placement="top" title='<span class="text-muted">Sentiment:</span>&nbsp;{{ number_format($sentiment) }}'>
                                    <span class="label label-{{ $sentimentClass }}">{{ ucwords($sentimentDescription) }}</span>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <a href="{{ route('dominion.trade.hold.sentiments', $hold) }}" class="btn btn-primary btn-xl btn-block">View hold sentiment details</a>
        @endcomponent
    </div>

</div>



<div class="row">
    <div class="col-sm-12 col-md-12">
        @component('partials.dominion.insight.box')

            @slot('title', 'Establish Trade Route')
            @slot('titleIconClass', 'fa fa-solid fa-arrow-right-from-bracket')
            @slot('tableResponsive', false)
            @slot('noPadding', true)

            <div class="box-body">
                @php
                    $hasAvailableTradeRouteSlots = $tradeCalculator->getAvailableTradeRouteSlots($selectedDominion) > 0;
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
                    <input type="hidden" name="hold" value="{{ $hold->id }}">
                    @csrf
                @endif

                <div class="row hold-row">
                    <div class="col-md-6">
                        <table class="table table-hover">
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
                                @foreach(collect($hold->resourceKeys())->sort() as $resourceKey)
                                    @php 
                                        $resource = OpenDominion\Models\Resource::fromKey($resourceKey);
                                        $buyPrice = $hold->buyPrice($resourceKey);
                                        $sellPrice = $hold->sellPrice($resourceKey);
                                    @endphp
                                    <tr>
                                        <td>{{ $resource->name }}</td>
                                        <td>{!! $buyPrice ? number_format($buyPrice, config('trade.price_decimals')) : '&mdash;' !!}</td>
                                        <td>{!! $sellPrice ? number_format(1/$sellPrice, config('trade.price_decimals')) : '&mdash;' !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($selectedDominion->protection_ticks)
                        <div class="col-md-6">
                            <p>You cannot trade while under protection.</p>
                        </div>
                    @elseif($selectedDominion->isLocked() or $selectedDominion->isAbandoned())
                        <div class="col-md-6">
                            <p>You cannot trade.</p>
                        </div>
                    @elseif(!$canTradeWithHold)
                        <div class="col-md-6">
                            <p>You cannot trade with this hold. You do not have any resources the hold is interested in buying.</p>
                        </div>
                    @elseif(!$hasAvailableTradeRouteSlots)
                        <div class="col-md-6">
                            <p>You do not have any available trade route slots.</p>
                        </div>
                    @else
                        <div class="col-md-2">
                            <h5 data-toggle="tooltip" data-placement="top" title="Do not exceed current net production or current stockpile (whichever is highest).">You Sell</h5>
                            <div class="input-group" data-toggle="tooltip" data-placement="top" title="Select the resource to buy. The amount bought is calculated based on the amount you offer to sell.">
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
                            <div class="input-group" data-toggle="tooltip" data-placement="top" title="Select the resource you want to sell and how much you want to sell per tick.">
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
            </div>      

        @endcomponent
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-6">
        @component('partials.dominion.insight.box')

            @slot('title', 'Trade Routes')
            @slot('titleIconClass', 'fa-solid fa-arrow-right-arrow-left')
            @slot('noPadding', true)

            <table class="table">
                <colgroup>
                    <col>
                    <col width="200">
                    <col width="200">
                    <col width="200">
                </colgroup>
                <thead>
                    <tr>
                        <th>Dominion</th>
                        <th>Resource Sold</th>
                        <th>Amount Sold</th>
                        <th>Resource Bought</th>
                        <th class="text-center">Trades</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hold->tradeRoutes->where('status',1) as $tradeRoute)
                        @php
                            $canViewTradeRouteCounterparty = false;
                            $canViewTradeRouteResources = false;
                            $canViewTradeRouteAmount = false;

                            // If selected dominion is in the same realm as the hold, selected dominion can see the resources
                            if($tradeRoute->dominion->realm->id == $selectedDominion->realm->id)
                            {
                                $canViewTradeRouteResources = true;
                                $canViewTradeRouteAmount = true;
                            }

                            // If selected dominion is trading with the hold, selected dominion can see the name of counterparties
                            #if($hold->tradeRoutes->where('dominion_id',$selectedDominion->id)->count())
                            if($tradeRoute->dominion->realm->id == $selectedDominion->realm->id)
                            {
                                $canViewTradeRouteCounterparty = true;
                            }

                        @endphp

                        <tr>
                            @if($canViewTradeRouteCounterparty)
                                <td>
                                    <a href="{{ route('dominion.insight.show', $tradeRoute->dominion) }}">
                                        {{ $tradeRoute->dominion->name }}
                                        (# {{ $tradeRoute->dominion->realm->number }})
                                    </a>
                                </td>
                            @else
                                <td><em>Not disclosed</em></td>
                            @endif

                            @if($canViewTradeRouteResources)
                                <td>{{ $tradeRoute->soldResource->name }}</td>
                                <td>{{ number_format($tradeRoute->source_amount) }}</td>
                                <td>{{ $tradeRoute->boughtResource->name }}</td>
                                <td class="text-center">{{ number_format($tradeRoute->trades) }}</td>
                            @else
                                <td><em>Not disclosed</em></td>
                                <td><em>Not disclosed</em></td>
                                <td class="text-center"><em>Not disclosed</em></td>
                            @endif
                        </tr>

                    @endforeach
                </tbody>
            </table>
            <a href="{{ route('dominion.trade.hold.ledger', $hold) }}" class="btn btn-primary btn-xl btn-block">View hold trade ledger</a>
        @endcomponent
    </div>
    
    <div class="col-sm-12 col-md-6">
        @component('partials.dominion.insight.box')

            @slot('title', 'Resources')
            @slot('titleIconClass', 'ra ra-mining-diamonds ra-fw')
            @slot('noPadding', true)


            <table class="table">
                <colgroup>
                    <col width="200">
                    <col>
                    <col width="200">
                    <col width="200">
                    <col width="200">
                </colgroup>
                <thead>
                    <tr>
                        <th>Resource</th>
                        <th>Supply</th>
                        <th>Production</th>
                        <th>Current Buy Price</th>
                        <th>Current Sell Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hold->resourceKeys() as $resourceKey)
                        @php
                            $resource = \OpenDominion\Models\Resource::fromKey($resourceKey);
                        @endphp
                        <tr>
                            <td>{{ $resource->name }}</td>
                            <td>{{ number_format($hold->{'resource_' . $resourceKey}) }}</td>
                            <td>{{ number_format($resourceCalculator->getProduction($hold, $resourceKey)) }}</td>
                            <td>{!! $hold->buyPrice($resourceKey) ? number_format($hold->buyPrice($resourceKey), config('trade.price_decimals')) : '&mdash;' !!}</td>
                            <td>{!! $hold->sellPrice($resourceKey) ? number_format(1/$hold->sellPrice($resourceKey), config('trade.price_decimals')) : '&mdash;' !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endcomponent
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-6">
        @component('partials.dominion.insight.box')

            @slot('title', 'Buildings')
            @slot('titleIconClass', 'fa fa-home fa-fw')
            @slot('noPadding', true)

            <table class="table">
                <colgroup>
                    <col>
                    <col width="200">
                </colgroup>
                <thead>
                    <tr>
                        <th>Building</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hold->buildings as $holdBuilding)
                        <tr>
                            <td>{{ $holdBuilding->building->name }}</td>
                            <td>{{ number_format($holdBuilding->amount) }} <span class="text-muted"></span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endcomponent
    </div>
    
    <div class="col-sm-12 col-md-6">
        @component('partials.dominion.insight.box')

            @slot('title', 'Units')
            @slot('titleIconClass', 'fa fa-solid fa-people-group')
            @slot('noPadding', true)

            <table class="table">
                <colgroup>
                    <col>
                    <col width="200">
                    <col width="200">
                </colgroup>
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hold->units as $holdUnit)
                        <tr>
                            <td>
                                <strong>{{ $holdUnit->unit->name }}</strong><br>
                                <small class="text-muted">Faction:</small> <a href="{{ route('scribes.faction', Str::slug($holdUnit->unit->race->name),'_') }}#units" target="_blank">{{ $holdUnit->unit->race->name }}</a>
                            </td>
                            <td>{{ number_format($holdUnit->amount) }}</td>
                            <td>{{ ucwords($unitHelper->getUnitStateDescription($holdUnit->state)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endcomponent
    </div>
</div>

@endif

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