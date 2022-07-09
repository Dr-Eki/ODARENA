@extends('layouts.master')
@section('title', 'Land')

{{--
@section('page-header', 'Land')
--}}

@section('content')
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="row">
            <div class="{{ $raceHelper->hasLandImprovements($selectedDominion->race) ? 'col-md-10' : 'col-md-12' }}">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-map fa-fw"></i> Land</h3>
                        <small class="pull-right text-muted">
                            <span data-toggle="tooltip" data-placement="top" title="How many acres of land you can afford to explore right now">Max explorable</span>: {{ number_format($explorationCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('acre', $explorationCalculator->getMaxAfford($selectedDominion)) }}
                        </small>
                    </div>
                    <form action="{{ route('dominion.land') }}" method="post" role="form">
                        @csrf
                        <input type="hidden" name="action" value="rezone">                    
                        <div class="box-body table-responsive no-padding">
                            <table class="table">
                                <colgroup>
                                    <col width="100">
                                    <col width="100">
                                    <col width="100">
                                    <col width="100">
                                    @for ($i = 1; $i <= 12; $i++)
                                        <col>
                                    @endfor
                                    <col width="100">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Land Type</th>
                                        <th class="text-center">Barren</th>
                                        <th class="text-center">Rezone From</th>
                                        <th class="text-center">Rezone Into</th>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <th class="text-center">{{ $i }}</th>
                                        @endfor
                                        <th class="text-center">Total<br>Incoming</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $incomingLandPerTick = array_fill(1,12,0);
                                        $barrenLand = $landCalculator->getBarrenLandByLandType($selectedDominion);
                                    @endphp
                                    @foreach ($landHelper->getLandTypes() as $landType)
                                        @php
                                            $amount = $barrenLand[$landType];
                                        @endphp
                                        <tr>
                                            <td>{!! $landHelper->getLandTypeIconHtml($landType) !!} {{ ucfirst($landType) }}</td>

                                            <!-- Rezone From -->
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

                                            <!-- Incoming Land -->
                                            @for ($i = 1; $i <= 12; $i++)
                                                @php
                                                    $land = (
                                                        $queueService->getExplorationQueueAmount($selectedDominion, "land_{$landType}", $i) +
                                                        $queueService->getInvasionQueueAmount($selectedDominion, "land_{$landType}", $i) +
                                                        $queueService->getExpeditionQueueAmount($selectedDominion, "land_{$landType}", $i)
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
                                            <td class="text-center">{{ number_format($queueService->getExplorationQueueTotalByResource($selectedDominion, "land_{$landType}") + $queueService->getInvasionQueueTotalByResource($selectedDominion, "land_{$landType}") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "land_{$landType}")) }}</td>


                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td><em>Total</em></td>
                                        <td class="text-center"><em>{{ number_format(array_sum($barrenLand)) }}</em></td>
                                        <td colspan="2">
                                            @if ((bool)$selectedDominion->race->getPerkValue('cannot_rezone'))
                                                <span class="label label-danger">{{ $selectedDominion->race->name }} dominions cannot rezone</span>
                                            @else
                                                <button type="submit" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Rezone</button>
                                            @endif
                                        </td>
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
                                    </tr>

                                    
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
            @if($raceHelper->hasLandImprovements($selectedDominion->race))
            <div class="col-md-2">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-map-marked"></i> Land Perks</h3>
                    </div>
                    <div class="box-body">
                        @foreach ($landImprovementPerks as $perkKey)
                            <ul>
                                  @if($landImprovementHelper->getPerkType($perkKey) == 'mod')
                                      <li>{{ $landImprovementHelper->getPerkDescription($perkKey, $selectedDominion->getLandImprovementPerkMultiplier($perkKey) * 100, false) }}</li>
                                  @elseif($landImprovementHelper->getPerkType($perkKey) == 'raw')
                                      <li>{{ $landImprovementHelper->getPerkDescription($perkKey, $selectedDominion->getLandImprovementPerkValue($perkKey), false) }}</li>
                                  @else
                                      <li><pre>Error! Unknown perk type (getPerkType()) for $perkKey {{ $perkKey }}</pre></li>
                                  @endif
                            </ul>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
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

                <p>You can afford to explore <b>{{ number_format($explorationCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('acre', $explorationCalculator->getMaxAfford($selectedDominion)) }}</b>.</p>
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
                <p>The Daily Land Bonus instantly gives you some barren acres of <strong>{{ $selectedDominion->race->home_land_type }}</strong>.</p>
                <p>You have a 0.50% chance to get 100 acres, and a 99.50% chance to get a random amount between 10 and 40 acres.</p>
                @if ($selectedDominion->protection_ticks > 0 or !$selectedDominion->round->hasStarted())
                <p><strong>You cannot claim daily bonus while you are in protection or before the round has started.</strong></p>
                @endif
                <form action="{{ route('dominion.land') }}" method="post" role="form">
                    @csrf
                    <input type="hidden" name="action" value="daily_land">
                    <button type="submit" name="land" class="btn btn-primary btn-block btn-lg" {{ $selectedDominion->isLocked() || $selectedDominion->daily_land || $selectedDominion->protection_ticks > 0 || !$selectedDominion->round->hasStarted() ? 'disabled' : null }}>
                        Claim Daily Land Bonus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
