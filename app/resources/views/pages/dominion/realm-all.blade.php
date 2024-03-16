@extends('layouts.master')
@section('title', 'The World')

@section('content')
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="row">
                    <div class="col-sm-12 text-center">
                        <h3 class="box-title">The World</h3>
                    </div>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table" id="dominions-table">
                    <colgroup>
                        <col>
                        <col width="100">
                        <col width="100">
                        <col width="100">
                        <col width="100">
                        <col width="100">
                        <col width="100">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Dominion</th>
                            <th class="text-center">Realm</th>
                            <th class="text-center">Faction</th>
                            <th class="text-center">Deity</th>
                            <th class="text-center">Land</th>
                            <th class="text-center">Networth</th>
                            <th class="text-center">Units<br>Returning</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dominions as $dominion)
                            @php
                                $isOwnRealm = ($selectedDominion->realm_id === $dominion->realm->id);
                                $isOwnAlly = $selectedDominion->realm->isAlly($dominion->realm);
                                
                                if($dominion->isLocked())
                                {
                                    $trStyle = 'text-decoration:line-through; color: #666';
                                }
                                if($isOwnRealm)
                                {
                                    $trStyle = 'background-color: #d9ffe0';
                                }
                                else
                                {
                                    $trStyle = null;
                                }
                            @endphp
                                <tr style="{{ $trStyle }}">
                                    <td>
                                        @if ($dominion->isLocked())
                                            <span data-toggle="tooltip" data-placement="top" title="<strong>This dominion has been locked.</strong><br>Reason: <em>{{ $dominion->getLockedReason($dominion->is_locked) }}</em>">
                                            <i class="fa fa-lock fa-lg text-grey" title=""></i>
                                            </span>
                                        @endif

                                        @if ($spellCalculator->isSpellActive($dominion, 'rainy_season'))
                                            <span data-toggle="tooltip" data-placement="top" title="Rainy Season">
                                            <i class="ra ra-droplet fa-lg text-blue"></i>
                                            </span>
                                        @endif

                                        @if ($spellCalculator->isSpellActive($dominion, 'primordial_wrath'))
                                            <span data-toggle="tooltip" data-placement="top" title="Primordial Wrath">
                                            <i class="ra ra-monster-skull fa-lg text-red" title=""></i>
                                            </span>
                                        @endif

                                        @if ($dominionHelper->isEnraged($dominion))
                                            <span data-toggle="tooltip" data-placement="top" title="Enraged">
                                            <i class="ra ra-explosion fa-lg text-red" title=""></i>
                                            </span>
                                        @endif

                                        @if ($spellCalculator->isSpellActive($dominion, 'ragnarok'))
                                            <span data-toggle="tooltip" data-placement="top" title="Ragnarök">
                                            <i class="ra ra-blast fa-lg text-red" title=""></i>
                                            </span>
                                        @endif

                                        @if ($spellCalculator->isSpellActive($dominion, 'stasis'))
                                            <span data-toggle="tooltip" data-placement="top" title="Stasis">
                                            <i class="ra ra-emerald fa-lg text-purple"></i>
                                            </span>
                                        @endif

                                        @if ($spellCalculator->isAnnexed($dominion))
                                            <span data-toggle="tooltip" data-placement="top" title="Annexed by {{ $spellCalculator->getAnnexer($dominion)->name }}!<br>Current raw military power: {{ number_format($militaryCalculator->getRawMilitaryPowerFromAnnexedDominion($dominion)) }}">
                                            <i class="ra ra-castle-flag fa-lg text-black"></i>
                                            </span>
                                        @endif

                                        @if ($spellCalculator->hasAnnexedDominions($dominion))
                                            <span data-toggle="tooltip" data-placement="top" title="Has annexed Barbarians!<br>Current additional raw military power: {{ number_format($militaryCalculator->getRawMilitaryPowerFromAnnexedDominions($dominion)) }}">
                                            <i class="ra ra-castle-flag fa-lg text-black"></i>
                                            </span>
                                        @endif

                                        @if ($dominion->isMonarch())
                                            <span data-toggle="tooltip" data-placement="top" title="Governor of Their Realm">
                                            <i class="fa fa-star fa-lg text-orange"></i>
                                            </span>
                                        @endif

                                        @if ($protectionService->isUnderProtection($dominion))
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $dominion->protection_ticks }} protection tick(s) left">
                                            <i class="ra ra-shield ra-lg text-aqua"></i>
                                            </span>
                                        @endif

                                        <span data-toggle="tooltip" data-placement="top" title="{{ $realmHelper->getDominionHelpString($dominion, $selectedDominion) }}">
                                            <a href="{{ route('dominion.insight.show', $dominion) }}">
                                                @if($dominion->id == $selectedDominion->id)
                                                    <strong>{{ $dominion->name }}</strong>
                                                @else
                                                    {{ $dominion->name }}
                                                @endif
                                            </a>
                                        </span>

                                        @if($isOwnAlly)
                                            <span class="label label-success">Ally</span>
                                        @endif

                                        @if($dominion->isAbandoned())
                                            <span data-toggle="tooltip" data-placement="top" title="This dominion has been abandoned by its ruler" class="label label-warning"><span>Abandoned</span></span>
                                        @elseif ($isOwnRealm && $dominion->round->isActive() && $dominion->user->isOnline() and $dominion->id !== $selectedDominion->id)
                                                <span class="label label-success">Online</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('dominion.realm', $dominion->realm->number) }}"># {{ $dominion->realm->number }}</a>
                                    </td>
                                    <td class="text-center">
                                        <span data-toggle="tooltip" data-placement="top" title="{!! $raceHelper->getRacePerksHelpString($dominion->race) !!}">
                                            {{ $dominion->race->name }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($dominion->hasDeity())
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
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $perksList }}" >{{ $dominion->deity->name }}</span>

                                        @elseif($dominion->hasPendingDeitySubmission())
                                            @if($isOwnRealm)
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $dominion->getPendingDeitySubmission()->name }} in {{ $dominion->getPendingDeitySubmissionTicksLeft() }} {{ Str::plural('tick', $dominion->getPendingDeitySubmissionTicksLeft()) }}" class="text-muted"><i class="fas fa-pray"></i></span>
                                            @else
                                                <span class="text-muted"><i class="fas fa-pray"></i></span>
                                            @endif
                                        @else
                                            &mdash;
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($dominion->land) }}</td>
                                    <td class="text-center">{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}</td>
                                    <td class="text-center">
                                        @if ($militaryCalculator->hasReturningUnits($dominion))
                                            <span class="label label-success">Yes</span>
                                        @else
                                            <span class="text-gray">No</span>
                                        @endif
                                    </td>
                                </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-xs-12">
                        <div class="box-body table-responsive no-padding">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <tr>
                                  <td>Dominions:</td>
                                  <td>{{ number_format($dominions->count()) }}</td>
                                </tr>
                                <tr>
                                  <td>Victories:</td>
                                  <td>{{ number_format($realmsDominionsStats['victories']) }}</td>
                                </tr>
                                  <tr>
                                    <td>Prestige:</td>
                                    <td>{{ number_format($realmsDominionsStats['prestige']) }}</td>
                                  </tr>
                                  <td>Total land:</td>
                                  <td>{{ number_format($landCalculator->getTotalLandForRealm($realm)) }}</td>
                                </tr>
                                  <td>Land conquered:</td>
                                  <td>{{ number_format($realmsDominionsStats['total_land_conquered']) }}</td>
                                </tr>
                                <tr>
                                  <td>Land explored:</td>
                                  <td>{{ number_format($realmsDominionsStats['total_land_explored']) }}</td>
                                </tr>
                                <tr>
                                  <td>Land lost:</td>
                                  <td>{{ number_format($realmsDominionsStats['total_land_lost']) }}</td>
                                </tr>
                                <tr>
                                  <td>Networth:</td>
                                  <td>{{ number_format($networthCalculator->getRealmNetworth($realm)) }}</td>
                                </tr>
                                @if($realm->getAllies()->count() > 0)
                                    <tr>
                                        <td>Allies:</td>
                                        <td>
                                            @foreach($realm->getAllies() as $alliedRealm)
                                                <a href="{{ route('dominion.realm', [$alliedRealm->number]) }}">{{ $alliedRealm->name }} (# {{ $alliedRealm->number }})</a><br>
                                            @endforeach
                                        </td>
                                    </tr>
                                @elseif(in_array($realm->round->mode, ['factions', 'factions-duration']))
                                    <tr>
                                        <td>Allies:</td>
                                        <td>None</td>
                                    </tr>
                                @endif
                                @if($realm->alignment === 'evil')
                                    <tr>
                                        <td>Imperial Crypt:</td>
                                        <td>{{ number_format($resourceCalculator->getRealmAmount($realm, 'body')) }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>

                      <p class="text-center"><a href="{{ route('dominion.world-news', [$realm->number]) }}">Read the News from the {{ $alignmentNoun }}</a></p>

                        @if(isset($realmDominionsStats) and array_sum($realmDominionsStats) > 0)
                            <div class="col-xs-12">
                                <div class="row">
                                    <strong>{{ $alignmentAdjective }} Lands</strong><br>
                                <div class="row">
                                    @foreach(OpenDominion\Models\Terrain::all()->sortBy('order') as $terrain)
                                        <div class="col-xs-4">
                                            {{ $terrain->name }}: {{ number_format($realmDominionsStats['terrain'][$terrain->key]) }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @include('partials.dominion.watched-dominions')
    </div>


</div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
    <style>
        #rulers-search #dominions-table_filter { display: none !important; }
    </style>
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var table = $('#dominions-table').DataTable({
                order: [4, 'desc'],
                paging: false,
                pageLength: 100,
                searching: false,
                info: false,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
        })(jQuery);
    </script>
@endpush
