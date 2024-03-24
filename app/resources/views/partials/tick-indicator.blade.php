@if(isset($selectedDominion))

<div id="spinner" class="bg-purple" style="padding: 10px 15px; z-index: 999999; font-size: 14px; font-weight: 400; color: #fff; display: {{ $selectedDominion->round->is_ticking ? 'block' : 'none' }};" data-is-ticking="{{ $selectedDominion->round->is_ticking }}">
    <i class="fa fa-solid fa-spinner fa-spin"></i> The World Spinner is currently spinning the world. No actions can be performed until the world has finished spinning.
</div>

<script>
    var spinner = document.getElementById('spinner');
    var isTicking = spinner.getAttribute('data-is-ticking') === '1';
    var tickCheckCount = 0;

    function checkIsTicking() {
        fetch('/api/v1/dominion/is-ticking')
            .then(response => response.json())
            .then(data => {
                if (data.is_ticking) {
                    spinner.style.display = 'block';
                    tickCheckCount = 0;
                } else {
                    spinner.style.display = 'none';
                    tickCheckCount = 0;
                }
                isTicking = data.is_ticking;
            });
    }

    function scheduleNextCheck() {
        var now = new Date();
        var minutes = now.getMinutes();
        var seconds = now.getSeconds();
        var milliseconds = now.getMilliseconds();
        var nextQuarterHour = 15 * Math.ceil((minutes + 1) / 15);
        var remainingTime = ((nextQuarterHour - minutes) * 60 - seconds) * 1000 - milliseconds;

        // If isTicking is true, check every second for up to 60 seconds
        // If isTicking is false, check every quarter hour
        var checkInterval = isTicking && tickCheckCount < 60 ? 1000 : remainingTime;

        setTimeout(function() {
            checkIsTicking();
            scheduleNextCheck();
            if (isTicking) {
                tickCheckCount++;
            }
        }, checkInterval);
    }

    checkIsTicking();
    scheduleNextCheck();
</script>

@endif