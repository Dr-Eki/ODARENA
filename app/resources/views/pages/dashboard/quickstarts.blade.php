@extends('layouts.master')

@section('title', 'Dashboard | Quickstarts')

@section('content')
<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Quickstarts</h3>
            </div>
            <div class="box-body">

                <div class="row">
                    <div class="col-sm-9 col-md-9">
                        <p>You have submitted {{ $user->quickstarts->count() }} quickstarts.</p>
                    </div>
                    <div class="col-sm-3 col-md-3">
                        <div class="row">
                            <div class="col-sm-6 col-md-6">
                                <a href="{{ route('dashboard.quickstarts.import') }}" class="btn btn-info btn-block">Import</a>
                            </div>
                            <div class="col-sm-6 col-md-6">
                                <a href="{{ route('dashboard.quickstarts.export') }}" class="btn btn-warning btn-block">Export</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="box box-primary"> <!-- table-responsive -->
            <table class="table table-striped table-hover">
                <colgroup>
                    <col width="50">
                    <col width="200">
                    <col width="150">
                    <col width="150">
                    <col width="150">
                    <col width="150">
                    <col width="150">
                    <col>
                    <col width="100">
                    <col width="150">
                    <col width="200">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">ID</th>
                        <th>Name</th>
                        <th>Faction</th>
                        <th>Title</th>
                        <th>Deity</th>
                        <th>OP</th>
                        <th>DP</th>
                        <th>Description</th>
                        <th>Date Added</th>
                        <th class="text-center">Availability</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($user->quickstarts as $quickstart)
                    <tr>
                        <td class="text-center">{{ $quickstart->id }}</td>
                        <td><a href="{{ route('scribes.quickstart', $quickstart) }}">{{ $quickstart->name }}</a></td>
                        <td>{{ $quickstart->race->name }}</td>
                        <td>{{ $quickstart->title->name }}</td>
                        <td>{{ $quickstart->deity ? $quickstart->deity->name : 'None'  }}</td>
                        <td>{{ number_format($quickstart->offensive_power) }}</td>
                        <td>{{ number_format($quickstart->defensive_power) }}</td>
                        <td>{{ $quickstart->description }}</td>
                        <td>{{ $quickstart->created_at }}</td>
                        <td class="text-center">
                            <form action="{{ route('dashboard.quickstarts.toggle-availability', $quickstart) }}" method="post">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-{{ $quickstart->is_public ? 'danger' : 'success' }}">
                                    {{ $quickstart->is_public ? 'Make Private' : 'Make Public' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="row">
                                <div class="col-sm-6 col-md-6">
                                    <a href="{{ route('dashboard.quickstarts.edit', $quickstart) }}" class="btn btn-xs btn-primary btn-block">Edit</a>
                                </div>
                                <div class="col-sm-6 col-md-6">
                                    <form action="{{ route('dashboard.quickstarts.delete', $quickstart) }}" method="post" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-danger btn-block">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>

@endsection


@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var table = $('#dominions-table').DataTable({
                order: [0, 'desc'],
                paging: false,
            });
        })(jQuery);
    </script>
@endpush
