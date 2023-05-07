@extends('layouts.topnav')
@section('title', "Scribes | Decrees")

@section('content')
@include('partials.scribes.nav')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Decrees</h3>
    </div>
    <div class="box-body">
        <p>As the ruler of a dominion, you can issue decrees. Each decree has one or more states you can select from. Only one decree state can be active at a time. You must revoke an active state in order to change to a different state.</p>
        <p>Once issued, the decree state perks are immediately active.</p>
        <p>Each decree has a cooldown period, during which you cannot revoke it. The cooldown is not affected by protection ticks. Only round ticks count.</p>
    </div>
</div>

<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Decrees</h3>
    </div>
    <div class="box-body table-responsive">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                        <col width="100">
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Decree</th>
                            <th>Cooldown</th>
                            <th>States</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($decrees as $decree)
                        <tr>
                            <td>
                            {{ $decree->name }}
                            {!! $decreeHelper->getExclusivityString($decree) !!}
                            </td>
                            <td>{{ $decree->cooldown }}</td>
                            <td>
                                @foreach($decree->states as $decreeState)
                                    <u>{{ $decreeState->name }}</u>
                                    {!! $decreeHelper->getDecreeStateDescription($decreeState) !!}
                                @endforeach
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
