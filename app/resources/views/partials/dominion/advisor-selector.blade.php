<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-question-circle"></i> Advisors</h3>
    </div>
    <div class="box-body text-center">

        <a href="{{ route('dominion.advisors.production') }}" class="btn btn-app {{ Route::is('dominion.advisors.production') ? 'active' : null }}">
            <i class="ra ra-mining-diamonds"></i> Production
        </a>

        <a href="{{ route('dominion.advisors.military') }}" class="btn btn-app {{ Route::is('dominion.advisors.military') ? 'active' : null }}">
            <i class="ra ra-sword"></i> Military
        </a>

        <a href="{{ route('dominion.advisors.statistics') }}" class="btn btn-app {{ Route::is('dominion.advisors.statistics') ? 'active' : null }}">
            <i class="fa fa-chart-bar"></i> Statistics
        </a>

        <a href="{{ route('dominion.advisors.history') }}" class="btn btn-app {{ Route::is('dominion.advisors.history') ? 'active' : null }}">
            <i class="fa fa-book"></i> History
        </a>

        <a href="{{ route('dominion.advisors.buffer') }}" class="btn btn-app {{ Route::is('dominion.advisors.buffer') ? 'active' : null }}">
            <i class="fa fa-brands fa-buffer"></i> Buffer
        </a>

    </div>
</div>
