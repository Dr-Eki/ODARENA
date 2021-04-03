@extends('layouts.master')

@section('content')
@php
    $availableBuildings = $buildingHelper->getBuildingsByRace($selectedDominion->race)->sortBy('name');
    $dominionBuildings = $buildingCalculator->getDominionBuildings($selectedDominion)->sortBy('name');
@endphp

<form action="{{ route('dominion.demolish') }}" method="post" role="form">
@csrf


<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="ra ra-groundbreaker"></i> Demolish Buildings</h3>
            </div>

            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <div class="">
                        <div class="box-header with-border">
                            <h3 class="box-title">{!! $landHelper->getLandTypeIconHtml('plain') !!}  Plains</h3>
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
                                        <th class="text-center">Demolish</th>
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
                                                    @if($dominionBuildings->contains('building_id', $building->id))
                                                        {{ $dominionBuildings->where('building_id', $building->id)->first()->owned }}
                                                        <small class="text-muted">({{ number_format(($dominionBuildings->where('building_id', $building->id)->first()->owned / $landCalculator->getTotalLand($selectedDominion))*100,2) }}%)</small>
                                                    @else
                                                        0 <small class="text-muted">(0%)</small>
                                                    @endif

                                                    @if($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}"))
                                                        <br>({{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}")) }})
                                                    @endif
                                              </td>
                                              <td class="text-center"><input type="number" name="demolish[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building) }}" value="{{ old('demolish.' . $building->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}></td>
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
                                        <th class="text-center">Demolish</th>
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
                                                    @if($dominionBuildings->contains('building_id', $building->id))
                                                        {{ $dominionBuildings->where('building_id', $building->id)->first()->owned }}
                                                        <small class="text-muted">({{ number_format(($dominionBuildings->where('building_id', $building->id)->first()->owned / $landCalculator->getTotalLand($selectedDominion))*100,2) }}%)</small>
                                                    @else
                                                        0 <small class="text-muted">(0%)</small>
                                                    @endif

                                                    @if($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}"))
                                                        <br>({{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}")) }})
                                                    @endif
                                              </td>
                                              <td class="text-center"><input type="number" name="demolish[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building) }}" value="{{ old('demolish.' . $building->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}></td>
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
                                        <th class="text-center">Demolish</th>
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
                                                    @if($dominionBuildings->contains('building_id', $building->id))
                                                        {{ $dominionBuildings->where('building_id', $building->id)->first()->owned }}
                                                        <small class="text-muted">({{ number_format(($dominionBuildings->where('building_id', $building->id)->first()->owned / $landCalculator->getTotalLand($selectedDominion))*100,2) }}%)</small>
                                                    @else
                                                        0 <small class="text-muted">(0%)</small>
                                                    @endif

                                                    @if($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}"))
                                                        <br>({{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}")) }})
                                                    @endif
                                              </td>
                                              <td class="text-center"><input type="number" name="demolish[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building) }}" value="{{ old('demolish.' . $building->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}></td>
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
                                        <th class="text-center">Demolish</th>
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
                                                    @if($dominionBuildings->contains('building_id', $building->id))
                                                        {{ $dominionBuildings->where('building_id', $building->id)->first()->owned }}
                                                        <small class="text-muted">({{ number_format(($dominionBuildings->where('building_id', $building->id)->first()->owned / $landCalculator->getTotalLand($selectedDominion))*100,2) }}%)</small>
                                                    @else
                                                        0 <small class="text-muted">(0%)</small>
                                                    @endif

                                                    @if($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}"))
                                                        <br>({{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}")) }})
                                                    @endif
                                              </td>
                                              <td class="text-center"><input type="number" name="demolish[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building) }}" value="{{ old('demolish.' . $building->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}></td>
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
                                        <th class="text-center">Demolish</th>
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
                                                    @if($dominionBuildings->contains('building_id', $building->id))
                                                        {{ $dominionBuildings->where('building_id', $building->id)->first()->owned }}
                                                        <small class="text-muted">({{ number_format(($dominionBuildings->where('building_id', $building->id)->first()->owned / $landCalculator->getTotalLand($selectedDominion))*100,2) }}%)</small>
                                                    @else
                                                        0 <small class="text-muted">(0%)</small>
                                                    @endif

                                                    @if($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}"))
                                                        <br>({{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}")) }})
                                                    @endif
                                              </td>
                                              <td class="text-center"><input type="number" name="demolish[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building) }}" value="{{ old('demolish.' . $building->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}></td>
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
                                        <th class="text-center">Demolish</th>
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
                                                    @if($dominionBuildings->contains('building_id', $building->id))
                                                        {{ $dominionBuildings->where('building_id', $building->id)->first()->owned }}
                                                        <small class="text-muted">({{ number_format(($dominionBuildings->where('building_id', $building->id)->first()->owned / $landCalculator->getTotalLand($selectedDominion))*100,2) }}%)</small>
                                                    @else
                                                        0 <small class="text-muted">(0%)</small>
                                                    @endif

                                                    @if($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}"))
                                                        <br>({{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$building->key}")) }})
                                                    @endif
                                              </td>
                                              <td class="text-center"><input type="number" name="demolish[building_{{ $building->key }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $buildingCalculator->getBuildingAmountOwned($selectedDominion, $building) }}" value="{{ old('demolish.' . $building->key) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}></td>
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
            <button type="submit" class="btn btn-danger" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Demolish</button>

            <span class="pull-right">
            <a href="{{ route('dominion.buildings') }}" class="btn btn-primary">Cancel</a>
            </span>
        </div>

    </div>
</div>
<div class="col-sm-12 col-md-3">
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Information</h3>
        </div>
        <div class="box-body">
            <p><b>Warning</b>: You are about to demolish buildings to reclaim barren land.</p>
            <p>Demolition is <b>instant and irrevocable</b>.</p>
        </div>
        <div class="box-footer">
        </div>
    </div>
</div>
</div>

@endsection
