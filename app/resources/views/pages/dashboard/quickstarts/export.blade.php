@extends('layouts.master')

@section('title', 'Dashboard | Quickstarts')

@section('content')
<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Export Quickstart</h3>
            </div>
            <div class="box-body">
                @if(!$user->api_key)
                    <p>To export quickstarts, you must first generate a key.</p>
                    <form action="{{ route('dashboard.quickstarts.export') }}" method="post">
                        @csrf
                        <button type="submit" class="btn btn-primary">Generate Key</button>
                    </form>
                @endif

                @if($user->api_key)
                    <p>Exporting a quickstart is done to transfer a quickstart from sim (<a href="https://sim.odarena.com/" target="_new">https://sim.odarena.com/</a>) to the game (<a href="https://odarena.com/" target="_new">https://odarena.com/</a>) or vice versa.</p>
                    <p>To export a dominion as a quickstart, you need your API key and the ID of the dominion you wish to.</p>

                    @if(!isset($selectedDominion))
                        <p>You have not selected a dominion to export quickstarts from.</p>
                        <p>Go to the <a href="{{ route('dashboard') }}">dashboard</a> to select a dominion.</p>
                    @else
                        <p>Your API key is:</p>
                        <p><code>{{ $user->api_key }}</code></p>
                    @endif
                    <hr>
                    <p>To generate a new key, press the button below. This immediately overwrites and invalidates your old key.</p>
                    <form action="{{ route('dashboard.quickstarts.export') }}" method="post">
                        @csrf
                        <button type="submit" class="btn btn-primary">Generate New Key</button>
                    </form>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection
