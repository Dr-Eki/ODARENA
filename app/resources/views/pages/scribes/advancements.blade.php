@extends('layouts.topnav')
@section('title', "Scribes | Advancements")

@section('content')
@include('partials.scribes.nav')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Advancements</h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-12">
                <p>Advancements are areas of scientific discovery and expertise which can benefit to your dominion. There are in total {{ count($advancements) }} advancements.</p>
                <p>Advancements are levelled up spending XP, which is generated each tick based on your prestige. You can also earn XP from invasions, expeditions, and by successuflly performing hostile spy ops and casting hostile spells on other dominions.</p>
                <p>The higher the level, the higher the base perk value (shown below here) becomes.</p>
                <ul>
                    <li>Levels up to and including 6: <code>[Base Perk] * [Level]</code></li>
                    <li>Levels 7 through 10:  <code>[Base Perk] * ( ( ( 6 - [Level] ) / 2 ) + 6 )</code></li>
                </ul>
                <p>The cost of levelling up Advancements is based on your land size.</p>
                @foreach($advancements as $advancement)
                    <a href="#{{ $advancement->name }}">{{ $advancement->name }}</a> |
                @endforeach

            </div>
        </div>
    </div>
</div>
    @foreach ($advancements as $advancement)
        <div class="box">
            <div class="box-header with-border">
                <a id="{{ $advancement->name }}"></a><h3 class="box-title">{{ $advancement->name }}</h3>

                @if($advancementHelper->hasExclusivity($advancement))
                    {!! $advancementHelper->getExclusivityString($advancement) !!}
                @endif
            </div>
            <div class="row">
                <div class="box-body">        
                    <ul>
                        @foreach($advancement->perks as $perk)
                        @php
                            $advancementPerkBase = $advancementHelper->extractAdvancementPerkValuesForScribes($perk->pivot->value) / 100;
                        @endphp
                        <li>
                            @if($advancementPerkBase > 0)
                                +{{ number_format($advancementPerkBase * 100, 2) }}%
                            @else
                                {{ number_format($advancementPerkBase * 100, 2) }}%
                            @endif

                            {{ $advancementHelper->getAdvancementPerkDescription($perk->key) }}
                        </li>

                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
