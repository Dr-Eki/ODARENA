@extends('layouts.master')
@section('title', 'Invasion')

@section('content')
@php
    $boxColor = ($event->data['result']['success'] ? 'success' : 'danger');
    if ($event->target->id === $selectedDominion->id)
    {
        $boxColor = ($event->data['result']['success'] ? 'danger' : 'success');
    }

    $isProtectorate = ($event->data['is_protectorate'] ?? false);

    $defender = $event->target;
    $target = $event->target;

    if ($isProtectorate)
    {
        $target = $event->target;
        $defender = OpenDominion\Models\Dominion::where('id', $event->data['protectorate']['protector_id'])->first();
    }

@endphp
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-{{ $boxColor }}">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="ra ra-crossed-swords"></i>
                    @if($event->target->realm->id === $selectedDominion->realm->id)
                        <span class="text-red">
                    @else
                        <span class="text-green">
                    @endif
                    {{ $event->source->name }}

                    @if($event->data['result']['success'])
                        successfully
                    @else
                        unsuccessfully
                    @endif

                    @if($event->data['attacker']['ambush'])
                        ambushed
                    @else
                        invaded
                    @endif

                    @if(isset($event->data['result']['annexation']) and $event->data['result']['annexation'] == TRUE)
                        and annexed
                    @endif

                    @if(isset($event->data['attacker']['liberation']) and $event->data['attacker']['liberation'] == TRUE)
                        and liberated
                    @endif

                    {{ $target->name }}
                    @if($isProtectorate)
                        (Protectorate of {{ $defender->name }})
                    @endif
                    </span>
                </h3>
            </div>
            <div class="box-bod no-padding">
                <div class="row">
                    <div class="col-xs-12 col-sm-4">
                        <div class="text-center">
                            <h4>{{ $event->source->name }}</h4>
                            @if (isset($event->data['result']['overwhelmed']) && $event->data['result']['overwhelmed'])
                                <p class="text-center text-red">
                                    @if ($event->source->id === $selectedDominion->id)
                                        Because you were severely outmatched, you suffer extra casualties.
                                    @else
                                        Because the forces from {{ $event->source->name }} were severely outmatched, they suffer extra casualties.
                                    @endif
                                </p>
                            @endif
                            @if ($event->source->realm->id === $selectedDominion->realm->id)
                                @if (isset($event->data['attacker']['instantReturn']))
                                    <p class="text-center text-blue">
                                        ⫷⫷◬⫸◬⫸◬<br>The waves align in your favour. <b>The invading units return home instantly.</b>
                                    </p>
                                @endif
                            @endif
                        </div>

                        <table class="table">
                            <colgroup>
                                <col width="25%">
                                <col width="25%">
                                <col width="25%">
                                <col width="25%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th>Sent</th>
                                    <th>Lost</th>
                                    <th>Returning</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for ($slot = 1; $slot <= $event->source->race->units->count(); $slot++)
                                @if((isset($event->data['attacker']['units_sent'][$slot]) and $event->data['attacker']['units_sent'][$slot] > 0) or
                                    (isset($event->data['attacker']['units_lost'][$slot]) and $event->data['attacker']['units_lost'][$slot] > 0) or
                                    (isset($event->data['attacker']['units_returning'][$slot]) and $event->data['attacker']['units_returning'][$slot] > 0)
                                    )

                                    @php
                                        $unitType = "unit{$slot}";
                                    @endphp
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->source->race, [$militaryCalculator->getUnitPowerWithPerks($event->source, null, null, $event->source->race->units->get(($slot-1)), 'offense'), $militaryCalculator->getUnitPowerWithPerks($event->source, null, null, $event->source->race->units->get(($slot-1)), 'defense'), ]) }}">
                                                {{ $event->source->race->units->where('slot', $slot)->first()->name }}
                                            </span>
                                        </td>
                                        <td>
                                            @if (isset($event->data['attacker']['units_sent'][$slot]))
                                                {{ number_format($event->data['attacker']['units_sent'][$slot]) }}
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td>
                                            @if (isset($event->data['attacker']['units_lost'][$slot]))
                                                {{ number_format($event->data['attacker']['units_lost'][$slot]) }}
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td>
                                            @if ($event->source->realm->id === $selectedDominion->realm->id)
                                                @if (isset($event->data['attacker']['units_returning'][$slot]))
                                                {{ number_format($event->data['attacker']['units_returning'][$slot]) }}
                                                @else
                                                0
                                                @endif
                                            @else
                                                <span class="text-muted">?</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @endfor
                            </tbody>
                        </table>

                        @if(isset($event->data['attacker']['annexation']['hasAnnexedDominions']) and $event->data['attacker']['annexation']['hasAnnexedDominions'] > 0)
                            @foreach($event->data['attacker']['annexation']['annexedDominions'] as $annexedDominionId => $annexedDominionData)
                                @php
                                    $annexedDominion = OpenDominion\Models\Dominion::findorfail($annexedDominionId);
                                @endphp
                                <div class="text-center">
                                    <h4>{{ $annexedDominion->name }}</h4>
                                </div>
                                <table class="table">
                                    <colgroup>
                                        <col width="25%">
                                        <col width="25%">
                                        <col width="25%">
                                        <col width="25%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Unit</th>
                                            <th>Sent</th>
                                            <th>Lost</th>
                                            <th>Returning</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @for ($slot = 1; $slot <= $annexedDominion->race->units->count(); $slot++)
                                        @if((isset($annexedDominionData['units_sent'][$slot]) and $annexedDominionData['units_sent'][$slot] > 0) or
                                            (isset($annexedDominionData['units_lost'][$slot]) and $annexedDominionData['units_lost'][$slot] > 0) or
                                            (isset($annexedDominionData['units_returning'][$slot]) and $annexedDominionData['units_returning'][$slot] > 0)
                                            )

                                            @php
                                                $unitType = "unit{$slot}";
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $annexedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($annexedDominion, null, null, $annexedDominion->race->units->get(($slot-1)), 'offense'), $militaryCalculator->getUnitPowerWithPerks($annexedDominion, null, null, $annexedDominion->race->units->get(($slot-1)), 'defense'), ]) }}">
                                                        {{ $annexedDominion->race->units->where('slot', $slot)->first()->name }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if (isset($annexedDominionData['units_sent'][$slot]))
                                                        {{ number_format($annexedDominionData['units_sent'][$slot]) }}
                                                    @else
                                                        0
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (isset($annexedDominionData['units_lost'][$slot]))
                                                        {{ number_format($annexedDominionData['units_lost'][$slot]) }}
                                                    @else
                                                        0
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($event->source->realm->id === $selectedDominion->realm->id)
                                                        @if (isset($annexedDominionData['units_returning'][$slot]))
                                                        {{ number_format($annexedDominionData['units_returning'][$slot]) }}
                                                        @else
                                                        0
                                                        @endif
                                                    @else
                                                        <span class="text-muted">?</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                        @endfor
                                    </tbody>
                                </table>
                            @endforeach
                        @endif

                        @if (
                                    (in_array($selectedDominion->round->mode, ['standard', 'standard-duration', 'factions', 'factions-duration', 'artefacts','pack','packs-duration']) and $event->source->realm->id === $selectedDominion->realm->id) or
                                    (in_array($selectedDominion->round->mode, ['deathmatch', 'deathmatch-duration']) and $event->source->id === $selectedDominion->id)
                            )
                        <table class="table">
                            <colgroup>
                                <col width="25%">
                                <col width="75%">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td>OP:</td>
                                    <td>
                                        @if ($event->data['result']['success'])
                                            <span class="text-green">
                                                {{ number_format($event->data['attacker']['op']) }}
                                            </span>
                                        @else
                                            <span class="text-red">
                                                {{ number_format($event->data['attacker']['op']) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Prestige:</td>
                                    <td>
                                    @if (isset($event->data['attacker']['prestige_change']))
                                        @php
                                            $prestigeChange = $event->data['attacker']['prestige_change'];
                                        @endphp
                                        @if ($prestigeChange < 0)
                                            <span class="text-red">
                                                {{ number_format($prestigeChange) }}
                                            </span>
                                        @elseif ($prestigeChange > 0)
                                            <span class="text-green">
                                                +{{ number_format($prestigeChange) }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                0
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">
                                            0
                                        </span>
                                    @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>XP:</td>
                                    <td>
                                    @if (isset($event->data['attacker']['xp']))
                                        <span class="text-green">
                                            +{{ number_format($event->data['attacker']['xp']) }}
                                        </span>
                                    @else
                                        <span class="text-muted">
                                            0
                                        </span>
                                    @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Morale:</td>
                                    <td>
                                    @if (isset($event->data['attacker']['morale_change']))
                                        @php
                                            $moraleChange = $event->data['attacker']['morale_change'];
                                        @endphp
                                        @if ($moraleChange < 0)
                                            <span class="text-red">
                                                {{ number_format($moraleChange) }}
                                            </span>
                                        @elseif ($moraleChange > 0)
                                            <span class="text-green">
                                                +{{ number_format($moraleChange) }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                0
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">
                                            0
                                        </span>
                                    @endif
                                    </td>
                                </tr>

                                @if (isset($event->data['attacker']['conversions']) and array_sum($event->data['attacker']['conversions']) > 0)
                                    <tr>
                                        <th colspan="2">Conversion</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">The {{ $raceHelper->getRaceAdjective($event->source->race) }} forces recall some of the dead.</small></td>
                                    </tr>
                                    @foreach($event->data['attacker']['conversions'] as $slot => $amount)
                                        @if($amount > 0 and is_numeric($slot))
                                            <tr>
                                                <td>{{ $event->source->race->units->where('slot', $slot)->first()->name }}:</td>
                                                <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                @if (isset($event->data['attacker']['plunder']) and array_sum($event->data['attacker']['plunder']) > 0)
                                <tr>
                                    <th colspan="2">Plunder</th>
                                </tr>
                                    @foreach($event->data['attacker']['plunder'] as $resource => $amount)
                                        @if($amount > 0)
                                            <tr>
                                                <td>{{ ucwords($resource) }}:</td>
                                                <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                @if (isset($event->data['attacker']['salvage']) and array_sum($event->data['attacker']['salvage']) > 0)
                                <tr>
                                    <th colspan="2">Salvage</th>
                                </tr>
                                    @foreach($event->data['attacker']['salvage'] as $resource => $amount)
                                        @if($amount > 0)
                                            <tr>
                                                <td>{{ ucwords($resource) }}:</td>
                                                <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                @if (isset($event->data['attacker']['demonic_collection']) and array_sum($event->data['attacker']['demonic_collection']) > 0)
                                <tr>
                                    <th colspan="2">Demonic Collection</th>
                                </tr>
                                <tr>
                                    <td colspan="2"><small class="text-muted">Tearing apart the dead, the {{ $raceHelper->getRaceAdjective($event->source->race) }} units collect souls, blood, and food.</small></td>
                                </tr>
                                    @foreach($event->data['attacker']['demonic_collection'] as $resource => $amount)
                                        @if($amount > 0)
                                            <tr>
                                                <td>{{ ucwords($resource) }}:</td>
                                                <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                @if (isset($event->data['attacker']['peasants_eaten']) and isset($event->data['attacker']['draftees_eaten']))
                                <tr>
                                    <th colspan="2">Population Eaten</th>
                                </tr>
                                <tr>
                                    <td colspan="2"><small class="text-muted">A gruesome sight as {{ $raceHelper->getRaceAdjective($event->source->race) }} warriors eat some of the {{ $raceHelper->getRaceAdjective($event->target->race) }}  {{ strtolower(str_plural($raceHelper->getPeasantsTerm($event->target->race))) }} and {{ strtolower(str_plural($raceHelper->getDrafteesTerm($event->target->race))) }}.</small></td>
                                </tr>
                                <tr>
                                    <td>{{ str_plural($raceHelper->getPeasantsTerm($event->target->race)) }}:</td>
                                    <td><span class="text-green">{{ number_format($event->data['attacker']['peasants_eaten']['peasants']) }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ str_plural($raceHelper->getDrafteesTerm($event->target->race)) }}:</td>
                                    <td><span class="text-green">{{ number_format($event->data['attacker']['draftees_eaten']['draftees']) }}</span></td>
                                </tr>
                                @endif

                                @if (isset($event->data['attacker']['peasants_burned']))
                                <tr>
                                    <th colspan="2">Population Burned</th>
                                </tr>
                                <tr>
                                    <td colspan="2"><small class="text-muted">The charred bodies of burned {{ strtolower(str_plural($raceHelper->getPeasantsTerm($event->target->race))) }} emit a foul odour across the battlefield.</small></td>
                                </tr>
                                <tr>
                                    <td>{{ str_plural($raceHelper->getPeasantsTerm($event->target->race)) }}:</td>
                                    <td><span class="text-green">{{ number_format($event->data['attacker']['peasants_burned']['peasants']) }}</span></td>
                                </tr>
                                @endif

                                @if (isset($event->data['attacker']['improvements_damage']))
                                <tr>
                                    <th colspan="2">Improvements Damage</th>
                                </tr>
                                <tr>
                                    <td colspan="2"><small class="text-muted">Heavy blows to {{$event->target->race->name}}'s improvements have caused damage.</small></td>
                                </tr>
                                <tr>
                                    <td colspan="2"><span class="text-green">{{ number_format($event->data['attacker']['improvements_damage']['improvement_points']) }} improvement points destroyed</span></td>
                                </tr>
                                @endif

                                @if (isset($event->data['defender']['units_stunned']) and array_sum($event->data['defender']['units_stunned']) > 0)
                                <tr>
                                    <th colspan="2">Stunned</th>
                                </tr>
                                <tr>
                                    <td colspan="2"><small class="text-muted">We stun some of the enemy units and they will not be able to fight for a while.</small></td>
                                </tr>
                                    @foreach($event->data['defender']['units_stunned'] as $slot => $amount)
                                        @if($amount > 0)
                                            <tr>
                                                <td>
                                                    @if($slot === 'draftees')
                                                        {{ $raceHelper->getDrafteesTerm($event->target->race) }}:
                                                    @else
                                                        {{ $event->target->race->units->where('slot', $slot)->first()->name }}:
                                                    @endif
                                                </td>
                                                <td><span class="text-red">{{ number_format($amount) }}</span></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                @if (isset($event->data['result']['crypt']['total']) and $event->data['result']['crypt']['total'] > 0)
                                <tr>
                                    <th colspan="2">Crypt</th>
                                </tr>
                                <tr>
                                    <td colspan="2"><small class="text-muted">Bodies are immediately added to the Imperial Crypt.</small></td>
                                </tr>
                                <tr>
                                    <td>Bodies:</td>
                                    <td><span class="text-green">+{{ number_format($event->data['result']['crypt']['total']) }}</span></td>
                                </tr>
                                @endif

                                @if (isset($event->data['attacker']['strength_gain']))
                                    <tr>
                                        <th>Strength</th>
                                    </tr>
                                    @if($event->data['attacker']['strength_gain'] > 0)
                                        <tr>
                                            <td colspan="2"><small class="text-muted">The Monster grows stronger.</small></td>
                                        </tr>
                                        <tr>
                                            <td>Strength:</td>
                                            <td><span class="text-green">+{{ number_format($event->data['attacker']['strength_gain']) }}</span></td>
                                        </tr>
                                    @else
                                    <tr>
                                        <td colspan="2"><small class="text-muted">The Monster is weakened.</small></td>
                                    </tr>
                                    <tr>
                                        <td>Strength:</td>
                                        <td><span class="text-red">-{{ number_format($event->data['attacker']['strength_gain']) }}</span></td>
                                    </tr>
                                    @endif
                                @endif

                                @if (isset($event->data['attacker']['mana_exhausted']) and $event->data['attacker']['mana_exhausted'] > 0)
                                    <tr>
                                        <th colspan="2">Mana Exhaustion</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Firing the Hailstorm Cannon depletes our mana supplies.</small></td>
                                    </tr>
                                    <tr>
                                        <td>Mana:</td>
                                        <td><span class="text-red">-{{ number_format($event->data['attacker']['mana_exhausted']) }}</span></td>
                                    </tr>
                                @endif

                                @if (isset($event->data['attacker']['ore_exhausted']) and $event->data['attacker']['ore_exhausted'] > 0)
                                    <tr>
                                        <th colspan="2">Ore Exhaustion</th>
                                    </tr>
                                    <tr>
                                        @if($target->race->name == 'Yeti')
                                            <td colspan="2"><small class="text-muted">Stonethrowers darken the sky with boulders.</small></td>
                                        @elseif($target->race->name == 'Gnome')
                                            <td colspan="2"><small class="text-muted">After a loud blasts of gunpowder, projectiles of ore rain down on the enemy.</small></td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <td>Ore:</td>
                                        <td><span class="text-red">-{{ number_format($event->data['attacker']['ore_exhausted']) }}</span></td>
                                    </tr>
                                @endif

                                @if (isset($event->data['attacker']['gunpowder_exhausted']) and $event->data['attacker']['gunpowder_exhausted'] > 0)
                                    <tr>
                                        <th colspan="2">Ore Exhaustion</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">The air fills with smoke and the distinct smell of gunpowder.</small></td>
                                    </tr>
                                    <tr>
                                        <td>Gunpowder:</td>
                                        <td><span class="text-red">-{{ number_format($event->data['attacker']['gunpowder_exhausted']) }}</span></td>
                                    </tr>
                                @endif

                                @if (isset($event->data['attacker']['resource_conversions']) and array_sum($event->data['attacker']['resource_conversions']) > 0)
                                <tr>
                                    <th colspan="2">New Resources</th>
                                </tr>
                                <tr>
                                    <td colspan="2"><small class="text-muted">The invading units extract resources from the battle field.</small></td>
                                </tr>
                                    @foreach($event->data['attacker']['resource_conversions'] as $resourceKey => $amount)
                                        @if($resourceKey !== 'bodies_spent')
                                            @php
                                                $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                            @endphp
                                            @if($amount > 0)
                                                <tr>
                                                    <td>{{ ucwords($resource->name) }}:</td>
                                                    <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                                </tr>
                                            @endif
                                        @endif
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                        @endif
                    </div>

                    <div class="col-xs-12 col-sm-4">
                        <div class="text-center">
                        <h4>{{ $defender->name }}</h4>
                        @if($isProtectorate)
                            <h5>Protector of {{ $target->name }}</h5>
                        @endif
                        </div>
                        <table class="table">
                            <colgroup>
                                <col width="34%">
                                <col width="33%">
                                <col width="33%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th>Defending</th>
                                    <th>Lost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($event->data['defender']['units_lost']['peasants']) and $event->data['defender']['units_lost']['peasants'] > 0)

                                    @php
                                        if(!isset($event->data['defender']['units_defending']['peasants']))
                                            $peasants = 0;
                                        else
                                            $peasants = $event->data['defender']['units_defending']['peasants'];
                                    @endphp

                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getDrafteeHelpString($defender->race, $defender) }}">
                                                {{ $raceHelper->getPeasantsTerm($defender->race) }}:
                                            </span>
                                        </td>
                                        <td>
                                            @if ($event->target->realm->id === $selectedDominion->realm->id)
                                                {{ number_format($peasants) }}
                                            @else
                                                <span class="text-muted">?</span>
                                            @endif
                                        </td>
                                        <td>{{ number_format($event->data['defender']['units_lost']['peasants']) }}</td>
                                    </tr>

                                @endif

                                @if(isset($event->data['defender']['units_lost']['draftees']) and $event->data['defender']['units_lost']['draftees'] > 0)

                                    @php
                                    if(!isset($event->data['defender']['units_defending']['draftees']))
                                        $draftees = 0;
                                    else
                                        $draftees = $event->data['defender']['units_defending']['draftees'];
                                    @endphp

                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getDrafteeHelpString($defender->race, $defender) }}">
                                                {{ $raceHelper->getDrafteesTerm($defender->race) }}:
                                            </span>
                                        </td>
                                        <td>
                                            @if ($event->target->realm->id === $selectedDominion->realm->id)
                                                {{ number_format($draftees) }}
                                            @else
                                                <span class="text-muted">?</span>
                                            @endif
                                        </td>
                                        <td>{{ number_format($event->data['defender']['units_lost']['draftees']) }}</td>
                                    </tr>

                                @endif
                                @for ($slot = 1; $slot <= $defender->race->units->count(); $slot++)
                                @if((isset($event->data['defender']['units_defending'][$slot]) and $event->data['defender']['units_defending'][$slot] > 0) or
                                    (isset($event->data['defender']['units_lost'][$slot]) and $event->data['defender']['units_lost'][$slot] > 0)
                                    )
                                    @php
                                        $unitType = "unit{$slot}";
                                    @endphp
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $defender->race, [$militaryCalculator->getUnitPowerWithPerks($defender, null, null, $defender->race->units->get(($slot-1)), 'offense'), $militaryCalculator->getUnitPowerWithPerks($defender, null, null, $defender->race->units->get(($slot-1)), 'defense'), ]) }}">
                                                {{ $defender->race->units->where('slot', $slot)->first()->name }}
                                            </span>
                                        </td>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $defender->race, [$militaryCalculator->getUnitPowerWithPerks($defender, null, null, $defender->race->units->get(($slot-1)), 'offense'), $militaryCalculator->getUnitPowerWithPerks($defender, null, null, $defender->race->units->get(($slot-1)), 'defense'), ]) }}">
                                                    @if ($event->target->realm->id === $selectedDominion->realm->id)
                                                        @if (isset($event->data['defender']['units_defending'][$slot]))
                                                            {{ number_format($event->data['defender']['units_defending'][$slot]) }}
                                                        @else
                                                            0
                                                        @endif
                                                    @else
                                                        <span class="text-muted">?</span>
                                                    @endif
                                            </span>
                                        </td>
                                        <td>
                                            @if (isset($event->data['defender']['units_lost'][$slot]))
                                                {{ number_format($event->data['defender']['units_lost'][$slot]) }}
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @endfor
                            </tbody>
                        </table>

                        @if(isset($event->data['defender']['annexation']['hasAnnexedDominions']) and $event->data['defender']['annexation']['hasAnnexedDominions'] > 0)
                            @foreach($event->data['defender']['annexation']['annexedDominions'] as $annexedDominionId => $annexedDominionData)
                                @php
                                    $annexedDominion = OpenDominion\Models\Dominion::findorfail($annexedDominionId);
                                @endphp
                                <div class="text-center">
                                    <h4>{{ $annexedDominion->name }}</h4>
                                </div>
                                <table class="table">
                                    <colgroup>
                                        <col width="34%">
                                        <col width="33%">
                                        <col width="33%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Unit</th>
                                            <th>Defending</th>
                                            <th>Lost</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @for ($slot = 1; $slot <= $annexedDominion->race->units->count(); $slot++)
                                        @if((isset($annexedDominionData['units_sent'][$slot]) and $annexedDominionData['units_sent'][$slot] > 0) or
                                            (isset($annexedDominionData['units_lost'][$slot]) and $annexedDominionData['units_lost'][$slot] > 0) or
                                            (isset($annexedDominionData['units_returning'][$slot]) and $annexedDominionData['units_returning'][$slot] > 0)
                                            )

                                            @php
                                                $unitType = "unit{$slot}";
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $annexedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($annexedDominion, null, null, $annexedDominion->race->units->get(($slot-1)), 'offense'), $militaryCalculator->getUnitPowerWithPerks($annexedDominion, null, null, $annexedDominion->race->units->get(($slot-1)), 'defense'), ]) }}">
                                                        {{ $annexedDominion->race->units->where('slot', $slot)->first()->name }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if (isset($annexedDominionData['units_sent'][$slot]))
                                                        {{ number_format($annexedDominionData['units_sent'][$slot]) }}
                                                    @else
                                                        0
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (isset($annexedDominionData['units_lost'][$slot]))
                                                        {{ number_format($annexedDominionData['units_lost'][$slot]) }}
                                                    @else
                                                        0
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                        @endfor
                                </table>
                            @endforeach
                        @endif

                        @if (
                                (in_array($selectedDominion->round->mode, ['standard', 'standard-duration', 'factions', 'factions-duration', 'artefacts','packs','packs-duration']) and $event->target->realm->id === $selectedDominion->realm->id) or
                                (in_array($selectedDominion->round->mode, ['deathmatch', 'deathmatch-duration']) and $event->target->id === $selectedDominion->id)
                            )

                        <table class="table">
                            <colgroup>
                                <col width="34%">
                                <col width="66%">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td>DP:</td>
                                    <td>
                                        @if ($event->data['result']['success'])
                                            <span class="text-red">
                                                {{ number_format($event->data['defender']['dp']) }}
                                            </span>
                                        @else
                                            <span class="text-green">
                                                {{ number_format($event->data['defender']['dp']) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Prestige:</td>
                                    <td>
                                    @if (isset($event->data['defender']['prestige_change']))
                                        @php
                                            $prestigeChange = $event->data['defender']['prestige_change'];
                                        @endphp
                                        @if ($prestigeChange < 0)
                                            <span class="text-red">
                                                {{ number_format($prestigeChange) }}
                                            </span>
                                        @elseif ($prestigeChange > 0)
                                            <span class="text-green">
                                                +{{ number_format($prestigeChange) }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                0
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">
                                            0
                                        </span>
                                    @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Morale:</td>
                                    <td>
                                    @if (isset($event->data['defender']['morale_change']))
                                        @php
                                            $moraleChange = $event->data['defender']['morale_change'];
                                        @endphp
                                        @if ($moraleChange < 0)
                                            <span class="text-red">
                                                {{ number_format($moraleChange) }}
                                            </span>
                                        @elseif ($moraleChange > 0)
                                            <span class="text-green">
                                                +{{ number_format($moraleChange) }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                0
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">
                                            0
                                        </span>
                                    @endif
                                    </td>
                                </tr>

                                @if (isset($event->data['defender']['conversions']) and array_sum($event->data['defender']['conversions']) > 0)
                                    <tr>
                                        <th colspan="2">Conversion</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">The {{ $raceHelper->getRaceAdjective($event->target->race) }} forces recall some of the dead.</small></td>
                                    </tr>
                                    @foreach($event->data['defender']['conversions'] as $slot => $amount)
                                        @if($amount > 0)
                                            <tr>
                                                <td>{{ $event->target->race->units->where('slot', $slot)->first()->name }}:</td>
                                                <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                @if (isset($event->data['defender']['salvage']) and array_sum($event->data['defender']['salvage']) > 0)
                                    <tr>
                                        <th colspan="2">Salvage</th>
                                    </tr>
                                    @foreach($event->data['defender']['salvage'] as $resource => $amount)
                                        @if($amount > 0)
                                            <tr>
                                                <td>{{ ucwords($resource) }}:</td>
                                                <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                @if (isset($event->data['defender']['demonic_collection']))
                                    <tr>
                                        <th colspan="2">Demonic Collection</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Tearing apart the dead, the {{ $raceHelper->getRaceAdjective($event->source->race) }} units collect souls, blood, and food.</small></td>
                                    </tr>
                                    @foreach($event->data['defender']['demonic_collection'] as $resource => $amount)
                                        @if($amount > 0)
                                            <tr>
                                                <td>{{ ucwords($resource) }}:</td>
                                                <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                @if (isset($event->data['attacker']['peasants_eaten']) and isset($event->data['attacker']['draftees_eaten']))
                                    <tr>
                                        <th colspan="2">Population Eaten</th>
                                    </tr>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">The {{ $raceHelper->getRaceAdjective($event->source->race) }} warriors eat some of our {{ strtolower(str_plural($raceHelper->getPeasantsTerm($event->target->race))) }} and {{ strtolower(str_plural($raceHelper->getDrafteesTerm($event->target->race))) }}.</small></td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($raceHelper->getPeasantsTerm($event->target->race)) }}:</td>
                                        <td><span class="text-green">{{ number_format($event->data['attacker']['peasants_eaten']['peasants']) }}</span></td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($raceHelper->getDrafteesTerm($event->target->race)) }}:</td>
                                        <td><span class="text-green">{{ number_format($event->data['attacker']['draftees_eaten']['draftees']) }}</span></td>
                                    </tr>
                                @endif

                                @if (isset($event->data['attacker']['peasants_burned']))
                                    <tr>
                                        <th colspan="2">Population Burned</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Our {{ strtolower(str_plural($raceHelper->getPeasantsTerm($event->target->race))) }} have been attacked with fire.</small></td>
                                    </tr>
                                    <tr>
                                        <td>{{ str_plural($raceHelper->getPeasantsTerm($event->target->race)) }} burned:</td>
                                        <td><span class="text-red">{{ number_format($event->data['attacker']['peasants_burned']['peasants']) }}</span></td>
                                    </tr>
                                @endif

                                @if (isset($event->data['attacker']['improvements_damage']))
                                    <tr>
                                        <th colspan="2">Improvements Damage</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Heavy blows to our improvements have weakened us.</small></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><span class="text-red">{{ number_format($event->data['attacker']['improvements_damage']['improvement_points']) }} improvement points destroyed</span></td>
                                    </tr>
                                @endif

                                @if (isset($event->data['defender']['units_stunned']) and array_sum($event->data['defender']['units_stunned']) > 0)
                                    <tr>
                                        <th colspan="2">Stunned</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Some of our units are stunned and will not be able to fight for two ticks.</small></td>
                                    </tr>
                                    @foreach($event->data['defender']['units_stunned'] as $slot => $amount)
                                        @if($amount > 0)
                                            <tr>
                                                <td>
                                                    @if($slot === 'draftees')
                                                        {{ $raceHelper->getDrafteesTerm($event->target->race) }}:
                                                    @else
                                                        {{ $event->target->race->units->where('slot', $slot)->first()->name }}:
                                                    @endif
                                                </td>
                                                <td><span class="text-red">{{ number_format($amount) }}</span></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                @if (isset($event->data['result']['crypt']['total']) and $event->data['result']['crypt']['total'] > 0)
                                    <tr>
                                        <th colspan="2">Crypt</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Bodies are immediately added to the Imperial Crypt.</small></td>
                                    </tr>
                                    <tr>
                                        <td>Bodies:</td>
                                        <td><span class="text-green">+{{ number_format($event->data['result']['crypt']['total']) }}</span></td>
                                    </tr>
                                @endif

                                @if (isset($event->data['defender']['resource_conversions']) and array_sum($event->data['defender']['resource_conversions']) > 0)
                                <tr>
                                    <th colspan="2">New Resources</th>
                                </tr>
                                <tr>
                                    <td colspan="2"><small class="text-muted">The defending units extract resources from the battle field.</small></td>
                                </tr>
                                    @foreach($event->data['defender']['resource_conversions'] as $resourceKey => $amount)
                                        @if($resourceKey !== 'bodies_spent')
                                            @php
                                                $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                            @endphp
                                            @if($amount > 0)
                                                <tr>
                                                    <td>{{ ucwords($resource->name) }}:</td>
                                                    <td><span class="text-green">+{{ number_format($amount) }}</span></td>
                                                </tr>
                                            @endif
                                        @endif
                                    @endforeach
                                @endif

                                @if (isset($event->data['defender']['resources_lost']) and array_sum($event->data['defender']['resources_lost']) != 0)
                                    <tr>
                                        <th colspan="2">Miasmic charges</th>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><small class="text-muted">Miasmic charges detonate.</small></td>
                                    </tr>
                                    @foreach($event->data['defender']['resources_lost'] as $resourceKey => $amount)
                                        @php
                                            $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                        @endphp
                                        @if($amount != 0)
                                            <tr>
                                                <td>{{ $resource->name }}:</td>
                                                <td><span class="text-red">{{ number_format($amount) }}</span></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                @if (isset($event->data['defender']['strength_gain']))
                                    <tr>
                                        <th colspan="2">Strength</th>
                                    </tr>
                                    @if($event->data['defender']['strength_gain'] > 0)
                                        <tr>
                                            <td colspan="2"><small class="text-muted">The Monster grows stronger.</small></td>
                                        </tr>
                                        <tr>
                                            <td>Strength:</td>
                                            <td><span class="text-green">+{{ number_format($event->data['defender']['strength_gain']) }}</span></td>
                                        </tr>
                                    @else
                                    <tr>
                                        <td colspan="2"><small class="text-muted">The Monster is weakened.</small></td>
                                    </tr>
                                    <tr>
                                        <td>Strength:</td>
                                        <td><span class="text-red">-{{ number_format($event->data['defender']['strength_gain']) }}</span></td>
                                    </tr>
                                    @endif
                                @endif

                                @if (isset($event->data['defender']['resources_spent']) and array_sum($event->data['defender']['resources_spent']))
                                    <tr>
                                        <th colspan="2">Resource Spenditure</th>
                                    </tr>
                                    @foreach($event->data['defender']['resources_spent'] as $resourceKey => $amount)
                                        @php
                                            $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                        @endphp
                                        @if($amount > 0)
                                            <tr>
                                                <td>{{ $resource->name }}</td>
                                                <td><span class="text-red">-{{ number_format($amount) }}</span></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif


                            </tbody>
                        </table>
                        @endif

                    </div>

                    <div class="col-xs-12 col-sm-4">
                        <div class="text-center">
                        <h4>
                            @if ($event->target->realm->id === $selectedDominion->realm->id)
                                Land Lost
                            @else
                                Land Gained
                            @endif
                        </h4>
                        </div>
                        <table class="table">
                            <colgroup>

                                @if ($event->target->realm->id === $selectedDominion->realm->id)
                                    <col width="50%">
                                    <col width="50%">
                                @else
                                    <col width="33%">
                                    <col width="33%">
                                    <col width="33%">
                                @endif
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Terrain</th>
                                    <th>
                                        @if ($event->target->realm->id === $selectedDominion->realm->id)
                                            Lost
                                        @else
                                            Conquered
                                        @endif
                                    </th>
                                    @if ($event->source->realm->id === $selectedDominion->realm->id)
                                        <th>Discovered</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @if (!isset($event->data['attacker']['land_conquered']))
                                    <tr>
                                        <td colspan="3" class="text-center">
                                            <em>None</em>
                                        </td>
                                    </tr>
                                @else

                                    @foreach($event->data['attacker']['terrain_gained'] as $terrainKey => $amount)
                                        @php
                                            $terrainKey = str_replace('terrain_', '', $terrainKey);
                                            $terrain = OpenDominion\Models\Terrain::where('key', $terrainKey)->first();

                                            $conqueredAmount = abs($event->data['attacker']['terrain_conquered']['available'][$terrainKey] ?? 0);
                                            $conqueredAmount += abs($event->data['attacker']['terrain_conquered']['queued'][$terrainKey] ?? 0);

                                            $discoveredAmount = abs($event->data['attacker']['terrain_discovered'][$terrainKey] ?? 0);
                                        @endphp
                                        <tr>
                                            <td>{{ $terrain->name }}</td>
                                            <td>{!! $conqueredAmount ? $conqueredAmount : '&mdash;' !!}</td>
                                            @if ($event->source->realm->id === $selectedDominion->realm->id)
                                                <td>{!! $discoveredAmount ? $discoveredAmount : '&mdash;' !!}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td><strong>Total</strong></td>
                                        <td><strong>{{ number_format($event->data['attacker']['land_conquered']) }}</strong></td>
                                        @if ($event->source->realm->id === $selectedDominion->realm->id)
                                            <td><strong>{{ number_format($event->data['attacker']['land_discovered']) }}</strong></td>
                                        @endif
                                    </tr>
                                @endif
                            </tbody>
                        </table>

                        <table class="table">
                            <div class="text-center">
                                <h4>
                                    @if ($event->target->realm->id === $selectedDominion->realm->id)
                                        Buildings Lost
                                    @else
                                        Buildings Destroyed
                                    @endif
                                </h4>
                                <small class="text-muted" style="font-weight: normal;">(including unfinished)</small>
                            </div>
                            <colgroup>
                                <col width="50%">
                                <col width="50%">
                            </colgroup>
                            <tbody>
                                @if(isset($event->data['defender']['buildings_lost_total']))
                                    @foreach($event->data['defender']['buildings_lost_total'] as $buildingKey => $amount)
                                        @php
                                            $building = OpenDominion\Models\Building::where('key', $buildingKey)->first();
                                        @endphp
                                    <tr>
                                        <td>{{ $building->name }}</td>
                                        <td>{{ number_format($amount )}}</td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="2" class="text-center">
                                            <em>None</em>
                                        </td>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="box-footer">
                    <div class="row">
                        <div class="col-sm-4">
                            <small class="text-muted">
                                <a href="{{ route('dominion.insight.show', [$event->source->id]) }}"><i class="fa fa-eye"></i> {{ $event->source->name }} (# {{ $event->source->realm->number }})</a>
                            </small>
                        </div>
                        <div class="col-sm-4">
                            <small class="text-muted">
                                <a href="{{ route('dominion.insight.show', [$event->target->id]) }}"><i class="fa fa-eye"></i> {{ $event->target->name }} (# {{ $event->target->realm->number }})</a>
                            </small>
                        </div>
                        <div class="col-sm-4">
                            <div class="pull-right">
                                <small class="text-muted">
                                    Invasion recorded at tick {{ number_format($event->tick) }}, {{ ($selectedDominion->round->ticks - $event->tick) }} ticks ago.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($event->story)
        <div class="col-sm-12 col-md-3">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="ra ra-quill-ink"></i>Chronicler
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <p>
                                {!! nl2br($event->story['story']) !!}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- 
@if(Request::get('viewImage'))
    @if($event->story and $event->story['image'])
    <div class="row">
        <div class="col-sm-12 col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="ra ra-quill-ink"></i>Chronicler
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <p>
                                {!! nl2br($event->story['story']) !!}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa-solid fa-paintbrush"></i>Artist's Impression
                    </h3>
                </div>
                <div class="box-body">
                    <img src="data:image/png;base64, {{ $event->story['image'] }}" width="100%" height="100%" alt="Artist's impression" />
                </div>
            </div>
        </div>
    </div>
    @endif
@endif
--}}

@endsection
