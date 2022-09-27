@extends('layouts.topnav')
@section('title', "Chronicles | Factions")

@section('content')

@include('partials.chronicles.top-row')

<div class="row">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fas fa-flag fa-fw"></i> Factions</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped" id="factions-table">
                <colgroup>
                    <col>
                    <col width="200">
                </colgroup>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Dominions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($races as $race)
                        <tr>
                            <td><a href="{{ route('chronicles.faction', $race->name) }}">{{ $race->name }}</a></td>
                            <td>{{ number_format($raceHelper->getRaceDominions($race, false, $chroniclesHelper->getMaxRoundNumber())->count()) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
    <style>
        #factions-search #factions-table_filter { display: none !important; }
    </style>
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var table = $('#factions-table').DataTable({
                order: [0, 'asc'],
                paging: false,
            });
        })(jQuery);
    </script>
@endpush
