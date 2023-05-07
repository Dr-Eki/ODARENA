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
@endsection
