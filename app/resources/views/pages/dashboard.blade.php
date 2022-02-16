@extends('layouts.master')

@section('page-header', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-lg-9">
            <div class="box box-primary table-responsive" id="dominion-search">
                <table class="table table-striped table-hover" id="dominions-table">
                    <colgroup>
                        <col width="60">
                        <col>
                        <col width="180">
                        <col width="150">
                        <col width="120">
                        <col width="120">
                        <col width="120">
                        <col width="120">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-center">Round</th>
                            <th>Dominion</th>
                            <th>Status</th>
                            <th>Mode</th>
                            <th>Faction</th>
                            <th>Land</th>
                            <th>Networth</th>
                            <th>Chapter</th>
                            <th>Era</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($rounds->all() as $round)
                        @if($roundService->hasUserDominionInRound($round))
                            @php
                                $dominion = $roundService->getUserDominionFromRound($round);
                            @endphp
                        @else
                            @php
                                $dominion = null;
                            @endphp
                        @endif
                        <tr>
                            <td class="text-center">{{ $round->number }}</td>
                            <td>
                                @if(isset($dominion))
                                    @if ($dominion->isSelectedByAuthUser())
                                        <a href="{{ route('dominion.status') }}">{{ $dominion->name }}</a>&nbsp;<span class="label label-success">Selected</span>
                                    @else
                                        <form action="{{ route('dominion.select', $dominion) }}" method="post">
                                            @csrf
                                            <button type="submit" class="btn btn-link" style="padding: 0;">{{ $dominion->name }}</button>
                                        </form>
                                    @endif

                                    @if($dominion->round->hasStarted() and !$dominion->isLocked())
                                    <div class="box box-danger" style="margin-top: 1em;">
                                        <div class="box-header with-border">
                                            <strong><i class="fas fa-user-slash"></i> Abandon dominion</strong>
                                        </div>
                                        <div class="col-sm-12 col-md-12">
                                            <p><small>
                                                If you wish to abandon your dominion, confirm below and click the button. <em>This action cannot be undone.</em><br>
                                                An abandoned dominion stays in the game, but has no further production. You can create a new dominion afterwards.
                                            </small></p>
                                        </div>
                                        <form action="{{ route('dominion.abandon', $dominion) }}" method="post">
                                            @csrf
                                            <div class="col-sm-12 col-md-4">
                                                <label>
                                                    <input type="checkbox" name="remember" required class="text-muted"> Confirm abandon
                                                </label>
                                            </div>
                                            <div class="col-sm-12 col-md-8">
                                                <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-user-slash"></i> Abandon dominion</button>
                                            </div>
                                        </form>
                                        </div>
                                    </div>
                                    @endif

                                @elseif(!$round->hasEnded())
                                    <span data-toggle="tooltip" data-placement="top" title="The battlefield awaits!">
                                        <a href="{{ route('round.register', $round) }}" class="btn btn-success btn-round btn-block"><i class="fas fa-sign-in-alt"></i> Register for Round {{ $round->number }}</a>
                                    </span>
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td>
                                @if($roundService->hasUserDominionInRound($round))

                                    @if($round->hasEnded())
                                        <span data-toggle="tooltip" data-placement="top" title="Start: {{ $round->start_date }}.<br>Registration: {{ $dominion->created_at }}.<br>Ended: {{ $round->end_date }}.">
                                            <span class="label label-info">Finished</span>
                                        </span>
                                    @endif

                                    @if($dominion->is_locked)
                                        <span data-toggle="tooltip" data-placement="top" title="This dominion was locked.">
                                            <span class="label label-warning">Locked</span>
                                        </span>
                                    @endif

                                    @if($dominion->isAbandoned())
                                        <span data-toggle="tooltip" data-placement="top" title="This dominion was abandoned.">
                                            <span class="label label-warning">Abandoned</span>
                                        </span>

                                    @endif

                                    @if(!$round->hasEnded() and !$dominion->isLocked())
                                        <span data-toggle="tooltip" data-placement="top" title="Start: {{ $round->start_date }}.<br>Current tick: {{ number_format($round->ticks) }}.<br>You joined: {{ $dominion->created_at }}.">
                                            <span class="label label-success">Playing</span>
                                        </span>
                                    @endif

                                @else
                                    @if($round->hasEnded() and $user->created_at <= $round->start_date)
                                        <span data-toggle="tooltip" data-placement="top" title="Start: {{ $round->start_date }}.<br>Ended: {{ $round->end_date }}.">
                                            <span class="label label-primary">Ended</span>
                                        </span>
                                    @elseif($round->hasEnded() and $user->created_at > $round->start_date)
                                        <span data-toggle="tooltip" data-placement="top" title="Start: {{ $round->start_date }}.<br>Ended: {{ $round->end_date }}.<br>User registration date: {{ $user->created_at }}.">
                                            <span class="label label-primary">User was not registered</span>
                                        </span>
                                    @elseif(!$round->hasEnded())
                                        <p>
                                        @if(!$round->hasStarted())
                                            <span data-toggle="tooltip" data-placement="top" title="Start: {{ $round->start_date }}.">
                                                <span class="label label-danger">Starting Soon</span>
                                            </span><br>
                                            @if($round->mode == 'standard' or $round->mode == 'deathmatch')
                                                <small class="text-muted">The round starts at {{ $round->start_date }}.<br>The target land size is {{ number_format($round->goal) }} acres.</small>
                                            @elseif($round->mode == 'standard-duration' or $round->mode == 'deathmatch-duration')
                                                <small class="text-muted">The round starts at {{ $round->start_date }}.<br>Round lasts for {{ number_format($round->goal) }} ticks.</small>
                                            @endif
                                        @else
                                            <span data-toggle="tooltip" data-placement="top" title="Start: {{ $round->start_date }}.">
                                                <span class="label label-warning">Active</span>
                                            </span><br>
                                            <small class="text-muted">The round started at {{ $round->start_date }}.<br>Current tick: {{ number_format($round->ticks) }}.</small>
                                        @endif

                                        @if($round->hasCountdown())
                                            <small class="text-muted">
                                            <br>The round ends in <strong>{{ number_format($round->ticksUntilEnd()) . ' ' . str_plural('tick', $round->ticksUntilEnd()) }}</strong>.
                                            </small>
                                        @endif
                                        </p>
                                    @endif
                                @endif
                            </td>

                            <td>
                                @if($round->number >= 62 or request()->getHost() == 'odarena.local')
                                <span data-toggle="tooltip" data-placement="top" title="{{ $roundHelper->getRoundModeDescription($round) }}">
                                    {!! $roundHelper->getRoundModeIcon($round) !!} {{ $roundHelper->getRoundModeString($round) }}
                                </span>
                                @else
                                    <span data-toggle="tooltip" data-placement="top" title="Game modes were introduced in Round 62.">
                                        &mdash;
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if($dominion)
                                    <a href="{{ route('scribes.faction', str_slug($dominion->race->name)) }}" target="_blank"><i class="ra ra-scroll-unfurled"></i></a> {{ $dominion->race->name }}
                                @else
                                    &mdash;
                                @endif
                            </td>

                            <td>
                                @if($dominion)
                                    {{ number_format($landCalculator->getTotalLand($dominion)) }}
                                @else
                                    &mdash;
                                @endif
                            </td>

                            <td>
                                @if($dominion)
                                    {{ number_format($networthCalculator->getDominionNetworth($dominion)) }}
                                @else
                                    &mdash;
                                @endif
                            </td>


                            <td>{{ $round->name }}</td>

                            <td>{{ $round->league->description }}</td>


                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Welcome {{ $dominions->isEmpty() ? '' : 'back' }} to ODARENA, <strong>{{ Auth::user()->display_name }}</strong>!</p>
                    <p>You have been playing since {{ $user->created_at }} and have participated in {{ number_format($dominions->count()) }} of {{ number_format($rounds->where('end_date', '>=', $user->created_at)->count()) }} finished rounds since joining.</p>
                </div>
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
                paging: false,
            });
        })(jQuery);
    </script>
@endpush
