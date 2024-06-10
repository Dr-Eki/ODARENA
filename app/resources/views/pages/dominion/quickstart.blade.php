@extends('layouts.master')
@section('title', 'Quickstart')

@section('content')
<div class="row">

    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-fast-forward fa-fw"></i> Quickstart</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-lg-12">
                        <pre>
                            {{ $quickstart }}
                        </pre>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <a href="{{ route(Route::currentRouteName()) }}" class="btn btn-primary">
                    <i class="fas fa-redo fa-fw"></i> Refresh
                </a>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>Click save to save this quickstart to your <a href="{{ route('dashboard.quickstarts') }}">Quickstarts</a>.</p>
                <p>Note that if you save the same dominion multiple times, each save will generate a new quickstart.</p>

                <form action="{{ route('dashboard.quickstarts.save', $selectedDominion) }}" method="post">
                    <table class="table table-striped">
                        <tr>
                            <td>Name:</td>
                            <td><input type="text" name="name" value="" placeholder="{{ $selectedDominion->race->name }} ({{ $selectedDominion->title->name }}) by {{ $selectedDominion->user->display_name }}"></td>
                        </tr>
                        <tr>
                            <td>OP:</td>
                            <td><input type="number" name="offensive_power" value="0"><br></td>
                        </tr>
                        <tr>
                            <td>DP:</td>
                            <td><input type="number" name="defensive_power" value="0"><br></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                Description:<br>
                                <textarea name="description" class="form-control" rows="3"></textarea></td>
                            </td>
                        </tr>
                        <tr>
                            <td>Make public:</td>
                            <td><input type="checkbox" name="public" value="1"></td>
                        </tr>
                    </table>
                    @csrf
                    <input type="hidden" name="dominion_id" value="{{ $selectedDominion->id }}">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save fa-fw"></i> Save
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection

