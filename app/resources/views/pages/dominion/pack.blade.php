@extends('layouts.master')
@section('title', 'Notes')

@section('content')
<div class="row">

    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                
            <h3 class="box-title"><i class="ra ra-double-team ra-fw"></i>{{ $selectedDominion->pack->user->display_name }}{{ $selectedDominion->pack->user->display_name[strlen($selectedDominion->pack->user->display_name) - 1] == 's' ? "'" : "'s" }} Pack</h3>
            </div>
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
                                <td>{{ ($packDominion->user_id == $packDominion->pack->user->id) ? 'Leader' : 'Member' }}</td>
                                <td>{{ $packDominion->race->name }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="box-footer">
                    <p>Pack password: <code>{{ $selectedDominion->pack->password }}</code></p>

                    @if ($packService->canEditPack($selectedDominion->user, $selectedDominion->pack))
                        <p><strong>Pack status:</strong></p>
                        <form action="{{ route('dominion.pack.change-status') }}" method="post">
                            @csrf
                            <input type="hidden" name="pack_id" value="{{ $selectedDominion->pack->id }}">
                            <input type="radio" required id="private" name="status" value="0" {{ $selectedDominion->pack->status === 0 ? 'checked' : null }}>&nbsp;<label for="private">Private</label>: password required to join.<br>
                            <input type="radio" required id="public" name="status" value="1" {{ $selectedDominion->pack->status === 1 ? 'checked' : null }}>&nbsp;<label for="public">Public</label>: no password required.<br>
                            <input type="radio" required id="closed" name="status" value="2" {{ $selectedDominion->pack->status === 2 ? 'checked' : null }}>&nbsp;<label for="closed">Closed</label>: no new members accepted.<br>
                            <button type="submit" class="btn btn-primary">Change Status</button>
                        </form>
                    @endif
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
