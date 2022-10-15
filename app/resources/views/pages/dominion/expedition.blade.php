@extends ('layouts.master')
@section('title', 'Expedition')

@section('content')

    <div class="row">

        <div class="col-sm-12 col-md-9">
            <form action="{{ route('dominion.expedition') }}" method="post" role="form" id="expedition_form">
                    @csrf

                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fas fa-drafting-compass"></i> Expedition</h3>
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

                                                $offenseFromBeingOutnumberedPerk = $unit->getPerkValue('offense_from_being_outnumbered');
                                                if ($offenseFromBeingOutnumberedPerk) {
                                                    $offenseFromBeingOutnumbered = explode(',', $offenseFromBeingOutnumberedPerk)[0];
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
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12 col-md-6">

                            <div class="box box-success">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fas fa-hiking"></i> Expedition Units</h3>
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
                                                <td>{{ number_format($selectedDominion->morale) }}</td>
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
                                                <td>Land discovered:</td>
                                                <td id="invasion-land-discovered-amount" data-amount="0">0</td>
                                            </tr>
                                            @if($selectedDominion->round->mode == 'artefacts')
                                            <tr>
                                                <td><i class="ra ra-alien-fire"></i> Chance to discover artefact:</td>
                                                <td id="artefact-discovery-chance" data-amount="0">0</td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                <div class="box-footer">

                                  @if ($selectedDominion->race->getPerkValue('cannot_send_expeditions'))
                                    <p><strong><em>{{ $selectedDominion->race->name }} cannot send expeditions.</em></strong></p>

                                  @elseif ($selectedDominion->getDeityPerkValue('cannot_send_expeditions'))
                                    <p><strong><em>Your deity prohibits sending expeditions.</em></strong></p>

                                  @elseif ($spellCalculator->isSpellActive($selectedDominion, 'rainy_season'))
                                    <p><strong><em>You cannot send out an expedition during the Rainy Season.</em></strong></p>

                                  @elseif ($spellCalculator->isSpellActive($selectedDominion, 'stasis'))
                                    <p><strong><em>You cannot send out an expedition while you are in stasis.</em></strong></p>
    
                                    @elseif ($spellCalculator->isSpellActive($selectedDominion, 'flood_the_caverns'))
                                        <p><strong><em>You cannot send expeditions while the caverns are flooded.</em></strong></p>

                                  @elseif ($selectedDominion->getSpellPerkValue('cannot_send_expeditions'))
                                    <p><strong><em>A spell is preventing you from sending expeditions.</em></strong></p>

                                    @elseif ($selectedDominion->getDecreePerkValue('cannot_send_expeditions'))
                                      <p><strong><em>You have issued a decree which forbids expeditions.</em></strong></p>

                                  @elseif ($protectionService->isUnderProtection($selectedDominion))
                                  <p><strong><em>You are currently under protection for <b>{{ $selectedDominion->protection_ticks }}</b> {{ str_plural('tick', $selectedDominion->protection_ticks) }} and may not send out an expedition during that time.</em></strong></p>

                                  @elseif (!$selectedDominion->round->hasStarted())
                                  <p><strong><em>You cannot send out an expedition until the round has started.</em></strong></p>

                                  @elseif ($selectedDominion->morale < 50)
                                  <p><strong><em>Your military needs at least 50 morale to send out an expedition others. You currently have {{ $selectedDominion->morale }} morale.</em></strong></p>

                                  @else
                                      <button type="submit"
                                              class="btn btn-danger"
                                              {{ $selectedDominion->isLocked() ? 'disabled' : null }}
                                              id="invade-button">
                                          <i class="fas fa-compass"></i>
                                          Send Expedition
                                      </button>
                                  @endif
                                </div>
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
                    <p>Here you can send out military units on an expedition to discover and claim new land and gain small amounts of XP and prestige.</p>
                    <p>Ammunition and immobile units cannot be sent on expeditions.</p>
                    {{-- 
                    <p>Currently, each acre of land discovered requires <b>{{ number_format(ceil($expeditionCalculator->getOpPerLand($selectedDominion))) }} OP</b>.</p>
                    --}}
                    
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
            var invasionLandDiscoveredElement = $('#invasion-land-discovered-amount');
            var artefactDiscoveryChanceElement = $('#artefact-discovery-chance');

            var invasionForceCountElement = $('#invasion-total-units');

            var invadeButtonElement = $('#invade-button');
            var allUnitInputs = $('input[name^=\'unit\']');

            @if (!$protectionService->isUnderProtection($selectedDominion))
                updateUnitStats();
            @endif

            $('input[name^=\'calc\']').change(function (e) {
                updateUnitStats();
            });

            $('input[name^=\'unit\']').change(function (e) {
                updateUnitStats();
            });

            function updateUnitStats() {
                // Update unit stats
                $.get(
                    "{{ route('api.dominion.expedition') }}?" + $('#expedition_form').serialize(), {},
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
                            invasionLandDiscoveredElement.data('amount', response.land_discovered_amount);
                            homeForcesOPElement.data('amount', response.home_offense);
                            homeForcesDPElement.data('amount', response.home_defense);
                            homeForcesDPRawElement.data('amount', response.home_defense_raw);
                            homeForcesMinDPElement.data('amount', response.min_dp);
                            homeForcesDPAElement.data('amount', response.home_dpa);
                            artefactDiscoveryChanceElement.data('amount', response.artefact_discovery_chance);

                            // Update OP / DP display
                            invasionForceOPElement.text(response.away_offense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            invasionForceDPElement.text(response.away_defense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            invasionForceMaxOPElement.text(response.max_op.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            invasionLandDiscoveredElement.text(response.land_discovered_amount.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesOPElement.text(response.home_offense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesDPElement.text(response.home_defense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesDPRawElement.text(response.home_defense_raw.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesMinDPElement.text(response.min_dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesDPAElement.text(response.home_dpa.toLocaleString(undefined, {maximumFractionDigits: 3}));
                            artefactDiscoveryChanceElement.text(response.artefact_discovery_chance.toLocaleString(undefined, {maximumFractionDigits: 2}) + '%');

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
            const abandoned = state.element.dataset.abandoned;
            let difficultyClass;

            if (percentage >= 120) {
                difficultyClass = 'text-red';
            } else if (percentage >= 75) {
                difficultyClass = 'text-green';
            } else if (percentage >= 60) {
                difficultyClass = 'text-muted';
            } else {
                difficultyClass = 'text-gray';
            }

            warStatus = '';
            if (war == 1) {
                warStatus = '<div class="pull-left">&nbsp;<span class="text-red">WAR</span></div>';
            }

            abandonedStatus = '';
            if (abandoned == 1) {
                abandonedStatus = '&nbsp;<div class="pull-left">&nbsp;<span class="label label-warning">Abandoned</span></div>';
            }

            return $(`
                <div class="pull-left">${state.text}</div>
                ${abandonedStatus}
                <div class="pull-right">${land} acres <span class="${difficultyClass}">(${percentage}%)</span> - ${networth} networth</div>
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
