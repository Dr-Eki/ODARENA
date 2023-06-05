@extends ('layouts.master')
@section('title', 'Calculations')

@section('content')

    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="alert alert-danger">
                The calculators are in early beta and may not work accurately. It is missing a lot of features, including the ability to calculate any DP mods. Please use carefully. <strong>Verify calculations manually.</strong>
          </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12 col-md-9">
            <form action="" method="get" role="form" id="calculate-defense-form" class="calculate-form">
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-shield-alt fa-fw"></i> Defense Calculator</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="race">Faction</label>
                            <select name="race" id="race_dp" class="form-control" style="width: 100%;">
                                <option value="0">Select a faction</option>
                                @foreach ($races as $race)
                                    <option value="{{ $race->id }}" {{ ($targetDominion !== null && $targetDominion->race_id == $race->id) ? 'selected' : null }}>
                                        {{ $race->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Land
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        name="calc[land]"
                                        class="form-control text-center"
                                        placeholder="1000"
                                        min="0"
                                        value="{{ $targetDominion !== null ? $targetDominion->land : null }}" />
                            </div>
                            <div class="col-xs-3 text-right">
                                Morale
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        name="calc[morale]"
                                        class="form-control text-center"
                                        placeholder="100"
                                        min="0"
                                        value="{{ ($targetDominion !== null && $targetInfoOps->has('clear_sight')) ? array_get($targetInfoOps['clear_sight']->data, "morale") : null }}" />
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Deity
                            </div>
                            <div class="col-xs-3 text-left">
                                <select name="calc[deity]" class="form-control" style="width: 100%;" required>
                                    <option value="0">Select a deity</option>
                                    @foreach ($deities as $deity)
                                        <option value="{{ $deity->id }}" {{ ($targetDominion !== null && $targetDominion->deity->id == $title->id) ? 'selected' : null }}>
                                            {{ $deity->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xs-3 text-right">
                                Title
                            </div>
                            <div class="col-xs-3 text-left">
                                <select name="calc[title]" class="form-control" style="width: 100%;" required>
                                    <option value="0">Select a title</option>
                                    @foreach ($titles as $title)
                                        <option value="{{ $title->id }}" {{ ($targetDominion !== null && $targetDominion->title_id == $title->id) ? 'selected' : null }}>
                                            {{ $title->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Devotion
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        name="calc[devotion]"
                                        class="form-control text-center"
                                        placeholder="0"
                                        min="0"
                                        max="1000"
                                        value="{{ ($targetDominion !== null && $targetInfoOps->has('clear_sight')) ? array_get($targetInfoOps['clear_sight']->data, "morale") : null }}" />
                            </div>

                            <div class="col-xs-3 text-right">
                                Realm
                            </div>
                            <div class="col-xs-3 text-left">
                                <select name="calc[realm]" class="form-control" style="width: 100%;">
                                    <option value="0">Select a realm</option>
                                    @foreach ($realms as $realm)
                                        <option value="{{ $realm->id }}" {{ ($targetDominion !== null && $targetDominion->realm->id == $realm->id) ? 'selected' : null }}>
                                            {{ $realmHelper->getAlignmentAdjective($realm->alignment) }} (# {{ $realm->number }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @foreach ($races as $race)
                            <div id="race_{{ $race->id }}_dp" class="table-responsive race_defense_fields" style="display: none;">
                                @php
                                    $buildingFieldsRequired = [];
                                    $landFieldsRequired = [];
                                    $prestigeRequired = false;
                                    $clearSightAccuracy = 1;
                                    if ($targetDominion !== null && $targetInfoOps->has('clear_sight')) {
                                        $clearSightAccuracy = array_get($targetInfoOps['clear_sight']->data, "clear_sight_accuracy");
                                        if ($clearSightAccuracy == null || $clearSightAccuracy == 0) {
                                            $clearSightAccuracy = 1;
                                        }
                                    }
                                @endphp
                                <table class="table table-condensed">
                                    <colgroup>
                                        <col>
                                        <col width="10%">
                                        <col width="25%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Unit</th>
                                            <th>DP</th>
                                            <th class="text-center">
                                                <span data-toggle="tooltip" data-placement="top" title="Number of this unit at home">
                                                    Defending
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <thead>
                                        <tr>
                                            <td>
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getDrafteeHelpString($selectedDominion->race) }}">
                                                    {{ $raceHelper->getDrafteesTerm($race) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $race->getPerkValue('draftee_dp') ?: 1 }}
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        name="calc[draftees]"
                                                        class="form-control text-center"
                                                        placeholder="0"
                                                        min="0"
                                                        value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('clear_sight')) ? ceil(array_get($targetInfoOps['clear_sight']->data, "military_draftees") / $clearSightAccuracy) : null }}" />
                                            </td>
                                        </tr>
                                        @foreach ($race->units->sortBy('slot') as $unit)
                                            @php
                                                $buildingPerks = $unit->perks->where('key', 'defense_from_building');
                                                foreach ($buildingPerks as $perk) {
                                                    $building = explode(',', $perk->pivot->value)[0];
                                                    if (!in_array($building, $buildingFieldsRequired)) {
                                                        $buildingFieldsRequired[] = $building;
                                                    }
                                                }
                                                $landPerks = $unit->perks->where('key', 'defense_from_land');
                                                foreach ($landPerks as $perk) {
                                                    $land = explode(',', $perk->pivot->value)[0];
                                                    if (!in_array($land, $landFieldsRequired)) {
                                                        $landFieldsRequired[] = $land;
                                                    }
                                                }
                                                if ($unit->perks->where('key', 'defense_from_prestige')->count()) {
                                                    $prestigeRequired = true;
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString("unit{$unit->slot}", $race) }}">
                                                        {{ $unitHelper->getUnitName("unit{$unit->slot}", $race) }}
                                                    </span>
                                                </td>
                                                <td class="unit{{ $unit->slot }}_stats">
                                                    <span class="dp">{{ $unit->power_defense }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <input type="number"
                                                            name="calc[unit{{ $unit->slot }}]"
                                                            class="form-control text-center"
                                                            placeholder="0"
                                                            min="0"
                                                            disabled
                                                            value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('clear_sight')) ? ceil(array_get($targetInfoOps['clear_sight']->data, "military_unit{$unit->slot}") / $clearSightAccuracy) : null }}" />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </thead>
                                </table>

                                <div class="form-group row">
                                    {{--@php
                                        $racialSpell = $spellHelper->getRacialSelfSpellForScribes($race);
                                    @endphp--}}
                                    <div class="col-xs-3 text-right">
                                        DP spell
                                    </div>
                                    <div class="col-xs-3 text-left">
                                        @if (1==2)
                                            <input type="checkbox"
                                                    step="any"
                                                    name="calc[{{ $racialSpell['key'] }}]"
                                                    checked
                                                    disabled />
                                        @endif
                                    </div>
                                    @foreach ($buildingFieldsRequired as $buildingKey)
                                        @php
                                            $building = OpenDominion\Models\Building::where('key', $buildingKey)->first();
                                        @endphp
                                        <div class="col-xs-3 text-right">
                                            {{ str_plural($building->name) }}
                                        </div>
                                        <div class="col-xs-3 text-left">
                                            <input type="number"
                                                    step="any"
                                                    name="calc[{{ $building }}_percent]"
                                                    class="form-control text-center"
                                                    placeholder="0"
                                                    min="0"
                                                    disabled
                                                    value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('survey_dominion')) ? round(array_get($targetInfoOps['survey_dominion']->data, "constructed.{$building}") / array_get($targetInfoOps['survey_dominion']->data, "total_land") * 100, 2) : 50 }}" />
                                        </div>
                                    @endforeach
                                    @foreach ($landFieldsRequired as $land)
                                        <div class="col-xs-3 text-right">
                                            {{ ucwords(dominion_attr_display("land_{$land}")) }} %
                                        </div>
                                        <div class="col-xs-3 text-left">
                                            <input type="number"
                                                    step="any"
                                                    name="calc[{{ $land }}_percent]"
                                                    class="form-control text-center"
                                                    placeholder="0"
                                                    min="0"
                                                    disabled
                                                    value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('land_spy')) ? round(array_get($targetInfoOps['land_spy']->data, "explored.{$land}.percentage"), 2) : 60 }}" />
                                        </div>
                                    @endforeach
                                    @if ($prestigeRequired)
                                        <div class="col-xs-3 text-right">
                                            Prestige
                                        </div>
                                        <div class="col-xs-3 text-left">
                                            <input type="number"
                                                    name="calc[prestige]"
                                                    class="form-control text-center"
                                                    placeholder="250"
                                                    min="0"
                                                    disabled
                                                    value="{{ ($targetDominion !== null && $targetInfoOps->has('clear_sight')) ? array_get($targetInfoOps['clear_sight']->data, "prestige") : null }}" />
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                              {{--
                                 Advancement
                              --}}
                            </div>
                            <div class="col-xs-3 text-left">
                              {{--
                                <select name="calc[tech_defense]" class="form-control">
                                    <option value="0"></option>
                                    <option value="2.5" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.barricades_level1")) ? 'selected' : null }}>Barricades Level 1</option>
                                    <option value="5" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.barricades_level2")) ? 'selected' : null }}>Barricades Level 2</option>
                                    <option value="7.5" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.barricades_level3")) ? 'selected' : null }}>Barricades Level 3</option>
                                    <option value="10" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.barricades_level4")) ? 'selected' : null }}>Barricades Level 4</option>
                                    <option value="12.5" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.barricades_level5")) ? 'selected' : null }}>Barricades Level 5</option>
                                    <option value="15" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.barricades_level6")) ? 'selected' : null }}>Barricades Level 6</option>
                                    <option value="16.25" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.barricades_level7")) ? 'selected' : null }}>Barricades Level 7</option>
                                    <option value="17.5" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.barricades_level8")) ? 'selected' : null }}>Barricades Level 8</option>
                                    <option value="18.75" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.barricades_level9")) ? 'selected' : null }}>Barricades Level 9</option>
                                    <option value="20" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.barricades_level10")) ? 'selected' : null }}>Barricades Level 10</option>
                                </select>
                              --}}

                            </div>
                            <div class="col-xs-3 text-right">
                                DP% from improvements
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        step="any"
                                        name="calc[walls_percent]"
                                        class="form-control text-center"
                                        placeholder="0"
                                        min="0"
                                        value="{{ ($targetDominion !== null && $targetInfoOps->has('castle_spy')) ? array_get($targetInfoOps['castle_spy']->data, "walls.rating") * 100 : null }}" />
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Attacker's Temples %
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        step="any"
                                        name="calc[temple_percent]"
                                        class="form-control text-center"
                                        placeholder="0"
                                        min="0"
                                        max="20" />
                            </div>
                            <div class="col-xs-3 text-right">
                                Guard Towers
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        step="any"
                                        name="calc[guard_towers]"
                                        class="form-control text-center"
                                        placeholder="0"
                                        min="0"
                                        max="20"
                                        value="{{ ($targetDominion !== null && $targetInfoOps->has('survey_dominion')) ? round(array_get($targetInfoOps['survey_dominion']->data, "constructed.guard_tower") / array_get($targetInfoOps['survey_dominion']->data, "total_land") * 100, 2) : null }}" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xs-9 text-left">
                                <small class="text-muted">
                                    The calculator is in early beta and may not work accurately. Please use carefully. Accuracy is not guaranteed. <strong>Verify calculations manually.</strong>
                                </small>
                            </div>
                            <div class="col-xs-3 text-right">
                                <button class="btn btn-primary btn-block" type="button" id="calculate-defense-button">Calculate</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Results</h3>
                    <span class="pull-right-container">
                        <small class="label pull-right label-danger">Experimental</small>
                    </span>
                </div>
                <div class="box-body table-responsive">
                    <table class="table">
                        <tbody>
                            @if ($targetDominion !== null)
                                <tr class="target-dominion-dp">
                                    <td colspan="2"><b>{{ $targetDominion->name }}</b></td>
                                </tr>
                            @endif
                            <tr style="font-weight: bold;">
                                <td>Total Defense:</td>
                                <td id="dp">--</td>
                            </tr>
                            <tr>
                                <td>Defensive Multiplier:</b></td>
                                <td id="dp-multiplier">--</td>
                            </tr>
                            <tr>
                                <td>Raw Defense:</td>
                                <td id="dp-raw">--</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="box-footer">
                    <small class="text-muted">
                        The calculator is in early beta and may not work accurately. Please use carefully. Accuracy is not guaranteed. <strong>Verify calculations manually.</strong>
                    </small>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('inline-styles')
    <style type="text/css">
        .calculate-form,
        .calculate-form .table>thead>tr>td,
        .calculate-form .table>tbody>tr>td {
            line-height: 2;
        }
        .calculate-form .form-control {
            height: 30px;
            padding: 3px 6px;
        }
    </style>
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            // DEFENSE CALCULATOR
            var DPTotalElement = $('#dp');
            var DPMultiplierElement = $('#dp-multiplier');
            var DPRawElement = $('#dp-raw');
            $('#race_dp').select2().change(function (e) {
                // Hide all racial fields
                $('.race_defense_fields').hide();
                $('.race_defense_fields input').prop('disabled', true);
                $('.race_defense_fields select').prop('disabled', true);
                // Show selected racial fields
                var race_id = $(this).val();
                var race_selector = '#race_' + race_id + '_dp';
                $(race_selector + ' input').prop('disabled', false);
                $(race_selector + ' select').prop('disabled', false);
                $(race_selector).show();
                // Reset results
                DPTotalElement.text('--');
                DPMultiplierElement.text('--');
                DPRawElement.text('--');
            });
            $('#calculate-defense-button').click(function (e) {
                updateUnitDefenseStats();
            });
            function updateUnitDefenseStats() {
                if ($('#race_dp').val() == 0) return;
                // Update unit stats
                $.get(
                    "{{ route('api.calculator.defense') }}?" + $('#calculate-defense-form').serialize(), {},
                    function(response) {
                        if(response.result == 'success') {
                            $.each(response.units, function(slot, stats) {
                                // Update unit stats display
                                $('#race_'+response.race+'_dp .unit'+slot+'_stats span.dp').text(stats.dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            });
                            // Update DP display
                            DPTotalElement.text(response.dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            DPMultiplierElement.text(response.dp_multiplier.toLocaleString(undefined, {maximumFractionDigits: 2}) + '%');
                            DPRawElement.text(response.dp_raw.toLocaleString(undefined, {maximumFractionDigits: 2}));
                        }
                    }
                );
            }
            // OFFENSE CALCULATOR
            var OPTotalElement = $('#op');
            var OPMultiplierElement = $('#op-multiplier');
            var OPRawElement = $('#op-raw');
            $('#race_op').select2().change(function (e) {
                // Hide all racial fields
                $('.race_offense_fields').hide();
                $('.race_offense_fields input').prop('disabled', true);
                $('.race_offense_fields select').prop('disabled', true);
                // Show selected racial fields
                var race_id = $(this).val();
                var race_selector = '#race_' + race_id + '_op';
                $(race_selector + ' input').prop('disabled', false);
                $(race_selector + ' select').prop('disabled', false);
                $(race_selector).show();
                // Reset results
                OPTotalElement.text('--');
                OPMultiplierElement.text('--');
                OPRawElement.text('--');
            });
            $('#calculate-offense-button').click(function (e) {
                updateUnitOffenseStats();
            });
            function updateUnitOffenseStats() {
                if ($('#race_op').val() == 0) return;
                // Update unit stats
                $.get(
                    "{{ route('api.calculator.offense') }}?" + $('#calculate-offense-form').serialize(), {},
                    function(response) {
                        if(response.result == 'success') {
                            $.each(response.units, function(slot, stats) {
                                // Update unit stats display
                                $('#race_'+response.race+'_op .unit'+slot+'_stats span.op').text(stats.op.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            });
                            // Update DP display
                            OPTotalElement.text(response.op.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            OPMultiplierElement.text(response.op_multiplier.toLocaleString(undefined, {maximumFractionDigits: 2}) + '%');
                            OPRawElement.text(response.op_raw.toLocaleString(undefined, {maximumFractionDigits: 2}));
                        }
                    }
                );
            }
            @if ($targetDominion !== null)
                $('#race_dp').trigger('change');
                //$('#calculate-defense-button').trigger('click');
                $('#race_dp').select2().change(function (e) {
                    $('.target-dominion-dp').hide();
                });
                $('#race_op').trigger('change');
                //$('#calculate-offense-button').trigger('click');
                $('#race_op').select2().change(function (e) {
                    $('.target-dominion-op').hide();
                });
            @endif
        })(jQuery);
    </script>
@endpush
