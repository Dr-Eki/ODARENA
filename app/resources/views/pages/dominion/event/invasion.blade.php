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

                        @if(isset($event->data['result']['annexation']) and $event->data['result']['annexation'] == TRUE)
                            and annexed
                        @endif

                        @if(isset($event->data['attacker']['liberation']) and $event->data['attacker']['liberation'] == TRUE)
                            and liberated
                        @endif

                        {{ $event->target->name }}
                        </span>
                    </h3>
                </div>
                <div class="box-bod no-padding">
                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <div class="text-center">

                                <h4>{{ $event->source->name }}</h4>
                            @if (isset($event->data['result']['overwhelmed']) && $event->data['result']['overwhelmed'])
                                <p class="text-center text-red">
                                    @if ($event->source->id === $selectedDominion->id)
                                        Because you were severely outmatched, you suffer extra casualties.
                                    @else
                                        Because the forces from {{ $event->source->name }} were severely outmatched, they suffer extra casualties.
                                    @endif
                                </p>
                            @endif
                            @if ($event->source->realm->id === $selectedDominion->realm->id)
                                @if (isset($event->data['attacker']['instantReturn']))
                                    <p class="text-center text-blue">
                                        ⫷⫷◬⫸◬⫸◬<br>The waves align in your favour. <b>The invading units return home instantly.</b>
                                    </p>
                                @endif
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
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->source->race, [$militaryCalculator->getUnitPowerWithPerks($event->source, null, null, $event->source->race->units->get(0), 'offense'), $militaryCalculator->getUnitPowerWithPerks($event->source, null, null, $event->source->race->units->get(0), 'defense'), ]) }}">
                                                    {{ $event->source->race->units->where('slot', $slot)->first()->name }}
                                                </span>
                                            </td>
                                            <td>
                                                @if (isset($event->data['attacker']['unitsSent'][$slot]))
                                                  {{ number_format($event->data['attacker']['unitsSent'][$slot]) }}
                                                @else
                                                  0
                                                @endif
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
                                </tbody>
                            </table>

                            @if(isset($event->data['attacker']['annexation']['hasAnnexedDominions']) and $event->data['attacker']['annexation']['hasAnnexedDominions'] > 0)
                                @foreach($event->data['attacker']['annexation']['annexedDominions'] as $annexedDominionId => $annexedDominionData)
                                    @php
                                        $annexedDominion = OpenDominion\Models\Dominion::findorfail($annexedDominionId);
                                    @endphp
                                    <div class="text-center">
                                        <h4>{{ $annexedDominion->name }}</h4>
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
                                            @if((isset($annexedDominionData['unitsSent'][$slot]) and $annexedDominionData['unitsSent'][$slot] > 0) or
                                                (isset($annexedDominionData['unitsLost'][$slot]) and $annexedDominionData['unitsLost'][$slot] > 0) or
                                                (isset($annexedDominionData['unitsReturning'][$slot]) and $annexedDominionData['unitsReturning'][$slot] > 0)
                                                )

                                                @php
                                                    $unitType = "unit{$slot}";
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $annexedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($annexedDominion, null, null, $annexedDominion->rac->units->get(0), 'offense'), $militaryCalculator->getUnitPowerWithPerks($annexedDominion, null, null, $annexedDominion->race->units->get(0), 'defense'), ]) }}">
                                                            {{ $annexedDominion->race->units->where('slot', $slot)->first()->name }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if (isset($annexedDominionData['unitsSent'][$slot]))
                                                          {{ number_format($annexedDominionData['unitsSent'][$slot]) }}
                                                        @else
                                                          0
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if (isset($annexedDominionData['unitsLost'][$slot]))
                                                          {{ number_format($annexedDominionData['unitsLost'][$slot]) }}
                                                        @else
                                                          0
                                                        @endif
                                                    </td>
                                                    <td>
                                                      @if ($event->source->realm->id === $selectedDominion->realm->id)
                                                          @if (isset($annexedDominionData['unitsReturning'][$slot]))
                                                            {{ number_format($annexedDominionData['unitsReturning'][$slot]) }}
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
                                        </tbody>
                                    </table>
                                @endforeach
                            @endif

                            @if (
                                      (($selectedDominion->round->mode == 'standard' or $selectedDominion->round->mode == 'standard-duration') and $event->source->realm->id === $selectedDominion->realm->id) or
                                      (($selectedDominion->round->mode == 'deathmatch' or $selectedDominion->round->mode == 'deathmatch-duration') and $event->source->id === $selectedDominion->id)
                                )
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

                                    @if (isset($event->data['attacker']['crypt']['total']) and $event->data['attacker']['crypt']['total'] > 0)
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

                                    @if (isset($event->data['attacker']['ore_exhausted']) and $event->data['attacker']['ore_exhausted'] > 0)
                                    <tr>
                                        <th colspan="2">Ore Exhaustion</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Stonethrowers darken the sky with boulders.</small></td>
                                    </tr>
                                    <tr>
                                        <td>Ore:</td>
                                        <td><span class="text-red">-{{ number_format($event->data['attacker']['ore_exhausted']) }}</span></td>
                                    </tr>
                                    @endif

                                    @if (isset($event->data['attacker']['resource_conversion']) and array_sum($event->data['attacker']['resource_conversion']) > 0)
                                    <tr>
                                        <th colspan="2">New Resources</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Some of the fallen return to us as new resources.</small></td>
                                    </tr>
                                    @foreach($event->data['attacker']['resource_conversion'] as $resource => $amount)
                                        @php
                                            $resourceName = str_replace('resource_','',$resource);
                                        @endphp
                                        @if($amount > 0)
                                            <tr>
                                                <td>{{ ucwords($resourceName) }}:</td>
                                                <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    @endif
                                </tbody>
                            </table>
                            @endif

                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <div class="text-center">
                            <h4>{{ $event->target->name }}</h4>
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
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->target->race, [$militaryCalculator->getUnitPowerWithPerks($event->target, null, null, $event->target->race->units->get(($slot-1)), 'offense'), $militaryCalculator->getUnitPowerWithPerks($event->target, null, null, $event->source->race->units->get(($slot-1)), 'defense'), ]) }}">
                                                    {{ $event->target->race->units->where('slot', $slot)->first()->name }}
                                                </span>
                                            </td>
                                            <td>
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->target->race, [$militaryCalculator->getUnitPowerWithPerks($event->target, null, null, $event->target->race->units->get(($slot-1)), 'offense'), $militaryCalculator->getUnitPowerWithPerks($event->target, null, null, $event->source->race->units->get(($slot-1)), 'defense'), ]) }}">
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
                                </tbody>
                            </table>

                            @if(isset($event->data['defender']['annexation']['hasAnnexedDominions']) and $event->data['defender']['annexation']['hasAnnexedDominions'] > 0)
                                @foreach($event->data['defender']['annexation']['annexedDominions'] as $annexedDominionId => $annexedDominionData)
                                    @php
                                        $annexedDominion = OpenDominion\Models\Dominion::findorfail($annexedDominionId);
                                    @endphp
                                    <div class="text-center">
                                        <h4>{{ $annexedDominion->name }}</h4>
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
                                            @for ($slot = 1; $slot <= 4; $slot++)
                                            @if((isset($annexedDominionData['unitsSent'][$slot]) and $annexedDominionData['unitsSent'][$slot] > 0) or
                                                (isset($annexedDominionData['unitsLost'][$slot]) and $annexedDominionData['unitsLost'][$slot] > 0) or
                                                (isset($annexedDominionData['unitsReturning'][$slot]) and $annexedDominionData['unitsReturning'][$slot] > 0)
                                                )

                                                @php
                                                    $unitType = "unit{$slot}";
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $annexedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($annexedDominion, null, null, $annexedDominion->rac->units->get(($slot-1)), 'offense'), $militaryCalculator->getUnitPowerWithPerks($annexedDominion, null, null, $annexedDominion->race->units->get(($slot-1)), 'defense'), ]) }}">
                                                            {{ $annexedDominion->race->units->where('slot', $slot)->first()->name }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if (isset($annexedDominionData['unitsSent'][$slot]))
                                                          {{ number_format($annexedDominionData['unitsSent'][$slot]) }}
                                                        @else
                                                          0
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if (isset($annexedDominionData['unitsLost'][$slot]))
                                                          {{ number_format($annexedDominionData['unitsLost'][$slot]) }}
                                                        @else
                                                          0
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                            @endfor
                                    </table>
                                @endforeach
                            @endif

                            @if (
                                    (($selectedDominion->round->mode == 'standard' or $selectedDominion->round->mode == 'standard-duration') and $event->target->realm->id === $selectedDominion->realm->id) or
                                    (($selectedDominion->round->mode == 'deathmatch' or $selectedDominion->round->mode == 'deathmatch-duration') and $event->target->id === $selectedDominion->id)
                                )

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
                                    @endif

                                    @if (isset($event->data['defender']['crypt']['total']) and $event->data['defender']['crypt']['total'] > 0)
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

                                    @if (isset($event->data['defender']['resource_conversion']) and array_sum($event->data['defender']['resource_conversion']) > 0)
                                        <tr>
                                            <th colspan="2">New Resources</th>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><small class="text-muted">Some of the fallen return to us as new resources.</small></td>
                                        </tr>
                                        @foreach($event->data['defender']['resource_conversion'] as $resource => $amount)
                                            @php
                                                $resourceName = str_replace('resource_','',$resource);
                                            @endphp
                                            @if($amount > 0)
                                                <tr>
                                                    <td>{{ ucwords($resourceName) }}:</td>
                                                    <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                                </tr>
                                            @endif
                                        @endforeach
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

                                    @if ($event->target->realm->id === $selectedDominion->realm->id)
                                        <col width="50%">
                                        <col width="50%">
                                    @else
                                        <col width="33%">
                                        <col width="33%">
                                        <col width="33%">
                                    @endif
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

                                        @foreach($landHelper->getLandTypes() as $landType)
                                            <tr>
                                                <td>{{ ucwords($landType) }}</td>
                                                <td>
                                                    @if(isset($event->data['attacker']['landConquered'][$landType]))
                                                        {{ number_format($event->data['attacker']['landConquered'][$landType]) }}
                                                    @else
                                                        &mdash;
                                                    @endif
                                                </td>
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

                    <div class="box-footer">
                        <div class="row">
                            <div class="col-sm-4">
                                <small class="text-muted">
                                    <a href="{{ route('dominion.insight.show', [$event->source->id]) }}"><i class="fa fa-eye"></i> {{ $event->source->name }} (# {{ $event->source->realm->number }})</a>
                                </small>
                            </div>
                            <div class="col-sm-4">
                                <small class="text-muted">
                                    <a href="{{ route('dominion.insight.show', [$event->target->id]) }}"><i class="fa fa-eye"></i> {{ $event->target->name }} (# {{ $event->target->realm->number }})</a>
                                </small>
                            </div>
                            <div class="col-sm-4">
                                <div class="pull-right">
                                    <small class="text-muted">
                                        Invasion recorded at
                                        {{ $event->created_at }}, tick
                                        {{ number_format($event->tick) }}.
                                    </small>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
