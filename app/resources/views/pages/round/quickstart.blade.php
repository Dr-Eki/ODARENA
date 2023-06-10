@extends('layouts.master')
@section('title', "Round {$round->number} Quickstart")

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Round #{{ $round->number }} &mdash; <strong>{{ $round->name }}</strong> (Quickstart)</h3>
            <span class="pull-right">
                <a href="{{ route('round.register', $round) }}" class="btn btn-warning">Normal Registration</a>
            </span>
        </div>
        <form action="{{ route('round.quickstart', $round) }}" method="post" class="form-horizontal" role="form">
            @csrf

            <div class="box box-body">
                <table class="table table-striped table-hover" id="quickstarts-table">
                    <colgroup>
                        <col width="60px">
                        <col width="">
                        <col width="120px">
                        <col width="120px">
                        <col width="120px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Name</th>
                            <th>Faction</th>
                            <th>Land</th>
                            <th>Ticks</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($quickstarts as $quickstart)
                            <tr>
                                <td class="text-center"><input type="radio" id="quickstart{{ $quickstart->id}}" name="quickstart" value="{{ $quickstart->id }}" required></td>
                                <td class="text-left"><label style="font-weight: normal; display: block;" for="quickstart{{ $quickstart->id}}"><a href="{{ route('scribes.quickstart', $quickstart->id) }}" target="_blank">{{ $quickstart->name }}</a></label></td>
                                <td>{{ $quickstart->race->name }}</td>
                                <td>{{ number_format($quickstart->land) }}</td>
                                <td>{{ number_format($quickstart->protection_ticks) }}</td>
                            </tr>
                    @endforeach
                    </tbody>
                </table>

                <!-- Dominion Name -->
                <div class="form-group">
                    <label for="dominion_name" class="col-sm-3 control-label">Dominion Name</label>
                    <div class="col-sm-6">
                        <input type="text" name="dominion_name" id="dominion_name" class="form-control" placeholder="Dominion Name" value="{{ old('dominion_name') }}" maxlength="50" required autofocus>
                        <p class="help-block">Your dominion name is shown when viewing and interacting with other players.</p>
                    </div>
                </div>


                <!-- Ruler Name -->
                <div class="form-group">
                    <label for="ruler_name" class="col-sm-3 control-label">Ruler Name</label>
                    <div class="col-sm-6">
                        <input type="text" name="ruler_name" id="ruler_name" class="form-control" placeholder="{{ Auth::user()->display_name }}" value="{{ old('ruler_name') }}" disabled>
                    </div>
                </div>

                {{-- Terms and Conditions --}}
                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-6">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="terms" required> I have read, understood, and agree with the <a href="{{ route('legal.termsandconditions') }}">Terms and Conditions</a> and the <a href="{{ route('legal.privacypolicy') }}">Privacy Policy</a>
                            </label>
                        </div>
                        @if($round->mode == 'deathmatch' or $round->mode == 'deathmatch-duration')
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="no_multis" required> <span class="label label-danger">Special rule:</span> This is deathmatch round and clause 3.2 of the Terms and Conditions does not apply. No multis are allowed this round.
                                </label>
                            </div>
                        @elseif($round->mode == 'packs' or $round->mode == 'packs-duration')
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="one_pack" required> <span class="label label-danger">Special rule:</span> This is packs round and clause 3.2 of the Terms and Conditions is slightly modified: you can play multis must they all be in the same pack.
                                </label>
                            </div>
                        @elseif($round->mode == 'artefacts')
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="limited_multis" required> <span class="label label-danger">Special rule:</span> This is an Artefacts round and clause 3.2 of the Terms and Conditions is limited: you are only allowed one multi (two total dominions) this round.
                            </label>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Notice --}}
                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-6">
                        @include('partials.register-notice')
                    </div>
                </div>

            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>

        </form>
    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
    <style>
        #quickstarts-table_filter { display: none !important; }
    </style>
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var table = $('#quickstarts-table').DataTable({
                order: [1, 'asc'],
                paging: true,
                pageLength: 10
            });
        })(jQuery);
    </script>
@endpush
