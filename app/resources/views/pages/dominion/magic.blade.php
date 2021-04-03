@extends ('layouts.master')

{{--
@section('page-header', 'Magic')
--}}

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="row">

                <div class="col-md-4">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-fairy-wand"></i> Self Spells</h3>
                        </div>
                        <form action="{{ route('dominion.magic') }}" method="post" role="form">
                            @csrf
                            <div class="box-body">
                                <table class="table table-striped">
                                    <colgroup>
                                        <col width="180">
                                    </colgroup>
                                  @foreach($selfAuras as $spell)
                                      @php
                                          $canCast = $spellCalculator->canCastSpell($selectedDominion, $spell);
                                          $isActive = $spellCalculator->isSpellActive($selectedDominion, $spell->key);
                                          $buttonStyle = ($isActive ? 'btn-success' : 'btn-primary');
                                      @endphp
                                      @if($spellCalculator->isSpellAvailableToDominion($selectedDominion, $spell))
                                          <tr>
                                              <td>
                                                <button type="submit" name="spell" value="{{ $spell->key }}" class="btn {{ $buttonStyle }} btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
                                                    {{ $spell->name }}
                                                </button>
                                              </td>
                                              <td>
                                                  <ul>
                                                      @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                                          <li>{{ $effect }}</li>
                                                      @endforeach
                                                          @include('partials.dominion.spell-basics')
                                                  </ul>
                                              </td>
                                          </tr>
                                      @endif
                                  @endforeach
                                  </table>

                                  <table class="table table-striped">
                                      <colgroup>
                                          <col width="180">
                                      </colgroup>
                                    @foreach($selfImpacts as $spell)
                                        @php
                                            $canCast = $spellCalculator->canCastSpell($selectedDominion, $spell);
                                            $isActive = $spellCalculator->isSpellActive($selectedDominion, $spell->key);
                                            $buttonStyle = ($isActive ? 'btn-success' : 'btn-primary');
                                        @endphp
                                        @if($spellCalculator->isSpellAvailableToDominion($selectedDominion, $spell))
                                            <tr>
                                                <td>
                                                  <button type="submit" name="spell" value="{{ $spell->key }}" class="btn {{ $buttonStyle }} btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
                                                      {{ $spell->name }}
                                                  </button>
                                                </td>
                                                <td>
                                                    <ul>
                                                        @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                                            <li>{{ $effect }}</li>
                                                        @endforeach
                                                            @include('partials.dominion.spell-basics')
                                                    </ul>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    </table>
                              </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-burning-embers"></i> Hostile Spells</h3>
                        </div>

                        <form action="{{ route('dominion.magic') }}" method="post" role="form">
                            @csrf

                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <select name="target_dominion" id="target_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                <option></option>
                                                @foreach ($rangeCalculator->getDominionsInRange($selectedDominion) as $dominion)
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

                                <div class="row">
                                    <div class="col-md-12">
                                        <p>Information gathering moved to <a href="{{ route('dominion.intelligence') }}"><i class="fa fa-eye fa-fw"></i> <span>Intelligence</span></a></li>.</p>
                                    </div>
                                </div>

                                <div class="box-body">
                                      <div class="col-md-6">
                                          <table class="table">
                                              <colgroup>
                                                  <col width="180">
                                              </colgroup>
                                            @foreach($hostileAuras as $spell)
                                                @php
                                                    $canCast = $spellCalculator->canCastSpell($selectedDominion, $spell);
                                                @endphp
                                                @if($spellCalculator->isSpellAvailableToDominion($selectedDominion, $spell))
                                                    <tr>
                                                        <td>
                                                          <button type="submit" name="spell" value="{{ $spell->key }}" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
                                                              {{ $spell->name }}
                                                          </button>
                                                        </td>
                                                        <td>
                                                            @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                                            {{ $effect }}<br>
                                                            @endforeach
                                                            @include('partials.dominion.spell-basics')
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table">
                                                <colgroup>
                                                    <col width="180">
                                                </colgroup>
                                              @foreach($hostileImpacts as $spell)
                                                  @php
                                                      $canCast = $spellCalculator->canCastSpell($selectedDominion, $spell);
                                                  @endphp
                                                  @if($spellCalculator->isSpellAvailableToDominion($selectedDominion, $spell))
                                                      <tr>
                                                          <td>
                                                            <button type="submit" name="spell" value="{{ $spell->key }}" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
                                                                {{ $spell->name }}
                                                            </button>
                                                          </td>
                                                          <td>
                                                              @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                                              {{ $effect }} <br>
                                                              @endforeach
                                                              @include('partials.dominion.spell-basics')
                                                          </td>
                                                      </tr>
                                                  @endif
                                              @endforeach
                                              </table>
                                          </div>
                                  </div>

                            </div>
                        </form>

                    </div>
                </div>
                <div class="col-md-8">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-incense"></i> Friendly Spells</h3>
                        </div>
                            <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                @csrf
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

                                    <div class="box-body">
                                          <div class="col-md-12">
                                              <table class="table">
                                                  <colgroup>
                                                      <col width="180">
                                                  </colgroup>
                                                @foreach($friendlyAuras as $spell)
                                                    @php
                                                        $canCast = $spellCalculator->canCastSpell($selectedDominion, $spell);
                                                    @endphp
                                                    @if($spellCalculator->isSpellAvailableToDominion($selectedDominion, $spell))
                                                        <tr>
                                                            <td>
                                                              <button type="submit" name="spell" value="{{ $spell->key }}" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
                                                                  {{ $spell->name }}
                                                              </button>
                                                            </td>
                                                            <td>
                                                                <ul>
                                                                    @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                                                        <li>{{ $effect }}</li>
                                                                    @endforeach
                                                                        @include('partials.dominion.spell-basics')
                                                                </ul>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                                @foreach ($friendlyImpacts as $spell)
                                                    @php
                                                        $canCast = $spellCalculator->canCast($selectedDominion, $spell['key']);
                                                    @endphp
                                                    @if($spellCalculator->isSpellAvailableToDominion($selectedDominion, $spell))
                                                        <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3">
                                                            <div class="form-group">
                                                                <button type="submit" name="spell" value="{{ $spell['key'] }}" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
                                                                    {{ $spell['name'] }}
                                                                </button>
                                                                    <ul>
                                                                        @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                                                            <li>{{ $effect }}</li>
                                                                        @endforeach
                                                                            @include('partials.dominion.spell-basics')
                                                                    </ul>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                              </table>
                                        </div>
                                      </div>
                                </div>

                            </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Here you may cast spells which temporarily benefit your dominion or hinder opposing dominions. You can also perform information gathering operations with magic.</p>
                    <p>Self-spells have a duration after which they expire and need to be cast again. Information Gathering spells never expire and </p>
                    <p>Any obtained data after successfully casting an information gathering spell gets posted to the <a href="{{ route('dominion.op-center') }}">Op Center</a> for your realmies.</p>
                    <p>Casting spells spends some wizard strength, but it regenerates a bit every tick.</p>

                    <ul>
                      <li>Mana: {{ number_format($selectedDominion->resource_mana) }}</li>
                      <li>Wizard Strength:  {{ floor($selectedDominion->wizard_strength) }}%</li>
                      <li>Wizard Ratio (offense): {{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'offense'), 3) }}</li>
                      <li>Spell damage modifier: {{ number_format((1-$spellDamageCalculator->getDominionHarmfulSpellDamageModifier($selectedDominion, null, null, null))*100, 2) }}% <span class="text-muted">(base, may vary by spell)</span></li>
                    </ul>

                    <a href="{{ route('scribes.spells') }}"><span><i class="ra ra-scroll-unfurled"></i> Read more about Spells in the Scribes.</span></a>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#target_dominion').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
            $('#target_dominion').change(function(e) {
                var warStatus = $(this).find(":selected").data('war');
                if (warStatus == 1) {
                    $('.war-spell').removeClass('disabled');
                } else {
                    $('.war-spell').addClass('disabled');
                }
            });
            @if (session('target_dominion'))
                $('#target_dominion').val('{{ session('target_dominion') }}').trigger('change.select2').trigger('change');
            @endif
        })(jQuery);


        (function ($) {
            $('#friendly_dominion').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
            $('#friendly_dominion').change(function(e) {
                var warStatus = $(this).find(":selected").data('war');
                if (warStatus == 1) {
                    $('.war-spell').removeClass('disabled');
                } else {
                    $('.war-spell').addClass('disabled');
                }
            });
            @if (session('friendly_dominion'))
                $('#friendly_dominion').val('{{ session('friendly_dominion') }}').trigger('change.select2').trigger('change');
            @endif
        })(jQuery);

        function select2Template(state) {
            if (!state.id) {
                return state.text;
            }

            const land = state.element.dataset.land;
            const percentage = state.element.dataset.percentage;
            const networth = state.element.dataset.networth;
            const war = state.element.dataset.war;
            let difficultyClass;

            if (percentage >= 120) {
                difficultyClass = 'text-red';
            } else if (percentage >= 75) {
                difficultyClass = 'text-green';
            } else if (percentage >= 66) {
                difficultyClass = 'text-muted';
            } else {
                difficultyClass = 'text-gray';
            }

            warStatus = '';
            if (war == 1) {
                warStatus = '<div class="pull-left">&nbsp;<span class="text-red">WAR</span></div>';
            }

            return $(`
                <div class="pull-left">${state.text}</div>
                ${warStatus}
                <div class="pull-right">${land} acres <span class="${difficultyClass}">(${percentage}%)</span> - ${networth} networth</div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush
