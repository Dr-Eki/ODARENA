@extends('layouts.master')

{{--
@section('page-header', 'Statistics Advisor')
--}}

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="row">

        <div class="col-md-12 col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-chart-bar"></i> Statistics Advisor</h3>
                </div>
                <div class="box-body no-padding">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <div class="box-header with-border">
                                <h4 class="box-title"><i class="ra ra-sword ra-fw"></i> Military</h4>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Offensive Power</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Offensive Power:</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getOffensivePower($selectedDominion)) }}</strong>
                                            @if ($militaryCalculator->getOffensivePowerMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getOffensivePowerRaw($selectedDominion))) }} raw)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>OP Multiplier:</td>
                                        <td>
                                            <strong>{{ number_string(($militaryCalculator->getOffensivePowerMultiplier($selectedDominion) - 1) * 100, 3, true) }}%</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>OPA:</td>
                                        <td>
                                            <strong>{{ number_format(($militaryCalculator->getOffensivePower($selectedDominion) / $landCalculator->getTotalLand($selectedDominion)), 3) }}</strong>
                                            @if ($militaryCalculator->getOffensivePowerMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getOffensivePowerRaw($selectedDominion) / $landCalculator->getTotalLand($selectedDominion)), 3) }})</small>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                                <table class="table">
                                    <colgroup>
                                        <col width="50%">
                                        <col width="50%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th colspan="2">Defensive Power</th>
                                        </tr>
                                    </thead>
                                        <tr>
                                            <td>Defensive Power:</td>
                                            <td>
                                                <strong>{{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }}</strong>
                                                @if ($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) !== 1.0)
                                                    <small class="text-muted">({{ number_format(($militaryCalculator->getDefensivePowerRaw($selectedDominion))) }} raw)</small>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>DP Multiplier:</td>
                                            <td>
                                                <strong>{{ number_string(($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) - 1) * 100, 3, true) }}%</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>DPA:</td>
                                            <td>
                                                <strong>{{ number_format(($militaryCalculator->getDefensivePower($selectedDominion) / $landCalculator->getTotalLand($selectedDominion)), 3) }}</strong>
                                                @if ($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) !== 1.0)
                                                    <small class="text-muted">({{ number_format(($militaryCalculator->getDefensivePowerRaw($selectedDominion) / $landCalculator->getTotalLand($selectedDominion)), 3) }})</small>
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
                                <thead class="hidden-xs">
                                    <tr>
                                        <th colspan="2">Offensive Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Attacking victory:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_attacking_success) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Bottomfeeds:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_attacking_bottomfeeds) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tactical razes:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_attacking_razes) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Overwhelmed failures:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_attacking_failures) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Land conquered:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_land_conquered) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Land discovered:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_land_discovered) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead class="hidden-xs">
                                    <tr>
                                        <th colspan="2">Defensive Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Invasions fought back:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_defending_success) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Invasions lost:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_defending_failures) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Land lost:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_land_lost) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Land explored:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_land_explored) }}</strong>
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
                                <thead class="hidden-xs">
                                    <tr>
                                        <th colspan="2">Units</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Enemy units killed:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_units_killed) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Total units converted:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_units_converted) }}</strong>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>{{ str_plural($unitHelper->getUnitName('unit1', $selectedDominion->race)) }} trained:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_unit1_trained) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($unitHelper->getUnitName('unit1', $selectedDominion->race)) }} lost:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_unit1_lost) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($unitHelper->getUnitName('unit2', $selectedDominion->race)) }} trained:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_unit2_trained) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($unitHelper->getUnitName('unit2', $selectedDominion->race)) }} lost:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_unit2_lost) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($unitHelper->getUnitName('unit3', $selectedDominion->race)) }} trained:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_unit3_trained) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($unitHelper->getUnitName('unit3', $selectedDominion->race)) }} lost:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_unit3_lost) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($unitHelper->getUnitName('unit4', $selectedDominion->race)) }} trained:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_unit4_trained) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($unitHelper->getUnitName('unit4', $selectedDominion->race)) }} lost:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_unit4_lost) }}</strong>
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <div class="box-header with-border">
                                <h4 class="box-title"><i class="fa fa-user-secret fa-fw"></i> Espionage</h4>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Spy Power</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Offensive Spy Ratio:</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'offense'), 3) }}</strong>
                                            @if ($militaryCalculator->getSpyRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getSpyRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Defensive Spy Ratio:</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'defense'), 3) }}</strong>
                                            @if ($militaryCalculator->getSpyRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getSpyRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="Number of spies you have plus how many spies you have from units that count as spies in part or whole">Offensive Spy Points:</span>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getSpyPoints($selectedDominion, 'offense')) }}</strong>
                                            @if ($militaryCalculator->getSpyRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getSpyRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="Number of spies you have plus how many spies you have from units that count as spies in part or whole">Defensive Spy Points:</span>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getSpyPoints($selectedDominion, 'defense')) }}</strong>
                                            @if ($militaryCalculator->getSpyRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getSpyRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Espionage success:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_espionage_success) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Spies trained:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_spies_trained) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Spies lost:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_spies_lost) }}</strong>
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
                                        <th colspan="2">Offensive Operations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Draftees Assassinated:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_assassinate_draftees_damage) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Wizards Assassinated:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_assassinate_wizards_damage) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Snare Impact:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_magic_snare_damage) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Boats Sunk:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_sabotage_boats_damage) }}</strong>
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
                                        <th colspan="2">Theft</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Gold stolen:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_gold_stolen) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lumber stolen:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_lumber_stolen) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Food stolen:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_food_stolen) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mana stolen:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_mana_stolen) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Ore stolen:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_ore_stolen) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Gems stolen:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_gems_stolen) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <div class="box-header with-border">
                                <h4 class="box-title"><i class="ra ra-fairy-wand ra-fw"></i> Magic</h4>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Wizard Power</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Offensive Wizard Ratio:</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'offense'), 3) }}</strong>
                                            @if ($militaryCalculator->getWizardRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getWizardRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Defensive Wizard Ratio:</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'defense'), 3) }}</strong>
                                            @if ($militaryCalculator->getWizardRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getWizardRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="Number of wizards (archmages count as two) you have plus how many wizards you have from units that count as wizards in part or whole">Offensive Wizard Points:</span>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getWizardPoints($selectedDominion, 'offense')) }}</strong>
                                            @if ($militaryCalculator->getWizardRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getWizardRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                      <td>
                                          <span data-toggle="tooltip" data-placement="top" title="Number of wizards (archmages count as two) you have plus how many wizards you have from units that count as wizards in part or whole">Defensive Wizard Points:</span>
                                      </td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getWizardPoints($selectedDominion, 'defense')) }}</strong>
                                            @if ($militaryCalculator->getWizardRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getWizardRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Magic Success:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_spell_success) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Wizards trained:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_wizards_trained) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Wizards lost:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_wizards_lost) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Archmages trained:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_archmages_trained) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Archmages lost:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_total_archmages_lost) }}</strong>
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
                                        <th colspan="2">Offensive Operations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Spies disbanded:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_disband_spies_damage) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Fireball damage:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_fireball_damage) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lightning bolt damage:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_lightning_bolt_damage) }}</strong>
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
                                        <th colspan="2">Offensive Operations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Earthquake duration:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_earthquake_hours) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Great Flood duration:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_great_flood_hours) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Insect Swarm duration:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_insect_swarm_hours) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Plague Ticks:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_plague_hours) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="box-header with-border">
                                        <h4 class="box-title"><i class="ra ra-double-team ra-fw"></i> Population</h4>
                                    </div>
                                </div>
                                <div class="col-xs-12">
                                    <table class="table">
                                        <colgroup>
                                            <col width="50%">
                                            <col width="50%">
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <th colspan="2">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Current Population:</td>
                                                <td>
                                                    <strong>{{ number_format($populationCalculator->getPopulation($selectedDominion)) }}</strong>
                                                </td>
                                            </tr>
                                            @if(!$selectedDominion->race->getPerkMultiplier('no_population'))
                                            <tr>
                                                <td>{{ str_plural($raceHelper->getPeasantsTerm($selectedDominion->race)) }}</td>
                                                <td>
                                                    <strong>{{ number_format($selectedDominion->peasants) }}</strong>
                                                    <small class="text-muted">({{ number_format((($selectedDominion->peasants / $populationCalculator->getPopulation($selectedDominion)) * 100), 2) }}%)</small>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Military Population:</td>
                                                <td>
                                                    <strong>{{ number_format($populationCalculator->getPopulationMilitary($selectedDominion)) }}</strong>
                                                    <small class="text-muted">({{ number_format((100 - ($selectedDominion->peasants / $populationCalculator->getPopulation($selectedDominion)) * 100), 2) }}%)</small>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Max Population:</td>
                                                <td>
                                                    <strong>{{ number_format($populationCalculator->getMaxPopulation($selectedDominion)) }}</strong>
                                                    @if ($populationCalculator->getMaxPopulationMultiplier($selectedDominion) !== 1.0)
                                                        <small class="text-muted">({{ number_format($populationCalculator->getMaxPopulationRaw($selectedDominion)) }} raw)</small>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td>Population Multiplier:</td>
                                                <td>
                                                    <strong>{{ number_string((($populationCalculator->getMaxPopulationMultiplier($selectedDominion) - 1) * 100), 3, true) }}%</strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

@endsection
