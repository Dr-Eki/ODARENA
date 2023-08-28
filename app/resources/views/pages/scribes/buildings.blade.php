@extends('layouts.topnav')
@section('title', "Scribes | Buildings")

@section('content')
@include('partials.scribes.nav')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Buildings</h3>
    </div>
    <div class="box-body">
        <p>It takes 12 ticks to construct a building.</p>
        <p>Holy Buildings are only available and effective if you are devoted to the corresponding deity.</p>
    </div>
</div>

<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Buildings</h3>
    </div>

    <div class="box-body table-responsive">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Building</th>
                            <th>Perks</th>
                        </tr>
                    </thead>
                    @foreach ($buildings->where('deity',null) as $building)
                        <tr>
                            <td>
                                {{ $building->name }}
                                {!! $buildingHelper->getExclusivityString($building) !!}
                            </td>
                            <td>
                                {!! $buildingHelper->getBuildingDescription($building) !!}
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>

<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Holy Buildings</h3>
    </div>

    <div class="box-body table-responsive">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Building</th>
                            <th>Deity</th>
                            <th>Perks</th>
                        </tr>
                    </thead>
                    @foreach ($buildings->whereNotNull('deity') as $building)
                        <tr>
                            <td>
                                {{ $building->name }}
                                {!! $buildingHelper->getExclusivityString($building) !!}
                            </td>
                            <td>
                                {{ $building->deity->name }}
                            </td>
                            <td>
                                {!! $buildingHelper->getBuildingDescription($building) !!}
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
