@extends('layouts.topnav')
@section('title', "Scribes | Research")

@section('content')
@include('partials.scribes.nav')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Research</h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-12">
                <p>You can research technologies to enhance different aspects of your dominion.</p>
                <p>Each technology takes 96 ticks to research. Bonuses can affect the duration.</p>
                <p>You can only research as many simultaneous technologies as you have research slots. Most factions start with one research slot.</p>
                <p>If you have multiple technologies with the same perk, only the highest perk will apply.</p>
                @foreach($techs as $techs)
                    <a href="#{{ $techs->name }}">{{ $techs->name }}</a> |
                @endforeach

            </div>
        </div>
    </div>
</div>
    @foreach ($techsWithLevel as $level => $techWithLevel)
        <div class="box box-body">
            <div class="box-header with-border">
                <h3 class="box-title">Tier {{ $level }}</h3>
            </div>
            @foreach($techWithLevel as $tech)
                @php
                    $techsRequired = $researchCalculator->getTechsRequired($tech);
                    $techsLeadTo = $researchCalculator->getTechsLeadTo($tech);
                @endphp
                <div class="box">
                    <div class="box-header with-border">
                        <a id="{{ $tech->name }}"></a><h3 class="box-title">{{ $tech->name }}</h3>

                        @if($researchHelper->hasExclusivity($tech))
                            {!! $researchHelper->getExclusivityString($tech) !!}
                        @endif
                    </div>


                    <div class="row">
                        <div class="col-sm-4">
                            <div class="box-header with-border text-center">
                                <b>Perks</b>
                            </div>
                            <div class="box-body">
                                <ul style="list-style-type: none">
                                @foreach($researchHelper->getTechPerkDescription($tech) as $effect)
                                    <li>{{ $effect }}</li>
                                @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <div class="box-header with-border text-center">
                                <b>Requires</b>
                            </div>
                            <div class="box-body no-border text-center">
                                <span class="text-muted">
                                    @if($techsRequired->count() > 0)
                                        @foreach($techsRequired as $techRequired)
                                            <a href="#{{ $techRequired->name }}">{{ $techRequired->name }}</a><br>
                                        @endforeach
                                    @else
                                        <em>None</em>
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <div class="box-header with-border text-center">
                                <b>Leads To</b>
                            </div>
                            <div class="box-body no-border text-center">
                                <span class="text-muted">
                                    @if($techsLeadTo->count() > 0)
                                        @foreach($techsLeadTo as $techLeadTo)
                                            <a href="#{{ $techLeadTo->name }}">{{ $techLeadTo->name }}</a><br>
                                        @endforeach
                                    @else
                                        <em>None</em>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
@endsection
