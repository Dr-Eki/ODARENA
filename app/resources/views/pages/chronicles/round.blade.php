@extends('layouts.topnav')
@section('title', "Chronicles | Round {$round->number}")

@section('content')

@include('partials.chronicles.top-row')

<div class="row">
    <!-- PODIUM -->
    <div class="col-md-6 text-center">
        <div class="box box-success">
            <div class="box-header with-border">
                <h1 class="box-title"><i class="ra ra-podium ra-fw"></i> Rankings</h1>
            </div>
            <div class="box-body">
                <table class="table table-striped table-hover">
                    <colgroup>
                        <col width="2em">
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th></th>
                            <th>Dominion</th>
                            <th>Ruler</th>
                            <th>Faction</th>
                            <th>Land</th>
                    </thead>
                    <tbody>
                    @foreach($topLargestDominions as $key => $dominionId)
                        @php
                            $dominion = OpenDominion\Models\Dominion::findOrFail($dominionId);
                        @endphp
                        <tr>
                            <td>{{ $roundHelper->getRoundPlacementEmoji($key) }}</td>
                            <td class="text-left"><a href="{{ route('chronicles.dominion', $dominionId) }}">{{ $dominion->name }}</a></td>
                            <td class="text-left"><a href="{{ route('chronicles.ruler', $dominion->user->display_name) }}">{{ $dominion->user->display_name }}</a></td>
                            <td class="text-left">{{ $dominion->race->name }}</td>
                            <td class="text-left">{{ number_format($dominion->land) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="box-footer">
                <a href="{{ route('chronicles.round.rankings', $round) }}">See all Rankings</a>
            </div>
        </div>
    </div>
    <!-- /PODIUM -->

    <!-- TOP STATS -->
    <div class="col-md-6 text-center">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h1 class="box-title"><i class="fas fa-medal"></i> Deeds of Glory</h1>
            </div>
            <div class="box-body">
                <table class="table table-striped table-hover">
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col width="20%">
                        <col width="20%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Deed</th>
                            <th></th>
                            <th>Dominion</th>
                            <th>Ruler</th>
                            <th>Faction</th>
                    </thead>
                    <tbody>
                    @foreach($gloryStats as $statKey)
                        @php
                            $result = $statsHelper->getTopDominionForRoundForStat($round, $statKey);
                            $dominion = OpenDominion\Models\Dominion::findOrFail(key($result));
                            $value = $result[key($result)];
                        @endphp
                        <tr>
                            <td class="text-left">{{ $statsHelper->getStatName($statKey) }}:</td>
                            <td class="text-left">{{ number_format($value) }}</td>
                            <td class="text-left"><a href="{{ route('chronicles.dominion', $dominion->id) }}">{{ $dominion->name }}</a></td>
                            <td class="text-left">
                                @if($dominion->isAbandoned())
                                    <em><span class="text-muted">{{ $dominion->ruler_name }}</span></em>
                                @else
                                    <a href="{{ route('chronicles.ruler', $dominion->user->display_name) }}">{{ $dominion->user->display_name }}</a>
                                @endif
                            </td>
                            <td class="text-left">{{ $dominion->race->name }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- /TOP STATS -->
</div>

<div class="row">

      <!-- ALL STATS -->
      <div class="col-md-9 text-center">
          <div class="box">
              <div class="box-header with-border">
                  <h1 class="box-title"><i class="fas fa-book-open"></i> All Stats</h1>
              </div>
              <div class="box-body">
                  <table class="table table-striped table-hover" id="stats-table">
                      <colgroup>
                          <col>
                          <col>
                          <col>
                      </colgroup>
                      <thead>
                          <tr>
                              <th>Stat</th>
                              <th>Value</th>
                              <th>Top Dominion</th>
                          </tr>
                      </thead>
                      <tbody>
                      @foreach($allDominionStatKeysForRound as $statKey)
                          @php
                              $result = $statsHelper->getTopDominionForRoundForStat($round, $statKey);
                              $dominion = OpenDominion\Models\Dominion::findOrFail(key($result));
                              $value = $result[key($result)];
                          @endphp
                          <tr>
                              <td class="text-left"><a href="{{ route('chronicles.round.stat', [$round, $statKey]) }}">{{ $statsHelper->getStatName($statKey) }}</a>:</td>
                              <td class="text-left">{{ number_format($value) }}</td>
                              <td class="text-left"><a href="{{ route('chronicles.dominion', $dominion->id) }}">{{ $dominion->name }}</a></td>
                          </tr>
                      @endforeach
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
      <!-- /ALL STATS -->

    <!-- ROUND OVERVIEW -->
    <div class="col-md-3 text-center">
        <div class="box box-info">
            <div class="box-header with-border">
                <h1 class="box-title"><i class="fas fa-book fa-fw"></i> Round Overview</h1>
            </div>
            <div class="box-body">
                <table class="table table-striped table-hover">
                    <colgroup>
                        <col>
                        <col>
                    </colgroup>
                    <tbody>
                        <tr>
                            <td>Mode:</td>
                            <td>
                                <span data-toggle="tooltip" data-placement="top" title="{{ $roundHelper->getRoundModeDescription($round) }}">
                                    {!! $roundHelper->getRoundModeIcon($round) !!} {{ $roundHelper->getRoundModeString($round) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Target:</td>
                            <td>{{ number_format($round->goal) }} {{ $roundHelper->getRoundModeGoalString($round) }}</td>
                        </tr>
                        <tr>
                            <td>Ticks:</td>
                            <td>{{ number_format($round->ticks) }}</td>
                        </tr>
                        <tr>
                            <td>Start:</td>
                            <td>{{ $round->start_date }}</td>
                        </tr>
                        <tr>
                            <td>Dominions:</td>
                            <td>{{ number_format($round->activeDominions()->count()) }}</td>
                        </tr>
                    </tbody>
                </table>
                    <table class="table table-striped table-hover">
                        <colgroup>
                            <col width="3em">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-center">Day</th>
                                <th class="text-center">
                                    <span data-toggle="tooltip" data-placement="top" title="Highest offensive power sent in a single invasion on a particular day">
                                        Top OP
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statsHelper->getTopOpStatsForRound($round) as $key => $data)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>{{ number_format($data->value) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
            </div>
        </div>
    </div>
    <!-- /ROUND OVERVIEW -->
</div>


@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
    <style>
        #stats-table #stats-table-table_filter { display: none !important; }
    </style>
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var table = $('#stats-table').DataTable({
                order: [0, 'asc'],
                paging: false,
            });
        })(jQuery);
    </script>
@endpush
