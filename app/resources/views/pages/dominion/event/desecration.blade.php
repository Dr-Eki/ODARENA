@extends('layouts.master')
@section('title', 'Desecration')

@section('content')
    @php
        $boxColor = 'success';
    @endphp
    @if($selectedDominion->realm->id !== $event->source->realm->id and $selectedDominion->realm->id !== $event->target->realm->id)
        <div class="row">
            <div class="col-sm-6 col-md-4 col-md-offset-4">
                <div class="box box-{{ $boxColor }}">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="ra ra-tombstone"></i> Desecration
                        </h3>
                    </div>
                    <div class="box-bod no-padding">
                        You cannot view this event.
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-sm-6 col-md-4 col-md-offset-4">
                <div class="box box-{{ $boxColor }}">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="ra ra-tombstone"></i> Desecration
                        </h3>
                    </div>
                    <div class="box-bod no-padding">
                        <div class="row">
                            <div class="col-xs-12 col-sm-12">
                                <div class="text-center">
                                <h4>Desecration by {{ $event->source->name }}</h4>
                                </div>
                                <table class="table">
                                    <colgroup>
                                        <col width="100">
                                        <col width="100">
                                        <col width="100">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Unit</th>
                                            <th>Sent</th>
                                            <th>Returning</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($event->data['units_sent'] as $slot => $amount)
                                            @php
                                                if($slot == 'wizards')
                                                {
                                                    $unitType = 'wizards';
                                                }
                                                elseif($slot == 'archmages')
                                                {
                                                    $unitType = 'archmages';
                                                }
                                                else
                                                {
                                                    $unitType = 'unit' . $slot;
                                                }

                                            @endphp
                                            <tr>
                                                <td>
                                                    @if(is_numeric($slot))
                                                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->source->race, [$militaryCalculator->getUnitPowerWithPerks($event->source, null, null, $event->source->race->units->get(0), 'offense'), $militaryCalculator->getUnitPowerWithPerks($event->source, null, null, $event->source->race->units->get(0), 'defense'), ]) }}">
                                                            {{ $event->source->race->units->where('slot', $slot)->first()->name }}
                                                        </span>
                                                    @else
                                                        {{ ucwords($unitType) }}
                                                    @endif
                                                </td>
                                                <td>{{ number_format($amount)}}</td>
                                                <td>{{ number_format($event->data['units_returning'][$slot])}}</td>
                                            </tr>
                                        @endforeach
                                </table>

                                <table class="table">
                                    <colgroup>
                                        <col width="100">
                                        <col width="200">
                                    </colgroup>
                                    <tbody>
                                        <tr>
                                            <td>Bodies desecrated</td>
                                            <td>{{ number_format($event->data['bodies']['desecrated']) }}</td>
                                        </tr>
                                        @if($event->data['bodies']['desecrated'] > 0)
                                            <tr>
                                                <td>{{ Str::plural($event->data['result']['resource_name'], $event->data['result']['amount']) }} returning</td>
                                                <td>{{ number_format($event->data['result']['amount']) }}</td>
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
                                Desecration recorded at
                                {{ $event->created_at }}, tick
                                {{ number_format($event->tick) }}.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
