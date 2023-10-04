@extends('layouts.topnav')
@section('title', "Scribes | Terrain")

@section('content')
@include('partials.scribes.nav')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Terrain</h3>
    </div>
    <div class="box-body">
        <p>Land is divided across various terrains.</p>
        <p>Different terrains provide different <a href="#terrain_perks">Terrain Perks</a> for different factions.</p>
    </div>
</div>

<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Terrain</h3>
    </div>
    <div class="box-body table-responsive">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Terrain</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($terrains as $terrain)
                        <tr>
                            <td>{{ $terrain->name }}</td>
                            <td>{{ $terrain->description }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="box">
    <a name="terrain_perks"></a>
    <div class="box-header with-border">
        <h3 class="box-title">Terrain Perks</h3>
    </div>
    <div class="box-body table-responsive">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-condensed table-striped">
                    <colgroup>
                        <col width="100">
                        @foreach($terrains as $terrain)
                            <col>
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr class="header-row">
                            <th>Faction</th>
                            @foreach($terrains as $terrain)
                                <th>{{ $terrain->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($races as $row => $race)
                            <tr>
                                <td><strong>{{ $race->name }}</strong></td>
                                @foreach($terrains as $terrain)
                                    @php
                                        $raceTerrain = OpenDominion\Models\RaceTerrain::where(['race_id' => $race->id, 'terrain_id' => $terrain->id])->first();
                                    @endphp
                                    <td>
                                        @if($raceTerrain->perks->count())
                                            @foreach($raceTerrain->perks as $perk)
                                                {!! $terrainHelper->getPerkDescription($perk->key, $perk->pivot->value, true) !!}
                                                <br>
                                            @endforeach
                                        @else
                                            <em class="text-muted">None</em>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            
                            @if (($row + 1) % 4 === 0)
                                <tr class="header-row">
                                    <th>Faction</th>
                                    @foreach($terrains as $terrain)
                                        <th>{{ $terrain->name }}</th>
                                    @endforeach
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection