@extends ('layouts.master')
@section('title', 'Desecrate')

@section('content')

<div class="row">

    <div class="col-sm-12 col-md-9">
        <form action="{{ route('dominion.desecrate') }}" method="post" role="form" id="desecrate_form">
                @csrf

                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-tombstone"></i> Desecrate</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="battlefield">Select a battlefield</label>
                            <select name="battlefield" id="battlefield" class="form-control select2" required style="width: 100%" data-placeholder="Select a battlefield" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                <option></option>
                                @foreach ($desecrationCalculator->getAvailableBattlefields($selectedDominion) as $battlefield)
                                    @php
                                        $bodies = 'Unknown number of bodies';
                                        if($selectedDominion->getSpellPerkValue('can_see_battlefield_bodies'))
                                        {
                                            $bodies = $battlefield->data['result']['bodies']['available'];
                                        }

                                        $isDesecrated = ($battlefield->data['result']['bodies']['desecrated'] > 0);
                                    @endphp

                                    <option value="{{ $battlefield->id }}"
                                            data-bodies="{{ $bodies }}"
                                            data-desecrated="{{ $isDesecrated }}"
                                            >
                                        {{ $battlefield->created_at }} - 
                                        @if($battlefield->type == 'invasion')
                                            {{ $battlefield->source->name }}

                                            @if($battlefield->data['result']['success'])
                                                successfully
                                            @endif

                                            @if($battlefield->data['result']['isAmbus'])
                                                ambushed
                                            @endif

                                            {{ $battlefield->target->name }} (# {{ $battlefield->target->realm->number }}) 

                                            @if($battlefield->data['result']['success'])
                                                conquering {{ $battlefield->data['result']['land'] }} acres
                                            @endif

                                        @elseif($battlefield->type == 'barbarian_invasion')
                                            {{ $battlefield->source->name }} (# {{ $battlefield->source->realm->number }})  {{ $battlefield->data['type'] }} a {{ $battlefield->data['target'] }} for {{ $battlefield->data['land'] }} acres
                                        @else
                                            Unsupported type: {{ $battlefield->type }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-users"></i> Units to send</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="100">
                                <col width="150">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th class="text-center">Available</th>
                                    <th class="text-center">Send</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($selectedDominion->race->units as $unit)

                                    @if(!$unit->getPerkValue('desecration'))
                                        @continue
                                    @endif

                                    @php
                                        $unitType = 'unit' . $unit->slot;
                                    @endphp

                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'offense'), $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'defense'), ]) }}">
                                                {{ $unit->name }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            {{ number_format($selectedDominion->{"military_unit{$unit->slot}"}) }}
                                        </td>
                                        <td class="text-center">
                                            <input type="number"
                                                   name="unit[{{ $unit->slot }}]"
                                                   id="unit[{{ $unit->slot }}]"
                                                   class="form-control text-center"
                                                   placeholder="0"
                                                   min="0"
                                                   max="{{ $selectedDominion->{"military_unit{$unit->slot}"} }}"
                                                   style="min-width:5em;"
                                                   data-slot="{{ $unit->slot }}"
                                                   data-amount="{{ $selectedDominion->{"military_unit{$unit->slot}"} }}"
                                                   {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12 col-md-6">

                        <div class="box box-danger">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="ra ra-sword"></i> Invasion force</h3>
                            </div>
                            <div class="box-body table-responsive no-padding">
                                <table class="table">
                                    <colgroup>
                                        <col width="30%">
                                        <col width="70%">
                                    </colgroup>
                                    <tbody>
                                        <tr>
                                            <td>OP:</td>
                                            <td>
                                                <strong id="invasion-force-op" data-amount="0">0</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Morale:</td>
                                            <td>{{ number_format($selectedDominion->morale) }}</td>
                                        </tr>
                                        {{-- 
                                        <tr>
                                            <td>DP:</td>
                                            <td id="invasion-force-dp" data-amount="0">0</td>
                                        </tr>
                                        --}}
                                        <tr>
                                            <td>
                                                Max OP:
                                                <i class="fa fa-question-circle"
                                                   data-toggle="tooltip"
                                                   data-placement="top"
                                                   title="You may send out a maximum of 133% of your new home DP in OP. (4:3 rule)"></i>
                                            </td>
                                            <td id="invasion-force-max-op" data-amount="0">0</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                Target DP:
                                            </td>
                                            <td id="target-dp" data-amount="0">0</td>
                                        </tr>
                                        <tr>
                                            <td>Land conquered:</td>
                                            <td id="invasion-land-conquered" data-amount="0">0</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="box-footer">
                                <button type="submit"
                                        class="btn btn-danger"
                                        {{ $selectedDominion->isLocked() ? 'disabled' : null }}
                                        id="invade-button">
                                    <i class="ra ra-crossed-swords"></i>
                                    Send Units
                                </button>
                            </div>
                        </div>

                    </div>
                    <div class="col-sm-12 col-md-6">

                        <div class="box">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-home"></i> Military At Home</h3>
                            </div>
                            <div class="box-body table-responsive no-padding">
                                <table class="table">
                                    <colgroup>
                                        <col width="30%">
                                        <col width="70%">
                                    </colgroup>
                                    <tbody>
                                        {{--
                                        <tr>
                                            <td>OP:</td>
                                            <td id="home-forces-op" data-original="{{ $militaryCalculator->getOffensivePower($selectedDominion) }}" data-amount="0">
                                                {{ number_format($militaryCalculator->getOffensivePower($selectedDominion), 2) }}
                                            </td>
                                        </tr>
                                        --}}
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
                                        {{--
                                        <tr>
                                            <td>
                                                Min DP:
                                                <i class="fa fa-question-circle"
                                                   data-toggle="tooltip"
                                                   data-placement="top"
                                                   title="You must leave at least 33% of your invasion force OP in DP at home. (33% rule)"></i>
                                            </td>
                                            <td id="home-forces-min-dp" data-amount="0">0</td>
                                        </tr>
                                        --}}
                                        <tr>
                                            <td>DPA:</td>
                                            <td id="home-forces-dpa" data-amount="0">
                                                {{ number_format($militaryCalculator->getDefensivePower($selectedDominion) / $selectedDominion->land, 2) }}
                                            </td>
                                        </tr>
                                        @if($selectedDominion->getSpellPerkValue('fog_of_war'))
                                            @php
                                                $spell = OpenDominion\Models\Spell::where('key', 'sazals_fog')->firstOrFail();
                                            @endphp
                                            <tr>
                                                <td>Sazal's Fog:</td>
                                                <td>
                                                    {{ number_format($spellCalculator->getSpellDuration($selectedDominion, $spell->key)) }} {{ str_plural('tick', $spellCalculator->getSpellDuration($selectedDominion, $spell->key)) }}
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

            </form>

            <div class="">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-boot-stomp"></i> Units returning</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                @for ($i = 1; $i <= 12; $i++)
                                    <col width="6%">
                                @endfor
                                <col width="100">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    @for ($i = 1; $i <= 12; $i++)
                                        <th class="text-center">{{ $i }}</th>
                                    @endfor
                                    <th class="text-center"><br>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($selectedDominion->race->units as $unit)
                                    @php
                                        $unitType = ('unit' . $unit->slot)
                                    @endphp

                                    @if(!$unit->getPerkValue('desecration'))
                                        @continue
                                    @endif

                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'offense'), $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'defense'), ]) }}">
                                                {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                                            </span>
                                        </td>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <td class="text-center">
                                                @if ($queueService->getDesecrationQueueAmount($selectedDominion, "military_{$unitType}", $i) === 0)
                                                    -
                                                @else
                                                    {{ number_format($queueService->getDesecrationQueueAmount($selectedDominion, "military_{$unitType}", $i)) }}
                                                @endif
                                            </td>
                                        @endfor
                                        <td class="text-center">
                                            {{ number_format($queueService->getDesecrationQueueTotalByResource($selectedDominion, "military_{$unitType}")) }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td>
                                        Bodies
                                    </td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        <td class="text-center">
                                            @if ($queueService->getDesecrationQueueAmount($selectedDominion, "resource_body", $i) === 0)
                                                -
                                            @else
                                                {{ number_format($queueService->getDesecrationQueueAmount($selectedDominion, "resource_body", $i)) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        {{ number_format($queueService->getDesecrationQueueTotalByResource($selectedDominion, "resource_body")) }}
                                    </td>
                                </tr>
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
                <p>Here you can invade other players to try to capture some of their land and to gain prestige. Invasions are successful if you send more OP than they have DP.</p>
                <p>If you hit the same target within two hours, you will not discover additional land. You will only get the acres you conquer. Note that this is down to the <em>exact second</em> of your previous hit and includes failed invasions.</p>
                <p>You will only gain prestige on targets 75% or greater relative to your own land size.</p>
                <p>For every acre you gain, you receive 25 experience points.</p>
                <p>Note that minimum raw DP a target can have is 10 DP per acre.</p>

                @if ($militaryCalculator->getRecentlyInvadedCount($selectedDominion) and $selectedDominion->race->name == 'Sylvan')
                    <hr />
                    <p><strong>You were recently invaded, enraging your Spriggan and Leshy.</strong></p>
                @endif

            </div>
        </div>
    </div>

</div>

@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
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
            var targetDpElement = $('#target-dp');

            var invasionForceCountElement = $('#invasion-total-units');

            var invadeButtonElement = $('#invade-button');
            var allUnitInputs = $('input[name^=\'unit\']');

            $('#battlefield').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });

            updateUnitStats();

            $('#target_dominion').change(function (e) {
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
                    "{{ route('api.dominion.invasion') }}?" + $('#desecrate_form').serialize(), {},
                    function(response) {
                        if(response.result == 'success')
                        {
                            $.each(response.units, function(slot, stats)
                            {
                                // Update unit stats data attributes
                                $('#unit\\['+slot+'\\]').data('dp', stats.dp);
                                $('#unit\\['+slot+'\\]').data('op', stats.op);
                                // Update unit stats display
                                $('#unit'+slot+'_dp').text(stats.dp.toLocaleString(undefined, {maximumFractionDigits: 3}));
                                $('#unit'+slot+'_op').text(stats.op.toLocaleString(undefined, {maximumFractionDigits: 3}));
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
                            targetDpElement.data('amount', response.target_dp);

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
                            targetDpElement.text(response.target_dp.toLocaleString(undefined, {maximumFractionDigits: 0}));

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

            const bodies = state.element.dataset.bodies;
            const desecrated = state.element.dataset.desecrated;
            const fogged = state.element.dataset.fogged;

            desecratedStatus = '';
            if (desecrated == 1) {
                desecrated = '&nbsp;<div class="pull-left">&nbsp;<span class="label label-warning">Previously desecrated</span></div>';
            }

            return $(`
                <div class="pull-left">${state.text}</div>
                ${desecratedStatus}
                <div class="pull-right">${bodies} bodies</div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush

@push('page-scripts')
    <script type="text/javascript">
    $("form").submit(function () {
        // prevent duplicate form submissions
        $(this).find(":submit").attr('disabled', 'disabled');
    });
    </script>
@endpush

