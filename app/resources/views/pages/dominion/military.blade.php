@extends('layouts.master')
@section('title', 'Military')

@section('content')
<div class="row">

    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="ra ra-sword"></i> Military</h3>
            </div>
            <form action="{{ route('dominion.military.train') }}" method="post" role="form">
                @csrf
                <div class="box-body table-responsive no-padding" id="units_overview_and_training">
                    <table class="table">
                        <colgroup>
                            <col>
                            <col width="100">
                            <col width="100">
                            <col width="150">
                            <col width="150">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th class="text-center">OP / DP</th>
                                <th class="text-center">Trained<br>(Incoming)</th>
                                <th class="text-center">Train</th>
                                <th class="text-center">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($unitHelper->getUnitTypes($selectedDominion->race) as $unitType)
                                @if($selectedDominion->race->getPerkValue('cannot_train_' . $unitType))
                                    @continue
                                @else
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'offense'), $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'defense'), ]) }}">
                                                {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                                            </span>
                                        </td>
                                          @if (in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4', 'unit5', 'unit6', 'unit7', 'unit8', 'unit9', 'unit10']))
                                              @php
                                                  $unit = $selectedDominion->race->units->filter(function ($unit) use ($unitType) {
                                                      return ($unit->slot == (int)str_replace('unit', '', $unitType));
                                                  })->first();

                                                  $offensivePower = $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unit, 'offense');
                                                  $defensivePower = $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unit, 'defense');

                                                  $hasDynamicOffensivePower = $unit->perks->filter(static function ($perk) {
                                                      return Str::startsWith($perk->key, ['offense_from_', 'offense_vs_']);
                                                  })->count() > 0;
                                                  $hasDynamicDefensivePower = $unit->perks->filter(static function ($perk) {
                                                      return Str::startsWith($perk->key, ['defense_from_', 'defense_vs_']);
                                                  })->count() > 0;

                                                
                                                  $incomingAmount = $unitCalculator->getUnitTypeTotalIncoming($selectedDominion, $unitType);
                                                  $unitTypeTotalTrained = $unitCalculator->getUnitTypeTotalTrained($selectedDominion, $unitType);
                                                  $unitTypeTotalPaid = $unitCalculator->getUnitTypeTotalPaid($selectedDominion, $unitType);

                                              @endphp
                                              <td class="text-center">  <!-- OP / DP -->
                                                  @if ($offensivePower === 0)
                                                      <span class="text-muted">0</span>
                                                  @else
                                                      {{ display_number_format($offensivePower) }}{{ $hasDynamicOffensivePower ? '*' : null }}
                                                  @endif
                                                  &nbsp;/&nbsp;
                                                  @if ($defensivePower === 0)
                                                      <span class="text-muted">0</span>
                                                  @else
                                                      {{ display_number_format($defensivePower) }}{{ $hasDynamicDefensivePower ? '*' : null }}
                                                  @endif
                                              </td>
                                              <td class="text-center">  <!-- Trained -->
                                                  {{ number_format($unitTypeTotalTrained) }}
                                                  <!-- Incoming -->
                                                  @if($incomingAmount)
                                                    <br>
                                                    <span data-toggle="tooltip" data-placement="top" title="<small class='text-muted'>Paid:</small> {{ number_format($unitTypeTotalPaid) }}">
                                                        ({{ number_format($incomingAmount) }})
                                                    </span>
                                                  @endif
                                              </td>
                                          @else
                                              @php
                                                    $unit = $unitType;
                                                    $incomingAmount = $unitCalculator->getUnitTypeTotalIncoming($selectedDominion, $unitType);
                                                    $unitTypeTotalTrained = $unitCalculator->getUnitTypeTotalTrained($selectedDominion, $unitType);
                                                    $unitTypeTotalPaid = $unitCalculator->getUnitTypeTotalPaid($selectedDominion, $unitType);
                                              @endphp
                                              <td class="text-center">&mdash;</td>
                                              <td class="text-center">  <!-- If Spy/Wiz/AM -->
                                                  {{ number_format($unitTypeTotalTrained) }}

                                                  @if($incomingAmount)
                                                      <span data-toggle="tooltip" data-placement="top" title="<small class='text-muted'>Paid:</small> {{ number_format($unitTypeTotalPaid) }}">
                                                          <br>({{ number_format($incomingAmount) }})
                                                      </span>
                                                  @endif
                                              </td>
                                          @endif

                                        <td class="text-center" style="min-width: 150px">
                                            @if (!$unitCalculator->isUnitTrainableByDominion($unit, $selectedDominion))
                                                &mdash;
                                            @else
                                                <div class="input-group">
                                                    <input type="number" name="train[military_{{ $unitType }}]" class="form-control text-center" placeholder="{{ number_format($trainingCalculator->getMaxTrainable($selectedDominion)[$unitType]) }}" min="0" max="{{ $trainingCalculator->getMaxTrainable($selectedDominion)[$unitType] }}" value="{{ old('train.' . $unitType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default train-max" data-type="military_{{ $unitType }}" type="button">Max</button>
                                                    </span>
                                                </div>
                                            @endif
                                        </td>

                                        <td class="text-center">  <!-- Cost -->
                                            @if (!$unitCalculator->isUnitTrainableByDominion($unit, $selectedDominion))
                                                &mdash;
                                            @else
                                                {!! $unitHelper->getUnitCostString($selectedDominion->race, $trainingCalculator->getTrainingCostsPerUnit($selectedDominion)[$unitType]) !!}
                                            @endif


                                        </td>
                                    </tr>
                                @endif
                            @endforeach

                        </tbody>
                    </table>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }} id="submit">
                        {{ $militaryHelper->getTrainingButtonLabel($selectedDominion->race) }}
                    </button>
                    <div class="pull-right">

                      @if(!$selectedDominion->race->getPerkValue('no_drafting'))
                          {{ ucwords(Str::plural($raceHelper->getDrafteesTerm($selectedDominion->race), $selectedDominion->military_draftees)) }}: <strong>{{ number_format($selectedDominion->military_draftees) }}</strong> 
                      @endif

                      @if ($dominionHelper->isEnraged($selectedDominion))
                          <br> You were recently invaded, enraging your Spriggan and Leshy.
                      @endif
                    </div>
                </div>
            </form>
        </div>

        @if($magicCalculator->getWizardPointsRequiredByAllUnits($selectedDominion))
            @include('partials.dominion.military-units-wizard-points-requirements')
        @endif

        @include('partials.dominion.military-units-training')
        @include('partials.dominion.military-units-returning')
    </div>


    <div class="col-sm-12 col-md-3" id="units_release_and_draft_rate">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Housing</h3>
                @if(!$selectedDominion->race->getPerkValue('cannot_release_units'))
                    <a href="{{ route('dominion.military.release') }}" class="pull-right">Release Units</a>
                @endif
            </div>
            <form action="{{ route('dominion.military.change-draft-rate') }}" method="post" role="form">
                @csrf
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <tbody>
                            <tr>
                                <td class="text">Military</td>
                                <td class="text">
                                    {{ number_format($populationCalculator->getPopulationMilitary($selectedDominion)) }}
                                    ({{ number_format($populationCalculator->getPopulationMilitaryPercentage($selectedDominion), 2) }}%)
                                </td>
                            </tr>
                            @if (!$selectedDominion->race->getPerkValue('no_drafting'))
                                <tr>
                                    @if ($selectedDominion->race->name == 'Myconid')
                                        <td class="text">Germination</td>
                                    @else
                                        <td class="text">Draft Rate</td>
                                    @endif

                                    <td class="text">
                                        <input type="number" name="draft_rate" class="form-control text-center"
                                            style="display: inline-block; width: 4em;" placeholder="0" min="0" max="100"
                                            value="{{ $selectedDominion->draft_rate }}" {{ ($selectedDominion->isLocked() or $selectedDominion->race->getPerkValue('cannot_change_draft_rate')) ? 'disabled' : null }}>&nbsp;%
                                    </td>
                                </tr>
                            @endif
                            @include('partials.dominion.housing')
                        </tbody>
                    </table>
                </div>
                @if (!$selectedDominion->race->getPerkValue('no_drafting'))
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ ($selectedDominion->isLocked() or $selectedDominion->race->getPerkValue('cannot_change_draft_rate')) ? 'disabled' : null }}>Change</button>
                        </form>

                        @if(!$selectedDominion->race->getPerkValue('cannot_release_units'))
                            <form action="{{ route('dominion.military.release-draftees') }}" method="post" role="form" class="pull-right">
                                @csrf
                                <input type="hidden" style="display:none;" name="release[draftees]" value={{ intval($selectedDominion->military_draftees) }}>
                                <button type="submit" class="btn btn-warning btn-small" {{ ($selectedDominion->isLocked() or $selectedDominion->military_draftees == 0) ? 'disabled' : null }}>Release {{ Str::plural($raceHelper->getDrafteesTerm($selectedDominion->race)) }}</button>
                            </form>
                        @endif
                    </div>
                @endif
        </div>

        {{--
            @include('partials.dominion.military-cost-modifiers')
        --}}
        @include('partials.dominion.military-power-modifiers')
        @include('partials.dominion.watched-dominions')
    </div>

</div>

@if($selectedDominion->hasSpellCast('annexation'))
<div class="row">
    <div class="col-sm-9 col-md-9">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="ra ra-castle-flag"></i> Annexed dominions</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table">
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Dominion</th>
                            <th>Military Power</th>
                            <th>Peasants</th>
                            <th>Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($spellCalculator->getAnnexedDominions($selectedDominion) as $dominion)
                            <tr>
                                <td>{{ $dominion->name }}</td>
                                <td>{{ number_format($militaryCalculator->getRawMilitaryPowerFromAnnexedDominion($dominion)) }}</td>
                                <td>{{ number_format($dominion->peasants) }}</td>
                                <td>{{ number_format($spellCalculator->getTicksRemainingOfAnnexation($selectedDominion, $dominion)) . ' ' . Str::plural('tick', $spellCalculator->getTicksRemainingOfAnnexation($selectedDominion, $dominion)) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>You have annexed <b>{{ count($spellCalculator->getAnnexedDominions($selectedDominion)) . ' ' . Str::plural('dominion', count($spellCalculator->getAnnexedDominions($selectedDominion))) }}</b>, providing you with an additional <b>{{ number_format($militaryCalculator->getRawMilitaryPowerFromAnnexedDominions($selectedDominion)) }}</b> raw offensive and defensive power.</p>
            </div>
        </div>
    </div>

</div>
@endif
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('.train-max').click(function(e) {
                var troopType = $(this).data('type');
                var troopInput = $('input[name=train\\['+troopType+'\\]]');
                var maxAmount = troopInput.attr('max');
                $('input[name^=train]').val('');
                troopInput.val(maxAmount);
            });
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
