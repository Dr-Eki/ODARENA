@extends('layouts.master')

@section('page-header', 'World News')

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
                                    <tr>
                                        <td>
                                            <span>{{ $gameEvent->created_at }}</span>
                                        </td>
                                        <td>
                                            @if ($gameEvent->type === 'invasion')
                                                @if ($gameEvent->source_type === \OpenDominion\Models\Dominion::class && in_array($gameEvent->source_id, $dominionIds, true))
                                                    @if ($gameEvent->data['result']['success'])
                                                        Victorious on the battlefield,
                                                        <span class="text-aqua">{{ $gameEvent->source->name }} <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a></span>
                                                        conquered
                                                        <span class="text-green text-bold">{{ number_format(array_sum($gameEvent->data['attacker']['landConquered'])) }}</span>
                                                        land from
                                                        <a href="{{ route('dominion.op-center.show', [$gameEvent->target->id]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span></a>
                                                        <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>.
                                                    @else
                                                        Sadly, the forces of
                                                        <span class="text-aqua">{{ $gameEvent->source->name }} <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a></span>
                                                        were beaten back by
                                                        <a href="{{ route('dominion.op-center.show', [$gameEvent->target->id]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span></a>
                                                        <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>.
                                                    @endif
                                                @elseif ($gameEvent->target_type === \OpenDominion\Models\Dominion::class)
                                                    @if ($gameEvent->data['result']['success'])
                                                        <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-orange">{{ $gameEvent->source->name }}</span></a>
                                                        <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>
                                                        invaded
                                                        <span class="text-aqua">{{ $gameEvent->target->name }} <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a></span>
                                                        and captured
                                                        <span class="text-red text-bold">{{ number_format(array_sum($gameEvent->data['attacker']['landConquered'])) }}</span>
                                                        land.
                                                    @else
                                                        @if ($gameEvent->source_realm_id == $selectedDominion->realm_id)
                                                            Fellow dominion
                                                        @endif
                                                        <span class="text-aqua">{{ $gameEvent->target->name }} <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a></span>
                                                        fended off an attack from
                                                        <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-orange">{{ $gameEvent->source->name }}</span></a>
                                                        <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>.
                                                    @endif
                                                @endif
                                            @elseif ($gameEvent->type === 'war_declared')
                                                <a href="{{ route('dominion.realm', [$gameEvent->source->number]) }}"><span class="text-aqua">{{ $gameEvent->source->name }}</span> (#{{ $gameEvent->source->number }})</a> has declared <span class="text-red text-bold">WAR</span> on <a href="{{ route('dominion.realm', [$gameEvent->target->number]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span> (#{{ $gameEvent->target->number }})</a>.
                                            @elseif ($gameEvent->type === 'war_canceled')
                                                <a href="{{ route('dominion.realm', [$gameEvent->source->number]) }}"><span class="text-aqua">{{ $gameEvent->source->name }}</span> (#{{ $gameEvent->source->number }})</a> has <span class="text-green text-bold">CANCELED</span> war against realm <a href="{{ route('dominion.realm', [$gameEvent->target->number]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span> (#{{ $gameEvent->target->number }})</a>.
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($gameEvent->type === 'invasion')
                                                @if ($gameEvent->source->realm_id == $selectedDominion->realm->id || $gameEvent->target->realm_id == $selectedDominion->realm->id)
                                                    <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="ra ra-crossed-swords ra-fw"></i></a>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
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
                    @if ($fromOpCenter)
                        <p>All the news for the target's realm for the last two days will be shown here.</p>
                    @else
                        <p>All the news of the world for the last two days can be seen here.</p>
                    @endif
                    <p>Only news about invasions are shown here.</p>
                    <!--
                    <p>
                        <label for="realm-select">Show a realm's World News:</label>
                        <select id="realm-select" class="form-control">
                            <option value="">All Realms</option>
                            @for ($i=1; $i<$realmCount; $i++)
                                <option value="{{ $i }}" {{ $realm && $realm->number == $i ? 'selected' : null }}>
                                    #{{ $i }} {{ $selectedDominion->realm->number == $i ? '(My Realm)' : null }}
                                </option>
                            @endfor
                        </select>
                    </p>
                  -->
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
                window.location.href = "{!! route('dominion.town-crier') !!}/" + selectedRealm;
            });
        })(jQuery);
    </script>
@endpush
