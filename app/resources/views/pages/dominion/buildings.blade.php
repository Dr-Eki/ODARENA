@extends('layouts.master')
@section('title', 'Buildings')

@section('content')
@php
    $availableBuildings = $buildingHelper->getBuildingsByRace($selectedDominion->race)->sortBy('name');
@endphp

<div class="row">
    <div class="col-md-9">
        <form action="{{ route('dominion.buildings') }}" method="post" role="form">
        @csrf
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-home"></i> Buildings </h3>

                    <small class="pull-right text-muted">
                        <span data-toggle="tooltip" data-placement="top" title="
                        <small class='text-muted'>Buildings afforded:</small> {{ $constructionCalculator->getMaxAfford($selectedDominion) }}">Barren</span>: {{ $landCalculator->getTotalBarrenLand($selectedDominion) }}
                    </small>
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
                                            <input type="number" name="build[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $constructionCalculator->getMaxAfford($selectedDominion) }}" value="{{ old('build.' . $building->key) }}" {{ ($selectedDominion->isLocked() or !$constructionCalculator->canBuildBuilding($selectedDominion, $building)) ? 'disabled' : null }}>
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
                    <button type="submit" class="btn btn-primary pull-right" id="submit" {{ ($selectedDominion->race->getPerkValue('cannot_build') or $selectedDominion->race->getPerkValue('growth_cannot_build')) ? 'disabled' : '' }} >Begin Construction</button>
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

                <p>You have {{ number_format($landCalculator->getTotalBarrenLand($selectedDominion)) . ' ' . str_plural('acre', $landCalculator->getTotalBarrenLand($selectedDominion)) }} of barren land
                and can afford to construct <strong>{{ number_format($constructionCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('building', $constructionCalculator->getMaxAfford($selectedDominion)) }}</strong>.</p>
                <p>You may also <a href="{{ route('dominion.demolish') }}">demolish buildings</a> if you wish.</p>
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
                          <tr>
                              <td>
                                  <span data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                      {{ $building->name }}
                                  </span>
                              </td>
                              @for ($i = 1; $i <= 12; $i++)
                                  <td class="text-center">
                                      @if (($queueService->getConstructionQueueAmount($selectedDominion, "building_{$building->key}", $i) + $queueService->getRepairQueueAmount($selectedDominion, "building_{$building->key}", $i)) === 0)
                                          -
                                      @else
                                          {{ number_format(($queueService->getConstructionQueueAmount($selectedDominion, "building_{$building->key}", $i) + $queueService->getRepairQueueAmount($selectedDominion, "building_{$building->key}", $i))) }}
                                      @endif
                                  </td>
                              @endfor
                              <td class="text-center">{{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}") + $queueService->getRepairQueueTotalByResource($selectedDominion, "building_{$building->key}")) }}</td>
                          </tr>
                      @endforeach
                  </tbody>
              </table>
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
