@extends('layouts.master')

@section('title', "Create Pack")

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Create Pack</h3>
        </div>
        <form action="{{ route('round.create-pack', $round) }}" method="post" class="form-horizontal" role="form">
            @csrf

            <div class="box-body">

                <!-- Round Details -->
                <div class="row">
                    <div class="col-sm-3 text-right"><b>Round Mode</b></div>
                    <div class="col-sm-6">
                        <p>{{ $roundHelper->getRoundModeString($round) }}: {{ $roundHelper->getRoundModeDescription($round) }}</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-3 text-right"><b>Round Target</b></div>
                    <div class="col-sm-6">
                        <p>{{ number_format($round->goal) }} {{ $roundHelper->getRoundModeGoalString($round) }}</p>
                    </div>
                </div>

                <!-- Leader -->
                <div class="row">
                    <div class="col-sm-3 text-right"><b>Pack leader</b></div>
                    <div class="col-sm-6">
                        <p>{{ $user->display_name }}</p> 
                    </div>
                </div>

                <!-- Pack Status -->
                <div class="form-group">
                    <label for="dominion_name" class="col-sm-3 control-label">Pack Status</label>
                    <div class="col-sm-6">
                        <input type="radio" required id="private" name="status" value="0" checked>&nbsp;<label for="private">Private</label>: password required to join.<br>
                        <input type="radio" required id="public" name="status" value="1">&nbsp;<label for="public">Public</label>: no password required.<br>
                        <input type="radio" required id="closed" name="status" value="2">&nbsp;<label for="closed">Closed</label>: no new members accepted.<br>
                        <p class="help-block">Required if pack status is Private or Closed. Unlike the password you use to log in, this password is not encrypted.</p>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="dominion_name" class="col-sm-3 control-label">Password</label>
                    <div class="col-sm-6">
                        <input type="text" name="pack_password" id="pack_password" class="form-control" placeholder="Pack password" value="{{ old('pack_password') }}" maxlength="100" autofocus>
                        <p class="help-block">Required if pack status is Private or Closed. Unlike the password you use to log in, this password is not encrypted.</p>
                    </div>
                </div>

            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary">Create Pack</button>
            </div>

        </form>
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
            $('#title').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
            @if (session('title'))
                $('#title').val('{{ session('title') }}').trigger('change.select2').trigger('change');
            @endif
        })(jQuery);


        (function ($) {
            $('#faction').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
            @if (session('faction'))
                $('#faction').val('{{ session('faction') }}').trigger('change.select2').trigger('change');
            @endif
        })(jQuery);

        function select2Template(state)
        {
            if (!state.id)
            {
                return state.text;
            }

            const current = state.element.dataset.current;
            const experimental = state.element.dataset.experimental;
            const maxPerRound = state.element.dataset.maxperround;

            experimentalStatus = ''
            if (experimental == 1) {
                experimentalStatus = '&nbsp;<div class="pull-left">&nbsp;<span class="label label-danger">Experimental</span></div>';
            }

            maxPerRoundStatus = ''
            if (maxPerRound > 0) {
                maxPerRoundStatus = '&nbsp;<div class="pull-left">&nbsp;<span class="label label-warning">Max ' + maxPerRound + ' per round</span></div>';
            }

            var xId = state.id;

            if(xId.startsWith("random") && state.id !== 'random_any')
            {
                const alignment = state.element.dataset.alignment;
                return $(`
                    <div class="pull-left">${state.text}</div>
                    ${experimentalStatus}
                    ${maxPerRoundStatus}
                    <div class="pull-right">${current} total dominion(s) in the ${alignment} realm</div>
                    <div style="clear: both;"></div>
                `);
            }

            if(state.id == 'random_any')
            {
                const alignment = state.element.dataset.alignment;
                return $(`
                    <div class="pull-left">${state.text}</div>
                    ${experimentalStatus}
                    ${maxPerRoundStatus}
                    <div class="pull-right">${current} total dominion(s) registered</div>
                    <div style="clear: both;"></div>
                `);
            }

            return $(`
                <div class="pull-left">${state.text}</div>
                ${experimentalStatus}
                ${maxPerRoundStatus}
                <div class="pull-right">${current} dominion(s)</div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush
