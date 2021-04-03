@extends('layouts.topnav')

@section('content')
@include('partials.scribes.nav')


<div class="row">

    <div class="col-sm-12 col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Factions</h2>
            </div>
        </div>
    </div>

</div>
<div class="row">
    <div class="col-md-12 col-md-4">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h4 class="box-title">The Commonwealth</h4>
            </div>
            <table class="table table-striped" style="margin-bottom: 0">
                <tbody>
                    @foreach ($goodRaces as $race)
                    @if($race['playable'] == 1)
                        <tr>
                            <td>
                                <a href="{{ route('scribes.faction', str_slug($race['name'])) }}">{{ $race['name'] }}</a>
                            </td>
                        </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-12 col-md-4">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h4 class="box-title">The Empire</h4>
            </div>
            <table class="table table-striped" style="margin-bottom: 0">
                <tbody>
                    @foreach ($evilRaces as $race)
                    @if($race['playable'] == 1)
                        <tr>
                            <td>
                                <a href="{{ route('scribes.faction', str_slug($race['name'])) }}">{{ $race['name'] }}</a>
                            </td>
                        </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-12 col-md-4">
        <div class="row">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h4 class="box-title">The Independent</h4>
                </div>
                <table class="table table-striped" style="margin-bottom: 0">
                    <tbody>
                        @foreach ($independentRaces as $race)
                        @if($race['playable'] == 1)
                            <tr>
                                <td>
                                    <a href="{{ route('scribes.faction', str_slug($race['name'])) }}">{{ $race['name'] }}</a>
                                </td>
                            </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>


            <div class="box ">
                <div class="box-header with-border">
                    <h4 class="box-title">Barbarian Horde</h4>
                </div>
                <table class="table table-striped" style="margin-bottom: 0">
                    <tbody>
                        @foreach ($npcRaces as $race)
                            <tr>
                                <td>
                                    <a href="{{ route('scribes.faction', str_slug($race['name'])) }}">{{ $race['name'] }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>


    </div>
</div>
@endsection
