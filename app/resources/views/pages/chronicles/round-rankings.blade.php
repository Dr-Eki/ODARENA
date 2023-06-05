@extends('layouts.topnav')
@section('title', "Chronicles | Round {$round->number} Rankings")

@section('content')
<div class="box box-primary">
    <div class="box-header with-border text-center">
        <h3 class="box-title">Round {{ $round->number }} Rankings</h3>
        <span class="pull-left"><a href="{{ route('chronicles.round', $round) }}">Go back to Round</a></span>
    </div>

    <div class="box box-body">
        <table class="table table-striped table-hover" id="dominions-table">
            <colgroup>
                <col>
                <col>
            </colgroup>
            <thead>
                <tr>
                    <th>Dominion</th>
                    <th>Realm</th>
                    <th>Ruler</th>
                    <th>Faction</th>
                    <th>Land</th>
                    <th>Networth</th>
                </tr>
            </thead>
            <tbody>
              @foreach($allDominions as $dominion)
                  <tr>
                      <td class="text-left"><a href="{{ route('chronicles.dominion', $dominion->id) }}">{{ $dominion->name }}</a></td>
                      <td class="text-left"># {{ $dominion->realm->number }}</td>
                      <td class="text-left">
                          @if($dominion->isAbandoned())
                              {{ $dominion->ruler_name }}
                          @else
                              <a href="{{ route('chronicles.ruler', $dominion->user->display_name) }}">{{ $dominion->user->display_name }}</a>
                          @endif
                      </td>
                      <td class="text-left">{{ $dominion->race->name }}</td>
                      <td class="text-left">{{ number_format($dominion->land) }}</td>
                      <td class="text-left">{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}</td>
                  </tr>
              @endforeach
            </tbody>
        </table>
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
                order: [4, 'desc'],
                paging: true,
                pageLength: 100
            });
        })(jQuery);
    </script>
@endpush
