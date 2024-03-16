@extends('layouts.master')
@section('title', 'Decrees')

@section('content')

{{-- //Columns must be a factor of 12 (1,2,3,4,6,12) --}}
@php
    $numOfCols = 2;
    $rowCount = 0;
    $bootstrapColWidth = 12 / $numOfCols;
@endphp

<div class="row">
    @foreach($decrees as $decree)
        @php
        $isIssued = $decreeHelper->isDominionDecreeIssued($selectedDominion, $decree);
        if($isIssued)
        {
            $dominionDecreeState = $decreeHelper->getDominionDecreeState($selectedDominion, $decree);
            $ticksUntilCanRevoke = $decreeCalculator->getTicksUntilDominionCanRevokeDecree($selectedDominion, $decree);
        }
        @endphp

        <div class="col-md-{{ $bootstrapColWidth }}">
            <div class="box {{ $isIssued ? 'box-success' : '' }}">
                <div class="box-header">
                    <div class="row">
                        <div class="col-lg-4">
                            <h4 class="box-title"><i class="fas fa-gavel fw-fw"></i> {{ ($decree->name) }}</h4>
                        </div>
                        @if($isIssued)
                            <div class="col-lg-4">
                                {{-- <span class="label label-success">Issued on tick {{ number_format($dominionDecreeState->tick) }}</span> --}}
                                @if($ticksUntilCanRevoke)
                                    <span class="label label-danger">Cooldown: {{ $ticksUntilCanRevoke . ' ' . Str::plural('tick', $ticksUntilCanRevoke) }}</span>
                                @endif
                            </div>
                            <div class="col-lg-4">
                                <form action="{{ route('dominion.decrees.revoke-decree') }}" method="post" role="form">
                                    @csrf
                                    <input type="hidden" name="dominionDecreeState" value="{{ $dominionDecreeState->id }}">
                                    <button type="submit" class="btn btn-small btn-block btn-danger" {{ !$decreeCalculator->canDominionRevokeDecree($selectedDominion, $decree) ? 'disabled' : ''}}>
                                        Revoke decree
                                    </button>
                                </form>   
                            </div>
                        @else
                            <div class="col-lg-4">
                                <span class="label text-muted" style="background: #ccc;">Not issued</span> 
                            </div>
                                
                            <div class="col-lg-4">
                                <span class="label label-info">Cooldown: {{ $decree->cooldown . ' ' . Str::plural('tick', $decree->cooldown) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="box-body">
                                <i class="far fa-file-alt fa-fw"></i> {{ $decree->description }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box">       
                    @foreach($decree->states as $decreeState)
                        <div class="row">
                            <div class="box-body">
                                <div class="col-sm-12 col-md-3">
                                    @if($isIssued and $dominionDecreeState->decree_state_id === $decreeState->id)
                                        <button type="button" class="btn btn-block btn-success" {{ $isIssued ? 'disabled' : ''}}>
                                            {{ $decreeState->name }}
                                        </button>
                                    @else
                                        <form action="{{ route('dominion.decrees.issue-decree') }}" method="post" role="form">
                                            @csrf
                                            <input type="hidden" name="decree" value="{{ $decree->id }}">
                                            <input type="hidden" name="decreeState" value="{{ $decreeState->id }}">
                                            <button type="submit" class="btn btn-block btn-primary" {{ $isIssued ? 'disabled' : ''}}>
                                                {{ $decreeState->name }}
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <div class="col-sm-12 col-md-6">
                                    <div class="box-body">
                                        {!! $decreeHelper->getDecreeStateDescription($decreeState) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @php
            $rowCount++;
        @endphp

        @if($rowCount % $numOfCols == 0)
            </div><div class="row">
        @endif
        
    @endforeach
</div>

<div class="row">
    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>As the ruler of your dominion, you can enact and revoke decrees.<p>
                <p>Once enacted or revoked, the effect is immediate. If revoked, it will be available to be enacted again.</p>
                <p>Some decrees have a cooldown period before they can be revoked.</p>
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

@push('inline-scripts')
     <script type="text/javascript">
         (function ($) {
             $('#renounce-deity select').change(function() {
                 var confirm = $(this).val();
                 if (confirm == "1") {
                     $('#renounce-deity button').prop('disabled', false);
                 } else {
                     $('#renounce-deity button').prop('disabled', true);
                 }
             });
         })(jQuery);
     </script>
 @endpush

    <script type="text/javascript">
        (function ($) {
            $('#monarch').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
            $('#realm_number').select2();
        })(jQuery);

        function select2Template(state) {
            if (!state.id) {
                return state.text;
            }

            const land = state.element.dataset.land;
            const percentage = state.element.dataset.percentage;
            const networth = state.element.dataset.networth;
            let difficultyClass;

            if (percentage >= 120) {
                difficultyClass = 'text-red';
            } else if (percentage >= 75) {
                difficultyClass = 'text-green';
            } else if (percentage >= 60) {
                difficultyClass = 'text-muted';
            } else {
                difficultyClass = 'text-gray';
            }

            return $(`
                <div class="pull-left">${state.text}</div>
                <div class="pull-right">${land} acres <span class="${difficultyClass}">(${percentage}%)</span> - ${networth} networth</div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush
