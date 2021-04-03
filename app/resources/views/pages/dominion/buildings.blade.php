@extends('layouts.master')

@section('content')
@php
    $availableBuildings = $buildingHelper->getBuildingsByRace($selectedDominion->race)->sortBy('name');
    $dominionBuildings = $buildingCalculator->getDominionBuildings($selectedDominion)->sortBy('name');
@endphp

<form action="{{ route('dominion.buildings') }}" method="post" role="form">
@csrf


<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-home"></i> Buildings </h3>
                <small class="pull-right text-muted">
                    <span data-toggle="tooltip" data-placement="top" title="How many buildings you can afford to explore right now">Max buildable</span>: {{ number_format($constructionCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('acre', $constructionCalculator->getMaxAfford($selectedDominion)) }}
                </small>
            </div>

            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <div class="">
                        <div class="box-header with-border">
                            <h3 class="box-title">{!! $landHelper->getLandTypeIconHtml('plain') !!}  Plains</h3>
                            <span class="pull-right barren-land">Barren: <strong>{{ number_format($landCalculator->getTotalBarrenLandByLandType($selectedDominion, 'plain')) }}</strong></span>
                        </div>

                        <div class="box-body table-responsive no-padding">
                            <table class="table">
                                <colgroup>
                                    <col>
                                    <col width="100">
                                    <col width="150">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Building</th>
                                        <th class="text-center">Owned<br>(Constructing)</th>
                                        <th class="text-center">Build</th>
                                    </tr>
                                </thead>
                                <tbody>
                                      @foreach($availableBuildings->where('land_type','plain') as $building)
                                          <tr>
                                              <td>
                                                  <span data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                                      {{ $building->name }}
                                                  </span>
                                              </td>
                                              <td class="text-center">
                                                    @if($buildingCalculator->getBuildingAmountOwned($selectedDominion, $building))
                                                        {{ $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building) }}
                                                        <small class="text-muted">({{ number_format(($dominionBuildings->where('building_id', $building->id)->first()->owned / $landCalculator->getTotalLand($selectedDominion))*100,2) }}%)</small>
                                                    @else
                                                        0 <small class="text-muted">(0%)</small>
                                                    @endif

                                                    @if($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}"))
                                                        <br>({{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}")) }})
                                                    @endif
                                              </td>
                                              <td class="text-center"><input type="number" name="build[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $constructionCalculator->getMaxAfford($selectedDominion) }}" value="{{ old('build.' . $building->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}></td>
                                          </tr>
                                      @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="">
                        <div class="box-header with-border">
                            <h3 class="box-title">{!! $landHelper->getLandTypeIconHtml('forest') !!}  Forest</h3>
                            <span class="pull-right barren-land">Barren: <strong>{{ number_format($landCalculator->getTotalBarrenLandByLandType($selectedDominion, 'forest')) }}</strong></span>
                        </div>

                        <div class="box-body table-responsive no-padding">
                            <table class="table">
                                <colgroup>
                                    <col>
                                    <col width="100">
                                    <col width="150">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Building</th>
                                        <th class="text-center">Owned<br>(Constructing)</th>
                                        <th class="text-center">Build</th>
                                    </tr>
                                </thead>
                                <tbody>
                                      @foreach($availableBuildings->where('land_type','forest') as $building)
                                          <tr>
                                              <td>
                                                  <span data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                                      {{ $building->name }}
                                                  </span>
                                              </td>
                                              <td class="text-center">
                                                    @if($buildingCalculator->getBuildingAmountOwned($selectedDominion, $building))
                                                        {{ $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building) }}
                                                        <small class="text-muted">({{ number_format(($dominionBuildings->where('building_id', $building->id)->first()->owned / $landCalculator->getTotalLand($selectedDominion))*100,2) }}%)</small>
                                                    @else
                                                        0 <small class="text-muted">(0%)</small>
                                                    @endif

                                                    @if($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}"))
                                                        <br>({{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}")) }})
                                                    @endif
                                              </td>
                                              <td class="text-center"><input type="number" name="build[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $constructionCalculator->getMaxAfford($selectedDominion) }}" value="{{ old('build.' . $building->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}></td>
                                          </tr>
                                      @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <div class="">
                        <div class="box-header with-border">
                            <h3 class="box-title">{!! $landHelper->getLandTypeIconHtml('mountain') !!} Mountains</h3>
                            <span class="pull-right barren-land">Barren: <strong>{{ number_format($landCalculator->getTotalBarrenLandByLandType($selectedDominion, 'mountain')) }}</strong></span>
                        </div>

                        <div class="box-body table-responsive no-padding">
                            <table class="table">
                                <colgroup>
                                    <col>
                                    <col width="100">
                                    <col width="150">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Building</th>
                                        <th class="text-center">Owned<br>(Constructing)</th>
                                        <th class="text-center">Build</th>
                                    </tr>
                                </thead>
                                <tbody>
                                      @foreach($availableBuildings->where('land_type','mountain') as $building)
                                          <tr>
                                              <td>
                                                  <span data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                                      {{ $building->name }}
                                                  </span>
                                              </td>
                                              <td class="text-center">
                                                    @if($buildingCalculator->getBuildingAmountOwned($selectedDominion, $building))
                                                        {{ $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building) }}
                                                        <small class="text-muted">({{ number_format(($dominionBuildings->where('building_id', $building->id)->first()->owned / $landCalculator->getTotalLand($selectedDominion))*100,2) }}%)</small>
                                                    @else
                                                        0 <small class="text-muted">(0%)</small>
                                                    @endif

                                                    @if($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}"))
                                                        <br>({{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}")) }})
                                                    @endif
                                              </td>
                                              <td class="text-center"><input type="number" name="build[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $constructionCalculator->getMaxAfford($selectedDominion) }}" value="{{ old('build.' . $building->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}></td>
                                          </tr>
                                      @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="">
                        <div class="box-header with-border">
                            <h3 class="box-title">{!! $landHelper->getLandTypeIconHtml('hill') !!}  Hills</h3>
                            <span class="pull-right barren-land">Barren: <strong>{{ number_format($landCalculator->getTotalBarrenLandByLandType($selectedDominion, 'hill')) }}</strong></span>
                        </div>

                        <div class="box-body table-responsive no-padding">
                            <table class="table">
                                <colgroup>
                                    <col>
                                    <col width="100">
                                    <col width="150">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Building</th>
                                        <th class="text-center">Owned<br>(Constructing)</th>
                                        <th class="text-center">Build</th>
                                    </tr>
                                </thead>
                                <tbody>
                                      @foreach($availableBuildings->where('land_type','hill') as $building)
                                          <tr>
                                              <td>
                                                  <span data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                                      {{ $building->name }}
                                                  </span>
                                              </td>
                                              <td class="text-center">
                                                    @if($buildingCalculator->getBuildingAmountOwned($selectedDominion, $building))
                                                        {{ $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building) }}
                                                        <small class="text-muted">({{ number_format(($dominionBuildings->where('building_id', $building->id)->first()->owned / $landCalculator->getTotalLand($selectedDominion))*100,2) }}%)</small>
                                                    @else
                                                        0 <small class="text-muted">(0%)</small>
                                                    @endif

                                                    @if($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}"))
                                                        <br>({{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}")) }})
                                                    @endif
                                              </td>
                                              <td class="text-center"><input type="number" name="build[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $constructionCalculator->getMaxAfford($selectedDominion) }}" value="{{ old('build.' . $building->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}></td>
                                          </tr>
                                      @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <div class="">
                        <div class="box-header with-border">
                            <h3 class="box-title">{!! $landHelper->getLandTypeIconHtml('swamp') !!}  Swamp</h3>
                            <span class="pull-right barren-land">Barren: <strong>{{ number_format($landCalculator->getTotalBarrenLandByLandType($selectedDominion, 'swamp')) }}</strong></span>
                        </div>

                        <div class="box-body table-responsive no-padding">
                            <table class="table">
                                <colgroup>
                                    <col>
                                    <col width="100">
                                    <col width="150">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Building</th>
                                        <th class="text-center">Owned<br>(Constructing)</th>
                                        <th class="text-center">Build</th>
                                    </tr>
                                </thead>
                                <tbody>
                                      @foreach($availableBuildings->where('land_type','swamp') as $building)
                                          <tr>
                                              <td>
                                                  <span data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                                      {{ $building->name }}
                                                  </span>
                                              </td>
                                              <td class="text-center">
                                                    @if($buildingCalculator->getBuildingAmountOwned($selectedDominion, $building))
                                                        {{ $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building) }}
                                                        <small class="text-muted">({{ number_format(($dominionBuildings->where('building_id', $building->id)->first()->owned / $landCalculator->getTotalLand($selectedDominion))*100,2) }}%)</small>
                                                    @else
                                                        0 <small class="text-muted">(0%)</small>
                                                    @endif

                                                    @if($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}"))
                                                        <br>({{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}")) }})
                                                    @endif
                                              </td>
                                              <td class="text-center"><input type="number" name="build[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $constructionCalculator->getMaxAfford($selectedDominion) }}" value="{{ old('build.' . $building->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}></td>
                                          </tr>
                                      @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="">
                        <div class="box-header with-border">
                            <h3 class="box-title">{!! $landHelper->getLandTypeIconHtml('water') !!}  Water</h3>
                            <span class="pull-right barren-land">Barren: <strong>{{ number_format($landCalculator->getTotalBarrenLandByLandType($selectedDominion, 'water')) }}</strong></span>
                        </div>

                        <div class="box-body table-responsive no-padding">
                            <table class="table">
                                <colgroup>
                                    <col>
                                    <col width="100">
                                    <col width="150">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Building</th>
                                        <th class="text-center">Owned<br>(Constructing)</th>
                                        <th class="text-center">Build</th>
                                    </tr>
                                </thead>
                                <tbody>
                                      @foreach($availableBuildings->where('land_type','water') as $building)
                                          <tr>
                                              <td>
                                                  <span data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                                      {{ $building->name }}
                                                  </span>
                                              </td>
                                              <td class="text-center">
                                                    @if($buildingCalculator->getBuildingAmountOwned($selectedDominion, $building))
                                                        {{ $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building) }}
                                                        <small class="text-muted">({{ number_format(($dominionBuildings->where('building_id', $building->id)->first()->owned / $landCalculator->getTotalLand($selectedDominion))*100,2) }}%)</small>
                                                    @else
                                                        0 <small class="text-muted">(0%)</small>
                                                    @endif

                                                    @if($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}"))
                                                        <br>({{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}")) }})
                                                    @endif
                                              </td>
                                              <td class="text-center"><input type="number" name="build[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $constructionCalculator->getMaxAfford($selectedDominion) }}" value="{{ old('build.' . $building->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}></td>
                                          </tr>
                                      @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="box-footer">
            <button type="submit" class="btn btn-primary pull-right">Build</button>
        </div>

    </div>
    </div>
    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">

                <p>Here you can construct buildings. Each building takes <b>12 ticks</b> to complete.</p>
                @php
                    $constructionMaterials = $raceHelper->getConstructionMaterials($selectedDominion->race);
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

                    @if($multiplier !== 1)
                        Your construction costs are
                        @if($multiplier > 1)
                            increased
                        @else
                            decreased
                        @endif
                        by <strong>{{ number_format(abs(($multiplier-1)*100),2) }}%</strong>.
                    @endif
                </p>

                <p>You have {{ number_format($landCalculator->getTotalBarrenLand($selectedDominion)) }} {{ str_plural('acre', $landCalculator->getTotalBarrenLand($selectedDominion)) }} of barren land
                  and can afford to construct <strong>{{ number_format($constructionCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('building', $constructionCalculator->getMaxAfford($selectedDominion)) }}</strong>.</p>
                <p>You may also <a href="{{ route('dominion.demolish') }}">demolish buildings</a> if you wish.</p>

                <a href="{{ route('scribes.buildings') }}"><span><i class="ra ra-scroll-unfurled"></i> Read more about Buildings in the Scribes.</span></a>
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
                                      @if ($queueService->getConstructionQueueAmount($selectedDominion, "building_{$building->key}", $i) === 0)
                                          -
                                      @else
                                          {{ number_format($queueService->getConstructionQueueAmount($selectedDominion, "building_{$building->key}", $i)) }}
                                      @endif
                                  </td>
                              @endfor
                              <td class="text-center">{{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}")) }}</td>
                          </tr>
                      @endforeach
                  </tbody>
              </table>
          </div>
      </div>
  </div>
</div>

@endsection
