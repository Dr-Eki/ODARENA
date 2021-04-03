@extends('layouts.master')

{{--
@section('page-header', 'Government')
--}}

@section('content')

@if ($selectedDominion->isMonarch())
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-star"></i> Governor's Duties</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">
                        <form action="{{ route('dominion.government.realm') }}" method="post" role="form">
                            @csrf
                            <label for="realm_name">Realm Message</label>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="realm_motd" id="realm_motd" placeholder="{{ $selectedDominion->realm->motd }}" maxlength="256" autocomplete="off" />
                                    </div>
                                </div>
                            </div>
                            <label for="realm_name">Realm Name</label>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="realm_name" id="realm_name" placeholder="{{ $selectedDominion->realm->name }}" maxlength="64" autocomplete="off" />
                                    </div>
                                </div>
                            </div>
                            <label for="realm_name">Discord link</label> <small class="text-muted">(format: https://discord.gg/xxxxxxx)</small>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="discord_link" id="discord_link" placeholder="{{ $selectedDominion->realm->discord_link }}" maxlength="64" autocomplete="off" />
                                    </div>
                                </div>
                            </div>
                            @if($realmCalculator->hasMonster($selectedDominion->realm))
                                <label for="realm_name">Percentage Contribution to the Monster</label>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <input type="number" class="form-control" name="realm_contribution" id="realm_contribution" placeholder="{{ $selectedDominion->realm->contribution }}" min=0 max=10 step=1 autocomplete="off" />
                                        </div>
                                    </div>
                                    <div class="col-xs-offset-6 col-xs-6 col-sm-offset-8 col-sm-4 col-lg-offset-10 col-lg-2">
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                Change
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @else

                                    <div class="col-xs-offset-6 col-xs-6 col-sm-offset-8 col-sm-4 col-lg-offset-10 col-lg-2">
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                Change
                                            </button>
                                        </div>
                                    </div>

                            @endif
                        </form>
                    </div>
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
                    <p><i class="fa fa-star fa-lg text-orange" title="Governor of The Realm"></i> <strong>Welcome, Governor!</strong></p>
                    <p>As the Governor, you have the power to declare war, revoke declarations of war against other realms, and moderate the
                      @if($selectedDominion->realm->alignment == 'evil')
                        Senate.
                      @elseif($selectedDominion->realm->alignment == 'good')
                        Parliament.
                      @elseif($selectedDominion->realm->alignment == 'independent')
                        Assembly.
                      @else
                        Council.
                      @endif

                      @if($realmCalculator->hasMonster($selectedDominion->realm))
                      <p>There is monster in your realm. You are tasked with deciding what percentage of everyone's food, lumber, and ore stockpiles shall be given to the monster each tick.</p>
                      @endif
            </div>
        </div>
    </div>
</div>
@endif

@if(!$selectedDominion->race->getPerkValue('cannot_vote'))
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-ticket"></i> Vote for Governor</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">
                        <form action="{{ route('dominion.government.monarch') }}" method="post" role="form">
                            @csrf
                            <label for="monarch">Select your candidate</label>
                            <div class="row">
                                <div class="col-sm-8 col-lg-10">
                                    <div class="form-group">
                                        <select name="monarch" id="monarch" class="form-control select2" required style="width: 100%" data-placeholder="Select a dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            <option></option>
                                            @foreach ($dominions as $dominion)
                                                @if(!$dominion->race->getPerkValue('cannot_vote'))
                                                    <option value="{{ $dominion->id }}"
                                                            data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}"
                                                            data-networth="{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}"
                                                            data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}">
                                                        {{ $dominion->name }} (#{{ $dominion->realm->number }})
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xs-offset-6 col-xs-6 col-sm-offset-0 col-sm-4 col-lg-2">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            Vote
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <table class="table table-condensed">
                                    <tr><th>Dominion</th><th>Voted for</th></tr>
                                    @foreach ($dominions as $dominion)
                                        @if(!$dominion->race->getPerkValue('cannot_vote'))
                                            <tr>
                                                <td>
                                                    @if ($dominion->isMonarch())
                                                        <span class="text-red">{{ $dominion->name }}</span>
                                                    @else
                                                        {{ $dominion->name }}
                                                    @endif
                                                </td>
                                                @if ($dominion->monarchVote)
                                                    <td>{{ $dominion->monarchVote->name }}</td>
                                                @else
                                                    <td>N/A</td>
                                                @endif
                                            </tr>
                                        @endif
                                    @endforeach
                                </table>
                            </div>
                        </form>
                    </div>
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
                <p>Here you can vote for the governor of your realm. You can change your vote at any time.</p>
                <p>The governor has the power to declare war and peace as well as moderate the council.</p>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-university"></i> Warriors League</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    @if ($selectedDominion->race->getPerkValue('cannot_join_guards'))
                        <div class="col-sm-12 text-center">
                            <p class="text-red">{{ $selectedDominion->race->name }} cannot join any of the Leagues.</p>
                        </div>
                    @else
                    <div class="col-sm-6 text-center">
                        <h4 class="text-yellow">
                            <i class="ra ra-heavy-shield" title="Warriors League"></i>
                            The Warriors League
                        </h4>
                        <ul class="text-left" style="padding: 0 50px;">
                            <li>Cannot interact with Dominions less than 75% or greater than 133% of your land size.</li>
                            <li>+5% offensive power against other members of the Warriors League.</li>
                            <li>Cannot explore.</li>
                        </ul>
                        @if ($isEliteGuardApplicant || $isEliteGuardMember)
                            <form action="{{ route('dominion.government.elite-guard.leave') }}" method="post" role="form">
                                @csrf
                                <button type="submit" name="land" class="btn btn-danger btn-sm-lg" {{ $selectedDominion->isLocked() || $hoursBeforeLeaveEliteGuard ? 'disabled' : null }}>
                                    @if ($isEliteGuardMember)
                                        Leave Warriors League
                                    @else
                                        Cancel Application
                                    @endif
                                </button>
                            </form>
                        @else
                            <form action="{{ route('dominion.government.elite-guard.join') }}" method="post" role="form">
                                @csrf
                                <button type="submit" name="land" class="btn btn-primary btn-sm-lg" {{ $selectedDominion->isLocked() || !$canJoinGuards ? 'disabled' : null }}>
                                    Apply to join the Warriors League
                                </button>
                            </form>
                        @endif
                    </div>
                    @endif
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
                <p>REWRITE</p>

                @if ($isEliteGuardMember)
                    <p>You are a member of the <span class="text-yellow"><i class="ra ra-heavy-shield" title="Warriors League"></i>Warriors League</span>.</p>

                    @if ($hoursBeforeLeaveEliteGuard)
                        <p>You cannot leave for {{ $hoursBeforeLeaveEliteGuard }} hours.</p>
                    @endif
                @else
                    <p>You are <span class="text-red">not</span> a member Warriors League. You can interact with dominions less than 40% or greater than 250% of your land size, unless they are members of Warriors League in which case the range is 75-133%.</p>
                @endif

                @if ($isEliteGuardApplicant)
                    <p>You will become a member of the Warriors League in {{ $hoursBeforeEliteGuardMember }} hours.</p>
                @endif
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
            } else if (percentage >= 66) {
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
