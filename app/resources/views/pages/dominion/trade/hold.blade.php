@extends('layouts.master')
@section('title', "Hold | $hold->name")

@section('content')

@if(!$hold->round->hasStarted() or $hold->status == 0)
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
                                <td>{{ $hold->land }}</td>
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
                        <th>Resource Bought</th>
                        <th>Resource Sold</th>
                        <th class="text-center">Trades</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hold->tradeRoutes->where('status',1) as $tradeRoute)
                        @php
                            $canViewTradeRouteCounterparty = false;
                            $canViewTradeRouteResources = false;

                            // If selected dominion is in the same realm as the hold, selected dominion can see the resources
                            if($tradeRoute->dominion->realm->id == $selectedDominion->realm->id)
                            {
                                $canViewTradeRouteResources = true;
                            }

                            // If selected dominion is trading with the hold, selected dominion can see the name of counterparties
                            if($hold->tradeRoutes->where('dominion_id',$selectedDominion->id)->count())
                            {
                                $canViewTradeRouteCounterparty = true;
                            }

                        @endphp

                        <tr>
                            @if($canViewTradeRouteCounterparty)
                                <td>
                                        {{ $tradeRoute->dominion->name }}
                                        (# {{ $tradeRoute->dominion->realm->number }})
                                </td>
                            @else
                                <td><em>Not disclosed</em></td>
                            @endif

                            @if($canViewTradeRouteResources)
                                <td>{{ $tradeRoute->bought_resource }}</td>
                                <td>{{ $tradeRoute->sold_resource }}</td>
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
                        <th>Current Sell Price</th>
                        <th>Current Buy Price</th>
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
                            <td>{!! $hold->sellPrice($resourceKey) ? number_format($hold->sellPrice($resourceKey), config('trade.price_decimals')) : '&mdash;' !!}</td>
                            <td>{!! $hold->buyPrice($resourceKey) ? number_format($hold->buyPrice($resourceKey), config('trade.price_decimals')) : '&mdash;' !!}</td>
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
