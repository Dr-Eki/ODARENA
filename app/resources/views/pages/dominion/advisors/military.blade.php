@extends('layouts.master')
@section('title', 'Military Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')

<div class="row">
    <div class="col-md-12 col-md-12">
        <div class="box box-primary">
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
                                    <td>Offensive Power</td>
                                    <td>
                                        <strong>{{ number_format($militaryCalculator->getOffensivePower($selectedDominion)) }}</strong>
                                        @if ($militaryCalculator->getOffensivePowerMultiplier($selectedDominion) !== 1.0)
                                            <small class="text-muted">({{ number_format(($militaryCalculator->getOffensivePowerRaw($selectedDominion))) }} raw)</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>OP Multiplier</td>
                                    <td>
                                        <strong>{{ number_string(($militaryCalculator->getOffensivePowerMultiplier($selectedDominion) - 1) * 100, 3, true) }}%</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>OPA</td>
                                    <td>
                                        <strong>{{ number_format(($militaryCalculator->getOffensivePower($selectedDominion) / $selectedDominion->land), 3) }}</strong>
                                        @if ($militaryCalculator->getOffensivePowerMultiplier($selectedDominion) !== 1.0)
                                            <small class="text-muted">({{ number_format(($militaryCalculator->getOffensivePowerRaw($selectedDominion) / $selectedDominion->land), 3) }})</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Max OP sent</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'op_sent_max')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Total OP sent</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'op_sent_total')) }}</strong>
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
                                        <td>Defensive Power</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }}</strong>
                                            @if ($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getDefensivePowerRaw($selectedDominion))) }} raw)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>DP Multiplier</td>
                                        <td>
                                            <strong>{{ number_string(($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) - 1) * 100, 3, true) }}%</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>DPA</td>
                                        <td>
                                            <strong>{{ number_format(($militaryCalculator->getDefensivePower($selectedDominion) / $selectedDominion->land), 3) }}</strong>
                                            @if ($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getDefensivePowerRaw($selectedDominion) / $selectedDominion->land), 3) }})</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Greatest successful DP</td>
                                        <td>
                                            <strong>{{ number_format($statsService->getStat($selectedDominion, 'dp_success_max')) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Greatest unsuccessful DP</td>
                                        <td>
                                            <strong>{{ number_format($statsService->getStat($selectedDominion, 'dp_failure_max')) }}</strong>
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
                                    <td>Attacking victory</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'invasion_victories')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Bottomfeeds</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'invasion_bottomfeeds')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Tactical razes</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'invasion_razes')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Overwhelmed failures</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'invasion_failures')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Land conquered</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'land_conquered')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Land discovered</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'land_discovered')) }}</strong>
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
                                    <td>Invasions fought back</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'defense_success')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Invasions lost</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'defense_failures')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Land lost</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'land_lost')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Land explored</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'land_explored')) }}</strong>
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
                                    <th colspan="2">Enemy Units</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Enemy units killed</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'units_killed')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Total units converted</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'units_converted')) }}</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <table class="table">
                            <colgroup>
                                <col width="25%">
                                <col width="25%">
                                <col width="25%">
                                <col width="25%">
                            </colgroup>
                            <thead class="hidden-xs">
                                <tr>
                                    <th>Unit</th>
                                    <th>Trained</th>
                                    <th>Lost</th>
                                    <th>Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedDominion->race->units as $unit)
                                    <tr>
                                        <td>{{ str_plural($unit->name) }}</td>
                                        <td>
                                            <strong>{{ number_format($statsService->getStat($selectedDominion, ('unit' .$unit->slot . '_trained'))) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($statsService->getStat($selectedDominion, ('unit' .$unit->slot . '_lost'))) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($statsService->getStat($selectedDominion, ('unit' .$unit->slot . '_training'))) }}</strong>
                                        </td>
                                    </tr>
                                @endforeach
                                {{--
                                <tr>
                                    <td>Total</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'unit1_trained') + $statsService->getStat($selectedDominion, 'unit2_trained') + $statsService->getStat($selectedDominion, 'unit3_trained') + $statsService->getStat($selectedDominion, 'unit4_trained')) }}</strong>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'unit1_lost') + $statsService->getStat($selectedDominion, 'unit2_lost') + $statsService->getStat($selectedDominion, 'unit3_lost') + $statsService->getStat($selectedDominion, 'unit4_lost')) }}</strong>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'unit1_training') + $statsService->getStat($selectedDominion, 'unit2_training') + $statsService->getStat($selectedDominion, 'unit3_training') + $statsService->getStat($selectedDominion, 'unit4_training')) }}</strong>
                                    </td>
                                </tr>
                                --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-md-12 col-md-12">
        <div class="box box-primary">
                <div class="row">
                    <div class="col-xs-12 col-sm-12">
                        <div class="box-header with-border">
                            <h4 class="box-title"><i class="fa fa-user-secret fa-fw"></i> Espionage</h4>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-4">
                        <table class="table">
                            <colgroup>
                                <col width="33%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Spy Power</th>
                                    <th>Offensive</th>
                                    <th>Defensive</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Ratio</td>
                                    <td>
                                        <strong>{{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'offense'), 3) }}</strong>
                                        @if ($militaryCalculator->getSpyRatioMultiplier($selectedDominion) !== 1.0)
                                            <small class="text-muted">({{ number_format(($militaryCalculator->getSpyRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'defense'), 3) }}</strong>
                                        @if ($militaryCalculator->getSpyRatioMultiplier($selectedDominion) !== 1.0)
                                            <small class="text-muted">({{ number_format(($militaryCalculator->getSpyRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><span data-toggle="tooltip" data-placement="top" title="Number of spies you have plus how many spies you have from units that count as spies in part or whole">Points</span></td>
                                    <td>
                                        <strong>{{ number_format($militaryCalculator->getSpyPoints($selectedDominion, 'offense')) }}</strong>
                                        @if ($militaryCalculator->getSpyRatioMultiplier($selectedDominion) !== 1.0)
                                            <small class="text-muted">({{ number_format(($militaryCalculator->getSpyRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format($militaryCalculator->getSpyPoints($selectedDominion, 'defense')) }}</strong>
                                        @if ($militaryCalculator->getSpyRatioMultiplier($selectedDominion) !== 1.0)
                                            <small class="text-muted">({{ number_format(($militaryCalculator->getSpyRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>


                        <table class="table">
                            <colgroup>
                                <col width="25%">
                                <col width="25%">
                                <col width="25%">
                                <col width="25%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Operation Type</th>
                                    <th>Sucessful</th>
                                    <th>Failure</th>
                                    <th>Success Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Info</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'espionage_info_success')) }}</strong>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'espionage_info_failure')) }}</strong>
                                    </td>
                                    <td>
                                        <strong>
                                            @if(($statsService->getStat($selectedDominion, 'espionage_info_success') + $statsService->getStat($selectedDominion, 'espionage_info_failure')) > 0)
                                                {{ number_format(($statsService->getStat($selectedDominion, 'espionage_info_success') / ($statsService->getStat($selectedDominion, 'espionage_info_success') + $statsService->getStat($selectedDominion, 'espionage_info_failure')))*100,2) }}%</strong>
                                            @else
                                                &mdash;
                                            @endif
                                    </td>
                                </tr>

                                <tr>
                                    <td>Theft</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'espionage_theft_success')) }}</strong>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'espionage_theft_failure')) }}</strong>
                                    </td>
                                    <td>
                                        <strong>
                                            @if(($statsService->getStat($selectedDominion, 'espionage_theft_success') + $statsService->getStat($selectedDominion, 'espionage_theft_failure')) > 0)
                                                {{ number_format(($statsService->getStat($selectedDominion, 'espionage_theft_success') / ($statsService->getStat($selectedDominion, 'espionage_theft_success') + $statsService->getStat($selectedDominion, 'espionage_theft_failure')))*100,2) }}%</strong>
                                            @else
                                                &mdash;
                                            @endif
                                    </td>
                                </tr>

                                <tr>
                                    <td>Hostile</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'espionage_hostile_success')) }}</strong>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'espionage_hostile_failure')) }}</strong>
                                    </td>
                                    <td>
                                        <strong>
                                            @if(($statsService->getStat($selectedDominion, 'espionage_hostile_success') + $statsService->getStat($selectedDominion, 'espionage_hostile_failure')) > 0)
                                                {{ number_format(($statsService->getStat($selectedDominion, 'espionage_hostile_success') / ($statsService->getStat($selectedDominion, 'espionage_hostile_success') + $statsService->getStat($selectedDominion, 'espionage_hostile_failure')))*100,2) }}%</strong>
                                            @else
                                                &mdash;
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
                                    <th colspan="2">Offensive Operations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Peasants killed</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'espionage_peasants_killed')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Peasants abducted</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'peasants_stolen')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Draftees killed</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'espionage_draftees_killed')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Draftees abducted</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'draftees_stolen')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Wizards killed</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'espionage_wizards_killed')) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Wizard strength</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'espionage_wizard_strength_damage')) }}%</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Improvements damage</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, 'espionage_damage_improvements')) }}</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-xs-12 col-sm-4">
                        <table class="table">
                            <colgroup>
                                <col width="33%">
                                <col>
                                <col>
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Theft</th>
                                    <th>Stolen</th>
                                    <th>Lost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedDominion->race->resources as $resourceKey)
                                    @php
                                        $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                    @endphp
                                    <tr>
                                        <td>{{ $resource->name }}</td>
                                        <td>
                                            <strong>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_stolen'))) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_lost'))) }}</strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="row">
    <div class="col-md-12 col-md-12">
        <div class="box box-primary">
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="box-header with-border">
                        <h4 class="box-title"><i class="ra ra-fairy-wand ra-fw"></i> Magic</h4>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-4">

                    <table class="table">
                        <colgroup>
                            <col width="33%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Wizard Power</th>
                                <th>Offensive</th>
                                <th>Defensive</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Ratio</td>
                                <td>
                                    <strong>{{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'offense'), 3) }}</strong>
                                    @if ($militaryCalculator->getWizardRatioMultiplier($selectedDominion) !== 1.0)
                                        <small class="text-muted">({{ number_format(($militaryCalculator->getWizardRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'defense'), 3) }}</strong>
                                    @if ($militaryCalculator->getWizardRatioMultiplier($selectedDominion) !== 1.0)
                                        <small class="text-muted">({{ number_format(($militaryCalculator->getWizardRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><span data-toggle="tooltip" data-placement="top" title="Number of spies you have plus how many spies you have from units that count as spies in part or whole">Points</span></td>
                                <td>
                                    <strong>{{ number_format($militaryCalculator->getWizardPoints($selectedDominion, 'offense')) }}</strong>
                                    @if ($militaryCalculator->getWizardRatioMultiplier($selectedDominion) !== 1.0)
                                        <small class="text-muted">({{ number_format(($militaryCalculator->getWizardRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ number_format($militaryCalculator->getWizardPoints($selectedDominion, 'defense')) }}</strong>
                                    @if ($militaryCalculator->getWizardRatioMultiplier($selectedDominion) !== 1.0)
                                        <small class="text-muted">({{ number_format(($militaryCalculator->getWizardRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table">
                        <colgroup>
                            <col width="20%">
                            <col width="20%">
                            <col width="20%">
                            <col width="20%">
                            <col width="20%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Spell Type</th>
                                <th>Successful</th>
                                <th>Failure</th>
                                <th>Success Rate</th>
                                <th>Duration (ticks)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Info</td>
                                <td>
                                    <strong>{{ number_format($statsService->getStat($selectedDominion, 'magic_info_success')) }}</strong>
                                </td>
                                <td>
                                    <strong>{{ number_format($statsService->getStat($selectedDominion, 'magic_info_failure')) }}</strong>
                                </td>
                                <td>
                                    <strong>
                                        @if(($statsService->getStat($selectedDominion, 'magic_info_success') + $statsService->getStat($selectedDominion, 'magic_info_failure')) > 0)
                                            {{ number_format(($statsService->getStat($selectedDominion, 'magic_info_success') / ($statsService->getStat($selectedDominion, 'magic_info_success') + $statsService->getStat($selectedDominion, 'magic_info_failure')))*100,2) }}%</strong>
                                        @else
                                            &mdash;
                                        @endif
                                </td>
                                <td>
                                    &mdash;
                                </td>
                            </tr>
                            <tr>
                                <td>Hostile</td>
                                <td>
                                    <strong>{{ number_format($statsService->getStat($selectedDominion, 'magic_hostile_success')) }}</strong>
                                </td>
                                <td>
                                    <strong>{{ number_format($statsService->getStat($selectedDominion, 'magic_hostile_failure')) }}</strong>
                                </td>
                                <td>
                                    <strong>
                                        @if(($statsService->getStat($selectedDominion, 'magic_hostile_success') + $statsService->getStat($selectedDominion, 'magic_hostile_failure')) > 0)
                                            {{ number_format(($statsService->getStat($selectedDominion, 'magic_hostile_success') / ($statsService->getStat($selectedDominion, 'magic_hostile_success') + $statsService->getStat($selectedDominion, 'magic_hostile_failure')))*100,2) }}%</strong>
                                        @else
                                            &mdash;
                                        @endif
                                </td>
                                <td>
                                    <strong>{{ number_format($statsService->getStat($selectedDominion, 'magic_hostile_duration')) }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td>Invasion</td>
                                <td>
                                    <strong>{{ number_format($statsService->getStat($selectedDominion, 'magic_invasion_success')) }}</strong>
                                </td>
                                <td>
                                    <strong>{{ number_format($statsService->getStat($selectedDominion, 'magic_invasion_failure')) }}</strong>
                                </td>
                                <td>
                                    <strong>
                                        @if(($statsService->getStat($selectedDominion, 'magic_invasion_success') + $statsService->getStat($selectedDominion, 'magic_invasion_failure')) > 0)
                                            {{ number_format(($statsService->getStat($selectedDominion, 'magic_invasion_success') / ($statsService->getStat($selectedDominion, 'magic_invasion_success') + $statsService->getStat($selectedDominion, 'magic_invasion_failure')))*100,2) }}%</strong>
                                        @else
                                            &mdash;
                                        @endif
                                </td>
                                <td>
                                    <strong>{{ number_format($statsService->getStat($selectedDominion, 'magic_invasion_duration')) }}</strong>
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
                                <td>Peasants killed</td>
                                <td>
                                    <strong>{{ number_format($statsService->getStat($selectedDominion, 'magic_peasants_killed')) }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td>Draftees killed</td>
                                <td>
                                    <strong>{{ number_format($statsService->getStat($selectedDominion, 'magic_draftees_killed')) }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td>Spies killed</td>
                                <td>
                                    <strong>{{ number_format($statsService->getStat($selectedDominion, 'magic_spies_killed')) }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td>Improvements damage</td>
                                <td>
                                    <strong>{{ number_format($statsService->getStat($selectedDominion, 'magic_damage_improvements')) }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td>Morale</td>
                                <td>
                                    <strong>{{ number_format($statsService->getStat($selectedDominion, 'magic_damage_morale')) }}%</strong>
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
                                <th>Resource</th>
                                <th>Destroyed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedDominion->race->resources as $resourceKey)
                                @php
                                    $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                @endphp
                                <tr>
                                    <td>{{ $resource->name }}</td>
                                    <td>
                                        <strong>{{ number_format($statsService->getStat($selectedDominion, ($resource->key . '_destroyed'))) }}</strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


  <div class="row">
      <div class="col-md-12 col-md-6">
          <div class="box box-primary">
            <div class="row">
                <div class="col-xs-12 col-sm-12">
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
                                        <td>Current Population</td>
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
                                        <td>Military Population</td>
                                        <td>
                                            <strong>{{ number_format($populationCalculator->getPopulationMilitary($selectedDominion)) }}</strong>
                                            <small class="text-muted">({{ number_format((100 - ($selectedDominion->peasants / $populationCalculator->getPopulation($selectedDominion)) * 100), 2) }}%)</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Max Population</td>
                                        <td>
                                            <strong>{{ number_format($populationCalculator->getMaxPopulation($selectedDominion)) }}</strong>
                                            @if ($populationCalculator->getMaxPopulationMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format($populationCalculator->getMaxPopulationRaw($selectedDominion)) }} raw)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td>Population Multiplier</td>
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
