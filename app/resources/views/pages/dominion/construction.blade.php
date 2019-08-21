@extends('layouts.master')

@section('page-header', 'Construction')

@section('content')
@if (!(bool)$selectedDominion->race->getPerkValue('cannot_construct'))
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-home"></i> Construct Buildings</h3>
                </div>
                <form action="{{ route('dominion.construct') }}" method="post" role="form">
                    @csrf
                    <div class="box-body no-padding">
                        <div class="row">

                            <div class="col-md-12 col-lg-6">
                                @php
                                    /** @var \Illuminate\Support\Collection $buildingTypesLeft */
                                    $landTypesBuildingTypes = collect($buildingHelper->getBuildingTypesByRace($selectedDominion->race))->filter(function ($buildingTypes, $landType) {
                                        return in_array($landType, ['plain', 'mountain', 'swamp'], true);
                                    });
                                @endphp

                                @include('partials.dominion.construction.table')
                            </div>

                            <div class="col-md-12 col-lg-6">
                                @php
                                    /** @var \Illuminate\Support\Collection $buildingTypesLeft */
                                    $landTypesBuildingTypes = collect($buildingHelper->getBuildingTypesByRace($selectedDominion->race))->filter(function ($buildingTypes, $landType) {
                                        return in_array($landType, ['cavern', 'forest', 'hill', 'water'], true);
                                    });
                                @endphp

                                @include('partials.dominion.construction.table')
                            </div>

                        </div>
                    </div>
                    <div class="box-footer">
                            <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Build</button>
                        <div class="pull-right">
                            You have {{ number_format($landCalculator->getTotalLand($selectedDominion)) }} {{ str_plural('acre', $landCalculator->getTotalLand($selectedDominion)) }} of land.
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                    <a href="{{ route('dominion.advisors.construct') }}" class="pull-right">Construction Advisor</a>
                </div>
                <div class="box-body">
                    <p>Construction will let you construct additional buildings and will take <b>12 hours</b> to process.</p>
                    <p>Construction per building will come at a cost of 1 acre of barren land of the building type, {{ number_format($constructionCalculator->getPlatinumCost($selectedDominion)) }} platinum and {{ number_format($constructionCalculator->getLumberCost($selectedDominion)) }} lumber.</p>
                    <p>You have {{ number_format($landCalculator->getTotalBarrenLand($selectedDominion)) }} {{ str_plural('acre', $landCalculator->getTotalBarrenLand($selectedDominion)) }} of barren land, {{ number_format($selectedDominion->resource_platinum) }} platinum and {{ number_format($selectedDominion->resource_lumber) }} lumber.</p>
                    @if ($selectedDominion->discounted_land)<p>Additionally, {{ $selectedDominion->discounted_land }} acres from invasion can be built at reduced cost.</p>@endif
                    <p>You can afford to construct <b>{{ number_format($constructionCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('building', $constructionCalculator->getMaxAfford($selectedDominion)) }}</b> at that rate.</p>
                    <p>You may also <a href="{{ route('dominion.destroy') }}">destroy buildings</a> if you wish.</p>
                </div>
            </div>
        </div>

    </div>
@else 
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <p>𒄩𒍑𒉣</p>
                <p style="opacity:0.8;">𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣</p>
                <p style="opacity:0.6;">𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣</p>
                <p style="opacity:0.4;">𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣</p>
                <p style="opacity:0.2;">𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣</p>
                <p>Void cannot construct buildings.</p>
                <p style="opacity:0.2;">𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣</p>
                <p style="opacity:0.4;">𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣</p>
                <p style="opacity:0.6;">𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣</p>
                <p style="opacity:0.8;">𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣𒄩𒍑𒉣</p>
                <p>𒄩𒍑𒉣</p>
            </div>

        </div>

    </div>
@endif
@endsection
