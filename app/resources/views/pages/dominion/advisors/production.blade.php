@extends('layouts.master')

@section('page-header', 'Production Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="row">

        <div class="col-md-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-industry"></i> Production Summary</h3>
                </div>
                <div class="box-body no-padding">
                    <div class="row">

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Production /hr</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Platinum:</td>
                                        <td>
                                            @if ($platinumProduction = $productionCalculator->getPlatinumProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($platinumProduction) }}</span>
                                            @else
                                                0
                                            @endif

                                            <small class="text-muted">({{ number_format(($productionCalculator->getPlatinumProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Food:</td>
                                        <td>
                                            @if ($foodProduction = $productionCalculator->getFoodProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($foodProduction) }}</span>
                                            @else
                                                0
                                            @endif

                                            <small class="text-muted">({{ number_format(($productionCalculator->getFoodProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lumber:</td>
                                        <td>
                                            @if ($lumberProduction = $productionCalculator->getLumberProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($lumberProduction) }}</span>
                                            @else
                                                0
                                            @endif

                                            <small class="text-muted">({{ number_format(($productionCalculator->getLumberProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mana:</td>
                                        <td>
                                            @if ($manaProduction = $productionCalculator->getManaProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($manaProduction) }}</span>
                                            @else
                                                0
                                            @endif

                                            <small class="text-muted">({{ number_format(($productionCalculator->getManaProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Ore:</td>
                                        <td>
                                            @if ($oreProduction = $productionCalculator->getOreProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($oreProduction) }}</span>
                                            @else
                                                0
                                            @endif

                                            <small class="text-muted">({{ number_format(($productionCalculator->getOreProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Gems:</td>
                                        <td>
                                            @if ($gemProduction = $productionCalculator->getGemProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($gemProduction) }}</span>
                                            @else
                                                0
                                            @endif

                                            <small class="text-muted">({{ number_format(($productionCalculator->getGemProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Experience points:</td>
                                        <td>
                                            @if ($techProduction = $productionCalculator->getTechProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($techProduction) }}</span>
                                            @else
                                                0
                                            @endif

                                            <small class="text-muted">({{ number_format(($productionCalculator->getTechProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Boats:</td>
                                        <td>
                                            @if ($boatProduction = $productionCalculator->getBoatProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($boatProduction, 2) }}</span>
                                            @else
                                                0
                                            @endif

                                            <small class="text-muted">({{ number_format(($productionCalculator->getBoatProductionMultiplier($selectedDominion)-1) * 100,2) }}%)</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Consumption /hr</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Food Eaten:</td>
                                        <td>
                                            @if ($foodConsumption = $productionCalculator->getFoodConsumption($selectedDominion))
                                                <span class="text-red">-{{ number_format($foodConsumption) }}</span>
                                            @else
                                                <span class="text-green">+0</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Food Decayed:</td>
                                        <td>
                                            @if ($foodDecay = $productionCalculator->getFoodDecay($selectedDominion))
                                                <span class="text-red">-{{ number_format($foodDecay) }}</span>
                                            @else
                                                <span class="text-green">+0</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lumber Rotted:</td>
                                        <td>
                                            @if ($lumberDecay = $productionCalculator->getLumberDecay($selectedDominion))
                                                <span class="text-red">-{{ number_format($lumberDecay) }}</span>
                                            @else
                                                <span class="text-green">+0</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mana Drain:</td>
                                        <td>
                                            @if ($manaDecay = $productionCalculator->getManaDecay($selectedDominion))
                                                <span class="text-red">-{{ number_format($manaDecay) }}</span>
                                            @else
                                                <span class="text-green">+0</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Net Change /hr</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="hidden-xs">
                                        <td colspan="2">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td>Food:</td>
                                        <td>
                                            @if (($foodNetChange = $productionCalculator->getFoodNetChange($selectedDominion)) < 0)
                                                <span class="text-red">{{ number_format($foodNetChange) }}</span>
                                            @else
                                                <span class="text-green">+{{ number_format($foodNetChange) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lumber:</td>
                                        <td>
                                            @if (($lumberNetChange = $productionCalculator->getLumberNetChange($selectedDominion)) < 0)
                                                <span class="text-red">{{ number_format($lumberNetChange) }}</span>
                                            @else
                                                <span class="text-green">+{{ number_format($lumberNetChange) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mana:</td>
                                        <td>
                                            @if (($manaNetChange = $productionCalculator->getManaNetChange($selectedDominion)) < 0)
                                                <span class="text-red">{{ number_format($manaNetChange) }}</span>
                                            @else
                                                <span class="text-green">+{{ number_format($manaNetChange) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
                </div>
            </div>

            <!-- Information Box: Production Summary -->

            <div class="col-md-12 col-md-3">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Information</h3>
                    </div>
                    <div class="box-body">
                        <p>The production advisor tells you about your resource production, population and jobs.</p>
                        <p>
                          @if ($selectedDominion->race->name == 'Growth')
                            <b>Growth</b><br>
                            Total: {{ number_format($populationCalculator->getPopulation($selectedDominion)) }} / {{ number_format($populationCalculator->getMaxPopulation($selectedDominion)) }}<br>
                            Cells: {{ number_format($selectedDominion->peasants) }} / {{ number_format($populationCalculator->getMaxPopulation($selectedDominion) - $populationCalculator->getPopulationMilitary($selectedDominion)) }}
                            @if ($selectedDominion->peasants_last_hour < 0)
                                <span class="text-red">(<b>{{ number_format($selectedDominion->peasants_last_hour) }}</b> last tick)</span>
                            @elseif ($selectedDominion->peasants_last_hour > 0)
                                <span class="text-green">(<b>+{{ number_format($selectedDominion->peasants_last_hour) }}</b> last tick)</span>
                            @endif

                          @elseif ($selectedDominion->race->name == 'Myconid')
                            <b>Network</b><br>
                            Total: {{ number_format($populationCalculator->getPopulation($selectedDominion)) }} / {{ number_format($populationCalculator->getMaxPopulation($selectedDominion)) }}<br>
                            Spores: {{ number_format($selectedDominion->peasants) }} / {{ number_format($populationCalculator->getMaxPopulation($selectedDominion) - $populationCalculator->getPopulationMilitary($selectedDominion)) }}
                            @if ($selectedDominion->peasants_last_hour < 0)
                                <span class="text-red">(<b>{{ number_format($selectedDominion->peasants_last_hour) }}</b> last tick)</span>
                            @elseif ($selectedDominion->peasants_last_hour > 0)
                                <span class="text-green">(<b>+{{ number_format($selectedDominion->peasants_last_hour) }}</b> last tick)</span>
                            @endif

                          @elseif ($selectedDominion->race->name == 'Swarm')
                            <b>Swarm</b><br>
                            Total: {{ number_format($populationCalculator->getPopulation($selectedDominion)) }} / {{ number_format($populationCalculator->getMaxPopulation($selectedDominion)) }}<br>
                            Larvae: {{ number_format($selectedDominion->peasants) }} / {{ number_format($populationCalculator->getMaxPopulation($selectedDominion) - $populationCalculator->getPopulationMilitary($selectedDominion)) }}
                            @if ($selectedDominion->peasants_last_hour < 0)
                                <span class="text-red">(<b>{{ number_format($selectedDominion->peasants_last_hour) }}</b> last tick)</span>
                            @elseif ($selectedDominion->peasants_last_hour > 0)
                                <span class="text-green">(<b>+{{ number_format($selectedDominion->peasants_last_hour) }}</b> last tick)</span>
                            @endif

                          @else
                              <b>Population</b>
                              <table class="table">
                                  <colgroup>
                                      <col width="50%">
                                      <col width="50%">
                                  </colgroup>
                                <tbody>
                                  </tr>
                                    <td>Population max:</td>
                                    <td>{{ number_format($populationCalculator->getMaxPopulation($selectedDominion)) }}</td>
                                  </tr>
                                  <tr>
                                    <td>Population current:</td>
                                    <td>{{ number_format($populationCalculator->getPopulation($selectedDominion)) }}</td>
                                  </tr>
                                  <tr>
                                    <td>Peasants max:</td>
                                    <td>{{ number_format($populationCalculator->getMaxPopulation($selectedDominion) - $populationCalculator->getPopulationMilitary($selectedDominion)) }}</td>
                                  </tr>
                                  <tr>
                                    <td>Peasants current:</td>
                                    <td>{{ number_format($selectedDominion->peasants) }}</td>
                                  </tr>
                                  <tr>
                                    <td>Peasants change:</td>
                                    <td>
                                        @if ($selectedDominion->peasants_last_hour < 0)
                                            <span class="text-red">{{ number_format($selectedDominion->peasants_last_hour) }} last tick)</span>
                                        @elseif ($selectedDominion->peasants_last_hour > 0)
                                            <span class="text-green">+{{ number_format($selectedDominion->peasants_last_hour) }} last tick</span>
                                        @endif
                                    </td>
                                  </tr>
                                  <tr>
                                    <td>Military:</td>
                                    <td>{{ number_format($populationCalculator->getPopulationMilitary($selectedDominion)) }}</td>
                                  </tr>
                                </tbody>
                              </table>

                              <b>Jobs</b>
                              <table class="table">
                                  <colgroup>
                                      <col width="50%">
                                      <col width="50%">
                                  </colgroup>
                                <tbody>
                                  </tr>
                                    <td>Jobs:</td>
                                    <td>{{ number_format($populationCalculator->getEmploymentJobs($selectedDominion)) }}</td>
                                  </tr>
                                  <tr>
                                    <td>Filled:</td>
                                    <td>{{ number_format($populationCalculator->getPopulationEmployed($selectedDominion)) }}</td>
                                  </tr>
                                  @php($jobsNeeded = ($selectedDominion->peasants - $populationCalculator->getEmploymentJobs($selectedDominion)))
                                  @if ($jobsNeeded < 0)
                                  <tr>
                                    <td><span data-toggle="tooltip" data-placement="top" title="How many new jobs need to be created to provide employment for all currently unemployed peasants">Jobs available:</span></td>
                                    <td>{{ number_format(abs($jobsNeeded)) }}</td>
                                  </tr>
                                  @else
                                  <tr>
                                    <td><span data-toggle="tooltip" data-placement="top" title="How many peasants you need to fill all available jobs">Peasants needed:</span></td>
                                    <td>{{ number_format(abs($jobsNeeded)) }}</td>
                                  </tr>
                                  @endif
                                </tbody>
                              </table>

                              <br>


                              Total: {{ number_format($populationCalculator->getPopulation($selectedDominion)) }} / {{ number_format($populationCalculator->getMaxPopulation($selectedDominion)) }}<br>
                              Peasants: {{ number_format($selectedDominion->peasants) }} / {{ number_format($populationCalculator->getMaxPopulation($selectedDominion) - $populationCalculator->getPopulationMilitary($selectedDominion)) }}
                              @if ($selectedDominion->peasants_last_hour < 0)
                                  <span class="text-red">(<b>{{ number_format($selectedDominion->peasants_last_hour) }}</b> last tick)</span>
                              @elseif ($selectedDominion->peasants_last_hour > 0)
                                  <span class="text-green">(<b>+{{ number_format($selectedDominion->peasants_last_hour) }}</b> last tick)</span>
                              @endif
                              <br>
                              Military: {{ number_format($populationCalculator->getPopulationMilitary($selectedDominion)) }}<br>
                              <br>
                              <b>Jobs</b><br>
                              Fulfilled: {{ number_format($populationCalculator->getPopulationEmployed($selectedDominion)) }} / {{ number_format($populationCalculator->getEmploymentJobs($selectedDominion)) }}<br>
                              @php($jobsNeeded = ($selectedDominion->peasants - $populationCalculator->getEmploymentJobs($selectedDominion)))
                              @if ($jobsNeeded < 0)
                                  Available: {{ number_format(abs($jobsNeeded)) }}<br>
                                  Opportunity cost of job overrun: <b>{{ number_format(2.7 * abs($jobsNeeded) * $productionCalculator->getPlatinumProductionMultiplier($selectedDominion)) }} platinum</b><br>
                                  <br>
                                  <i>"You should acquire additional peasants, since you have idle jobs.<br><br>Employed peasants pay their income tax in platinum to the dominion." -Production Advisor</i>
                              @elseif ($jobsNeeded === 0)
                                  Available: 0<br>
                                  No opportunity cost
                              @else
                                  Needed: {{ number_format($jobsNeeded) }}<br>
                                  Opportunity cost of job underrun: <b>{{ number_format(2.7 * $jobsNeeded * $productionCalculator->getPlatinumProductionMultiplier($selectedDominion)) }} platinum</b><br>
                                  <br>
                                  <i>"You should construct additional job buildings, since you have idle peasants.<br><br>Only employed peasants pay their income tax in platinum to the dominion." -Production Advisor</i>
                              @endif
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- /Information Box: Production Summary -->

<!-- PRODUCTION DETAILS -->
<div class="col-md-12 col-md-9">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="ra ra-mining-diamonds"></i> Production Details</h3>
        </div>

            <div class="box-body no-padding">
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
                                      <th><span data-toggle="tooltip" data-placement="top" title="The maximum amount of the resource you can have stored">Max Storage</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much of max storage you are currently using">Storage %</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have produced this round">Total Produced</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have stolen this round">Total Stolen</span></th>


                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have spent on training military units">Spent on Training</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have spent on buildings">Spent on Buildings</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have spent on exploring">Spent on Exploring</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have spent on rezoning">Spent on Rezoning</span></th>
                                      <th><span data-toggle="tooltip" data-placement="top" title="How much you have spent on improvements">Spent on Improvements</span></th>
                                  </tr>
                            </thead>
                            <tbody>
                                  <tr>
                                      <td>Platinum</td>
                                      <td>{{ number_format($productionCalculator->getPlatinumProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getPlatinumProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getPlatinumProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($productionCalculator->getPlatinumProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($selectedDominion->resource_platinum) }}</td>
                                      <td>{{ number_format($productionCalculator->getMaxStorage($selectedDominion, 'platinum')) }}</td>
                                      <td>{{ number_format(($selectedDominion->resource_platinum/$productionCalculator->getMaxStorage($selectedDominion, 'platinum')) * 100, 2) }}%</td>
                                      <td>{{ number_format($selectedDominion->stat_total_platinum_production) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_platinum_stolen) }}</td>
                                  </tr>
                                  <tr>
                                      <td>Food</td>
                                      <td>{{ number_format($productionCalculator->getFoodProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getFoodProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getFoodProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td><span data-toggle="tooltip" data-placement="top" title="Food decay plus food consumption" class="text-red">-{{ number_format($productionCalculator->getFoodDecay($selectedDominion) + $productionCalculator->getFoodConsumption($selectedDominion)) }}</span></td>
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
                                      <td>&mdash;</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($selectedDominion->stat_total_food_production) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_food_stolen) }}</td>
                                  </tr>
                                  <tr>
                                      <td>Lumber</td>
                                      <td>{{ number_format($productionCalculator->getLumberProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getLumberProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getLumberProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td><span data-toggle="tooltip" data-placement="top" title="Lumber rot" class="text-red">-{{ number_format($productionCalculator->getLumberDecay($selectedDominion)) }}</span></td>
                                      <td>
                                          @if($productionCalculator->getLumberNetChange($selectedDominion) > 0)
                                              <span class="text-green">
                                          @else
                                              <span class="text-red">
                                          @endif
                                          {{ number_format($productionCalculator->getLumberNetChange($selectedDominion)) }}
                                          </span>
                                      </td>
                                      <td>{{ number_format($selectedDominion->resource_lumber) }}</td>
                                      <td>{{ number_format($productionCalculator->getMaxStorage($selectedDominion, 'lumber')) }}</td>
                                      <td>{{ number_format(($selectedDominion->resource_lumber/$productionCalculator->getMaxStorage($selectedDominion, 'lumber')) * 100, 2) }}%</td>
                                      <td>{{ number_format($selectedDominion->stat_total_lumber_production) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_lumber_stolen) }}</td>
                                  </tr>
                                  <tr>
                                      <td>Mana</td>
                                      <td>{{ number_format($productionCalculator->getManaProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getManaProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getManaProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td><span data-toggle="tooltip" data-placement="top" title="Lumber drain"  class="text-red">-{{ number_format($productionCalculator->getManaDecay($selectedDominion)) }}</span></td>
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
                                      <td>&mdash;</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($selectedDominion->stat_total_mana_production) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_mana_stolen) }}</td>
                                  </tr>
                                      <td>Ore</td>
                                      <td>{{ number_format($productionCalculator->getOreProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getOreProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getOreProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($productionCalculator->getOreProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($selectedDominion->resource_ore) }}</td>
                                      <td>{{ number_format($productionCalculator->getMaxStorage($selectedDominion, 'ore')) }}</td>
                                      <td>{{ number_format(($selectedDominion->resource_ore/$productionCalculator->getMaxStorage($selectedDominion, 'ore')) * 100, 2) }}%</td>
                                      <td>{{ number_format($selectedDominion->stat_total_ore_production) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_ore_stolen) }}</td>
                                  </tr>
                                  </tr>
                                      <td>Gems</td>
                                      <td>{{ number_format($productionCalculator->getGemProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getGemProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getGemProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($productionCalculator->getGemProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($selectedDominion->resource_gem) }}</td>
                                      <td>{{ number_format($productionCalculator->getMaxStorage($selectedDominion, 'gem')) }}</td>
                                      <td>{{ number_format(($selectedDominion->resource_gem/$productionCalculator->getMaxStorage($selectedDominion, 'gem')) * 100, 2) }}%</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gem_production) }}</td>
                                      <td>{{ number_format($selectedDominion->stat_total_gem_stolen) }}</td>
                                  </tr>
                                  </tr>
                                      <td>XP</td>
                                      <td>{{ number_format($productionCalculator->getTechProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getTechProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getTechProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($productionCalculator->getTechProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($selectedDominion->resource_tech) }}</td>
                                      <td>&mdash;</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($selectedDominion->stat_total_tech_production) }}</td>
                                      <td>&mdash;</td>
                                  </tr>
                                  </tr>
                                      <td>Boats</td>
                                      <td>{{ number_format($productionCalculator->getBoatProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($productionCalculator->getBoatProductionRaw($selectedDominion)) }}</td>
                                      <td>{{ number_format(($productionCalculator->getBoatProductionMultiplier($selectedDominion)-1)*100, 2) }}%</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($productionCalculator->getBoatProduction($selectedDominion)) }}</td>
                                      <td>{{ number_format($selectedDominion->resource_boats) }}</td>
                                      <td>&mdash;</td>
                                      <td>&mdash;</td>
                                      <td>{{ number_format($selectedDominion->stat_total_boat_production) }}</td>
                                      <td>&mdash;</td>
                                  </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
            </div>
        </div>


        <!-- /NEW -->


    </div>

@endsection
