@extends ('layouts.master')
@section('title', 'Magic')
@section('content')
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="ra ra-fairy-wand "></i> Magic</h3>
                <small class="pull-right text-muted">
                    <span data-toggle="tooltip" data-placement="top" title="Wizards Per Acre (Wizard Ratio) on offense">WPA</span>: {{ number_format($magicCalculator->getWizardRatio($selectedDominion, 'offense'),3) }},
                    <span data-toggle="tooltip" data-placement="top" title="Wizard Strength">WS</span>: {{ $selectedDominion->wizard_strength }}%,
                    Mana: {{ number_format($selectedDominion->resource_mana) }},
                    Magic Level: {{ $magicCalculator->getMagicLevel($selectedDominion) }}
                </small>
            </div>
                <div class="box-body">
                    @for ($i = 0; $i <= $magicCalculator->getMagicLevel($selectedDominion); $i++)
                        @php
                            $levelSpells = $magicCalculator->getLevelSpells($selectedDominion, $i);
                        @endphp
                        <div class="row">
                            <div class="col-sm-12 col-md-12">
                                <div class="box">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">
                                            @if($i === 0)
                                                Cantrips
                                            @else
                                                Level {{ $i }} Spells
                                            @endif
                                        </h3>
                                        {{--
                                            <span class="pull-right label label-info" data-toggle="tooltip" data-placement="top" title="Number of spells available at this level">{{ number_format($levelSpells->count()) }}</span>
                                        --}}
                                    </div>
                                    <div class="box-body" id="level{{ $i }}">
                                        {{-- //Columns must be a factor of 12 (1,2,3,4,6,12) --}}
                                        @php
                                            $numOfCols = 4;
                                            $rowCount = 0;
                                            $bootstrapColWidth = 12 / $numOfCols;
                                        @endphp
                                        <div class="row">
                                            @foreach($levelSpells as $spell)
                                                @php
                                                    $canCast = $spellCalculator->canCastSpell($selectedDominion, $spell, $selectedDominion->resource_mana);
                                                    $isActive = $spellCalculator->isSpellActive($selectedDominion, $spell->key);
                                                    $style = ($isActive ? 'success' : 'primary');
                                                @endphp
                                                <div class="col-md-{{ $bootstrapColWidth }}">
                                                    <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                                        @csrf
                                                        <input type="hidden" name="type" value="self_spell">
                                                        <input type="hidden" name="spell" value="{{ $spell->key }}">
                                                        <div class="box box-{{ $style }}">
                                                            <div class="box-header with-border">
                                                                <button type="submit" class="btn btn-{{ $style }} btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
                                                                    {{ $spell->name }}
                                                                </button>
                                                            </div>
                    
                                                            <div class="box-body text-center">
                                                                <ul style="list-style-type: none; margin: 0; padding: 0;">
                                                                    <li>@include('partials.dominion.spell-basics')</li>
                                                                    @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                                                        <li>{{ $effect }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                    
                                                @php
                                                    $rowCount++;
                                                @endphp
                    
                                                @if($rowCount % $numOfCols == 0)
                                                    </div><div class="row">
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endfor
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>Here you can cast spells on your own dominion. They take effect immediately.</p>
                <p>Cantrips do not require any mana to be cast. Spells level 1 and higher require mana.</p>
                <p><i class="fas fa-hourglass-start"></i> Duration: how many ticks this spell will last.</p>
                <p><i class="fas fa-hourglass-end"></i> Cooldown: how many ticks you must wait until you can cast this spell again. Not all spells have a cooldown.</p>
                <p>WS: this is the percentage of your wizard strength that will be used to cast the spell.</p>

                <a href="{{ route('scribes.spells') }}"><span><i class="fas fa-book"></i> Read more about Spells in the Scribes.</span></a>
            </div>
        </div>
        @if($selectedDominion->race->key == 'afflicted')
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa-solid fa-virus"></i> Pestilence</h3>
                </div>
                <div class="box-body table-responsive box-border">
                    @if($pestilences->count())
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Target</th>
                                    <th>Spell</th>
                                    <th>Duration</th>
                            </thead>
                            <tbody>
                            @foreach($pestilences as $pestilence)
                                <tr>
                                    <td><a href="{{ route('dominion.insight.show', $pestilence->dominion) }}">{{ $pestilence->dominion->name }} (# {{ $pestilence->dominion->realm->number }})<a></td>
                                    <td>{{ $pestilence->spell->name }}</td>
                                    <td>{{ $pestilence->duration }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>You have no pestilences active.</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

@push('page-scripts')
    <script type="text/javascript">
        $("form").submit(function () {
            // prevent duplicate form submissions
            $(this).find(":submit").attr('disabled', 'disabled');
        });
    </script>
@endpush