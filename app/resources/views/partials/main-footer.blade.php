<footer class="main-footer">

    <div class="pull-right">

    @if (isset($selectedDominion) and $selectedDominion->round->isActive())

        @php
            $diff = $selectedDominion->round->start_date->subDays(1)->diff(now());
            $roundDay = floor($selectedDominion->round->start_date->subDays(1)->diffInDays(now()));
            $currentHour = ($diff->h + 1);
        @endphp

        @if($selectedDominion->round->hasStarted())
            <span data-toggle="tooltip" data-placement="top" title="Round target: {{ number_format($selectedDominion->round->goal) }} {{ $roundHelper->getRoundModeGoalString($selectedDominion->round) }}.">Tick <strong>{{ number_format($selectedDominion->round->ticks) }}</strong> / Day <strong>{{ $roundDay }}</strong> / Hour <strong>{{ $currentHour }}</strong></span>
            @if ($selectedDominion->round->hasCountdown())
                | Round ends in <strong><span data-toggle="tooltip" data-placement="top" title="The round ends at tick {{ number_format($selectedDominion->round->end_tick) }}.<br>Current tick: {{ number_format($selectedDominion->round->ticks) }}.">{{ number_format($selectedDominion->round->ticksUntilEnd()) . ' ' . Str::plural('tick', $selectedDominion->round->ticksUntilEnd()) }}</span></strong>.
            @endif
        @else
            <span data-toggle="tooltip" data-placement="top" title="Start date: {{ $selectedDominion->round->start_date }}">Round {{ $selectedDominion->round->number }} starts in <strong>{{ number_format($selectedDominion->round->hoursUntilStart()) . ' ' . Str::plural('hour', $selectedDominion->round->hoursUntilStart()) }}</strong>.</span>
        @endif
    @elseif (isset($selectedDominion) and !$selectedDominion->round->hasStarted())
        <span data-toggle="tooltip" data-placement="top" title="The round starts at {{ $selectedDominion->round->start_date }}">
            @if($selectedDominion->round->hoursUntilStart() > 0)
                Round {{ $selectedDominion->round->number }} starts in <strong>{{ number_format($selectedDominion->round->hoursUntilStart()) . ' ' . Str::plural('hour', $selectedDominion->round->hoursUntilStart()) }}</strong>.
            @else
                Round {{ $selectedDominion->round->number }} starts in <strong>{{ number_format($selectedDominion->round->minutesUntilStart()) . ' ' . Str::plural('minutes', $selectedDominion->round->minutesUntilStart()) }}</strong>.
            @endif
        </span>
    @endif

    <br>

    </div>

    <i class="ra ra-campfire ra-fw"></i><a href="https://lounge.odarena.com/" target="_blank">Lounge</a> | <i class="fa fa-file-text-o"></i> <a href="{{ route('legal.privacy-policy') }}">Privacy Policy</a> / <a href="{{ route('legal.terms-and-conditions') }}">Terms and Conditions</a> | <i class="fab fa-discord fa-fw"></i><a href="{{ config('app.discord_invite_link') }}">Discord</a>

</footer>
