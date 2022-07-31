<div class="box box-primary">
    <div class="box-body no-padding">
        <div class="row">
            <div class="col-md-12 col-md-12">
                <div class="navbar-collapse">
                    <ul class="nav navbar-nav scribes-menu">
                        <li class="{{ Route::is('scribes.advancements') ? 'active' : null }}"><a href="{{ route('scribes.advancements') }}">Advancements</a></li>
                        <li class="{{ Route::is('scribes.buildings') ? 'active' : null }}"><a href="{{ route('scribes.buildings') }}">Buildings</a></li>
                        <li class="{{ Route::is('scribes.decrees') ? 'active' : null }}"><a href="{{ route('scribes.decrees') }}">Decrees</a></li>
                        <li class="{{ Route::is('scribes.deities') ? 'active' : null }}"><a href="{{ route('scribes.deities') }}">Deities</a></li>
                        <li class="{{ Route::is('scribes.factions') ? 'active' : null }}"><a href="{{ route('scribes.factions') }}">Factions</a></li>
                        <li class="{{ Route::is('scribes.improvements') ? 'active' : null }}"><a href="{{ route('scribes.improvements') }}">Improvements</a></li>
                        <li class="{{ Route::is('scribes.Quickstarts') ? 'active' : null }}"><a href="{{ route('scribes.quickstarts') }}">Quickstarts</a></li>
                        <li class="{{ Route::is('scribes.resources') ? 'active' : null }}"><a href="{{ route('scribes.resources') }}">Resources</a></li>
                        <li class="{{ Route::is('scribes.sabotage') ? 'active' : null }}"><a href="{{ route('scribes.sabotage') }}">Sabotage</a></li>
                        <li class="{{ Route::is('scribes.spells') ? 'active' : null }}"><a href="{{ route('scribes.spells') }}">Spells</a></li>
                        <li class="{{ Route::is('scribes.titles') ? 'active' : null }}"><a href="{{ route('scribes.titles') }}">Titles</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
