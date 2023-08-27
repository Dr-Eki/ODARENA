@extends('layouts.master')
@section('title', 'Demolish')

@section('content')
@php
    $availableBuildings = $buildingCalculator->getDominionBuildingsAvailableAndOwned($selectedDominion)->sortBy('name');
    #$availableBuildings = $buildingHelper->getBuildingsByRace($selectedDominion->race)->sortBy('name');
    $dominionBuildings = $buildingCalculator->getDominionBuildings($selectedDominion)->sortBy('name');
@endphp

<div class="row">
    <div class="col-md-9">
        <form action="{{ route('dominion.demolish') }}" method="post" role="form">
        @csrf
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-groundbreaker"></i> Demolish Buildings </h3>
                </div>           
                <div class="box-body">
                    @php
                        $numOfCols = 4;
                        $rowCount = 0;
                        $bootstrapColWidth = 12 / $numOfCols;
                    @endphp
                    <div class="row">
                    @foreach($availableBuildings as $building)
                        @php
                            $amountOwned = $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building);
                            $constructionAmount = $queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}");
                            $boxClass = '';
                            $titleClass = '';

                            $amountOwned ? $boxClass = 'box-primary' : null;
                            $constructionAmount ? $boxClass = 'box-warning' : null;
                            (($amountOwned + $constructionAmount) == 0) ? $titleClass = 'text-muted' : null;
                        
                        @endphp
                        <div class="col-md-{{ $bootstrapColWidth }}">
                            <div class="box {{ $boxClass }}">
                                <div class="box-header with-border">
                                    <strong data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                        <span class="{{ $titleClass }}">
                                            {{ $building->name }}
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
                                            <input type="number" name="demolish[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building) }}" value="{{ old('demolish.' . $building->key) }}" {{ ($selectedDominion->isLocked() or !$amountOwned) ? 'disabled' : null }}>
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
                <div class="box-footer">
                    <button type="submit" class="btn btn-danger" {{ $selectedDominion->isLocked() ? 'disabled' : null }} id="submit">Demolish Buildings</button>
        
                    <span class="pull-right">
                        <a href="{{ route('dominion.buildings') }}" class="btn btn-primary">Cancel</a>
                    </span>
                </div>
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
                <p><span class="label label-danger">Warning</span> You are about to demolish buildings to reclaim barren land.</p>
                <p>Demolition is <b>instant and irrevocable</b>.</p>
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
