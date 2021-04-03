@extends ('layouts.master')

{{--
@section('page-header', 'Op Center')
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
                          @foreach ($infoOps->chunk(2) as $operations)
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



                              @foreach ($hostileInfos->chunk(4) as $spells)
                                  <div class="row">
                                      @foreach ($spells as $spell)
                                          @php
                                              $canCast = $spellCalculator->canCastSpell($selectedDominion, $spell);
                                          @endphp
                                          <div class="col-xs-6 col-sm-4 col-md-6 col-lg-4 text-center">
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
                                      @endforeach
                                  </div>
                              @endforeach

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
                <p>This is where you collect information about other dominions through Espionage and Magic.</p>
                <p>Successfully obtained information is saved in the Op Center.</p>
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

<div class="row">

    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header">
                <h3 class="box-title"><i class="fa fa-eye"></i> Op Center</h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover" id="dominions-table">
                    <colgroup>
                        <col>
                        <col width="100">
                        {{--
                        <col width="100">
                        <col width="100">
                        --}}
                        <col width="100">
                        <col width="100">
                        <col>
                        <col width="130">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Dominion</th>
                            <th class="text-center">Faction</th>
                            {{--
                            <th class="text-center">OP</th>
                            <th class="text-center">DP</th>
                            --}}
                            <th class="text-center">Land</th>
                            <th class="text-center">Networth</th>
                            <th class="text-center">Last Op</th>
                            <th class="text-center">Recent Ops</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($latestInfoOps as $targetDominionOps)
                            @php $lastInfoOp = $targetDominionOps->first(); @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('dominion.op-center.show', $lastInfoOp->targetDominion) }}">{{ $lastInfoOp->targetDominion->name }} (#{{ $lastInfoOp->targetDominion->realm->number }})</a>
                                    @if ($lastInfoOp->isInvalid())
                                        <span class="label label-danger">Invalid</span>
                                    @elseif ($lastInfoOp->isStale())
                                        <span class="label label-warning">Stale</span>
                                    @endif
                                    @if ($lastInfoOp->isInaccurate())
                                        <span class="label label-info">Inaccurate</span>
                                    @endif
                                </td>
                                <td class="text-center" data-search="" data-order="{{ $lastInfoOp->targetDominion->race->name }}">
                                    {{ $lastInfoOp->targetDominion->race->name }}
                                </td>
                                {{--
                                <td class="text-center" data-search="" data-order="{{ $infoOpService->getOffensivePower($selectedDominion->realm, $lastInfoOp->targetDominion) }}">
                                    {{ $infoOpService->getOffensivePowerString($selectedDominion->realm, $lastInfoOp->targetDominion) }}
                                </td>
                                <td class="text-center" data-search="" data-order="{{ $infoOpService->getDefensivePower($selectedDominion->realm, $lastInfoOp->targetDominion) }}">
                                    {{ $infoOpService->getDefensivePowerString($selectedDominion->realm, $lastInfoOp->targetDominion) }}
                                </td>
                                --}}
                                <td class="text-center" data-search="" data-order="{{ $infoOpService->getLand($targetDominionOps) }}">
                                    {{ $infoOpService->getLandString($targetDominionOps) }}
                                    <br>
                                    <span class="small {{ $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $lastInfoOp->targetDominion) }}">
                                        {{ number_format($rangeCalculator->getDominionRange($selectedDominion, $lastInfoOp->targetDominion), 1) }}%
                                    </span>
                                </td>
                                <td class="text-center" data-search="" data-order="{{ $infoOpService->getNetworth($targetDominionOps) }}">
                                    {{ $infoOpService->getNetworthString($targetDominionOps) }}
                                </td>
                                <td class="text-center" data-search="" data-order="{{ $lastInfoOp->created_at->getTimestamp() }}">
                                    {{ $infoOpService->getInfoOpName($lastInfoOp) }}
                                    by
                                    @if ($lastInfoOp->sourceDominion->id === $selectedDominion->id)
                                        <strong>
                                            {{ $selectedDominion->name }}
                                        </strong>
                                    @else
                                        {{ $lastInfoOp->sourceDominion->name }}
                                    @endif
                                    <br>
                                    <span class="small">
                                        {{ $lastInfoOp->created_at }}
                                    </span>
                                </td>
                                <td class="text-center" data-search="" data-order="{{ $infoOpService->getNumberOfActiveInfoOps($targetDominionOps) }}">
                                    {{ $infoOpService->getNumberOfActiveInfoOps($targetDominionOps) }}/{{ $infoOpService->getMaxInfoOps() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>Whenever you or someone else in your realm performs an information gathering espionage operation or magic spell, the information gathered is posted in the Op Center.</p>
                <p>Through this page, you can help one another find targets and scout threats to one another.</p>
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
