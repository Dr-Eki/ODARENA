@extends('layouts.topnav')

@section('content')
    <div class="row">
        <div class="col-sm-8 col-sm-offset-2">
            <div style="margin-bottom: 20px;">
                <img src="{{ asset('assets/app/images/odarena.png') }}" class="img-responsive" alt="OD Arena">
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-sm-3">
            <div class="box">
                <div class="box-header with-border text-center">
                    <h3 class="box-title">
                        @if ($currentRound === null)
                            Current Round
                        @else
                            {{ $currentRound->hasStarted() ? 'Current' : 'Next' }} Round: <strong>{{ $currentRound->number }}</strong>
                        @endif
                    </h3>
                </div>
                @if ($currentRound === null || $currentRound->hasEnded())
                    <div class="box-body text-center" style="padding: 0; border-bottom: 1px solid #f4f4f4;">
                        <p style="font-size: 1.5em;" class="text-red">Inactive</p>
                    </div>
                    <div class="box-body text-center">
                        <p><strong>There is no ongoing round.</strong></p>
                        @if ($discordInviteLink = config('app.discord_invite_link'))
                            <p>Check the Discord for more information.</p>

                            <p style="padding: 0 20px;">
                                <a href="{{ $discordInviteLink }}" target="_blank">
                                    <img src="{{ asset('assets/app/images/join-the-discord.png') }}" alt="Join the Discord" class="img-responsive">
                                </a>
                            </p>
                        @endif
                    </div>
                @elseif (!$currentRound->hasStarted() && $currentRound->openForRegistration())
                    <div class="box-body text-center" style="padding: 0; border-bottom: 1px solid #f4f4f4;">
                        <p style="font-size: 1.5em;" class="text-yellow">Open for Registration</p>
                    </div>
                    <div class="box-body text-center">
                        <p>Registration for round {{ $currentRound->number }} is open.</p>
                        <p>The round starts on {{ $currentRound->start_date }} and lasts for {{ $currentRound->durationInDays() }} days.</p>
                    </div>
                @elseif (!$currentRound->hasStarted())
                    <div class="box-body text-center" style="padding: 0; border-bottom: 1px solid #f4f4f4;">
                        <p style="font-size: 1.5em;" class="text-yellow">Starting Soon</p>
                    </div>
                    <div class="box-body text-center">
                        <p>Registration for round {{ $currentRound->number }} opens on {{ $currentRound->start_date->subDays(3) }}.</p>
                        <p>The round starts on {{ $currentRound->start_date }} and lasts for {{ $currentRound->durationInDays() }} days.</p>
                    </div>
                @else
                    <div class="box-body text-center" style="padding: 0;">
                        <p style="font-size: 1.5em;" class="text-green">Active</p>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col width="50%">
                                <col width="50%">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td class="text-center">Day:</td>
                                    <td class="text-center">
                                        {{ number_format($currentRound->start_date->subDays(1)->diffInDays(now())) }} / {{ number_format($currentRound->durationInDays()) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center">Players:</td>
                                    <td class="text-center">{{ number_format($currentRound->dominions->count()) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center">Realms:</td>
                                    <td class="text-center">{{ number_format($currentRound->realms->count()) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer text-center">
                        @if ($currentRound->daysUntilEnd() < 1)
                            <p>
                                <em class="text-red">The round ends in {{ $currentRound->daysUntilEnd() }} {{ str_plural('day', $currentRound->daysUntilEnd()) }}.</em>
                            </p>
                        @else
                            <p>
                                <em><a href="{{ route('round.register', $currentRound) }}">Join the ongoing round!</a></em>
                            </p>
                        @endif
                    </div>
                @endif
            </div>
            @if ($currentRound !== null)
                <div class="box">
                    <div class="box-header with-border text-center">
                        <h3 class="box-title">
                            {{ $currentRound->hasStarted() && !$currentRound->hasEnded() ? 'Current' : 'Previous' }} Round Rankings
                        </h3>
                        <div class="box-body table-responsive no-padding">
                            @if ($currentRankings !== null && !$currentRankings->isEmpty())
                                <table class="table">
                                    <colgroup>
                                        <col>
                                        <col>
                                        <col>
                                        <col>
                                    </colgroup>
                                    <thead>
                                    </thead>
                                    <tbody>
                                        @foreach ($currentRankings as $row)
                                            <tr>
                                                <td class="text-center">{{ $row->land_rank }}</td>
                                                <td>
                                                    {{ $row->dominion_name }} (#{{ $row->realm_number }})
                                                </td>
                                                <td class="text-center">{{ number_format($row->land) }}</td>
                                                <td class="text-center">
                                                    @php
                                                        $rankChange = (int)$row->land_rank_change;
                                                    @endphp
                                                    @if ($rankChange > 0)
                                                        <span class="text-success"><i class="fa fa-caret-up"></i> {{ $rankChange }}</span>
                                                    @elseif ($rankChange === 0)
                                                        <span class="text-warning">-</span>
                                                    @else
                                                        <span class="text-danger"><i class="fa fa-caret-down"></i> {{ abs($rankChange) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                No rankings recorded yet.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="col-sm-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Welcome to OD Arena!</h3>
                </div>
                <div class="box-body">
                    <p>OD Arena is a persistent browser-based fantasy game where you control a dominion and is charged with defending its lands and competing with other players to become the largest in the current round.</p>

                    <p>To start playing, first <a href="{{ route('auth.register') }}">Register An Account</a>.</p>
                    <p>If you already have an account, <a href="{{ route('auth.login') }}">Login</a> instead.</p>
                    <p>Then once you are logged in, you can create your Dominion and join the round.</p>

                    @if ($currentRound === null || $currentRound->hasEnded())
                    <p><em>There is currently no round. A new one will start in a day or two.</em></p>
                    <p>Join us on our <a href="{{ $discordInviteLink }}" target="_blank">Discord server <i class="fa fa-external-link"></i></a> to be informed.
                    @else
                    <p>And please come join us on our <a href="{{ $discordInviteLink }}" target="_blank">Discord server <i class="fa fa-external-link"></i></a>!
                    @endif
                     It's the main place for game announcements, game-related chat and development chat.</p>
                   </p>
                </div>
                <div class="box-body">
                    <p>OD Arena is based on <a href="https://beta.opendominion.net/" target="_new">OpenDominion</a>, created by WaveHack.</p>

                    <p>Just like OpenDominion, OD Arena is open source software and can be found on <a href="https://github.com/Dr-Eki/OpenDominion" target="_blank">GitHub <i class="fa fa-external-link"></i></a>.</p>
                </div>

            </div>
        </div>

        <div class="col-sm-3">
            <img src="{{ asset('assets/app/images/odarena-icon.png') }}" class="img-responsive" alt="">
        </div>

    </div>
@endsection
