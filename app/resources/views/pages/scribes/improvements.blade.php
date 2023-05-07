@extends('layouts.topnav')
@section('title', "Scribes | Improvements")

@section('content')
@include('partials.scribes.nav')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Improvements</h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-12">
                <p>The formula used to calculate improvements is:</p>
                <code>[Perk Value] = [Perk Max] * (1 - exp(-[Amount Invested] / ([Coefficient] * [Land] + 15000)))</code>
                <p>The stated maximum below can be exceeded with improvement bonuses such as
            </div>
        </div>
    </div>
</div>

<div class="box">
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
                            <th>Improvement</th>
                            <th>Perks</th>
                        </tr>
                    </thead>
                    @foreach ($improvements as $improvement)
                        <tr>
                            <td>
                                {{ $improvement->name }}
                                {!! $improvementHelper->getExclusivityString($improvement) !!}
                            </td>
                            <td>
                                <table>
                                    <colgroup>
                                        <col width="180">
                                        <col width="80">
                                        <col width="100">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <td><u>Perk</u></td>
                                            <td><u>Max</u></td>
                                            <td><u>Coefficient</u></td>
                                        </tr>
                                @foreach($improvement->perks as $perk)
                                    @php
                                        $improvementPerkMax = number_format($improvementHelper->extractImprovementPerkValuesForScribes($perk->pivot->value)[0]);
                                        $improvementPerkCoefficient = number_format($improvementHelper->extractImprovementPerkValuesForScribes($perk->pivot->value)[1]);
                                        if($improvementPerkMax > 0)
                                        {
                                            $improvementPerkMax = '+' . $improvementPerkMax;
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ ucfirst($improvementHelper->getImprovementPerkDescription($perk->key)) }}</td>
                                        <td>{{ $improvementPerkMax }}%</td>
                                        <td>{{ $improvementPerkCoefficient }}</td>
                                    <tr>
                                @endforeach
                                </table>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
