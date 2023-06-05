@extends('layouts.master')
@section('title', 'Watched Dominions')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><i class="fas fa-eye"></i> Watched Dominions</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover" id="dominions-table">
                        <colgroup>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col width="50">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Dominion</th>
                                <th class="">Faction</th>
                                <th class="">Realm</th>
                                <th class="">Land</th>
                                <th class="">Networth</th>
                                <th class="">DP</th>
                                <th class="">Units Returning</th>
                                <th class="">Unwatch</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($selectedDominion->watchedDominions()->get() as $dominion)
                            @php
                                $dpMultiplierReduction = $militaryCalculator->getDefensiveMultiplierReduction($selectedDominion);

                                $dominionDP = $militaryCalculator->getDefensivePower(
                                   $dominion,
                                   $selectedDominion,
                                   $dominion->land / $selectedDominion->land,
                                   null,
                                   $dpMultiplierReduction,
                                   false, # Ignore ambush
                                   false,
                                   [],
                                   false, # No $invadingUnits  
                                );

                                $dominionFogged = ($dominion->getSpellPerkValue('fog_of_war') == 1);

                            @endphp

                                <tr>
                                    <td>
                                        @if ($dominion->isLocked())
                                            <span data-toggle="tooltip" data-placement="top" title="This dominion has been locked.<br>Reason: <strong>{{ $dominion->getLockedReason($dominion->is_locked) }}</strong>">
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

                                        @if ($spellCalculator->isSpellActive($dominion, 'stasis'))
                                            <span data-toggle="tooltip" data-placement="top" title="Stasis">
                                            <i class="ra ra-emerald fa-lg text-red"</i>
                                            </span>
                                        @endif

                                        @if ($dominion->isMonarch())
                                            <span data-toggle="tooltip" data-placement="top" title="Governor of The Realm">
                                            <i class="fa fa-star fa-lg text-orange"></i>
                                            </span>
                                        @endif

                                        @if ($protectionService->isUnderProtection($dominion))
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $dominion->protection_ticks }} protection tick(s) left">
                                            <i class="ra ra-shield ra-lg text-aqua"></i>
                                            </span>
                                        @endif

                                        <a href="{{ route('dominion.insight.show', $dominion) }}">{{ $dominion->name }}</a>

                                        @if($dominion->id === $selectedDominion->id)
                                        <span class="label label-primary">You</span>
                                        @endif

                                        @if ($dominion->isAbandoned())
                                            <span data-toggle="tooltip" data-placement="top" title="This dominion has been abandoned by its ruler" class="label label-warning"><span>Abandoned</span></span>
                                        @endif
                                    </td>
                                    <td>{{ $dominion->race->name }}</td>
                                    <td><a href="{{ route('dominion.realm', [$dominion->realm->number]) }}"># {{ $dominion->realm->number }}</a></td>
                                    <td>{{ number_format($dominion->land) }}</td>
                                    <td>{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}</td>
                                    <td>{!! $dominionFogged ? '<span class="label label-default">Fog</span>' : number_format($dominionDP) . ' *' !!}</td>
                                    <td>
                                        @if ($militaryCalculator->hasReturningUnits($dominion))
                                            <span class="label label-success">Yes</span>
                                        @else
                                            <span class="text-gray">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('dominion.insight.unwatch-dominion', $dominion) }}" method="post">
                                            @csrf
                                            <input type="hidden" name="dominion_id" value="{{ $dominion->id }}">
                                            <button class="btn btn-success btn-block" type="submit" id="capture"><i class="fas fa-eye-slash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="box-footer">
                    <small class="text-muted">* Defensive Power is calculated with your target defensive modifiers reductions ({{ number_format($dpMultiplierReduction/100,2) }}%). The defensive power shown is with the basic, static perks and do not take into account circumstantial perks such as perks vs. specific types of targets or perks based on specific unit compositions.</small>
                </div>
            </div>
        </div>

        {{-- 
        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>This is a list of dominions you have chosen to watch.</p>
                </div>
            </div>
        </div>
        --}}

    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#dominions-table').DataTable({
                order: [[3, 'desc']],
            });
        })(jQuery);
    </script>
@endpush
