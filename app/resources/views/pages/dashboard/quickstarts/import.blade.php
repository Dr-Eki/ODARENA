@extends('layouts.master')

@section('title', 'Dashboard | Quickstarts')

@section('content')
<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Import Quickstart</h3>
            </div>
            <div class="box-body">
                <p>To import a quickstart, you need the API key and the ID of the quickstart you want to import. You also need to know the source (sim or game) of the quickstart.</p>
                <p>If the import is successful, the quickstart will be added to your quickstarts.</p>

                <form action="{{ route('dashboard.quickstarts.import') }}" method="post">
                    @csrf
                    <div class="form-group row">
                        <label for="api_key" class="col-sm-2 col-form-label">API Key</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="api_key" name="api_key" value="{{ old('api_key') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="quickstart_id" class="col-sm-2 col-form-label">Quickstart ID</label>
                        <div class="col-sm-10">
                            <input type="number" class="form-control" id="quickstart_id" name="quickstart_id" value="{{ old('quickstart_id') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="source" class="col-sm-2 col-form-label">Source</label>
                        <div class="col-sm-10">
                            <select class="form-control" id="source" name="source">
                                <option value="sim">Sim</option>
                                <option value="game">Game</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Import</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
