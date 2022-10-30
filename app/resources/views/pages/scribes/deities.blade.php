@extends('layouts.topnav')
@section('title', "Scribes | Deities")

@section('content')
@include('partials.scribes.nav')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Deities</h3>
    </div>
    <div class="box-body">
        <p>You can devote your dominion to a deity in exchange for some perks (good and bad). For every tick that you remain devoted to a deity, the perks are increased by 0.10% per tick to a maximum of +100%, when the perk values are doubled.</p>
        <p>It takes 48 ticks for a devotion to take effect. Your dominion can only be submitted to one deity at a time. However, you can renounce your deity to select a new one (which resets the ticks counter).</p>
        <p>The range multiplier is the maximum land size range the deity permits you to interact with, unless recently invaded, and takes effect immediately once you submit to a deity. A dominion with a wider range cannot take actions against a dominion with a more narrow range, unless the two ranges overlap.</p>
    </div>
</div>

<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Deities</h3>
    </div>
    <div class="box-body table-responsive">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Deity</th>
                            <th>Perks</th>
                            <th>Spells</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($deities as $deity)
                        <tr>
                            <td>
                                {{ $deity->name }}
                                {!! $deityHelper->getExclusivityString($deity) !!}
                            </td>
                            <td>
                                <ul>
                                    <li>Range multiplier: {{ $deity->range_multiplier }}x</li>
                                    @foreach($deityHelper->getDeityPerksString($deity) as $effect)
                                        <li>{{ ucfirst($effect) }}</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>
                                <ul>
                                    @foreach($deityHelper->getDeitySpells($deity) as $spell)
                                        <li><a href="{{ route('scribes.spells') }}#{{ $spell->name }}" target="_new">{{ $spell->name }}</a></li>
                                    @endforeach
                                </ul>
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
