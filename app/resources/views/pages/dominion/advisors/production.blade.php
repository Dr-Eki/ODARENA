@extends('layouts.master')
@section('title', 'Production Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="row">
<!-- PRODUCTION DETAILS -->
<div class="col-md-12 col-md-12">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="ra ra-mining-diamonds"></i> Production Details</h3>
        </div>

            <div class="box-body table-responsive no-padding">
                <div class="row">
                    <div class="col-xs-12 col-sm-12">
                        <table class="table">
                            <thead>
                                  <tr>
                                      <th>Resource</th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="Production per tick including modifiers">Production/tick</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="Modifier for production of this resource (includes morale modifier)">Modifier</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much (if any) is lost of this resource per tick in upkeep">Loss/tick</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="Net change per tick">Net/tick</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you currently have">Current</span></th>
                                  </tr>
                            </thead>
                            <tbody>
                                  @foreach($selectedDominion->race->resources as $resourceKey)
                                      @php
                                          $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                      @endphp

                                      <tr>
                                          <td>{{ $resource->name }}</td>
                                          <td>{{ number_format($resourceCalculator->getProduction($selectedDominion, $resourceKey)) }}</td>
                                          <td>{{ number_format(($resourceCalculator->getProductionMultiplier($selectedDominion, $resourceKey)-1)*100, 2) }}%</td>
                                          <td>{{ number_format($resourceCalculator->getConsumption($selectedDominion, $resourceKey)) }}</td>
                                          <td>{{ number_format($resourceCalculator->getProduction($selectedDominion, $resourceKey) - $resourceCalculator->getConsumption($selectedDominion, $resourceKey)) }}</td>
                                          <td>{{ number_format($selectedDominion->{'resource_' . $resourceKey}) }}</td>
                                  @endforeach
                                  <tr>
                                      <td>XP</td>
                                      <td>{{ number_format($productionCalculator->getXpGeneration($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getXpGenerationMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($productionCalculator->getXpGeneration($selectedDominion)) }}</td>
                                      <td>{{ number_format($selectedDominion->xp) }}</td>
                                  </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </div>
</div>
<!-- /PRODUCTION DETAILS -->


<!-- SPENDING DETAILS -->
<div class="col-md-12 col-md-12">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="ra ra-book"></i> Spending Details</h3>
        </div>

            <div class="box-body table-responsive no-padding">
                <div class="row">

                    <div class="col-xs-12 col-sm-12">
                        <table class="table">
                            <colgroup>
                                <col width="120">
                                <col width="12.5%">
                                <col width="12.5%">
                                <col width="12.5%">
                                <col width="12.5%">
                                <col width="12.5%">
                                <col width="12.5%">
                                <col width="12.5%">
                            </colgroup>
                            <thead>
                                  <tr>
                                      <th>Resource</th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have spent of this resource on training units (including spies, wizards, and arch mages)">Training</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have spent of this resource on buildings">Building</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have spent of this resource on rezoning land">Rezoning</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have spent of this resource on exploring land">Exploring</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have spent of this resource on improvements">Improvements</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much of this resource you have bought">Bought</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much of this resource you have sold">Sold</span></th>
                                  </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedDominion->race->resources as $resourceKey)
                                    @php
                                        $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                    @endphp

                                    <tr>
                                        <td>{{ $resource->name }}</td>
                                        <td>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_training'))) }}</td>
                                        <td>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_building'))) }}</td>
                                        <td>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_rezoning'))) }}</td>
                                        <td>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_exploring'))) }}</td>
                                        <td>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_improvements'))) }}</td>
                                        <td>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_bought'))) }}</td>
                                        <td>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_sold'))) }}</td>
                                    </tr>

                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </div>
</div>
<!-- /SPENDING DETAILS -->


<!-- MAINTENANCE DETAILS -->
<div class="col-md-12 col-md-12">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="ra ra-book"></i> Additional Details</h3>
        </div>

            <div class="box-body table-responsive no-padding">
                <div class="row">

                    <div class="col-xs-12 col-sm-12">
                        <table class="table">
                            <colgroup>
                                <col width="120">
                                <col width="15%">
                                <col width="15%">
                                <col width="15%">
                                <col width="15%">
                                <col width="15%">
                            </colgroup>
                            <thead>
                                  <tr>
                                      <th>Resource</th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="Salvaged from units lost in combat">Salvaged</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="Plundered from other dominions">Plundered</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="Stolen from other dominions">Stolen</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="Lost to theft or plunder">Lost</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="Mana spent on casting spells">Cast</span></th>
                                  </tr>
                            </thead>
                            @foreach($selectedDominion->race->resources as $resourceKey)
                                @php
                                    $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                @endphp

                                <tr>
                                    <td>{{ $resource->name }}</td>
                                    <td>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_salvaged'))) }}</td>
                                    <td>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_plundered'))) }}</td>
                                    <td>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_stolen'))) }}</td>
                                    <td>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_lost'))) }}</td>
                                    <td>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_cast'))) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
    </div>
</div>
<!-- /MAINTENANCE DETAILS -->




@endsection
