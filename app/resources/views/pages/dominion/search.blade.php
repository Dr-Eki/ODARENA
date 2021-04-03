@extends('layouts.master')

{{--
@section('page-header', 'Search Dominions')
--}}

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-search"></i> Search Dominions</h3>
                </div>
                <div class="box-body table-responsive" id="dominion-search">
                    <div class="row no-margin">
                        <div class="col-sm-6 col-md-4 form-horizontal">
                            <div class="form-group">
                                <label class="col-sm-6 control-label text-right">Faction:</label>
                                <div class="col-sm-6">
                                    <select class="form-control" name="race">
                                        <option value="">All</option>
                                        @foreach ($dominions->pluck('race.name')->sort()->unique() as $race)
                                            <option value="{{ $race }}">{{ $race }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-6 control-label text-right">Limit:</label>
                                <div class="col-sm-6">
                                    <select class="form-control" name="range">
                                        <option value="">No Limit</option>
                                        <option value="true">My Range</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4 form-horizontal">
                            <div class="form-group">
                                <label class="col-sm-6 control-label text-right">Land Min:</label>
                                <div class="col-sm-6">
                                    <input type="number" name="landMin" class="form-control input-sm" min="0" placeholder="0" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-6 control-label text-right">Land Max:</label>
                                <div class="col-sm-6">
                                    <input type="number" name="landMax" class="form-control input-sm" placeholder="--" />
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-12 text-right">
                                    <button class="btn btn-default search-range" data-min="{{ ceil($landCalculator->getTotalLand($selectedDominion) * 0.40) }}" data-max="{{ floor($landCalculator->getTotalLand($selectedDominion) / 0.40) }}">40%</button>
                                    <button class="btn btn-danger search-range" data-min="{{ ceil($landCalculator->getTotalLand($selectedDominion) * 0.60) }}" data-max="{{ floor($landCalculator->getTotalLand($selectedDominion) / 0.60) }}">60%</button>
                                    <button class="btn btn-warning search-range" data-min="{{ ceil($landCalculator->getTotalLand($selectedDominion) * 0.75) }}" data-max="{{ floor($landCalculator->getTotalLand($selectedDominion) / 0.75) }}">75%</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4 form-horizontal">
                            <div class="form-group">
                                <label class="col-sm-6 control-label text-right">Networth Min:</label>
                                <div class="col-sm-6">
                                    <input type="number" name="networthMin" class="form-control input-sm" min="0" placeholder="0" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-6 control-label text-right">Networth Max:</label>
                                <div class="col-sm-6">
                                    <input type="number" name="networthMax" class="form-control input-sm" placeholder="--" />
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-6 col-sm-offset-6">
                                    <button id="dominion-search" class="btn btn-block btn-primary">Search</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="table table-hover" id="dominions-table">
                        <colgroup>
                            <col>
                            <col width="100">
                            <col width="100">
                            <col width="100">
                            <col width="100">
                            <col width="100">
                            <col width="100" class="hidden">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Dominion</th>
                                <th class="text-center">Realm</th>
                                <th class="text-center">Faction</th>
                                <th class="text-center">Land</th>
                                <th class="text-center">Networth</th>
                                <th class="text-center">Units<br>Returning</th>
                                <th class="text-center hidden">My Range</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($selectedDominion->round->hasStarted())
                                @foreach ($dominions as $dominion)
                                    @if ($dominion->isLocked())
                                      <tr style="text-decoration:line-through; color: #666">
                                    @else
                                      <tr>
                                    @endif
                                        <td data-search="{{ $dominion->name }}">
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

                                            @if ($spellCalculator->isSpellActive($dominion, 'ragnarok'))
                                                <span data-toggle="tooltip" data-placement="top" title="Ragnarök">
                                                <i class="ra ra-blast fa-lg text-red" title=""></i>
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

                                            @if ($guardMembershipService->isEliteGuardMember($dominion))
                                                <span data-toggle="tooltip" data-placement="top" title="Warriors League">
                                                <i class="ra ra-heavy-shield ra-lg text-yellow"></i>
                                                </span>
                                            @elseif ($guardMembershipService->isBarbarianGuardMember($dominion))
                                                <span data-toggle="tooltip" data-placement="top" title="Ib-Tham's Guard">
                                                <i class="ra ra-heavy-shield ra-lg text-muted"></i>
                                                </span>
                                            @endif

                                            <a href="{{ route('dominion.op-center.show', $dominion) }}">{{ $dominion->name }}</a>
                                            @if($dominion->id === $selectedDominion->id)
                                            <span class="label label-primary">You</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{ $dominion->realm->number }}
                                        </td>
                                        <td class="text-center">
                                            {{ $dominion->race->name }}
                                        </td>
                                        <td class="text-center" data-order="{{ $landCalculator->getTotalLand($dominion) }}" data-search="{{ $landCalculator->getTotalLand($dominion) }}">
                                            {{ number_format($landCalculator->getTotalLand($dominion)) }}
                                        </td>
                                        <td class="text-center" data-order="{{ $networthCalculator->getDominionNetworth($dominion) }}" data-search="{{ $networthCalculator->getDominionNetworth($dominion) }}">
                                            {{ number_format($networthCalculator->getDominionNetworth($dominion)) }}
                                        </td>
                                        <td>
                                            @if ($militaryCalculator->hasReturningUnits($dominion))
                                                <span class="label label-success">Yes</span>
                                            @else
                                                <span class="text-gray">No</span>
                                            @endif
                                        </td>
                                        <td class="hidden">
                                            @if ($rangeCalculator->isInRange($selectedDominion, $dominion) and $selectedDominion->realm->id !== $dominion->realm->id)
                                                true
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
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
                    <p>Use the search to find dominions matching certain criteria.</p>
                    <p>The grey button labelled 40% pre-fills the land min and land max with dominions 40%-250% your range.</p>
                    <p>The green button labelled 60% pre-fills the land min and land max with dominions 60%-166% your range: Barbarian range.</p>
                    <p>The orange button labelled 75% pre-fills the land min and land max with dominions 75%-133% your range: Warriors League range</p>
                    @if (!$selectedDominion->round->hasStarted())
                        <p>The current round has not started. No dominions will be listed.</p>
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
    <style>
        #dominion-search #dominions-table_filter { display: none !important; }
    </style>
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var race = $('select[name=race]').val();
                if (race && race != data[2]) return false;

                var landMin = parseInt($('input[name=landMin]').val());
                var landMax = parseInt($('input[name=landMax]').val());
                var land = parseFloat(data[3]) || 0;

                if (!(isNaN(landMin) && isNaN(landMax)) &&
                    !(isNaN(landMin) && land <= landMax) &&
                    !(landMin <= land && isNaN(landMax)) &&
                    !(landMin <= land && land <= landMax))
                {
                    return false;
                }

                var nwMin = parseInt($('input[name=networthMin]').val());
                var nwMax = parseInt($('input[name=networthMax]').val());
                var nw = parseFloat(data[4]) || 0;

                if (!(isNaN(nwMin) && isNaN(nwMax)) &&
                    !(isNaN(nwMin) && nw <= nwMax) &&
                    !(nwMin <= nw && isNaN(nwMax)) &&
                    !(nwMin <= nw && nw <= nwMax))
                {
                    return false;
                }

                var range = $('select[name=range]').val();
                if (range && data[6] != "true") return false;

                return true;
            }
        );
        (function ($) {
            var table = $('#dominions-table').DataTable({
                order: [[3, 'desc']],
                paging: false,
            });
            $('#dominion-search').click(function() {
                table.draw();
            });
            $('.search-range').click(function() {
                $('input[name=landMin]').val($(this).data('min'));
                $('input[name=landMax]').val($(this).data('max'));
            })
        })(jQuery);
    </script>
@endpush
