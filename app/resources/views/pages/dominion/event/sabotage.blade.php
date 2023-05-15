@extends('layouts.master')
@section('title', 'Sorcery')

@section('content')
@php
    $boxColor = 'success';
    $spyop = OpenDominion\Models\Spyop::where('key', $event->data['spyop_key'])->first();

    if($event->source->realm->id == $selectedDominion->realm->id)
    {
        $class = 'green';
    }
    else
    {
        $class = 'red';
    }
@endphp
@if($selectedDominion->realm->id !== $event->source->realm->id and $selectedDominion->realm->id !== $event->target->realm->id)
    <div class="row">
        <div class="col-sm-12 col-md-4 col-md-offset-4">
            <div class="box box-{{ $boxColor }}">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-user-secret"></i> Sabotage
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
        <div class="col-sm-12 col-md-4 col-md-offset-4">
            <div class="box box-{{ $boxColor }}">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-user-secret"></i> Sabotage
                    </h3>
                </div>
                <div class="box-body no-padding">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <div class="text-center">
                                <h4>
                                    {{ $spyop->name }}
                                    @if($canViewSource or $event->data['target']['reveal_ops'])
                                        performed by {{ $event->source->name }}
                                    @endif
                                    on {{ $event->target->name }}
                                </h4>
                            </div>

                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <tbody>

                                    @foreach($event['data']['damage_dealt'] as $damageType => $damageData)
                                        <tr>
                                            @if($damageType == 'construction')
                                                <td class="text-right">Construction delayed:</td>
                                                <td>
                                                    @foreach($damageData as $buildingKey => $amount)
                                                        @php
                                                            $building = OpenDominion\Models\Building::where('key', $buildingKey)->first();
                                                        @endphp
                                                        {{ $building->name }}: {{ number_format($amount) }}<br>
                                                    @endforeach
                                                </td>

                                            @elseif($damageType == 'buildings')
                                                <td class="text-right">Buildings sabotaged:</td>
                                                <td>
                                                    @foreach($damageData as $buildingKey => $amount)
                                                        @php
                                                            $building = OpenDominion\Models\Building::where('key', $buildingKey)->first();
                                                        @endphp
                                                        {{ $building->name }}: {{ number_format($amount) }}<br>
                                                    @endforeach
                                                </td>

                                            @elseif($damageType == 'improvements')
                                                <td class="text-right">Improvements sabotaged:</td>
                                                <td>
                                                    @foreach($damageData as $improvementKey => $amount)
                                                        @php
                                                            $improvement = OpenDominion\Models\Improvement::where('key', $improvementKey)->first();
                                                        @endphp
                                                        {{ $improvement->name }}: {{ number_format($amount) }}<br>
                                                    @endforeach
                                                </td>

                                            @elseif($damageType == 'resources')
                                                <td class="text-right">Resources sabotaged:</td>
                                                <td>
                                                    @foreach($damageData as $resourceKey => $amount)
                                                        @php
                                                            $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                                        @endphp
                                                        {{ $resource->name }}: {{ number_format($amount) }}<br>
                                                    @endforeach
                                                </td>

                                            @else
                                                <td class="text-right">{{ $sabotageHelper->getDamageTypeString($damageType, $event->target->race, intval($damageData[$damageType])) }}:</td>
                                                <td>{{ number_format($damageData[$damageType]) }}</td>
                                            @endif
                                        </tr>
                                    @endforeach

                                    @if($canViewSource or $event->data['target']['reveal_ops'])
                                        <tr>
                                            <td class="text-right">Sabotage units sent:</td>
                                            <td>
                                                @foreach($event['data']['units'] as $slot => $amount)
                                                    {{ $unitHelper->getUnitName($slot, $event->source->race) }}: {{ number_format($amount) }}<br>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endif
                                    @if(array_sum($event['data']['killed_units']))
                                        <tr>
                                            <td class="text-right">Sabotage units killed:</td>
                                            <td>
                                                @foreach($event['data']['killed_units'] as $slot => $amount)
                                                    {{ $unitHelper->getUnitName($slot, $event->source->race) }}: {{ number_format($amount) }}<br>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endif

                                    @if($canViewSource)
                                        <tr>
                                            <td class="text-right">Spy strength:</td>
                                            <td>{{ number_format($event['data']['saboteur']['spy_strength_spent']) }}%</td>
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
                            Sabotage recorded at
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
