@extends ('layouts.master')

@section('page-header', 'Espionage')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-user-secret"></i> Offensive Operations</h3>
                </div>

                @if ($protectionService->isUnderProtection($selectedDominion))
                    <div class="box-body">
                        You are currently under protection for <b>{{ $selectedDominion->protection_ticks }}</b> {{ str_plural('tick', $selectedDominion->protection_ticks) }} and may not perform any espionage operations during that time.
                    </div>
                @else
                    <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                        @csrf

                        <div class="box-body">

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="target_dominion">Select a target</label>
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
                                    <label>Information Operations</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <p>Moved to <a href="{{ route('dominion.intelligence') }}"><i class="fa fa-eye fa-fw"></i> <span>Intelligence</span></a></li>.</p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <label>Resource Theft</label>
                                </div>
                            </div>


                            @foreach ($theftOps->chunk(4) as $operations)
                                <div class="row">
                                    @foreach ($operations as $operation)
                                        @if($espionageCalculator->isSpyopAvailableToDominion($selectedDominion, $operation))
                                            <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
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

                            <div class="row">
                                <div class="col-md-12">
                                    <label>Hostile Operations</label>
                                </div>
                            </div>

                            @foreach ($hostileOps->chunk(4) as $operations)
                                <div class="row">
                                    @foreach ($operations as $operation)
                                        @if($espionageCalculator->isSpyopAvailableToDominion($selectedDominion, $operation))
                                                <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
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
                @endif

            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Here you can perform espionage operations on hostile dominions to win important information for you and your realmies.</p>
                    <p>Any obtained data after successfully performing an information gathering operation gets posted to the <a href="{{ route('dominion.op-center') }}">Op Center</a> for your realmies.</p>
                    <p>Performing espionage operations spends some spy strength, but it regenerates a bit every tick.</p>
                    <p>You have {{ floor($selectedDominion->spy_strength) }}% spy strength.</p>
                    <ul>
                      <li>Spy Strength: {{ floor($selectedDominion->spy_strength) }}%</li>
                      <li>Spy Ratio (offense): {{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'offense'), 3) }}</li>
                    </ul>

                    <a href="{{ route('scribes.espionage') }}"><span><i class="ra ra-scroll-unfurled"></i> Read more about Espionage in the Scribes.</span></a>
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
                    $('.war-op').removeClass('disabled');
                } else {
                    $('.war-op').addClass('disabled');
                }
            });
            @if (session('target_dominion'))
                $('#target_dominion').val('{{ session('target_dominion') }}').trigger('change.select2');
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
