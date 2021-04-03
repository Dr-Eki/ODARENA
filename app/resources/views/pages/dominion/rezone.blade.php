@extends('layouts.master')

{{--
@section('page-header', 'Rezone Land')
--}}

@section('content')
@if (!(bool)$selectedDominion->race->getPerkValue('cannot_rezone'))
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-refresh"></i> Re-zone Land</h3>
                </div>
                <form action="{{ route('dominion.rezone') }}" method="post" role="form">
                    @csrf
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="100">
                                <col width="100">
                                <col width="100">
                                <col width="100">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Land type</th>
                                    <th class="text-center">Barren</th>
                                    <th class="text-center">Amount</th> {{-- wording change? --}}
                                    <th class="text-center">Convert to</th>
                                    <th class="text-center">Amount</th>
                                </tr>
                            </thead>
                            @foreach ($landCalculator->getBarrenLandByLandType($selectedDominion) as $landType => $amount)
                                <tr>
                                    <td>
                                        {{ ucfirst($landType) }}
                                        @if ($landType === $selectedDominion->race->home_land_type)
                                            <br>
                                            <small class="text-muted"><i><span title="This is the land type where your {{ strtolower($selectedDominion->race->name) }} race constructs home buildings on">Home land type</span></i></small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($amount) }}</td>
                                    <td class="text-center">
                                        <input name="remove[{{ $landType }}]" type="number"
                                               class="form-control text-center" placeholder="0" min="0"
                                               max="{{ $amount }}"
                                               value="{{ old('remove.' . $landType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </td>
                                    <td class="text-center">{{ ucfirst($landType) }}</td>
                                    <td class="text-center">
                                        <input name="add[{{ $landType }}]" type="number"

                                               class="form-control text-center" placeholder="0" min="0"
                                               max="{{ $rezoningCalculator->getMaxAfford($selectedDominion) }}"
                                               value="{{ old('add.' . $landType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    <div class="box-footer">
                        <button class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Re-Zone</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                    <a href="{{ route('dominion.advisors.land') }}" class="pull-right">Land Advisor</a>
                </div>
                <div class="box-body">
                    <p>Land rezoning is the art of converting barren land of one type into another type. Rezoning is instant.</p>

                    <p>Each acre costs {{ number_format($rezoningCalculator->getRezoningCost($selectedDominion)) }} {{ $rezoningCalculator->getRezoningMaterial($selectedDominion) }} to rezone.</p>

                    @if (1-$rezoningCalculator->getCostMultiplier($selectedDominion) !== 0)
                      <p>Your rezoning costs are

                      @if (1-$rezoningCalculator->getCostMultiplier($selectedDominion) > 0)
                        decreased
                      @else
                        increased
                      @endif

                       by <strong>{{ number_format((abs(1-$rezoningCalculator->getCostMultiplier($selectedDominion)))*100, 2) }}%</strong>.</p>

                    @endif


                    <p>You have {{ number_format($landCalculator->getTotalBarrenLand($selectedDominion)) }} {{ str_plural('acre', $landCalculator->getTotalBarrenLand($selectedDominion)) }} of barren land
                      and you can afford to re-zone <b>{{ number_format($rezoningCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('acre', $rezoningCalculator->getMaxAfford($selectedDominion)) }}</b>.</p>
                </div>
            </div>
        </div>

    </div>
@else
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <p>{{ $selectedDominion->race->name }} cannot rezone land.</p>
        </div>
    </div>
</div>
@endif
@endsection
