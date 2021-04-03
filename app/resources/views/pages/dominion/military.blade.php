@extends('layouts.master')

{{--
@section('page-header', 'Military')
--}}

@section('content')
<div class="row">

    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="ra ra-sword"></i> Military</h3>
                <a href="{{ route('dominion.mentor.military') }}" class="pull-right"><span><i class="ra ra-help"></i> Mentor</span></a>
            </div>
            <form action="{{ route('dominion.military.train') }}" method="post" role="form">
                @csrf
                <div class="box-body table-responsive no-padding">
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
                                <th class="text-center">Trained<br>(Training)</th>
                                <th class="text-center">Train</th>
                                <th class="text-center">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($unitHelper->getUnitTypes() as $unitType)
                                @if(($selectedDominion->race->getPerkValue('cannot_train_spies') and $unitType == 'spies') or ($selectedDominion->race->getPerkValue('cannot_train_wizards') and $unitType == 'wizards') or ($selectedDominion->race->getPerkValue('cannot_train_archmages') and $unitType == 'archmages'))
                                {{-- Do nothing --}}
                                @else
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race) }}">
                                                {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                                            </span>
                                        </td>
                                          @if (in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4']))
                                              @php
                                                  $unit = $selectedDominion->race->units->filter(function ($unit) use ($unitType) {
                                                      return ($unit->slot == (int)str_replace('unit', '', $unitType));
                                                  })->first();

                                                  $offensivePower = $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unit, 'offense');
                                                  $defensivePower = $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unit, 'defense');

                                                  $hasDynamicOffensivePower = $unit->perks->filter(static function ($perk) {
                                                      return starts_with($perk->key, ['offense_from_', 'offense_staggered_', 'offense_vs_']);
                                                  })->count() > 0;
                                                  $hasDynamicDefensivePower = $unit->perks->filter(static function ($perk) {
                                                      return starts_with($perk->key, ['defense_from_', 'defense_staggered_', 'defense_vs_']);
                                                  })->count() > 0;
                                              @endphp
                                              <td class="text-center">  <!-- OP / DP -->
                                                  @if ($offensivePower === 0)
                                                      <span class="text-muted">0</span>
                                                  @else
                                                      {{ (strpos($offensivePower, '.') !== false) ? number_format($offensivePower, 2) : number_format($offensivePower) }}{{ $hasDynamicOffensivePower ? '*' : null }}
                                                  @endif
                                                  &nbsp;/&nbsp;
                                                  @if ($defensivePower === 0)
                                                      <span class="text-muted">0</span>
                                                  @else
                                                      {{ (strpos($defensivePower, '.') !== false) ? number_format($defensivePower, 2) : number_format($defensivePower) }}{{ $hasDynamicDefensivePower ? '*' : null }}
                                                  @endif
                                              </td>
                                              <td class="text-center">  <!-- Trained -->
                                                  {{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, $unit->slot)) }}
                                                  @if($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}") > 0)
                                                  <br>
                                                  ({{ number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}")) }})
                                                  @endif
                                              </td>
                                          @else
                                              <td class="text-center">&mdash;</td>
                                              <td class="text-center">  <!-- If Spy/Wiz/AM -->
                                                  {{ number_format($selectedDominion->{'military_' . $unitType}) }}

                                                  @if($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}") > 0)
                                                  <br>
                                                  ({{ number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}")) }})
                                                  @endif
                                              </td>
                                              @endif
                                        <td class="text-center">  <!-- Train -->
                                          @if ($selectedDominion->race->getUnitPerkValueForUnitSlot(intval(str_replace('unit','',$unitType)), 'cannot_be_trained'))
                                            &mdash;
                                          @else
                                            <input type="number" name="train[military_{{ $unitType }}]" class="form-control text-center" placeholder="{{ number_format($trainingCalculator->getMaxTrainable($selectedDominion)[$unitType]) }}" min="0" max="" size="8" style="min-width:5em;" value="{{ old('train.' . $unitType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                          @endif
                                        </td>

                                        <td class="text-center">  <!-- Cost -->
                                            @if ($selectedDominion->race->getUnitPerkValueForUnitSlot(intval(str_replace('unit','',$unitType)), 'cannot_be_trained'))
                                              &mdash;
                                            @else
                                                @php
                                                    // todo: move this shit to view presenter or something
                                                    $labelParts = [];

                                                    foreach ($trainingCalculator->getTrainingCostsPerUnit($selectedDominion)[$unitType] as $costType => $value) {

                                                      # Only show resource if there is a corresponding cost
                                                      if($value !== 0)
                                                      {

                                                        switch ($costType) {
                                                            case 'gold':
                                                                $labelParts[] = number_format($value) . ' gold';
                                                                break;

                                                            case 'ore':
                                                                $labelParts[] = number_format($value) . ' ore';
                                                                break;

                                                            case 'food':
                                                                $labelParts[] =  number_format($value) . ' food';
                                                                break;

                                                            case 'mana':
                                                                $labelParts[] =  number_format($value) . ' mana';
                                                                break;

                                                            case 'lumber':
                                                                $labelParts[] =  number_format($value) . ' lumber';
                                                                break;

                                                            case 'gem':
                                                                $labelParts[] =  number_format($value) . ' ' . str_plural('gem', $value);
                                                                break;

                                                            case 'prestige':
                                                                $labelParts[] =  number_format($value) . ' Prestige';
                                                                break;

                                                            case 'boat':
                                                                $labelParts[] =  number_format($value) . ' ' . str_plural('boat', $value);
                                                                break;

                                                            case 'champion':
                                                                $labelParts[] =  number_format($value) . ' ' . str_plural('Champion', $value);
                                                                break;

                                                            case 'soul':
                                                                $labelParts[] =  number_format($value) . ' ' . str_plural('Soul', $value);
                                                                break;

                                                            case 'blood':
                                                                $labelParts[] =  number_format($value) . ' blood';
                                                                break;

                                                            case 'unit1':
                                                            case 'unit2':
                                                            case 'unit3':
                                                            case 'unit4':
                                                                $labelParts[] =  number_format($value) . ' ' . str_plural($unitHelper->getUnitName($costType, $selectedDominion->race), $value);
                                                                break;

                                                            case 'morale':
                                                                $labelParts[] =  number_format($value) . '% morale';
                                                                break;

                                                            case 'peasant':
                                                                $labelParts[] =  number_format($value) . ' peasant';
                                                                break;

                                                            case 'wild_yeti':
                                                                $labelParts[] =  number_format($value) . ' ' . str_plural('wild yeti', $value);
                                                                break;

                                                            case 'spy':
                                                                $labelParts[] =  number_format($value) . ' ' . str_plural('Spy', $value);
                                                                break;

                                                            case 'wizard':
                                                                $labelParts[] =  number_format($value) . ' ' . str_plural('Wizard', $value);
                                                                break;

                                                            case 'archmage':
                                                                $labelParts[] =  number_format($value) . ' ' . str_plural('Archmage', $value);
                                                                break;

                                                            case 'wizards':
                                                                $labelParts[] = '1 Wizard';
                                                                break;

                                                            default:
                                                                break;
                                                            }

                                                        } #ENDIF
                                                    }

                                                    echo implode(',<br>', $labelParts);
                                                @endphp
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endforeach

                        </tbody>
                    </table>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                      @if ($selectedDominion->race->name == 'Growth')
                          Mutate
                      @elseif ($selectedDominion->race->name == 'Myconid')
                          Grow
                      @elseif ($selectedDominion->race->name == 'Swarm')
                          Hatch
                      @else
                          Train
                      @endif
                    </button>
                    <div class="pull-right">
                      You have <strong>{{ number_format($selectedDominion->military_draftees) }}</strong> {{ ucwords(str_plural($raceHelper->getDrafteesTerm($selectedDominion->race))) }} available.

                      @if ($selectedDominion->race->name == 'Demon')
                          <br> You also have <strong>{{ number_format($selectedDominion->resource_soul) }}</strong> souls and <strong>{{ number_format($selectedDominion->resource_blood) }}</strong> gallons of blood.
                      @endif

                      @if ($selectedDominion->race->name == 'Norse')
                          <br> You also have <strong>{{ number_format($selectedDominion->resource_champion) }}</strong> legendary champions waiting.
                      @endif

                      @if ($militaryCalculator->getRecentlyInvadedCount($selectedDominion) and $selectedDominion->race->name == 'Sylvan')
                          <br> You were recently invaded, enraging your Spriggan and Leshy.
                      @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-sword"></i> Units in training and home</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <colgroup>
                            <col>
                            @for ($i = 1; $i <= 12; $i++)
                                <col width="20">
                            @endfor
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Unit</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    <th class="text-center">{{ $i }}</th>
                                @endfor
                                <th class="text-center">Home (Training)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($unitHelper->getUnitTypes() as $unitType)
                                <tr>
                                    <td>

                                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race) }}">
                                            {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                                        </span>
                                    </td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        <td class="text-center">
                                            @if ($queueService->getTrainingQueueAmount($selectedDominion, "military_{$unitType}", $i) === 0)
                                                -
                                            @else
                                                {{ number_format($queueService->getTrainingQueueAmount($selectedDominion, "military_{$unitType}", $i)) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        {{ number_format($selectedDominion->{'military_' . $unitType}) }}
                                        ({{ number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}")) }})
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Units returning from battle</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <colgroup>
                            <col>
                            @for ($i = 1; $i <= 12; $i++)
                                <col width="20">
                            @endfor
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Unit</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    <th class="text-center">{{ $i }}</th>
                                @endfor
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (range(1, 4) as $slot)
                                @php($unitType = ('unit' . $slot))
                                <tr>
                                    <td>

                                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race) }}">
                                            {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                                        </span>
                                    </td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        <td class="text-center">
                                            @if ($queueService->getInvasionQueueAmount($selectedDominion, "military_{$unitType}", $i) === 0)
                                                -
                                            @else
                                                {{ number_format($queueService->getInvasionQueueAmount($selectedDominion, "military_{$unitType}", $i)) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        {{ number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, "military_{$unitType}")) }}
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
                <h3 class="box-title">Housing</h3>
                <a href="{{ route('dominion.military.release') }}" class="pull-right">Release Units</a>
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
                            @if ($selectedDominion->race->name !== 'Growth')
                            <tr>
                                @if ($selectedDominion->race->name == 'Myconid')
                                <td class="text">Germination</td>
                                @else
                                <td class="text">Draft Rate</td>
                                @endif
                                <td class="text">
                                    <input type="number" name="draft_rate" class="form-control text-center"
                                           style="display: inline-block; width: 4em;" placeholder="0" min="0" max="100"
                                           value="{{ $selectedDominion->draft_rate }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>&nbsp;%
                                </td>
                            </tr>
                            @endif
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
                        </tbody>
                    </table>
                </div>
                @if ($selectedDominion->race->name !== 'Growth')
                <div class="box-footer">
                    <button type="submit"
                            class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Change
                    </button>
                </div>
                @endif
            </form>
        </div>
        @include('partials.dominion.military-cost-modifiers')
        @include('partials.dominion.military-power-modifiers')
    </div>

</div>
@endsection
