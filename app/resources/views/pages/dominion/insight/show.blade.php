@extends('layouts.master')
@section('title', "Insight | $dominion->name")

@section('content')

@if(!$dominion->round->hasStarted() or $protectionService->isUnderProtection($dominion))
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-eye-slash"></i> Insight unavailable</h3>
            </div>
            <div class="box-body">
                @if(!$dominion->round->hasStarted())
                    <p>The round has not started yet.</p>
                @elseif($protectionService->isUnderProtection($dominion))
                    <p>This dominion is under protection.</p>
                @else
                    <p>Insight is not available for this dominion right now.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@elseif($dominion->getSpellPerkValue('fog_of_war'))
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-cloud"></i> Fog of war</h3>
            </div>
            <div class="box-body">
                <p>This dominion is temporarily hidden from insight.</p>
                <p><em>Strike at your own risk!</em></p>
                @if($insightHelper->getArchiveCount($dominion, $selectedDominion) > 0)
                    <p>
                        <a href="{{ route('dominion.insight.archive', $dominion) }}">View Archive</a> ({{ number_format($insightHelper->getArchiveCount($dominion, $selectedDominion)) }})
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-body text-center">
                @if($selectedDominion->isWatchingDominion($dominion))
                    <form action="{{ route('dominion.insight.unwatch-dominion', $dominion) }}" method="post">
                        @csrf
                        <input type="hidden" name="dominion_id" value="{{ $dominion->id }}">
                        <button class="btn btn-success btn-block" type="submit" id="capture"><i class="fas fa-eye-slash"></i> Unwatch Dominion</button>
                    </form>
                @else
                    <form action="{{ route('dominion.insight.watch-dominion', $dominion) }}" method="post">
                        @csrf
                        <input type="hidden" name="dominion_id" value="{{ $dominion->id }}">
                        <button class="btn btn-primary btn-block" type="submit" id="capture"><i class="fas fa-eye"></i> Watch Dominion</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@else
<div class="row">
    <div class="col-sm-12 col-md-9">
        @component('partials.dominion.insight.box')
        @if(in_array($selectedDominion->round->mode, ['standard', 'standard-duration', 'deathmatch', 'deathmatch-duration']))
            @slot('title', ('The Dominion of ' . $dominion->name))
        @else
            @slot('title', ('The Dominion of ' . $dominion->name) . ' (# ' . $dominion->realm->number . ')')
        @endif
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
                                    @if($dominion->user->hasAvatar())
                                        <img style="display: inline; vertical-align: middle; height: 1em;" src="{{ $dominion->user->getAvatarUrl() }}" alt="Avatar of {{ $dominion->user->display_name }}">
                                    @elseif($dominion->race->key === 'barbarian')
                                        <img style="display: inline; vertical-align: middle; height: 1em;" src="{{ asset('assets/app/images/barbarian.svg') }}" alt="Avatar of {{ $dominion->user->display_name }}">
                                    @else
                                        <img style="display: inline; vertical-align: middle; height: 1em;" src="{{ asset('img/no_avatar.png') }}" alt="Avatar of {{ $dominion->user->display_name }}">
                                    @endif
                                    @if(isset($dominion->title->name))
                                        <em>
                                            <span data-toggle="tooltip" data-placement="top" title="{!! $titleHelper->getRulerTitlePerksForDominion($dominion) !!}">
                                                {{ $dominion->title->name }}
                                            </span>
                                        </em>
                                    @endif
                                
                                    {{ $dominion->ruler_name }}
                                
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
                                    <span class="{{ $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $dominion) }}">
                                        ({{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 2) }}%)
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="">
                                        {{ Str::plural($raceHelper->getPeasantsTerm($dominion->race)) }}:</td>
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
                                    <span data-toggle="tooltip" data-placement="top" title="{{ $dominionHelper->getPrestigeHelpString($dominion) }}">
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
                            <tr>
                                <td>XP:</td>
                                <td>{{ number_format($dominion->xp) }}</td>
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
                                <th colspan="2">Military</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Morale:</td>
                                <td>{{ number_format($dominion->morale) }} / {{ number_format($moraleCalculator->getBaseMorale($dominion)) }}</td>
                            </tr>
                            @if(($peasantDp = $dominion->race->getPerkValue('peasant_dp') + $dominion->getTechPerkValue('peasant_dp')) > 0)
                                <tr>
                                    <td>
                                    <span data-toggle="tooltip" data-placement="top" title="DP: {{ $peasantDp }}">
                                        {{ $raceHelper->getPeasantsTerm($dominion->race) }}:
                                    </span>
                                    </td>
                                    <td>{{ number_format($dominion->peasants) }}</td>
                                </tr>
                            @endif
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
                                <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($dominion, 'spies')) }}</td>
                            </tr>
                            <tr>
                                <td>Wizards:</td>
                                <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($dominion, 'wizards')) }}</td>
                            </tr>
                            <tr>
                                <td>ArchMages:</td>
                                <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($dominion, 'archmages')) }}</td>
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
                  <form action="{{ route('dominion.insight.archive', $dominion) }}" method="post">
                      @csrf
                      <input type="hidden" name="target_dominion_id" value="{{ $dominion->id }}">
                      <input type="hidden" name="round_tick" value="{{ $selectedDominion->round->ticks }}">
                      <button class="btn btn-primary btn-block" type="submit" id="capture">Archive this Insight</button>
                  </form>
                  @if($insightHelper->getArchiveCount($dominion, $selectedDominion) > 0)
                      <p>
                          <a href="{{ route('dominion.insight.archive', $dominion) }}">View Archive</a> ({{ number_format($insightHelper->getArchiveCount($dominion, $selectedDominion)) }})
                      </p>
                  @endif
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-body text-center">
                @if($selectedDominion->isWatchingDominion($dominion))
                    <form action="{{ route('dominion.insight.unwatch-dominion', $dominion) }}" method="post">
                        @csrf
                        <input type="hidden" name="dominion_id" value="{{ $dominion->id }}">
                        <button class="btn btn-success btn-block" type="submit" id="capture"><i class="fas fa-eye-slash"></i> Unwatch Dominion</button>
                    </form>
                @else
                    <form action="{{ route('dominion.insight.watch-dominion', $dominion) }}" method="post">
                        @csrf
                        <input type="hidden" name="dominion_id" value="{{ $dominion->id }}">
                        <button class="btn btn-primary btn-block" type="submit" id="capture"><i class="fas fa-eye"></i> Watch Dominion</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        @if($selectedDominion->race->name == 'Cult')
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-brain-freeze"></i> Psionic Strength</h3>
                </div>
                <div class="box-body">
                    {{ number_format($dominionCalculator->getPsionicStrength($dominion),6) }}
                </div>
            </div>
        @endif

        @if($dominion->hasProtector())
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fas fa-user-shield"></i> Protectorate</h3>
                </div>
                <div class="box-body">
                    <p>This dominion is a protectorate of <a href="{{ route('dominion.insight.show', $dominion->protector) }}">{{ $dominion->protector->name }}</a>.</p>
                </div>
            </div>
        @endif

        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="ra ra-axe"></i> Military</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table">
                    <colgroup>
                        <col width="33%">
                        <col width="33%">
                        <col width="33%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Modifier</th>
                            <th>Offensive</th>
                            <th>Defensive</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Military:</strong></td>
                            <td><strong>{{ number_format(($militaryCalculator->getOffensivePowerMultiplier($dominion) - 1) * 100, 2) }}%</strong></td>
                            <td><strong>{{ number_format(($militaryCalculator->getDefensivePowerMultiplier($dominion) - 1) * 100, 2) }}%</strong></td>
                        </tr>
                        <tr>
                            <td>Enemy modifers:</td>
                            <td>{{ number_format(($militaryCalculator->getOffensiveMultiplierReduction($dominion)-1)*100, 2) }}%</td>
                            <td>{{ number_format($militaryCalculator->getDefensiveMultiplierReduction($dominion)*100, 2) }}%</td>
                        </tr>
                        <tr>
                            <td>Own casualties:</td>
                            <td>{{ number_format(($casualtiesCalculator->getBasicCasualtiesPerkMultipliers($dominion, 'offense'))*100, 2) }}%</td>
                            <td>{{ number_format(($casualtiesCalculator->getBasicCasualtiesPerkMultipliers($dominion, 'defense'))*100, 2) }}%</td>
                        </tr>
                        <tr>
                            <td colspan="3"><p class="text-muted"><small><em>The perks above are the basic, static values and do not take into account circumstantial perks such as perks vs. specific types of targets or perks based on specific unit compositions.</em></small></p></td>
                        </tr>
                        <tr>
                            <td>Spy Ratio:</td>
                            <td>{{ number_format($militaryCalculator->getSpyRatio($dominion, 'offense'), 2) }}</td>
                            <td>{{ number_format($militaryCalculator->getSpyRatio($dominion, 'defense'), 2) }}</td>
                        </tr>
                        <tr>
                            <td>Wizard Ratio:</td>
                            <td>{{ number_format(($magicCalculator->getWizardRatio($dominion, 'offense')), 2) }}</td>
                            <td>{{ number_format(($magicCalculator->getWizardRatio($dominion, 'defense')), 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-pray"></i> Deity</h3>
            </div>
            <div class="box-body">
                @if(!$dominion->hasDeity())
                    <p>This dominion is not currently devoted to a deity.</p>
                @elseif($dominion->hasPendingDeitySubmission())
                    <p>This dominion is currently in the process of submitting to a deity.</p>
                @else
                    @php
                        $perksList = '<ul>';
                        $perksList .= '<li>Devotion: ' . number_format($dominion->devotion->duration) . ' ' . Str::plural('tick', $dominion->devotion->duration) . '</li>';
                        $perksList .= '<li>Range multiplier: ' . $dominion->deity->range_multiplier . 'x</li>';
                        foreach($deityHelper->getDeityPerksString($dominion->deity, $dominion->getDominionDeity()) as $effect)
                        {
                            $perksList .= '<li>' . ucfirst($effect) . '</li>';
                        }
                        $perksList .= '<ul>';
                    @endphp
                    <p>This dominion is devoted to <b>{{ $dominion->deity->name }}</b>.</p>

                    <ul>
                    <li>Devotion: {{ number_format($dominion->devotion->duration) . ' ' . Str::plural('tick', $dominion->devotion->duration) }}</li>
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
                        <li><a href="{{ route('dominion.insight.show', $barbarian) }}">{{ $barbarian->name }}</a> ({{ $spellCalculator->getTicksRemainingOfAnnexation($dominion, $barbarian) . ' ' . Str::plural('tick', $spellCalculator->getTicksRemainingOfAnnexation($dominion, $barbarian))}} remaining)</li>
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
                    <p>This dominion is currently annexed, providing the Legion with <b>{{ number_format($militaryCalculator->getRawMilitaryPowerFromAnnexedDominion($dominion)) }}</b> additional raw military power.</p>
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
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Spell</th>
                            <th>Effect</th>
                            <th class="text-center">Duration</th>
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
                                    <td class="text-center">{{ $dominionSpell->duration . ' ' . Str::plural('tick', $dominionSpell->duration)}} </td>
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
                                        $improvementPerkMax *= $dominion->getImprovementsMod();

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
                        <th class="text-center">Home<br>(Incoming)</th>
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
                                    $amount += $queueService->getEvolutionQueueAmount($dominion, "military_{$unitType}", $i);
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
                                ({{ number_format($queueService->getTrainingQueueTotalByResource($dominion, "military_{$unitType}") + $queueService->getSummoningQueueTotalByResource($dominion, "military_{$unitType}") + $queueService->getEvolutionQueueTotalByResource($dominion, "military_{$unitType}")) }})
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
                                    $amount += $queueService->getDesecrationQueueAmount($dominion, "military_{$unitType}", $i);
                                    $amount += $queueService->getStunQueueAmount($dominion, "military_{$unitType}", $i);
                                    $amount += $queueService->getArtefactattackQueueAmount($dominion, "military_{$unitType}", $i);
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
                                {{ number_format($queueService->getInvasionQueueTotalByResource($dominion, "military_{$unitType}") + $queueService->getExpeditionQueueTotalByResource($dominion, "military_{$unitType}") + $queueService->getTheftQueueTotalByResource($dominion, "military_{$unitType}") + $queueService->getSabotageQueueTotalByResource($dominion, "military_{$unitType}") + $queueService->getDesecrationQueueTotalByResource($dominion, "military_{$unitType}") + $queueService->getStunQueueTotalByResource($dominion, "military_{$unitType}") + $queueService->getArtefactattackQueueTotalByResource($dominion, "military_{$unitType}")) }}
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
                        <th>Building</th>
                        <th class="text-center">Amount</th>
                        <th class="text-center">% of land</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($buildingCalculator->getDominionBuildingsAvailableAndOwned($dominion) as $building)
                        @php
                            $amount = $dominion->{'building_' . $building->key}; #$buildingCalculator->getBuildingAmountOwned($dominion, $building);
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
                <span class="pull-right">Incoming Buildings: <strong>{{ number_format($queueService->getConstructionQueueTotal($dominion) + $queueService->getRepairQueueTotal($dominion)) }}</strong> ({{ number_format(((($queueService->getConstructionQueueTotal($dominion) + $queueService->getRepairQueueTotal($dominion)) / $dominion->land) * 100), 2) }}%)</span>
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
                        <th>Building </th>
                        @for ($i = 1; $i <= 12; $i++)
                            <th class="text-center">{{ $i }}</th>
                        @endfor
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($buildingCalculator->getDominionBuildingsAvailableAndOwned($dominion) as $building)
                        <tr>
                            <td>
                                <span data-toggle="tooltip" data-placement="top" title="{!! $buildingHelper->getBuildingDescription($building) !!}">
                                    {{ $building->name }}
                                </span>
                            </td>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $amount = $queueService->getConstructionQueueAmount($dominion, "building_{$building->key}", $i);
                                    $amount += $queueService->getRepairQueueAmount($dominion, "building_{$building->key}", $i);
                                @endphp
                                <td class="text-center">
                                    @if ($amount === 0)
                                        -
                                    @else
                                        {{ number_format($amount) }}
                                    @endif
                                </td>
                            @endfor
                            <td class="text-center">{{ number_format($queueService->getConstructionQueueTotalByResource($dominion, "building_{$building->key}") + $queueService->getRepairQueueTotalByResource($dominion, "building_{$building->key}")) }}</td>
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

            @slot('title', 'Land')
            @slot('titleIconClass', 'ra ra-honeycomb')
            <table class="table">
                <colgroup>
                    <col width="100">
                    <col>
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th>Terrain</th>
                        <th class="text-center">Amount</th>
                        <th>
                            <span data-toggle="tooltip" data-placement="top" title="Perk value shown is total for each perk (sum of perk values across terrains)">
                                Perks
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dominion->race->raceTerrains as $raceTerrain)
                        <tr>
                            <td>{{ $raceTerrain->terrain->name }}</td>
                            <td class="text-center">
                                {{ number_format($dominion->{'terrain_' . $raceTerrain->terrain->key}) }}
                                <small class="text-muted">({{ number_format(($dominion->{'terrain_' . $raceTerrain->terrain->key} / $dominion->land)*100,2) }}%)</small>
                            </td>
                            <td>
                                @if($raceTerrain->perks->count())
                                    @foreach($raceTerrain->perks as $perk)
                                        @php
                                            $perkValue = $dominion->getTerrainPerkValue($perk->key);
                                            if($terrainHelper->getPerkType($perk->key) == 'mod')
                                            {
                                            $perkValue = $dominion->getTerrainPerkMultiplier($perk->key);
                                            }
                                        @endphp
                                        {!! $terrainHelper->getPerkDescription($perk->key, $perkValue, false) !!}
                                        <br>
                                    @endforeach
                                @else
                                    <em class="text-muted">None</em>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endcomponent
    </div>

    <div class="col-sm-12 col-md-6">
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
                        <th>Terrain</th>
                        @for ($i = 1; $i <= 12; $i++)
                            <th class="text-center">{{ $i }}</th>
                        @endfor
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(OpenDominion\Models\Terrain::all()->sortBy('order') as $terrain)
                        <tr>
                            <td>{{ ucfirst($terrain->name) }}</td>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $amount = $queueService->getExplorationQueueAmount($dominion, "terrain_{$terrain->key}", $i);
                                    $amount += $queueService->getInvasionQueueAmount($dominion, "terrain_{$terrain->key}", $i);
                                    $amount += $queueService->getExpeditionQueueAmount($dominion, "terrain_{$terrain->key}", $i);
                                @endphp
                                <td class="text-center">
                                    @if ($amount === 0)
                                        -
                                    @else
                                        {{ number_format($amount) }}
                                    @endif
                                </td>
                            @endfor
                            <td class="text-center">{{ number_format($queueService->getExplorationQueueTotalByResource($dominion, "terrain_{$terrain->key}") + $queueService->getInvasionQueueTotalByResource($dominion, "terrain_{$terrain->key}")  + $queueService->getExpeditionQueueTotalByResource($dominion, "terrain_{$terrain->key}")) }}</td>
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
            @slot('titleIconClass', 'fas fa-layer-group')
            @slot('noPadding', true)

            @if($dominion->advancements->count() > 0)
                <table class="table">
                    <colgroup>
                        <col width="150">
                        <col width="50">
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Advancement</th>
                            <th>Level</th>
                            <th>Effect</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dominionAdvancements as $dominionAdvancement)
                            @php
                                $advancement = OpenDominion\Models\Advancement::findOrFail($dominionAdvancement->pivot->advancement_id);
                            @endphp
                            <tr>
                                <td>{{ $advancement->name }}</td>
                                <td>{{ $dominionAdvancement->pivot->level }}</td>
                                <td>
                                    <ul>
                                        @foreach($advancement->perks as $perk)
                                        @php
                                            $advancementPerkBase = $dominion->extractAdvancementPerkValues($perk->pivot->value);

                                            $spanClass = 'text-muted';

                                            if($advancementPerkMultiplier = $dominion->getAdvancementPerkMultiplier($perk->key))
                                            {
                                                $spanClass = '';
                                            }
                                        @endphp
                                        <li>
                                            @if($advancementPerkMultiplier > 0)
                                                +{{ number_format($advancementPerkMultiplier * 100, 2) }}%
                                            @else
                                                {{ number_format($advancementPerkMultiplier * 100, 2) }}%
                                            @endif

                                            {{ $advancementHelper->getAdvancementPerkDescription($perk->key) }}
                                        </li>
                                        @endforeach
                                    </ul>
                                </td>
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

            @slot('title', 'Decrees')
            @slot('titleIconClass', 'fas fa-gavel')
            @slot('noPadding', true)

            @if(count($dominionDecreeStates) > 0)
                <table class="table">
                    <colgroup>
                        <col width="150">
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Decree</th>
                            <th>State</th>
                            <th>Cooldown</th>
                            <th>Perks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dominionDecreeStates as $dominionDecreeState)
                            @php
                                $decree = OpenDominion\Models\Decree::findOrFail($dominionDecreeState->decree_id);
                                $decreeState = OpenDominion\Models\DecreeState::findOrFail($dominionDecreeState->decree_state_id);
                            @endphp
                            <tr>
                                <td>{{ $decree->name }}</td>
                                <td>{{ $decreeState->name }}</td>
                                <td>{{ $decreeCalculator->getTicksUntilDominionCanRevokeDecree($dominion, $decree) }}</td>
                                <td>{!! $decreeHelper->getDecreeStateDescription($decreeState) !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="box-body">
                    <p>No decrees have been issued in this dominion.</p>
                </div>
            @endif
        @endcomponent
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-6">
        @component('partials.dominion.insight.box')
            @slot('title', 'Research')
            @slot('titleIconClass', 'fa fa-flask')
            @slot('noPadding', true)

            <table class="table">
                <colgroup>
                    <col width="50%">
                    <col width="50%">
                </colgroup>
                <thead class="hidden-xs">
                    <tr>
                        <th>Research</th>
                        <th>Perks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dominionTechs as $dominionTech)
                        @php
                            $tech = OpenDominion\Models\Tech::findOrFail($dominionTech->id);
                        @endphp
                        <tr>
                            <td>{{ $tech->name }}</td>
                            <td>
                                <ul style="list-style-type: none">
                                    @foreach($researchHelper->getTechPerkDescription($tech, $dominion->race) as $effect)
                                        <li>{{ $effect }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endcomponent
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-12">
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
<div class="row">

    <div class="col-sm-12 col-md-12">
        @component('partials.dominion.insight.box')
            @slot('title', 'Data')
            @slot('titleIconClass', 'fas fa-database')
            @slot('noPadding', true)

            <div class="box-body">
                <button id="words" class="btn btn-primary btn-block" type="button" >Copy data</button>
                <textarea id="text_copy" class="form-control" name="text_copy" rows="4" cols="8" readonly>{!! str_replace("\n",'',trim(json_encode($insightService->captureDominionInsight($dominion, $selectedDominion, true)))) !!}</textarea>
            </div>

        @endcomponent
    </div>
</div>


@endif

@endsection

@push('inline-scripts')
    <script type="text/javascript">
        document.querySelector("#words").onclick = function () {
        document.querySelector("#text_copy").select();
        document.execCommand("copy");
        };

        document.querySelector("#input-btn").onclick = function () {
        document.querySelector("#input").select();
        document.execCommand("copy");
        };

    </script>
@endpush