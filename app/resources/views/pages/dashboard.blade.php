@extends('layouts.master')

@section('page-header', 'Dashboard')

@section('content')
    <div class="box">
        <div class="box-body">
            @if ($dominions->isEmpty())
                <p>Welcome to ODARENA!</p>
                <p>To start playing, please register in a round below.</p>
            @else
                <p>Welcome back to ODARENA, {{ Auth::user()->display_name }}!</p>
                <p>Select a dominion below to go to its status screen.</p>
            @endif
        </div>
    </div>

    <div class="row">

        <div class="col-lg-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-capitol ra-fw"></i> Dominions</h3>
                </div>

                @if ($dominions->isEmpty())

                    <div class="box-body">
                        <p>Are you ready to join the fray? Click the Register button to join the current round.</p>
                        @if ($discordInviteLink = config('app.discord_invite_link'))
                            <p>If you need any help, come join us on Discord.</p>
                            <p style="padding: 0 20px;">
                                <a href="{{ $discordInviteLink }}" target="_blank">
                                    <img src="{{ asset('assets/app/images/join-the-discord.png') }}" alt="Join the Discord" class="img-responsive">
                                </a>
                            </p>
                        @endif
                    </div>

                @else

                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col width="80">
                                <col>
                                <col width="200">
                                <col width="80">
                                <col width="80">
                                <col width="80">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">Round</th>
                                    <th>Dominion</th>
                                    <th class="text-center">Faction</th>
                                    <th class="text-center">Land</th>
                                    <th class="text-center">Networth</th>
                                    <th class="text-center">Realm</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dominions->all() as $dominion)
                                    <tr>
                                        <td class="text-center">
                                            {{ $dominion->round->number }}
                                        </td>
                                        <td>
                                            @if (!$dominion->round->hasStarted())
                                                <span class="label label-success">Starting soon</span>
                                            @endif

                                            @if($dominion->isLocked())
                                                <span class="label label-info">Finished</span>
                                            @endif

                                            @if ($dominion->isSelectedByAuthUser())
                                                <a href="{{ route('dominion.status') }}">{{ $dominion->name }}</a>
                                                <span class="label label-success">Selected</span>
                                            @else
                                                <form action="{{ route('dominion.select', $dominion) }}" method="post">
                                                    @csrf
                                                    <button type="submit" class="btn btn-link" style="padding: 0;">{{ $dominion->name }}</button>
                                                </form>
                                            @endif

                                            @if($dominion->round->hasStarted() and !$dominion->isLocked())
                                                <div class="col-sm-6 pull-right">
                                                    <p>If you wish to abandon your dominion {{ $dominion->name }} in round {{ $dominion->round->number }}, you can do so here by checking confirm and then pressing the button. <em>This action cannot be undone.</em></p>
                                                    <form action="{{ route('dominion.abandon', $dominion) }}" method="post">
                                                        @csrf
                                                        <label>
                                                            <input type="checkbox" name="remember" required> Confirm abandon
                                                        </label>
                                                        <button type="submit" class="btn btn-danger btn-xs">Abandon dominion</button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{ $dominion->race->name }}
                                        </td>
                                        <td class="text-center">
                                            {{ number_format($landCalculator->getTotalLand($dominion)) }}
                                        </td>
                                        <td class="text-center">
                                            {{ number_format($networthCalculator->getDominionNetworth($dominion)) }}
                                        </td>
                                        <td class="text-center">
                                            #{{ $dominion->realm->number }}: {{ $dominion->realm->name }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @endif

            </div>
        </div>

        <div class="col-lg-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o fa-fw"></i> Rounds</h3>
                </div>

                @if ($rounds->isEmpty())

                    <div class="box-body">
                        <p>There are currently no active rounds.</p>
                    </div>

                @else

                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col width="80">
                                <col>
                                <col width="160">
                                <col width="80">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">Round</th>
                                    <th>Chapter</th>
                                    <th>Era</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Register</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rounds->all() as $round)
                                    @php
                                        $trClass = 'danger';
                                        $userAlreadyRegistered = $round->userAlreadyRegistered(Auth::user());

                                        if ($round->hasEnded()) {
                                            $trClass = '';
                                        } elseif ($userAlreadyRegistered) {
                                            $trClass = 'info';
                                        } elseif ($round->hasStarted()) {
                                            $trClass = 'warning';
                                        } elseif ($round->openForRegistration()) {
                                            $trClass = 'success';
                                        }
                                    @endphp

                                    <tr class="{{ $trClass }}">
                                        <td class="text-center">{{ $round->number }}</td>
                                        <td>
                                            {{ $round->name }}
                                        </td>
                                        <td>
                                          {{ $round->league->description }}
                                        </td>
                                        <td class="text-center">
                                            @if ($round->hasEnded())
                                                <abbr title="Ended at {{ $round->end_date }}">Ended</abbr>
                                            @elseif ($round->isActive())
                                                <abbr title="Ending at {{ $round->end_date }}">
                                                    Ending in {{ $round->hoursUntilEnd() }} {{ str_plural('hour', $round->hoursUntilEnd()) }}
                                                </abbr>
                                            @else
                                                <abbr title="Starting at {{ $round->start_date }}">
                                                    Starting in {{ $round->daysUntilStart() }} {{ str_plural('day', $round->daysUntilStart()) }}
                                                </abbr>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($round->hasEnded())
                                                <a href="{{ route('valhalla.round', $round) }}">Valhalla</a>
                                            @elseif ($userAlreadyRegistered && $round->isActive())
                                                Playing
                                            @elseif ($userAlreadyRegistered && !$round->hasStarted())
                                                Registered
                                            @elseif ($round->openForRegistration())
                                                <a href="{{ route('round.register', $round) }}" class="btn btn-primary btn-flat btn-xs">Register</a>
                                            @else
                                                In {{ $round->daysUntilRegistration() }} day(s)
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @endif

            </div>
        </div>

    </div>
@endsection
