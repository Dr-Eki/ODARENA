@extends ('layouts.master')

{{--
@section('page-header', 'Offensive Ops')
--}}

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
                        <form action="{{ route('dominion.offensive-ops') }}" method="post" role="form">
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
                                                          data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}">
                                                      {{ $dominion->name }} (#{{ $dominion->realm->number }}) - {{ $dominion->race->name }}
                                                  </option>
                                              @endforeach
                                          </select>
                                      </div>
                                  </div>
                              </div>
                              @foreach ($spyops->chunk(2) as $operations)
                                  <div class="row">
                                      @foreach ($operations as $operation)
                                          @if($espionageCalculator->isSpyopAvailableToDominion($selectedDominion, $operation))
                                                  <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center">
                                                      <div class="form-group">
                                                          <button type="submit"
                                                                  name="operation"
                                                                  value="{{ $operation->key }}"
                                                                  class="btn btn-primary btn-block"
                                                                  {{ $selectedDominion->isLocked() || !$espionageCalculator->canPerform($selectedDominion, $operation) ? 'disabled' : null }}>
                                                              {{ $operation->name }}
                                                          </button>
                                                              @foreach($espionageHelper->getSpyopEffectsString($operation) as $effect)
                                                              {{ $effect }}<br>
                                                              @endforeach
                                                      </div>
                                                  </div>
                                          @endif
                                      @endforeach
                                  </div>
                              @endforeach
                              {{-- Removed because everything is now in $spyops
                              @foreach ($theftSpyops->chunk(2) as $operations)
                                  <div class="row">
                                      @foreach ($operations as $operation)
                                          @if($espionageCalculator->isSpyopAvailableToDominion($selectedDominion, $operation))
                                                  <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center">
                                                      <div class="form-group">
                                                          <button type="submit"
                                                                  name="operation"
                                                                  value="{{ $operation->key }}"
                                                                  class="btn btn-primary btn-block"
                                                                  {{ $selectedDominion->isLocked() || !$espionageCalculator->canPerform($selectedDominion, $operation) ? 'disabled' : null }}>
                                                              {{ $operation->name }}
                                                          </button>
                                                              @foreach($espionageHelper->getSpyopEffectsString($operation) as $effect)
                                                              {{ $effect }}<br>
                                                              @endforeach
                                                      </div>
                                                  </div>
                                          @endif
                                      @endforeach
                                  </div>
                              @endforeach
                              --}}
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

                        <form action="{{ route('dominion.offensive-ops') }}" method="post" role="form">
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

                                  @foreach ($hostileSpells->chunk(2) as $spells)
                                      <div class="row">
                                          @foreach ($spells as $spell)
                                              @if($spellCalculator->isSpellAvailableToDominion($selectedDominion, $spell))
                                                  @php
                                                      $canCast = $spellCalculator->canCastSpell($selectedDominion, $spell);
                                                  @endphp
                                                  <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center">
                                                      <div class="form-group">
                                                          <button type="submit" name="operation" value="{{ $spell->key }}" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
                                                              {{ $spell->name }}
                                                          </button>
                                                          <p>
                                                          @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                                              {{ $effect }}<br>
                                                          @endforeach
                                                              @include('partials.dominion.spell-basics')
                                                          </p>
                                                      </div>
                                                  </div>
                                              @endif
                                          @endforeach
                                      </div>
                                  @endforeach
                            </div>
                        </form>

                        <div class="box-body">
                            <h4>Active Hostile Spells</h4>
                            <table class="table table-condensed">
                                <colgroup>
                                    <col>
                                    <col>
                                    <col width="100">
                                    <col width="50">
                                </colgroup>
                                <tr>
                                    <th>Dominion</th>
                                    <th>Spell</th>
                                    <th>Duration</th>
                                    <th></th>
                                </tr>
                            @foreach($spellCalculator->getPassiveSpellsCast($selectedDominion, true) as $activePassiveSpellCast)
                                @php
                                    $spell = $spellCalculator->getSpellObjectFromKey($activePassiveSpellCast->spell);
                                @endphp
                                @if($spell->scope == 'hostile')
                                    <tr>
                                        <td><a href="{{ route('dominion.op-center.show', [$activePassiveSpellCast->target_dominion_id]) }}">{{ $activePassiveSpellCast->target_dominion_name }}&nbsp;(#&nbsp;{{ $activePassiveSpellCast->target_dominion_realm_number }})</a></td>
                                        <td>{{ $spell->name }}</td>
                                        <td>{{ $activePassiveSpellCast->duration }} / {{ $spell->duration }}</td>
                                        <td>
                                            <form action="{{ route('dominion.offensive-ops') }}" method="post" role="form">
                                                @csrf
                                                <input type="hidden" name="type" value="spell">
                                                <input type="hidden" name="spell_dominion" value="{{ $activePassiveSpellCast->target_dominion_id }}">
                                                <input type="hidden" name="operation" value="{{ $spell->key }}">
                                                <button type="submit" class="btn btn-primary btn-block">
                                                <i class="ra ra-cycle"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                    <a href="{{ route('dominion.advisors.magic') }}" class="pull-right">Magic Advisor</a>
                </div>
                <div class="box-body">
                    <p>Offensive spy-ops and spells are used to damage or weaken hostile dominions.</p>
                    <p>The total damage you do depends on the base damage and can be further increased or decreased by how strong your spies or wizards are relative to the target's and whether the target has any damage reductions.</p>
                    <p>For information gathering, see <a href="{{ route('dominion.intelligence') }}"><span><i class="fa fa-eye"></i> Op Center</span></a>.</p>
                    <p>
                        <a href="{{ route('scribes.spells') }}"><span><i class="ra ra-scroll-unfurled"></i> Read more about Spells in the Scribes.</span></a><br>
                        <a href="{{ route('scribes.spy-ops') }}"><span><i class="ra ra-scroll-unfurled"></i> Read more about Spy Ops in the Scribes.</span></a>
                    </p>
                </div>
            </div>
        </div>

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
                $('#espionage_dominion').val('{{ session('espionage_dominion') }}').trigger('change.select2').trigger('change');
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
