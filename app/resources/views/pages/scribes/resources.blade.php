@extends('layouts.topnav')
@section('title', "Scribes | Resources")

@section('content')
@include('partials.scribes.nav')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Resources</h3>
    </div>
    <div class="box-body">
        <p>All factions use at least one and most use several resources to train units, construct buildings, cast spells, and invest in improvements.</p>
    </div>
</div>

<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Resources</h3>
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
                            <th>Resource</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($resources as $resource)
                        @foreach($resourceHelper->getRacesByResource($resource) as $race)
                            @php
                                $races[] = $race->name;
                            @endphp
                        @endforeach
                        <tr>
                            <td>{{ $resource->name }}</td>
                            <td>{{ $resource->description }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
