@extends ('layouts.master')

@section('page-header', 'Magic')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="row">

                <div class="col-md-6">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-user-secret"></i> Espionage</h3>
                            <small class="pull-right text-muted">
                                <span data-toggle="tooltip" data-placement="top" title="Spies Per Acre (Spy Ratio) on offense">SPA</span>: {{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'offense'),3) }},
                                <span data-toggle="tooltip" data-placement="top" title="Spy Strength">SS</span>: {{ $selectedDominion->spy_strength }}%
                            </small>
                        </div>
                        <form action="{{ route('dominion.intelligence') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="type" value="espionage">

                            <div class="box-body">
                              <div class="row">
                                  <div class="col-md-12">
                                      <div class="form-group">
                                          <select name="espionage_dominion" id="espionage_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                              <option></option>
                                              @foreach ($rangeCalculator->getDominionsInRange($selectedDominion) as $dominion)
                                                  <option value="{{ $dominion->id }}"
                                                          data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}"
                                                          data-networth="{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}"
                                                          data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}"
                                                          data-war="{{ ($selectedDominion->realm->war_realm_id == $dominion->realm->id || $dominion->realm->war_realm_id == $selectedDominion->realm->id) ? 1 : 0 }}">
                                                      {{ $dominion->name }} (#{{ $dominion->realm->number }}) - {{ $dominion->race->name }}
                                                  </option>
                                              @endforeach
                                          </select>
                                      </div>
                                  </div>
                              </div>
                              @foreach ($espionageHelper->getInfoGatheringOperations()->chunk(4) as $operations)
                                  <div class="row">
                                      @foreach ($operations as $operation)
                                          <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                              <div class="form-group">
                                                  <button type="submit" name="operation" value="{{ $operation['key'] }}" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() || !$espionageCalculator->canPerform($selectedDominion, $operation['key']) ? 'disabled' : null }}>
                                                      {{ $operation['name'] }}
                                                  </button>
                                                  <p>{{ $operation['description'] }}</p>
                                              </div>
                                          </div>
                                      @endforeach
                                  </div>
                              @endforeach
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-fairy-wand"></i> Magic</h3>
                            <small class="pull-right text-muted">
                                <span data-toggle="tooltip" data-placement="top" title="Wizards Per Acre (Wizard Ratio) on offense">WPA</span>: {{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'offense'),3) }},
                                <span data-toggle="tooltip" data-placement="top" title="Wizard Strength">WS</span>: {{ $selectedDominion->wizard_strength }}%,
                                Mana: {{ number_format($selectedDominion->resource_mana) }}
                            </small>
                        </div>

                        <form action="{{ route('dominion.intelligence') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="type" value="spell">

                            <div class="box-body">

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <select name="spell_dominion" id="spell_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
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

                                <div class="box-body">
                                    <div class="col-md-12">
                                        <table class="table">
                                            <colgroup>
                                                <col width="180">
                                            </colgroup>
                                          @foreach($hostileInfos as $spell)
                                              @php
                                                  $canCast = $spellCalculator->canCastSpell($selectedDominion, $spell);
                                              @endphp
                                              @if($spellCalculator->isSpellAvailableToDominion($selectedDominion, $spell))
                                                  <tr>
                                                      <td>
                                                        <button type="submit" name="operation" value="{{ $spell->key }}" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
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
                    <a href="{{ route('dominion.advisors.magic') }}" class="pull-right">Intelligence Advisor</a>
                </div>
                <div class="box-body">
                    <p>This is where you collect information about other dominions through Espionage and Magic.</p>
                    <p>Successfully obtained information is saved in the <a href="{{ route('dominion.op-center') }}">Op Center</a>.</p>
                    <ul>
                      <li>Spy Strength:  {{ floor($selectedDominion->spy_strength) }}%</li>
                      <li>Spy Ratio (offense): {{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'offense'), 3) }}</li>
                      <li>Wizard Strength:  {{ floor($selectedDominion->wizard_strength) }}%</li>
                      <li>Wizard Ratio (offense): {{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'offense'), 3) }}</li>
                      <li>Mana: {{ number_format($selectedDominion->resource_mana) }}</li>
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
            $('#spell_dominion').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
            $('#spell_dominion').change(function(e) {
                var warStatus = $(this).find(":selected").data('war');
                if (warStatus == 1) {
                    $('.war-spell').removeClass('disabled');
                } else {
                    $('.war-spell').addClass('disabled');
                }
            });
            @if (session('spell_dominion'))
                $('#spell_dominion').val('{{ session('spell_dominion') }}').trigger('change.select2').trigger('change');
            @endif
        })(jQuery);


        (function ($) {
            $('#espionage_dominion').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
            $('#espionage_dominion').change(function(e) {
                var warStatus = $(this).find(":selected").data('war');
                if (warStatus == 1) {
                    $('.war-spell').removeClass('disabled');
                } else {
                    $('.war-spell').addClass('disabled');
                }
            });
            @if (session('espionage_dominion'))
                $('#espionage_dominion').val('{{ session('friendly_dominion') }}').trigger('change.select2').trigger('change');
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
