@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $race->name }}</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12 col-md-9">
                    {{-- Description --}}
                    <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Description</h4>
                    <em>
                        {!! $raceHelper->getRaceDescriptionHtml($race) !!}
                    </em>

                    <div class="row">
                        <div class="col-md-12 col-md-3">
                            {{-- Home land --}}
                            <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Home land</h4>
                            <p>
                                {!! $landHelper->getLandTypeIconHtml($race->home_land_type) !!} {{ ucfirst($race->home_land_type) }}
                            </p>
                        </div>
                        <div class="col-md-12 col-md-9">
                            {{-- Racial Spell --}}
                            <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Racial Spell</h4>
                            @php
                                $racialSpell = $spellHelper->getRacialSelfSpell($race);
                            @endphp
                            <p>
                                <strong>{{ $racialSpell['name'] }}</strong>: {{ $racialSpell['description'] }}<br>
                                <strong>Cost:</strong> {{ $racialSpell['mana_cost']}}x<br>
                                <strong>Duration:</strong> {{ $racialSpell['duration']}} ticks<br>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-md-3">
                    <table class="table table-striped">
                        <colgroup>
                            <col>
                            <col width="50px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Faction Perk</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($race->perks as $perk)
                                @php
                                    $perkDescription = $raceHelper->getPerkDescriptionHtmlWithValue($perk);
                                @endphp
                                <tr>
                                    <td>
                                        {!! $perkDescription['description'] !!}
                                    </td>
                                    <td class="text-center">
                                        {!! $perkDescription['value']  !!}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    {{-- Military Units --}}
                    <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Military Units</h4>

                    <table class="table table-striped">
                        <colgroup>
                            <col width="200px">
                            <col width="50px">
                            <col width="50px">
                            <col>
                            <col width="100px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th class="text-center">OP</th>
                                <th class="text-center">DP</th>
                                <th>Perks</th>
                                <th class="text-center">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($race->units as $unit)
                                @php
                                    $unitCostString = (number_format($unit->cost_platinum) . ' platinum');

                                    if ($unit->cost_ore > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_ore) . ' ore');
                                    }

                                    if ($unit->cost_lumber > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_lumber) . ' lumber');
                                    }

                                    if ($unit->cost_food > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_food) . ' food');
                                    }

                                    if ($unit->cost_mana > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_mana) . ' mana');
                                    }

                                    if ($unit->cost_gem > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_gem) . ' gem');
                                    }

                                    if ($unit->cost_prestige > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_prestige) . ' Prestige');
                                    }

                                    if ($unit->cost_boat > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_boat) . ' boat');
                                    }

                                    if ($unit->cost_champion > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_champion) . ' Champion');
                                    }

                                    if ($unit->cost_soul > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_soul) . ' Soul');
                                    }

                                    if ($unit->cost_unit1 > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_unit1) . ' Unit1');
                                    }

                                    if ($unit->cost_unit2 > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_unit2) . ' Unit2');
                                    }

                                    if ($unit->cost_unit3 > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_unit3) . ' Unit3');
                                    }

                                    if ($unit->cost_unit4 > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_unit4) . ' Unit4');
                                    }

                                    if ($unit->cost_morale > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_morale) . '% morale');
                                    }

                                    if ($unit->cost_wild_yeti > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_wild_yeti) . ' wild yeti');
                                    }

                                @endphp
                                <tr>
                                    <td>
                                        {!! $unitHelper->getUnitTypeIconHtml("unit{$unit->slot}") !!}
                                        {{ $unit->name }}
                                    </td>
                                    <td class="text-center">
                                        {{ $unit->power_offense }}
                                    </td>
                                    <td class="text-center">
                                        {{ $unit->power_defense }}
                                    </td>
                                    <td>
                                        {!! $unitHelper->getUnitHelpString("unit{$unit->slot}", $race) !!}
                                    </td>
                                    <td class="text-center">
                                        {{ $unitCostString }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
