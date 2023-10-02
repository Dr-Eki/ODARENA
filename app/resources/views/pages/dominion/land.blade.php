@extends('layouts.master')
@section('title', 'Land')

@section('content')

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
                            @endphp
                            @foreach(OpenDominion\Models\Terrain::all()->sortBy('order') as $terrain)
                                @php
                                    $amount = $selectedDominion->{'terrain_' . $terrain->key};
                                @endphp
                                <tr>
                                    <td>{{ $terrain->name }}</td>
                                    <td class="text-center">
                                        {{ number_format($selectedDominion->{'terrain_' . $terrain->key}) }}
                                        <small class="text-muted">({{ number_format(($selectedDominion->{'terrain_' . $terrain->key} / $selectedDominion->land)*100,2) }}%)</small>
                                    </td>
                                    <td class="text-center">
                                        <input name="remove[{{ $terrain->key }}]" type="number"
                                            class="form-control text-center" placeholder="0" min="0"
                                            max="{{ $amount }}"
                                            value="{{ old('remove.' . $terrain->key) }}" {{ ($selectedDominion->isLocked() or !$amount) ? 'disabled' : null }}>
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
                                        <span data-toggle="tooltip" data-placement="top" title="<small class='text-muted'>Paid:</small> {{  number_format($selectedDominion->{'terrain_' . $terrain->key} + $queueService->getInvasionQueueTotalByResource($selectedDominion, "terrain_{$terrain->key}") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "terrain_{$terrain->key}") + $queueService->getRezoningQueueTotalByResource($selectedDominion, "terrain_{$terrain->key}")) }}">
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
                                @php
                                    $totalLandIncoming = 0;
                                    $totalLandIncoming += $queueService->getInvasionQueueTotalByResource($selectedDominion, 'land');
                                    $totalLandIncoming += $queueService->getExpeditionQueueTotalByResource($selectedDominion, 'land');
                                @endphp
                                <td class="text-center"><em>{{ number_format($totalLandIncoming) }}</em></td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <h4>Land</h4>
                <p>
                    You have <strong>{{ number_format($selectedDominion->land) }}</strong> acres of land.
                    @if(($incomingLand = $queueService->getInvasionQueueTotalByResource($selectedDominion, 'land') + $queueService->getExpeditionQueueTotalByResource($selectedDominion, 'land')))
                        You also have <strong>{{ number_format($incomingLand) }}</strong> acres incoming.
                    @endif
                </p>

                <h4>Rezone</h4>
                <p>You can rezone from one terrain to another. 
                    @if($selectedDominion->protection_ticks == 96)
                        Rezoning is instant during the first tick of protection, thereafter it takes <strong>12 ticks</strong> to complete.
                    @else
                        Rezoning takes <strong>12 ticks</strong> to complete.
                    @endif
                Each acre costs <strong>{{ number_format($rezoningCalculator->getRezoningCost($selectedDominion)) }} {{ $rezoningCalculator->getRezoningMaterial($selectedDominion) }}</strong> to rezone.</p>
                <p>Terrain being rezoned does not provide any perks.</p>
                @if (1-$rezoningCalculator->getCostMultiplier($selectedDominion) !== 0)
                    <p>Your rezoning costs are
                    @if (1-$rezoningCalculator->getCostMultiplier($selectedDominion) > 0)
                        decreased
                    @else
                        increased
                    @endif
                    by <strong>{{ number_format((abs(1-$rezoningCalculator->getCostMultiplier($selectedDominion)))*100, 2) }}%</strong>.</p>
                @endif

                <p>You can afford to re-zone <b>{{ number_format($rezoningCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('acre', $rezoningCalculator->getMaxAfford($selectedDominion)) }}</b>.</p>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        @php
            $canClaim = (!$selectedDominion->isLocked() || !$selectedDominion->daily_land || $selectedDominion->protection_ticks > 0 || $selectedDominion->round->hasStarted());
        @endphp
        <div class="box {{ $canClaim ? 'box-warning' : null }}">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-plus"></i> Daily Bonus</h3>
            </div>
            <div class="box-body">
                <p>The Daily Land Bonus instantly gives you some land with <strong>{{ $selectedDominion->race->homeTerrain()->name }}</strong> terrain.</p>
                <p>You have a 0.50% chance to get 100 acres, and a 99.50% chance to get a random amount between 10 and 40 acres.</p>
                @if ($selectedDominion->protection_ticks > 0 or !$selectedDominion->round->hasStarted())
                <p><strong>You cannot claim daily bonus while you are in protection or before the round has started.</strong></p>
                @endif
                <form action="{{ route('dominion.land.daily-bonus') }}" method="post" role="form">
                    @csrf
                    <input type="hidden" name="action" value="daily_land">
                    <button type="submit" name="land" class="btn btn-primary btn-block btn-lg" {{ !$canClaim ? 'disabled' : null }}>
                        Claim Daily Land Bonus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-map fa-fw"></i> Terrain Perks</h3>
            </div>           
            <div class="box-body table-responsive no-padding">
                <table class="table">
                    <colgroup>
                        <col width="100">
                        <col width="100">
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Terrain</th>
                            <th>Current</th>
                            <th>Current Total</th>
                            <th>Base Perks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($selectedDominion->race->raceTerrains as $raceTerrain)
                            <tr>
                                <td>{{ $raceTerrain->terrain->name }}</td>
                                <td class="text-center">
                                    {{ number_format($selectedDominion->{'terrain_' . $raceTerrain->terrain->key}) }}
                                    <small class="text-muted">({{ number_format(($selectedDominion->{'terrain_' . $raceTerrain->terrain->key} / $selectedDominion->land)*100,2) }}%)</small>
                                </td>
                                <td>
                                    @if($raceTerrain->perks->count())
                                        @foreach($raceTerrain->perks as $perk)
                                            @php
                                                $perkValue = $selectedDominion->getTerrainPerkValue($perk->key);
                                                if($terrainHelper->getPerkType($perk->key) == 'mod')
                                                {
                                                    $perkValue /= 10;
                                                }
                                            @endphp
                                            {!! $terrainHelper->getPerkDescription($perk->key, $perkValue, false) !!}
                                            <br>
                                        @endforeach
                                    @else
                                        <em class="text-muted">None</em>
                                    @endif
                                </td>
                                <td>
                                    @if($raceTerrain->perks->count())
                                        @foreach($raceTerrain->perks as $perk)
                                            {!! $terrainHelper->getPerkDescription($perk->key, $perk->pivot->value, true) !!}
                                            <br>
                                        @endforeach
                                    @else
                                        <em class="text-muted">None</em>
                                    @endif
                                </td>
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
                <p>If you have the same perk from multiple terrains, the sum of the perks will be used and will be shown here.</p>
                <p>Terrain being rezoned does not provide any perks.</p>
            </div>
        </div>
    </div>
</div>


@endsection

@push('page-scripts')
    <script type="text/javascript">
    $("form").submit(function () {
        // prevent duplicate form submissions
        $(this).find(":submit").attr('disabled', 'disabled');
    });
    </script>
@endpush
