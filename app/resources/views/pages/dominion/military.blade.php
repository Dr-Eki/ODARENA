@extends('layouts.master')
@section('title', 'Military')

@php
    $start = microtime(true);
@endphp


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
                                <th class="text-center">Trained<br>(Training)</th>
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
                                                  {{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, $unit->slot)) }}
                                                  @if($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}") > 0)
                                                  <br>
                                                      <span data-toggle="tooltip" data-placement="top" title="<small class='text-muted'>Paid:</small> {{ number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}") + $militaryCalculator->getTotalUnitsForSlot($selectedDominion, $unit->slot)) }}">
                                                          ({{ number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}")) }})
                                                      </span>
                                                  @endif
                                              </td>
                                          @else
                                              @php
                                                  $unit = $unitType;
                                              @endphp
                                              <td class="text-center">&mdash;</td>
                                              <td class="text-center">  <!-- If Spy/Wiz/AM -->
                                                  {{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, $unitType)) }}

                                                  @if($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}") > 0)
                                                      <span data-toggle="tooltip" data-placement="top" title="<small class='text-muted'>Paid:</small> {{ number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}") + $militaryCalculator->getTotalUnitsForSlot($selectedDominion, $unitType)) }}">
                                                          <br>({{ number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}")) }})
                                                      </span>
                                                  @endif
                                              </td>
                                          @endif

                                        <td class="text-center" style="min-width: 150px">
                                            @if (!$unitHelper->isUnitTrainableByDominion($unit, $selectedDominion))
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
                                            @if (!$unitHelper->isUnitTrainableByDominion($unit, $selectedDominion))
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
                      @if ($selectedDominion->race->name == 'Growth')
                          Mutate
                      @elseif ($selectedDominion->race->name == 'Myconid')
                          Grow
                      @elseif ($selectedDominion->race->name == 'Swarm')
                          Hatch
                      @elseif ($selectedDominion->race->name == 'Lux')
                          Ascend
                      @else
                          Train
                      @endif
                    </button>
                    <div class="pull-right">

                      @if(!$selectedDominion->race->getPerkValue('no_drafting'))
                          {{ ucwords(str_plural($raceHelper->getDrafteesTerm($selectedDominion->race), $selectedDominion->military_draftees)) }}: <strong>{{ number_format($selectedDominion->military_draftees) }}</strong> 
                      @endif

                      @if ($dominionHelper->isEnraged($selectedDominion))
                          <br> You were recently invaded, enraging your Spriggan and Leshy.
                      @endif
                    </div>
                </div>
            </form>
        </div>
        {{ ldump(microtime(true) - $start) }}

        <!-- Stacked boxes -->
        <div class="col-sm-12 col-md-12" id="units_in_training_and_home">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-sword"></i> Units in training and home</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <colgroup>
                            <col>
                            @for ($i = 1; $i <= 12; $i++)
                                <col width="100">
                            @endfor
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Unit</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    <th class="text-center">{{ $i }}</th>
                                @endfor
                                <th class="text-center">Home<br>(Training)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($unitHelper->getUnitTypes($selectedDominion->race) as $unitType)
                                <tr>
                                    <td>

                                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'offense'), $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'defense'), ]) }}">
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
        {{ ldump(microtime(true) - $start) }}

        <div class="col-sm-12 col-md-12" id="units_returning">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-boot-stomp"></i> Units returning</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <colgroup>
                            <col>
                            @for ($i = 1; $i <= 12; $i++)
                                <col width="100">
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
                            @foreach (range(1, $selectedDominion->race->units->count()) as $slot)
                                @php
                                    $unitType = ('unit' . $slot)
                                @endphp
                                <tr>
                                    <td>

                                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'offense'), $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'defense'), ]) }}">
                                            {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                                        </span>
                                    </td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $unitTickAmount = $queueService->getInvasionQueueAmount($selectedDominion, "military_{$unitType}", $i);
                                            $unitTickAmount += $queueService->getExpeditionQueueAmount($selectedDominion, "military_{$unitType}", $i);
                                            $unitTickAmount += $queueService->getTheftQueueAmount($selectedDominion, "military_{$unitType}", $i);
                                            $unitTickAmount += $queueService->getSabotageQueueAmount($selectedDominion, "military_{$unitType}", $i);
                                            $unitTickAmount += $queueService->getDesecrationQueueAmount($selectedDominion, "military_{$unitType}", $i);
                                        @endphp
                                        <td class="text-center">
                                            {{ ($unitTickAmount > 0) ? number_format($unitTickAmount) : '-' }}
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        {{ number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, "military_{$unitType}") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "military_{$unitType}") + $queueService->getTheftQueueAmount($selectedDominion, "military_{$unitType}", $i) + $queueService->getSabotageQueueAmount($selectedDominion, "military_{$unitType}", $i)  + $queueService->getDesecrationQueueAmount($selectedDominion, "military_{$unitType}", $i)) }}
                                    </td>
                                </tr>
                            @endforeach
                            @if(!$selectedDominion->race->getPerkValue('cannot_train_spies'))
                                <tr>
                                    <td>Spies</td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $spiesTickAmount = $queueService->getInvasionQueueAmount($selectedDominion, "military_spies", $i);
                                            $spiesTickAmount += $queueService->getExpeditionQueueAmount($selectedDominion, "military_spies", $i);
                                            $spiesTickAmount += $queueService->getTheftQueueAmount($selectedDominion, "military_spies", $i);
                                            $spiesTickAmount += $queueService->getSabotageQueueAmount($selectedDominion, "military_spies", $i);
                                            $spiesTickAmount += $queueService->getDesecrationQueueAmount($selectedDominion, "military_spies", $i);
                                        @endphp
                                        <td class="text-center">
                                            {{ ($spiesTickAmount > 0) ? number_format($spiesTickAmount) : '-' }}
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        {{ number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, "military_spies") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "military_spies") + $queueService->getTheftQueueAmount($selectedDominion, "military_spies", $i) + $queueService->getSabotageQueueAmount($selectedDominion, "military_spies", $i) + $queueService->getDesecrationQueueAmount($selectedDominion, "military_spies", $i)) }}
                                    </td>
                                </tr>
                            @endif
                            @if(!$selectedDominion->race->getPerkValue('cannot_train_wizards'))
                                <tr>
                                    <td>Wizards</td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $wizardsTickAmount = $queueService->getInvasionQueueAmount($selectedDominion, "military_wizards", $i);
                                            $wizardsTickAmount += $queueService->getExpeditionQueueAmount($selectedDominion, "military_wizards", $i);
                                            $wizardsTickAmount += $queueService->getTheftQueueAmount($selectedDominion, "military_wizards", $i);
                                            $wizardsTickAmount += $queueService->getSabotageQueueAmount($selectedDominion, "military_wizards", $i);
                                            $wizardsTickAmount += $queueService->getDesecrationQueueAmount($selectedDominion, "military_wizards", $i);
                                        @endphp
                                        <td class="text-center">
                                            {{ ($wizardsTickAmount > 0) ? number_format($wizardsTickAmount) : '-' }}
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        {{ number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, "military_wizards") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "military_wizards") + $queueService->getTheftQueueAmount($selectedDominion, "military_wizards", $i) + $queueService->getSabotageQueueAmount($selectedDominion, "military_wizards", $i) + $queueService->getDesecrationQueueAmount($selectedDominion, "military_wizards", $i)) }}
                                    </td>
                                </tr>
                            @endif
                            @if(!$selectedDominion->race->getPerkValue('cannot_train_archmages'))
                                <tr>
                                    <td>Archmages</td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $archmagesTickAmount = $queueService->getInvasionQueueAmount($selectedDominion, "military_archmages", $i);
                                            $archmagesTickAmount += $queueService->getExpeditionQueueAmount($selectedDominion, "military_archmages", $i);
                                            $archmagesTickAmount += $queueService->getTheftQueueAmount($selectedDominion, "military_archmages", $i);
                                            $archmagesTickAmount += $queueService->getSabotageQueueAmount($selectedDominion, "military_archmages", $i);
                                            $archmagesTickAmount += $queueService->getDesecrationQueueAmount($selectedDominion, "military_archmages", $i);
                                        @endphp
                                        <td class="text-center">
                                            {{ ($archmagesTickAmount > 0) ? number_format($archmagesTickAmount) : '-' }}
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        {{ number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, "military_archmages") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "military_archmages") + $queueService->getTheftQueueAmount($selectedDominion, "military_archmages", $i) + $queueService->getSabotageQueueAmount($selectedDominion, "military_archmages", $i) + $queueService->getDesecrationQueueAmount($selectedDominion, "military_archmages", $i)) }}
                                    </td>
                                </tr>
                            @endif
                            @if(array_sum($returningResources) > 0)
                                <tr>
                                    <th colspan="14">Resources and Other</th>
                                </tr>

                                @foreach($returningResources as $key => $totalAmount)
                                    @if($totalAmount !== 0)
                                        @php

                                            $name = 'undefined:'.$key;

                                            if(in_array($key, $selectedDominion->race->resources))
                                            {
                                                $name = OpenDominion\Models\Resource::where('key', $key)->first()->name;
                                                $key = 'resource_' . $key;
                                            }
                                            elseif($key == 'xp')
                                            {
                                                $name = 'XP';
                                            }
                                            elseif($key == 'prestige')
                                            {
                                                $name = 'Prestige';
                                            }

                                        @endphp
                                        <tr>
                                            <td>{{ $name }}</td>
                                            @for ($i = 1; $i <= 12; $i++)
                                                @php
                                                    $resourceTickAmount = $queueService->getInvasionQueueAmount($selectedDominion, $key, $i);
                                                    $resourceTickAmount += $queueService->getExpeditionQueueAmount($selectedDominion, $key, $i);
                                                    $resourceTickAmount += $queueService->getTheftQueueAmount($selectedDominion, $key, $i);
                                                    $resourceTickAmount += $queueService->getSabotageQueueAmount($selectedDominion, $key, $i);
                                                    $resourceTickAmount += $queueService->getDesecrationQueueAmount($selectedDominion, $key, $i);
                                                @endphp
                                                <td class="text-center">
                                                    {{ ($resourceTickAmount > 0) ? number_format($resourceTickAmount) : '-' }}
                                                </td>
                                            @endfor
                                            <td class="text-center">
                                                {{ number_format($totalAmount) }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        {{ ldump(microtime(true) - $start) }}
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
                            @if ($selectedDominion->race->name !== 'Growth' and !$selectedDominion->race->getPerkValue('no_drafting'))
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
                            @include('partials.dominion.housing')
                            @if($selectedDominion->race->name == 'Undead' and $selectedDominion->realm->alignment == 'evil')
                                <tr>
                                    <td class="text">Crypt bodies:</td>
                                    <td class="text">{{ number_format($resourceCalculator->getRealmAmount($selectedDominion->realm, 'body')) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @if ($selectedDominion->race->name !== 'Growth' and !$selectedDominion->race->getPerkValue('no_drafting'))
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Change</button>
                    </form>

                    @if(!$selectedDominion->race->getPerkValue('cannot_release_units'))
                        <form action="{{ route('dominion.military.release-draftees') }}" method="post" role="form" class="pull-right">
                            @csrf
                            <input type="hidden" style="display:none;" name="release[draftees]" value={{ intval($selectedDominion->military_draftees) }}>
                            <button type="submit" class="btn btn-warning btn-small" {{ ($selectedDominion->isLocked() or $selectedDominion->military_draftees == 0) ? 'disabled' : null }}>Release {{ str_plural($raceHelper->getDrafteesTerm($selectedDominion->race)) }}</button>
                        </form>
                    @endif
                </div>
                @endif
        </div>
        {{ ldump(microtime(true) - $start) }}

        @include('partials.dominion.military-cost-modifiers')
        {{ ldump(microtime(true) - $start) }}
        @include('partials.dominion.military-power-modifiers')
        {{ ldump(microtime(true) - $start) }}
        @include('partials.dominion.watched-dominions')
        {{ ldump(microtime(true) - $start) }}
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
                                <td>{{ number_format($spellCalculator->getTicksRemainingOfAnnexation($selectedDominion, $dominion)) . ' ' . str_plural('tick', $spellCalculator->getTicksRemainingOfAnnexation($selectedDominion, $dominion)) }}</td>
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
                <p>You have annexed <b>{{ count($spellCalculator->getAnnexedDominions($selectedDominion)) . ' ' . str_plural('dominion', count($spellCalculator->getAnnexedDominions($selectedDominion))) }}</b>, providing you with an additional <b>{{ number_format($militaryCalculator->getRawMilitaryPowerFromAnnexedDominions($selectedDominion)) }}</b> raw offensive and defensive power.</p>
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
