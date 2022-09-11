@extends('layouts.master')
@section('title', 'Expedition')

@section('content')
    @php
        $boxColor = 'success';
    @endphp
    <div class="row">
        <div class="col-sm-12 col-md-8 col-md-offset-2">
            <div class="box box-{{ $boxColor }}">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-drafting-compass"></i> Expedition
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
                                    <col width="33%">
                                    <col width="33%">
                                    {{--
                                    <col width="25%">
                                    --}}
                                    <col width="33%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th>Sent</th>
                                        <th>Returning</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for ($slot = 1; $slot <= $event->source->race->units->count(); $slot++)
                                    @if((isset($event->data['units_sent'][$slot]) and $event->data['units_sent'][$slot] > 0) or
                                        (isset($event->data['units_lost'][$slot]) and $event->data['units_lost'][$slot] > 0) or
                                        (isset($event->data['units_returning'][$slot]) and $event->data['units_returning'][$slot] > 0)
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
                                                @if (isset($event->data['units_sent'][$slot]))
                                                  {{ number_format($event->data['units_sent'][$slot]) }}
                                                @else
                                                  0
                                                @endif
                                            </td>
                                            {{--
                                            <td>
                                                @if (isset($event->data['units_lost'][$slot]))
                                                  {{ number_format($event->data['units_lost'][$slot]) }}
                                                @else
                                                  0
                                                @endif
                                            </td>
                                            --}}
                                            <td>
                                              @if ($event->source->realm->id === $selectedDominion->realm->id)
                                                  @if (isset($event->data['units_returning'][$slot]))
                                                    {{ number_format($event->data['units_returning'][$slot]) }}
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
                                        <td>OP:</td>
                                        <td>{{ number_format($event->data['op_sent']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Prestige:</td>
                                        <td>{{ number_format($event->data['prestige_change']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>XP:</td>
                                        <td>{{ number_format($event->data['xp']) }}</td>
                                    </tr>
                                    @if(isset($event->data['ash_found']) and $event->data['ash_found'] > 0)
                                    <tr>
                                        <td>Ash found:</td>
                                        <td>{{ number_format($event->data['ash_found']) }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                            @endif

                        </div>

                        <div class="col-xs-12 col-sm-6">
                            <div class="text-center">
                            <h4>Land Discovered</h4>
                            </div>
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th class="text-center">Land Type</th>
                                        <th class="text-center">Discovered</th>
                                    </tr>
                                    @foreach($event->data['land_discovered'] as $landType => $amount)
                                        @if($amount > 0)
                                            <tr>
                                                <td class="text-center">{{ ucfirst(str_replace('land_','',$landType)) }}</td>
                                                <td class="text-center">{{ number_format($amount) }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    <tr>
                                        <td class="text-center"><b>Total</b></td>
                                        <td class="text-center"><b>{{ number_format(array_sum($event->data['land_discovered'])) }}</b></td>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
                <div class="box-footer">
                    <div class="pull-right">
                        <small class="text-muted">
                            Expedition recorded at
                            {{ $event->created_at }}, tick
                            {{ number_format($event->tick) }}.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($event->data['artefact']) and $event->data['artefact']['found'])
    @php
        $artefact = OpenDominion\Models\Artefact::findOrFail($event->data['artefact']['id']);
    @endphp
    <div class="row">
        <div class="col-sm-12 col-md-8 col-md-offset-2">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="ra ra-alien-fire"></i> Artefact
                    </h3>
                </div>
                <div class="box-body no-padding">
                    <div class="col-xs-12 col-sm-12">
                        You have discovered an artefact!

                        <h3>{{ $artefact->name }}</h3>

                        Add artefact perks...
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection
