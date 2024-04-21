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
            @slot('titleIconClass', 'fa-solid fa-dungeon')
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
                                <td>{{ Str::plural($raceHelper->getPeasantsTerm($hold->race)) }}:</td>
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
                        <th>Bought</th>
                        <th>Sold</th>
                        <th class="text-center">Trades</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hold->tradeRoutes as $tradeRoute)
                        ...
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
                </colgroup>
                <thead>
                    <tr>
                        <th>Resource</th>
                        <th>Storage</th>
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
                            <td>{{ number_format($hold->sellPrice($resourceKey), 4) }}</td>
                            <td>{{ number_format($hold->buyPrice($resourceKey), 4) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endcomponent
    </div>


</div>

@endif

@endsection

@push('inline-scripts')
    <script type="text/javascript">
        document.querySelector("#words").onclick = function () {
        document.querySelector("#text_copy").select();
        document.execCommand("copy");
        };

        document.querySelector("#input-btn").onclick = function () {
        document.querySelector("#input").select();
        document.execCommand("copy");
        };

    </script>
@endpush