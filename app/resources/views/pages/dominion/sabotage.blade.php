@extends ('layouts.master')
@section('title', 'Sabotage')

@section('content')
<div class="row">
    <div class="col-sm-12 col-md-9">
        <form action="{{ route('dominion.sabotage')}}" method="post" role="form" id="sabotage_form">
        @csrf

        <!-- TARGET -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-on-target"></i> Target</h3>
                        <small class="pull-right text-muted">
                            <span data-toggle="tooltip" data-placement="top" title="Spy Per Acre (Spy Ratio) on offense">SPA</span>: {{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'offense'),3) }},
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
                        <h3 class="box-title"><i class="ra ra-arson"></i> Operation</h3>
                    </div>
                    <div class="box-body">
                    {{-- //Columns must be a factor of 12 (1,2,3,4,6,12) --}}
                    @php
                        $numOfCols = 3;
                        $rowCount = 0;
                        $bootstrapColWidth = 12 / $numOfCols;
                    @endphp

                    <div class="row">

                    @foreach($spyops as $spyop)
                        @php
                            $canPerform = $sabotageCalculator->canPerformSpyop($selectedDominion, $spyop);
                        @endphp
                        <div class="col-md-{{ $bootstrapColWidth }}">
                            <label class="btn btn-block">
                                <div class="box box-danger">
                                    <div class="box-header with-border">
                                        <input type="radio" id="spyop" name="spyop" value="{{ $spyop->id }}" required>&nbsp;<h4 class="box-title">{{ $spyop->name }}</h4>
                                    </div>

                                    <div class="box-body">
                                        <ul>
                                            @foreach($espionageHelper->getSpyopEffectsString($spyop) as $effect)
                                                <li>{{ $effect }}</li>
                                            @endforeach
                                        </ul>

                                        <div class="box-footer">
                                            @include('partials.dominion.sabotage-operation-basics')
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>

                        @php
                            $rowCount++;
                        @endphp

                        @if($rowCount % $numOfCols == 0)
                            </div><div class="row">
                        @endif

                    @endforeach
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

                        @if ($selectedDominion->getSpellPerkValue('cannot_sabotage'))
                            <p><strong><em>A magical state is preventing you from performing sabotage.</em></strong></p>
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
                <p>You can at most send <strong>{{ number_format($militaryCalculator->getMaxSpyUnitsSendable($selectedDominion)) }}</strong> spy units.</p>
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
            var sorceryManaCostElement = $('#sorcery-mana-cost');
            var manaAvailableElement = $('#mana-available');
            var castButtonElement = $('#cast-button');

            $('#target_dominion').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });

            $('#target_dominion').change(function (e) {
                updateManaCost();
            });

            $('#amountSlider').change(function (e) {
                updateManaCost();
            });

            $('#spell').change(function (e) {
                updateManaCost();
            });

            function updateManaCost() {
                // Update unit stats
                $.get(
                    "{{ route('api.dominion.sorcery') }}?" + $('#sabotage_form').serialize(), {},
                    function(response) {
                        if(response.result == 'success')
                        {
                            // Update OP / DP data attributes
                            sorceryManaCostElement.data('amount', response.mana_cost);

                            // Update OP / DP display
                            sorceryManaCostElement.text(response.mana_cost.toLocaleString(undefined, {maximumFractionDigits: 2}));

                            calculate();
                        }
                    }
                );
            }

            function calculate() {
                // Check mana afford
                var manaAffordRule = parseFloat(sorceryManaCostElement.data('amount')) > parseFloat(manaAvailableElement.data('amount'));
                if (manaAffordRule) {
                    sorceryManaCostElement.addClass('text-danger');
                } else {
                    sorceryManaCostElement.removeClass('text-danger');
                }

                // Check 4:3 rule
                var maxOffenseRule = parseFloat(invasionForceOPElement.data('amount')) > parseFloat(invasionForceMaxOPElement.data('amount'));
                if (maxOffenseRule) {
                    invasionForceOPElement.addClass('text-danger');
                } else {
                    invasionForceOPElement.removeClass('text-danger');
                }

                // Check if invade button should be disabled
                if (manaAffordRule || maxOffenseRule) {
                    castButtonElement.attr('disabled', 'disabled');
                } else {
                    castButtonElement.removeAttr('disabled');
                }

            }
        })(jQuery);


        function select2Template(state) {
            if (!state.id) {
                return state.text;
            }

            const land = state.element.dataset.land;
            const percentage = state.element.dataset.percentage;
            const networth = state.element.dataset.networth;
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

@push('page-scripts')
    <script type="text/javascript">
    $("form").submit(function () {
        // prevent duplicate form submissions
        $(this).find(":submit").attr('disabled', 'disabled');
    });
    </script>
@endpush
