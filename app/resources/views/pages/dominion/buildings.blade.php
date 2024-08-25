@extends('layouts.master')
@section('title', 'Buildings')

@section('content')
<div class="row">
    <div class="col-md-9">
        <form action="{{ route('dominion.buildings') }}" method="post" role="form">
        @csrf
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-home"></i> Buildings </h3>

                    <small class="pull-right text-muted">
                        <span data-toggle="tooltip" data-placement="top" title="
                        <small class='text-muted'>Buildings afforded:</small> {{ number_format($constructionCalculator->getMaxAfford($selectedDominion)) }}">Barren</span>: {{ number_format($landCalculator->getTotalBarrenLand($selectedDominion)) }}
                    </small>
                </div>
                <div class="box-body">
                    @php
                        $numOfCols = 4;
                        $rowCount = 0;
                        $bootstrapColWidth = 12 / $numOfCols;
                    @endphp

                    @foreach($categories as $categoryKey => $categoryBuildings)
                        @php
                            if($availableBuildings->where('category', $categoryKey)->count() == 0)
                            {
                                continue;
                            }

                            $rowCount = 0;
                        @endphp
                        <div class="box-body">
                            <div class="box-header with-border">
                                <h4 class="box-title">{!! $buildingHelper->getBuildingCategoryIcon($categoryKey) !!} {{ ucwords($categoryKey) }} </h4>
                            </div>
                            <div class="row">
                                @foreach($availableBuildings->where('category', $categoryKey)->sortBy('name') as $building)
                                    @php
                                        $amountOwned = $selectedDominion->{'building_' . $building->key};
                                        $constructionAmount = $queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}");
                                        $constructionAmount += $queueService->getRepairQueueTotalByResource($selectedDominion, "building_{$building->key}");
                                        $constructionAmount += $queueService->getInvasionQueueTotalByResource($selectedDominion, "building_{$building->key}");
                                        $canBuildBuilding = $constructionCalculator->canBuildBuilding($selectedDominion, $building);
                                        $boxClass = '';
                                        $titleClass = '';

                                        $amountOwned ? $boxClass = 'box-primary' : '';
                                        $constructionAmount ? $boxClass = 'box-warning' : '';
                                        !$canBuildBuilding ? $boxClass = 'box-danger' : '';
                                        (($amountOwned + $constructionAmount) == 0) ? $titleClass = 'text-muted' : null;
                                    
                                    @endphp
                                    <div class="col-md-{{ $bootstrapColWidth }}">
                                        <div class="box {{ $boxClass }}">
                                            <div class="box-header with-border">
                                                <strong data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                                    <span class="{{ $titleClass }}">
                                                        {{ $building->name }}
                                                        @if(isset($building->deity))
                                                            <small class="pull-right" style="font-variant: small-caps;">
                                                                {{ $building->deity->name }}
                                                            </small>
                                                        @endif
                                                    </span>
                                                </strong>
                                            </div>

                                            <div class="box-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        @if($amountOwned)
                                                            {{ number_format($amountOwned) }}
                                                            <small class="text-muted">({{ number_format(($amountOwned / $selectedDominion->land)*100,2) }}%)</small>
                                                        @else
                                                            0 <small class="text-muted">(0%)</small>
                                                        @endif
                                                        
                                                        @if($constructionAmount)
                                                            <span data-toggle="tooltip" data-placement="top" title="<small class='text-muted'>Paid:</small> {{ number_format($constructionAmount + $amountOwned) }} <small>({{ number_format((($constructionAmount + $amountOwned) / $selectedDominion->land)*100,2) }}%)</small>">
                                                                <br>({{ number_format($constructionAmount) }})
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="col-md-8">                                                
                                                        <input type="number" name="build[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $constructionCalculator->getMaxAfford($selectedDominion) }}" value="{{ old('build.' . $building->key) }}" {{ ($selectedDominion->isLocked() or !$canBuildBuilding) ? 'disabled' : null }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @php
                                        $rowCount++;
                                    @endphp

                                    @if($rowCount % $numOfCols == 0)
                                        </div><div class="row">
                                    @endif

                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="box-footer">
                    <button type="submit" class="btn btn-primary pull-right" id="submit" {{ ($selectedDominion->race->getPerkValue('cannot_build')) ? 'disabled' : '' }} >Begin Construction</button>
                </div>
            </div>
        </form>
    </div>
        
    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">

                <p>Here you can construct buildings. Each building takes <b>{{ $constructionCalculator->getConstructionTicks($selectedDominion) }} ticks</b> to complete.</p>
                @php
                    $constructionMaterials = $selectedDominion->race->construction_materials;
                    $primaryCost = $constructionCalculator->getConstructionCostPrimary($selectedDominion);
                    $secondaryCost = $constructionCalculator->getConstructionCostSecondary($selectedDominion);
                    $multiplier = $constructionCalculator->getCostMultiplier($selectedDominion);

                    if(count($constructionMaterials) == 2)
                    {
                        $costString = 'Each building costs ' . number_format($primaryCost) . ' ' . $constructionMaterials[0] . ' and ' . number_format($secondaryCost) . ' ' . $constructionMaterials[1] . '.';
                    }
                    else
                    {
                        $costString = 'Each building costs ' . number_format($primaryCost) . ' ' . $constructionMaterials[0] . '.';
                    }

                @endphp

                <p>
                    {{ $costString }}

                    @if($multiplier != 1)
                        Your construction costs are
                        @if($multiplier > 1)
                            increased
                        @else
                            decreased
                        @endif
                        by <strong>{{ number_format(abs(($multiplier-1)*100),2) }}%</strong>.
                    @endif
                </p>

                <p>You have {{ number_format($landCalculator->getTotalBarrenLand($selectedDominion)) . ' ' . Str::plural('acre', $landCalculator->getTotalBarrenLand($selectedDominion)) }} of barren land
                and can afford to construct <strong>{{ number_format($constructionCalculator->getMaxAfford($selectedDominion)) }} {{ Str::plural('building', $constructionCalculator->getMaxAfford($selectedDominion)) }}</strong>.</p>
                <p>You may also <a href="{{ route('dominion.demolish') }}">demolish buildings</a> if you wish.</p>

                @if($moraleFromCastleRatioPerk = $selectedDominion->race->getPerkValue('morale_per_percentage_castle_buildings'))
                    @php
                        $castleAmount = $buildingCalculator->getBuildingCategoryAmount($selectedDominion, 'castle');
                        $castleRatio = $buildingCalculator->getBuildingCategoryRatio($selectedDominion, 'castle') * 100;
                    @endphp
                    <h4>Castle</h4>
                    <p>You have {{ number_format($castleAmount) }} ({{ number_format($castleRatio, 2) }}%) castle buildings, increasing your morale by {{ floor($castleRatio * $moraleFromCastleRatioPerk) }}. </p>
                @endif

                <h4>Holy Buildings</h4>
                <p>Holy buildings require devotion to a specific deity to build.</p>
                <p>If you are not devoted to the deity of a building, you do not gain any production, perks, or other effects from that building.</p>
                <p>Construction costs and times are the same as regular buildings.</p>
                <p>Land with holy buildings is considered holy land.</p>
                <p>You have {{ number_format($buildingCalculator->getBuildingCategoryAmount($selectedDominion, 'hole')) }} ({{ number_format($buildingCalculator->getBuildingCategoryRatio($selectedDominion, 'holy') * 100, 2) }}%) holy lands.</p>

            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-clock-o"></i> Incoming Buildings</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table">
                    <colgroup>
                        <col width="200">
                        @for ($i = 1; $i <= 12; $i++)
                            <col>
                        @endfor
                        <col width="100">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Building Type</th>
                            @for ($i = 1; $i <= 12; $i++)
                                <th class="text-center">{{ $i }}</th>
                            @endfor
                            <th class="text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($availableBuildings as $building)
                            @php
                                $totalConstructionAmount = $queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}");
                                $totalRepairAmount = $queueService->getRepairQueueTotalByResource($selectedDominion, "building_{$building->key}");
                                $totalInvasionAmount = $queueService->getInvasionQueueTotalByResource($selectedDominion, "building_{$building->key}");
                                $totalTotalAmount = $totalConstructionAmount + $totalRepairAmount + $totalInvasionAmount;
                            @endphp
                            <tr>
                                <td>
                                    <span data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                        {{ $building->name }}
                                    </span>
                                </td>
                                @for ($i = 1; $i <= 12; $i++)
                                    @php
                                        $constructionAmount = $queueService->getConstructionQueueAmount($selectedDominion, "building_{$building->key}", $i);
                                        $repairAmount = $queueService->getRepairQueueAmount($selectedDominion, "building_{$building->key}", $i);
                                        $invasionAmount = $queueService->getInvasionQueueAmount($selectedDominion, "building_{$building->key}", $i);
                                        $totalAmount = $constructionAmount + $repairAmount + $invasionAmount;
                                    @endphp
                                    <td class="text-center">
                                        @if ($totalAmount)
                                            {{ number_format($totalAmount) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endfor
                                <td class="text-center">{{ number_format($totalTotalAmount) }}</td>
                            </tr>
                        @endforeach
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
                <h3 class="box-title"><i class="fa fa-map fa-fw"></i> Land</h3>
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
                            <th class="text-center">Land</th>
                            @for ($i = 1; $i <= 12; $i++)
                                <th class="text-center">{{ $i }}</th>
                            @endfor
                            <th class="text-center">Total<br>Incoming</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">
                                {{ number_format($selectedDominion->land) }}
                            </td>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $landIncoming = (
                                        $queueService->getInvasionQueueAmount($selectedDominion, 'land', $i) +
                                        $queueService->getExpeditionQueueAmount($selectedDominion, 'land', $i) +
                                        $queueService->getRezoningQueueAmount($selectedDominion, 'land', $i)
                                    );
                                @endphp
                                <td class="text-center">
                                    @if ($landIncoming)
                                        {{ number_format($landIncoming) }}
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                            @endfor
                            <td class="text-center">
                                <span data-toggle="tooltip" data-placement="top" title="<small class='text-muted'>Paid:</small> {{  number_format($selectedDominion->land + $queueService->getInvasionQueueTotalByResource($selectedDominion, 'land') + $queueService->getExpeditionQueueTotalByResource($selectedDominion, 'land')) }}">
                                    {{ number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, 'land') + $queueService->getExpeditionQueueTotalByResource($selectedDominion, 'land')) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        @php
            $canClaim = (!$selectedDominion->isLocked() and !$selectedDominion->daily_land and $selectedDominion->protection_ticks === 0 and $selectedDominion->round->hasStarted());
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


@endsection
@push('page-scripts')
    <script type="text/javascript">
    $("form").submit(function () {
        // prevent duplicate form submissions
        $(this).find(":submit").attr('disabled', 'disabled');
    });
    </script>
@endpush
