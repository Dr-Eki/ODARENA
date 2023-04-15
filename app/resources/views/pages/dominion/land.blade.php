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
                            <span data-toggle="tooltip" data-placement="top" title="How many acres of land you can afford to rezone right now">Max rezonable</span>: {{ number_format($rezoningCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('acre', $rezoningCalculator->getMaxAfford($selectedDominion)) }}
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
                                    <col width="100">
                                    @for ($i = 1; $i <= 12; $i++)
                                        <col>
                                    @endfor
                                    <col width="100">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Land Type</th>
                                        <th class="text-center">Owned</th>
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
                                        $totalLand = $landCalculator->getTotalLand($selectedDominion);
                                    @endphp
                                    @foreach ($landHelper->getLandTypes() as $landType)
                                        @php
                                            $amount = $barrenLand[$landType];
                                        @endphp
                                        <tr>
                                            <td>{!! $landHelper->getLandTypeIconHtml($landType) !!} {{ ucfirst($landType) }}</td>

                                            <!-- Rezone From -->
                                            <td class="text-center">
                                                {{ number_format($selectedDominion->{'land_' . $landType}) }} 
                                                <small class="text-muted">({{ number_format(($selectedDominion->{'land_' . $landType} / $totalLand)*100,2) }}%)</small>
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($amount) }}
                                            </td>
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
                                            <td class="text-center">
                                                <span data-toggle="tooltip" data-placement="top" title="<small class='text-muted'>Paid:</small> {{  number_format($selectedDominion->{'land_' . $landType} + $queueService->getInvasionQueueTotalByResource($selectedDominion, "land_{$landType}") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "land_{$landType}") + $queueService->getExplorationQueueTotalByResource($selectedDominion, "land_{$landType}")) }}">
                                                    {{ number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, "land_{$landType}") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "land_{$landType}") + $queueService->getExplorationQueueTotalByResource($selectedDominion, "land_{$landType}")) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td><em>Total</em></td>
                                        <td class="text-center"><em>{{ number_format($landCalculator->getTotalLand($selectedDominion)) }}</em></td>
                                        <td class="text-center"><em>{{ number_format($landCalculator->getTotalBarrenLand($selectedDominion)) }}</em></td>
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
                                            @if($incomingLandPerTick[$i])
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

@if(env('APP_ENV') == 'local')
    <div class="row">
        <div class="col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-map fa-fw"></i> Terrain</h3>
                </div>           
                <div class="box-body table-responsive no-padding">
                    <form action="{{ route('dominion.land.rezone') }}" method="post" role="form">
                        @csrf
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
                                    <th>Terrain</th>
                                    <th class="text-center">Current</th>
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
                                    $incomingTerrainPerTick = array_fill(1,12,0);
                                    $barrenLand = $landCalculator->getBarrenLandByLandType($selectedDominion);
                                    $totalLand = $landCalculator->getTotalLand($selectedDominion);
                                @endphp
                                @foreach(OpenDominion\Models\Terrain::all()->sortBy('order') as $terrain)
                                    <tr>
                                        <td>{{ $terrain->name }}</td>
                                        <td class="text-center">{{ number_format($selectedDominion->{'terrain_' . $terrain->key}) }}</td>
                                        <td class="text-center">
                                            <input name="remove[{{ $terrain->key }}]" type="number"
                                                class="form-control text-center" placeholder="0" min="0"
                                                max="{{ $amount }}"
                                                value="{{ old('remove.' . $terrain->key) }}" {{ ($selectedDominion->isLocked() || !$amount) ? 'disabled' : null }}>
                                        </td>
                                        <td class="text-center">
                                            <input name="add[{{ $terrain->key }}]" type="number"

                                                class="form-control text-center" placeholder="0" min="0"
                                                max="{{ $rezoningCalculator->getMaxAfford($selectedDominion) }}"
                                                value="{{ old('add.' . $terrain->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        </td>
                                        @for ($i = 1; $i <= 12; $i++)
                                            @php
                                                $land = (
                                                    $queueService->getInvasionQueueAmount($selectedDominion, "terrain_{$terrain->key}", $i) +
                                                    $queueService->getExpeditionQueueAmount($selectedDominion, "terrain_{$terrain->key}", $i) +
                                                    $queueService->getRezoningQueueAmount($selectedDominion, "terrain_{$terrain->key}", $i)
                                                );
                                                $incomingTerrainPerTick[$i] += $land;
                                            @endphp
                                            <td class="text-center">
                                                @if (!$land)
                                                    -
                                                @else
                                                    {{ number_format($land) }}
                                                @endif
                                            </td>
                                        @endfor
                                        <td class="text-center">
                                            <span data-toggle="tooltip" data-placement="top" title="<small class='text-muted'>Paid:</small> {{  number_format($selectedDominion->{'terrain_' . $terrain->key} + $queueService->getInvasionQueueTotalByResource($selectedDominion, "terrain_{$terrain->key}") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "terrain_{$terrain->key}") + $queueService->getRezoningQueueTotalByResource($selectedDominion, "terrain_{$landType}")) }}">
                                                {{ number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, "terrain_{$terrain->key}") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "terrain_{$terrain->key}") + $queueService->getRezoningQueueTotalByResource($selectedDominion, "terrain_{$terrain->key}")) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td><strong>Total</strong></td>
                                    <td class="text-center"><strong>{{ number_format($selectedDominion->land) }}</strong></td>
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
                                                @if($incomingTerrainPerTick[$i])
                                                    {{ number_format($incomingTerrainPerTick[$i]) }}
                                                @else
                                                    -
                                                @endif
                                            </em>
                                        </td>
                                    @endfor
                                    <td class="text-center"><em>{{ number_format(array_sum($incomingTerrainPerTick)) }}</em></td>
                                </tr>
                            </tbody>
                        </table>
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
                    <h4>Rezone</h4>
                    <p>You can rezone from one terrain to another. Rezoning takes <strong>12 ticks</strong> to complete. Each acre costs <strong>{{ number_format($rezoningCalculator->getRezoningCost($selectedDominion)) }} {{ $rezoningCalculator->getRezoningMaterial($selectedDominion) }}</strong> to rezone.</p>

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
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

@push('page-scripts')
    <script type="text/javascript">
    $("form").submit(function () {
        // prevent duplicate form submissions
        $(this).find(":submit").attr('disabled', 'disabled');
    });
    </script>
@endpush
