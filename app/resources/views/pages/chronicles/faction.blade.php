@extends('layouts.topnav')
@section('title', "Chronicles | Faction | {$race->name}")

@section('content')

@include('partials.chronicles.top-row')

<div class="row">
    <div class="box box-{{ $chroniclesHelper->getAlignmentBoxClass($race->alignment) }}">
        <div class="box-header text-center">
            <h1 class="box-title" style="font-size: 200%; font-variant: small-caps;"> {{ $race->name }}</h1>
        </div>
        <div class="box-body">
            <div class="col-sm-4 text-center">
                <h4>Land max</h4>
                <h3>{{ number_format($raceHelper->getMaxLandForRace($race)) }}</h3>
            </div>

            <div class="col-sm-4 text-center">
                <h4>Land average</h4>
                <h3>{{ number_format($raceHelper->getTotalLandForRace($race) / max(1, $raceHelper->getDominionCountForRace($race, false, $chroniclesHelper->getMaxRoundNumber()))) }}</h3>
            </div>

            <div class="col-sm-4 text-center">
                <h4>Unique rulers</h4>
                <h3>{{ number_format($raceHelper->getUniqueRulersCountForRace($race)) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="box">
        <div class="box-header with-border">
            <h1 class="box-title">Overview of the 20 Most Recent Rounds</h1>
        </div>
        <div class="box-body">
            <div class="col-sm-4">
                <div class="box-header with-border">
                    <h4 class="box-title"><i class="ra ra-sword ra-fw"></i> Military Accomplishments</h4>
                </div>

                <table class="table table-striped table-hover">
                    <colgroup>
                        <col width="50%">
                        <col>
                    </colgroup>
                    <tbody>
                    <tr>
                    @foreach ($militarySuccessStats as $statKey)
                        <tr>
                            <td class="text-right">{{ $statsHelper->getStatName($statKey) }}:</td>
                            <td>{{ number_format($raceHelper->getStatSumForRace($race, $statKey)) }}</td>
                        </tr>
                    @endforeach
                    </tr>
                </table>
            </div>

            <div class="col-sm-4">
                <div class="box-header with-border">
                    <h4 class="box-title"><i class="ra ra-broken-skull ra-fw"></i> Military Failures</h4>
                </div>

                <table class="table table-striped table-hover">
                    <colgroup>
                        <col width="50%">
                        <col>
                    </colgroup>
                    <tbody>
                    <tr>
                    @foreach ($militaryFailureStats as $statKey)
                        <tr>
                            <td class="text-right">{{ $statsHelper->getStatName($statKey) }}:</td>
                            <td>{{ number_format($raceHelper->getStatSumForRace($race, $statKey)) }}</td>
                        </tr>
                    @endforeach
                    </tr>
                </table>
            </div>

            <div class="col-sm-4">
                <div class="box-header with-border">
                    <h4 class="box-title"><i class="ra ra-crossed-swords ra-fw"></i> Units</h4>
                </div>

                <table class="table table-striped table-hover">
                    <colgroup>
                        <col>
                        <col width="100">
                        <col width="100">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Trained</th>
                            <th>Casualties</th>
                    </thead>
                    <tbody>
                    @for ($slot = 1; $slot <= $race->units->count(); $slot++)
                        @php
                            $unitType = 'unit'.$slot;
                        @endphp
                        <tr>
                            <td>{{ $unitHelper->getUnitName($unitType, $race) }}</td>
                            <td>{{ number_format($raceHelper->getStatSumForRace($race, ('unit' . $slot .'_trained'))) }}</td>
                            <td>{{ number_format($raceHelper->getStatSumForRace($race, ('unit' . $slot .'_lost'))) }}</td>
                        </tr>
                    @endfor
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="box">
        <div class="box-header with-border">
            <h1 class="box-title">All Dominions</h1>
        </div>
        <div class="box-body">
            <table class="table table-striped table-hover" id="dominions-table">
                <colgroup>
                    <col width="60">
                    <col>
                    <col width="180">
                    <col width="150">
                    <col width="120">
                    <col width="120">
                    <col width="120">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">Round</th>
                        <th>Dominion</th>
                        <th>Ruler</th>
                        <th>Land</th>
                        <th>Networth</th>
                        <th>Chapter</th>
                        <th>Era</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($dominions as $dominion)
                    <tr>
                        <td class="text-center"><a href="{{ route('chronicles.round', $dominion->round) }}">{{ $dominion->round->number }}</a></td>
                        <td><a href="{{ route('chronicles.dominion', $dominion) }}">{{ $dominion->name }}</a></td>
                        <td>
                            @if($dominion->isAbandoned())
                                {{ $dominion->ruler_name }}
                            @else
                                <a href="{{ route('chronicles.ruler', $dominion->user->display_name) }}">{{ $dominion->user->display_name }}</a>
                            @endif
                        </td>
                        <td>{{ number_format($dominion->land) }}</td>
                        <td>{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}</td>
                        <td><a href="{{ route('chronicles.round', $dominion->round) }}">{{ $dominion->round->name }}</a></td>
                        <td>{{ $dominion->round->league->description }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>

@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
    <style>
        #dominion-search #dominions-table_filter { display: none !important; }
    </style>
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var table = $('#dominions-table').DataTable({
                order: [0, 'desc'],
                paging: true,
            });
        })(jQuery);
    </script>
@endpush
