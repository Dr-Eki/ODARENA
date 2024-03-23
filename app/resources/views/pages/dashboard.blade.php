@extends('layouts.master')

@section('title', 'Dashboard')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-12">
            <div class="box">
                <div class="box-body">
                    <h4>Welcome {{ $dominions->isEmpty() ? '' : 'back' }} to ODARENA, <strong>{{ Auth::user()->display_name }}</strong></h4> 
                    <p>You have been playing since {{ $user->created_at }} and have participated in {{ number_format($dominions->count()) }} of {{ number_format($rounds->where('end_date', '>=', $user->created_at)->count()) }} completed rounds since joining. <a href="{{ route('chronicles.ruler', $user->display_name) }}">View the Chronicles of {{ $user->display_name }}</a> for your full stats.</p>



                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="box box-primary table-responsive" id="dominion-search">
                <table class="table table-striped table-hover" id="dominions-table">
                    <colgroup>
                        <col width="60">
                        <col>
                        <col width="200">
                        <col width="200">
                        <col width="200">
                        <col width="200">
                        <col width="200">
                        <col width="200">
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

                                    @if($dominion->round->hasStarted() and !$dominion->isLocked() and !$dominion->isAbandoned())
                                        <div class="box box-danger" style="margin-top: 1em;">
                                            <div class="box-header with-border">
                                                <strong><i class="fas fa-user-slash"></i> Abandon dominion</strong>
                                            </div>
                                            <div class="col-sm-12 col-md-12">
                                                <p><small class="text-muted">
                                                    If you wish to abandon your dominion, confirm below and click the button. An abandoned dominion stays in the game, but has no further production. You can create a new dominion afterwards, but if you abandon a second time, you need to contact admin first due to a bug.
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
                                        <span data-toggle="tooltip" data-placement="top" title="Start: {{ $round->start_date }}.<br>Registration: {{ $dominion->created_at }}.">
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
                                        <span data-toggle="tooltip" data-placement="top" title="Start: {{ $round->start_date }}.">
                                            <span class="label label-primary">Ended</span>
                                        </span>
                                    @elseif($round->hasEnded() and $user->created_at > $round->start_date)
                                        <span data-toggle="tooltip" data-placement="top" title="Start: {{ $round->start_date }}.<br>User registration date: {{ $user->created_at }}.">
                                            <span class="label label-primary">User was not registered</span>
                                        </span>
                                    @elseif(!$round->hasEnded())
                                        <p>
                                        @if(!$round->hasStarted())
                                            <span data-toggle="tooltip" data-placement="top" title="Start: {{ $round->start_date }}.">
                                                <span class="label label-danger">Starting Soon</span>
                                            </span><br>
                                            @if(in_array($round->mode, ['standard','deathmatch','factions','packs']))
                                                <small class="text-muted">The round starts at {{ $round->start_date }}.<br>The target land size is {{ number_format($round->goal) }}.</small>
                                            @elseif(in_array($round->mode, ['standard-duration','deathmatch-duration','factions-duration','packs-duration']))
                                                <small class="text-muted">The round starts at {{ $round->start_date }}.<br>The round lasts for {{ number_format($round->goal) }} ticks.</small>
                                            @elseif(in_array($round->mode, ['artefacts','artefacts-packs']))
                                                <small class="text-muted">The round starts at {{ $round->start_date }}.<br>The round lasts until a realm holds {{ number_format($round->goal) }} artefacts.</small>
                                            @endif
                                        @else
                                            <span data-toggle="tooltip" data-placement="top" title="Start: {{ $round->start_date }}.">
                                                <span class="label label-warning">Active</span>
                                            </span><br>
                                            <small class="text-muted">The round started at {{ $round->start_date }}.<br>Current tick: {{ number_format($round->ticks) }}.</small>
                                        @endif

                                        @if($round->hasCountdown())
                                            <small class="text-muted">
                                            <br>The round ends in <strong>{{ number_format($round->ticksUntilEnd()) . ' ' . Str::plural('tick', $round->ticksUntilEnd()) }}</strong>.
                                            </small>
                                        @endif
                                        </p>
                                    @endif
                                @endif
                            </td>

                            <td>
                                @if($round->number >= 62 or in_array(request()->getHost(), ['sim.odarena.com', 'odarena.local', 'odarena.virtual']))
                                    <span data-toggle="tooltip" data-placement="top" title="{{ $roundHelper->getRoundModeDescription($round) }}">
                                        {!! $roundHelper->getRoundModeIcon($round) !!} {{ $roundHelper->getRoundModeString($round) }}
                                    </span>

                                    @if(in_array($round->mode, ['packs','packs-duration','artefacts-packs']))
                                        @php
                                            $userRoundPacks = $packService->getPacksCreatedByUserInRound($user, $round);
                                        @endphp

                                        @if(!$round->hasEnded() and $userRoundPacks->count() > 0)

                                            <div class="col-sm-12">
                                                @foreach($userRoundPacks as $pack)
                                                    @php
                                                        $canDelete = $packService->canDeletePack($user, $pack);
                                                        $members = $pack->dominions->count();
                                                    @endphp
                                                        You are the Leader of a pack, which has {{ $members }} {{ Str::plural('member', $members) }}.
                                                        @if($canDelete)
                                                            <form action="{{ route('dashboard.delete-pack', $pack) }}" method="post">
                                                                @csrf
                                                                <input type="hidden" name="pack_id" value="{{ $pack->id }}">
                                                                <input type="hidden" name="action" value="delete">
                                                                <button class="btn btn-xs btn-danger" type="submit"><i class="fa fa-trash"></i> Delete</button>
                                                            </form>
                                                        @else
                                                            <span data-toggle="tooltip" data-placement="top" title="You cannot delete this pack because it has members.">
                                                                <button class="btn btn-xs btn-danger" disabled><i class="fa fa-trash"></i> Delete</button>
                                                            </span>
                                                        @endif
                                                    <br>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                @else
                                    <span data-toggle="tooltip" data-placement="top" title="Game modes were introduced in Round 62.">
                                        &mdash;
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if($dominion)
                                    <a href="{{ route('scribes.faction', Str::slug($dominion->race->name)) }}" target="_blank"><i class="fas fa-book"></i></a> {{ $dominion->race->name }}
                                @else
                                    &mdash;
                                @endif
                            </td>

                            <td>
                                @if($dominion)
                                    {{ number_format($dominion->land) }}
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


                            <td>
                                @if($round->hasEnded())
                                    <a href="{{ route('chronicles.round', $round) }}">{{ $round->name }}</a>
                                @else
                                    {{ $round->name }}
                                @endif
                            </td>

                            <td>{{ $round->league->description }}</td>


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
                paging: false,
            });
        })(jQuery);
    </script>
@endpush
