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
                        <col width="50">
                        <col width="50">
                        <col width="150">
                        <col>
                        <col width="100">
                        <col width="100">
                        <col width="150">
                        <col width="150">
                        <col width="150">
                        <col width="150">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-center">&nbsp;</th>
                            <th class="text-center">ID</th>
                            <th>Author</th>
                            <th>Name</th>
                            <th>OP</th>
                            <th>DP</th>
                            <th>Faction</th>
                            <th>Title</th>
                            <th>Deity</th>
                            <th>Remaing Ticks</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($quickstarts->sortByDesc('id')->filter(function ($quickstart) use ($user) {
                            return $quickstart->is_public == 1 or $quickstart->user_id == $user->id;
                        }) as $quickstart)
                            <tr>
                                <td class="text-center"><input type="radio" id="quickstart{{ $quickstart->id}}" name="quickstart" value="{{ $quickstart->id }}" required></td>
                                <td><label for="quickstart{{ $quickstart->id}}">{{ $quickstart->id }}</label></td>
                                <td><label for="quickstart{{ $quickstart->id}}">{{ optional($quickstart->user)->display_name ?? '-' }}</label></td>
                                <td><a href="{{ route('scribes.quickstart', $quickstart->id) }}">{{ $quickstart->name }}</a></td>
                                <td>{{ number_format($quickstart->offensive_power) }}</td>
                                <td>{{ number_format($quickstart->defensive_power) }}</td>
                                <td>{{ $quickstart->race->name }}</td>
                                <td>{{ $quickstart->title->name }}</td>
                                <td>{{ optional($quickstart->deity)->name ?? 'None' }}</td>
                                <td>{{ $quickstart->protection_ticks }}</td>
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

                @if(in_array($round->mode, ['packs','packs-duration','artefacts-packs']))
                    <div class="form-group">
                        <label for="faction" class="col-sm-3 control-label">Join Pack</label>
                        <div class="col-sm-4">
                            @if($packs->count())
                                <select name="pack" id="pack" class="form-control select2" data-placeholder="Select a pack" required>
                                    @foreach ($packs as $pack)
                                        @php
                                            $isSelected = ($pack->id == old('pack') or $pack->user->id ==  Auth::user()->id) ? 'selected' : '';
                                        @endphp

                                        <option value="{{ $pack->id }}" {{ $isSelected }} {{ $pack->status == 2 ? 'disabled' : null }}>
                                            {{ $pack->user->display_name }}{{ $pack->user->display_name[strlen($pack->user->display_name) - 1] == 's' ? "'" : "'s" }} Pack
                                            ({{ number_format($pack->dominions->count()) }} {{ Str::plural('member', $pack->dominions->count()) }})
                                            {{ $pack->status == 1 ? '(Public - No Password Needed)' : null }}
                                            {{ $pack->status == 2 ? '(Closed)' : null }}
                                        </option>
                                    @endforeach

                                    @if($packs->where('status', 1)->count())
                                        <option value="random_public">Random public pack</option>
                                    @else
                                        <option value="random_public" disabled>No public packs available</option>
                                    @endif

                                </select>
                            @else
                                <select name="pack" id="pack" class="form-control select2" data-placeholder="No packs available" disabled>
                                    <option selected>No packs created yet</option>
                                </select>
                            @endif
                        </div>
                        <div class="col-sm-2">
                            <a href="{{ route('round.create-pack', $round) }}" class="btn btn-primary btn-block">Create New Pack</a>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="faction" class="col-sm-3 control-label">Password</label>
                        <div class="col-sm-6">
                            <input type="password" name="pack_password" class="form-control" placeholder="Enter pack password (required if pack is not Public)">
                        </div>
                    </div>

                @endif

                {{-- Terms and Conditions --}}
                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-6">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="terms" required> I have read, understood, and agree with the <a href="{{ route('legal.terms-and-conditions') }}">Terms and Conditions</a> and the <a href="{{ route('legal.privacy-policy') }}">Privacy Policy</a>
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
                        @elseif($round->mode == 'artefacts' or $round->mode == 'artefacts-packs')
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
                order: [1, 'desc'],
                paging: true,
                pageLength: 10
            });
        })(jQuery);
    </script>
@endpush
