@extends('layouts.master')
@section('title', 'Status')

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
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="{!! $raceHelper->getRacePerksHelpString($selectedDominion->race) !!}">
                                            {{ $selectedDominion->race->name }}
                                        </span>
                                    </td>
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
                                        <span data-toggle="tooltip" data-placement="top" title="<ul><li>Prestige increases your offensive power, food production, and population.</li><li>Each prestige produces 1 XP/tick.</li><li>Multiplier: {{ 1+$prestigeCalculator->getPrestigeMultiplier($selectedDominion) }}x</li></ul>">
                                            Prestige:
                                        </span>
                                    </td>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="{{ $dominionHelper->getPrestigeHelpString($selectedDominion) }}">
                                            {{ number_format(floor($selectedDominion->prestige)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Victories:</td>
                                    <td>{{ number_format($statsService->getStat($selectedDominion, 'invasion_victories')) }}</td>
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
                            <tbody>
                                @foreach($selectedDominion->race->resources as $resourceKey)
                                    @php
                                        $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                    @endphp
                                    <tr>
                                        <td>{{ $resource->name }}:</td>
                                        <td>{{ number_format($resourceCalculator->getAmount($selectedDominion, $resourceKey)) }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="<p>Used to unlock Advancements.</p><p>Unspent XP increases the perk from your Ruler Title.</p>">
                                            Experience Points:
                                        </span>
                                    </td>
                                    <td>{{ number_format($selectedDominion->xp) }}</td>
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
                            <tbody>
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="(100% + {{ $moraleCalculator->getBaseMoraleModifier($selectedDominion) }}%) * {{ $moraleCalculator->getBaseMoraleMultiplier($selectedDominion) }}">
                                            Morale:
                                        </span>
                                    </td>
                                    <td>{{ number_format($selectedDominion->morale) }}% / {{ number_format($moraleCalculator->getBaseMorale($selectedDominion)) }}%</td>
                                </tr>
                                @if(!$selectedDominion->race->getPerkValue('no_drafting'))
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getDrafteeHelpString( $selectedDominion->race) }}">
                                            {{ $raceHelper->getDrafteesTerm($selectedDominion->race) }}:
                                        </span>
                                    </td>
                                    <td>{{ number_format($selectedDominion->military_draftees) }}</td>
                                </tr>
                                @endif
                                @foreach($selectedDominion->race->units as $unit)
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString(('unit' . $unit->slot), $selectedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unit, 'offense'), $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unit, 'defense'), ]) }}">
                                            {{ $unit->name }}:
                                        </span>
                                    </td>
                                    <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, $unit->slot)) }}</td>
                                </tr>
                                @endforeach

                                @if (!$selectedDominion->race->getPerkValue('cannot_train_spies'))
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="Spy strength: {{ number_format($selectedDominion->spy_strength) }}% {{ $selectedDominion->spy_strength < 100 ? '(+' . $militaryCalculator->getSpyStrengthRegen($selectedDominion) . '%/tick)' : '' }}">
                                            Spies:
                                        </span>
                                    </td>
                                    <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 'spies')) }}</td>
                                </tr>
                                @endif

                                @if (!$selectedDominion->race->getPerkValue('cannot_train_wizards'))
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="Wizard strength: {{ number_format($selectedDominion->wizard_strength) }}% {{ $selectedDominion->wizard_strength < 100 ? '(+' . $militaryCalculator->getWizardStrengthRegen($selectedDominion) . '%/tick)' : '' }}">
                                            Wizards:
                                        </span>
                                    </td>
                                    <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 'wizards')) }}</td>
                                </tr>
                                @endif

                                @if (!$selectedDominion->race->getPerkValue('cannot_train_archmages'))
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="Wizard strength: {{ number_format($selectedDominion->wizard_strength) }}% {{ $selectedDominion->wizard_strength < 100 ? '(+' . $militaryCalculator->getWizardStrengthRegen($selectedDominion) . '%/tick)' : '' }}">
                                            Archmages:
                                        </span>
                                    </td>
                                    <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 'archmages')) }}</td>
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
                                @php
                                    $dpFromUnitsWithoutSufficientResources = $militaryCalculator->dpFromUnitWithoutSufficientResources($selectedDominion);
                                @endphp

                                @if($dpFromUnitsWithoutSufficientResources)
                                    <span class="text-red" data-toggle="tooltip" data-placement="top" title="{{ number_format($dpFromUnitsWithoutSufficientResources) }} raw DP</b> unavailable due to insufficient resources!">
                                        <b>{{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }}</b>
                                        @if ($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) !== 1.0)
                                            <small class="text-red">({{ number_format(($militaryCalculator->getDefensivePowerRaw($selectedDominion))) }} raw)</small>
                                        @endif
                                    </span>
                                @else
                                    {{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }}
                                    @if ($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) !== 1.0)
                                        <small class="text-muted">({{ number_format(($militaryCalculator->getDefensivePowerRaw($selectedDominion))) }} raw)</small>
                                    @endif
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
                        @include('partials.dominion.housing')
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

                        @if($selectedDominion->race->name == 'Cult')
                        <tr>
                            <td><i class="ra ra-brain-freeze ra-fw"></i><span data-toggle="tooltip" data-placement="top" title="A measurement of the mental fortitude of your dominion"> Psionic Strength:</td>
                            <td>{{ number_format($dominionCalculator->getPsionicStrength($selectedDominion),6) }}</td>
                        </tr>
                        @endif



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

    @if($titleCalculator->canChangeTitle($selectedDominion))
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-exchange-alt fa-fw text-red"></i> Change Ruler Title</h3>
                </div>
                <div class="box-body">
                    <p><strong class="text-red">You can only change ruler title before you have taken an action.</strong> As soon as you take any action, this option goes away.</p>
                    <form action="{{ route('dominion.status.change-title') }}" method="post" role="form" id="tick_form">
                        @csrf
                        <input type="hidden" name="returnTo" value="{{ Route::currentRouteName() }}">
                        <div class="input-group">
                            <select name="title_id" id="title" class="form-control select2" data-placeholder="Select a title" required>
                            <option></option>
                                @foreach ($titles as $title)
                                    <option value="{{ $title->id }}">
                                        {{ $title->name }}
                                        (@foreach ($title->perks as $perk)
                                            @php
                                                $perkDescription = $titleHelper->getPerkDescriptionHtmlWithValue($perk);
                                            @endphp
                                                {!! $perkDescription['description'] !!} {!! $perkDescription['value']  !!}
                                        @endforeach)
                                    </option>
                                @endforeach
                            </select>
                            <span class="input-group-append">
                                <button type="submit" class="btn btn-block btn-warning">Change ruler title</button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($dominionProtectionService->canTick($selectedDominion))
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-shield text-aqua"></i> Protection</h3>
                </div>
                <div class="box-body">
                    <p>You are under a magical state of protection. You have <b>{{ $selectedDominion->protection_ticks }}</b> protection {{ str_plural('tick', $selectedDominion->protection_ticks) }} left.</p>
                    <p>During protection you cannot be attacked or attack other dominions. You can neither cast any offensive spells or engage in espionage.</p>
                    <p>Regularly scheduled ticks do not count towards your dominion while you are in protection.</p>
                    <p>Select number of ticks and click the button below to proceed that many ticks. <em>There is no undo or Go Back option so make sure you are ready to proceed.</em> </p>
                    <form action="{{ route('dominion.status') }}" method="post" role="form" id="tick_form">
                    @csrf
                    <input type="hidden" name="returnTo" value="{{ Route::currentRouteName() }}">
                    <select class="btn btn-warning" name="ticks">
                        @for ($i = 1; $i <= min(24, $selectedDominion->protection_ticks); $i++)
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
        </div>
    @endif

    @if ($dominionProtectionService->canDelete($selectedDominion))
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-broken-shield text-red"></i> Delete Dominion</h3>
                </div>
                <div class="box-body">
                    <p>You can delete your dominion and create a new one.</p>
                    <p><strong>There is instant and cannot be undone.</strong></p>
                    <form id="delete-dominion" class="form-inline" action="{{ route('dominion.misc.delete') }}" method="post">
                        @csrf
                        <div class="input-group">
                            <select class="form-control">
                                <option value="0">Delete?</option>
                                <option value="1">Confirm Delete</option>
                            </select>
                            <span class="input-group-append">
                                <button type="submit" class="btn btn-block btn-danger" disabled>Delete my dominion</button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    
    @if ($dominionProtectionService->canDelete($selectedDominion) and $selectedDominion->states->count() > 0 and in_array(request()->getHost(), ['sim.odarena.com', 'odarena.local', 'odarena.virtual']))
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fas fa-history text-purple"></i> Go Back To Previous Tick</h3>
                    <span class="pull-right label label-danger">Experimental</span>
                </div>
                <div class="box-body">
                <p>Click a tick number below to restore your dominion to the state it was when that tick began.</p>
                <p><span class="label label-danger">Warning</span> All ticks that happened after the point you go back to will be deleted.</p>
                <p><span class="label label-success">Note</span> The green button is the current tick. Clicking it will undo all actions taken this tick.</p>
                <div class="row">
                    @foreach($selectedDominion->states->sortDesc() as $dominionState)
                        <div class="col-md-1">
                            <div class="box">
                                <div class="box-header with-border">
                                    <form class="form-inline" action="{{ route('dominion.misc.restore-dominion-state') }}" method="post">
                                        @csrf
                                        <input type="hidden" name="dominion_state" id="dominion_state" value="{{ $dominionState->id }}">
                                        <button class="btn btn-block {{ ($dominionState->dominion_protection_tick == $selectedDominion->protection_ticks) ? 'btn-success' : 'btn-info' }}">
                                            {{ $dominionState->dominion_protection_tick }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="box-footer">
                    <small class="text-muted">This is an experimental feature. Use at your own risk.</small>
                </div>
            </div>
        </div>
    @endif

    @if(!$selectedDominion->round->hasStarted() or $dominionProtectionService->canTick($selectedDominion))
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fas fa-fast-forward fa-fw text-orange"></i> Quickstart</h3>
                </div>
                <div class="box-body">
                    <p>Click the button below to generate a quickstart based on the current state of your dominion.</p>
                    <a href="{{ route('dominion.quickstart') }}" class="btn btn-warning">
                        <i class="fas fa-fast-forward fa-fw"></i> Generate Quickstart
                    </a>
                </div>
            </div>
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

    <div class="col-md-12 col-md-3">
        @include('partials.dominion.watched-dominions')
    </div>

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


@if($titleCalculator->canChangeTitle($selectedDominion))
    @push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
    @endpush

    @push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
    @endpush

    @push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#title').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
            @if (session('title'))
                $('#title').val('{{ session('title') }}').trigger('change.select2').trigger('change');
            @endif
        })(jQuery);


        (function ($) {
            $('#faction').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
            @if (session('faction'))
                $('#faction').val('{{ session('faction') }}').trigger('change.select2').trigger('change');
            @endif
        })(jQuery);

        function select2Template(state)
        {
            if (!state.id)
            {
                return state.text;
            }

            const current = state.element.dataset.current;
            const experimental = state.element.dataset.experimental;
            const maxPerRound = state.element.dataset.maxperround;

            return $(`
                <div class="pull-left">${state.text}</div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
    @endpush
@endif
