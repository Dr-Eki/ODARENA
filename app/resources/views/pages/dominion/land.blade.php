@extends('layouts.master')

{{--
@section('page-header', 'Land')
--}}

@section('content')
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-redo-alt"></i> Rezone Land</h3>
                        <small class="pull-right text-muted">
                            <span data-toggle="tooltip" data-placement="top" title="How many acres of land you can afford to rezone right now">Max rezonable</span>: {{ number_format($rezoningCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('acre', $rezoningCalculator->getMaxAfford($selectedDominion)) }}
                        </small>
                    </div>
                    <form action="{{ route('dominion.land') }}" method="post" role="form">
                        @csrf
                        <input type="hidden" name="action" value="rezone">
                        <div class="box-body table-responsive no-padding">
                            <table class="table">
                                <colgroup>
                                    <col>
                                    <col width="100">
                                    <col width="100">
                                    <col width="100">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Land Type</th>
                                        <th class="text-center">Barren</th>
                                        <th class="text-center">Rezone From</th>
                                        <th class="text-center">Rezone Into</th>
                                    </tr>
                                </thead>
                                @foreach ($landCalculator->getBarrenLandByLandType($selectedDominion) as $landType => $amount)
                                    <tr>
                                        <td>
                                          {!! $landHelper->getLandTypeIconHtml($landType) !!}
                                          {{ ucfirst($landType) }}
                                          @if ($landType === $selectedDominion->race->home_land_type)
                                              <small class="text-muted"><span title="This is the land type where {{ ($selectedDominion->race->name) }} constructs home buildings."><i class="fa fa-home"></span></i></small>
                                          @endif
                                        </td>
                                        <td class="text-center">{{ number_format($amount) }}</td>
                                        <td class="text-center">
                                            <input name="remove[{{ $landType }}]" type="number"
                                                   class="form-control text-center" placeholder="0" min="0"
                                                   max="{{ $amount }}"
                                                   value="{{ old('remove.' . $landType) }}" {{ $selectedDominion->isLocked() || $amount == 0 ? 'disabled' : null }}>
                                        </td>
                                        <td class="text-center">
                                            <input name="add[{{ $landType }}]" type="number"

                                                   class="form-control text-center" placeholder="0" min="0"
                                                   max="{{ $rezoningCalculator->getMaxAfford($selectedDominion) }}"
                                                   value="{{ old('add.' . $landType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                        <div class="box-footer">
                          @if ((bool)$selectedDominion->race->getPerkValue('cannot_rezone'))
                              <span class="label label-danger">{{ $selectedDominion->race->name }} dominions cannot rezone</span>
                          @else
                            <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>rezone</button>
                          @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-telescope"></i> Explore Land</h3>
                        <small class="pull-right text-muted">
                            <span data-toggle="tooltip" data-placement="top" title="How many acres of land you can afford to explore right now">Max explorable</span>: {{ number_format($explorationCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('acre', $explorationCalculator->getMaxAfford($selectedDominion)) }}
                        </small>
                    </div>
                    <form action="{{ route('dominion.land') }}" method="post" role="form">
                        @csrf
                        <input type="hidden" name="action" value="explore">
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
                                        <th>Land Type</th>
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
                                                {!! $landHelper->getLandTypeIconHtml($landType) !!}
                                                {{ ucfirst($landType) }}
                                                @if ($landType === $selectedDominion->race->home_land_type)
                                                    <small class="text-muted"><span title="This is the land type where {{ ($selectedDominion->race->name) }} constructs home buildings."><i class="fa fa-home"></span></i></small>
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
                              <span class="label label-danger">{{ $selectedDominion->race->name }} dominions cannot explore</span>
                          @elseif ($guardMembershipService->isEliteGuardMember($selectedDominion))
                              <span class="label label-danger">Members of the Warriors League cannot explore</span>
                          @elseif ($spellCalculator->isSpellActive($selectedDominion, 'rainy_season'))
                              <span class="label label-primary">You cannot explore during the Rainy Season</span>
                          @elseif ($spellCalculator->isSpellActive($selectedDominion, 'stasis'))
                              <span class="label label-danger">You cannot explore while you are in stasis</span>
                          @else
                            <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Explore</button>
                          @endif
                        </div>
                    </form>
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
                <h4>Rezone</h4>
                <p>You can convert barren land of one type into another. Rezoning is instant. Each acre costs <strong>{{ number_format($rezoningCalculator->getRezoningCost($selectedDominion)) }} {{ $rezoningCalculator->getRezoningMaterial($selectedDominion) }}</strong> to rezone.</p>

                @if (1-$rezoningCalculator->getCostMultiplier($selectedDominion) !== 0)
                  <p>Your rezoning costs are

                  @if (1-$rezoningCalculator->getCostMultiplier($selectedDominion) > 0)
                    decreased
                  @else
                    increased
                  @endif

                   by <strong>{{ number_format((abs(1-$rezoningCalculator->getCostMultiplier($selectedDominion)))*100, 2) }}%</strong>.</p>

                @endif

                <p>You have {{ number_format($landCalculator->getTotalBarrenLand($selectedDominion)) }} {{ str_plural('acre', $landCalculator->getTotalBarrenLand($selectedDominion)) }} of barren land
                  and you can afford to re-zone <b>{{ number_format($rezoningCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('acre', $rezoningCalculator->getMaxAfford($selectedDominion)) }}</b>.</p>


                <h4>Explore</h4>
                <p>You can explore land to grow your dominion. It takes <b>{{ $explorationCalculator->getExploreTime($selectedDominion) }}  ticks</b> to explore.</p>
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

<div class="row">

    <div class="col-sm-12 col-md-9">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-clock-o"></i> Incoming Land</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table">
                    <colgroup>
                        <col width="100">
                        @for ($i = 1; $i <= 12; $i++)
                            <col>
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

<div class="row">
    <div class="col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-plus"></i> Daily Bonus</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-xs-3 text-center">
                        <form action="{{ route('dominion.land') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="action" value="daily_land">
                            <button type="submit" name="land" class="btn btn-primary btn-lg" {{ $selectedDominion->isLocked() || $selectedDominion->daily_land || $selectedDominion->protection_ticks > 0 || !$selectedDominion->round->hasStarted() ? 'disabled' : null }}>
                                <i class="ra ra-compass ra-fw"></i>
                                Claim Daily Land Bonus
                            </button>
                        </form>
                    </div>
                    <div class="col-xs-9">
                        <p>The Daily Land Bonus instantly gives you some barren acres of <strong>{{ $selectedDominion->race->home_land_type }}</strong>.</p>
                        <p>You have a 0.50% chance to get 100 acres, and a 99.50% chance to get a random amount between 10 and 40 acres.</p>
                        @if ($selectedDominion->protection_ticks > 0 or !$selectedDominion->round->hasStarted())
                        <p><strong>You cannot claim daily bonus while you are in protection or before the round has started.</strong></p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa fa-users"></i> Support ODARENA</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <script type='text/javascript' src='https://ko-fi.com/widgets/widget_2.js'></script><script type='text/javascript'>kofiwidget2.init('Support ODARENA on Ko-fi', '#dd4b39', 'P5P526XK1');kofiwidget2.draw();</script>
                    </div>
                    <div class="col-md-9">
                        <p>In addition to be free open source software, ODARENA is and always will be free to play. There will be no advertising and your data will never be used for anything other than game statistics.</p>
                        <p>While not much, maintaining the game is a side project and costs are taken out of pocket. Any support of any kind is highly appreciated.</p>
                    </div>
                </div>


            </div>
        </div>
    </div>
</div>
@endsection
