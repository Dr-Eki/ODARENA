<aside class="main-sidebar">
    <section class="sidebar">

        @if (isset($selectedDominion))
            <div class="user-panel">
                <div class="pull-left image">
                    <img src="{{ Auth::user()->getAvatarUrl() }}" class="img-circle" alt="{{ Auth::user()->display_name }}">
                </div>
                <div class="pull-left info">
                    <p>{{ $selectedDominion->name }}</p>
                    <a href="{{ route('dominion.realm') }}">{{ $selectedDominion->realm->name }} (#{{ $selectedDominion->realm->number }})</a>
                </div>
            </div>
        @endif

        <ul class="sidebar-menu" data-widget="tree">
            @if (isset($selectedDominion))
                <li class="{{ Route::is('dominion.status') ? 'active' : null }}"><a href="{{ route('dominion.status') }}"><i class="fas fa-map-pin fa-fw"></i> <span>Status</span></a></li>


                <li class="{{ Route::is('dominion.resources') ? 'active' : null }}">
                    <a href="{{ route('dominion.resources') }}">
                        <i class="ra ra-mining-diamonds ra-fw"></i>
                        <span>Resources</span>
                        @if($productionCalculator->isOnBrinkOfStarvation($selectedDominion))
                            <span class="label label-danger pull-right"><i class="ra ra-apple"></i></span>
                        @endif
                    </a>
                </li>

                <li class="{{ Route::is('dominion.land') ? 'active' : null }}">
                    <a href="{{ route('dominion.land') }}">
                        <i class="fa fa-map fa-fw"></i>
                        <span>Land</span>

                        <span class="pull-right-container">
                            @if (!$selectedDominion->daily_land and $selectedDominion->protection_ticks == 0 and $selectedDominion->round->hasStarted())
                                <small class="label label-primary pull-right"><i class="fa fa-plus"></i></small>
                            @endif
                        </span>
                    </a>
                </li>

                <!-- Hide Construct Buildings from cannot_construct races -->
                @if (!(bool)$selectedDominion->race->getPerkValue('cannot_construct'))
                    <li class="{{ Route::is('dominion.buildings') ? 'active' : null }}">
                        <a href="{{ route('dominion.buildings') }}">
                            <i class="fa fa-home fa-fw"></i>
                            <span>Buildings</span>
                        </a>
                    </li>
                @endif

                <!-- Hide Improvements from cannot_improve_castle races -->
                @if (!(bool)$selectedDominion->race->getPerkValue('cannot_improve_castle'))
                <li class="{{ Route::is('dominion.improvements') ? 'active' : null }}">
                  <a href="{{ route('dominion.improvements') }}">
                    <i class="fa fa-arrow-up fa-fw"></i> <span>Improvements</span>
                  </a>
                </li>
                @endif

                <!-- TECHS -->
                @if (!(bool)$selectedDominion->race->getPerkValue('cannot_tech'))
                <li class="{{ Route::is('dominion.advancements') ? 'active' : null }}"><a href="{{ route('dominion.advancements') }}"><i class="fa fa-flask fa-fw"></i> <span>Advancements</span>
                    @if($techCalculator->maxLevelAfforded($selectedDominion) !== 0)
                      <span class="pull-right-container"><small class="label pull-right bg-green">{{ $techCalculator->maxLevelAfforded($selectedDominion) }}</small></span></a></li>
                    @endif

                    </a></li>

                @endif

                <li class="{{ Route::is('dominion.military') ? 'active' : null }}"><a href="{{ route('dominion.military') }}"><i class="ra ra-sword ra-fw"></i> <span>Military</span></a></li>

                <!-- Hide Invade from cannot_invade races -->
                @if (!(bool)$selectedDominion->race->getPerkValue('cannot_invade'))
                <li class="{{ Route::is('dominion.invade') ? 'active' : null }}"><a href="{{ route('dominion.invade') }}"><i class="ra ra-crossed-swords ra-fw"></i> <span>Invade</span></a></li>
                @endif

                <li class="{{ Route::is('dominion.intelligence') ? 'active' : null }}"><a href="{{ route('dominion.intelligence') }}"><i class="fa fa-eye fa-fw"></i> <span>Op Center</span></a></li>
                <li class="{{ Route::is('dominion.offensive-ops') ? 'active' : null }}"><a href="{{ route('dominion.offensive-ops') }}"><i class="ra ra-skull ra-fw"></i> <span>Spells &amp; Spy Ops</span></a></li>
                <li class="{{ Route::is('dominion.friendly-ops') ? 'active' : null }}"><a href="{{ route('dominion.friendly-ops') }}"><i class="ra ra-fairy-wand ra-fw"></i> <span>Friendly Magic</span></a></li>

                {{--
                <li class="{{ Route::is('dominion.magic') ? 'active' : null }}"><a href="{{ route('dominion.magic') }}"><i class="ra ra-fairy-wand ra-fw"></i> <span>Magic</span></a></li>
                <li class="{{ Route::is('dominion.espionage') ? 'active' : null }}"><a href="{{ route('dominion.espionage') }}"><i class="fa fa-user-secret fa-fw"></i> <span>Espionage</span></a></li>
                --}}
                <li class="{{ Route::is('dominion.search') ? 'active' : null }}"><a href="{{ route('dominion.search') }}"><i class="fa fa-search fa-fw"></i> <span>Search</span></a></li>

                <li class="{{ Route::is('dominion.council*') ? 'active' : null }}"><a href="{{ route('dominion.council') }}"><i class="fas fa-comments ra-fw"></i>
                  <span>
                    @if($selectedDominion->realm->alignment == 'evil')
                        Senate
                    @elseif($selectedDominion->realm->alignment == 'good')
                        Parliament
                    @elseif($selectedDominion->realm->alignment == 'independent')
                        Assembly
                    @else
                        Council
                    @endif
                </span> {!! $councilUnreadCount > 0 ? ('<span class="pull-right-container"><small class="label pull-right bg-green">' . $councilUnreadCount . '</small></span>') : null !!}</a></li>

                <li class="{{ Route::is('dominion.government') ? 'active' : null }}"><a href="{{ route('dominion.government') }}"><i class="fa fa-university fa-fw"></i> <span>Government</span></a></li>

                <li class="{{ Route::is('dominion.realm') ? 'active' : null }}"><a href="{{ route('dominion.realm') }}"><i class="fas fa-map-signs fa-fw"></i> <span>The World</span></a></li>

                <li class="{{ Route::is('dominion.world-news') ? 'active' : null }}">
                    <a href="{{ route('dominion.world-news') }}">
                        <i class="far fa-newspaper fa-fw"></i>
                        <span>World News</span>
                        {!! $newsUnreadCount > 0 ? ('<span class="pull-right-container"><small class="label pull-right bg-green">' . $newsUnreadCount . '</small></span>') : null !!}
                    </a>
                </li>

                {{--
                <li class="{{ Route::is('dominion.rankings') ? 'active' : null }}"><a href="{{ route('dominion.rankings') }}"><i class="fa fa-trophy ra-fw"></i> <span>Rankings</span></a></li>
                <li class="{{ Route::is('dominion.notes') ? 'active' : null }}"><a href="{{ route('dominion.notes') }}"><i class="ra ra-quill-ink ra-fw"></i> <span>Notes</span></a></li>
                <li class="{{ Route::is('dominion.calculations') ? 'active' : null }}">
                    <a href="{{ route('dominion.calculations') }}">
                        <i class="ra ra-gears ra-fw"></i>
                        <span>Calculations</span>
                        <span class="pull-right-container">
                            <small class="label pull-right">Beta</small>
                        </span>
                    </a>
                </li>
                --}}

            @else

                <li class="{{ Route::is('dashboard') ? 'active' : null }}"><a href="{{ route('dashboard') }}"><i class="ra ra-capitol ra-fw"></i> <span>Select your Dominion</span></a></li>

            @endif
        </ul>
    </section>
</aside>
