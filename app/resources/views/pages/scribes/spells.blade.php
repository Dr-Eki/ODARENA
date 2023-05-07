@extends('layouts.topnav')
@section('title', "Scribes | Spells")

@section('content')
@include('partials.scribes.nav')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Spells</h3>
    </div>
    <div class="box-body">
        <div class="col-md-4">
            <h4>Class</h4>
            <ul>
                <li><b>Active</b>: the effect of the spell is immediate and then dissipates. No lingering effect.</li>
                <li><b>Passive</b>: the spell lingers for a specific duration.</li>
                <li><b>Invasion</b>: the spell is triggered automatically during an invasion.</li>
            </ul>
        </div>
        <div class="col-md-4">
            <h4>Scope</h4>
            <ul>
                <li><b>Friendly</b>: cast on dominions in your realm.</li>
                <li><b>Hostile</b>: cast on enemy dominions.</li>
                <li><b>Self</b>: cast on yourself.</li>
            </ul>
        </div>
        <div class="col-md-4">
            <h4>General</h4>
            <ul>
                <li><b>Cost</b>: mana cost multiplied by your land size.</li>
                <li><b>Duration</b>: how long the spell lasts.</li>
                <li><b>Cooldown</b>: time before spell can be cast again.</li>
            </ul>
        </div>
    </div>
</div>

<!-- BEGIN AURA -->
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Passive Spells</h3>
    </div>

    <div class="box-header">
        <h4 class="box-title">Friendly Passive Spells</h4>
    </div>
    <div class="box-body table-responsive">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                        <col width="50">
                        <col width="100">
                        <col width="50">
                        <col width="50">
                        <col width="50">
                        <col width="50">
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Spell</th>
                            <th>Level</th>
                            <th>Deity</th>
                            <th>Cost</th>
                            <th>Wizard Strength</th>
                            <th>Duration</th>
                            <th>Cooldown</th>
                            <th>Effect</th>
                        </tr>
                    </thead>
                    @foreach ($spells as $spell)
                        @if($spell->class == 'passive' and $spell->scope == 'friendly')
                        <tr>
                            <td>
                                {{ $spell->name }}
                                {!! $spellHelper->getExclusivityString($spell) !!}
                            </td>
                            <td>{{ $spell->magic_level }}</td>
                            <td>{!! $spell->deity ? $spell->deity->name : '<span class="text-muted">Any</span>' !!}</td>
                            <td>{{ $spell->cost }}x</td>
                            <td>{{ $spellCalculator->getWizardStrengthCost($spell) }}%</td>
                            <td>{{ $spell->duration }} ticks</td>
                            <td>
                                @if($spell->cooldown > 0)
                                    {{ $spell->cooldown }} ticks
                                @else
                                    None
                                @endif
                            </td>
                            <td>
                                <ul>
                                    @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                        <li>{{ ucfirst($effect) }}</li>
                                    @endforeach
                                <ul>
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </table>
            </div>
        </div>
    </div>

    <div class="box-header">
        <h4 class="box-title">Hostile Passive Spells</h4>
    </div>
    <div class="box-body table-responsive">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                        <col width="50">
                        <col width="100">
                        <col width="50">
                        <col width="50">
                        <col width="50">
                        <col width="50">
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Spell</th>
                            <th>Level</th>
                            <th>Deity</th>
                            <th>Cost</th>
                            <th>Wizard Strength</th>
                            <th>Duration</th>
                            <th>Cooldown</th>
                            <th>Effect</th>
                        </tr>
                    </thead>
                    @foreach ($spells as $spell)
                        @if($spell->class == 'passive' and $spell->scope == 'hostile')
                        <a id="{{ $spell->name }}"></a>
                        <tr>
                            <td>
                                {{ $spell->name }}
                                {!! $spellHelper->getExclusivityString($spell) !!}
                            </td>
                            <td>{{ $spell->magic_level }}</td>
                            <td>{!! $spell->deity ? $spell->deity->name : '<span class="text-muted">Any</span>' !!}</td>
                            <td>{{ $spell->cost }}x</td>
                            <td>{{ $spellCalculator->getWizardStrengthCost($spell) }}%</td>
                            <td>{{ $spell->duration }} ticks</td>
                            <td>
                                @if($spell->cooldown > 0)
                                    {{ $spell->cooldown }} ticks
                                @else
                                    None
                                @endif
                            </td>
                            <td>
                                <ul>
                                    @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                        <li>{{ ucfirst($effect) }}</li>
                                    @endforeach
                                <ul>
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </table>
            </div>
        </div>
    </div>

    <div class="box-header">
        <h4 class="box-title">Passive Self Spells</h4>
    </div>
    <div class="box-body table-responsive">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                        <col width="50">
                        <col width="100">
                        <col width="50">
                        <col width="50">
                        <col width="50">
                        <col width="50">
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Spell</th>
                            <th>Level</th>
                            <th>Deity</th>
                            <th>Cost</th>
                            <th>Wizard Strength</th>
                            <th>Duration</th>
                            <th>Cooldown</th>
                            <th>Effect</th>
                        </tr>
                    </thead>
                    @foreach ($spells as $spell)
                        @php
                            $exclusives = count($spell->exclusive_races);
                            $excludes = count($spell->excluded_races);
                        @endphp
                        @if($spell->class == 'passive' and $spell->scope == 'self')
                        <a id="{{ $spell->name }}"></a>
                        <tr>
                            <td>
                                {{ $spell->name }}
                                {!! $spellHelper->getExclusivityString($spell) !!}
                            </td>
                            <td>{{ $spell->magic_level }}</td>
                            <td>{!! $spell->deity ? $spell->deity->name : '<span class="text-muted">Any</span>' !!}</td>
                            <td>{{ $spell->cost }}x</td>
                            <td>{{ $spellCalculator->getWizardStrengthCost($spell) }}%</td>
                            <td>{{ $spell->duration }} ticks</td>
                            <td>
                                @if($spell->cooldown > 0)
                                    {{ $spell->cooldown }} ticks
                                @else
                                    None
                                @endif
                            </td>
                            <td>
                                <ul>
                                    @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                        <li>{{ ucfirst($effect) }}</li>
                                    @endforeach
                                <ul>
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </table>
            </div>
        </div>
    </div>
    <!-- END AURA -->

    <!-- BEGIN IMPACT -->
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Active Spells</h3>
        </div>

        <div class="box-header">
            <h4 class="box-title">Friendly Active Spells</h4>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-striped">
                        <colgroup>
                            <col width="200">
                            <col width="50">
                            <col width="100">
                            <col width="50">
                            <col width="50">
                            <col width="50">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Spell</th>
                                <th>Level</th>
                                <th>Deity</th>
                                <th>Cost</th>
                                <th>Wizard Strength</th>
                                <th>Cooldown</th>
                                <th>Effect</th>
                            </tr>
                        </thead>
                        @foreach ($spells as $spell)
                            @if($spell->class == 'active' and $spell->scope == 'friendly')
                            <a id="{{ $spell->name }}"></a>
                            <tr>
                                <td>
                                    {{ $spell->name }}
                                    {!! $spellHelper->getExclusivityString($spell) !!}
                                </td>
                                <td>{{ $spell->magic_level }}</td>
                                <td>{!! $spell->deity ? $spell->deity->name : '<span class="text-muted">Any</span>' !!}</td>
                                <td>{{ $spell->cost }}x</td>
                                <td>{{ $spellCalculator->getWizardStrengthCost($spell) }}%</td>
                                <td>
                                    @if($spell->cooldown > 0)
                                        {{ $spell->cooldown }} ticks
                                    @else
                                        None
                                    @endif
                                </td>
                                <td>
                                    <ul>
                                        @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                            <li>{{ ucfirst($effect) }}</li>
                                        @endforeach
                                    <ul>
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </table>
                </div>
            </div>
        </div>

        <div class="box-header">
            <h4 class="box-title">Hostile Active Spells</h4>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-striped">
                        <colgroup>
                            <col width="200">
                            <col width="50">
                            <col width="100">
                            <col width="50">
                            <col width="50">
                            <col width="50">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Spell</th>
                                <th>Level</th>
                                <th>Deity</th>
                                <th>Cost</th>
                                <th>Wizard Strength</th>
                                <th>Cooldown</th>
                                <th>Effect</th>
                            </tr>
                        </thead>
                        @foreach ($spells as $spell)
                            @if($spell->class == 'active' and $spell->scope == 'hostile')
                            <a id="{{ $spell->name }}"></a>
                            <tr>
                                <td>
                                    {{ $spell->name }}
                                    {!! $spellHelper->getExclusivityString($spell) !!}
                                </td>
                                <td>{{ $spell->magic_level }}</td>
                                <td>{!! $spell->deity ? $spell->deity->name : '<span class="text-muted">Any</span>' !!}</td>
                                <td>{{ $spell->cost }}x</td>
                                <td>{{ $spellCalculator->getWizardStrengthCost($spell) }}%</td>
                                <td>
                                    @if($spell->cooldown > 0)
                                        {{ $spell->cooldown }} ticks
                                    @else
                                        None
                                    @endif
                                </td>
                                <td>
                                    <ul>
                                        @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                            <li>{{ ucfirst($effect) }}</li>
                                        @endforeach
                                    <ul>
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </table>
                </div>
            </div>
        </div>


        <div class="box-header">
            <h4 class="box-title">Active Self Spells</h4>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-striped">
                        <colgroup>
                            <col width="200">
                            <col width="50">
                            <col width="100">
                            <col width="50">
                            <col width="50">
                            <col width="50">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Spell</th>
                                <th>Level</th>
                                <th>Deity</th>
                                <th>Cost</th>
                                <th>Wizard Strength</th>
                                <th>Cooldown</th>
                                <th>Effect</th>
                            </tr>
                        </thead>
                        @foreach ($spells as $spell)
                            @if($spell->class == 'active' and $spell->scope == 'self')
                            <a id="{{ $spell->name }}"></a>
                            <tr>
                                <td>
                                    {{ $spell->name }}
                                    {!! $spellHelper->getExclusivityString($spell) !!}
                                </td>
                                <td>{{ $spell->magic_level }}</td>
                                <td>{!! $spell->deity ? $spell->deity->name : '<span class="text-muted">Any</span>' !!}</td>
                                <td>{{ $spell->cost }}x</td>
                                <td>{{ $spellCalculator->getWizardStrengthCost($spell) }}%</td>
                                <td>
                                    @if($spell->cooldown > 0)
                                        {{ $spell->cooldown }} ticks
                                    @else
                                        None
                                    @endif
                                </td>
                                <td>
                                    <ul>
                                        @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                            <li>{{ ucfirst($effect) }}</li>
                                        @endforeach
                                    <ul>
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
        <!-- END IMPACT -->

        <!-- BEGIN INVASION -->

        <div class="box-header">
            <h4 class="box-title">Invasion Spells</h4>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-striped">
                        <colgroup>
                            <col width="200">
                            <col width="50">
                            <col width="100">
                            <col width="50">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Spell</th>
                                <th>Level</th>
                                <th>Deity</th>
                                <th>Duration</th>
                                <th>Effect</th>
                            </tr>
                        </thead>
                        @foreach ($spells as $spell)
                            @if($spell->class == 'invasion' and $spell->scope == 'hostile')
                            <a id="{{ $spell->name }}"></a>
                            <tr>
                                <td>
                                    {{ $spell->name }}
                                    {!! $spellHelper->getExclusivityString($spell) !!}
                                </td>
                                <td>{{ $spell->magic_level }}</td>
                                <td>{!! $spell->deity ? $spell->deity->name : '<span class="text-muted">Any</span>' !!}</td>
                                <td>{{ $spell->duration }} ticks</td>
                                <td>
                                    <ul>
                                        @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                            <li>{{ ucfirst($effect) }}</li>
                                        @endforeach
                                    <ul>
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
        <!-- END INVASION -->
</div>
@endsection
