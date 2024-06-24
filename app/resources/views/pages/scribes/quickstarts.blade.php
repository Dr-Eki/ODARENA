@extends('layouts.topnav')
@section('title', "Scribes | Quickstarts")

@section('content')
@include('partials.scribes.nav')

<div class="row">
    <div class="col-md-12 col-md-12">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h4 class="box-title">Quickstarts</h4>
            </div>
            <table class="table table-striped" style="margin-bottom: 0">
                <colgroup>
                    <col width="50">
                    <col width="150">
                    <col>
                    <col width="150">
                    <col width="150">
                    <col width="150">
                    <col width="150">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">ID</th>
                        <th>Author</th>
                        <th>Name</th>
                        <th>Faction</th>
                        <th>Title</th>
                        <th>Deity</th>
                        <th>Remaing Ticks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quickstarts->sortByDesc('id')->filter(function ($quickstart) use ($user) {
                            return $quickstart->is_public == 1 or (Auth::user() and $quickstart->user_id == Auth::user()->id);
                        }) as $quickstart)
                        <tr>
                            <td class="text-center">{{ $quickstart->id }}</td>
                            <td>{{ optional($quickstart->user)->display_name ?? '-' }}</td>
                            <td><a href="{{ route('scribes.quickstart', $quickstart->id) }}">{{ $quickstart->name }}</a></td>
                            <td>{{ $quickstart->race->name }}</td>
                            <td>{{ $quickstart->title->name }}</td>
                            <td>{{ optional($quickstart->deity)->name ?? 'None' }}</td>
                            <td>{{ $quickstart->protection_ticks }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>
@endsection
