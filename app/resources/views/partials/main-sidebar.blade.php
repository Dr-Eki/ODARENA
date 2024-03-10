
<aside class="main-sidebar">
    <section class="sidebar">

        @if (isset($selectedDominion))
            @php
                $roundSettings = $selectedDominion->round->settings;            
            @endphp
    

            <div class="user-panel">
                <div class="pull-left image">
                    <img src="{{ Auth::user()->getAvatarUrl() }}" class="img-circle" alt="{{ Auth::user()->display_name }}">
                </div>
                <div class="pull-left info">
                    <p><a href="{{ route('dominion.status') }}" style="color:#fff;">{{ $selectedDominion->name }}</a></p>
                    <a href="{{ route('dominion.realm') }}">{{ $selectedDominion->realm->name }} (#{{ $selectedDominion->realm->number }})</a>
                </div>
            </div>
        @endif

        <ul class="sidebar-menu" data-widget="tree">
            @if (isset($selectedDominion))
                <li class="{{ Route::is('dominion.status') ? 'active' : null }}">
                    <a href="{{ route('dominion.status') }}">
                        <i class="fas fa-map-pin fa-fw"></i>
                        <span>Status</span>

                        @if(($unreadNotificationsCount = $selectedDominion->unreadNotifications->count()))
                            <span class="label label-warning pull-right"><i class="fas fa-scroll fa-fw"></i>&nbsp;{{ number_format($unreadNotificationsCount) }}</span>
                        @endif
                    </a>
                
                </li>

                


                <li class="{{ Route::is('dominion.resources') ? 'active' : null }}">
                    <a href="{{ route('dominion.resources') }}">
                        <i class="ra ra-mining-diamonds ra-fw"></i>
                        <span>Resources</span>
                        @if($resourceCalculator->isOnBrinkOfStarvation($selectedDominion))
                            <span class="label label-danger pull-right"><i class="ra ra-apple"></i></span>
                        @endif
                    </a>
                </li>

                <li class="{{ Route::is('dominion.land') ? 'active' : null }}">
                    <a href="{{ route('dominion.land') }}">
                        <i class="fa fa-map fa-fw"></i>
                        <span>Land</span>
                        @if (!$selectedDominion->daily_land and $selectedDominion->protection_ticks == 0 and $selectedDominion->round->hasStarted())
                            <span class="label label-primary pull-right"><i class="fa fa-plus"></i></span>
                        @endif
                    </a>
                </li>

                @if ($roundSettings['buildings'] and !$selectedDominion->race->getPerkValue('cannot_build'))
                    <li class="{{ Route::is('dominion.buildings') ? 'active' : null }}">
                        <a href="{{ route('dominion.buildings') }}">
                            <i class="fa fa-home fa-fw"></i>
                            <span>Buildings</span>
                            @if(($barrenLand = $landCalculator->getTotalBarrenLand($selectedDominion)))
                                <span class="label label-danger pull-right">{{ number_format($barrenLand) }}</span>
                            @endif
                        </a>
                    </li>
                @endif


                @if ($roundSettings['improvements'] and !$selectedDominion->race->getPerkValue('cannot_improve'))
                    <li class="{{ Route::is('dominion.improvements') ? 'active' : null }}">
                        <a href="{{ route('dominion.improvements') }}">
                            <i class="fas fa-arrow-up fa-fw"></i><span>Improvements</span>
                        </a>
                    </li>
                @endif

                @if ($roundSettings['advancements'] and !$selectedDominion->race->getPerkValue('cannot_tech'))
                    <li class="{{ Route::is('dominion.advancements') ? 'active' : null }}">
                        <a href="{{ route('dominion.advancements') }}"><i class="fas fa-layer-group fa-fw"></i> <span>Advancements</span>

                        @if($advancementCalculator->maxLevelAfforded($selectedDominion))
                            <span class="pull-right-container"><small class="label pull-right bg-green">{{ $advancementCalculator->maxLevelAfforded($selectedDominion) }}</small></span></a>
                        @else
                            </a>
                        @endif
                    </li>
                @endif

                @if ($roundSettings['research'] and !$selectedDominion->race->getPerkValue('cannot_research'))
                    <li class="{{ Route::is('dominion.research') ? 'active' : null }}">
                        <a href="{{ route('dominion.research') }}"><i class="fas fa-flask fa-fw"></i> <span>Research</span>

                            @if(($freeResearchSlots = $researchCalculator->getFreeResearchSlots($selectedDominion)) > 0)
                                <span class="pull-right-container"><small class="label pull-right bg-red">{{ $freeResearchSlots }}</small></span>
                            @elseif($researchCalculator->getOngoingResearchCount($selectedDominion) !== 0)
                                <span class="pull-right-container"><small class="label pull-right bg-yellow">{{ $researchCalculator->getTicksUntilNextResearchCompleted($selectedDominion) }}</small></span>
                            @endif
                        </a>
                    </li>
                @endif

                <li class="{{ Route::is('dominion.military') ? 'active' : null }}"><a href="{{ route('dominion.military') }}"><i class="ra ra-sword ra-fw"></i> <span>Military</span></a></li>

                @if ($roundSettings['invasions'] and !$selectedDominion->race->getPerkValue('cannot_invade'))
                    <li class="{{ Route::is('dominion.invade') ? 'active' : null }}"><a href="{{ route('dominion.invade') }}"><i class="ra ra-crossed-swords ra-fw"></i> <span>Invade</span></a></li>
                @endif

                @if ($roundSettings['invasions'] and $selectedDominion->race->getPerkValue('can_desecrate'))
                    <li class="{{ Route::is('dominion.desecrate') ? 'active' : null }}"><a href="{{ route('dominion.desecrate') }}"><i class="ra ra-tombstone ra-fw"></i> <span>Desecrate</span></a></li>
                @endif

                @if (in_array($selectedDominion->round->mode, ['artefacts', 'artefacts-packs']))
                    <li class="{{ Route::is('dominion.artefacts') ? 'active' : null }}"><a href="{{ route('dominion.artefacts') }}"><i class="ra ra-alien-fire"></i> <span>Artefacts</span></a></li>
                @endif

                @if ($roundSettings['expeditions'] and !$selectedDominion->race->getPerkValue('cannot_send_expeditions'))
                    <li class="{{ Route::is('dominion.expedition') ? 'active' : null }}"><a href="{{ route('dominion.expedition') }}"><i class="fas fa-drafting-compass fa-fw"></i> <span>Expedition</span></a></li>
                @endif

                @if ($roundSettings['theft'] and !$selectedDominion->race->getPerkValue('cannot_steal'))
                    <li class="{{ Route::is('dominion.theft') ? 'active' : null }}"><a href="{{ route('dominion.theft') }}"><i class="fas fa-hand-lizard fa-fw"></i> <span>Theft</span></a></li>
                @endif

                @if ($roundSettings['sabotage'] and !$selectedDominion->race->getPerkValue('cannot_sabotage'))
                    <li class="{{ Route::is('dominion.sabotage') ? 'active' : null }}"><a href="{{ route('dominion.sabotage') }}"><i class="fa fa-user-secret fa-fw"></i> <span>Sabotage</span></a></li>
                @endif

                @if ($roundSettings['sorcery'] and !$selectedDominion->race->getPerkValue('cannot_perform_sorcery'))
                    <li class="{{ Route::is('dominion.sorcery') ? 'active' : null }}"><a href="{{ route('dominion.sorcery') }}"><i class="fas fa-hat-wizard fa-fw"></i> <span>Sorcery</span></a></li>
                @endif

                @if($roundSettings['spells'])
                    <li class="{{ Route::is('dominion.magic') ? 'active' : null }}"><a href="{{ route('dominion.magic') }}"><i class="ra ra-fairy-wand ra-fw"></i> <span>Magic</span></a></li>
                @endif

                <li class="{{ Route::is('dominion.search') ? 'active' : null }}"><a href="{{ route('dominion.search') }}"><i class="fa fa-search fa-fw"></i> <span>Search</span></a></li>

                @if ($roundSettings['decrees'] and !$selectedDominion->race->getPerkValue('cannot_issue_decrees'))
                    <li class="{{ Route::is('dominion.decrees') ? 'active' : null }}"><a href="{{ route('dominion.decrees') }}"><i class="fas fa-gavel fw-fw"></i> <span>Decrees</span></a></li>
                @endif

                @if($roundSettings['deities'] and !$selectedDominion->race->getPerkValue('cannot_submit_to_deity'))
                    <li class="{{ Route::is('dominion.deity') ? 'active' : null }}"><a href="{{ route('dominion.deity') }}"><i class="fas fa-pray fa-fw"></i> <span>Deity</span></a></li>
                @endif

                @if(!$selectedDominion->race->getPerkValue('cannot_vote'))
                    <li class="{{ Route::is('dominion.government') ? 'active' : null }}"><a href="{{ route('dominion.government') }}"><i class="fa fa-university fa-fw"></i> <span>Government</span></a></li>
                @endif

                <li class="{{ Route::is('dominion.realm') ? 'active' : null }}">
                    <a href="{{ route('dominion.realm') }}">
                        <i class="fas fa-map-signs fa-fw"></i>
                        <span>The World</span>
                        <span class="pull-right-container">
                            <span href="#realms" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-angle-down"></i></span>
                            <ul class="collapse" id="realms">
                                <li>&nbsp;<a href="{{ route('dominion.realm.all') }}">All</a></li>
                                @foreach($selectedDominion->round->realms()->get() as $realm)
                                    @if(in_array($selectedDominion->round->mode, ['packs','packs-duration','artefacts-packs']))
                                        <li>&nbsp;<a href="{{ route('dominion.realm', $realm->number) }}">{{ $realmHelper->getRealmPackName($realm) }} (# {{ $realm->number }})</a></li>
                                    @else
                                        <li>&nbsp;<a href="{{ route('dominion.realm', $realm->number) }}">{{ $realmHelper->getAlignmentNoun($realm->alignment) }} (# {{ $realm->number }})</a></li>
                                    @endif
                                @endforeach
                            </ul>
                        </span>
                    </a>
                </li>

                <li id="world-news" class="{{ Route::is('dominion.world-news') ? 'active' : null }}">
                    <a href="{{ route('dominion.world-news') }}">
                        <i class="far fa-newspaper fa-fw"></i>
                        <span>World News</span>
                        @if($newsUnreadCount > 0)
                            @php
                                $newsUnreadCount = $newsUnreadCount > 99 ? '99+' : $newsUnreadCount;
                            @endphp
                            <span class="pull-right-container"><small class="label pull-right bg-green">{{ $newsUnreadCount }}</small></span>
                        @endif
                    </a>
                </li>

                <li class="{{ Route::is('dominion.council*') ? 'active' : null }}"><a href="{{ route('dominion.council') }}"><i class="fas fa-comments ra-fw"></i>
                    <span>{{ $realmHelper->getAlignmentCouncilTerm($selectedDominion->realm->alignment) }}</span>&nbsp;
                    {!! $councilUnreadCount > 0 ? ('<span class="pull-right-container"><small class="label pull-right bg-green">' . $councilUnreadCount . '</small></span>') : null !!}</a>
                </li>

                {{--
                <li class="{{ Route::is('dominion.notes') ? 'active' : null }}">
                    <a href="{{ route('dominion.notes') }}">
                        <i class="ra ra-quill-ink ra-fw"></i>Notes
                    </a>
                </li>
                --}}

                @if($selectedDominion->pack)
                    <li class="{{ Route::is('dominion.pack') ? 'active' : null }}">
                        <a href="{{ route('dominion.pack') }}">
                            <i class="ra ra-double-team ra-fw"></i>Pack
                        </a>
                    </li>
                @endif
            @else

                <li class="{{ Route::is('dashboard') ? 'active' : null }}"><a href="{{ route('dashboard') }}"><i class="ra ra-capitol ra-fw"></i> <span>Select your Dominion</span></a></li>

            @endif
        </ul>
    </section>
</aside>
