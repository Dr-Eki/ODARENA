@extends('layouts.master')

{{--
@section('page-header', 'World News')
--}}

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-newspaper-o"></i> News from the
                        @if ($realm !== null)

                          @if($realm->alignment == 'good')
                            Commonwealth Realm of {{ $realm->name }} (#{{ $realm->number }})
                          @elseif($realm->alignment == 'evil')
                            Imperial Realm of {{ $realm->name }} (#{{ $realm->number }})
                          @elseif($realm->alignment == 'npc')
                            Barbarian Horde
                          @endif

                        @else
                            whole World
                        @endif
                    </h3>
                </div>

                @if ($gameEvents->isEmpty())
                    <div class="box-body">
                        <p>No recent events.</p>
                    </div>
                @else
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-striped">
                            <colgroup>
                                <col width="140">
                                <col>
                                <col width="50">
                            </colgroup>
                            <tbody>
                                @foreach ($gameEvents as $gameEvent)
                                @if($gameEvent->type !== 'send_units' or ($gameEvent->type == 'send_units' and $gameEvent->source->realm->id === $selectedDominion->realm->id))
                                    <tr>
                                        <td style="vertical-align: top;">
                                            <span>{{ $gameEvent->created_at }}</span>
                                        </td>
                                        <td>
                                            @if ($gameEvent->type === 'invasion')
                                                @if ($gameEvent->source_type === \OpenDominion\Models\Dominion::class && in_array($gameEvent->source_id, $dominionIds, true))
                                                    @if ($gameEvent->data['result']['success'])
                                                        @if(isset($gameEvent->data['attacker']['liberation']) and $gameEvent->data['attacker']['liberation'])
                                                            Victorious on the battlefield,
                                                            <a href="{{ route('dominion.insight.show', [$gameEvent->source->id]) }}"><span class="text-aqua">{{ $gameEvent->source->name }}</a></span> <span class="text-aqua"><a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a></span>
                                                            liberated
                                                            <span data-toggle="tooltip" data-placement="top" title="{{ $gameEvent->target->race->name }} ({{ number_format($landCalculator->getTotalLand($gameEvent->target)/$landCalculator->getTotalLand($selectedDominion)*100,2) }}%)">
                                                            <a href="{{ route('dominion.insight.show', [$gameEvent->target->id]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span></a>
                                                            <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a></span>
                                                            and conquered
                                                            <span class="text-green text-bold">{{ number_format(array_sum($gameEvent->data['attacker']['landConquered'])) }}</span>
                                                            land.
                                                        @else
                                                            Victorious on the battlefield,
                                                            <a href="{{ route('dominion.insight.show', [$gameEvent->source->id]) }}"><span class="text-aqua">{{ $gameEvent->source->name }}</span></a> <span class="text-aqua"><a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a></span>
                                                            conquered
                                                            <span class="text-green text-bold">{{ number_format(array_sum($gameEvent->data['attacker']['landConquered'])) }}</span>
                                                            land
                                                            from
                                                            <span data-toggle="tooltip" data-placement="top" title="{{ $gameEvent->target->race->name }} ({{ number_format($landCalculator->getTotalLand($gameEvent->target)/$landCalculator->getTotalLand($selectedDominion)*100,2) }}%)">
                                                            <a href="{{ route('dominion.insight.show', [$gameEvent->target->id]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span></a>
                                                            <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a></span>.
                                                        @endif

                                                    @else
                                                        Sadly, the forces of
                                                        <span class="text-aqua">{{ $gameEvent->source->name }} <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a></span>
                                                        were beaten back by
                                                        <span data-toggle="tooltip" data-placement="top" title="{{ $gameEvent->target->race->name }} ({{ number_format($landCalculator->getTotalLand($gameEvent->target)/$landCalculator->getTotalLand($selectedDominion)*100,2) }}%)">
                                                        <a href="{{ route('dominion.insight.show', [$gameEvent->target->id]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span></a>
                                                        <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a></span>.
                                                    @endif
                                                @elseif ($gameEvent->target_type === \OpenDominion\Models\Dominion::class)
                                                    @if ($gameEvent->data['result']['success'])
                                                        @if(isset($gameEvent->data['attacker']['liberation']) and $gameEvent->data['attacker']['liberation'])
                                                            <span data-toggle="tooltip" data-placement="top" title="{{ $gameEvent->source->race->name }} ({{ number_format($landCalculator->getTotalLand($gameEvent->source)/$landCalculator->getTotalLand($selectedDominion)*100,2) }}%)">
                                                            <a href="{{ route('dominion.insight.show', [$gameEvent->source->id]) }}"><span class="text-orange">{{ $gameEvent->source->name }}</span></a>
                                                            <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a></span>
                                                            liberated
                                                              <span data-toggle="tooltip" data-placement="top" title="{{ $gameEvent->target->race->name }} ({{ number_format($landCalculator->getTotalLand($gameEvent->target)/$landCalculator->getTotalLand($selectedDominion)*100,2) }}%)">
                                                              <a href="{{ route('dominion.insight.show', [$gameEvent->target->id]) }}" class="text-aqua">{{ $gameEvent->target->name }}</a> <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a></span>
                                                            and captured
                                                            <span class="text-red text-bold">{{ number_format(array_sum($gameEvent->data['attacker']['landConquered'])) }}</span>
                                                            land.
                                                        @else
                                                            <span data-toggle="tooltip" data-placement="top" title="{{ $gameEvent->source->race->name }} ({{ number_format($landCalculator->getTotalLand($gameEvent->source)/$landCalculator->getTotalLand($selectedDominion)*100,2) }}%)">
                                                            <a href="{{ route('dominion.insight.show', [$gameEvent->source->id]) }}"><span class="text-orange">{{ $gameEvent->source->name }}</span></a>
                                                            <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a></span>
                                                            invaded
                                                              <span data-toggle="tooltip" data-placement="top" title="{{ $gameEvent->target->race->name }} ({{ number_format($landCalculator->getTotalLand($gameEvent->target)/$landCalculator->getTotalLand($selectedDominion)*100,2) }}%)">
                                                              <a href="{{ route('dominion.insight.show', [$gameEvent->target->id]) }}" class="text-aqua">{{ $gameEvent->target->name }}</a> <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a></span>
                                                            and captured
                                                            <span class="text-red text-bold">{{ number_format(array_sum($gameEvent->data['attacker']['landConquered'])) }}</span>
                                                            land.
                                                        @endif
                                                    @else
                                                        @if ($gameEvent->source_realm_id == $selectedDominion->realm_id)
                                                            Fellow dominion
                                                        @endif
                                                        <a href="{{ route('dominion.insight.show', [$gameEvent->target->id]) }}"><span class="text-aqua">{{ $gameEvent->target->name }}</span></a> <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a></span>
                                                        fended off an attack from
                                                        <span data-toggle="tooltip" data-placement="top" title="{{ $gameEvent->source->race->name }} ({{ number_format($landCalculator->getTotalLand($gameEvent->source)/$landCalculator->getTotalLand($selectedDominion)*100,2) }}%)">
                                                        <a href="{{ route('dominion.insight.show', [$gameEvent->source->id]) }}"><span class="text-orange">{{ $gameEvent->source->name }}</span></a>
                                                        <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a></span>.
                                                    @endif
                                                @endif
                                            @elseif ($gameEvent->type === 'barbarian_invasion')
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $gameEvent->source->race->name }} ({{ number_format($landCalculator->getTotalLand($gameEvent->source)/$landCalculator->getTotalLand($selectedDominion)*100,2) }}%)">
                                                <a href="{{ route('dominion.insight.show', [$gameEvent->source->id]) }}"><span class="text-yellow">{{ $gameEvent->source->name }}</span></a>
                                                <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a></span>
                                                {{ $gameEvent['data']['type'] }} a nearby {{ $gameEvent['data']['target'] }} and captured <span class="text-yellow text-bold">{{ number_format($gameEvent['data']['land']) }}</span> land.
                                            @elseif ($gameEvent->type === 'new_dominion')
                                                @php
                                                if($gameEvent->target->alignment == 'evil')
                                                {
                                                    $alignment = 'Empire';
                                                    $verb = 'joined';
                                                }
                                                elseif($gameEvent->target->alignment == 'good')
                                                {
                                                    $alignment = 'Commonwealth';
                                                    $verb = 'enlisted in';
                                                }
                                                elseif($gameEvent->target->alignment == 'independent')
                                                {
                                                    $alignment = 'Independent';
                                                    $verb = 'appeared among';
                                                }
                                                elseif($gameEvent->target->alignment == 'npc')
                                                {
                                                    $alignment = 'Barbarian Horde';
                                                    $verb = 'was spotted in';
                                                }
                                                else
                                                {
                                                    $alignment = 'Unknown';
                                                }

                                                if(isset($gameEvent->data['random_faction']) and $gameEvent->data['random_faction'])
                                                {
                                                    $verb = 'randomly ' . $verb;
                                                }
                                                @endphp

                                                The {{ $raceHelper->getRaceAdjective($gameEvent->source->race) }} dominion of <a href="{{ route('dominion.insight.show', [$gameEvent->source->id]) }}"><span class="text-aqua">{{ $gameEvent->source->name }}</span></a>, led by <em>{{ $gameEvent->source->title->name }}</em> {{ $gameEvent->source->ruler_name }}, {{ $verb }} the
                                                <a href="{{ route('dominion.realm', [$gameEvent->target->number]) }}">
                                                  @if ($gameEvent->target->id == $selectedDominion->realm_id)
                                                    <span class="text-green">
                                                  @else
                                                    <span class="text-red">
                                                  @endif
                                                  {{ $alignment }}</span></a>.
                                            @elseif($gameEvent->type === 'abandon_dominion')
                                                The dominion <a href="{{ route('dominion.insight.show', [$gameEvent->source->id]) }}"><span class="text-aqua">{{ $gameEvent->source->name }}</a> <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a></span> was abandoned by <em>{{ $gameEvent->data['ruler_title'] }}</em> {{ $gameEvent->data['ruler_name'] }}.
                                            @elseif($gameEvent->type === 'round_countdown')
                                                <p><span class="label label-danger"><i class="fas fa-hourglass-end"></i></span> The dominion <a href="{{ route('dominion.insight.show', [$gameEvent->source->id]) }}"><span class="text-aqua">{{ $gameEvent->source->name }}</a> <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a></span> has reached {{ number_format($gameEvent->source->round->land_target) }} land and the countdown has started.</p>
                                                The round ends at the end of the 12th hour from now, at {{ $selectedDominion->round->end_date }}.
                                            @elseif ($gameEvent->type === 'deity_renounced')
                                                <a href="{{ route('dominion.insight.show', [$gameEvent->target->id]) }}"><span class="text-aqua">{{ $gameEvent->target->name }}</span></a> <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a> has renounced <span class="text-orange">{{ $gameEvent->source->name }}</span>.
                                            @elseif ($gameEvent->type === 'deity_completed')
                                                <span class="text-orange">{{ $gameEvent->source->name }}</span> has accepted the devotion of <a href="{{ route('dominion.insight.show', [$gameEvent->target->id]) }}"><span class="text-aqua">{{ $gameEvent->target->name }}</span></a> <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>.
                                            @elseif ($gameEvent->type === 'invasion_support')
                                                An army from <a href="{{ route('dominion.insight.show', [$gameEvent->source->id]) }}"><span class="text-aqua">{{ $gameEvent->source->name }}</span></a> <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a> rushed to aid <a href="{{ route('dominion.insight.show', [$gameEvent->target->id]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span></a> <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a> in combat.
                                            @elseif ($gameEvent->type === 'defense_support')
                                                An army from <a href="{{ route('dominion.insight.show', [$gameEvent->source->id]) }}"><span class="text-aqua">{{ $gameEvent->source->name }}</span></a> <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a> rushed to aid <a href="{{ route('dominion.insight.show', [$gameEvent->target->id]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span></a> <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a> in combat.
                                            @elseif ($gameEvent->type === 'expedition' and $gameEvent->source->realm_id == $selectedDominion->realm->id)
                                                An expedition was sent out by <a href="{{ route('dominion.insight.show', [$gameEvent->source->id]) }}"><span class="text-aqua">{{ $gameEvent->source->name }}</span></a> <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>, discovering <span class="text-green text-bold">{{ number_format(array_sum($gameEvent->data['land_discovered'])) }}</span> land.
                                            @endif
                                        </td>
                                        <td class="text-center">
                                        @if ($gameEvent->type == 'invasion' and ($gameEvent->source->realm_id == $selectedDominion->realm->id or $gameEvent->target->realm_id == $selectedDominion->realm->id))
                                            <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="ra ra-crossed-swords ra-fw"></i></a>
                                        @endif

                                        @if ($gameEvent->type == 'expedition' and ($gameEvent->source->realm_id == $selectedDominion->realm->id or $gameEvent->target->realm_id == $selectedDominion->realm->id))
                                            <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="fas fa-drafting-compass fa-fw"></i></a>
                                        @endif
                                        </td>
                                    </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if ($fromOpCenter)
                    <div class="box-footer">
                        <em>Revealed {{ $clairvoyanceInfoOp->updated_at }} by {{ $clairvoyanceInfoOp->sourceDominion->name }}</em>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                  <p>The World News shows you all invasions that have taken place in the world in the last two days.</p>
                    <p>
                        <label for="realm-select">To see older news, select a realm below.</label>
                        <select id="realm-select" class="form-control">
                            <option value="">All Realms</option>
                            @for ($i=1; $i<=$realmCount; $i++)
                                <option value="{{ $i }}" {{ $realm && $realm->number == $i ? 'selected' : null }}>
                                    {{ $i }} {{ $selectedDominion->realm->number == $i ? '(My Realm)' : null }}
                                </option>
                            @endfor
                        </select>
                    </p>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#realm-select').change(function() {
                var selectedRealm = $(this).val();
                window.location.href = "{!! route('dominion.world-news') !!}/" + selectedRealm;
            });
        })(jQuery);
    </script>
@endpush
