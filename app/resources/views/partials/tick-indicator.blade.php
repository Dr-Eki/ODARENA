<div id="spinner" class="bg-purple" style="padding: 10px 15px; z-index: 999999; font-size: 14px; font-weight: 400; color: #fff; display: {{ $selectedDominion->round->is_ticking ? 'block' : 'none' }};" data-is-ticking="{{ $selectedDominion->round->is_ticking }}">
    <i class="fa fa-solid fa-spinner fa-spin"></i> The World Spinner is currently spinning the world. No actions can be performed until the world has finished spinning.
</div>

<script>
    var spinner = document.getElementById('spinner');
    var isTicking = spinner.getAttribute('data-is-ticking') === '1';
    var intervalId = setInterval(checkIsTicking, isTicking ? 1000 : 10000);
    var counter = 0;

    function checkIsTicking() {
        counter++;
        if (counter > 1800) {
            clearInterval(intervalId);
            return;
        }

        fetch('/api/v1/is-round-ticking') // replace with your route
            .then(response => response.json())
            .then(data => {
                if (data.is_ticking) {
                    spinner.style.display = 'block';
                    if (!isTicking) {
                        clearInterval(intervalId);
                        intervalId = setInterval(checkIsTicking, 1000);
                    }
                } else {
                    spinner.style.display = 'none';
                    if (isTicking) {
                        clearInterval(intervalId);
                        intervalId = setInterval(checkIsTicking, 10000);
                    }
                }
                isTicking = data.is_ticking;
            });
    }
</script>