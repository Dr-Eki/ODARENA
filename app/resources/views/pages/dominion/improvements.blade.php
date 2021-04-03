@extends('layouts.master')

{{--
@section('page-header', 'Improvements')
--}}

@section('content')

@php

$totalImprovementPoints = 0;
foreach($improvementHelper->getImprovementTypes($selectedDominion) as $improvementType)
{
    $totalImprovementPoints += $selectedDominion->{'improvement_'.$improvementType};
}

@endphp

@if ((bool)$selectedDominion->race->getPerkValue('cannot_improve_castle'))
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <p>{{ $selectedDominion->race->name }} cannot use improvements.</p>
            </div>
        </div>
    </div>
@else
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-arrow-up fa-fw"></i> Improvements</h3>
                </div>

                <form action="{{ route('dominion.improvements') }}" method="post" role="form">
                    @csrf
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col width="150">
                                <col width="150">
                                <col width="50">
                                <col>
                                <col width="100">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Part</th>
                                    <th colspan="2">Invest</th>
                                    <th>Rating</th>
                                    <th class="text-center">Invested</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($improvementHelper->getImprovementTypes($selectedDominion) as $improvementType)
                                    <tr>
                                        <td>
                                            <i class="ra ra-{{ $improvementHelper->getImprovementIcon($improvementType) }} ra-fw" data-toggle="tooltip" data-placement="top" title="{{ $improvementHelper->getImprovementHelpString($improvementType, $selectedDominion) }}"></i>
                                            {{ ucfirst($improvementType) }}
                                        </td>
                                        <td class="text-center">
                                            <input type="number" name="improve[{{ $improvementType }}]" class="form-control text-center" placeholder="0" min="0" size="8" style="min-width:8em; width:100%;" value="{{ old('improve.' . $improvementType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        </td>
                                        <td>
                                            <button class="btn btn-default improve-max" data-type="{{ $improvementType }}" type="button" style="width:4em;">Max</button>
                                        </td>
                                        <td>
                                            {{ sprintf(
                                                $improvementHelper->getImprovementRatingString($improvementType),
                                                number_format($improvementCalculator->getImprovementMultiplierBonus($selectedDominion, $improvementType) * 100, 2)
                                            ) }}
                                        </td>
                                        <td class="text-center">{{ number_format($selectedDominion->{'improvement_' . $improvementType}) }}</td>
                                    </tr>
                                @endforeach
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>Total</strong></td>
                                        <td class="text-center">{{ number_format($totalImprovementPoints) }}</td>
                                    </tr>

                                @php
                                    $totalSabotaged = 0;
                                @endphp
                                @foreach($queueService->getSabotageQueue($selectedDominion) as $sabotage)
                                  @php
                                  $totalSabotaged += $sabotage->amount;
                                  @endphp
                                @endforeach
                                @if($totalSabotaged > 0)
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>Sabotaged</strong><br><small class="text-muted">Will be restored automatically</small></td>
                                        <td class="text-center">{{ number_format($totalSabotaged) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <div class="pull-right">
                            <select name="resource" class="form-control">
                                @if ((bool)$selectedDominion->race->getPerkValue('tissue_improvement'))
                                    <option value="food" data-amount="{{ $selectedDominion->resource_food }}" {{ $selectedResource  === 'food' ? 'selected' : ''}}>Food</option>
                                @else
                                    @if ((bool)$selectedDominion->race->getPerkValue('can_invest_mana'))
                                        <option value="mana" data-amount="{{ $selectedDominion->resource_mana }}" {{ $selectedResource  === 'mana' ? 'selected' : ''}}>Mana</option>
                                    @endif
                                    @if ((bool)$selectedDominion->race->getPerkValue('can_invest_soul'))
                                        <option value="soul" data-amount="{{ $selectedDominion->resource_soul }}" {{ $selectedResource  === 'soul' ? 'selected' : ''}}>Soul</option>
                                    @endif
                                    @if ((bool)$selectedDominion->race->getPerkValue('can_invest_food'))
                                        <option value="food" data-amount="{{ $selectedDominion->resource_food }}" {{ $selectedResource  === 'food' ? 'selected' : ''}}>Food</option>
                                    @endif
                                    <option value="gems" data-amount="{{ $selectedDominion->resource_gems }}" {{ $selectedDominion->most_recent_improvement_resource  === 'gems' ? 'selected' : ''}}>Gems</option>
                                    <option value="lumber" data-amount="{{ $selectedDominion->resource_lumber }}" {{ $selectedDominion->most_recent_improvement_resource  === 'lumber' ? 'selected' : ''}}>Lumber</option>
                                    <option value="ore" data-amount="{{ $selectedDominion->resource_ore }}" {{ $selectedDominion->most_recent_improvement_resource  === 'ore' ? 'selected' : ''}}>Ore</option>
                                    <option value="gold" data-amount="{{ $selectedDominion->resource_gold }}" {{ $selectedDominion->most_recent_improvement_resource === 'gold' ? 'selected' : ''}}>Gold</option>
                                @endif
                            </select>
                        </div>

                        <div class="pull-right" style="padding: 7px 8px 0 0">
                            Resource to invest:
                        </div>

                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Invest</button>
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
                    <p>Invest resources in your castle to improve certain parts of your dominion. Improvements take effect immediately.</p>

                    @if($improvementCalculator->getMasonriesBonus($selectedDominion) > 0 or $improvementCalculator->getTechBonus($selectedDominion) > 0)
                    <p>
                      @if($improvementCalculator->getMasonriesBonus($selectedDominion) > 0 and $improvementCalculator->getTechBonus($selectedDominion) == 0)
                        Masonries
                      @elseif($improvementCalculator->getTechBonus($selectedDominion) > 0 and $improvementCalculator->getMasonriesBonus($selectedDominion) == 0)
                        Advancements
                      @elseif($improvementCalculator->getTechBonus($selectedDominion) > 0 and $improvementCalculator->getMasonriesBonus($selectedDominion) > 0)
                        Masonries and Advancements
                      @endif

                      are increasing your castle improvements by <strong>{{ number_format(($improvementCalculator->getTechBonus($selectedDominion) + $improvementCalculator->getMasonriesBonus($selectedDominion))*100,2) }}%</strong>.
                    </p>
                    @endif

                    <p>Resources invested are converted to points.</p>
                    <table class="table">
                        <colgroup>
                            <col width="25%">
                            <col width="25%">
                            <col width="25%">
                            <col width="25%">
                        </colgroup>
                        <thead>
                          <tr>
                            <th>Resource</th>
                            <th>Points each</th>
                            <th>Points each (raw)</th>
                            <th>Modifier</th>
                          </tr>
                        </thead>
                      <tbody>
                      @if ((bool)$selectedDominion->race->getPerkValue('tissue_improvement'))
                        <tr>
                          <td>Food</td>
                          <td>{{ number_format($improvementCalculator->getResourceWorth('food', $selectedDominion),2) }}</td>
                          <td>{{ $improvementCalculator->getResourceWorthRaw('food', $selectedDominion) }}</td>
                          <td>{{ $improvementCalculator->getResourceWorthMultipler('food', $selectedDominion)*100 }}%</td>
                        </tr>
                      @else
                          @if ((bool)$selectedDominion->race->getPerkValue('can_invest_mana'))
                            <tr>
                              <td>Mana</td>
                              <td>{{ number_format($improvementCalculator->getResourceWorth('mana', $selectedDominion),2) }}</td>
                              <td>{{ $improvementCalculator->getResourceWorthRaw('mana', $selectedDominion) }}</td>
                              <td>{{ $improvementCalculator->getResourceWorthMultipler('mana', $selectedDominion)*100 }}%</td>
                            </tr>
                          @endif
                          @if ((bool)$selectedDominion->race->getPerkValue('can_invest_soul'))
                            <tr>
                              <td>Souls</td>
                              <td>{{ number_format($improvementCalculator->getResourceWorth('soul', $selectedDominion),2) }}</td>
                              <td>{{ $improvementCalculator->getResourceWorthRaw('soul', $selectedDominion) }}</td>
                              <td>{{ $improvementCalculator->getResourceWorthMultipler('soul', $selectedDominion)*100 }}%</td>
                            </tr>
                          @endif
                            <tr>
                              <td>Gems</td>
                              <td>{{ number_format($improvementCalculator->getResourceWorth('gems', $selectedDominion),2) }}</td>
                              <td>{{ $improvementCalculator->getResourceWorthRaw('gems', $selectedDominion) }}</td>
                              <td>{{ $improvementCalculator->getResourceWorthMultipler('gems', $selectedDominion)*100 }}%</td>
                            </tr>
                            <tr>
                              <td>Lumber</td>
                              <td>{{ number_format($improvementCalculator->getResourceWorth('lumber', $selectedDominion),2) }}</td>
                              <td>{{ $improvementCalculator->getResourceWorthRaw('lumber', $selectedDominion) }}</td>
                              <td>{{ $improvementCalculator->getResourceWorthMultipler('lumber', $selectedDominion)*100 }}%</td>
                            </tr>
                            <tr>
                              <td>Ore</td>
                              <td>{{ number_format($improvementCalculator->getResourceWorth('ore', $selectedDominion),2)}}</td>
                              <td>{{ $improvementCalculator->getResourceWorthRaw('ore', $selectedDominion) }}</td>
                              <td>{{ $improvementCalculator->getResourceWorthMultipler('ore', $selectedDominion)*100 }}%</td>
                            </tr>
                            <tr>
                              <td>Gold</td>
                              <td>{{ number_format($improvementCalculator->getResourceWorth('gold', $selectedDominion),2) }}</td>
                              <td>{{ $improvementCalculator->getResourceWorthRaw('gold', $selectedDominion) }}</td>
                              <td>{{ $improvementCalculator->getResourceWorthMultipler('gold', $selectedDominion)*100 }}%</td>
                            </tr>
                        @endif
                      </tbody>
                    </table>

                    @if ((bool)$selectedDominion->race->getPerkValue('can_invest_soul'))
                    <p>You currently have <strong>{{ number_format($selectedDominion->resource_soul) }}</strong> souls.</p>
                    @endif

                </div>
            </div>
        </div>

    </div>
@endif
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('.improve-max').click(function(e) {
                var selectedOption = $('select[name=resource] option:selected'),
                    selectedResource = selectedOption.val(),
                    maxAmount = selectedOption.data('amount'),
                    improvementType = $(this).data('type');
                $('input[name^=improve]').val('');
                $('input[name=improve\\['+improvementType+'\\]]').val(maxAmount);
            });
        })(jQuery);
    </script>
@endpush
