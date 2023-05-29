@extends('layouts.master')
@section('title', 'World News')

@section('content')
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-newspaper-o"></i> News from the
                    @if ($realm !== null)

                        @switch($realm->alignment)
                            @case('good')
                                Commonwealth
                                @break
                            @case('evil')
                                Empire
                                @break
                            @case('independent')
                                Independent Realm
                                @break
                            @case('npc')
                                Barbarian Horde
                                @break
                            @case('players')
                                Players
                                @break
                            @default
                                {{ ucwords($realm->alignment) }}
                                @break
                        @endswitch

                        @if($realm->alignment == 'good')
                        Commonwealth
                        @elseif($realm->alignment == 'evil')
                        Empire
                        @elseif($realm->alignment == 'independent')
                        Independent Realm
                        @elseif($realm->alignment == 'npc')
                        Barbarian Horde
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
                                    <td style="vertical-align: top;">
                                        <span data-toggle="tooltip" data-placement="top" title="Tick {{ $gameEvent->tick }}">{{ $gameEvent->created_at }}</span>
                                    </td>
                                    <td>{!! $worldNewsHelper->getWorldNewsString($selectedDominion, $gameEvent) !!}</td>
                                    <td class="text-center">
                                        @if ($gameEvent->type == 'invasion' and ($gameEvent->source->realm_id == $selectedDominion->realm->id or $gameEvent->target->realm_id == $selectedDominion->realm->id))
                                            <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="ra ra-crossed-swords ra-fw"></i></a>
                                        @endif
                                        @if ($gameEvent->type == 'expedition' and ($gameEvent->source->realm_id == $selectedDominion->realm->id))
                                            <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="fas fa-drafting-compass fa-fw"></i></a>
                                        @endif
                                        @if ($gameEvent->type == 'theft' and ($gameEvent->source->realm_id == $selectedDominion->realm->id or $gameEvent->target->realm_id == $selectedDominion->realm->id))
                                            <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="fas fa-hand-lizard fa-fw"></i></a>
                                        @endif
                                        @if ($gameEvent->type == 'sorcery' and ($gameEvent->source->realm_id == $selectedDominion->realm->id or $gameEvent->target->realm_id == $selectedDominion->realm->id))
                                            <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="fas fa-hat-wizard fa-fw"></i></a>
                                        @endif
                                        @if ($gameEvent->type == 'sabotage' and ($gameEvent->source->realm_id == $selectedDominion->realm->id or $gameEvent->target->realm_id == $selectedDominion->realm->id))
                                            <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="fa fa-user-secret fa-fw"></i></a>
                                        @endif
                                        @if ($gameEvent->type == 'desecration' and ($gameEvent->source->realm_id == $selectedDominion->realm->id or $gameEvent->target->realm_id == $selectedDominion->realm->id))
                                            <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="ra ra-tombstone ra-fw"></i></a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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

        @include('partials.dominion.watched-dominions')
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
