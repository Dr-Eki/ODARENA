@extends('layouts.master')

{{--
@section('page-header', 'Explore')
--}}

@section('content')

<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="ra ra-telescope"></i> Explore Land</h3>
            </div>
            <form action="{{ route('dominion.explore') }}" method="post" role="form">
                @csrf
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <colgroup>
                            <col>
                            <col width="100">
                            <col width="100">
                            <col width="100">
                            <col width="100">
                            @if ($selectedDominion->race->getPerkValue('land_improvements'))
                            <col width="200">
                            @endif
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Terrain</th>
                                <th class="text-center">Owned</th>
                                <th class="text-center">Barren</th>
                                <th class="text-center">Incoming</th>
                                <th class="text-center">Explore For</th>
                                @if ($selectedDominion->race->getPerkValue('land_improvements'))
                                    <th class="text-center">Bonus</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalIncomingLand = 0;
                            @endphp
                            @foreach ($landHelper->getLandTypes() as $landType)
                                <tr>
                                    <td>
                                        {{ ucfirst($landType) }}
                                        @if ($landType === $selectedDominion->race->home_land_type)
                                            <br>
                                            <small class="text-muted"><i><span title="This is the land type where your {{ strtolower($selectedDominion->race->name) }} race constructs home buildings on">Home land type</span></i></small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ number_format($selectedDominion->{'land_' . $landType}) }}
                                        <small>
                                            ({{ number_format((($selectedDominion->{'land_' . $landType} / $landCalculator->getTotalLand($selectedDominion)) * 100), 2) }}%)
                                        </small>
                                    </td>
                                    <td class="text-center">{{ number_format($landCalculator->getTotalBarrenLandByLandType($selectedDominion, $landType)) }}</td>
                                    <td class="text-center">{{ number_format($queueService->getExplorationQueueTotalByResource($selectedDominion, "land_{$landType}") + $queueService->getInvasionQueueTotalByResource($selectedDominion, "land_{$landType}")) }}</td>
                                    <td class="text-center">
                                        <input type="number" name="explore[land_{{ $landType }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $explorationCalculator->getMaxAfford($selectedDominion) }}" value="{{ old('explore.' . $landType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </td>
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
                                </tr>
                                @php
                                    $totalIncomingLand += $queueService->getExplorationQueueTotalByResource($selectedDominion, "land_{$landType}");
                                    $totalIncomingLand += $queueService->getInvasionQueueTotalByResource($selectedDominion, "land_{$landType}")
                                @endphp
                            @endforeach
                            {{--
                                <tr>
                                    <td><em>Total</em></td>
                                    <td class="text-center"><em>{{ number_format($landCalculator->getTotalLand($selectedDominion)) }}</em></td>
                                    <td class="text-center"><em>{{ number_format($landCalculator->getTotalBarrenLand($selectedDominion)) }} <small class="text-muted">({{ number_format(($landCalculator->getTotalBarrenLand($selectedDominion) / $landCalculator->getTotalLand($selectedDominion))*100)}}%)</span></em></td>
                                    <td class="text-center"><em>{{ number_format($totalIncomingLand) }}</em></td>
                                    <td></td>
                                </tr>
                            --}}
                        </tbody>
                    </table>
                </div>
                <div class="box-footer">
                  @if ((bool)$selectedDominion->race->getPerkValue('cannot_explore'))
                      <p><strong>Your faction is not able to explore.</strong></p>
                  @elseif ($guardMembershipService->isEliteGuardMember($selectedDominion))
                      <p><strong>As a member of the Warriors League, you cannot explore.</strong></p>
                  @elseif ($spellCalculator->isSpellActive($selectedDominion, 'rainy_season'))
                      <p><strong>Your cannot explore during the Rainy Season.</strong></p>
                  @elseif ($spellCalculator->isSpellActive($selectedDominion, 'stasis'))
                      <p><strong>You cannot explore while you are in stasis.</strong></p>
                  @else
                    <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Explore</button>
                  @endif
                </div>
            </form>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
                <a href="{{ route('dominion.advisors.land') }}" class="pull-right">Land Advisor</a>
            </div>
            <div class="box-body">
                <p>Here you can explore land to grow your dominion. It takes <b>{{ $explorationCalculator->getExploreTime($selectedDominion) }}  ticks</b> to explore.</p>
                <p>The cost for exploring one acre is {{ number_format($explorationCalculator->getGoldCost($selectedDominion)) }} gold and {{ number_format($explorationCalculator->getDrafteeCost($selectedDominion)) }} {{ str_plural('draftee', $explorationCalculator->getDrafteeCost($selectedDominion)) }}. Additionally, for every 1% of your current size you explore, you lose 8% morale.</p>

                @if ($explorationCalculator->getGoldCostBonus($selectedDominion) !== 1 or $explorationCalculator->getDrafteeCostModifier($selectedDominion) !== 0)
                  <p>Bonuses are

                  @if (1-$explorationCalculator->getGoldCostBonus($selectedDominion) > 0)
                    decreasing
                  @else
                    increasing
                  @endif

                   your exploring gold costs by <strong>{{ number_format((abs(1-$explorationCalculator->getGoldCostBonus($selectedDominion)))*100, 2) }}%</strong>

                  and

                  @if (1-$explorationCalculator->getDrafteeCostModifier($selectedDominion) > 0)
                    decreasing
                  @else
                    increasing
                  @endif

                   your draftee costs by <strong>{{ number_format(abs($explorationCalculator->getDrafteeCostModifier($selectedDominion))) }}</strong>.</p>

                @endif


                <p>You can afford to explore for <b>{{ number_format($explorationCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('acre', $explorationCalculator->getMaxAfford($selectedDominion)) }}</b>.</p>
            </div>
        </div>
    </div>
</div>
@endsection
