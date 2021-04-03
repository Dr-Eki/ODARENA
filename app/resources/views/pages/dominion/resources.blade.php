@extends('layouts.master')

{{--
@section('page-header', 'Resources')
--}}

@section('content')
    @php($resources = $bankingCalculator->getResources($selectedDominion))

<div class="row">

    <div class="col-md-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-industry"></i> Production</h3>
            </div>
            <div class="box-body no-padding">
                <div class="row">
                    <div class="col-xs-12 col-sm-12">
                        <table class="table">
                            <colgroup>
                                <col width="150">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td>Gold:</td>
                                    <td>
                                        @if ($goldProduction = $productionCalculator->getGoldProduction($selectedDominion))
                                            <span class="text-green">{{ number_format($goldProduction) }}</span>
                                        @else
                                            0
                                        @endif

                                        <small class="text-muted">({{ number_format(($productionCalculator->getGoldProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Food:</td>
                                    <td>
                                        @if ($foodProduction = $productionCalculator->getFoodProduction($selectedDominion))
                                            <span class="text-green">{{ number_format($foodProduction) }}</span>
                                        @else
                                            0
                                        @endif

                                        <small class="text-muted">({{ number_format(($productionCalculator->getFoodProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</small>

                                        <br>
                                        <small class="text-muted">Consumed: </small>
                                        @if ($foodConsumption = $productionCalculator->getFoodConsumption($selectedDominion))
                                            <span class="text-red">-{{ number_format($foodConsumption) }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif


                                        @if($monsterContribution = $productionCalculator->getContributionRate($selectedDominion->realm))
                                            <br>
                                            <small class="text-muted">Contributed: </small>
                                            @if ($foodContribution = $productionCalculator->getContribution($selectedDominion, 'food'))
                                                <span class="text-red">-{{ number_format($foodContribution) }}</span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        @endif

                                        <br>
                                        <small class="text-muted">Net change: </small>
                                        @if (($foodNetChange = $productionCalculator->getFoodNetChange($selectedDominion)) > 0)
                                            <span class="text-green">+{{ number_format($foodNetChange) }}</span>
                                        @else
                                            <span class="text-red">{{ number_format($foodNetChange) }}</span>
                                        @endif

                                        @if($productionCalculator->isOnBrinkOfStarvation($selectedDominion))
                                            <br>
                                            <span class="label label-danger">Starvation imminent!</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Lumber:</td>
                                    <td>
                                        @if ($lumberProduction = $productionCalculator->getLumberProduction($selectedDominion))
                                            <span class="text-green">{{ number_format($lumberProduction) }}</span>
                                        @else
                                            0
                                        @endif

                                        <small class="text-muted">({{ number_format(($productionCalculator->getLumberProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Mana:</td>
                                    <td>
                                        @if ($manaProduction = $productionCalculator->getManaProduction($selectedDominion))
                                            <span class="text-green">{{ number_format($manaProduction) }}</span>
                                        @else
                                            0
                                        @endif

                                        <small class="text-muted">({{ number_format(($productionCalculator->getManaProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</small>


                                        @if($monsterContribution = $productionCalculator->getContributionRate($selectedDominion->realm))
                                            <br>
                                            <small class="text-muted">Contributed: </small>
                                            @if ($manaContribution = $productionCalculator->getContribution($selectedDominion, 'mana'))
                                                <span class="text-red">-{{ number_format($manaContribution) }}</span>
                                            @else
                                                0
                                            @endif

                                            <br>
                                            <small class="text-muted">Net change:</small>
                                            @if (($manaNetChange = $productionCalculator->getManaNetChange($selectedDominion)) > 0)
                                                <span class="text-green">+{{ number_format($manaNetChange) }}</span>
                                            @else
                                                <span class="text-red">-{{ number_format($manaNetChange) }}</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Ore:</td>
                                    <td>
                                        @if ($oreProduction = $productionCalculator->getOreProduction($selectedDominion))
                                            <span class="text-green">{{ number_format($oreProduction) }}</span>
                                        @else
                                            0
                                        @endif

                                        <small class="text-muted">({{ number_format(($productionCalculator->getOreProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Gems:</td>
                                    <td>
                                        @if ($gemProduction = $productionCalculator->getGemProduction($selectedDominion))
                                            <span class="text-green">{{ number_format($gemProduction) }}</span>
                                        @else
                                            0
                                        @endif

                                        <small class="text-muted">({{ number_format(($productionCalculator->getGemProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Experience points:</td>
                                    <td>
                                        @if ($techProduction = $productionCalculator->getTechProduction($selectedDominion))
                                            <span class="text-green">{{ number_format($techProduction) }}</span>
                                        @else
                                            0
                                        @endif

                                        <small class="text-muted">({{ number_format(($productionCalculator->getTechProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</small>
                                    </td>
                                </tr>
                                @if ($selectedDominion->race->name == 'Demon')
                                <tr>
                                    <td>Souls:</td>
                                    <td>
                                        @if ($soulProduction = $productionCalculator->getSoulProduction($selectedDominion))
                                            <span class="text-green">{{ number_format($soulProduction) }}</span>
                                        @else
                                            0
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
                <a href="{{ route('dominion.advisors.production') }}" class="pull-right"><span>Production Advisor</span></a>
            </div>
            <div class="box-body">
                  <table class="table">
                      <colgroup>
                          <col width="50%">
                          <col width="50%">
                      </colgroup>
                    <tbody>
                      </tr>
                        <td><span data-toggle="tooltip" data-placement="top" title="Total population:<br>Current / Available">Population:</span></td>
                        <td>{{ number_format($populationCalculator->getPopulation($selectedDominion)) }} / {{ number_format($populationCalculator->getMaxPopulation($selectedDominion)) }}</td>
                      </tr>
                      <tr>
                        <td><span data-toggle="tooltip" data-placement="top" title="{{ round($populationCalculator->getPopulationBirthRaw($selectedDominion) * $populationCalculator->getPopulationBirthMultiplier($selectedDominion)) }}% growth per tick">{{ str_plural($raceHelper->getPeasantsTerm($selectedDominion->race)) }}</span>:</td>
                        <td>{{ number_format($selectedDominion->peasants) }} / {{ number_format($populationCalculator->getMaxPopulation($selectedDominion) - $populationCalculator->getPopulationMilitary($selectedDominion)) }}
                            @if ($selectedDominion->peasants_last_hour < 0)
                                <span class="text-red">{{ number_format($selectedDominion->peasants_last_hour) }} last tick</span>
                            @elseif ($selectedDominion->peasants_last_hour > 0)
                                <span class="text-green">+{{ number_format($selectedDominion->peasants_last_hour) }} last tick</span>
                            @endif
                        </td>
                      </tr>
                      <tr>
                        <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by Barracks:<br>Filled / Available">Barracks housing:</span></td>
                        <td>{{ number_format($populationCalculator->getUnitsHousedInBarracks($selectedDominion)) }} / {{ number_format($populationCalculator->getAvailableHousingFromBarracks($selectedDominion)) }}</td>
                      </tr>
                      <tr>
                        <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by Forest Havens:<br>Filled / Available">Spy housing:</span></td>
                        <td>{{ number_format($populationCalculator->getUnitsHousedInForestHavens($selectedDominion)) }} / {{ number_format($populationCalculator->getAvailableHousingFromForestHavens($selectedDominion)) }}</td>
                      </tr>
                      <tr>
                        <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by Wizard Guilds:<br>Filled / Available">Wizard housing:</span></td>
                        <td>{{ number_format($populationCalculator->getUnitsHousedInWizardGuilds($selectedDominion)) }} / {{ number_format($populationCalculator->getAvailableHousingFromWizardGuilds($selectedDominion)) }}</td>
                      </tr>

                      <tr>
                        <td>Military:</td>
                        <td>{{ number_format($populationCalculator->getPopulationMilitary($selectedDominion)) }}</td>
                      </tr>

                      </tr>
                        <td><span data-toggle="tooltip" data-placement="top" title="Available jobs:<br>Peasants / Available Jobs">Jobs:</span></td></td>
                        <td>{{ number_format($populationCalculator->getPopulationEmployed($selectedDominion)) }} / {{ number_format($populationCalculator->getEmploymentJobs($selectedDominion)) }}</td>
                      </tr>
                      @php($jobsNeeded = ($selectedDominion->peasants - $populationCalculator->getEmploymentJobs($selectedDominion)))
                      @if ($jobsNeeded < 0)
                      <tr>
                        <td><span data-toggle="tooltip" data-placement="top" title="How many peasants you need in order to fill all available jobs">Jobs available:</span></td>
                        <td>{{ number_format(abs($jobsNeeded)) }}</td>
                      </tr>
                      @else
                      <tr>
                        <td><span data-toggle="tooltip" data-placement="top" title="How many new jobs need to be created to provide employment for all currently unemployed peasants<br>Peasants - Jobs = Jobs Needed">Jobs needed:</span></td>
                        <td>{{ number_format(abs($jobsNeeded)) }}</td>
                      </tr>
                      @endif
                      <tr>
                        <td>Lost income:</td>
                        <td>{{ number_format(2.7 * abs($jobsNeeded) * $productionCalculator->getGoldProductionMultiplier($selectedDominion)) }} gold</td>
                      </tr>
                      <tr>
                        <td>Per {{ $raceHelper->getPeasantsTerm($selectedDominion->race) }}:</td>
                        <td>{{ number_format((2.7 * abs($jobsNeeded) * $productionCalculator->getGoldProductionMultiplier($selectedDominion)) / max(1, abs($jobsNeeded)), 3) }} gold</td>
                      </tr>
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
              <h3 class="box-title"><i class="fas fa-exchange-alt"></i> Exchange</h3>
            </div>
            <form action="{{ route('dominion.resources') }}" method="post" {{--class="form-inline" --}}role="form">
                @csrf
                <div class="box-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="form-group col-sm-6">
                                    <label for="source">Exchange this</label>
                                    <select name="source" id="source" class="form-control" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        @foreach ($resources as $field => $resource)
                                            @if (!$resource['sell'])
                                                @continue
                                            @endif

                                            <option value="{{ $field }}" {{ $field  == $selectedDominion->most_recent_exchange_from ? 'selected' : ''}} >{{ $resource['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-sm-6">
                                    <label for="target">Into this</label>
                                    <select name="target" id="target" class="form-control" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        @foreach ($resources as $field => $resource)
                                            @if (!$resource['buy'])
                                                @continue
                                            @endif

                                            <option value="{{ $field }}" {{ $field  == $selectedDominion->most_recent_exchange_to ? 'selected' : ''}} >{{ $resource['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="form-group col-sm-3">
                                    <label for="amount" id="amountLabel">{{ reset($resources)['label'] }}</label>
                                    <input type="number"
                                           name="amount"
                                           id="amount"
                                           class="form-control text-center"
                                           value="{{ old('amount') }}"
                                           placeholder="0"
                                           min="0"
                                           max="{{ reset($resources)['max'] }}"
                                            {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                </div>
                                <div class="form-group col-sm-6">
                                    <label for="amountSlider">Amount</label>
                                    <input type="number"
                                           id="amountSlider"
                                           class="form-control slider"
                                           {{--value="0"--}}
                                           data-slider-value="0"
                                           data-slider-min="0"
                                           data-slider-max="{{ reset($resources)['max'] }}"
                                           data-slider-step="1"
                                           data-slider-tooltip="show"
                                           data-slider-handle="triangle"
                                           data-slider-id="yellow"
                                            {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                </div>
                                <div class="form-group col-sm-3">
                                    <label id="resultLabel">{{ reset($resources)['label'] }}</label>
                                    <p id="result" class="form-control-static text-center">0</p >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                        Exchange
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>You can exchange resources with the empire. Exchanging resources processes <b>instantly</b>.</p>
                <ul>
                    <li>Gold, lumber and ore trade 2 for 1.</li>
                    <li>Gems trade for 1:2 gold, lumber or ore.</li>
                    <li>Food trades for 1:4 gold, lumber or ore.</li>
                </ul>
            </div>
        </div>
    </div>

</div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/admin-lte/plugins/bootstrap-slider/slider.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/admin-lte/plugins/bootstrap-slider/bootstrap-slider.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            const resources = JSON.parse('{!! json_encode($resources) !!}');

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
