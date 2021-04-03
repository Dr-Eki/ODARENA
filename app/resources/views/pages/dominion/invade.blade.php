@extends ('layouts.master')

{{--
@section('page-header', 'Invade')
--}}

@section('content')

    <div class="row">

        <div class="col-sm-12 col-md-9">
            <form action="{{ route('dominion.invade') }}" method="post" role="form" id="invade_form">
                    @csrf

                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-crossed-swords"></i> Invade</h3>
                        </div>
                        <div class="box-body">
                            <div class="form-group">
                                <label for="target_dominion">Select a target</label>
                                <select name="target_dominion" id="target_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    <option></option>
                                    @foreach ($rangeCalculator->getDominionsInRange($selectedDominion) as $dominion)
                                        <option value="{{ $dominion->id }}"
                                                data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}"
                                                data-networth="{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}"
                                                data-percentage="{{ $rangeCalculator->getDominionRange($selectedDominion, $dominion) }}"
                                                data-war="{{ $governmentService->isAtWarWithRealm($selectedDominion->realm, $dominion->realm) ? 1 : 0 }}">
                                            {{ $dominion->name }} (#{{ $dominion->realm->number }}) - {{ $dominion->race->name }}
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
                                    @endphp
                                    @foreach (range(1, 4) as $unitSlot)
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
                                            if ($hasDynamicOffensivePower)
                                            {
                                                $offenseVsBuildingPerk = $unit->getPerkValue('offense_vs_building');
                                                if ($offenseVsBuildingPerk) {
                                                    $offenseVsBuildingTypes[] = explode(',', $offenseVsBuildingPerk)[0];
                                                }

                                                $offenseVsLandPerk = $unit->getPerkValue('offense_vs_land');
                                                if ($offenseVsLandPerk) {
                                                    $offenseVsLandTypes[] = explode(',', $offenseVsLandPerk)[0];
                                                }

                                                $offenseVsPrestigePerk = $unit->getPerkValue('offense_vs_prestige');
                                                if ($offenseVsPrestigePerk) {
                                                    $offenseVsPrestige[] = explode(',', $offenseVsPrestigePerk)[0];
                                                }

                                                $offenseVsBarrenPerk = $unit->getPerkValue('offense_vs_barren_land');
                                                if ($offenseVsBarrenPerk) {
                                                    $offenseVsBarren[] = explode(',', $offenseVsBarrenPerk)[0];
                                                }

                                                $offenseVsResourcePerk = $unit->getPerkValue('offense_vs_resource');
                                                if ($offenseVsResourcePerk) {
                                                    $offenseVsResource = explode(',', $offenseVsResourcePerk)[0];
                                                }

                                                $offenseFromMobPerk = $unit->getPerkValue('offense_mob');
                                                if ($offenseFromMobPerk) {
                                                    $offenseFromMob = explode(',', $offenseFromMobPerk)[0];
                                                }

                                            }
                                            $hasDynamicDefensivePower = $unit->perks->filter(static function ($perk) {
                                                return starts_with($perk->key, ['defense_from_', 'defense_staggered_', 'defense_vs_']);
                                            })->count() > 0;
                                        @endphp

                                        <tr>
                                            <td>
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString("unit{$unitSlot}", $selectedDominion->race) }}">
                                                    {{ $unitHelper->getUnitName("unit{$unitSlot}", $selectedDominion->race) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span id="unit{{ $unitSlot }}_op">{{ (strpos($offensivePower, '.') !== false) ? number_format($offensivePower, 2) : number_format($offensivePower) }}</span>{{ $hasDynamicOffensivePower ? '*' : null }}
                                                /
                                                <span id="unit{{ $unitSlot }}_dp" class="text-muted">{{ (strpos($defensivePower, '.') !== false) ? number_format($defensivePower, 2) : number_format($defensivePower) }}</span><span class="text-muted">{{ $hasDynamicDefensivePower ? '*' : null }}</span>
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
                                    @foreach ($offenseVsBuildingTypes as $buildingType)
                                        <tr>
                                            <td colspan="3" class="text-right">
                                                <b>Enter target {{ ucwords(str_replace('_', ' ', $buildingType)) }} percentage:</b>
                                            </td>
                                            <td>
                                                <input type="number"
                                                       name="calc[{{ $buildingType }}_percent]"
                                                       class="form-control text-center"
                                                       min="0"
                                                       max="100"
                                                       placeholder="0"
                                                       {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            </td>
                                            <td>&nbsp;</td>
                                        </tr>
                                    @endforeach
                                    @foreach ($offenseVsLandTypes as $landType)
                                        <tr>
                                            <td colspan="3" class="text-right">
                                                <b>Enter target {{ ucwords(str_replace('_', ' ', $landType)) }} percentage:</b>
                                            </td>
                                            <td>
                                                <input type="number"
                                                       name="calc[{{ $landType }}_percent]"
                                                       class="form-control text-center"
                                                       min="0"
                                                       max="100"
                                                       placeholder="0"
                                                       {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            </td>
                                            <td>&nbsp;</td>
                                        </tr>
                                    @endforeach
                                    @if($offenseVsPrestige)
                                        <tr>
                                            <td colspan="3" class="text-right">
                                                <b>Enter target prestige:</b>
                                            </td>
                                            <td>
                                                <input type="number"
                                                       name="calc[prestige]"
                                                       class="form-control text-center"
                                                       min="0"
                                                       max="100"
                                                       placeholder="0"
                                                       {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            </td>
                                            <td>&nbsp;</td>
                                        </tr>
                                    @endif
                                    @if($offenseVsBarren)
                                        <tr>
                                            <td colspan="3" class="text-right">
                                                <b>Enter target barren percentage:</b>
                                            </td>
                                            <td>
                                                <input type="number"
                                                       name="calc[barren_land_percent]"
                                                       class="form-control text-center"
                                                       min="0"
                                                       max="100"
                                                       placeholder="0"
                                                       {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            </td>
                                            <td>&nbsp;</td>
                                        </tr>
                                    @endif
                                    @if($offenseVsResource)
                                        <tr>
                                            <td colspan="3" class="text-right">
                                                <b>Enter target {{ $offenseVsResource }} amount:</b>
                                            </td>
                                            <td>
                                                <input type="number"
                                                       name="calc[{{ $offenseVsResource }}]"
                                                       class="form-control text-center"
                                                       min="0"
                                                       placeholder="0"
                                                       {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            </td>
                                            <td>&nbsp;</td>
                                        </tr>
                                    @endif
                                    @if($offenseFromMob)
                                        <tr>
                                            <td colspan="3" class="text-right">
                                                <b>Enter total number of units target has at home:</b>
                                            </td>
                                            <td>
                                                <input type="number"
                                                       name="calc[opposing_units]"
                                                       class="form-control text-center"
                                                       min="0"
                                                       placeholder="0"
                                                       {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                  {{-- Tried and failed to get this to be a hidden input, but textarea works... --}}
                                                  <textarea type="hidden" style="display: none;"
                                                         id="invasion-total-units"
                                                         name="calc[units_sent]"
                                                         class="form-control text-center"
                                                         min="0"
                                                         placeholder="0"
                                                         data-amount="0"
                                                         value=""
                                                         {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                       </textarea>


                                            </td>
                                        </tr>
                                    @endif
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
                                            <col width="50%">
                                            <col width="50%">
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
                                                <td>{{ number_format($selectedDominion->morale) }}%</td>
                                            </tr>                                            <!--
                                            <tr>
                                                <td>DP:</td>
                                                <td id="invasion-force-dp" data-amount="0">0</td>
                                            </tr>
                                          -->
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
                                                <td>Land conquered:</td>
                                                <td id="invasion-land-conquered" data-amount="0">0</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="box-footer">

                                  @if ((bool)$selectedDominion->race->getPerkValue('cannot_invade'))
                                    <p><strong><em>Your faction is not able to invade other dominions.</em></strong></p>

                                  @elseif ($spellCalculator->isSpellActive($selectedDominion, 'rainy_season'))
                                    <p><strong><em>You cannot invade during the Rainy Season.</em></strong></p>

                                  @elseif ($spellCalculator->isSpellActive($selectedDominion, 'stasis'))
                                    <p><strong><em>You cannot invade while you are in stasis.</em></strong></p>

                                  @elseif ($protectionService->isUnderProtection($selectedDominion))
                                  <p><strong><em>You are currently under protection for <b>{{ $selectedDominion->protection_ticks }}</b> {{ str_plural('tick', $selectedDominion->protection_ticks) }} and may not invade during that time.</em></strong></p>

                                  @elseif (!$selectedDominion->round->hasStarted())
                                  <p><strong><em>You cannot invade until the round has started.</em></strong></p>

                                  @elseif ($selectedDominion->morale < 50)
                                  <p><strong><em>Your military needs at least 50% morale to invade others. Your military currently has {{ $selectedDominion->morale }}% morale.</em></strong></p>

                                  @else
                                    @if($selectedDominion->race->name == 'Dimensionalists')

                                      @if($spellCalculator->isSpellActive($selectedDominion, 'portal'))
                                      <button type="submit"
                                              class="btn btn-danger"
                                              {{ $selectedDominion->isLocked() ? 'disabled' : null }}
                                              id="invade-button">
                                          <i class="ra ra-player-teleport"></i>
                                          Teleport Units
                                      </button>
                                      @else
                                        <p><strong><em>You must open a portal before you can send your units to invade.</em></strong></p>
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

                                <p><em style="color:#888; margin-left: 10px;">Note that minimum raw DP is 10 DP per acre.</em></p>

                            </div>

                        </div>
                        <div class="col-sm-12 col-md-6">

                            <div class="box">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-home"></i> New home forces</h3>
                                </div>
                                <div class="box-body table-responsive no-padding">
                                    <table class="table">
                                        <colgroup>
                                            <col width="50%">
                                            <col width="50%">
                                        </colgroup>
                                        <tbody>
                                            <tr>
                                                <td>OP:</td>
                                                <td id="home-forces-op" data-original="{{ $militaryCalculator->getOffensivePower($selectedDominion) }}" data-amount="0">
                                                    {{ number_format($militaryCalculator->getOffensivePower($selectedDominion), 2) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Mod DP:</td>
                                                <td>
                                                    <span id="home-forces-dp" data-original="{{ $militaryCalculator->getDefensivePower($selectedDominion) }}" data-amount="0">
                                                        {{ number_format($militaryCalculator->getDefensivePower($selectedDominion), 2) }}
                                                    </span>

                                                    <small class="text-muted">
                                                        (<span id="home-forces-dp-raw" data-original="{{ $militaryCalculator->getDefensivePowerRaw($selectedDominion) }}" data-amount="0">{{ number_format($militaryCalculator->getDefensivePowerRaw($selectedDominion), 2) }}</span> raw)
                                                    </small>
                                                </td>
                                            </tr>
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
                                            <tr>
                                                <td>DPA:</td>
                                                <td id="home-forces-dpa" data-amount="0">
                                                    {{ number_format($militaryCalculator->getDefensivePower($selectedDominion) / $landCalculator->getTotalLand($selectedDominion), 3) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                </form>
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

            @include('partials.dominion.military-power-modifiers')
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

            var invasionForceCountElement = $('#invasion-total-units');

            var invadeButtonElement = $('#invade-button');
            var allUnitInputs = $('input[name^=\'unit\']');

            $('#target_dominion').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });

            @if (!$protectionService->isUnderProtection($selectedDominion))
                updateUnitStats();
            @endif

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
                    "{{ route('api.dominion.invasion') }}?" + $('#invade_form').serialize(), {},
                    function(response) {
                        if(response.result == 'success')
                        {
                            $.each(response.units, function(slot, stats)
                            {
                                // Update unit stats data attributes
                                $('#unit\\['+slot+'\\]').data('dp', stats.dp);
                                $('#unit\\['+slot+'\\]').data('op', stats.op);
                                // Update unit stats display
                                $('#unit'+slot+'_dp').text(stats.dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                                $('#unit'+slot+'_op').text(stats.op.toLocaleString(undefined, {maximumFractionDigits: 2}));
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
                            homeForcesDPElement.text(response.home_defense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesDPRawElement.text(response.home_defense_raw.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesMinDPElement.text(response.min_dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesDPAElement.text(response.home_dpa.toLocaleString(undefined, {maximumFractionDigits: 3}));

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

            const land = state.element.dataset.land;
            var range = new Number(state.element.dataset.percentage);
            const percentage = range.toPrecision(8);
            const networth = state.element.dataset.networth;
            const war = state.element.dataset.war;
            let difficultyClass;

            if (percentage >= 120) {
                difficultyClass = 'text-red';
            } else if (percentage >= 75) {
                difficultyClass = 'text-green';
            } else if (percentage >= 66) {
                difficultyClass = 'text-muted';
            } else {
                difficultyClass = 'text-gray';
            }

            warStatus = '';
            if (war == 1) {
                warStatus = '<div class="pull-left">&nbsp;<span class="text-red">WAR</span></div>';
            }

            return $(`
                <div class="pull-left">${state.text}</div>
                ${warStatus}
                <div class="pull-right">${land} acres <span class="${difficultyClass}">(${percentage}%)</span> - ${networth} networth</div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush
