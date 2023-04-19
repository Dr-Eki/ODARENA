@extends ('layouts.master')
@section('title', 'Magic')
@section('content')
<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="ra ra-fairy-wand "></i> Magic</h3>
                <small class="pull-right text-muted">
                    <span data-toggle="tooltip" data-placement="top" title="Wizards Per Acre (Wizard Ratio) on offense">WPA</span>: {{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'offense'),3) }},
                    <span data-toggle="tooltip" data-placement="top" title="Wizard Strength">WS</span>: {{ $selectedDominion->wizard_strength }}%,
                    Mana: {{ number_format($resourceCalculator->getAmount($selectedDominion, 'mana')) }},
                    Magic Level: {{ $magicCalculator->getMagicLevel($selectedDominion) }}
                </small>
            </div>
                <div class="box-body">
                    @for ($i = 0; $i <= $magicCalculator->getMagicLevel($selectedDominion); $i++)
                        <div class="row">
                            <div class="col-sm-12 col-md-12" id="level {{ $i }}">
                                <div class="box">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">Level {{ $i }}</h3>
                                    </div>
                                    <div class="box-body">
                                        {{-- //Columns must be a factor of 12 (1,2,3,4,6,12) --}}
                                        @php
                                            $numOfCols = 3;
                                            $rowCount = 0;
                                            $bootstrapColWidth = 12 / $numOfCols;
                                        @endphp
                                        <div class="row">
                                            @foreach($magicCalculator->getLevelSpells($selectedDominion, $i) as $spell)
                                                @php
                                                    $canCast = $spellCalculator->canCastSpell($selectedDominion, $spell, $resourceCalculator->getAmount($selectedDominion, 'mana'));
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
                    
                                                            <div class="box-body">
                                                                
                                                                <ul>
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
                
                {{--
                <table class="table table-striped">
                    <colgroup>
                        <col width="180">
                    </colgroup>
                    @foreach($selfSpells as $spell)
                                @php
                                    $canCast = $spellCalculator->canCastSpell($selectedDominion, $spell, $resourceCalculator->getAmount($selectedDominion, 'mana'));
                                    $isActive = $spellCalculator->isSpellActive($selectedDominion, $spell->key);
                                    $buttonStyle = ($isActive ? 'btn-success' : 'btn-primary');
                                @endphp
                                <tr>
                                    <td>
                                        <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                            @csrf
                                            <input type="hidden" name="type" value="self_spell">
                                            <input type="hidden" name="spell" value="{{ $spell->key }}">
                                            <button type="submit" class="btn {{ $buttonStyle }} btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
                                                {{ $spell->name }}
                                            </button>                                            
                                        </form>
                                    </td>
                                    <td>
                                        <ul>
                                            @foreach($spellHelper->getSpellEffectsString($spell, $selectedDominion->race) as $effect)
                                                <li>{{ $effect }}</li>
                                            @endforeach
                                                @include('partials.dominion.spell-basics')
                                        </ul>
                                    </td>
                                </tr>
                    @endforeach
                </table>
                --}}
            </div>
        </div>
    </div>

    {{-- 
    <div class="col-sm-12 col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="ra ra-incense"></i> Friendly Spells</h3>
                <small class="pull-right text-muted">
                    <span data-toggle="tooltip" data-placement="top" title="Wizards Per Acre (Wizard Ratio) on offense">WPA</span>: {{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'offense'),3) }},
                    <span data-toggle="tooltip" data-placement="top" title="Wizard Strength">WS</span>: {{ $selectedDominion->wizard_strength }}%,
                    Mana: {{ number_format($resourceCalculator->getAmount($selectedDominion, 'mana')) }}
                </small>
            </div>

            <form action="{{ route('dominion.magic') }}" method="post" role="form">
                @csrf
                <input type="hidden" name="type" value="friendly_spell">

                <div class="box-body">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <select name="friendly_dominion" id="friendly_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    <option></option>
                                    @foreach ($rangeCalculator->getFriendlyDominionsInRange($selectedDominion) as $dominion)
                                        <option value="{{ $dominion->id }}"
                                                data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}"
                                                data-networth="{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}"
                                                data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}">
                                            {{ $dominion->name }} (#{{ $dominion->realm->number }}) - {{ $dominion->race->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    @foreach ($friendlySpells->chunk(2) as $spells)
                        <div class="row">
                        @foreach ($spells as $spell)
                            @if($spellCalculator->isSpellAvailableToDominion($selectedDominion, $spell))
                                @php
                                $canCast = $spellCalculator->canCastSpell($selectedDominion, $spell, $resourceCalculator->getAmount($selectedDominion, 'mana'));
                                @endphp
                                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center">
                                <div class="form-group">

                                <input type="hidden" name="spell" value="{{ $spell->key }}">
                                <button type="submit" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
                                    {{ $spell->name }}
                                </button>
                                <p>
                                @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                    {{ $effect }}<br>
                                @endforeach
                                @include('partials.dominion.spell-basics')
                                </p>
                                </div>
                            @endif
                        @endforeach
                        </div>
                    @endforeach
                </div>
            </form>

            <div class="box-body">
                <h4>Friendly Spells You Have Cast</h4>
                <table class="table table-condensed">
                    <colgroup>
                        <col>
                        <col>
                        <col width="100">
                        <col width="50">
                    </colgroup>
                    <tr>
                        <th>Cast On</th>
                        <th>Spell</th>
                        <th>Duration</th>
                        <th></th>
                    </tr>
                @foreach($spellCalculator->getPassiveSpellsCastByDominion($selectedDominion, 'friendly') as $activePassiveSpellCast)
                        <tr>
                            <td><a href="{{ route('dominion.insight.show', [$activePassiveSpellCast->dominion->id]) }}">{{ $activePassiveSpellCast->dominion->name }}&nbsp;(#&nbsp;{{ $activePassiveSpellCast->dominion->realm->number }})</a></td>
                            <td>{{ $activePassiveSpellCast->spell->name }}</td>
                            <td>{{ $activePassiveSpellCast->duration }} / {{ $activePassiveSpellCast->spell->duration }}</td>
                            <td>
                                <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                    @csrf
                                    <input type="hidden" name="type" value="friendly_spell">
                                    <input type="hidden" name="friendly_dominion" value="{{ $activePassiveSpellCast->target_dominion_id }}">
                                    <input type="hidden" name="spell" value="{{ $activePassiveSpellCast->spell->key }}">
                                    <button type="submit" class="btn btn-primary btn-block">
                                    <i class="ra ra-cycle"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                @endforeach
                </table>


                <h4>Friendly Spells Cast On You</h4>
                <table class="table table-condensed">
                    <colgroup>
                        <col>
                        <col>
                        <col width="100">
                        <col width="50">
                    </colgroup>
                    <tr>
                        <th>Cast By</th>
                        <th>Spell</th>
                        <th>Duration</th>
                        <th></th>
                    </tr>
                @foreach($spellCalculator->getPassiveSpellsCastOnDominion($selectedDominion, 'friendly') as $activePassiveSpellCast)
                        <tr>
                            <td><a href="{{ route('dominion.insight.show', [$activePassiveSpellCast->caster->id]) }}">{{ $activePassiveSpellCast->caster->name }}&nbsp;(#&nbsp;{{ $activePassiveSpellCast->caster->realm->number }})</a></td>
                            <td>{{ $activePassiveSpellCast->spell->name }}</td>
                            <td>{{ $activePassiveSpellCast->duration }} / {{ $activePassiveSpellCast->spell->duration }}</td>
                            <td></td>
                        </tr>
                @endforeach
                </table>
            </div>
        </div>
    </div>
    --}}

    {{--
    <div class="col-sm-12 col-md-4">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>You can cast spells on yourself and some factions can even cast spells on friendly dominions. These spells are always successful.</p>

                <a href="{{ route('scribes.spells') }}"><span><i class="ra ra-scroll-unfurled"></i> Read more about Spells in the Scribes.</span></a>
            </div>
        </div>
    </div>
    --}}
</div>

    @push('page-styles')
        <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
    @endpush

    @push('page-scripts')
        <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
        <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
    @endpush

    @push('inline-scripts')
        <script type="text/javascript">
            (function ($) {
                $('#dominions-table').DataTable({
                    order: [[4, 'desc']],
                });
                //$('#clairvoyance-table').DataTable({
                //    order: [[2, 'desc']],
                //});
            })(jQuery);
        </script>
    @endpush

@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
@endpush

@push('page-scripts')
    <script type="text/javascript">
    $("form").submit(function () {
        // prevent duplicate form submissions
        $(this).find(":submit").attr('disabled', 'disabled');
    });
    </script>
@endpush
