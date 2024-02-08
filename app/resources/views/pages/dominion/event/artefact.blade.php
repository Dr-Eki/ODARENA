@extends('layouts.master')
@section('title', 'Artefact')

@section('content')
    @php
        $boxColor = 'success';
    @endphp
    <div class="row">
        <div class="col-sm-12 col-md-8 col-md-offset-2">
            <div class="box box-{{ $boxColor }}">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="ra ra-alien-fire"></i> Attack on Artefact
                    </h3>
                </div>
                <div class="box-body no-padding">
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="text-center">
                            <h4>{{ $event->source->name }}</h4>
                            @if ($event->source->realm->id === $selectedDominion->realm->id)
                                @if (isset($event->data['instant_return']))
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
                                    @for ($slot = 1; $slot <= $event->source->race->units->count(); $slot++)
                                        @if((isset($event->data['attacker']['units_sent'][$slot]) and $event->data['attacker']['units_sent'][$slot] > 0) or
                                            (isset($event->data['attacker']['units_lost'][$slot]) and $event->data['attacker']['units_lost'][$slot] > 0) or
                                            (isset($event->data['attacker']['units_returning'][$slot]) and $event->data['attacker']['units_returning'][$slot] > 0)
                                            )

                                            @php
                                                $unitType = "unit{$slot}";
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->source->race, [$militaryCalculator->getUnitPowerWithPerks($event->source, null, null, $event->source->race->units->get($slot-1), 'offense'), $militaryCalculator->getUnitPowerWithPerks($event->source, null, null, $event->source->race->units->get($slot-1), 'defense'), ]) }}">
                                                        {{ $event->source->race->units->where('slot', $slot)->first()->name }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if (isset($event->data['attacker']['units_sent'][$slot]))
                                                    {{ number_format($event->data['attacker']['units_sent'][$slot]) }}
                                                    @else
                                                    0
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (isset($event->data['attacker']['units_lost'][$slot]))
                                                    {{ number_format($event->data['attacker']['units_lost'][$slot]) }}
                                                    @else
                                                    0
                                                    @endif
                                                </td>
                                                <td>
                                                @if ($event->source->realm->id === $selectedDominion->realm->id)
                                                    @if (isset($event->data['attacker']['units_returning'][$slot]))
                                                        {{ number_format($event->data['attacker']['units_returning'][$slot]) }}
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
                            </table>

                            @if ($event->source->realm->id === $selectedDominion->realm->id)
                                <table class="table">
                                    <colgroup>
                                        <col width="33%">
                                        <col>
                                    </colgroup>
                                    <tbody>
                                        <tr>
                                            <td>Damage dealt:</td>
                                            <td>
                                                <span data-toggle="tooltip" data-placement="top" title="<small class='text-muted'>OP sent:</small> {{ number_format($event->data['attacker']['op']) }}">
                                                    {{ number_format($event->data['attacker']['damage_dealt']) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Prestige:</td>
                                            <td>{{ vsprintf('%+g', number_format($event->data['attacker']['prestige_gained'])) }}</td>
                                        </tr>
                                        <tr>
                                            <td>XP:</td>
                                            <td>{{ vsprintf('%+g', number_format($event->data['attacker']['xp_gained'])) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Morale:</td>
                                            <td>{{ vsprintf('%+g', number_format($event->data['attacker']['morale_change'])) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        <div class="col-xs-12 col-sm-6">
                            @php
                                $artefact = OpenDominion\Models\Artefact::where('key', $event->data['artefact']['key'])->firstOrFail();
                            @endphp
                            <div class="text-center">
                            <h4>{{ $artefact->name }}</h4>
                            @if ($event->source->realm->id === $selectedDominion->realm->id)
                                @if (isset($event->data['instant_return']))
                                    <p class="text-center text-blue">
                                        ⫷⫷◬⫸◬⫸◬<br>The waves align in your favour. <b>The invading units return home instantly.</b>
                                    </p>
                                @endif
                            @endif
                            </div>
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <tbody>
                                    <tr>
                                        <td colspan="2">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td>Damage suffered:</td>
                                        <td><span class="text-danger">{{ number_format($event->data['artefact']['damage_suffered']) }}</span></td>
                                    </tr>
                                    <tr>
                                        <td>Previous power:</td>
                                        <td>{{ number_format($event->data['artefact']['current_power']) }}</td>
                                    </tr>
                                    @if($event->data['artefact']['result']['shield_broken'])
                                        <tr>
                                            <td>
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td>New power:</td>
                                            <td>{{ number_format($event->data['artefact']['new_power']) }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                </div>
                <div class="box-footer">
                    <div class="pull-right">
                        <small class="text-muted">
                            Artefact attack recorded at
                            {{ $event->created_at }}, tick
                            {{ number_format($event->tick) }}.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
