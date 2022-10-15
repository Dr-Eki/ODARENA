@extends ('layouts.master')
@section('title', 'Theft')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <form action="{{ route('dominion.theft')}}" method="post" role="form">
            @csrf

            <!-- TARGET -->
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-hand-lizard"></i> Target</h3>
                            <small class="pull-right text-muted">
                                <span data-toggle="tooltip" data-placement="top" title="Spies Per Acre (Spy Ratio) on offense">SPA</span>: {{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'offense'),3) }},
                                <span data-toggle="tooltip" data-placement="top" title="Spy Strength">SS</span>: {{ $selectedDominion->spy_strength }}%
                            </small>
                        </div>

                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <select name="target_dominion" id="target_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            <option></option>
                                            @foreach ($rangeCalculator->getDominionsInRange($selectedDominion) as $dominion)
                                                <option value="{{ $dominion->id }}"
                                                        data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}"
                                                        data-networth="{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}"
                                                        data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}"
                                                        data-abandoned="{{ $dominion->isAbandoned() ? 1 : 0 }}">
                                                    {{ $dominion->name }} (#{{ $dominion->realm->number }}) - {{ $dominion->race->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RESOURCE -->
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-mining-diamonds"></i> Resource</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <select name="resource" id="resource" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            @foreach ($selectedDominion->race->resources as $resourceKey)
                                                @php
                                                    $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                                @endphp

                                                @if(!$selectedDominion->race->getPerkValue('no_' . $resource->key . '_theft') and $theftHelper->canDominionStealResource($selectedDominion, $resource))
                                                    <option value="{{ $resource->id }}" {{ $selectedDominion->most_recent_theft_resource  == $resource->key ? 'selected' : '' }}>
                                                        {{ $resource->name }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SPY UNITS -->
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-user-secret"></i> Spies</h3>
                        </div>
                        <div class="box-body table-responsive no-padding">
                            <table class="table">
                                <colgroup>
                                    <col>
                                    <col width="200">
                                    <col width="200">
                                    <col width="200">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th>Total</th>
                                        <th>Available</th>
                                        <th class="text-center">Send</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!$selectedDominion->race->getPerkValue('cannot_train_spies'))
                                        <tr>
                                            <td>Spies</td>
                                            <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 'spies')) }}</td>
                                            <td>{{ number_format($selectedDominion->military_spies) }}</td>
                                            <td class="text-center">
                                                <input type="number"
                                                       name="unit[spies]"
                                                       id="unit[spies]"
                                                       class="form-control text-center"
                                                       placeholder="0"
                                                       min="0"
                                                       max="{{ $selectedDominion->military_spies }}"
                                                       style="min-width:5em;"
                                                       data-slot="spies"
                                                       data-amount="{{ $selectedDominion->military_spies }}"
                                                       {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            </td>
                                        </tr>
                                    @endif
                                    @foreach ($selectedDominion->race->units as $unit)
                                        @if($unitHelper->isUnitOffensiveSpy($unit))
                                            @php
                                                $unitSlot = $unit->slot;
                                            @endphp
                                            <tr>
                                                <td>{{ $unit->name }}</td>
                                                <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, $unitSlot)) }}</td>
                                                <td>{{ number_format($selectedDominion->{"military_unit{$unitSlot}"}) }}</td>
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
                                                           {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>

                            @if ($selectedDominion->getSpellPerkValue('cannot_steal'))
                                <p><strong><em>A magical state is preventing you from stealing.</em></strong></p>
                            @else
                                <div class="box-footer">
                                    <button type="submit"
                                            class="btn btn-danger"
                                            {{ $selectedDominion->isLocked() ? 'disabled' : null }}
                                            id="invade-button">
                                        <i class="fa fa-hand-lizard"></i>
                                        Send Spies
                                    </button>
                                </div>
                            @endif
                        </div>
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
                    <p>Select target, resource, and number of spies you wish to send. Below is a table showing how much of each resource each spy unit can carry. It takes six ticks for the units and stolen resources to return.</p>
                    <p>Theft costs spy strength. The cost is calculated as number of spy units you send relative to the total number of spy units that you have.</p>
                    <p>You currently have
                          <strong>{{ number_format($selectedDominion->spy_strength) }}%</strong> spy strength,
                          <strong>{{ number_format($militaryCalculator->getTotalSpyUnits($selectedDominion)) }}</strong> total spy units, and
                          <strong>{{ number_format($militaryCalculator->getTotalSpyUnitsAtHome($selectedDominion)) }}</strong> spy units at home.
                          You can at most send <strong>{{ number_format($militaryCalculator->getMaxSpyUnitsSendable($selectedDominion)) }}</strong> spy units.</p>
                    <table class="table">
                        <colgroup>
                            <col width="40%">
                            <col width="30%">
                            <col width="30%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Resource</th>
                                <th>Raw Per Unit</th>
                                <th>Mod Per Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($selectedDominion->race->resources as $resourceKey)
                            @php
                                $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                            @endphp
                            @if($theftHelper->canDominionStealResource($selectedDominion, $resource))
                                <tr>
                                    <td>{{ $resource->name }}</td>
                                    <td>{{ number_format($theftHelper->getMaxCarryPerSpyForResource($resource),2) }}</td>
                                    <td>{{ number_format($theftCalculator->getMaxCarryPerSpyForResource($selectedDominion, $resource),2) }}</td>
                                </tr>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                    <p class="text-muted"><small><em>The perks above are the basic, static values and do not take into account circumstantial perks such as perks vs. specific types of targets or perks based on specific unit compositions.</em></small></p>
                </div>
            </div>
        </div>

    </div>

    @push('page-styles')
        <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
    @endpush

    @push('page-scripts')
        <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
        <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
    @endpush

    @push('inline-scripts')
        <script type="text/javascript">
            (function ($) {
                $('#dominions-table').DataTable({
                    order: [[4, 'desc']],
                });
                //$('#clairvoyance-table').DataTable({
                //    order: [[2, 'desc']],
                //});
            })(jQuery);
        </script>
    @endpush

@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#target_dominion').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
            $('#target_dominion').change(function(e) {
                var warStatus = $(this).find(":selected").data('war');
                if (warStatus == 1) {
                    $('.war-spell').removeClass('disabled');
                } else {
                    $('.war-spell').addClass('disabled');
                }
            });
            @if (session('target_dominion'))
                $('#spell_dominion').val('{{ session('spell_dominion') }}').trigger('change.select2').trigger('change');
            @endif
        })(jQuery);

        function select2Template(state) {
            if (!state.id) {
                return state.text;
            }

            const land = state.element.dataset.land;
            const percentage = state.element.dataset.percentage;
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
                abandonedStatus = '<div class="pull-left">&nbsp;<span class="label label-warning">Abandoned</span></div>';
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
