@extends('layouts.topnav')
@section('title', 'ODARENA | Free browser and text based strategy fantasy game')

@section('content')
    <div class="row">
        <div class="col-sm-8 col-sm-offset-2">
            <div style="margin-bottom: 20px;">
                <img src="{{ asset('assets/app/images/odarena.png') }}" class="img-responsive" alt="ODARENA">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3">
            <div class="box {{ $currentRound === null ? 'box-warning' : 'box-success' }}">
                <div class="box-header with-border text-center">
                    <h3 class="box-title">
                        @if ($currentRound === null)
                            Current Round
                        @else
                            {{ $currentRound->hasStarted() ? 'Current' : 'Upcoming' }} Round: <strong>{{ $currentRound->number }}</strong>
                        @endif
                    </h3>
                </div>
                @if ($currentRound === null || $currentRound->hasEnded())
                    <div class="box-body text-center" style="padding: 0; border-bottom: 1px solid #f4f4f4;">
                        <p style="font-size: 1.5em;" class="text-red">Registration: Closed</p>
                    </div>
                    <div class="box-body text-center">
                        <p><strong>There is no ongoing round.</strong></p>
                        <p>A new round will start shortly!</p>
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
                        <p style="font-size: 1.5em;" class="text-green">Registration: <strong>Open</strong></p>
                    </div>
                    <div class="box-body text-center">
                        <p>
                            <span data-toggle="tooltip" data-placement="top" title="Start date: {{ $currentRound->start_date }}">
                                @if($currentRound->hoursUntilStart() > 0)
                                    Round {{ $currentRound->number }} starts in <strong>{{ number_format($currentRound->hoursUntilStart()) . ' ' . str_plural('hour', $currentRound->hoursUntilStart()) }}</strong>.
                                @else
                                    Round {{ $currentRound->number }} starts in <strong>{{ number_format($currentRound->minutesUntilStart()) . ' ' . str_plural('minutes', $currentRound->minutesUntilStart()) }}</strong>.
                                @endif
                            </span>
                        </p>

                        @if($currentRound->mode == 'standard' or $currentRound->mode == 'deathmatch')
                            <p>Land goal: <strong>{{ number_format($currentRound->goal) }}.</strong></p>
                        @elseif($currentRound->mode == 'standard-duration' or $currentRound->mode == 'deathmatch-duration')
                            <p>Round length: <strong>{{ number_format($currentRound->goal) }} ticks.</strong></p>
                        @elseif($currentRound->mode == 'artefacts')
                            <p>Artefacts to control: <strong>{{ number_format($currentRound->goal) }}.</strong></p>
                        @endif

                        <a href="{{ route('round.register', $currentRound) }}">
                            <button type="submit" class="btn btn-primary btn-block">Register Now!</button>
                        </a>
                    </div>
                @else
                    <div class="box-body text-center" style="padding: 0;">
                        <p style="font-size: 1.5em;" class="text-green">Registration: <strong>Open</strong></p>
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
                                        {{ number_format($currentRound->start_date->subDays(1)->diffInDays(now())) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center">Tick:</td>
                                    <td class="text-center">
                                        {{ number_format($currentRound->ticks) }}
                                    </td>
                                </tr>
                                <tr>
                                    @if($currentRound->mode == 'standard' or $currentRound->mode == 'deathmatch')
                                        <td class="text-center">Land goal:</td>
                                        <td class="text-center">
                                            {{ number_format($currentRound->goal) }}
                                        </td>
                                    @elseif($currentRound->mode == 'standard-duration' or $currentRound->mode == 'deathmatch-duration')
                                        <td class="text-center">Round length:</td>
                                        <td class="text-center">
                                            {{ number_format($currentRound->goal) }} ticks
                                        </td>
                                    @endif


                                </tr>
                                <tr>
                                    <td class="text-center">Largest dominion:</td>
                                    <td class="text-center">
                                        {{ number_format($largestDominion) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center">Dominions:</td>
                                    <td class="text-center">{{ number_format($currentRound->dominions->count()) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer text-center">
                        @if ($currentRound->hasCountDown())
                            <p>
                                <em class="text-red">The round ends in {{ $currentRound->ticksUntilEnd() }} {{ str_plural('tick', $currentRound->ticksUntilEnd()) }}.</em>
                            </p>
                        @else
                            <p>
                                <a href="{{ route('round.register', $currentRound) }}">
                                <button type="submit" class="btn btn-primary btn-block">Join round {{ $currentRound->number }} now!</button>
                                </a>
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        <div class="col-sm-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">Welcome to ODARENA!</h3>
                </div>
                <div class="box-body">
                    @if(request()->getHost() == 'sim.odarena.com')
                        <p>This is the <strong>ODARENA Simulator</strong>.</p>
                        <p>The simulator is identical to the game. Here's how it works:</p>
                        <ol>
                            <li>Create a new account. The sim uses an entirely separate database from the game, in order to make changes here (for upcoming rounds) without affecting the game.</li>
                            <li>Create a dominion as usual.</li>
                            <li>Start ticking through protection like you would normally.</li>
                            <li>Once you have depleted your protection ticks, you can delete and start a new dominion.</li>
                            <li>Refresh the page and you will be able to create a new dominion.</li>
                            <li>No scheduled ticks take place here &mdash; ever.</li>
                        </ol>
                        <p>To start simming, first <a href="{{ route('auth.register') }}">Register An Account</a>.</p>
                        <p>If you already have an account, <a href="{{ route('auth.login') }}">Login To Your Account</a>.</p>
                    @else
                        <p><strong>ODARENA</strong> is a world in which you take control of a dominion. Your goal is to be the largest dominion when a round ends.</p>
                        <p>Rounds last until a dominion reaches a predetermined size. Once someone has reached this target, a countdown of 12 hours begins. Then, a new round begins.<p>
                        <p>The game can be a little daunting at first, but its fast pace and experimental nature make it fun and exciting. The meta changes frequently and there are many ways to play the game.</p>
                        <hr>
                        <p>To start playing, first <strong><a href="{{ route('auth.register') }}">Register An Account</a></strong>.</p>
                        <p>If you already have an account, <a href="{{ route('auth.login') }}">Login To Your Account</a>.</p>
                        <p>Then once you are logged in, you can create your Dominion and join the round.</p>
                        <hr>
                        <p><strong>ODARENA</strong> has:</p>
                        <ul>
                            <li>{{ $factions }} <a href="{{ route('scribes.factions') }}">factions</a></li>
                            <li>{{ $buildings }} <a href="{{ route('scribes.buildings') }}">buildings</a></li>
                            <li>{{ $spells }} <a href="{{ route('scribes.spells') }}">spells</a></li>
                            <li>{{ $sabotage }} <a href="{{ route('scribes.sabotage') }}">sabotage operations</a></li>
                            <li>{{ $advancements }} <a href="{{ route('scribes.advancements') }}">advancements</a></li>
                            <li>{{ $techs }} <a href="{{ route('scribes.research') }}">technologies</a></li>
                            <li>{{ $improvements }} <a href="{{ route('scribes.improvements') }}">improvements</a></li>
                            <li>{{ $resources }} <a href="{{ route('scribes.resources') }}">resources</a></li>
                        </ul>
                        <p>The strategy possibilities are almost infinite.</p>
                        <hr>
                        @if ($discordInviteLink = config('app.discord_invite_link'))
                            <p>Please feel welcome to join us on our <i class="fab fa-discord"></i> <a href="{{ $discordInviteLink }}" target="_blank">Discord server</a>!  It's the main place for game announcements, game-related chat and development chat.</p>
                        @endif

                       </p>
                    @endif
                    <hr>
                    <p>ODARENA is based on <a href="https://www.opendominion.net/" target="_new">OpenDominion</a>, originally created by WaveHack.</p>
                    <p>ODARENA is open source software. The code can be found on <a href="https://github.com/Dr-Eki/ODArena" target="_blank">GitHub</a>.</p>
                </div>
            </div>
        </div>

        <div class="col-sm-3">
            <img src="{{ asset('assets/app/images/odarena-icon.png') }}" class="img-responsive" alt="">
        </div>

    </div>
@endsection
