@extends('layouts.master')

@section('page-header', 'Op Center')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'clear_sight');
                @endphp

                @slot('title', ('Status Screen (' . $dominion->name . ')'))
                @slot('titleIconClass', 'fa fa-chart-bar')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Cast magic spell 'Clear Sight' to reveal information.</p>
                @else
                    @slot('tableResponsive', false)
                    @slot('noPadding', true)


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
                                        <td><em>{{ isset($infoOp->data['title']) ? $infoOp->data['title'] : '' }}</em> {{ $infoOp->data['ruler_name'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>Faction:</td>
                                        <td>{{ $dominion->race->name }}</td>
                                    </tr>
                                    <tr>
                                        <td>Land:</td>
                                        <td>
                                            {{ number_format($infoOp->data['land']) }}
                                            <span class="{{ $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $dominion) }}">
                                                ({{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 2) }}%)
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($raceHelper->getPeasantsTerm($dominion->race)) }}:</td>
                                        <td>{{ number_format($infoOp->data['peasants']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Employment:</td>
                                        <td>{{ number_format($infoOp->data['employment'], 2) }}%</td>
                                    </tr>
                                    <tr>
                                        <td>Networth:</td>
                                        <td>{{ number_format($infoOp->data['networth']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Prestige:</td>
                                        <td>{{ number_format($infoOp->data['prestige']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Victories:</td>
                                        <td>{{ number_format($infoOp->data['victories']) }}</td>
                                    </tr>
                                    @if(isset($infoOp->data['net_victories']))
                                    <tr>
                                        <td>Net Victories:</td>
                                        <td>{{ number_format($infoOp->data['net_victories']) }}</td>
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
                                        <th colspan="2">Resources</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Gold:</td>
                                        <td>{{ number_format($infoOp->data['resource_gold']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Food:</td>
                                        <td>{{ number_format($infoOp->data['resource_food']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Lumber:</td>
                                        <td>{{ number_format($infoOp->data['resource_lumber']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Mana:</td>
                                        <td>{{ number_format($infoOp->data['resource_mana']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Ore:</td>
                                        <td>{{ number_format($infoOp->data['resource_ore']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Gems:</td>
                                        <td>{{ number_format($infoOp->data['resource_gems']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Experience Points:</td>
                                        <td>{{ number_format($infoOp->data['resource_tech']) }}</td>
                                    </tr>

                                    @if ($dominion->race->name == 'Norse' and isset($infoOp->data['resource_champion']))
                                    <tr>
                                        <td>Champions:</td>
                                        <td>{{ number_format($infoOp->data['resource_champion']) }}</td>
                                    </tr>
                                    @elseif ($dominion->race->name == 'Demon')
                                        @if(isset($infoOp->data['resource_soul']))
                                        <tr>
                                            <td>Souls:</td>
                                            <td>{{ number_format($infoOp->data['resource_soul']) }}</td>
                                        </tr>
                                        @endif
                                        @if(isset($infoOp->data['resource_blood']))
                                        <tr>
                                            <td>Blood:</td>
                                            <td>{{ number_format($infoOp->data['resource_blood']) }}</td>
                                        </tr>
                                        @endif
                                    @elseif ($dominion->race->name == 'Yeti' and isset($infoOp->data['resource_wild_yeti']) and $infoOp->data['resource_wild_yeti'] > 0)
                                    <tr>
                                        <td>Wild yetis:</td>
                                        <td>{{ number_format($infoOp->data['resource_wild_yeti']) }}</td>
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
                                        <td>{{ number_format($infoOp->data['morale']) }}%</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getDrafteeHelpString( $dominion->race) }}">
                                                {{ $raceHelper->getDrafteesTerm($dominion->race) }}:
                                            </span>
                                        </td>
                                        <td>{{ number_format($infoOp->data['military_draftees']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                          <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('unit1', $dominion->race) }}">
                                              {{ $dominion->race->units->get(0)->name }}:
                                          </span>
                                        </td>
                                        <td>{{ number_format($infoOp->data['military_unit1']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                          <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('unit2', $dominion->race) }}">
                                              {{ $dominion->race->units->get(1)->name }}:
                                          </span>
                                        </td>
                                        <td>{{ number_format($infoOp->data['military_unit2']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                          <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('unit3', $dominion->race) }}">
                                              {{ $dominion->race->units->get(2)->name }}:
                                          </span>
                                        </td>
                                        <td>{{ number_format($infoOp->data['military_unit3']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                          <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('unit4', $dominion->race) }}">
                                              {{ $dominion->race->units->get(3)->name }}:
                                          </span>
                                        </td>
                                        <td>{{ number_format($infoOp->data['military_unit4']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Spies:</td>
                                        <td>{{ number_format($infoOp->data['military_spies']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Wizards:</td>
                                        <td>{{ number_format($infoOp->data['military_wizards']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>ArchMages:</td>
                                        <td>{{ number_format($infoOp->data['military_archmages']) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @php
                        $recentlyInvadedCount = (isset($infoOp->data['recently_invaded_count']) ? (int)$infoOp->data['recently_invaded_count'] : 0);
                    @endphp

                    @if ($recentlyInvadedCount > 0)
                        <p class="text-center" style="margin-bottom: 0.5em;">
                            @if ($recentlyInvadedCount >= 5)
                                This dominion has been invaded <strong><em>extremely heavily</em></strong> in recent times.
                            @elseif ($recentlyInvadedCount >= 3)
                                This dominion has been invaded <strong>heavily</strong> in recent times.
                            @else
                                This dominion has been invaded in recent times.
                            @endif
                        </p>
                    @endif
                @endif

                @slot('boxFooter')
                    @if ($infoOp !== null)
                        <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                        @if ($infoOp->isInvalid())
                            <span class="label label-danger">Invalid</span>
                        @elseif ($infoOp->isStale())
                            <span class="label label-warning">Stale</span>
                        @endif
                        @if ($infoOp->isInaccurate())
                            <span class="label label-info" data-toggle="tooltip" data-placement="top" title="The information is distorted by magic, consult the Scribes">Inaccurate</span>
                        @endif
                    @endif

                    <div class="pull-right">
                        <form action="{{ route('dominion.intelligence') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="spell_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="type" value="spell">
                            <input type="hidden" name="operation" value="clear_sight">
                            <button type="submit" class="btn btn-sm btn-primary">Clear Sight ({{ number_format($spellCalculator->getManaCost($selectedDominion, 'clear_sight')) }} mana)</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'clear_sight']) }}">View Archives</a>
                    </div>
                @endslot
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>This page contains the data that your realmies have gathered about dominion <b>{{ $dominion->name }}</b> from realm {{ $dominion->realm->name }} (#{{ $dominion->realm->number }}).</p>

                    @include('partials.dominion.op-center.labels-explainer')

                </div>
            </div>
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'revelation');
                @endphp

                @slot('title', 'Active Spells')
                @slot('titleIconClass', 'ra ra-fairy-wand')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Cast magic spell 'Revelation' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    <table class="table">
                        <colgroup>
                            <col width="150">
                            <col>
                            <col width="100">
                            <col width="200">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Spell</th>
                                <th>Effect</th>
                                <th class="text-center">Duration</th>
                                <th class="text-center">Cast By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($infoOp->data as $spellOpInfo)
                                @php
                                    $spell = OpenDominion\Models\Spell::where('id', $spellOpInfo['spell_id'])->first();
                                    $castByDominion = OpenDominion\Models\Dominion::with('realm')->findOrFail($spellOpInfo['caster_id']);
                                @endphp
                                <tr>
                                    <td>{{ $spell->name }}</td>
                                    <td>
                                        <ul>
                                        @foreach($spellHelper->getSpellEffectsString($spell, $selectedDominion->race) as $effect)
                                            <li>{{ $effect }}</li>
                                        @endforeach
                                        <ul>
                                    </td>
                                    <td class="text-center">{{ $spellOpInfo['duration'] }} / {{ $spell->duration }} ticks</td>
                                    <td class="text-center">
                                        <a href="{{ route('dominion.realm', $castByDominion->realm->number) }}">{{ $castByDominion->name }} (#{{ $castByDominion->realm->number }})</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    @if ($infoOp !== null)
                        <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                        @if ($infoOp->isInvalid())
                            <span class="label label-danger">Invalid</span>
                        @elseif ($infoOp->isStale())
                            <span class="label label-warning">Stale</span>
                        @endif
                        @if ($infoOp->isInaccurate())
                            <span class="label label-info" data-toggle="tooltip" data-placement="top" title="The information is distorted by magic, consult the Scribes">Inaccurate</span>
                        @endif
                    @endif

                    <div class="pull-right">
                        <form action="{{ route('dominion.intelligence') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="spell_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="type" value="spell">
                            <input type="hidden" name="operation" value="revelation">
                            <button type="submit" class="btn btn-sm btn-primary">Revelation ({{ number_format($spellCalculator->getManaCost($selectedDominion, 'revelation')) }} mana)</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'revelation']) }}">View Archives</a>
                    </div>
                @endslot
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'castle_spy');
                @endphp

                @slot('title', 'Improvements')
                @slot('titleIconClass', 'fa fa-arrow-up')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Castle Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    <table class="table">
                        <colgroup>
                            <col width="150">
                            <col>
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <td>Part</td>
                                <td>Rating</td>
                                <td class="text-center">Invested</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($improvementHelper->getImprovementTypes($dominion) as $improvementType)
                                <tr>
                                    <td>
                                      <i class="ra ra-{{ $improvementHelper->getImprovementIcon($improvementType) }} ra-fw" data-toggle="tooltip" data-placement="top" title="{{ $improvementHelper->getImprovementHelpString($improvementType, $selectedDominion) }}"></i>
                                        {{ ucfirst($improvementType) }}
                                    </td>
                                    <td>
                                        {{ sprintf(
                                            $improvementHelper->getImprovementRatingString($improvementType),
                                            number_format((array_get($infoOp->data, "{$improvementType}.rating") * 100), 2)
                                        ) }}
                                    </td>
                                    <td class="text-center">{{ number_format(array_get($infoOp->data, "{$improvementType}.points")) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    @if ($infoOp !== null)
                        <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                        @if ($infoOp->isInvalid())
                            <span class="label label-danger">Invalid</span>
                        @elseif ($infoOp->isStale())
                            <span class="label label-warning">Stale</span>
                        @endif
                        @if ($infoOp->isInaccurate())
                            <span class="label label-info" data-toggle="tooltip" data-placement="top" title="The information is distorted by magic, consult the Scribes">Inaccurate</span>
                        @endif
                    @endif

                    <div class="pull-right">
                        <form action="{{ route('dominion.intelligence') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="espionage_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="type" value="espionage">
                            <input type="hidden" name="operation" value="castle_spy">
                            <button type="submit" class="btn btn-sm btn-primary">Castle Spy</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'castle_spy']) }}">View Archives</a>
                    </div>
                @endslot
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'barracks_spy');
                @endphp

                @slot('title', 'Units in training and home')
                @slot('titleIconClass', 'ra ra-sword')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Barracks Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    <table class="table">
                        <colgroup>
                            <col>
                            @for ($i = 1; $i <= 12; $i++)
                                <col width="20">
                            @endfor
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Unit</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    <th class="text-center">{{ $i }}</th>
                                @endfor
                                <th class="text-center">Home (Training)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Draftees</td>
                                <td colspan="12">&nbsp;</td>
                                <td class="text-center">
                                    {{ number_format(array_get($infoOp->data, 'units.home.draftees', 0)) }}
                                </td>
                            </tr>
                            @foreach ($unitHelper->getUnitTypes() as $unitType)
                                <tr>
                                    <td>
                                      <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $dominion->race) }}">
                                        {{ $unitHelper->getUnitName($unitType, $dominion->race) }}
                                      </span>
                                    </td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $amount = array_get($infoOp->data, "units.training.{$unitType}.{$i}", 0);
                                        @endphp
                                        <td class="text-center">
                                            @if ($amount === 0)
                                                -
                                            @else
                                                {{ number_format($amount) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        @php
                                            $unitsAtHome = (int)array_get($infoOp->data, "units.home.{$unitType}");
                                        @endphp

                                        @if (in_array($unitType, ['spies', 'wizards', 'archmages']))
                                            ???
                                        @elseif ($unitsAtHome !== 0)
                                            {{ number_format($unitsAtHome) }}
                                        @else
                                            0
                                        @endif

                                        @if ($amountTraining = array_get($infoOp->data, "units.training.{$unitType}"))
                                            ({{ number_format(array_sum($amountTraining)) }})
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    @if ($infoOp !== null)
                        <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                        @if ($infoOp->isInvalid())
                            <span class="label label-danger">Invalid</span>
                        @elseif ($infoOp->isStale())
                            <span class="label label-warning">Stale</span>
                        @endif
                        @if ($infoOp->isInaccurate())
                            <span class="label label-info" data-toggle="tooltip" data-placement="top" title="The information is distorted by magic, consult the Scribes">Inaccurate</span>
                        @endif
                    @endif

                    <div class="clearfix pull-right">
                        <form action="{{ route('dominion.intelligence') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="espionage_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="type" value="espionage">
                            <input type="hidden" name="operation" value="barracks_spy">
                            <button type="submit" class="btn btn-sm btn-primary">Barracks Spy</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'barracks_spy']) }}">View Archives</a>
                    </div>
                @endslot
            @endcomponent
        </div>
        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'barracks_spy');
                @endphp

                @slot('title', 'Units returning from battle')
                @slot('titleIconClass', 'fa fa-clock-o')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Barracks Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    <table class="table">
                        <colgroup>
                            <col>
                            @for ($i = 1; $i <= 12; $i++)
                                <col width="20">
                            @endfor
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Unit</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    <th class="text-center">{{ $i }}</th>
                                @endfor
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (range(1, 4) as $slot)
                                @php
                                    $unitType = ('unit' . $slot);
                                @endphp
                                <tr>
                                    <td>
                                      <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $dominion->race) }}">
                                        {{ $unitHelper->getUnitName($unitType, $dominion->race) }}
                                      </span>
                                    </td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $amount = array_get($infoOp->data, "units.returning.{$unitType}.{$i}", 0);
                                        @endphp
                                        <td class="text-center">
                                            @if ($amount === 0)
                                                -
                                            @else
                                                {{ number_format($amount) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        @if ($amountTraining = array_get($infoOp->data, "units.returning.{$unitType}"))
                                            ~{{ number_format(array_sum($amountTraining)) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'survey_dominion');
                @endphp

                @slot('title', 'Constructed Buildings')
                @slot('titleIconClass', 'fa fa-home')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Survey Dominion' to reveal information.</p>
                @else
                    @slot('noPadding', true)
                    @slot('titleExtra')
                        <span class="pull-right">Barren Land: <strong>{{ number_format(array_get($infoOp->data, 'barren_land')) }}</strong> ({{ number_format(((array_get($infoOp->data, 'barren_land') / $landCalculator->getTotalLand($dominion)) * 100), 2) }}%)</span>
                    @endslot

                    <table class="table">
                        <colgroup>
                            <col>
                            <col width="100">
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Building Type</th>
                                <th class="text-center">Amount</th>
                                <th class="text-center">% of land</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($buildingHelper->getBuildingsByRace($dominion->race) as $building)
                                @php
                                    $amount = array_get($infoOp->data, "constructed.{$building->key}");
                                @endphp
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                            {{ $building->name }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ number_format($amount) }}</td>
                                    <td class="text-center">{{ number_format((($amount / $landCalculator->getTotalLand($dominion)) * 100), 2) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    @if ($infoOp !== null)
                        <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                        @if ($infoOp->isInvalid())
                            <span class="label label-danger">Invalid</span>
                        @elseif ($infoOp->isStale())
                            <span class="label label-warning">Stale</span>
                        @endif
                        @if ($infoOp->isInaccurate())
                            <span class="label label-info" data-toggle="tooltip" data-placement="top" title="The information is distorted by magic, consult the Scribes">Inaccurate</span>
                        @endif
                    @endif

                    <div class="pull-right">
                        <form action="{{ route('dominion.intelligence') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="espionage_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="type" value="espionage">
                            <input type="hidden" name="operation" value="survey_dominion">
                            <button type="submit" class="btn btn-sm btn-primary">Survey Dominion</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'survey_dominion']) }}">View Archives</a>
                    </div>
                @endslot
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'survey_dominion');
                @endphp

                @slot('title', 'Incoming building breakdown')
                @slot('titleIconClass', 'fa fa-clock-o')
                @if(isset($infoOp->data['constructing_land']))
                @slot('titleExtra')
                    <span class="pull-right">Incoming Buildings: <strong>{{ number_format(array_get($infoOp->data, 'constructing_land')) }}</strong> ({{ number_format(((array_get($infoOp->data, 'constructing_land') / $landCalculator->getTotalLand($dominion)) * 100), 2) }}%)</span>
                @endslot
                @endif

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Survey Dominion' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    <table class="table">
                        <colgroup>
                            <col>
                            @for ($i = 1; $i <= 12; $i++)
                                <col width="20">
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
                            @foreach ($buildingHelper->getBuildingsByRace($dominion->race) as $building)
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                            {{ $building->name }}
                                        </span>
                                    </td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $amount = array_get($infoOp->data, "constructing.{$building->key}.{$i}", 0);
                                        @endphp
                                        <td class="text-center">
                                            @if ($amount === 0)
                                                -
                                            @else
                                                {{ number_format($amount) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        @if ($amountConstructing = array_get($infoOp->data, "constructing.{$building->key}"))
                                            {{ number_format(array_sum($amountConstructing)) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'land_spy');
                @endphp

                @slot('title', 'Explored Land')
                @slot('titleIconClass', 'ra ra-honeycomb')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Land Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    <table class="table">
                        <colgroup>
                            <col>
                            <col width="100">
                            <col width="100">
                            <col width="100">
                            @if ($dominion->race->getPerkValue('land_improvements'))
                                <col width="100">
                            @endif
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Land Type</th>
                                <th class="text-center">Number</th>
                                <th class="text-center">% of total</th>
                                <th class="text-center">Barren</th>
                                @if ($dominion->race->getPerkValue('land_improvements') or $dominion->race->getPerkValue('defense_from_forest'))
                                    <th class="text-center">Bonus</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($landHelper->getLandTypes() as $landType)
                                <tr>
                                    <td>
                                        {{ ucfirst($landType) }}
                                        @if ($landType === $dominion->race->home_land_type)
                                            <small class="text-muted"><i>(home)</i></small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format(array_get($infoOp->data, "explored.{$landType}.amount")) }}</td>
                                    <td class="text-center">{{ number_format(array_get($infoOp->data, "explored.{$landType}.percentage"), 2) }}%</td>
                                    <td class="text-center">{{ number_format(array_get($infoOp->data, "explored.{$landType}.barren")) }}</td>

                                    @if ($dominion->race->getPerkValue('land_improvements') and isset($infoOp->data['land_improvements']))
                                        <td class="text-center">
                                              +{{ number_format($infoOp->data['land_improvements'][$landType]*100,2) }}%

                                              @if($landType == 'plain')
                                                  Offensive Power
                                              @elseif($landType == 'mountain')
                                                  Gold Production
                                              @elseif($landType == 'swamp')
                                                   Wizard Strength
                                              @elseif($landType == 'forest')
                                                  Max Population
                                              @elseif($landType == 'hill')
                                                  Defensive Power
                                              @elseif($landType == 'water')
                                                  Food and Boat Production
                                              @endif
                                        </td>
                                    @endif

                                    @if ($dominion->race->getPerkValue('defense_from_forest') and isset($infoOp->data['landtype_defense']))
                                        <td class="text-center">
                                            @if($infoOp->data['landtype_defense'][$landType] !== 0)
                                                +{{ number_format($infoOp->data['landtype_defense'][$landType]*100,2) }}% Defensive Power
                                            @else
                                                &mdash;
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    @if ($infoOp !== null)
                        <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                        @if ($infoOp->isInvalid())
                            <span class="label label-danger">Invalid</span>
                        @elseif ($infoOp->isStale())
                            <span class="label label-warning">Stale</span>
                        @endif
                        @if ($infoOp->isInaccurate())
                            <span class="label label-info" data-toggle="tooltip" data-placement="top" title="The information is distorted by magic, consult the Scribes">Inaccurate</span>
                        @endif
                    @endif

                    <div class="pull-right">
                        <form action="{{ route('dominion.intelligence') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="espionage_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="type" value="espionage">
                            <input type="hidden" name="operation" value="land_spy">
                            <button type="submit" class="btn btn-sm btn-primary">Land Spy</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'land_spy']) }}">View Archives</a>
                    </div>
                @endslot
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'land_spy');
                @endphp

                @slot('title', 'Incoming land breakdown')
                @slot('titleIconClass', 'fa fa-clock-o')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Land Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    <table class="table">
                        <colgroup>
                            <col>
                            @for ($i = 1; $i <= 12; $i++)
                                <col width="20">
                            @endfor
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Land Type</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    <th class="text-center">{{ $i }}</th>
                                @endfor
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($landHelper->getLandTypes() as $landType)
                                <tr>
                                    <td>
                                        {{ ucfirst($landType) }}
                                        @if ($landType === $dominion->race->home_land_type)
                                            <small class="text-muted"><i>(home)</i></small>
                                        @endif
                                    </td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $amount = array_get($infoOp->data, "incoming.{$landType}.{$i}", 0);
                                        @endphp
                                        <td class="text-center">
                                            @if ($amount === 0)
                                                -
                                            @else
                                                {{ number_format($amount) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        @if ($amountIncoming = array_get($infoOp->data, "incoming.{$landType}"))
                                            {{ number_format(array_sum($amountIncoming)) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'vision');
                @endphp

                @slot('title', 'Technological Advancements')
                @slot('titleIconClass', 'fa fa-flask')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Cast magic spell 'Vision' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    <table class="table">
                        <colgroup>
                            <col width="150">
                            <col>
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Advancement</th>
                                <th>Level</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($infoOp->data['advancements'] as $advancement)
                                @php
                                    $tech = OpenDominion\Models\Tech::where('key', $advancement['key'])->firstOrFail();
                                @endphp
                                <tr>
                                    <td>{{ $advancement['name'] }}</td>
                                    <td>{{ $advancement['level'] }}</td>
                                    <td>{{ $techHelper->getTechDescription($tech) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    @if ($infoOp !== null)
                        <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                        @if ($infoOp->isInvalid())
                            <span class="label label-danger">Invalid</span>
                        @elseif ($infoOp->isStale())
                            <span class="label label-warning">Stale</span>
                        @endif
                        @if ($infoOp->isInaccurate())
                            <span class="label label-info" data-toggle="tooltip" data-placement="top" title="The information is distorted by magic, consult the Scribes">Inaccurate</span>
                        @endif
                    @endif

                    <div class="pull-right">
                        <form action="{{ route('dominion.intelligence') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="spell_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="type" value="spell">
                            <input type="hidden" name="operation" value="vision">
                            <button type="submit" class="btn btn-sm btn-primary">Vision ({{ number_format($spellCalculator->getManaCost($selectedDominion, 'vision')) }} mana)</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'vision']) }}">View Archives</a>
                    </div>
                @endslot
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @slot('title', 'Heroes')
                @slot('titleIconClass', 'ra ra-knight-helmet')
                <p>Not yet implemented.</p>
            @endcomponent
        </div>
    </div>
@endsection
