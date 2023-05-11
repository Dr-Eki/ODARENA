@extends('layouts.master')

@section('title', "Round {$round->number} Registration")

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Round #{{ $round->number }} &mdash; <strong>{{ $round->name }}</strong></h3>
            <span class="pull-right">
                <a href="{{ route('round.quickstart', $round) }}" class="btn btn-warning"><i class="fas fa-fast-forward fa-fw"></i> Quickstart</a>
            </span>
        </div>
        <form action="{{ route('round.register', $round) }}" method="post" class="form-horizontal" role="form">
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

                <!-- Dominion Name -->
                <div class="form-group">
                    <label for="dominion_name" class="col-sm-3 control-label">Dominion Name</label>
                    <div class="col-sm-6">
                        <input type="text" name="dominion_name" id="dominion_name" class="form-control" placeholder="Dominion Name" value="{{ old('dominion_name') }}" maxlength="50" required autofocus>
                        <p class="help-block">Your dominion name is shown when viewing and interacting with other players.</p>
                    </div>
                </div>

                <!-- Title -->
                <div class="form-group">
                    <label for="title" class="col-sm-3 control-label">Ruler Title</label>
                    <div class="col-sm-6">
                        <select name="title" id="title" class="form-control select2" data-placeholder="Select a title" required>
                          <option></option>
                            @foreach ($titles as $title)
                            <option value="{{ $title->id }}" data-current="{{ $countTitles[$title->key] ?? 0 }}">
                                {{ $title->name }}
                                (@foreach ($title->perks as $perk)
                                    @php
                                        $perkDescription = $titleHelper->getPerkDescriptionHtmlWithValue($perk);
                                    @endphp

                                    {!! $perkDescription['description'] !!} {!! $perkDescription['value']  !!}
                                @endforeach)
                            </option>
                        @endforeach
                      </select>
                        <p class="help-block">This is the title you will go by. Select one that complements your intended strategy.</p>
                    </div>
                </div>

                <!-- Ruler Name -->
                <div class="form-group">
                    <label for="ruler_name" class="col-sm-3 control-label">Ruler Name</label>
                    <div class="col-sm-6">
                        <input type="text" name="ruler_name" id="ruler_name" class="form-control" placeholder="{{ Auth::user()->display_name }}" value="{{ old('ruler_name') }}" disabled>
                    </div>
                </div>

                <!-- Faction -->
                @if(in_array($round->mode, ['factions','factions-duration']))
                    <div class="form-group">
                        <label for="faction" class="col-sm-3 control-label">Faction</label>
                        <div class="col-sm-6">
                            <select name="race" id="faction" class="form-control select2" data-placeholder="Select a faction" required>
                                @foreach ($races->filter(function ($race) { return $race->playable === 1; }) as $race)
                                    <option value="{{ $race->id }}"
                                        data-current="{{ isset($countRaces[$race->name]) ? number_format($countRaces[$race->name]) : 0 }}"
                                        data-maxPerRound="{{ $race->max_per_round }}"
                                        data-experimental="{{ $race->experimental }}"
                                        data-minRoundsPlayed="{{ $race->minimum_rounds ? number_format($race->minimum_rounds) : 0 }}">
                                        {{ $race->name }}
                                    </option>
                                @endforeach
                                    <option value="random_any"
                                        data-current="{{ count($countRaces) }}"
                                        data-maxPerRound="{{ null }}"
                                        data-experimental="{{ null }}"
                                        data-minRoundsPlayed="0">
                                        Random (any)
                                    </option>
                            </select>
                        </div>
                    </div>

                @else
                    <div class="form-group">
                        <label for="faction" class="col-sm-3 control-label">Faction</label>
                        <div class="col-sm-6">
                            <select name="race" id="faction" class="form-control select2" data-placeholder="Select a faction" required>
                                <option></option>
                                <option value="random_any"
                                        data-current="{{ array_sum($countAlignment) }}">
                                    Random
                                </option>

                                <optgroup label="The Commonwealth">
                                    <option value="random_good"
                                            data-current="{{ isset($countAlignment['good']) ? number_format($countAlignment['good']) : 0 }}"
                                            data-alignment="Commonwealth">
                                        Random Commonwealth
                                    </option>
                                    @foreach ($races->filter(function ($race) { return $race->alignment === 'good'; }) as $race)
                                        <option value="{{ $race->id }}"
                                            data-current="{{ isset($countRaces[$race->name]) ? number_format($countRaces[$race->name]) : 0 }}"
                                            data-maxPerRound="{{ $race->max_per_round }}"
                                            data-experimental="{{ $race->experimental }}"
                                            data-minRoundsPlayed="{{ $race->minimum_rounds ? number_format($race->minimum_rounds) : 0 }}">
                                            {{ $race->name }}
                                        </option>
                                    @endforeach
                                </optgroup>

                                <optgroup label="The Empire">
                                    <option value="random_evil"
                                            data-current="{{ isset($countAlignment['evil']) ? number_format($countAlignment['evil']) : 0 }}"
                                            data-alignment="Imperial">
                                        Random Imperial
                                    </option>
                                    @foreach ($races->filter(function ($race) { return $race->alignment === 'evil'; }) as $race)
                                        <option value="{{ $race->id }}"
                                            data-current="{{ isset($countRaces[$race->name]) ? number_format($countRaces[$race->name]) : 0 }}"
                                            data-maxPerRound="{{ $race->max_per_round }}"
                                            data-experimental="{{ $race->experimental }}"
                                            data-minRoundsPlayed="{{ $race->minimum_rounds ? number_format($race->minimum_rounds) : 0 }}">
                                            {{ $race->name }}
                                        </option>
                                    @endforeach
                                </optgroup>

                                <optgroup label="The Independent">
                                    <option value="random_independent"
                                            data-current="{{ isset($countAlignment['independent']) ? number_format($countAlignment['independent']) : 0 }}"
                                            data-alignment="Independent">
                                        Random Independent
                                    </option>
                                    @foreach ($races->filter(function ($race) { return $race->alignment === 'independent'; }) as $race)
                                        <option value="{{ $race->id }}"
                                            data-current="{{ isset($countRaces[$race->name]) ? number_format($countRaces[$race->name]) : 0 }}"
                                            data-maxPerRound="{{ $race->max_per_round }}"
                                            data-experimental="{{ $race->experimental }}"
                                            data-minRoundsPlayed="{{ $race->minimum_rounds ? number_format($race->minimum_rounds) : 0 }}">
                                            {{ $race->name }}
                                        </option>
                                    @endforeach
                                </optgroup>

                        </select>
                            <small class="help-block">Consult <a href="{{ route('scribes.factions') }}" target="_blank">The Scribes</a> for details about each faction and ruler titles.</small>
                            <small class="help-block">Factions labelled <span class="label label-danger">Experimental</span> have significant changes that have been deemed at risk of being overpowered. If you play such a faction, you understand that if the community agrees (or if admin decides) that it is overpowered or unfit, the dominion will be locked. Not for violating any rules; but to keep the round fun and exciting. &mdash; When permitted, we recommend playing at least one additional dominion, just in case.</small>
                        </div>
                    </div>
                @endif

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
