@extends('layouts.master')

@section('content')
    @php
        $boxColor = ($event->data['result']['success'] ? 'success' : 'danger');
        if ($event->target->id === $selectedDominion->id)
        {
            $boxColor = ($event->data['result']['success'] ? 'danger' : 'success');
        }
    @endphp
    <div class="row">
        <div class="col-sm-12 col-md-8 col-md-offset-2">
            <div class="box box-{{ $boxColor }}">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="ra ra-crossed-swords"></i>
                        @if($event->target->realm->id === $selectedDominion->realm->id)
                          <span class="text-red">
                        @else
                          <span class="text-green">
                        @endif
                        {{ $event->source->name }}

                        @if($event->data['result']['success'])
                        successfully
                        @else
                        unsuccessfully
                        @endif

                        @if($event->data['attacker']['ambush'])
                        ambushed
                        @else
                        invaded
                        @endif
                        {{ $event->target->name }}
                        </span>
                    </h3>
                </div>
                <div class="box-bod no-padding">
                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <div class="text-center">
                            <h4>{{ $event->source->name }}'s Army</h4>
                            @if (isset($event->data['result']['overwhelmed']) && $event->data['result']['overwhelmed'])
                                <p class="text-center text-red">
                                    @if ($event->source->id === $selectedDominion->id)
                                        Because you were severely outmatched, you suffer extra casualties.
                                    @else
                                        Because the forces from {{ $event->source->name }} were severely outmatched, they suffer extra casualties.
                                    @endif
                                </p>
                            @endif
                            </div>
                            <table class="table">
                                <colgroup>
                                    <col width="25%">
                                    <col width="25%">
                                    <col width="25%">
                                    <col width="25%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th>Sent</th>
                                        <th>Lost</th>
                                        <th>Returning</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for ($slot = 1; $slot <= 4; $slot++)
                                    @if((isset($event->data['attacker']['unitsSent'][$slot]) and $event->data['attacker']['unitsSent'][$slot] > 0) or
                                        (isset($event->data['attacker']['unitsLost'][$slot]) and $event->data['attacker']['unitsLost'][$slot] > 0) or
                                        (isset($event->data['attacker']['unitsReturning'][$slot]) and $event->data['attacker']['unitsReturning'][$slot] > 0)
                                        )

                                        @php
                                            $unitType = "unit{$slot}";
                                        @endphp
                                        <tr>
                                            <td>
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->source->race) }}">
                                                    {{ $event->source->race->units->where('slot', $slot)->first()->name }}
                                                </span>
                                            </td>
                                            <td>
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->source->race) }}">
                                                      @if ($event->source->realm->id === $selectedDominion->realm->id)
                                                          @if (isset($event->data['attacker']['unitsSent'][$slot]))
                                                            {{ number_format($event->data['attacker']['unitsSent'][$slot]) }}
                                                          @else
                                                            0
                                                          @endif
                                                      @else
                                                            <span class="text-muted">?</span>
                                                      @endif
                                                </span>
                                            </td>
                                            <td>
                                                @if (isset($event->data['attacker']['unitsLost'][$slot]))
                                                  {{ number_format($event->data['attacker']['unitsLost'][$slot]) }}
                                                @else
                                                  0
                                                @endif
                                            </td>
                                            <td>
                                              @if ($event->source->realm->id === $selectedDominion->realm->id)
                                                  @if (isset($event->data['attacker']['unitsReturning'][$slot]))
                                                    {{ number_format($event->data['attacker']['unitsReturning'][$slot]) }}
                                                  @else
                                                    0
                                                  @endif
                                              @else
                                                    <span class="text-muted">?</span>
                                              @endif
                                            </td>
                                        </tr>
                                    @endif
                                    @endfor
                                    @if (isset($event->data['attacker']['boatsLost']))
                                        <tr>
                                            <td>Boats</td>
                                            <td>boats_sent</td>
                                            <td>{{ number_format($event->data['attacker']['boatsLost']) }}</td>
                                            <td>boats_returning</td>
                                        </tr>
                                    @endif
                            </table>

                            @if ($event->source->realm->id === $selectedDominion->realm->id)
                            <table class="table">
                                <colgroup>
                                    <col width="25%">
                                    <col width="75%">
                                </colgroup>
                                <tbody>
                                    <tr>
                                        <td>OP:</td>
                                        <td>
                                            @if ($event->data['result']['success'])
                                                <span class="text-green">
                                                    {{ number_format($event->data['attacker']['op']) }}
                                                </span>
                                            @else
                                                <span class="text-red">
                                                    {{ number_format($event->data['attacker']['op']) }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Prestige:</td>
                                        <td>
                                        @if (isset($event->data['attacker']['prestigeChange']))
                                            @php
                                                $prestigeChange = $event->data['attacker']['prestigeChange'];
                                            @endphp
                                            @if ($prestigeChange < 0)
                                                <span class="text-red">
                                                    {{ number_format($prestigeChange) }}
                                                </span>
                                            @elseif ($prestigeChange > 0)
                                                <span class="text-green">
                                                    +{{ number_format($prestigeChange) }}
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    0
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted">
                                                0
                                            </span>
                                        @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>XP:</td>
                                        <td>
                                        @if (isset($event->data['attacker']['researchPoints']))
                                            <span class="text-green">
                                                +{{ number_format($event->data['attacker']['researchPoints']) }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                0
                                            </span>
                                        @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Morale:</td>
                                        <td>
                                        @if (isset($event->data['attacker']['moraleChange']))
                                            @php
                                                $moraleChange = $event->data['attacker']['moraleChange'];
                                            @endphp
                                            @if ($moraleChange < 0)
                                                <span class="text-red">
                                                    {{ number_format($moraleChange) }}%
                                                </span>
                                            @elseif ($moraleChange > 0)
                                                <span class="text-green">
                                                    +{{ number_format($moraleChange) }}%
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    0%
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted">
                                                0%
                                            </span>
                                        @endif
                                        </td>
                                    </tr>

                                    @if (isset($event->data['attacker']['conversions']))
                                    <tr>
                                        <th colspan="2">Conversion</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">The {{ $raceHelper->getRaceAdjective($event->source->race) }} forces recall some of the dead.</small></td>
                                    </tr>
                                        @foreach($event->data['attacker']['conversions'] as $slot => $amount)
                                            @if($amount > 0)
                                                <tr>
                                                    <td>{{ $event->source->race->units->where('slot', $slot)->first()->name }}:</td>
                                                    <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif

                                    @if (isset($event->data['attacker']['plunder']) and array_sum($event->data['attacker']['plunder']) > 0)
                                    <tr>
                                        <th colspan="2">Plunder</th>
                                    </tr>
                                        @foreach($event->data['attacker']['plunder'] as $resource => $amount)
                                            @if($amount > 0)
                                                <tr>
                                                    <td>{{ ucwords($resource) }}:</td>
                                                    <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif

                                    @if (isset($event->data['attacker']['salvage']) and array_sum($event->data['attacker']['salvage']) > 0)
                                    <tr>
                                        <th colspan="2">Salvage</th>
                                    </tr>
                                        @foreach($event->data['attacker']['salvage'] as $resource => $amount)
                                            @if($amount > 0)
                                                <tr>
                                                    <td>{{ ucwords($resource) }}:</td>
                                                    <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif

                                    @if (isset($event->data['attacker']['demonic_collection']) and array_sum($event->data['attacker']['demonic_collection']) > 0)
                                    <tr>
                                        <th colspan="2">Demonic Collection</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Tearing apart the dead, the {{ $raceHelper->getRaceAdjective($event->source->race) }} units collect souls, blood, and food.</small></td>
                                    </tr>
                                        @foreach($event->data['attacker']['demonic_collection'] as $resource => $amount)
                                            @if($amount > 0)
                                                <tr>
                                                    <td>{{ ucwords($resource) }}:</td>
                                                    <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif

                                    @if (isset($event->data['attacker']['champion']))
                                    <tr>
                                        <th colspan="2">Legendary Champions</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">The {{ $raceHelper->getRaceAdjective($event->source->race) }} warriors fight bravely; some to the end, becoming legendary champions.</small></td>
                                    </tr>
                                        @foreach($event->data['attacker']['champion'] as $amount)
                                            <tr>
                                                <td colspan="2"><p class="text-green text-center">{{ number_format($amount) }} new champions return!</p></td>
                                            </tr>
                                        @endforeach
                                    @endif

                                    @if (isset($event->data['attacker']['peasants_eaten']) and isset($event->data['attacker']['draftees_eaten']))
                                    <tr>
                                        <th colspan="2">Population Eaten</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">A gruesome sight as {{ $raceHelper->getRaceAdjective($event->source->race) }} warriors eat some of the {{ $raceHelper->getRaceAdjective($event->target->race) }}  {{ strtolower(str_plural($raceHelper->getPeasantsTerm($event->target->race))) }} and {{ strtolower(str_plural($raceHelper->getDrafteesTerm($event->target->race))) }}.</small></td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($raceHelper->getPeasantsTerm($event->target->race)) }}:</td>
                                        <td><span class="text-green">{{ number_format($event->data['attacker']['peasants_eaten']['peasants']) }}</span></td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($raceHelper->getDrafteesTerm($event->target->race)) }}:</td>
                                        <td><span class="text-green">{{ number_format($event->data['attacker']['draftees_eaten']['draftees']) }}</span></td>
                                    </tr>
                                    @endif

                                    @if (isset($event->data['attacker']['peasants_burned']))
                                    <tr>
                                        <th colspan="2">Population Burned</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">The charred bodies of burned {{ str_plural($raceHelper->getPeasantsTerm($event->target->race)) }} emit a foul odour across the battlefield.</small></td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($raceHelper->getPeasantsTerm($event->target->race)) }}:</td>
                                        <td><span class="text-green">{{ number_format($event->data['attacker']['peasants_burned']['peasants']) }}</span></td>
                                    </tr>
                                    @endif

                                    @if (isset($event->data['attacker']['improvements_damage']))
                                    <tr>
                                        <th colspan="2">Improvements Damage</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Heavy blows to {{$event->target->race->name}}'s improvements have caused damage.</small></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><span class="text-green">{{ number_format($event->data['attacker']['improvements_damage']['improvement_points']) }} improvement points destroyed</span></td>
                                    </tr>
                                    @endif



                                    @if (isset($event->data['defender']['isMindControl']) and array_sum($event->data['defender']['mindControlledUnits']) > 0)
                                    <tr>
                                        <th colspan="2">Mind Control</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Cultist Mystics take control of the minds of some of ours soldiers and turn them against us.</small></td>
                                    </tr>
                                    @foreach($event->data['defender']['mindControlledUnits'] as $slot => $amount)
                                        <tr>
                                            <td>{{ $event->source->race->units->where('slot', $slot)->first()->name }}:</td>
                                            <td><span class="text-red">{{ number_format($amount) }}</span> <small class="text-muted">(of which {{ number_format($amount*0.10) }} died in combat)</small></td>
                                        </tr>
                                    @endforeach
                                    @endif

                                    @if (isset($event->data['defender']['isMenticide']))
                                    <tr>
                                        <th colspan="2">Menticide</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">The Mystics destroy the minds of the mind controlled units, capturing them.</small></td>
                                    </tr>
                                    <tr>
                                        <td>Total units lost:</td>
                                        <td><span class="text-red">{{ number_format($event->data['defender']['menticide']['newThralls']) }}</span></td>
                                    </tr>
                                    @endif



                                    @if (isset($event->data['defender']['unitsStunned']) and array_sum($event->data['defender']['unitsStunned']) > 0)
                                    <tr>
                                        <th colspan="2">Stunned</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">We stun some of the enemy units and they will not be able to fight for two ticks.</small></td>
                                    </tr>
                                        @foreach($event->data['defender']['unitsStunned'] as $slot => $amount)
                                            @if($amount > 0)
                                                <tr>
                                                    <td>
                                                        @if($slot === 'draftees')
                                                            {{ $raceHelper->getDrafteesTerm($event->target->race) }}:
                                                        @else
                                                            {{ $event->target->race->units->where('slot', $slot)->first()->name }}:
                                                        @endif
                                                    </td>
                                                    <td><span class="text-red">{{ number_format($amount) }}</span></td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif

                                    @if (isset($event->data['attacker']['crypt']) and $event->data['attacker']['crypt']['total'] > 0)
                                    <tr>
                                        <th colspan="2">Crypt</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Dead bodies are immediately added to the Imperial Crypt.</small></td>
                                    </tr>
                                    <tr>
                                        <td>Bodies:</td>
                                        <td><span class="text-green">+{{ number_format($event->data['attacker']['crypt']['total']) }}</span></td>
                                    </tr>
                                    @endif

                                    @if (isset($event->data['attacker']['mana_exhausted']) and $event->data['attacker']['mana_exhausted'] > 0)
                                    <tr>
                                        <th colspan="2">Mana Exhaustion</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Firing the Hailstorm Cannon depletes our mana supplies.</small></td>
                                    </tr>
                                    <tr>
                                        <td>Mana:</td>
                                        <td><span class="text-red">-{{ number_format($event->data['attacker']['mana_exhausted']) }}</span></td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                            @endif

                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <div class="text-center">
                            <h4>{{ $event->target->name }}'s Army</h4>
                            </div>
                            <table class="table">
                                <colgroup>
                                    <col width="34%">
                                    <col width="33%">
                                    <col width="33%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th>Defending</th>
                                        <th>Lost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(isset($event->data['defender']['unitsLost']['peasants']) and $event->data['defender']['unitsLost']['peasants'] > 0)

                                        @php
                                        if(!isset($event->data['defender']['unitsDefending']['peasants']))
                                            $peasants = 0;
                                        else
                                            $peasants = $event->data['defender']['unitsDefending']['peasants'];
                                        @endphp

                                        <tr>
                                            <td>
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getDrafteeHelpString( $event->source->race) }}">
                                                    {{ $raceHelper->getPeasantsTerm($selectedDominion->race) }}:
                                                </span>
                                            </td>
                                            <td>
                                                @if ($event->target->realm->id === $selectedDominion->realm->id)
                                                    {{ number_format($peasants) }}
                                                @else
                                                    <span class="text-muted">?</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format($event->data['defender']['unitsLost']['peasants']) }}</td>
                                        </tr>

                                    @endif

                                    @if(isset($event->data['defender']['unitsLost']['draftees']) and $event->data['defender']['unitsLost']['draftees'] > 0)

                                        @php
                                        if(!isset($event->data['defender']['unitsDefending']['draftees']))
                                            $draftees = 0;
                                        else
                                            $draftees = $event->data['defender']['unitsDefending']['draftees'];
                                        @endphp

                                        <tr>
                                            <td>
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getDrafteeHelpString( $event->source->race) }}">
                                                    {{ $raceHelper->getDrafteesTerm($event->target->race) }}:
                                                </span>
                                            </td>
                                            <td>
                                                @if ($event->target->realm->id === $selectedDominion->realm->id)
                                                    {{ number_format($draftees) }}
                                                @else
                                                    <span class="text-muted">?</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format($event->data['defender']['unitsLost']['draftees']) }}</td>
                                        </tr>

                                    @endif
                                    @for ($slot = 1; $slot <= 4; $slot++)
                                    @if((isset($event->data['defender']['unitsDefending'][$slot]) and $event->data['defender']['unitsDefending'][$slot] > 0) or
                                        (isset($event->data['defender']['unitsLost'][$slot]) and $event->data['defender']['unitsLost'][$slot] > 0)
                                        )

                                        @php
                                            $unitType = "unit{$slot}";
                                        @endphp
                                        <tr>
                                            <td>
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->target->race) }}">
                                                    {{ $event->target->race->units->where('slot', $slot)->first()->name }}
                                                </span>
                                            </td>
                                            <td>
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->target->race) }}">
                                                      @if ($event->target->realm->id === $selectedDominion->realm->id)
                                                          @if (isset($event->data['defender']['unitsDefending'][$slot]))
                                                            {{ number_format($event->data['defender']['unitsDefending'][$slot]) }}
                                                          @else
                                                            0
                                                          @endif
                                                      @else
                                                          <span class="text-muted">?</span>
                                                      @endif
                                                </span>
                                            </td>
                                            <td>
                                                @if (isset($event->data['defender']['unitsLost'][$slot]))
                                                  {{ number_format($event->data['defender']['unitsLost'][$slot]) }}
                                                @else
                                                  0
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                    @endfor
                                    @if (isset($event->data['attacker']['boatsLost']))
                                        <tr>
                                            <td>Boats</td>
                                            <td>boats_sent</td>
                                            <td>{{ number_format($event->data['attacker']['boatsLost']) }}</td>
                                            <td>boats_returning</td>
                                        </tr>
                                    @endif
                            </table>

                            @if ($event->target->realm->id === $selectedDominion->realm->id)
                            <table class="table">
                                <colgroup>
                                    <col width="34%">
                                    <col width="66%">
                                </colgroup>
                                <tbody>
                                    <tr>
                                        <td>DP:</td>
                                        <td>
                                            @if ($event->data['result']['success'])
                                                <span class="text-red">
                                                    {{ number_format($event->data['defender']['dp']) }}
                                                </span>
                                            @else
                                                <span class="text-green">
                                                    {{ number_format($event->data['defender']['dp']) }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Prestige:</td>
                                        <td>
                                        @if (isset($event->data['defender']['prestigeChange']))
                                            @php
                                                $prestigeChange = $event->data['defender']['prestigeChange'];
                                            @endphp
                                            @if ($prestigeChange < 0)
                                                <span class="text-red">
                                                    {{ number_format($prestigeChange) }}
                                                </span>
                                            @elseif ($prestigeChange > 0)
                                                <span class="text-green">
                                                    +{{ number_format($prestigeChange) }}
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    0
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted">
                                                0
                                            </span>
                                        @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Morale:</td>
                                        <td>
                                        @if (isset($event->data['defender']['moraleChange']))
                                            @php
                                                $moraleChange = $event->data['defender']['moraleChange'];
                                            @endphp
                                            @if ($moraleChange < 0)
                                                <span class="text-red">
                                                    {{ number_format($moraleChange) }}%
                                                </span>
                                            @elseif ($moraleChange > 0)
                                                <span class="text-green">
                                                    +{{ number_format($moraleChange) }}
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    0%
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted">
                                                0%
                                            </span>
                                        @endif
                                        </td>
                                    </tr>

                                    @if (isset($event->data['defender']['conversions']))
                                    <tr>
                                        <th colspan="2">Conversion</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">The {{ $raceHelper->getRaceAdjective($event->target->race) }} forces recall some of the dead.</small></td>
                                    </tr>
                                        @foreach($event->data['defender']['conversions'] as $slot => $amount)
                                            @if($amount > 0)
                                                <tr>
                                                    <td>{{ $event->target->race->units->where('slot', $slot)->first()->name }}:</td>
                                                    <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif

                                    @if (isset($event->data['defender']['salvage']) and array_sum($event->data['defender']['salvage']) > 0)
                                    <tr>
                                        <th colspan="2">Salvage</th>
                                    </tr>
                                        @foreach($event->data['defender']['salvage'] as $resource => $amount)
                                            @if($amount > 0)
                                                <tr>
                                                    <td>{{ ucwords($resource) }}:</td>
                                                    <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif

                                    @if (isset($event->data['defender']['demonic_collection']))
                                    <tr>
                                        <th colspan="2">Demonic Collection</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Tearing apart the dead, the {{ $raceHelper->getRaceAdjective($event->source->race) }} units collect souls, blood, and food.</small></td>
                                    </tr>
                                        @foreach($event->data['defender']['demonic_collection'] as $resource => $amount)
                                            @if($amount > 0)
                                                <tr>
                                                    <td>{{ ucwords($resource) }}:</td>
                                                    <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif

                                    @if (isset($event->data['attacker']['peasants_eaten']) and isset($event->data['attacker']['draftees_eaten']))
                                    <tr>
                                        <th colspan="2">Population Eaten</th>
                                    </tr>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">The {{ $raceHelper->getRaceAdjective($event->source->race) }} warriors eat some of our {{ strtolower(str_plural($raceHelper->getPeasantsTerm($event->target->race))) }} and {{ strtolower(str_plural($raceHelper->getDrafteesTerm($event->target->race))) }}.</small></td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($raceHelper->getPeasantsTerm($event->target->race)) }}:</td>
                                        <td><span class="text-green">{{ number_format($event->data['attacker']['peasants_eaten']['peasants']) }}</span></td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($raceHelper->getDrafteesTerm($event->target->race)) }}:</td>
                                        <td><span class="text-green">{{ number_format($event->data['attacker']['draftees_eaten']['draftees']) }}</span></td>
                                    </tr>
                                    @endif

                                    @if (isset($event->data['attacker']['peasants_burned']))
                                    <tr>
                                        <th colspan="2">Population Burned</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Our {{ strtolower(str_plural($raceHelper->getPeasantsTerm($event->target->race))) }} have been attacked with fire.</small></td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($raceHelper->getPeasantsTerm($event->target->race)) }} burned:</td>
                                        <td><span class="text-red">{{ number_format($event->data['attacker']['peasants_burned']['peasants']) }}</span></td>
                                    </tr>
                                    @endif

                                    @if (isset($event->data['attacker']['improvements_damage']))
                                    <tr>
                                        <th colspan="2">Improvements Damage</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Heavy blows to our improvements have weakened us.</small></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><span class="text-red">{{ number_format($event->data['attacker']['improvements_damage']['improvement_points']) }} improvement points destroyed</span></td>
                                    </tr>
                                    @endif

                                    @if (isset($event->data['defender']['isMindControl']) and array_sum($event->data['defender']['mindControlledUnits']) > 0)
                                    <tr>
                                        <th colspan="2">Mind Control</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Our Mystics take control of the minds of some enemy soldiers and make them join us in battle.</small></td>
                                    </tr>
                                    @foreach($event->data['defender']['mindControlledUnits'] as $slot => $amount)
                                        <tr>
                                            <td>{{ $event->source->race->units->where('slot', $slot)->first()->name }}:</td>
                                            <td><span class="text-green">{{ number_format($amount) }}</span> <small class="text-muted">(of which {{ number_format($amount*0.10) }} died in combat)</small></td>
                                        </tr>
                                    @endforeach
                                    @endif

                                    @if (isset($event->data['defender']['isMenticide']))
                                    <tr>
                                        <th colspan="2">Menticide</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">With the power of menticide, we permanently convert as many of the surviving mind controlled units as we can fit. The rest are executed.</small></td>
                                    </tr>
                                    <tr>
                                        <td>New Thralls:</td>
                                        <td><span class="text-green">{{ number_format($event->data['defender']['menticide']['newThralls']) }}</span></td>
                                    </tr>
                                    @endif

                                    @if (isset($event->data['defender']['unitsStunned']) and array_sum($event->data['defender']['unitsStunned']) > 0)
                                    <tr>
                                        <th colspan="2">Stunned</th>
                                    </tr>
                                    <tr>
                                          <td colspan="2"><small class="text-muted">Some of our units are stunned and will not be able to fight for two ticks.</small></td>
                                    </tr>
                                        @foreach($event->data['defender']['unitsStunned'] as $slot => $amount)
                                            @if($amount > 0)
                                                <tr>
                                                    <td>
                                                        @if($slot === 'draftees')
                                                            {{ $raceHelper->getDrafteesTerm($event->target->race) }}:
                                                        @else
                                                            {{ $event->target->race->units->where('slot', $slot)->first()->name }}:
                                                        @endif
                                                    </td>
                                                    <td><span class="text-red">{{ number_format($amount) }}</span></td>
                                                </tr>
                                            @endif
                                        @endforeach

                                        @if (isset($event->data['defender']['crypt']) and $event->data['defender']['crypt']['total'] > 0)
                                        <tr>
                                            <th colspan="2">Crypt</th>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><small class="text-muted">Dead bodies are immediately added to the Imperial Crypt.</small></td>
                                        </tr>
                                        <tr>
                                            <td>Bodies:</td>
                                            <td><span class="text-green">+{{ number_format($event->data['defender']['crypt']['total']) }}</span></td>
                                        </tr>
                                        @endif

                                    @endif
                                </tbody>
                            </table>
                            @endif

                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <div class="text-center">
                            <h4>
                              @if ($event->target->realm->id === $selectedDominion->realm->id)
                                  Land Lost
                              @else
                                  Land Gained
                              @endif
                            </h4>
                            </div>
                            <table class="table">
                                <colgroup>
                                    <col width="33%">
                                    <col width="33%">
                                    <col width="33%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Landtype</th>
                                        <th>
                                            @if ($event->target->realm->id === $selectedDominion->realm->id)
                                                Lost
                                            @else
                                                Conquered
                                            @endif
                                        </th>
                                        @if ($event->source->realm->id === $selectedDominion->realm->id)
                                          <th>Discovered</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (!isset($event->data['attacker']['landConquered']))
                                        <tr>
                                            <td colspan="3" class="text-center">
                                                <em>None</em>
                                            </td>
                                        </tr>
                                    @else

                                        @php
                                            if(isset($event->data['attacker']['landDiscovered']) and !isset($event->data['attacker']['landGenerated']))
                                                $landChanges = array_merge($event->data['attacker']['landConquered'], $event->data['attacker']['landDiscovered']);
                                            else
                                                $landChanges = array_merge($event->data['attacker']['landConquered'], $event->data['attacker']['landGenerated']);

                                        @endphp

                                        @foreach($landChanges as $landType => $amount)
                                        <tr>
                                            <td>{{ ucwords($landType) }}</td>
                                            <td>{{ number_format($event->data['attacker']['landConquered'][$landType]) }}</td>
                                            @if ($event->source->realm->id === $selectedDominion->realm->id)
                                              <td>
                                                  @if(isset($event->data['attacker']['landDiscovered'][$landType]))
                                                      @if(isset($event->data['attacker']['extraLandDiscovered'][$landType]))
                                                          {{ number_format($event->data['attacker']['landDiscovered'][$landType]+$event->data['attacker']['extraLandDiscovered'][$landType]) }}
                                                      @else
                                                          {{ number_format($event->data['attacker']['landDiscovered'][$landType]) }}
                                                      @endif
                                                  @else
                                                      &mdash;
                                                  @endif
                                              </td>
                                            @endif
                                        </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>


                            <table class="table">
                                <div class="text-center">
                                <h4>
                                  @if ($event->target->realm->id === $selectedDominion->realm->id)
                                      Buildings Lost
                                  @else
                                      Buildings Destroyed
                                  @endif
                                </h4>
                                <small class="text-muted" style="font-weight: normal;">(including unfinished)</small>
                                </div>
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <tbody>
                                @if(isset($event->data['defender']['buildingsLost']))
                                    @foreach($event->data['defender']['buildingsLost'] as $building => $details)
                                        @php
                                            $buildingName = str_replace('_',' ',$building);
                                            $buildingName = ucwords($buildingName);

                                            $destroyed = array_sum($details);
                                        @endphp

                                    <tr>
                                        <td>{{ $buildingName }}</td>
                                        <td>{{ number_format($destroyed )}}</td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="2" class="text-center">
                                            <em>None</em>
                                        </td>
                                @endif
                                </tbody>
                            </table>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            @if (isset($event->data['defender']['recentlyInvadedCount']) and $event->data['defender']['recentlyInvadedCount'] > 0 and $event->data['result']['success'])
                                <p class="text-center">
                                    @if ($event->source->id === $selectedDominion->id)
                                        Because the target was recently invaded, your prestige gains and their defensive losses are reduced.
                                    @else
                                        Because the target was recently invaded, {{ $event->source->name }} (# {{ $event->source->realm->number }})'s prestige gains and {{ $event->target->name }} (# {{ $event->target->realm->number }})'s defensive losses are reduced.
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
