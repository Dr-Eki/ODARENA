@extends('layouts.master')

{{--
@section('page-header', 'Status')
--}}

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-chart-bar"></i> The Dominion of {{ $selectedDominion->name }}</h3>
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
                                        <th colspan="2">Overview</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Ruler:</td>
                                        <td>
                                            @if(isset($selectedDominion->title->name))
                                                  <em>
                                                      <span data-toggle="tooltip" data-placement="top" title="{!! $titleHelper->getRulerTitlePerksForDominion($selectedDominion) !!}">
                                                          {{ $selectedDominion->title->name }}
                                                      </span>
                                                  </em>
                                            @endif

                                            {{ $selectedDominion->ruler_name }}

                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Faction:</td>
                                        <td>{{ $selectedDominion->race->name }}</td>
                                    </tr>
                                    <tr>
                                        <td>Land:</td>
                                        <td>{{ number_format($landCalculator->getTotalLand($selectedDominion, true)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $raceHelper->getPeasantsTerm($selectedDominion->race) }}:</td>
                                        <td>{{ number_format($selectedDominion->peasants) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Employment:</td>
                                        <td>{{ number_format($populationCalculator->getEmploymentPercentage($selectedDominion), 2) }}%</td>
                                    </tr>
                                    <tr>
                                        <td>Networth:</td>
                                        <td>{{ number_format($networthCalculator->getDominionNetworth($selectedDominion)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="<ul><li>Prestige increases your offensive power, food production, and population.</li><li>Each prestige produces 1 XP per tick.</li><li>Multiplier: {{ 1+$prestigeCalculator->getPrestigeMultiplier($selectedDominion) }}x</li></ul>">
                                              Prestige:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->prestige) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Victories:</td>
                                        <td>{{ number_format($selectedDominion->stat_attacking_success) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Net Victories:</td>
                                        <td>{{ number_format($militaryCalculator->getNetVictories($selectedDominion)) }}</td>
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
                                        <th colspan="2">Resources</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Gold:</td>
                                        <td>{{ number_format($selectedDominion->resource_gold) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Food:</td>
                                        <td>{{ number_format($selectedDominion->resource_food) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Lumber:</td>
                                        <td>{{ number_format($selectedDominion->resource_lumber) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Mana:</td>
                                        <td>{{ number_format($selectedDominion->resource_mana) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Ore:</td>
                                        <td>{{ number_format($selectedDominion->resource_ore) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Gems:</td>
                                        <td>{{ number_format($selectedDominion->resource_gems) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="<p>Used to unlock Advancements.</p><p>Unspent XP increases the perk from your Ruler Title.</p>">
                                              Experience Points:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->resource_tech) }}</td>
                                    </tr>
                                    @if ($selectedDominion->race->name == 'Norse')
                                    <tr>
                                        <td>Champions:</td>
                                        <td>{{ number_format($selectedDominion->resource_champion) }}</td>
                                    </tr>
                                    @elseif ($selectedDominion->race->name == 'Demon')
                                    <tr>
                                        <td>Souls:</td>
                                        <td>{{ number_format($selectedDominion->resource_soul) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Blood:</td>
                                        <td>{{ number_format($selectedDominion->resource_blood) }}</td>
                                    </tr>
                                    @endif
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
                                        <th colspan="2">Military</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Morale:</td>
                                        <td>{{ number_format($selectedDominion->morale) }}% / {{ number_format(100 * (1 + $militaryCalculator->getBaseMoraleModifier($selectedDominion, $populationCalculator->getPopulation($selectedDominion)))) }}%</td>                                 </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getDrafteeHelpString( $selectedDominion->race) }}">
                                                {{ $raceHelper->getDrafteesTerm($selectedDominion->race) }}:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->military_draftees) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                          <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('unit1', $selectedDominion->race) }}">
                                              {{ $selectedDominion->race->units->get(0)->name }}:
                                          </span>
                                        </td>
                                        <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 1)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                          <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('unit2', $selectedDominion->race) }}">
                                              {{ $selectedDominion->race->units->get(1)->name }}:
                                          </span>
                                        </td>
                                        <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 2)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                          <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('unit3', $selectedDominion->race) }}">
                                              {{ $selectedDominion->race->units->get(2)->name }}:
                                          </span>
                                        </td>
                                        <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 3)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                          <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('unit4', $selectedDominion->race) }}">
                                              {{ $selectedDominion->race->units->get(3)->name }}:
                                          </span>
                                        </td>
                                        <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 4)) }}</td>
                                    </tr>

                                    @if (!(bool)$selectedDominion->race->getPerkValue('cannot_train_spies'))
                                    <tr>
                                        <td>Spies:</td>
                                        <td>{{ number_format($selectedDominion->military_spies) }}</td>
                                    </tr>
                                    @endif

                                    @if (!(bool)$selectedDominion->race->getPerkValue('cannot_train_wizards'))
                                    <tr>
                                        <td>Wizards:</td>
                                        <td>{{ number_format($selectedDominion->military_wizards) }}</td>
                                    </tr>
                                    @endif

                                    @if (!(bool)$selectedDominion->race->getPerkValue('cannot_train_archmages'))
                                    <tr>
                                        <td>ArchMages:</td>
                                        <td>{{ number_format($selectedDominion->military_archmages) }}</td>
                                    @endif

                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Statistics</h3>
                    <a href="{{ route('dominion.advisors.statistics') }}" class="pull-right"><span>Statistics Advisor</span></a>
                </div>
                <div class="box-body">
                      <table class="table">
                          <colgroup>
                              <col width="50%">
                              <col width="50%">
                          </colgroup>
                        <tbody>
                            <tr>
                                <td colspan="2" class="text-center"><strong>Military</strong></td>
                            </tr>
                            <tr>
                                <td><span data-toggle="tooltip" data-placement="top" title="Your current Defensive Power (DP)">Defensive Power:</span></td>
                                <td>
                                    {{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }}
                                    @if ($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) !== 1.0)
                                        <small class="text-muted">({{ number_format(($militaryCalculator->getDefensivePowerRaw($selectedDominion))) }} raw)</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><span data-toggle="tooltip" data-placement="top" title="Your current Offensive Power (OP)">Offensive Power:</span></td>
                                <td>
                                    {{ number_format($militaryCalculator->getOffensivePower($selectedDominion)) }}
                                    @if ($militaryCalculator->getOffensivePowerMultiplier($selectedDominion) !== 1.0)
                                        <small class="text-muted">({{ number_format(($militaryCalculator->getOffensivePowerRaw($selectedDominion))) }} raw)</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><span data-toggle="tooltip" data-placement="top" title="Your current Spies Per Acre (SPA) on offense">Offensive Spy Ratio:</span></td>
                                <td>
                                    {{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'offense'), 3) }}
                                    @if ($militaryCalculator->getSpyRatioMultiplier($selectedDominion) !== 1.0)
                                        <small class="text-muted">({{ number_format(($militaryCalculator->getSpyRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><span data-toggle="tooltip" data-placement="top" title="Your current Wizards Per Acre (WPA) on offense">Offensive Wizard Ratio:</span></td>
                                <td>
                                    {{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'offense'), 3) }}
                                    @if ($militaryCalculator->getWizardRatioMultiplier($selectedDominion) !== 1.0)
                                        <small class="text-muted">({{ number_format(($militaryCalculator->getWizardRatioMultiplier($selectedDominion)-1)*100, 2) }}%)</small>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td colspan="2" class="text-center"><strong>Population</strong></td>
                            </tr>
                            <tr>
                                <td>Current Population:</td>
                                <td>
                                    {{ number_format($populationCalculator->getPopulation($selectedDominion)) }}
                                </td>
                            </tr>
                            @if(!$selectedDominion->race->getPerkMultiplier('no_population'))
                            <tr>
                                <td>{{ str_plural($raceHelper->getPeasantsTerm($selectedDominion->race)) }}:</td>
                                <td>
                                    {{ number_format($selectedDominion->peasants) }}
                                    <small class="text-muted">({{ number_format((($selectedDominion->peasants / $populationCalculator->getPopulation($selectedDominion)) * 100), 2) }}%)</small>
                                </td>
                            </tr>
                            <tr>
                                <td>Military Population:</td>
                                <td>
                                    {{ number_format($populationCalculator->getPopulationMilitary($selectedDominion)) }}
                                    <small class="text-muted">({{ number_format((100 - ($selectedDominion->peasants / $populationCalculator->getPopulation($selectedDominion)) * 100), 2) }}%)</small>
                                </td>
                            </tr>
                            <tr>
                                <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by Barracks:<br>Filled / Available">Barracks housing:</span></td>
                                <td>{{ number_format($populationCalculator->getUnitsHousedInBarracks($selectedDominion)) }} / {{ number_format($populationCalculator->getAvailableHousingFromBarracks($selectedDominion)) }}</td>
                            </tr>
                            <tr>
                                <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by Forest Havens:<br>Filled / Available">Spy housing:</span></td>
                                <td>{{ number_format($populationCalculator->getUnitsHousedInForestHavens($selectedDominion)) }} / {{ number_format($populationCalculator->getAvailableHousingFromForestHavens($selectedDominion)) }}</td>
                            </tr>
                            <tr>
                                <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by Wizard Guilds:<br>Filled / Available">Wizard housing:</span></td>
                                <td>{{ number_format($populationCalculator->getUnitsHousedInWizardGuilds($selectedDominion)) }} / {{ number_format($populationCalculator->getAvailableHousingFromWizardGuilds($selectedDominion)) }}</td>
                            </tr>
                            <tr>
                                <td>Max Population:</td>
                                <td>
                                    {{ number_format($populationCalculator->getMaxPopulation($selectedDominion)) }}
                                    @if ($populationCalculator->getMaxPopulationMultiplier($selectedDominion) !== 1.0)
                                        <small class="text-muted">({{ number_format($populationCalculator->getMaxPopulationRaw($selectedDominion)) }} raw)</small>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td>Population Multiplier:</td>
                                <td>
                                    {{ number_string((($populationCalculator->getMaxPopulationMultiplier($selectedDominion) - 1) * 100), 3, true) }}%
                                </td>
                            </tr>



                        </tbody>
                      </table>

                </div>
            </div>
        </div>


        @if ($selectedDominion->realm->motd && ($selectedDominion->realm->motd_updated_at > now()->subDays(3)))
            <div class="col-sm-12 col-md-9">
                <div class="panel panel-warning">
                    <div class="panel-body">
                        <b>Message of the Day:</b> {{ $selectedDominion->realm->motd }}
                        <br/><small class="text-muted">Posted {{ $selectedDominion->realm->motd_updated_at }}</small>
                    </div>
                </div>
            </div>
        @endif

        @if ($dominionProtectionService->canTick($selectedDominion) or $dominionProtectionService->canDelete($selectedDominion))
        <div class="col-sm-12 col-md-9">
            @if ($dominionProtectionService->canTick($selectedDominion))
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-shield text-aqua"></i> Protection</h3>
                    </div>
                      <div class="box-body">
                          <p>You are under a magical state of protection. You have <b>{{ $selectedDominion->protection_ticks }}</b> protection {{ str_plural('tick', $selectedDominion->protection_ticks) }} left.</p>
                          <p>During protection you cannot be attacked or attack other dominions. You can neither cast any offensive spells or engage in espionage.</p>
                          <p>Regularly scheduled ticks do not count towards your dominion while you are in protection.</p>
                          <p>Click the button below to proceed to the next tick. <em>There is no undo option so make sure you are ready to proceed.</em> </p>
                          <form action="{{ route('dominion.status') }}" method="post" role="form" id="tick_form">
                          @csrf
                          <input type="hidden" name="returnTo" value="{{ Route::currentRouteName() }}">
                          <select class="btn btn-warning" name="ticks">
                              @for ($i = 1; $i <= $selectedDominion->protection_ticks; $i++)
                              <option value="{{ $i }}">{{ $i }}</option>
                              @endfor
                          </select>

                          <button type="submit"
                                  class="btn btn-info"
                                  {{ $selectedDominion->isLocked() ? 'disabled' : null }}
                                  id="tick-button">
                              <i class="ra ra-shield"></i>
                              Proceed tick(s) ({{ $selectedDominion->protection_ticks }} {{ str_plural('tick', $selectedDominion->protection_ticks) }} left)
                        </form>
                      </div>
                </div>
            @endif
            @if ($dominionProtectionService->canDelete($selectedDominion))
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-broken-shield text-red"></i> Delete Dominion</h3>
                    </div>
                      <div class="box-body">
                          <p>You can delete your dominion and create a new one.</p>
                          <p><strong>There is no way to undo this action.</strong></p>
                          <form id="delete-dominion" class="form-inline" action="{{ route('dominion.misc.delete') }}" method="post">
                              @csrf
                              <div class="form-group">
                                  <select class="form-control">
                                      <option value="0">Delete?</option>
                                      <option value="1">Confirm Delete</option>
                                  </select>
                                  <p>
                                    <button type="submit" class="btn btn-sm btn-danger" disabled>Delete my dominion</button>
                                  </p>
                              </div>
                          </form>
                      </div>
                </div>
            @endif
        </div>
        @endif

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-newspaper-o"></i> Recent News</h3>
                </div>

                @if ($notifications->isEmpty())
                    <div class="box-body">
                        <p>No recent news.</p>
                    </div>
                @else
                    <div class="box-body">
                        <table class="table table-condensed no-border">
                            @foreach ($notifications as $notification)
                                @php
                                    $route = array_get($notificationHelper->getNotificationCategories(), "{$notification->data['category']}.{$notification->data['type']}.route", '#');

                                    if (is_callable($route)) {
                                        if (isset($notification->data['data']['_routeParams'])) {
                                            $route = $route($notification->data['data']['_routeParams']);
                                        } else {
                                            // fallback
                                            $route = '#';
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <span class="text-muted">{{ $notification->created_at }}</span>
                                    </td>
                                    <td>
                                        @if ($route !== '#')<a href="{{ $route }}">@endif
                                            <i class="{{ array_get($notificationHelper->getNotificationCategories(), "{$notification->data['category']}.{$notification->data['type']}.iconClass", 'fa fa-question') }}"></i>
                                            {{ $notification->data['message'] }}
                                        @if ($route !== '#')</a>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    <div class="box-footer">
                        <div class="pull-right">
                            {{ $notifications->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if ($selectedDominion->pack !== null)
            <div class="col-sm-12 col-md-3">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Pack</h3>
                    </div>
                    <div class="box-body">
                        <p>You are in pack <em>{{$selectedDominion->pack->name}}</em> with:</p>
                        <ul>
                            @foreach ($selectedDominion->pack->dominions as $dominion)
                                <li>
                                    @if ($dominion->ruler_name === $dominion->name)
                                        <strong>{{ $dominion->name }}</strong>
                                    @else
                                        {{ $dominion->ruler_name }} of <strong>{{ $dominion->name }}</strong>
                                    @endif

                                    @if($dominion->ruler_name !== $dominion->user->display_name)
                                        ({{ $dominion->user->display_name }})
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        <p>
                            Slots used: {{ $selectedDominion->pack->dominions->count() }} / {{ $selectedDominion->pack->size }}.
                            @if ($selectedDominion->pack->isFull())
                                (full)
                            @elseif ($selectedDominion->pack->isClosed())
                                (closed)
                            @endif
                        </p>
                        @if (!$selectedDominion->pack->isFull() && !$selectedDominion->pack->isClosed())
                            <p>Your pack will automatically close on <strong>{{ $selectedDominion->pack->getClosingDate() }}</strong> to make space for random players in your realm.</p>
                            @if ($selectedDominion->pack->creator_dominion_id === $selectedDominion->id)
                                <p>
                                    <form action="{{ route('dominion.misc.close-pack') }}" method="post">
                                        @csrf
                                        <button type="submit" class="btn btn-link" style="padding: 0;">Close Pack Now</button>
                                    </form>
                                </p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection
@push('inline-scripts')
     <script type="text/javascript">
         (function ($) {
             $('#delete-dominion select').change(function() {
                 var confirm = $(this).val();
                 if (confirm == "1") {
                     $('#delete-dominion button').prop('disabled', false);
                 } else {
                     $('#delete-dominion button').prop('disabled', true);
                 }
             });
         })(jQuery);
     </script>
 @endpush
