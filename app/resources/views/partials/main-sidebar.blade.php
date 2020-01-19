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

                <!--
                <li class="header">GENERAL</li>
                -->
                <li class="{{ Route::is('dominion.status') ? 'active' : null }}"><a href="{{ route('dominion.status') }}"><i class="fa fa-bar-chart fa-fw"></i> <span>Status</span></a></li>
                <li class="{{ Route::is('dominion.advisors.*') ? 'active' : null }}"><a href="{{ route('dominion.advisors') }}"><i class="fa fa-question-circle fa-fw"></i> <span>Advisors</span></a></li>
                <li class="{{ Route::is('dominion.bonuses') ? 'active' : null }}">
                    <a href="{{ route('dominion.bonuses') }}">
                        <i class="fa fa-plus fa-fw"></i>
                        <span>Daily Bonus</span>
                        <span class="pull-right-container">
                            @if (!$selectedDominion->daily_land)
                                <span class="label label-primary pull-right">L</span>
                            @endif
                        </span>
                    </a>
                </li>

                <!--
                <li class="header">DOMINION</li>
                -->

                <!-- Hide Explore Land from cannot_explore races -->
                @if (!(bool)$selectedDominion->race->getPerkValue('cannot_explore'))
                <li class="{{ Route::is('dominion.explore') ? 'active' : null }}"><a href="{{ route('dominion.explore') }}"><i class="ra ra-telescope ra-fw"></i> <span>Explore</span></a></li>
                @endif

                <!-- Hide Construct Buildings from cannot_construct races -->
                @if (!(bool)$selectedDominion->race->getPerkValue('cannot_construct'))
                <li class="{{ Route::is('dominion.construct') ? 'active' : null }}"><a href="{{ route('dominion.construct') }}"><i class="fa fa-home fa-fw"></i> <span>Buildings</span></a></li>
                @endif

                <li class="{{ Route::is('dominion.rezone') ? 'active' : null }}"><a href="{{ route('dominion.rezone') }}"><i class="fa fa-refresh fa-fw"></i> <span>Re-zone</span></a></li>

                <!-- Hide Castle from cannot_improve_castle races -->
                @if (!(bool)$selectedDominion->race->getPerkValue('cannot_improve_castle'))
                <li class="{{ Route::is('dominion.improvements') ? 'active' : null }}">
                  <a href="{{ route('dominion.improvements') }}">
                    <i class="fa fa-arrow-up fa-fw"></i> <span>
                      @if((bool)$selectedDominion->race->getPerkValue('tissue_improvement'))
                      Feeding
                      @else
                      Improvements
                      @endif
                      </span>
                  </a>
                </li>
                @endif

                <!-- NATIONAL BANK -->
                <li class="{{ Route::is('dominion.bank') ? 'active' : null }}"><a href="{{ route('dominion.bank') }}">
                @if ($selectedDominion->race->name == 'Demon')
                  <i class="ra ra-daggers ra-fw"></i> <span>Sacrificial Altar
                @else
                  <i class="fa fa-money fa-fw"></i> <span>Exchange
                @endif
                </span></a></li>

                <!-- TECHS -->
                @if (!(bool)$selectedDominion->race->getPerkValue('cannot_tech'))
                <li class="{{ Route::is('dominion.techs') ? 'active' : null }}"><a href="{{ route('dominion.techs') }}"><i class="fa fa-flask fa-fw"></i> <span>Advancements</span></a></li>

                @php

                @endphp

                @endif

                <!--
                <li class="header">BLACK OPS</li>
                -->
                <li class="{{ Route::is('dominion.military') ? 'active' : null }}"><a href="{{ route('dominion.military') }}"><i class="ra ra-sword ra-fw"></i> <span>Military</span></a></li>

                <!-- Hide Invade from cannot_invade races -->
                @if (!(bool)$selectedDominion->race->getPerkValue('cannot_invade'))
                <li class="{{ Route::is('dominion.invade') ? 'active' : null }}"><a href="{{ route('dominion.invade') }}"><i class="ra ra-crossed-swords ra-fw"></i> <span>Invade</span></a></li>
                @endif

                <li class="{{ Route::is('dominion.magic') ? 'active' : null }}"><a href="{{ route('dominion.magic') }}"><i class="ra ra-fairy-wand ra-fw"></i> <span>Magic</span></a></li>
                <li class="{{ Route::is('dominion.espionage') ? 'active' : null }}"><a href="{{ route('dominion.espionage') }}"><i class="fa fa-user-secret fa-fw"></i> <span>Espionage</span></a></li>
                <li class="{{ Route::is('dominion.search') ? 'active' : null }}"><a href="{{ route('dominion.search') }}"><i class="fa fa-search fa-fw"></i> <span>Search</span></a></li>

                <!--
                <li class="header">COMMS</li>
                -->
                <li class="{{ Route::is('dominion.council*') ? 'active' : null }}"><a href="{{ route('dominion.council') }}"><i class="fa fa-group ra-fw"></i> <span>Council</span> {!! $councilUnreadCount > 0 ? ('<span class="pull-right-container"><small class="label pull-right bg-green">' . $councilUnreadCount . '</small></span>') : null !!}</a></li>
                <li class="{{ Route::is('dominion.op-center*') ? 'active' : null }}"><a href="{{ route('dominion.op-center') }}"><i class="fa fa-globe ra-fw"></i> <span>Op Center</span></a></li>
                <li class="{{ Route::is('dominion.government') ? 'active' : null }}"><a href="{{ route('dominion.government') }}"><i class="fa fa-university fa-fw"></i> <span>Government</span></a></li>

                <!--
                <li class="header">REALM</li>
                -->
                <li class="{{ Route::is('dominion.realm') ? 'active' : null }}"><a href="{{ route('dominion.realm') }}"><i class="ra ra-circle-of-circles ra-fw"></i> <span>The World</span></a></li>
                <li class="{{ Route::is('dominion.town-crier') ? 'active' : null }}"><a href="{{ route('dominion.town-crier') }}"><i class="fa fa-newspaper-o ra-fw"></i> <span>World News</span></a></li>
                <li class="{{ Route::is('dominion.rankings') ? 'active' : null }}"><a href="{{ route('dominion.rankings') }}"><i class="fa fa-trophy ra-fw"></i> <span>Rankings</span></a></li>

                {{--<li class="header">MISC</li>--}}

                @if (app()->environment() !== 'production')
                    <li class="header">SECRET</li>
                    <li class="{{ Request::is('dominion/debug') ? 'active' : null }}"><a href="{{ url('dominion/debug') }}"><i class="ra ra-dragon ra-fw"></i> <span>Debug Page</span></a></li>
                @endif

            @else

                <li class="{{ Route::is('dashboard') ? 'active' : null }}"><a href="{{ route('dashboard') }}"><i class="ra ra-capitol ra-fw"></i> <span>Select your Dominion</span></a></li>

            @endif

{{--            <li class="{{ Route::is('dashboard') ? 'active' : null }}"><a href="{{ route('dashboard') }}"><i class="fa fa-dashboard fa-fw"></i> <span>Dashboard</span></a></li>--}}
        </ul>

    </section>
</aside>
