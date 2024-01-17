@extends('layouts.topnav')
@section('title', "Chronicles | Round {$dominion->round->number} | {$dominion->name}")

@section('content')

<div class="row">
    <div class="col-sm-12 col-md-9">
        @component('partials.dominion.insight.box')
        @slot('title', ('The Dominion of ' . $dominion->name))
        @slot('titleIconClass', 'fa fa-chart-bar')
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
                                <td>
                                    @if(!$dominion->isAbandoned())
                                        <a href="{{ route('chronicles.ruler', $dominion->user->display_name) }}">
                                    @endif
                                        @if(isset($dominion->title->name))
                                              <em>
                                                  <span data-toggle="tooltip" data-placement="top" title="{!! $titleHelper->getRulerTitlePerksForDominion($dominion) !!}">
                                                      {{ $dominion->title->name }}
                                                  </span>
                                              </em>
                                        @endif

                                        {{ $dominion->ruler_name }}
                                    </a>

                                </td>
                            </tr>
                            <tr>
                                <td>Faction:</td>
                                <td>
                                    <span data-toggle="tooltip" data-placement="top" title="{!! $raceHelper->getRacePerksHelpString($dominion->race) !!}">
                                        {{ $dominion->race->name }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Land:</td>
                                <td>
                                    {{ number_format($dominion->land) }}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="">
                                        {{ str_plural($raceHelper->getPeasantsTerm($dominion->race)) }}:</td>
                                    </span>
                                <td>{{ number_format($dominion->peasants) }}</td>
                            </tr>
                            <tr>
                                <td>Employment:</td>
                                <td>{{ number_format($populationCalculator->getEmploymentPercentage($dominion), 2) }}%</td>
                            </tr>
                            <tr>
                                <td>Networth:</td>
                                <td>{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}</td>
                            </tr>
                            <tr>
                                <td>Prestige:</td>
                                <td>
                                    <span data-toggle="tooltip" data-placement="top" title="<em>Effective: {{ number_format(floor($dominion->prestige)) }}<br>Actual: {{ number_format((float)$dominion->prestige,8) }}<br>Interest: {{ number_format((float)$productionCalculator->getPrestigeInterest($dominion),8) }}</em>">
                                        {{ number_format(floor($dominion->prestige)) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Victories:</td>
                                <td>{{ $statsService->getStat($dominion, 'invasion_victories') }}</td>
                            </tr>
                            <tr>
                                <td>Net Victories:</td>
                                <td>{{ $militaryCalculator->getNetVictories($dominion) }}</td>
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
                            @foreach($dominion->race->resources as $resourceKey)
                                @php
                                    $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                @endphp
                                <tr>
                                    <td>{{ $resource->name }}:</td>
                                    <td>{{ number_format($resourceCalculator->getAmount($dominion, $resourceKey)) }}</td>
                                </tr>
                            @endforeach
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
                                <td>{{ number_format($dominion->morale) }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getDrafteeHelpString($dominion->race, $dominion) }}">
                                        {{ $raceHelper->getDrafteesTerm($dominion->race) }}:
                                    </span>
                                </td>
                                <td>{{ number_format($dominion->military_draftees) }}</td>
                            </tr>
                            @foreach($dominion->race->units as $unit)
                                <tr>
                                    <td>
                                    <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString(('unit' . $unit->slot), $dominion->race, [$militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unit, 'offense'), $militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unit, 'defense'), ]) }}">
                                        {{ $unit->name }}:
                                    </span>
                                    </td>
                                    <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($dominion, $unit->slot)) }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td>Spies:</td>
                                <td>{{ number_format($dominion->military_spies) }}</td>
                            </tr>
                            <tr>
                                <td>Wizards:</td>
                                <td>{{ number_format($dominion->military_wizards) }}</td>
                            </tr>
                            <tr>
                                <td>ArchMages:</td>
                                <td>{{ number_format($dominion->military_archmages) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endcomponent
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-body text-center">
                <a href="{{ route('chronicles.round', $dominion->round) }}">
                    <button class="btn btn-primary btn-block" type="submit" id="capture">Round {{ $dominion->round->number }}</button>
                </a>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-pray"></i> Deity</h3>
            </div>
            <div class="box-body">
                @if(!$dominion->hasDeity())
                    <p>This dominion was not devoted to a deity.</p>
                @elseif($dominion->hasPendingDeitySubmission())
                    <p>This dominion was in the process of submitting to a deity.</p>
                @else
                    @php
                        $perksList = '<ul>';
                        $perksList .= '<li>Devotion: ' . number_format($dominion->devotion->duration) . ' ' . str_plural('tick', $dominion->devotion->duration) . '</li>';
                        $perksList .= '<li>Range multiplier: ' . $dominion->deity->range_multiplier . 'x</li>';
                        foreach($deityHelper->getDeityPerksString($dominion->deity, $dominion->getDominionDeity()) as $effect)
                        {
                            $perksList .= '<li>' . ucfirst($effect) . '</li>';
                        }
                        $perksList .= '<ul>';
                    @endphp
                    <p>This dominion was devoted to <b>{{ $dominion->deity->name }}</b>.</p>

                    <ul>
                    <li>Devotion: {{ number_format($dominion->devotion->duration) . ' ' . str_plural('tick', $dominion->devotion->duration) }}</li>
                    <li>Range multiplier: {{ $dominion->deity->range_multiplier }}x</li>
                    @foreach($deityHelper->getDeityPerksString($dominion->deity, $dominion->getDominionDeity()) as $effect)

                        <li>{{ ucfirst($effect) }}</li>

                    @endforeach
                    </ul>

                @endif
            </div>
        </div>

        @if($spellCalculator->hasAnnexedDominions($dominion))
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-castle-flag"></i> Annexed dominions</h3>
                </div>
                <div class="box-body">
                    <p>The Legion has has annexed Barbarians, providing <b>{{ number_format($militaryCalculator->getRawMilitaryPowerFromAnnexedDominions($dominion)) }}</b> additional raw military power.</p>

                    <ul>
                    @foreach($spellCalculator->getAnnexedDominions($dominion) as $barbarian)
                        <li><a href="{{ route('dominion.insight.show', $barbarian) }}">{{ $barbarian->name }}</a> ({{ $spellCalculator->getTicksRemainingOfAnnexation($dominion, $barbarian) . ' ' . str_plural('tick', $spellCalculator->getTicksRemainingOfAnnexation($dominion, $barbarian))}} remaining)</li>
                    @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if ($dominionHelper->isEnraged($dominion))
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-explosion"></i> Enraged</h3>
                </div>
                <div class="box-body">
                    <p>Because they were recently invaded, the Leshy and Spriggan are enraged.</p>
                </div>
            </div>
        @endif

        @if($spellCalculator->isAnnexed($dominion))
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-castle-flag"></i> Annexation</h3>
                </div>
                <div class="box-body">
                    <p>This dominion was annexed, providing the Legion with <b>{{ number_format($militaryCalculator->getRawMilitaryPowerFromAnnexedDominion($dominion)) }}</b> additional raw military power.</p>
                </div>
            </div>
        @endif
    </div>

</div>

<div class="row">
    <div class="col-sm-12 col-md-6">
        @component('partials.dominion.insight.box')

            @slot('title', 'Active Spells')
            @slot('titleIconClass', 'ra ra-fairy-wand')
            @slot('noPadding', true)
            @php
                $activePassiveSpells = $spellCalculator->getPassiveSpellsCastOnDominion($dominion);
            @endphp

            @if(count($activePassiveSpells) > 0)

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
                        @foreach ($activePassiveSpells as $dominionSpell)
                            @if($dominionSpell->duration > 0)
                                @php
                                    $spell = OpenDominion\Models\Spell::where('id', $dominionSpell->spell_id)->first();
                                    $caster = $spellCalculator->getCaster($dominion, $spell->key);
                                @endphp
                                <tr>
                                    <td>{{ $spell->name }}</td>
                                    <td>
                                        <ul>
                                        @foreach($spellHelper->getSpellEffectsString($spell, $dominion->race) as $effect)
                                            <li>{{ $effect }}</li>
                                        @endforeach
                                        <ul>
                                    </td>
                                    <td class="text-center">{{ $dominionSpell->duration . ' ' . str_plural('tick', $dominionSpell->duration)}} </td>
                                    <td class="text-center">
                                        <a href="{{ route('chronicles.dominion', $caster) }}">{{ $caster->name }} (#{{ $caster->realm->number }})</a>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="box-body">
                    <p>There are currently no spells affecting this dominion.</p>
                </div>
            @endif
        @endcomponent
    </div>

    <div class="col-sm-12 col-md-6">
        @component('partials.dominion.insight.box')

            @slot('title', 'Improvements')
            @slot('titleIconClass', 'fa fa-arrow-up')
            @slot('noPadding', true)

            <table class="table">
                <colgroup>
                    <col width="150">
                    <col>
                    <col width="200">
                </colgroup>
                <thead>
                    <tr>
                        <th>Improvement</th>
                        <th>Perks</th>
                        <th class="text-center">Invested</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($improvementHelper->getImprovementsByRace($dominion->race) as $improvement)
                        <tr>
                            <td><span data-toggle="tooltip" data-placement="top"> {{ $improvement->name }}</span></td>
                            <td>
                                @foreach($improvement->perks as $perk)
                                    @php
                                        $improvementPerkMax = $dominion->extractImprovementPerkValues($perk->pivot->value)[0];
                                        $improvementPerkMaxMultiplier = 1;
                                        $improvementPerkMaxMultiplier += $dominion->getBuildingPerkMultiplier('improvements');
                                        $improvementPerkMaxMultiplier += $dominion->getBuildingPerkMultiplier('improvements_capped')
                                        $improvementPerkMaxMultiplier += $dominion->getBuildingPerkMultiplier('quadratic_improvements_mod');
                                        $improvementPerkMaxMultiplier += $dominion->getAdvancementPerkMultiplier('improvements')
                                        $improvementPerkMaxMultiplier += $dominion->getSpellPerkMultiplier('improvements')
                                        $improvementPerkMaxMultiplier += $dominion->race->getPerkMultiplier('improvements_max');

                                        $improvementPerkMax *= $improvementPerkMaxMultiplier;
                                        
                                        $improvementPerkCoefficient = $dominion->extractImprovementPerkValues($perk->pivot->value)[1];

                                        $spanClass = 'text-muted';

                                        if($improvementPerkMultiplier = $dominion->getImprovementPerkMultiplier($perk->key))
                                        {
                                            $spanClass = '';
                                        }
                                    @endphp

                                    <span class="{{ $spanClass }}" data-toggle="tooltip" data-placement="top" title="Max: {{ number_format($improvementPerkMax,2) }}%<br>Coefficient: {{ number_format($improvementPerkCoefficient) }}">

                                    @if($improvementPerkMultiplier > 0)
                                        +{{ number_format($improvementPerkMultiplier * 100, 2) }}%
                                    @else
                                        {{ number_format($improvementPerkMultiplier * 100, 2) }}%
                                    @endif

                                     {{ $improvementHelper->getImprovementPerkDescription($perk->key) }} <br></span>

                                @endforeach
                            </td>
                            <td class="text-center">{{ number_format($improvementCalculator->getDominionImprovementAmountInvested($dominion, $improvement)) }}</td>
                        </tr>
                    @endforeach
                        <tr>
                            <td colspan="2" class="text-right"><strong>Total</strong></td>
                            <td class="text-center">{{ number_format($improvementCalculator->getDominionImprovementTotalAmountInvested($dominion)) }}</td>
                        </tr>

                    @php
                        $totalSabotaged = 0;
                        foreach($queueService->getSabotageQueue($dominion) as $sabotage)
                        {
                            if($sabotage->resource === 'improvements')
                            {
                                $totalSabotaged += $sabotage->amount;
                            }
                        }
                    @endphp

                    @if($totalSabotaged > 0)
                        <tr>
                            <td colspan="2" class="text-right"><strong>Sabotaged</strong><br><small class="text-muted">Will be restored automatically</small></td>
                            <td class="text-center">{{ number_format($totalSabotaged) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endcomponent
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-6">
        @component('partials.dominion.insight.box')

            @slot('title', 'Units in training and home')
            @slot('titleIconClass', 'ra ra-sword')

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
                        <th class="text-center">Home<br>(Training)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($unitHelper->getUnitTypes($dominion->race) as $unitType)
                        <tr>
                            <td>
                              <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $dominion->race, [$militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unitHelper->getUnitFromRaceUnitType($dominion->race, $unitType), 'offense'), $militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unitHelper->getUnitFromRaceUnitType($dominion->race, $unitType), 'defense'), ]) }}">
                                {{ $unitHelper->getUnitName($unitType, $dominion->race) }}
                              </span>
                            </td>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $amount = $queueService->getTrainingQueueAmount($dominion, "military_{$unitType}", $i);
                                    $amount += $queueService->getSummoningQueueAmount($dominion, "military_{$unitType}", $i);
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
                                {{ number_format($dominion->{'military_' . $unitType}) }}
                                ({{ number_format($queueService->getTrainingQueueTotalByResource($dominion, "military_{$unitType}") + $queueService->getSummoningQueueTotalByResource($dominion, "military_{$unitType}")) }})
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endcomponent
    </div>
    <div class="col-sm-12 col-md-6">
        @component('partials.dominion.insight.box')

            @slot('title', 'Units returning')
            @slot('titleIconClass', 'ra ra-boot-stomp')
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
                        <th class="text-center"><br>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($unitHelper->getUnitTypes($dominion->race) as $unitType)
                        <tr>
                            <td>
                              <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $dominion->race, [$militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unitHelper->getUnitFromRaceUnitType($dominion->race, $unitType), 'offense'), $militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unitHelper->getUnitFromRaceUnitType($dominion->race, $unitType), 'defense'), ]) }}">
                                {{ $unitHelper->getUnitName($unitType, $dominion->race) }}
                              </span>
                            </td>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $amount = $queueService->getInvasionQueueAmount($dominion, "military_{$unitType}", $i);
                                    $amount += $queueService->getExpeditionQueueAmount($dominion, "military_{$unitType}", $i);
                                    $amount += $queueService->getTheftQueueAmount($dominion, "military_{$unitType}", $i);
                                    $amount += $queueService->getSabotageQueueAmount($dominion, "military_{$unitType}", $i);
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
                                {{ number_format($queueService->getInvasionQueueTotalByResource($dominion, "military_{$unitType}") + $queueService->getExpeditionQueueTotalByResource($dominion, "military_{$unitType}") + $queueService->getTheftQueueTotalByResource($dominion, "military_{$unitType}") + $queueService->getSabotageQueueTotalByResource($dominion, "military_{$unitType}")) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endcomponent
    </div>
</div>
<div class="row">
    <div class="col-sm-12 col-md-6">
        @component('partials.dominion.insight.box')

            @slot('title', 'Buildings')
            @slot('titleIconClass', 'fa fa-home')
            @slot('noPadding', true)
            @slot('titleExtra')
                @if($dominion->race->name == 'Swarm')
                    <span class="pull-right" data-toggle="tooltip" data-placement="top" title="Barren vs Swarm: <strong>{{ number_format($landCalculator->getTotalBarrenLandForSwarm($dominion)) }}</strong> ({{ number_format((($landCalculator->getTotalBarrenLandForSwarm($dominion) / $dominion->land) * 100), 2) }}%)">
                @else
                    <span class="pull-right">
                @endif
                    Barren Land: <strong>{{ number_format($landCalculator->getTotalBarrenLand($dominion)) }}</strong> ({{ number_format((($landCalculator->getTotalBarrenLand($dominion) / $dominion->land) * 100), 2) }}%)</span>
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
                            $amount = $buildingCalculator->getBuildingAmountOwned($dominion, $building);
                        @endphp
                        <tr>
                            <td>
                                <span data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                    {{ $building->name }}
                                </span>
                            </td>
                            <td class="text-center">{{ number_format($amount) }}</td>
                            <td class="text-center">{{ number_format((($amount / $dominion->land) * 100), 2) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endcomponent
    </div>
    <div class="col-sm-12 col-md-6">
        @component('partials.dominion.insight.box')

            @slot('title', 'Incoming buildings')
            @slot('titleIconClass', 'fa fa-clock-o')
            @slot('titleExtra')
                <span class="pull-right">Incoming Buildings: <strong>{{ number_format($queueService->getConstructionQueueTotal($dominion)) }}</strong> ({{ number_format((($queueService->getConstructionQueueTotal($dominion) / $dominion->land) * 100), 2) }}%)</span>
            @endslot
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
                                    $amount = $queueService->getConstructionQueueAmount($dominion, "building_{$building->key}", $i);
                                @endphp
                                <td class="text-center">
                                    @if ($amount === 0)
                                        -
                                    @else
                                        {{ number_format($amount) }}
                                    @endif
                                </td>
                            @endfor
                            <td class="text-center">{{ number_format($queueService->getConstructionQueueTotalByResource($dominion, "building_{$building->key}")) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endcomponent
    </div>
</div>
<div class="row">

    <div class="col-sm-12 col-md-6 }} ">
        @component('partials.dominion.insight.box')

            @slot('title', 'Land')
            @slot('titleIconClass', 'ra ra-honeycomb')
            <table class="table">
                <colgroup>
                    <col>
                    <col width="100">
                    <col width="100">
                    <col width="100">
                </colgroup>
                <thead>
                    <tr>
                        <th>Land Type</th>
                        <th class="text-center">Number</th>
                        <th class="text-center">% of total</th>
                        <th class="text-center">Barren</th>
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
                            <td class="text-center">{{ number_format($dominion->{'land_' . $landType}) }}</td>
                            <td class="text-center">{{ number_format(($dominion->{'land_' . $landType} / $dominion->land) * 100, 2) }}%</td>
                            <td class="text-center">{{ number_format($landCalculator->getTotalBarrenLandByLandType($dominion, $landType)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endcomponent
    </div>

    <div class="col-sm-12 col-md-6 ">
        @component('partials.dominion.insight.box')

            @slot('title', 'Incoming land breakdown')
            @slot('titleIconClass', 'fa fa-clock-o')
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
                                    $amount = $queueService->getExplorationQueueAmount($dominion, "land_{$landType}", $i);
                                    $amount += $queueService->getInvasionQueueAmount($dominion, "land_{$landType}", $i);
                                    $amount += $queueService->getExpeditionQueueAmount($dominion, "land_{$landType}", $i);
                                @endphp
                                <td class="text-center">
                                    @if ($amount === 0)
                                        -
                                    @else
                                        {{ number_format($amount) }}
                                    @endif
                                </td>
                            @endfor
                            <td class="text-center">{{ number_format($queueService->getExplorationQueueTotalByResource($dominion, "land_{$landType}") + $queueService->getInvasionQueueTotalByResource($dominion, "land_{$landType}")  + $queueService->getExpeditionQueueTotalByResource($dominion, "land_{$landType}")) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endcomponent
    </div>

</div>
<div class="row">

    <div class="col-sm-12 col-md-6">
        @component('partials.dominion.insight.box')

            @slot('title', 'Advancements')
            @slot('titleIconClass', 'fa fa-flask')
            @slot('noPadding', true)

            @if(count($advancements) > 0)
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
                        @foreach ($advancements as $advancement)
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
            @else
                <div class="box-body">
                    <p>There are currently no advancements affecting this dominion.</p>
                </div>
            @endif
        @endcomponent
    </div>

    <div class="col-sm-12 col-md-6">
        @component('partials.dominion.insight.box')
            @slot('title', 'Statistics')
            @slot('titleIconClass', 'fa fa-chart-bar')
            @slot('noPadding', true)

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
                            <strong>{{ number_format($statsService->getStat($dominion, 'invasion_victories')) }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Bottomfeeds</td>
                        <td>
                            <strong>{{ number_format($statsService->getStat($dominion, 'invasion_bottomfeeds')) }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Tactical razes</td>
                        <td>
                            <strong>{{ number_format($statsService->getStat($dominion, 'invasion_razes')) }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Overwhelmed failures</td>
                        <td>
                            <strong>{{ number_format($statsService->getStat($dominion, 'invasion_failures')) }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Land conquered</td>
                        <td>
                            <strong>{{ number_format($statsService->getStat($dominion, 'land_conquered')) }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Land discovered</td>
                        <td>
                            <strong>{{ number_format($statsService->getStat($dominion, 'land_discovered')) }}</strong>
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
                            <strong>{{ number_format($statsService->getStat($dominion, 'defense_success')) }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Invasions lost</td>
                        <td>
                            <strong>{{ number_format($statsService->getStat($dominion, 'defense_failures')) }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Land lost</td>
                        <td>
                            <strong>{{ number_format($statsService->getStat($dominion, 'land_lost')) }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Land explored</td>
                        <td>
                            <strong>{{ number_format($statsService->getStat($dominion, 'land_explored')) }}</strong>
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
                        <th colspan="2">Enemy Units</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Enemy units killed</td>
                        <td>
                            <strong>{{ number_format($statsService->getStat($dominion, 'units_killed')) }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Total units converted</td>
                        <td>
                            <strong>{{ number_format($statsService->getStat($dominion, 'units_converted')) }}</strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        @endcomponent
    </div>
</div>
@endsection
