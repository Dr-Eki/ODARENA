@extends ('layouts.master')
@section('title', 'Artefacts')

@section('content')

<div class="row">
    <div class="col-sm-9 col-md-9">
        <form action="{{ route('dominion.artefacts') }}" method="post" role="form" id="artefacts_form">
                @csrf

            <div class="row">
                <div class="col-sm-12">
                    <div class="box box-warning">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-alien-fire"></i> Artefact</h3>
                        </div>
                        <div class="box-body">
                            <div class="form-group">
                                <label for="target_artefact">Select artefact</label>
                                <select name="target_artefact" id="target_artefact" class="form-control select2" required style="width: 100%" data-placeholder="Select artefact" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    <option></option>
                                    @foreach ($otherRealmArtefacts as $realmArtefact)
                                        <option value="{{ $realmArtefact->id }}"
                                                data-power="{{ number_format($realmArtefact->power) }}"
                                                data-maxpower="{{ number_format($realmArtefact->max_power) }}">
                                            {{ $realmArtefact->artefact->name }} (#{{ $realmArtefact->realm->number }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($artefactCalculator->getDamageType($selectedDominion) == 'military')
                <div class="row">
                    <div class="col-sm-12">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                            </div>
                            <div class="box-body table-responsive no-padding">
                                <table class="table">
                                    <colgroup>
                                        <col>
                                        <col width="100">
                                        <col width="100">
                                        <col width="100">
                                        <col width="150">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Unit</th>
                                            <th class="text-center">OP / DP</th>
                                            <th class="text-center">Available</th>
                                            <th class="text-center">Send</th>
                                            <th class="text-center">Total OP / DP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $offenseVsBuildingTypes = [];
                                            $offenseVsLandTypes = [];
                                            $offenseVsPrestige = [];
                                            $offenseVsBarren = [];
                                            $offenseVsResource = [];
                                            $offenseVsOpposingUnits = [];
                                            $offenseFromMob = [];
                                            $offenseFromBeingOutnumbered = [];
                                        @endphp
                                        @foreach (range(1, $selectedDominion->race->units->count()) as $unitSlot)
                                            @php
                                                $unit = $selectedDominion->race->units->filter(function ($unit) use ($unitSlot) {
                                                    return ($unit->slot === $unitSlot);
                                                })->first();
                                            @endphp

                                            @if ($unit->power_offense == 0 and $unit->getPerkValue('sendable_with_zero_op') != 1)
                                                @continue
                                            @endif

                                            @php
                                                $offensivePower = $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unit, 'offense');
                                                $defensivePower = $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unit, 'defense');

                                                $hasDynamicOffensivePower = $unit->perks->filter(static function ($perk) {
                                                    return starts_with($perk->key, ['offense_from_', 'offense_staggered_', 'offense_vs_', 'offense_m']);
                                                })->count() > 0;

                                                $hasDynamicDefensivePower = $unit->perks->filter(static function ($perk) {
                                                    return starts_with($perk->key, ['defense_from_', 'defense_staggered_', 'defense_vs_']);
                                                })->count() > 0;

                                                $unitType = 'unit' . $unitSlot;
                                            @endphp

                                            <tr>
                                                <td>
                                                    <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'offense'), $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'defense'), ]) }}">
                                                        {{ $unitHelper->getUnitName("unit{$unitSlot}", $selectedDominion->race) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span id="unit{{ $unitSlot }}_op">{{ floatval($offensivePower) }}</span>{{ $hasDynamicOffensivePower ? '*' : null }}
                                                    /
                                                    <span id="unit{{ $unitSlot }}_dp" class="text-muted">{{ floatval($defensivePower) }}</span><span class="text-muted">{{ $hasDynamicDefensivePower ? '*' : null }}</span>
                                                </td>
                                                <td class="text-center">
                                                    {{ number_format($selectedDominion->{"military_unit{$unitSlot}"}) }}
                                                </td>
                                                <td class="text-center">
                                                    <input type="number"
                                                        name="unit[{{ $unitSlot }}]"
                                                        id="unit[{{ $unitSlot }}]"
                                                        class="form-control text-center"
                                                        placeholder="0"
                                                        min="0"
                                                        max="{{ $selectedDominion->{"military_unit{$unitSlot}"} }}"
                                                        style="min-width:5em;"
                                                        data-slot="{{ $unitSlot }}"
                                                        data-amount="{{ $selectedDominion->{"military_unit{$unitSlot}"} }}"
                                                        data-op="{{ $unit->power_offense }}"
                                                        data-dp="{{ $unit->power_defense }}"
                                                        {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                </td>
                                                <td class="text-center" id="unit{{ $unitSlot }}_stats">
                                                    <span class="op">0</span> / <span class="dp text-muted">0</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-6">
                        <div class="box box-danger">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="ra ra-sword"></i> Attack force</h3>
                            </div>
                            <div class="box-body table-responsive no-padding">
                                <table class="table">
                                    <colgroup>
                                        <col width="50%">
                                        <col width="50%">
                                    </colgroup>
                                    <tbody>
                                        <tr>
                                            <td>Damage dealt:</td>
                                            <td>
                                                <strong id="invasion-force-op" data-amount="0">0</strong>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="box-footer">
    
                            @if ($selectedDominion->isSpellActive('rainy_season'))
                                <p><strong><em>You cannot attack during the Rainy Season.</em></strong></p>
    
                            @elseif ($selectedDominion->isSpellActive('stasis'))
                                <p><strong><em>You cannot attack while you are in stasis.</em></strong></p>
    
                            @elseif ($selectedDominion->isSpellActive('flood_the_caverns'))
                                <p><strong><em>You cannot attack while the caverns are flooded.</em></strong></p>
    
                            @elseif ($protectionService->isUnderProtection($selectedDominion))
                                <p><strong><em>You are currently under protection for <b>{{ $selectedDominion->protection_ticks }}</b> {{ str_plural('tick', $selectedDominion->protection_ticks) }} and may not attack during that time.</em></strong></p>
    
                            @elseif (!$selectedDominion->round->hasStarted())
                                <p><strong><em>You cannot attack until the round has started.</em></strong></p>
    
                            @elseif ($selectedDominion->morale < 50)
                                <p><strong><em>Your military needs at least 50% morale to attack. You currently have {{ $selectedDominion->morale }} morale.</em></strong></p>
    
                            @else
                                @if($selectedDominion->race->name == 'Dimensionalists')
                                    @if($resourceCalculator->getAmount($selectedDominion, 'cosmic_alignment') >= $selectedDominion->race->getPerkValue('cosmic_alignment_to_invade'))
                                        <button type="submit"
                                                class="btn btn-danger"
                                                {{ $selectedDominion->isLocked() ? 'disabled' : null }}
                                                id="invade-button">
                                            <i class="ra ra-player-teleport"></i>
                                            Plot chart and teleport units
                                        </button>
    
                                        <br><span class="label label-info">This will expend {{ number_format($selectedDominion->race->getPerkValue('cosmic_alignment_to_invade')) }} Cosmic Alignments.</span>
    
                                    @else
                                        <span class="label label-danger">You need at least {{ number_format($selectedDominion->race->getPerkValue('cosmic_alignment_to_invade')) }} Cosmic Alignments to plot a chart to teleport units. Currently: {{ number_format($resourceCalculator->getAmount($selectedDominion, 'cosmic_alignment')) }}.</span>
                                    @endif
    
                                @else
                                    <button type="submit"
                                            class="btn btn-danger"
                                            {{ $selectedDominion->isLocked() ? 'disabled' : null }}
                                            id="invade-button">
                                        <i class="ra ra-crossed-swords"></i>
                                        Send Units
                                    </button>
                                @endif
                            @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <div class="box box-success">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-home"></i> Status At Home</h3>
                            </div>
                            <div class="box-body table-responsive no-padding">
                                <table class="table">
                                    <colgroup>
                                        <col width="50%">
                                        <col width="50%">
                                    </colgroup>
                                    <tbody>
                                        <tr>
                                            <td>Mod DP:</td>
                                            <td>
                                                <span id="home-forces-dp" data-original="{{ $militaryCalculator->getDefensivePower($selectedDominion) }}" data-amount="0">
                                                    {{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }}
                                                </span>
    
                                                <small class="text-muted">
                                                    (<span id="home-forces-dp-raw" data-original="{{ $militaryCalculator->getDefensivePowerRaw($selectedDominion) }}" data-amount="0">{{ number_format($militaryCalculator->getDefensivePowerRaw($selectedDominion)) }}</span> raw)
                                                </small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>DPA:</td>
                                            <td id="home-forces-dpa" data-amount="0">
                                                {{ number_format($militaryCalculator->getDefensivePower($selectedDominion) / $selectedDominion->land, 2) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </form>
    </div>
    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>Begin by selecting which artefact you want to target.</p>
                @if($artefactCalculator->getDamageType($selectedDominion) == 'military')
                    <p>Then select the units you want to send to attack the artefact.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@php
    $realmArtefacts = $selectedDominion->realm->realmArtefacts;
    $realmArtefactsCount = $selectedDominion->realm->artefacts->count();
@endphp
    @if($realmArtefactsCount)
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-castle"></i> Realm Artefacts</h3>
                </div>
                <div class="box-body">
                    <div class="box box-primary table-responsive" id="spells-cast">
                        <table class="table table-striped table-hover" id="spells-cast-table">
                            <colgroup>
                                <col>
                                <col width="200">
                                <col width="400">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Artefact</th>
                                    <th>Aegis</th>
                                    <th>Perks</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($realmArtefacts as $realmArtefact)
                                @php
                                    $realmArtefactPowerRatio = $realmArtefact->power / $realmArtefact->max_power;

                                    if($realmArtefactPowerRatio < 0.10)
                                    {
                                        $powerColor = 'danger';
                                    }
                                    elseif($realmArtefactPowerRatio < 0.65)
                                    {
                                        $powerColor = 'warning';
                                    }
                                    elseif($realmArtefactPowerRatio < 1)
                                    {
                                        $powerColor = 'info';
                                    }
                                    else
                                    {
                                        $powerColor = 'success';
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $realmArtefact->artefact->name }}</strong><br>
                                        <i class="text-muted">{{ $realmArtefact->artefact->description }}</i>
                                    </td>
                                    <td>
                                        <span class="label label-{{ $powerColor }}">{{ number_format($realmArtefact->power) }} / {{ number_format($realmArtefact->max_power) }}</span>
                                    <td>
                                        <ul>
                                            @foreach($artefactHelper->getArtefactPerksString($realmArtefact->artefact) as $effect)
                                                <li>{{ ucfirst($effect) }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Your realm has <strong>{{ number_format($realmArtefactsCount) }} {{ str_plural('artefact', $realmArtefactsCount) }}</strong>.</p>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/admin-lte/plugins/bootstrap-slider/slider.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/admin-lte/plugins/bootstrap-slider/bootstrap-slider.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">

        $(document).keypress(
            function(event)
            {
                if (event.which == '13')
                {
                    event.preventDefault();
                }
            }
        );

        (function ($) {
            var invasionForceOPElement = $('#invasion-force-op');
            var invasionForceDPElement = $('#invasion-force-dp');
            var invasionForceMaxOPElement = $('#invasion-force-max-op');
            var homeForcesOPElement = $('#home-forces-op');
            var homeForcesDPElement = $('#home-forces-dp');
            var homeForcesDPRawElement = $('#home-forces-dp-raw');
            var homeForcesMinDPElement = $('#home-forces-min-dp');
            var homeForcesDPAElement = $('#home-forces-dpa');
            var invasionLandConqueredElement = $('#invasion-land-conquered');

            var invasionForceCountElement = $('#invasion-total-units');

            var invadeButtonElement = $('#invade-button');
            var allUnitInputs = $('input[name^=\'unit\']');

            $('#target_artefact').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });

            @if (!$protectionService->isUnderProtection($selectedDominion))
                updateUnitStats();
            @endif

            $('#target_artefact').change(function (e) {
                updateUnitStats();
            });

            $('input[name^=\'calc\']').change(function (e) {
                updateUnitStats();
            });

            $('input[name^=\'unit\']').change(function (e) {
                updateUnitStats();
            });

            function updateUnitStats() {
                // Update unit stats
                $.get(
                    "{{ route('api.dominion.invasion') }}?" + $('#artefacts_form').serialize(), {},
                    function(response) {
                        if(response.result == 'success')
                        {
                            $.each(response.units, function(slot, stats)
                            {
                                // Update unit stats data attributes
                                $('#unit\\['+slot+'\\]').data('dp', stats.dp);
                                $('#unit\\['+slot+'\\]').data('op', stats.op);
                                // Update unit stats display
                                $('#unit'+slot+'_dp').text(stats.dp.toLocaleString(undefined, {maximumFractionDigits: 5}));
                                $('#unit'+slot+'_op').text(stats.op.toLocaleString(undefined, {maximumFractionDigits: 5}));
                            });

                            // Update OP / DP data attributes
                            invasionForceOPElement.data('amount', response.away_offense);
                            invasionForceDPElement.data('amount', response.away_defense);
                            invasionForceMaxOPElement.data('amount', response.max_op);
                            invasionLandConqueredElement.data('amount', response.land_conquered);
                            homeForcesOPElement.data('amount', response.home_offense);
                            homeForcesDPElement.data('amount', response.home_defense);
                            homeForcesDPRawElement.data('amount', response.home_defense_raw);
                            homeForcesMinDPElement.data('amount', response.min_dp);
                            homeForcesDPAElement.data('amount', response.home_dpa);

                            // Update OP / DP display
                            invasionForceOPElement.text(response.away_offense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            invasionForceDPElement.text(response.away_defense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            invasionForceMaxOPElement.text(response.max_op.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            invasionLandConqueredElement.text(response.land_conquered.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesOPElement.text(response.home_offense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesDPElement.text(response.home_defense.toLocaleString(undefined, {maximumFractionDigits: 0}));
                            homeForcesDPRawElement.text(response.home_defense_raw.toLocaleString(undefined, {maximumFractionDigits: 0}));
                            homeForcesMinDPElement.text(response.min_dp.toLocaleString(undefined, {maximumFractionDigits: 0}));
                            homeForcesDPAElement.text(response.home_dpa.toLocaleString(undefined, {maximumFractionDigits: 0}));

                            invasionForceCountElement.text(response.units_sent);

                            calculate();
                        }
                    }
                );
            }

            function calculate() {
                // Calculate subtotals for each unit
                allUnitInputs.each(function () {
                    var unitOP = parseFloat($(this).data('op'));
                    var unitDP = parseFloat($(this).data('dp'));
                    var amountToSend = parseInt($(this).val() || 0);
                    var totalUnitOP = amountToSend * unitOP;
                    var totalUnitDP = amountToSend * unitDP;
                    var unitSlot = parseInt($(this).data('slot'));
                    var unitStatsElement = $('#unit' + unitSlot + '_stats');
                    unitStatsElement.find('.op').text(totalUnitOP.toLocaleString(undefined, {maximumFractionDigits: 2}));
                    unitStatsElement.find('.dp').text(totalUnitDP.toLocaleString(undefined, {maximumFractionDigits: 2}));
                });

                // Check 33% rule
                var minDefenseRule = parseFloat(homeForcesDPElement.data('amount')) < parseFloat(homeForcesMinDPElement.data('amount'));
                if (minDefenseRule) {
                    homeForcesDPElement.addClass('text-danger');
                } else {
                    homeForcesDPElement.removeClass('text-danger');
                }

                // Check 4:3 rule
                var maxOffenseRule = parseFloat(invasionForceOPElement.data('amount')) > parseFloat(invasionForceMaxOPElement.data('amount'));
                if (maxOffenseRule) {
                    invasionForceOPElement.addClass('text-danger');
                } else {
                    invasionForceOPElement.removeClass('text-danger');
                }

                // Check if invade button should be disabled
                if (minDefenseRule || maxOffenseRule) {
                    invadeButtonElement.attr('disabled', 'disabled');
                } else {
                    invadeButtonElement.removeAttr('disabled');
                }


            }
        })(jQuery);


        function select2Template(state) {
            if (!state.id) {
                return state.text;
            }

            const power = parseInt(state.element.dataset.power);
            const maxpower = parseInt(state.element.dataset.maxpower);

            let realmArtefactPowerRatio = power / maxpower;
            let powerColor;

            if (realmArtefactPowerRatio < 0.10) {
                powerColor = 'danger';
            } else if (realmArtefactPowerRatio < 0.65) {
                powerColor = 'warning';
            } else if (realmArtefactPowerRatio < 1) {
                powerColor = 'info';
            } else {
                powerColor = 'success';
            }

            return $(`
                <div class="pull-left">${state.text}</div>
                <div class="pull-right">Aegis: <span class="label label-${powerColor}">${power} / ${maxpower}</span></div>
                <div style="clear: both;"></div>
            `);
        }
    </script>

    <script type="text/javascript">
        (function ($) {
            const resources = JSON.parse('{!! json_encode([1,2,3,4,5,6,7,8,9,10]) !!}');

            // todo: let/const aka ES6 this
            var sourceElement = $('#source'),
                targetElement = $('#target'),
                amountElement = $('#amount'),
                amountLabelElement = $('#amountLabel'),
                amountSliderElement = $('#amountSlider'),
                resultLabelElement = $('#resultLabel'),
                resultElement = $('#result');

            function updateResources() {
                var sourceOption = sourceElement.find(':selected'),
                    sourceResourceType = _.get(resources, sourceOption.val()),
                    sourceAmount = Math.min(parseInt(amountElement.val()), _.get(sourceResourceType, 'max')),
                    targetOption = targetElement.find(':selected'),
                    targetResourceType = _.get(resources, targetOption.val()),
                    targetAmount = (Math.floor(sourceAmount * sourceResourceType['sell'] * targetResourceType['buy']) || 0);

                // Change labels
                amountLabelElement.text(sourceOption.text());
                resultLabelElement.text(targetOption.text());

                // Update amount
                amountElement
                    .attr('max', sourceResourceType['max'])
                    .val(sourceAmount);

                // Update slider
                amountSliderElement
                    .slider('setAttribute', 'max', sourceResourceType['max'])
                    .slider('setValue', sourceAmount);

                // Update target amount
                resultElement.text(targetAmount.toLocaleString());
            }

            sourceElement.on('change', updateResources);
            targetElement.on('change', updateResources);
            amountElement.on('change', updateResources);

            amountSliderElement.slider({
                formatter: function (value) {
                    return value.toLocaleString();
                }
            }).on('change', function (slideEvent) {
                amountElement.val(slideEvent.value.newValue).change();
            });

            updateResources();
        })(jQuery);
    </script>

@endpush
