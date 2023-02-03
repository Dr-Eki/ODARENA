@if($round->hasStarted())
    @if($round->hasCountdown())
        <p><em>This round has already started and round ends in {{ number_format($round->ticksUntilEnd()) . ' ' . str_plural('tick', $round->ticksUntilEnd()) }}</em>.</p>
    @else
        <p>This round has already started!</p>
    @endif
    <p>When you register, you start with 96 protection ticks. Make the most of them. Once you have used them all, you leave protection immediately.</p>
    <p>Regularly scheduled ticks do not count towards your dominion while you are in protection.</p>

    @if ($discordInviteLink = config('app.discord_invite_link'))
    <p>If you need any help or just want to chat, come join us on <a href="{{ $discordInviteLink }}" target="_blank">Discord</a>.</p>
    @endif

@else
    <p>The round starts on {{ $round->start_date->format('l, jS \o\f F Y \a\t H:i') }}.
        @if($round->mode == 'standard' or $round->mode == 'deathmatch' or $round->mode == 'factions')
            The target land size is {{ number_format($round->goal) }}. Once a dominion has reached that, a countdown of 48 ticks begins, and then the round ends.
        @elseif($round->mode == 'standard-duration' or $round->mode == 'deathmatch-duration' or $round->mode == 'factions-duration')
            The round lasts for {{ number_format($round->goal) }} ticks.
        @elseif($round->mode == 'artefacts')
            The round lasts until one realm controls {{ number_format($round->goal) }} artefacts.
        @endif
    </p>
    <p>When you register, you start with 96 protection ticks. Make the most of them. Once you have used them all, you leave protection immediately.</p>
    <p>Regularly scheduled ticks do not count towards your dominion while you are in protection.</p>

    @if ($discordInviteLink = config('app.discord_invite_link'))
        <p>In the meantime, come join us on <a href="{{ $discordInviteLink }}" target="_blank">Discord</a>.</p>
    @endif

@endif

<p>Head over to the <a href="https://sim.odarena.com/" target="_blank">ODARENA Simulator</a> if you want to sim protection. Click <a href="https://lounge.odarena.com/2020/02/24/odarena-sim/" target="_blank">here</a> to read about how the sim works.</p>
