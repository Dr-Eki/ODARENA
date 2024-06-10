@extends('layouts.topnav')
@section('title', "Scribes | Quickstarts")

@section('content')
@include('partials.scribes.nav')

@if(!$quickstart->is_public and (!Auth::user() or $quickstart->user_id != Auth::user()->id))
    @php
        abort(403, 'This quickstart is not public');
    @endphp
@endif

<div class="row">
    <div class="col-md-12 col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h4 class="box-title">Overview</h4>
            </div>
            <table class="table table-striped" style="margin-bottom: 0">
                <colgroup>
                    <col>
                    <col>
                </colgroup>
                <tbody>
                    <tr>
                        <td class="text-left">Faction</td>
                        <td class="text-left">{{ $quickstart->race->name }} <a href="{{ route('scribes.faction', $quickstart->race->name) }}"><i class="fas fa-book"></i></a></td>
                    </tr>
                    <tr>
                        <td class="text-left">Title</td>
                        <td class="text-left">{{ $quickstart->title->name }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">Offensive Power</td>
                        <td class="text-left">{{ number_format($quickstart->offensive_power) }} <em>(est.)</em></td>
                    </tr>
                    <tr>
                        <td class="text-left">Defensive Power</td>
                        <td class="text-left">{{ number_format($quickstart->defensive_power) }} <em>(est.)</em></td>
                    </tr>
                    <tr>
                        <td class="text-left">Deity</td>
                        <td class="text-left">{{ isset($quickstart->deity) ? ($quickstart->deity->name . ' (' . number_format($quickstart->devotion_ticks) . ' ' . Str::plural('tick', $quickstart->devotion_ticks) . ' devotion)' ): 'none' }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">Ticks</td>
                        <td class="text-left">{{ number_format($quickstart->protection_ticks) . ' (protection ' . Str::plural('tick', $quickstart->protection_ticks) . ' remaining)'  }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">Land</td>
                        <td class="text-left">{{ number_format($quickstart->land) }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">Peasants</td>
                        <td class="text-left">{{ number_format($quickstart->peasants) }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">Prestige</td>
                        <td class="text-left">{{ number_format($quickstart->prestige) }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">XP</td>
                        <td class="text-left">{{ number_format($quickstart->xp) }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">Draft rate</td>
                        <td class="text-left">{{ number_format($quickstart->draft_rate) }}%</td>
                    </tr>
                    <tr>
                        <td class="text-left">Morale</td>
                        <td class="text-left">{{ number_format($quickstart->morale) }}%</td>
                    </tr>
                    <tr>
                        <td class="text-left">Spy strength</td>
                        <td class="text-left">{{ number_format($quickstart->spy_strength) }}%</td>
                    </tr>
                    <tr>
                        <td class="text-left">Wizard strength</td>
                        <td class="text-left">{{ number_format($quickstart->wizard_strength) }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>


    @if($quickstart->description)

        <div class="col-md-12 col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h4 class="box-title">Description</h4>
                </div>
                <div class="box-body">
                    {!! $quickstart->description !!}
                </div>
            </div>
        </div>
    @endif

    <div class="col-md-12 col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h4 class="box-title">Units</h4>
            </div>
            <table class="table table-striped" style="margin-bottom: 0">
                <colgroup>
                    <col width="50%">
                    <col>
                </colgroup>
                <tbody>
                    @foreach($quickstart->units as $unitKey => $amount)
                        @if($amount > 0)
                            <tr>
                                @php
                                    if(in_array($unitKey, ['unit1','unit2','unit3','unit4','unit5','unit6','unit7','unit8','unit9','unit10']))
                                    {
                                        $slot = (int)str_replace('unit','',$unitKey);

                                        $unit = $quickstart->race->units->filter(function ($unit) use ($slot) {
                                            return ($unit->slot === $slot);
                                        })->first();
                                        $unitName = $unit->name;
                                    }
                                    elseif(in_array($unitKey, ['spies','wizards','archmages']))
                                    {
                                        $unitName = ucwords($unitKey);
                                    }
                                    else
                                    {
                                        $unitName = $raceHelper->getDrafteesTerm($quickstart->race);
                                    }
                                @endphp
                                <td>
                                    <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitKey, $quickstart->race) }}">
                                        {{ $unitName }}:
                                    </span>
                                </td>
                                <td>{{ number_format($amount) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-12 col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h4 class="box-title">Resources</h4>
            </div>
            <table class="table table-striped" style="margin-bottom: 0">
                <colgroup>
                    <col width="50%">
                    <col>
                </colgroup>
                <tbody>
                    @foreach($quickstart->resources as $resourceKey => $amount)
                        @php
                            $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                        @endphp
                        @if($amount > 0)
                            <tr>
                                <td>{{ $resource->name }}:</td>
                                <td>{{ number_format($amount) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-12 col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h4 class="box-title">Terrain</h4>
            </div>
            <table class="table table-striped" style="margin-bottom: 0">
                <colgroup>
                    <col width="50%">
                    <col>
                </colgroup>
                <tbody>
                    @foreach($quickstart->terrains as $terrainKey => $amount)
                        @if($amount > 0)
                            @php
                                $terrain = OpenDominion\Models\Terrain::where('key', $terrainKey)->first();
                            @endphp
                            <tr>
                                <td>{{ $terrain->name }}:</td>
                                <td>{{ number_format($amount) }}</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr>
                        <td><strong>Total:</strong></td>
                        <td>{{ number_format($quickstart->land) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="row">

    <div class="col-md-12 col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h4 class="box-title">Buildings</h4>
            </div>
            <table class="table table-striped" style="margin-bottom: 0">
                <colgroup>
                    <col width="50%">
                    <col>
                </colgroup>
                <tbody>
                    @foreach($quickstart->buildings as $buildingKey => $amount)
                        @php
                            $building = OpenDominion\Models\Building::where('key', $buildingKey)->first();
                        @endphp
                        @if($amount > 0)
                            <tr>
                                <td>{{ $building->name }}:</td>
                                <td>{{ number_format($amount) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-12 col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h4 class="box-title">Advancements</h4>
            </div>
            <table class="table table-striped" style="margin-bottom: 0">
                <colgroup>
                    <col width="50%">
                    <col>
                </colgroup>
                <tbody>
                    @foreach($quickstart->advancements as $advancementKey => $advancementLevel)
                        @php
                            $advancement = OpenDominion\Models\Advancement::where('key', $advancementKey)->first();
                        @endphp
                        <tr>
                            <td>{{ $advancement->name }}:</td>
                            <td>level {{ $advancementLevel }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-12 col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h4 class="box-title">Spells</h4>
            </div>
            <table class="table table-striped" style="margin-bottom: 0">
                <colgroup>
                    <col width="50%">
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th>Spell</th>
                        <th>Duration</th>
                        <th>Cooldown</th>
                    </th>
                <tbody>
                    @foreach($quickstart->spells as $spellKey => $durationData)
                        @php
                            $spell = OpenDominion\Models\Spell::where('key', $spellKey)->first();
                            $durationData = explode(',', $durationData);
                            $duration = $durationData[0];
                            $cooldown = $durationData[1];
                        @endphp
                        @if(($duration + $cooldown) > 0)
                            <tr>
                                <td>{{ $spell->name }}:</td>
                                <td>{{ number_format($duration) }}</td>
                                <td>{{ number_format($cooldown) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-12 col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h4 class="box-title">Improvements</h4>
            </div>
            <table class="table table-striped" style="margin-bottom: 0">
                <colgroup>
                    <col width="50%">
                    <col>
                </colgroup>
                <tbody>
                    @foreach($quickstart->improvements as $improvementKey => $amount)
                        @php
                            $improvement = OpenDominion\Models\Improvement::where('key', $improvementKey)->first();
                        @endphp
                        @if($amount > 0)
                            <tr>
                                <td>{{ $improvement->name }}:</td>
                                <td>{{ number_format($amount) }} points</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-12 col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h4 class="box-title">Decrees</h4>
            </div>
            <table class="table table-striped" style="margin-bottom: 0">
                <colgroup>
                    <col width="50%">
                    <col>
                </colgroup>
                <tbody>
                    @foreach($quickstart->decree_states as $decreeStateData)
                        @php
                            $decreeStateData = explode(',', $decreeStateData);
                            $decreeId = (int)$decreeStateData[0];
                            $decreeStateId = (int)$decreeStateData[1];
                            $decree = OpenDominion\Models\Decree::findOrFail($decreeId);
                            $decreeState = OpenDominion\Models\DecreeState::findOrFail($decreeStateId);
                        @endphp
                        <tr>
                            <td>{{ $decree->name }}:</td>
                            <td>
                                <u>{{ $decreeState->name }}</u>
                                {!! $decreeHelper->getDecreeStateDescription($decreeState) !!}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
