<footer class="main-footer">

    <div class="pull-right">
        @if (isset($selectedDominion) && ($selectedDominion->round->isActive()))
            @php
            $diff = $selectedDominion->round->start_date->subDays(1)->diff(now());
            $roundDay = $selectedDominion->round->start_date->subDays(1)->diffInDays(now());
            $roundDurationInDays = $selectedDominion->round->durationInDays();
            $currentHour = ($diff->h + 1);
            $currentTick = 1+floor(intval(Date('i')) / 15);

            echo "Day <strong>{$roundDay}</strong>/{$roundDurationInDays}, hour <strong>{$currentHour}</strong>, tick <strong>{$currentTick}</strong>.";

            @endphp
        @endif
        <span class="hidden-xs">Version: </span>{!! $version !!}
        <br>
    </div>

    @if (!isset($selectedDominion))
    <i class="fa fa-file-text-o"></i> <a href="{{ route('legal.privacypolicy') }}">Privacy Policy</a> | <a href="{{ route('legal.termsandconditions') }}">Terms and Conditions</a>
    @endif

</footer>
