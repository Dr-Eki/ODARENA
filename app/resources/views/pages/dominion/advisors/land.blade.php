@extends('layouts.master')

@section('page-header', 'Land Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="row">

        <div class="col-sm-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-honeycomb"></i> Land Advisor</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <colgroup>
                            <col>
                            <col width="100">
                            <col width="100">
                            <col width="100">
                            @if ($selectedDominion->race->getPerkValue('land_improvements') or $selectedDominion->race->getPerkValue('defense_from_forest'))
                                <col width="200">
                            @endif
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Land Type</th>
                                <th class="text-center">Number</th>
                                <th class="text-center">% of total</th>
                                <th class="text-center">Barren</th>
                                @if ($selectedDominion->race->getPerkValue('land_improvements') or $selectedDominion->race->getPerkValue('defense_from_forest'))
                                    <th class="text-center">Bonus</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($landHelper->getLandTypes() as $landType)
                                <tr>
                                    <td>
                                        {{ ucfirst($landType) }}
                                        @if ($landType === $selectedDominion->race->home_land_type)
                                            <small class="text-muted"><i>(home)</i></small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($selectedDominion->{'land_' . $landType}) }}</td>
                                    <td class="text-center">{{ number_format((($selectedDominion->{'land_' . $landType} / $landCalculator->getTotalLand($selectedDominion)) * 100), 2) }}%</td>
                                    <td class="text-center">{{ number_format($landCalculator->getTotalBarrenLandByLandType($selectedDominion, $landType)) }}</td>
                                    @if ($selectedDominion->race->getPerkValue('land_improvements'))
                                        <td class="text-center">
                                              @if($landType == 'plain')
                                                  +{{ number_format($landImprovementCalculator->getOffensivePowerBonus($selectedDominion)*100,2) }}% Offensive Power
                                              @elseif($landType == 'mountain')
                                                  +{{ number_format($landImprovementCalculator->getGoldProductionBonus($selectedDominion)*100,2) }}% Gold Production
                                              @elseif($landType == 'swamp')
                                                  +{{ number_format($landImprovementCalculator->getWizardPowerBonus($selectedDominion)*100,2) }}% Wizard Strength
                                              @elseif($landType == 'forest')
                                                  +{{ number_format($landImprovementCalculator->getPopulationBonus($selectedDominion)*100,2) }}% Max Population
                                              @elseif($landType == 'hill')
                                                  +{{ number_format($landImprovementCalculator->getDefensivePowerBonus($selectedDominion)*100,2) }}% Defensive Power
                                              @elseif($landType == 'water')
                                                  +{{ number_format($landImprovementCalculator->getFoodProductionBonus($selectedDominion)*100,2) }}% Food and Boat Production
                                              @endif
                                        </td>
                                    @endif


                                    @if ($selectedDominion->race->getPerkValue('defense_from_forest'))
                                        <td class="text-center">
                                            @if($militaryCalculator->getDefensivePowerModifierFromLandType($selectedDominion, $landType))
                                                +{{ number_format($militaryCalculator->getDefensivePowerModifierFromLandType($selectedDominion, $landType)*100,2) }}% Defensive Power
                                            @else
                                                &mdash;
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                                <tr>
                                    <td><em>Total</em></td>
                                    <td class="text-center"><em>{{ number_format($landCalculator->getTotalLand($selectedDominion)) }}</em></td>
                                    <td class="text-center"><em>100.00%</em></td>
                                    <td class="text-center"><em>{{ number_format($landCalculator->getTotalBarrenLand($selectedDominion)) }}</em></td>
                                    @if ($selectedDominion->race->getPerkValue('land_improvements'))
                                        <th class="text-center"></th>
                                    @endif
                                </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Incoming land breakdown</h3>
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
                                <th>Land Type</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    <th class="text-center">{{ $i }}</th>
                                @endfor
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php
                            $incomingLandPerTick = array_fill(1,12,0);
                        @endphp
                        @foreach ($landHelper->getLandTypes() as $landType)
                            <tr>
                                <td>
                                    {{ ucfirst($landType) }}
                                    @if ($landType === $selectedDominion->race->home_land_type)
                                        <small class="text-muted"><i>(home)</i></small>
                                    @endif
                                </td>
                                @for ($i = 1; $i <= 12; $i++)
                                    @php
                                        $land = (
                                            $queueService->getExplorationQueueAmount($selectedDominion, "land_{$landType}", $i) +
                                            $queueService->getInvasionQueueAmount($selectedDominion, "land_{$landType}", $i)
                                        );
                                        $incomingLandPerTick[$i] += $land;
                                    @endphp
                                    <td class="text-center">
                                        @if ($land === 0)
                                            -
                                        @else
                                            {{ number_format($land) }}
                                        @endif
                                    </td>
                                @endfor
                                <td class="text-center">{{ number_format($queueService->getExplorationQueueTotalByResource($selectedDominion, "land_{$landType}") + $queueService->getInvasionQueueTotalByResource($selectedDominion, "land_{$landType}")) }}</td>
                            </tr>
                        @endforeach
                            <tr>
                                <td><em>Total</em></td>
                                @for ($i = 1; $i <= 12; $i++)
                                    <td class="text-center">
                                      <em>
                                    @if($incomingLandPerTick[$i] !== 0)
                                        {{ number_format($incomingLandPerTick[$i]) }}
                                    @else
                                        -
                                    @endif
                                    </em>
                                    </td>
                                @endfor
                                <td class="text-center"><em>{{ number_format(array_sum($incomingLandPerTick)) }}</em></td>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
