@extends('layouts.master')
{{--
@section('page-header', 'Production Advisor')
--}}

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
                                      <th><span data-toggle="tooltip" data-placement="top" title="Raw production per tick (not including modifiers)">Raw/tick</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="Modifier for production of this resource (includes morale modifier)">Modifier</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much (if any) is lost of this resource per tick in upkeep">Loss/tick</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="Net change per tick">Net/tick</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you currently have">Current</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have produced this round">Total Produced</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have stolen this round">Total Stolen</span></th>
                                  </tr>
                            </thead>
                            <tbody>
                                  <tr>
                                      <td>Gold</td>
                                      <td>{{ number_format($productionCalculator->getGoldProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getGoldProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getGoldProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($productionCalculator->getGoldProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($selectedDominion->resource_gold) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gold_production) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gold_stolen) }}</td>
                                  </tr>
                                  <tr>
                                      <td>Food</td>
                                      <td>{{ number_format($productionCalculator->getFoodProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getFoodProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getFoodProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td><span data-toggle="tooltip" data-placement="top" title="Food consumption" class="text-red">-{{ number_format($productionCalculator->getFoodConsumption($selectedDominion)) }}</span></td>
                                      <td>
                                          @if($productionCalculator->getFoodNetChange($selectedDominion) > 0)
                                              <span class="text-green">
                                          @else
                                              <span class="text-red">
                                          @endif
                                          {{ number_format($productionCalculator->getFoodNetChange($selectedDominion)) }}
                                          </span>
                                      </td>
                                      <td>{{ number_format($selectedDominion->resource_food) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_food_production) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_food_stolen) }}</td>
                                  </tr>
                                  <tr>
                                      <td>Lumber</td>
                                      <td>{{ number_format($productionCalculator->getLumberProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getLumberProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getLumberProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td>&mdash;</td>
                                      <td>
                                          @if($productionCalculator->getLumberProduction($selectedDominion) > 0)
                                              <span class="text-green">
                                          @else
                                              <span class="text-red">
                                          @endif
                                          {{ number_format($productionCalculator->getLumberProduction($selectedDominion)) }}
                                          </span>
                                      </td>
                                      <td>{{ number_format($selectedDominion->resource_lumber) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_lumber_production) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_lumber_stolen) }}</td>
                                  </tr>
                                  <tr>
                                      <td>Mana</td>
                                      <td>{{ number_format($productionCalculator->getManaProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getManaProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getManaProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td>
                                            @if($productionCalculator->getContribution($selectedDominion, 'mana'))
                                                <span class="text-red">-{{ number_format(($productionCalculator->getContribution($selectedDominion, 'mana'))) }}</span>
                                            @else
                                            &mdash;
                                            @endif
                                      </td>
                                      <td>
                                          @if($productionCalculator->getManaNetChange($selectedDominion) > 0)
                                              <span class="text-green">
                                          @else
                                              <span class="text-red">
                                          @endif
                                          {{ number_format($productionCalculator->getManaNetChange($selectedDominion)) }}
                                          </span>
                                      </td>
                                      <td>{{ number_format($selectedDominion->resource_mana) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_mana_production) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_mana_stolen) }}</td>
                                  </tr>
                                  <tr>
                                      <td>Ore</td>
                                      <td>{{ number_format($productionCalculator->getOreProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getOreProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getOreProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($productionCalculator->getOreProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($selectedDominion->resource_ore) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_ore_production) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_ore_stolen) }}</td>
                                  </tr>
                                  <tr>
                                      <td>Gems</td>
                                      <td>{{ number_format($productionCalculator->getGemProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getGemProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getGemProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($productionCalculator->getGemProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($selectedDominion->resource_gems) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gem_production) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gem_stolen) }}</td>
                                  </tr>
                                  <tr>
                                      <td>XP</td>
                                      <td>{{ number_format($productionCalculator->getTechProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getTechProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getTechProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($productionCalculator->getTechProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($selectedDominion->resource_tech) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_tech_production) }}</td>
                                      <td>&mdash;</td>
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
                                <col>
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
                                  <tr>
                                      <td>Gold</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gold_spent_training) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gold_spent_building) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gold_spent_rezoning) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gold_spent_exploring) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gold_spent_improving) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gold_bought) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gold_sold) }}</td>
                                  </tr>
                                  <tr>
                                      <td>Food</td>
                                      <td>{{ number_format($selectedDominion->stat_total_food_spent_training) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_food_spent_building) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_food_spent_rezoning) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_food_spent_exploring) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_food_spent_improving) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_food_bought) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_food_sold) }}</td>
                                  </tr>
                                  <tr>
                                      <td>Lumber</td>
                                      <td>{{ number_format($selectedDominion->stat_total_lumber_spent_training) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_lumber_spent_building) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_lumber_spent_rezoning) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_lumber_spent_exploring) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_lumber_spent_improving) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_lumber_bought) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_lumber_sold) }}</td>
                                  </tr>
                                  <tr>
                                      <td>Mana</td>
                                      <td>{{ number_format($selectedDominion->stat_total_mana_spent_training) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_mana_spent_building) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_mana_spent_rezoning) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_mana_spent_exploring) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_mana_spent_improving) }}</td>
                                      <td>&mdash;</td>
                                      <td>&mdash;</td>
                                  </tr>
                                  <tr>
                                      <td>Ore</td>
                                      <td>{{ number_format($selectedDominion->stat_total_ore_spent_training) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_ore_spent_building) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_ore_spent_rezoning) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_ore_spent_exploring) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_ore_spent_improving) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_ore_bought) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_ore_sold) }}</td>
                                  </tr>
                                  <tr>
                                      <td>Gems</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gem_spent_training) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gem_spent_building) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gem_spent_rezoning) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gem_spent_exploring) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gem_spent_improving) }}</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gem_sold) }}</td>
                                  </tr>
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
                                <col>
                                <col width="15%">
                                <col width="15%">
                                <col width="15%">
                                <col width="15%">
                                <col width="15%">
                            </colgroup>
                            <thead>
                                  <tr>
                                      <th>Resource</th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much food has decayed, lumber has rot, and mana has been drained">Decay, Rot, or Drain</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much food your population has consumed">Consumed</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much mana you have spent on casting spells">Cast</span></th>
                                  </tr>
                            </thead>
                            <tbody>
                                  <tr>
                                      <td>Food</td>
                                      <td>{{ number_format($selectedDominion->stat_total_food_decayed) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_food_consumed) }}</td>
                                      <td>&mdash;</td>
                                  </tr>
                                  <tr>
                                      <td>Lumber</td>
                                      <td>{{ number_format($selectedDominion->stat_total_lumber_rotted) }}</td>
                                      <td>&mdash;</td>
                                      <td>&mdash;</td>
                                  </tr>
                                  <tr>
                                      <td>Mana</td>
                                      <td>{{ number_format($selectedDominion->stat_total_mana_drained) }}</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($selectedDominion->stat_total_mana_cast) }}</td>
                                  </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </div>
</div>
<!-- /MAINTENANCE DETAILS -->




@endsection
