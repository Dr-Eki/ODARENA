<footer class="main-footer">

    <div class="pull-right">

      @if (isset($selectedDominion) and $selectedDominion->round->isActive())
          @php
              $diff = $selectedDominion->round->start_date->subDays(1)->diff(now());
              $roundDay = $selectedDominion->round->start_date->subDays(1)->diffInDays(now());
              $currentHour = ($diff->h + 1);
              $currentTick = 1+floor(intval(Date('i')) / 15);
          @endphp

          @if($selectedDominion->round->hasStarted())
              <span data-toggle="tooltip" data-placement="top" title="Current round tick: {{ number_format($selectedDominion->round->ticks) }}<br>Countdown trigger: {{ number_format($selectedDominion->round->land_target) }} acres.">Day <strong>{{ $roundDay }}</strong>, hour <strong>{{ $currentHour }}</strong>, tick <strong>{{ $currentTick }}</strong>.</span>

              @if ($selectedDominion->round->hasCountdown())
                  | Round ends in <strong><span data-toggle="tooltip" data-placement="top" title="The round ends at {{ $selectedDominion->round->end_date }}">{{ number_format($hoursUntilRoundEnds) . ' ' . str_plural('hour', $hoursUntilRoundEnds) }}</span></strong>.
              @endif
          @else
              <span data-toggle="tooltip" data-placement="top" title="Start date: {{ $selectedDominion->round->start_date }}">Round {{ $selectedDominion->round->number }} starts in <strong>{{ number_format($selectedDominion->round->hoursUntilStart()) . ' ' . str_plural('hour', $selectedDominion->round->hoursUntilStart()) }}</strong>.</span>
          @endif

      @elseif (isset($selectedDominion) and !$selectedDominion->round->hasStarted())
          Round <strong>{{ $selectedDominion->round->number }}</strong> starts in <strong><span data-toggle="tooltip" data-placement="top" title="The round starts at {{ $selectedDominion->round->start_date }}">{{ number_format($selectedDominion->round->hoursUntilStart()) . ' ' . str_plural('hour', $selectedDominion->round->hoursUntilStart()) }}</span></strong>.
      @endif

      <br>

    </div>

    <i class="ra ra-campfire ra-fw"></i><a href="https://lounge.odarena.com/" target="_blank">Lounge</a> | <i class="fa fa-file-text-o"></i> <a href="{{ route('legal.privacypolicy') }}">Privacy Policy</a> / <a href="{{ route('legal.termsandconditions') }}">Terms and Conditions</a> | <i class="fab fa-discord fa-fw"></i><a href="{{ config('app.discord_invite_link') }}">Discord</a>

</footer>
