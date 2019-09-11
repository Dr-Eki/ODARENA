@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Factions</h3>
        </div>
        <div class="box-body no-padding">
            <div class="row">
                <div class="col-md-12 col-md-6">
                    <div class="box-header with-border">
                        <h4 class="box-title">Commonwealth</h4>
                    </div>
                    <table class="table table-striped" style="margin-bottom: 0">
                        <tbody>
                            @foreach ($goodRaces as $race)
                                <tr>
                                    <td>
                                        <a href="{{ route('scribes.race', str_slug($race['name'])) }}">{{ $race['name'] }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="col-md-12 col-md-6">
                    <div class="box-header with-border">
                        <h4 class="box-title">Empire</h4>
                    </div>
                    <table class="table table-striped" style="margin-bottom: 0">
                        <tbody>
                            @foreach ($evilRaces as $race)
                                <tr>
                                    <td>
                                        <a href="{{ route('scribes.race', $race['name']) }}">{{ $race['name'] }}</a>
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
