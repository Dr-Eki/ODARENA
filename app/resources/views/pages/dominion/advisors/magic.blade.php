@extends('layouts.master')

{{--
@section('page-header', 'Magic Advisor')
--}}

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="row">

        <div class="col-md-12 col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-burning-embers"></i> Spells affecting your dominion</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <colgroup>
                            <col width="150">
                            <col>
                            <col width="100">
                            <col width="200">
                            <col width="200">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Spell</th>
                                <th>Effect</th>
                                <th class="text-center">Duration</th>
                                <th class="text-center">Cast By</th>
                                <th class="text-center">Cast At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($spellCalculator->getActiveSpells($selectedDominion) as $activeSpell)
                                <tr>
                                    <td>{{ $activeSpells[$activeSpell->spell]->name }}</td>
                                    <td>
                                        <ul>
                                            @foreach($spellHelper->getSpellEffectsString($activeSpells[$activeSpell->spell]) as $effect)
                                                <li>{{ $effect }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="text-center">{{ $activeSpell->duration }} / {{ $activeSpells[$activeSpell->spell]->duration }}</td>
                                    <td class="text-center">
                                        @if ($activeSpell->cast_by_dominion_id !== null)
                                            <a href="{{ route('dominion.realm', $activeSpell->cast_by_dominion_realm_number) }}">{{ $activeSpell->cast_by_dominion_name }} (#{{ $activeSpell->cast_by_dominion_realm_number }})</a>
                                        @else
                                            <em>Unknown</em>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $activeSpell->created_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
