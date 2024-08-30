@extends('layouts.master')
@section('title', 'The World')

@section('content')
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="row">
                    @if(in_array($selectedDominion->round->mode, ['standard','standard-duration','artefacts']))
                        <div class="col-sm-3 text-center">
                            @if($realm->number === 1)
                                <span style="display:block; font-weight: bold;">Barbarians</span>
                            @else
                                <a href="/dominion/realm/1"><span style="display:block;" data-toggle="tooltip" data-placement="top" title="{{ $realmNames[1] }}">Barbarians</span></a>
                            @endif
                        </div>
                        <div class="col-sm-3 text-center">
                            @if($realm->number === 2)
                                <span style="display:block; font-weight: bold;">Commonwealth</span>
                            @else
                                <a href="/dominion/realm/2"><span style="display:block;" data-toggle="tooltip" data-placement="top" title="{{ $realmNames[2] }}">Commonwealth</span></a>
                            @endif
                        </div>
                        <div class="col-sm-3 text-center">
                            @if($realm->number === 3)
                                <span style="display:block; font-weight: bold;">The Empire</span>
                            @else
                                <a href="/dominion/realm/3"><span style="display:block;" data-toggle="tooltip" data-placement="top" title="{{ $realmNames[3] }}">The Empire</span></a>
                            @endif
                        </div>
                        <div class="col-sm-3 text-center">
                            @if($realm->number === 4)
                                <span style="display:block; font-weight: bold;">Independent</span>
                            @else
                                <a href="/dominion/realm/4"><span style="display:block;" data-toggle="tooltip" data-placement="top" title="{{ $realmNames[4] }}">Independent</span></a>
                            @endif
                        </div>
                    @elseif(in_array($selectedDominion->round->mode, ['deathmatch','deathmatch-duration']))
                        <div class="col-sm-6 text-center">
                            @if($realm->number === 1)
                                <span style="display:block; font-weight: bold;">Barbarians</span>
                            @else
                                <a href="/dominion/realm/1"><span style="display:block;" data-toggle="tooltip" data-placement="top" title="{{ $realmNames[1] }}">Barbarians</span></a>
                            @endif
                        </div>
                        <div class="col-sm-6 text-center">
                            @if($realm->number === 2)
                                <span style="display:block; font-weight: bold;">Players</span>
                            @else
                                <a href="/dominion/realm/2"><span style="display:block;" data-toggle="tooltip" data-placement="top" title="{{ $realmNames[2] }}">Players</span></a>
                            @endif
                        </div>
                    @elseif(in_array($selectedDominion->round->mode, ['factions','factions-duration']))
                        @foreach($selectedDominion->round->realms as $roundRealm)
                            <div class="col-sm-{{ round(12 / count($selectedDominion->round->realms)) }} text-center">
                                @php
                                    $realmRace = ($roundRealm->alignment == 'npc' ? OpenDominion\Models\Race::where('alignment', $roundRealm->alignment)->first() : OpenDominion\Models\Race::where('key', $roundRealm->alignment)->first());
                                @endphp

                                @if($realm->number === $roundRealm->number)
                                    <span style="font-weight: bold;">{{ $realmRace->name }}</span>
                                @else
                                    <a href="/dominion/realm/{{ $roundRealm->number }}"><span data-toggle="tooltip" data-placement="top" title="{{ $realmNames[$roundRealm->number] }}">{{ $realmRace->name }}</span></a>
                                @endif
                                <small class="text-muted" data-toggle="tooltip" data-placement="top" title="Number of dominions in this realm">({{ $roundRealm->dominions->count() }})</small>
                            </div>
                        @endforeach
                    @elseif(in_array($selectedDominion->round->mode, ['packs','packs-duration','artefacts-packs']))
                        @foreach($selectedDominion->round->realms as $roundRealm)
                            <div class="col-sm-{{ round(12 / count($selectedDominion->round->realms)) }} text-center">
                                @php
                                    $realmName = $roundRealm->alignment == 'npc' ? 'Barbarian' : $realmHelper->getRealmPackName($roundRealm);
                                @endphp

                                @if($realm->number === $roundRealm->number)
                                    <span style="font-weight: bold;">{{ $realmName }}</span>
                                @else
                                    <a href="/dominion/realm/{{ $roundRealm->number }}"><span data-toggle="tooltip" data-placement="top" title="{{ $roundRealm->name }}">{{ $realmName }}</span></a>
                                @endif
                                <small class="text-muted" data-toggle="tooltip" data-placement="top" title="Number of dominions in this realm">({{ $roundRealm->dominions->count() }})</small>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="box-header with-border">
                  <div class="row">
                      <div class="col-xs-12 text-center">
                          @if($realm->alignment == 'good')
                              <img src="{{ asset('assets/app/images/commonwealth.svg') }}" class="img-responsive" style="height: 42px;display: inline;" alt="The Commonwealth">
                          @elseif($realm->alignment == 'evil')
                              <img src="{{ asset('assets/app/images/empire.svg') }}" class="img-responsive" style="height: 42px;display: inline;" alt="The Empire">
                          @elseif($realm->alignment == 'independent')
                              <img src="{{ asset('assets/app/images/independent.svg') }}" class="img-responsive" style="height: 42px;display: inline;" alt="Independent Dominions">
                          @elseif($realm->alignment == 'npc')
                              <img src="{{ asset('assets/app/images/barbarian.svg') }}" class="img-responsive" style="height: 42px;display: inline;" alt="The Barbarian Horde">
                          @elseif($realm->alignment == 'players')
                              <img src="{{ asset('assets/app/images/odarena-icon.png') }}" class="img-responsive" style="height: 42px;display: inline;" alt="ODARENA">
                          @endif
                      </div>
                      <div class="col-xs-12 text-center">
                          @if($realm->round->mode == 'standard' or $realm->round->mode == 'standard-duration')
                              <h4>The {{ $alignmentNoun }}</h4>
                          @elseif($realm->round->mode == 'deathmatch' or $realm->round->mode == 'deathmatch-duration')
                              <h4>{{ $realm->alignment == 'npc' ? 'The' : '' }} {{ $alignmentNoun }}</h4>
                          @endif
                          <span>
                              @if(isset($realm->name) and !in_array($realm->name, $defaultRealmNames))
                                  <em>{{ $realm->name }}</em>
                              @endif
                          </span>
                      </div>
                  </div>
            </div>
            <div class="box-body table-responsive no-padding">

                <table class="table">
                    <colgroup>
                        <col width="50">
                        <col>
                        <col width="100">
                        <col width="100">
                        <col width="100">
                        <col width="100">
                        <col width="100">
                        <col width="100">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Dominion</th>
                            <th class="text-center">Faction</th>
                            <th class="text-center">Deity</th>
                            <th class="text-center">Land</th>
                            <th class="text-center">Networth</th>
                            <th class="text-center">DP</th>
                            <th class="text-center">Units<br>Returning</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $i = 0;
                        @endphp
                        @foreach($dominions as $dominion)
                            @php
                                $i++;
                            @endphp

                            @if ($dominion === null)
                            @else
                              @if ($dominion->isLocked())
                                  <tr style="text-decoration:line-through; color: #666">
                              @else
                                  <tr>
                              @endif
                                    <td class="text-center">{{ $i }} </td>
                                    <td>
                                        @if ($dominion->isLocked())
                                            <span data-toggle="tooltip" data-placement="top" title="<strong>This dominion has been locked.</strong><br>Reason: <em>{{ $dominion->getLockedReason($dominion->is_locked) }}</em>">
                                            <i class="fa fa-lock fa-lg text-grey" title=""></i>
                                            </span>
                                        @endif

                                        @if ($spellCalculator->isSpellActive($dominion, 'rainy_season'))
                                            <span data-toggle="tooltip" data-placement="top" title="Rainy Season">
                                            <i class="ra ra-droplet fa-lg text-blue"></i>
                                            </span>
                                        @endif

                                        @if ($spellCalculator->isSpellActive($dominion, 'primordial_wrath'))
                                            <span data-toggle="tooltip" data-placement="top" title="Primordial Wrath">
                                            <i class="ra ra-monster-skull fa-lg text-red" title=""></i>
                                            </span>
                                        @endif

                                        @if ($dominionHelper->isEnraged($dominion))
                                            <span data-toggle="tooltip" data-placement="top" title="Enraged">
                                            <i class="ra ra-explosion fa-lg text-red" title=""></i>
                                            </span>
                                        @endif

                                        @if ($spellCalculator->isSpellActive($dominion, 'ragnarok'))
                                            <span data-toggle="tooltip" data-placement="top" title="Ragnarök">
                                            <i class="ra ra-blast fa-lg text-red" title=""></i>
                                            </span>
                                        @endif

                                        @if ($spellCalculator->isSpellActive($dominion, 'stasis'))
                                            <span data-toggle="tooltip" data-placement="top" title="Stasis">
                                            <i class="ra ra-emerald fa-lg text-purple"></i>
                                            </span>
                                        @endif

                                        @if ($spellCalculator->isAnnexed($dominion))
                                            <span data-toggle="tooltip" data-placement="top" title="Annexed by {{ $spellCalculator->getAnnexer($dominion)->name }}!<br>Current raw military power: {{ number_format($militaryCalculator->getRawMilitaryPowerFromAnnexedDominion($dominion)) }}">
                                            <i class="ra ra-castle-flag fa-lg text-black"></i>
                                            </span>
                                        @endif

                                        @if ($spellCalculator->hasAnnexedDominions($dominion))
                                            <span data-toggle="tooltip" data-placement="top" title="Has annexed Barbarians!<br>Current additional raw military power: {{ number_format($militaryCalculator->getRawMilitaryPowerFromAnnexedDominions($dominion)) }}">
                                            <i class="ra ra-castle-flag fa-lg text-black"></i>
                                            </span>
                                        @endif

                                        @if ($dominion->isMonarch())
                                            <span data-toggle="tooltip" data-placement="top" title="Governor of The Realm">
                                            <i class="fa fa-star fa-lg text-orange"></i>
                                            </span>
                                        @endif

                                        @if ($protectionService->isUnderProtection($dominion))
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $dominion->protection_ticks }} protection tick(s) left">
                                            <i class="ra ra-shield ra-lg text-aqua"></i>
                                            </span>
                                        @endif

                                        <span data-toggle="tooltip" data-placement="top" title="{{ $realmHelper->getDominionHelpString($dominion, $selectedDominion) }}">
                                            <a href="{{ route('dominion.insight.show', $dominion) }}">
                                                @if($dominion->id == $selectedDominion->id)
                                                    <strong>{{ $dominion->name }}</strong>
                                                @else
                                                    {{ $dominion->name }}
                                                @endif
                                            </a>
                                        </span>

                                          @if($dominion->isAbandoned())
                                            <span data-toggle="tooltip" data-placement="top" title="This dominion has been abandoned by its ruler" class="label label-warning"><span>Abandoned</span></span>
                                        @else
                                            @if ($isOwnRealm && $dominion->round->isActive() && $dominion->user->isOnline() and $dominion->id !== $selectedDominion->id)
                                                <span class="label label-success">Online</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span data-toggle="tooltip" data-placement="top" title="{!! $raceHelper->getRacePerksHelpString($dominion->race) !!}">
                                            {{ $dominion->race->name }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($dominion->hasDeity())
                                            @php
                                                $perksList = '<ul>';
                                                $perksList .= '<li>Devotion: ' . number_format($dominion->devotion->duration) . ' ' . Str::plural('tick', $dominion->devotion->duration) . '</li>';
                                                $perksList .= '<li>Range multiplier: ' . $dominion->deity->range_multiplier . 'x</li>';
                                                foreach($deityHelper->getDeityPerksString($dominion->deity, $dominion->getDominionDeity()) as $effect)
                                                {
                                                    $perksList .= '<li>' . ucfirst($effect) . '</li>';
                                                }
                                                $perksList .= '<ul>';
                                            @endphp
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $perksList }}" >{{ $dominion->deity->name }}</span>

                                        @elseif($dominion->hasPendingDeitySubmission())
                                            @if($isOwnRealm)
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $dominion->getPendingDeitySubmission()->name }} in {{ $dominion->getPendingDeitySubmissionTicksLeft() }} {{ Str::plural('tick', $dominion->getPendingDeitySubmissionTicksLeft()) }}" class="text-muted"><i class="fas fa-pray"></i></span>
                                            @else
                                                <span class="text-muted"><i class="fas fa-pray"></i></span>
                                            @endif
                                        @else
                                            &mdash;
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($dominion->land) }}</td>
                                    <td class="text-center">{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}</td>
                                    <td class="text-center">
                                        @if($dominion->getSpellPerkValue('fog_of_war') or !$dominion->round->hasStarted())
                                            <em class='text-muted'>Unknown</em>
                                        @else
                                            {{ number_format($militaryCalculator->getDefensivePower($dominion)) }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($militaryCalculator->hasReturningUnits($dominion))
                                            <span class="label label-success">Yes</span>
                                        @else
                                            <span class="text-gray">No</span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>

        @if($realm->alignment === 'npc' and $realm->round->hasStarted())
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-uncertainty"></i> Barbarian AI</h3>
                </div>

                <div class="box-body">
                      <div class="row">
                          <ul>
                          @foreach($barbarianSettings as $setting => $value)
                              <li>{{ $setting }}: <code>{{ $value }}</code></li>
                          @endforeach
                          <li>NPC_MODIFIER: <code>rand(500,1000)</code>, assigned to each Barbarian at registration</li>
                          <li>CHANCE_TO_HIT: <code>1 / ([Chance To Hit Constant] - (14 - min([Current Day], 14))) = {{ 1/($barbarianSettings['CHANCE_TO_HIT_CONSTANT'] - (14 - min($realm->round->start_date->subDays(1)->diffInDays(now()),14))) }}</code></li>
                          <li>DPA_TARGET: <code>[DPA Constant] + (([Ticks Into The Round] * [DPA Per Tick]) + ([Times Invaded] * [DPA_PER_TIMES_INVADED])) * [NPC Modifier] = {{ $barbarianSettings['DPA_CONSTANT'] }} + ({{ $realm->round->ticks }} * {{ $barbarianSettings['DPA_PER_TICK'] }})  * [NPC Modifier] = ({{ $barbarianSettings['DPA_CONSTANT'] + ($realm->round->ticks * $barbarianSettings['DPA_PER_TICK']) }} + ([Times Invaded] * {{ $barbarianSettings['DPA_PER_TIMES_INVADED'] }} ) * [NPC Modifier]</code></li>
                          <li>OPA_TARGET: <code>[DPA] * [OPA Multiplier]</code></li>
                          </ul>
                      </div>
                </div>

            </div>
        @endif
    </div>

    <div class="col-sm-12 col-md-3">
        @if($realm->hasArtefacts())
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Artefacts</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <colgroup>
                            <col width="50%">
                            <col width="50%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Artefact</th>
                                <th>Aegis</th>
                            </tr>
                        </thead>
                        @foreach($realm->artefacts as $artefact)
                            @php
                                $realmArtefact = OpenDominion\Models\RealmArtefact::where('realm_id', $realm->id)->where('artefact_id', $artefact->id)->firstOrFail();
                            @endphp

                            <tr>
                                <td>
                                    <span data-toggle="tooltip" data-placement="top" title="{{ $artefactHelper->getArtefactTooltip($artefact) }}">
                                        {{ $artefact->name }}
                                    </span>
                                </td>
                                <td>{{ number_format($realmArtefact->power) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif

        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-xs-12">
                        <div class="box-body table-responsive no-padding">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <tr>
                                  <td>Dominions:</td>
                                  <td>{{ number_format($dominions->count()) }}</td>
                                </tr>
                                <tr>
                                  <td>Victories:</td>
                                  <td>{{ number_format($realmDominionsStats['victories']) }}</td>
                                </tr>
                                  <tr>
                                    <td>Prestige:</td>
                                    <td>{{ number_format($realmDominionsStats['prestige']) }}</td>
                                  </tr>
                                  <td>Total land:</td>
                                  <td>{{ number_format($landCalculator->getTotalLandForRealm($realm)) }}</td>
                                </tr>
                                  <td>Land conquered:</td>
                                  <td>{{ number_format($realmDominionsStats['total_land_conquered']) }}</td>
                                </tr>
                                <tr>
                                  <td>Land explored:</td>
                                  <td>{{ number_format($realmDominionsStats['total_land_explored']) }}</td>
                                </tr>
                                <tr>
                                  <td>Land lost:</td>
                                  <td>{{ number_format($realmDominionsStats['total_land_lost']) }}</td>
                                </tr>
                                <tr>
                                  <td>Networth:</td>
                                  <td>{{ number_format($networthCalculator->getRealmNetworth($realm)) }}</td>
                                </tr>
                                @if($realm->getAllies()->count() > 0)
                                    <tr>
                                        <td>Allies:</td>
                                        <td>
                                            @foreach($realm->getAllies() as $alliedRealm)
                                                <a href="{{ route('dominion.realm', [$alliedRealm->number]) }}">{{ $alliedRealm->name }} (# {{ $alliedRealm->number }})</a><br>
                                            @endforeach
                                        </td>
                                    </tr>
                                @elseif(in_array($realm->round->mode, ['factions', 'factions-duration']))
                                    <tr>
                                        <td>Allies:</td>
                                        <td>None</td>
                                    </tr>
                                @endif

                                @if(in_array($realm->round->mode, ['packs', 'packs-duration','artefacts-packs']) and $realm->alignment !== 'npc')
                                    <tr>
                                        <td>Pack status:</td>
                                        <td>{{ $packHelper->getPackStatusString($realm->pack) }}</td>
                                    </tr>
                                @endif

                                @if($realm->alignment === 'evil')
                                    <tr>
                                        <td>Imperial Crypt:</td>
                                        <td>{{ number_format($resourceCalculator->getRealmAmount($realm, 'body')) }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>

                      <p class="text-center"><a href="{{ route('dominion.world-news', [$realm->number]) }}">Read the News from the {{ $alignmentNoun }}</a></p>
                    </div>
                </div>
            </div>
        </div>
        @include('partials.dominion.watched-dominions')
    </div>


</div>
@endsection
