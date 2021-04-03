@extends('layouts.master')

{{--
@section('page-header', 'The World')
--}}

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-circle-of-circles"></i> {{ $realm->name }} (#{{ $realm->number }})</h3>
                </div>
                <div class="box-body table-responsive no-padding">

                    <table class="table">
                        <colgroup>
                            <col width="50">
                            <col>
                            @if ($isOwnRealm && $selectedDominion->pack !== null)
                                <col width="200">
                            @endif
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
                                <th class="text-center">Land</th>
                                <th class="text-center">Networth</th>
                                <th class="text-center">Units Returning</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < $round->realm_size; $i++)
                                @php
                                    $dominion = $dominions->get($i);
                                @endphp

                                @if ($dominion === null)
                                @else
                                  @if ($dominion->isLocked())
                                    <tr style="text-decoration:line-through; color: #666">
                                  @else
                                    <tr>
                                  @endif
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>
                                            @if ($dominion->isLocked())
                                                <span data-toggle="tooltip" data-placement="top" title="This dominion has been locked.<br>Reason: <strong>{{ $dominion->getLockedReason($dominion->is_locked) }}</strong>">
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

                                            @if ($guardMembershipService->isEliteGuardMember($dominion))
                                                <span data-toggle="tooltip" data-placement="top" title="Warriors League">
                                                <i class="ra ra-heavy-shield ra-lg text-yellow"></i>
                                                </span>
                                            @elseif ($guardMembershipService->isBarbarianGuardMember($dominion))
                                                <span data-toggle="tooltip" data-placement="top" title="Ib-Tham's Guard">
                                                <i class="ra ra-heavy-shield ra-lg text-muted"></i>
                                                </span>
                                            @endif

                                            @if ($dominion->id === $selectedDominion->id)
                                                <span data-toggle="tooltip" data-placement="top" title="<em>{{ $dominion->title->name }}</em> {{ $dominion->ruler_name }} &mdash; That's you, chief!">
                                                <b>{{ $dominion->name }}</b>
                                                </span>
                                            @else
                                                <span data-toggle="tooltip" data-placement="top" title="<em>{{ $dominion->title->name }}</em> {{ $dominion->ruler_name }} @if($dominion->realm->id === $selectedDominion->realm->id) {{ '<br>Morale: ' . $dominion->morale . '%' }} @endif @if($dominion->race->name === 'Barbarian') {{ '<br>NPC modifier: ' . $dominion->npc_modifier/1000 . '<br>Times invaded: ' . $dominion->stat_defending_failures }} @endif ">
                                                @if ($isOwnRealm)
                                                    {{ $dominion->name }}
                                                @else
                                                    <a href="{{ route('dominion.op-center.show', $dominion) }}">{{ $dominion->name }}</a>
                                                @endif
                                                </span>
                                            @endif

                                            @if($dominion->isAbandoned())
                                                <span data-toggle="tooltip" data-placement="top" title="This dominion has been abandoned by its ruler" class="label label-warning"><span>Abandoned</span></span>
                                            @else
                                                @if ($isOwnRealm && $dominion->round->isActive() && $dominion->user->isOnline() and $dominion->id !== $selectedDominion->id)
                                                    <span class="label label-success">Online</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{ $dominion->race->name }}
                                        </td>
                                        <td class="text-center">{{ number_format($landCalculator->getTotalLand($dominion, true)) }}</td>
                                        <td class="text-center">{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}</td>
                                        <td class="text-center">
                                            @if ($militaryCalculator->hasReturningUnits($dominion))
                                                <span class="label label-success">Yes</span>
                                            @else
                                                <span class="text-gray">No</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endfor
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
                              <li>DPA_TARGET: <code>[DPA Constant] + (([Hours Into The Round] * [DPA Per Hour]) + ([Times Invaded] * [DPA_PER_TIMES_INVADED])) * [NPC Modifier] = {{ $barbarianSettings['DPA_CONSTANT'] }} + ({{ $hoursIntoTheRound }} * {{ $barbarianSettings['DPA_PER_HOUR'] }})  * [NPC Modifier] = ({{ $barbarianSettings['DPA_CONSTANT'] + ($hoursIntoTheRound * $barbarianSettings['DPA_PER_HOUR']) }} + ([Times Invaded] * {{ $barbarianSettings['DPA_PER_TIMES_INVADED'] }} ) * [NPC Modifier]</code></li>
                              <li>OPA_TARGET: <code>[DPA] * [OPA Multiplier]</code></li>
                              </ul>
                          </div>
                    </div>

                </div>
            @endif
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                      <div class="row">
                          <div class="col-xs-2">
                            @if($realm->alignment == 'good')
                            <img src="{{ asset('assets/app/images/commonwealth.svg') }}" class="img-responsive" alt="The Commonwealth">
                            @elseif($realm->alignment == 'evil')
                            <img src="{{ asset('assets/app/images/empire.svg') }}" class="img-responsive" alt="The Empire">
                            @elseif($realm->alignment == 'independent')
                            <img src="{{ asset('assets/app/images/independent.svg') }}" class="img-responsive" alt="Independent Dominions">
                            @elseif($realm->alignment == 'npc')
                            <img src="{{ asset('assets/app/images/barbarian.svg') }}" class="img-responsive" alt="The Barbarian Horde">
                            @endif
                          </div>
                          <div class="col-xs-10">
                            <p>This is the {{ $alignmentAdjective }} Realm of <strong>{{ $realm->name }} (#{{ $realm->number }})</strong>.</p>

                            @if($realmCalculator->hasMonster($realm))
                                @php
                                    $monster = $realmCalculator->getMonster($realm)
                                @endphp

                                  This realm has a monster: <b>{{ $monster->name }}</b>!

                            @endif

                          </div>
                      </div>
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
                                  <tr>
                                  <tr>
                                    <td>Current land:</td>
                                    <td>{{ number_format($landCalculator->getTotalLandForRealm($realm)) }} acres</td>
                                  </tr>
                                    <td>Land conquered:</td>
                                    <td>{{ number_format($realmDominionsStats['total_land_conquered']) }} acres</td>
                                  </tr>
                                  <tr>
                                    <td>Land explored:</td>
                                    <td>{{ number_format($realmDominionsStats['total_land_explored']) }} acres</td>
                                  </tr>
                                  <tr>
                                    <td>Land lost:</td>
                                    <td>{{ number_format($realmDominionsStats['total_land_lost']) }} acres</td>
                                  </tr>
                                  <tr>
                                    <td>Networth:</td>
                                    <td>{{ number_format($networthCalculator->getRealmNetworth($realm)) }}</td>
                                  </tr>
                                  @if($realm->alignment === 'evil')
                                  <tr>
                                    <td>Imperial Crypt:</td>
                                    <td>{{ number_format($selectedDominion->realm->crypt) }} bodies</td>
                                  </tr>
                                  @endif
                              </table>
                            </div>

                            <p class="text-center"><a href="{{ route('dominion.world-news', [$realm->number]) }}">Read the News from the {{ $alignmentNoun }}</a></p>

                            @if(isset($realmDominionsStats) and array_sum($realmDominionsStats) > 0)
                            <div class="col-xs-12">
                              <div class="box-body table-responsive no-padding">
                                <table class="table">
                                    <colgroup>
                                        <col width="25%">
                                        <col width="25%">
                                        <col width="25%">
                                        <col width="25%">
                                    </colgroup>
                                    <tr>
                                        <th colspan="6" class="text-center">{{ $alignmentAdjective }} Lands</th>
                                    </tr>
                                    <tr>
                                        <td>Plains:</td>
                                        <td><span data-toggle="tooltip" data-placement="top" title="{{ number_format( ($realmDominionsStats['plain'] / array_sum($realmDominionsStats)) * 100,2) }}%">{{ number_format($realmDominionsStats['plain']) }}</span></td>

                                        <td>Mountains:</td>
                                        <td><span data-toggle="tooltip" data-placement="top" title="{{ number_format( ($realmDominionsStats['mountain'] / array_sum($realmDominionsStats)) * 100,2) }}%">{{ number_format($realmDominionsStats['mountain']) }}</span></td>

                                        <td>Swamp:</td>
                                        <td><span data-toggle="tooltip" data-placement="top" title="{{ number_format( ($realmDominionsStats['swamp'] / array_sum($realmDominionsStats)) * 100,2) }}%">{{ number_format($realmDominionsStats['swamp']) }}</span></td>
                                    </tr>
                                    <tr>
                                      <td>Forest:</td>
                                      <td><span data-toggle="tooltip" data-placement="top" title="{{ number_format( ($realmDominionsStats['forest'] / array_sum($realmDominionsStats)) * 100,2) }}%">{{ number_format($realmDominionsStats['forest']) }}</span></td>

                                      <td>Hills:</td>
                                      <td><span data-toggle="tooltip" data-placement="top" title="{{ number_format( ($realmDominionsStats['hill'] / array_sum($realmDominionsStats)) * 100,2) }}%">{{ number_format($realmDominionsStats['hill']) }}</span></td>

                                      <td>Water:</td>
                                      <td><span data-toggle="tooltip" data-placement="top" title="{{ number_format( ($realmDominionsStats['water'] / array_sum($realmDominionsStats)) * 100,2) }}%">{{ number_format($realmDominionsStats['water']) }}</span></td>
                                    </tr>
                                </table>
                              </div>
                          </div>
                          @endif

                      </div>
                </div>
                @if (($prevRealm !== null) || ($nextRealm !== null))
                    <div class="box-footer">
                        <div class="row">
                            <div class="col-xs-4">
                                @if ($prevRealm !== null)
                                    <a href="{{ route('dominion.realm', $prevRealm->number) }}">&lt; Previous</a><br>
                                    <small class="text-muted">{{ $prevRealm->name }} (# {{  $prevRealm->number }})</small>
                                @endif
                            </div>
                            <div class="col-xs-4">
                                <form action="{{ route('dominion.realm.change-realm') }}" method="post" role="form">
                                    @csrf
                                    <input type="number" name="realm" class="form-control text-center" placeholder="{{ $realm->number }}" min="1" max="{{ $realmCount }}">
                                </form>
                            </div>
                            <div class="col-xs-4 text-right">
                                @if ($nextRealm !== null)
                                    <a href="{{ route('dominion.realm', $nextRealm->number) }}">Next &gt;</a><br>
                                    <small class="text-muted">{{ $nextRealm->name }} (# {{  $nextRealm->number }})</small>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
