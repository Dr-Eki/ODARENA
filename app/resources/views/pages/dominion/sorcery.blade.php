@extends ('layouts.master')
@section('title', 'Sorcery')

@section('content')
<div class="row">
    <div class="col-sm-12 col-md-9">
        <form action="{{ route('dominion.sorcery')}}" method="post" role="form" id="sorcery_form">
        @csrf

        <!-- TARGET -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-on-target"></i> Target</h3>
                        <small class="pull-right text-muted">
                            <span data-toggle="tooltip" data-placement="top" title="Wizards Per Acre (Wizard Ratio) on offense">WPA</span>: {{ number_format($magicCalculator->getWizardRatio($selectedDominion, 'offense'),3) }},
                            <span data-toggle="tooltip" data-placement="top" title="Wizard Strength">WS</span>: {{ $selectedDominion->wizard_strength }}%
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
                                                    data-land="{{ number_format($dominion->land) }}"
                                                    data-networth="{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}"
                                                    data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}"
                                                    data-wpa="{{ $dominion->getSpellPerkValue('fog_of_war') ? 'Unknown' : number_format($magicCalculator->getWizardRatio($dominion, 'defense'), 3) }}"
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

        <!-- ENHANCEMENT -->
        {{--
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-mining-diamonds"></i> Enhancement</h3>
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

                                            @if(!$selectedDominion->race->getPerkValue('no_' . $resource->key . '_theft'))
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
        --}}

        <!-- SPELL -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-scroll"></i> Spell</h3>
                    </div>
                    <div class="box-body">
                    {{-- //Columns must be a factor of 12 (1,2,3,4,6,12) --}}
                    @php
                        $numOfCols = 3;
                        $rowCount = 0;
                        $bootstrapColWidth = 12 / $numOfCols;
                    @endphp

                    <div class="row">

                        @foreach($spells as $spell)
                            @php
                                $canCast = $spellCalculator->canCastSpell($selectedDominion, $spell, $resourceCalculator->getAmount($selectedDominion, 'mana'));
                            @endphp
                            <div class="col-md-{{ $bootstrapColWidth }}">
                                <label class="btn btn-block">
                                    <div class="box {!! $sorceryHelper->getSpellClassBoxClass($spell) !!}">
                                        <div class="box-header with-border">
                                            <input type="radio" id="spell" name="spell" value="{{ $spell->id }}" required>&nbsp;<h4 class="box-title">{{ $spell->name }}</h4>
                                            <span class="pull-right" data-toggle="tooltip" data-placement="top" title="{!! $sorceryHelper->getSpellClassDescription($spell) !!}"><i class="{!! $sorceryHelper->getSpellClassIcon($spell) !!}"></i></span>
                                        </div>

                                        <div class="box-body">
                                            <ul>
                                                @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                                    <li>{{ $effect }}</li>
                                                @endforeach
                                            </ul>

                                            <div class="box-footer">
                                                @include('partials.dominion.sorcery-spell-basics')
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

        <!-- WIZARD STRENGTH AND CAST BUTTON-->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-hat-wizard"></i> Wizard Strength</h3>
                    </div>
                    <div class="box-body">
                        <input type="hidden" id="mana-available" data-amount="{{ $resourceCalculator->getAmount($selectedDominion, 'mana') }}">

                        <input type="number"
                               id="amountSlider"
                               class="form-control slider"
                               name="wizard_strength"
                               value="0"
                               data-slider-value="{{ min($selectedDominion->wizard_strength, 4) }}"
                               data-slider-min="{{ min($selectedDominion->wizard_strength, 4) }}"
                               data-slider-max="{{ $selectedDominion->wizard_strength }}"
                               data-slider-step="1"
                               data-slider-tooltip="show"
                               data-slider-handle="round"
                               data-slider-id="blue"
                                {{ $selectedDominion->isLocked() ? 'disabled' : null }}>


                        <div class="box-body table-responsive no-padding">
                            <table class="table">
                                <cols>
                                    <col width="20%">
                                    <col>
                                </cols>
                                <tbody>
                                    <tr>
                                        <td>Mana:</td>
                                        <td><span id="sorcery-mana-cost" data-amount="0">0</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="box-footer">
                            <button type="submit"
                                    class="btn btn-danger"
                                    {{ ($selectedDominion->isLocked() or $selectedDominion->wizard_strength < 4) ? 'disabled' : null }}
                                    id="cast-button">
                                <i class="fas fa-hand-sparkles"></i>
                                Cast Spell
                            </button>
                        </div>
                    </div>
                </form>
                </div>
            </div>
        </div>

        </form>
    </div>
    <!-- INFORMATION -->
    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>Select target, spell, and how much of your wizard strength you wish to use.</p>
                <p>The amount of wizard strength you use determines how much mana you need per 1% of Wizard Strength. You must use at least 4% Wizard Strength to perform sorcery.</p>
                <p>Performing sorcery requires that you have a wizard ratio of at least 0.10 and 4% available Wizard Strength.</p>
                @if(($sorceryDamageDealtMultiplier = $sorceryCalculator->getDamageDealtMultiplier($selectedDominion)) > 1)
                    <p>Sorcery damage dealt is increased by <strong>{{ number_format(($sorceryDamageDealtMultiplier-1) * 100, 2) }}%</strong>.</p>
                @endif
                <table class="table">
                    <colgroup>
                        <col>
                        <col width="80">
                        <col width="80">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Total</th>
                            <th>Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($selectedDominion->race->units as $unit)
                            @if($unitHelper->isUnitOffensiveWizard($unit))
                                @php
                                    $unitSlot = $unit->slot;
                                @endphp
                                <tr>
                                    <td>{{ $unit->name }}</td>
                                    <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, $unitSlot)) }}</td>
                                    <td>{{ number_format($selectedDominion->{"military_unit{$unitSlot}"}) }}</td>
                                </tr>
                            @endif
                        @endforeach
                        @if(!$selectedDominion->race->getPerkValue('cannot_train_wizards'))
                            <tr>
                                <td>Wizards</td>
                                <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 'wizards')) }}</td>
                                <td>{{ number_format($selectedDominion->military_wizards) }}</td>
                            </tr>
                        @endif
                        @if(!$selectedDominion->race->getPerkValue('cannot_train_archmages'))
                            <tr>
                                <td>Archmages</td>
                                <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 'archmages')) }}</td>
                                <td>{{ number_format($selectedDominion->military_archmages) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="ra ra-fairy-wand"></i> Active Spells</h3>
            </div>
            <div class="box-body">
                <h4>Hostile Spells You Have Cast</h4>
                <div class="box box-primary table-responsive" id="spells-cast">
                    <table class="table table-striped table-hover" id="spells-cast-table">
                        <colgroup>
                            <col>
                            <col width="200">
                            <col width="200">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Cast On</th>
                                <th>Spell</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($spellCalculator->getPassiveSpellsCastByDominion($selectedDominion, 'hostile') as $activePassiveSpellCast)
                                <tr>
                                    <td><a href="{{ route('dominion.insight.show', [$activePassiveSpellCast->dominion->id]) }}">{{ $activePassiveSpellCast->dominion->name }}&nbsp;(#&nbsp;{{ $activePassiveSpellCast->dominion->realm->number }})</a></td>
                                    <td>{{ $activePassiveSpellCast->spell->name }}</td>
                                    <td>{{ $activePassiveSpellCast->duration }} / {{ $activePassiveSpellCast->spell->duration }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <h4>Hostile Spells Cast On You</h4>
                <table class="table table-condensed">
                    <colgroup>
                        <col>
                        <col width="200">
                        <col width="200">
                    </colgroup>
                    <tr>
                        <th>Spell</th>
                        <th>Duration</th>
                        <th>Break</th>
                    </tr>
                    @foreach($spellCalculator->getPassiveSpellsCastOnDominion($selectedDominion, 'hostile') as $activePassiveSpellCast)
                        <tr>
                            <td>{{ $activePassiveSpellCast->spell->name }}</td>
                            <td>{{ $activePassiveSpellCast->duration . ' ' . str_plural('tick', $activePassiveSpellCast->duration)}}</td>
                            <td>
                                @if($activePassiveSpellCast->spell->class !== 'invasion')
                                    <form action="{{ route('dominion.sorcery') }}" method="post" role="form" id="break_spell" name="break_spell">
                                        @csrf
                                        <input type="hidden" name="action" value="break_spell">
                                        <input type="hidden" name="spell" value="{{ $activePassiveSpellCast->spell->id }}">
                                        <span data-toggle="tooltip" data-placement="top" title="Try to break this spell<br><ul><li>Mana: {{ number_format($spellCalculator->getManaCost($selectedDominion, $activePassiveSpellCast->spell->key)) }}</li><li>Wizard Strength: 5%</li></ul>">
                                            <button type="submit" class="btn btn-warning btn-block">
                                                <i class="ra ra-explosive-materials"></i>
                                            </button>
                                        </span>
                                    </form>
                                @else
                                    &mdash;
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>


    <!-- INFORMATION -->
    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>These are the currently active sorcery spells you have cast on others or have been cast on you.</p>
                <p>You can try to break spells cast on you.</p>
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
                    "{{ route('api.dominion.sorcery') }}?" + $('#sorcery_form').serialize(), {},
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
            const wpa = state.element.dataset.wpa;
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
                <div class="pull-right">${land} acres <span class="${difficultyClass}">(${percentage}%)</span> - ${wpa} WPA - ${networth} networth</div>
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

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
    <style>
        #spells-cast #spells-cast-table_filter { display: none !important; }
    </style>
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var table = $('#spells-cast-table').DataTable({
                order: [2, 'desc'],
                paging: false,
            });
        })(jQuery);
    </script>
@endpush
