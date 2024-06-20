@extends('layouts.master')
@section('title', 'Land')

@section('content')

<div class="row">
    <div class="col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-map fa-fw"></i> Land</h3>
            </div>           
            <div class="box-body table-responsive no-padding">
                <table class="table">
                    <colgroup>
                        <col width="100">
                        @for ($i = 1; $i <= 12; $i++)
                            <col>
                        @endfor
                        <col width="100">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-center">Land</th>
                            @for ($i = 1; $i <= 12; $i++)
                                <th class="text-center">{{ $i }}</th>
                            @endfor
                            <th class="text-center">Total<br>Incoming</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">
                                {{ number_format($selectedDominion->land) }}
                            </td>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $landIncoming = (
                                        $queueService->getInvasionQueueAmount($selectedDominion, 'land', $i) +
                                        $queueService->getExpeditionQueueAmount($selectedDominion, 'land', $i) +
                                        $queueService->getRezoningQueueAmount($selectedDominion, 'land', $i)
                                    );
                                @endphp
                                <td class="text-center">
                                    @if ($landIncoming)
                                        {{ number_format($landIncoming) }}
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                            @endfor
                            <td class="text-center">
                                <span data-toggle="tooltip" data-placement="top" title="<small class='text-muted'>Paid:</small> {{  number_format($selectedDominion->land + $queueService->getInvasionQueueTotalByResource($selectedDominion, 'land') + $queueService->getExpeditionQueueTotalByResource($selectedDominion, 'land')) }}">
                                    {{ number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, 'land') + $queueService->getExpeditionQueueTotalByResource($selectedDominion, 'land')) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        @php
            $canClaim = (!$selectedDominion->isLocked() and !$selectedDominion->daily_land and $selectedDominion->protection_ticks === 0 and $selectedDominion->round->hasStarted());
        @endphp
        <div class="box {{ $canClaim ? 'box-warning' : null }}">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-plus"></i> Daily Bonus</h3>
            </div>
            <div class="box-body">
                <p>The Daily Land Bonus instantly gives you some land with <strong>{{ $selectedDominion->race->homeTerrain()->name }}</strong> terrain.</p>
                <p>You have a 0.50% chance to get 100 acres, and a 99.50% chance to get a random amount between 10 and 40 acres.</p>
                @if ($selectedDominion->protection_ticks > 0 or !$selectedDominion->round->hasStarted())
                    <p><strong>You cannot claim daily bonus while you are in protection or before the round has started.</strong></p>
                @endif
                <form action="{{ route('dominion.land.daily-bonus') }}" method="post" role="form">
                    @csrf
                    <input type="hidden" name="action" value="daily_land">
                    <button type="submit" name="land" class="btn btn-primary btn-block btn-lg" {{ !$canClaim ? 'disabled' : null }}>
                        Claim Daily Land Bonus
                    </button>
                </form>
            </div>
        </div>
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
