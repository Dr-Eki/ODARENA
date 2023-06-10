@extends('layouts.master')
@section('title', 'Notes')

@section('content')
<div class="row">

    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                
            <h3 class="box-title"><i class="ra ra-double-team ra-fw"></i>{{ $selectedDominion->pack->user->display_name }}{{ $selectedDominion->pack->user->display_name[strlen($selectedDominion->pack->user->display_name) - 1] == 's' ? "'" : "'s" }} Pack</h3>
            </div>
                <form action="{{ route('dominion.pack') }}" method="post" role="form">
                @csrf
                <div class="box-body">
                    <table class="table">
                        <thead>
                            <th>Dominion</th>
                            <th>Player</th>
                            <th>Role</th>
                            <th>Faction</th>
                        </thead>
                        <tbody>
                        @foreach($selectedDominion->pack->dominions as $packDominion)
                            <tr>
                                <td>{{ $packDominion->name }}</td>
                                <td>{{ $packDominion->user->display_name }}</td>
                                <td>{{ ($packDominion->user_id == $selectedDominion->user->id) ? 'Leader' : 'Member' }}</td>
                                <td>{{ $packDominion->race->name }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="box-footer">
                    <p>Pack password: <code>{{ $selectedDominion->pack->password }}</code></p>
                </div>
            </form>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Pack</h3>
            </div>
            <div class="box-body">
                <p>See details about your pack here.</p>
            </div>
        </div>

        @include('partials.dominion.watched-dominions')
    </div>

</div>
@endsection


@push('page-scripts')
    <script type="text/javascript">
    $("form").submit(function () {
        // prevent duplicate form submissions
        $(this).find(":submit").attr('disabled', 'disabled');
    });
    </script>
@endpush
