@extends('layouts.master')
@section('title', 'Government')

{{--
@section('page-header', 'Government')
--}}

@section('content')

@if($selectedDominion->hasProtector())
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-user-shield"></i> Protectorship</h3>
            </div>
            <div class="box-body">
                <p>You are under the guaranteed protection of <a href="{{ route('dominion.insight.show', $selectedDominion->protector) }}">{{ $selectedDominion->protector->name }} (# {{ $selectedDominion->protector->realm->number }})</a>, providing you with {{ number_format($militaryCalculator->getDefensivePower($selectedDominion->protector)) }} DP.</p>
            </div>
        </div>
    </div>
</div>
@endif

@if($selectedDominion->isProtector())
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-user-shield"></i> Protectorship</h3>
            </div>
            <div class="box-body">
                <p>You are the protector of <a href="{{ route('dominion.insight.show', $selectedDominion->protectedDominion) }}">{{ $selectedDominion->protectedDominion->name }} (# {{ $selectedDominion->protectedDominion->realm->number }})</a>, providing them with {{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }} DP.</p>
            </div>
        </div>
    </div>
</div>
@endif

@if($selectedDominion->protectorshipOffers->count() > 0)
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-bomb"></i> Artillery</h3>
            </div>
            <div class="box-body table-responsive">
                <p>You have received the following protection offers:</p>
                <table class="table table-striped table-hover">
                    <colgroup>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <tr>
                        <th>Ruler</th>
                        <th>Dominion</th>
                        <th>Respond</th>
                    </tr>
                    @foreach($selectedDominion->protectorshipOffers as $protectorshipOffer)
                        <tr>
                            <td><em>{{ $protectorshipOffer->protector->title->name }}</em> {{ $protectorshipOffer->protector->ruler_name }}</td>
                            <td>{{ $protectorshipOffer->protector->name }}</td>
                            <td>
                                <div class="btn-toolbar">
                                    <form action="{{ route('dominion.government.answer-protectorship-offer') }}" method="post" role="form">
                                        @csrf
                                        <input type="hidden" name="protectorship_offer_id" value="{{ $protectorshipOffer->id }}">
                                        <input type="hidden" name="answer" value="accept">
                                        <button type="submit" class="btn btn-success" {{ !$governmentCalculator->canBeProtected($selectedDominion) ? 'disabled' : ''}}>
                                            Accept Offer
                                        </button>
                                    </form>
                                    <form action="{{ route('dominion.government.answer-protectorship-offer') }}" method="post" role="form">
                                        @csrf
                                        <input type="hidden" name="protectorship_offer_id" value="{{ $protectorshipOffer->id }}">
                                        <input type="hidden" name="answer" value="decline">
                                        <button type="submit" class="btn btn-danger" {{ !$governmentCalculator->canBeProtected($selectedDominion) ? 'disabled' : ''}}>
                                            Decline Offer
                                        </button>
                                    </form> 
                                </div> 
                            </td>
                        </tr>
                    @endforeach
                </table>
                <div class="box-footer">
                    <p>Accepting an offer will form a permanent protectorship bond between you and the protector. When you accept an offer, all other offers will be cancelled and no further offers can be made.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@elseif(($unprotectedArtilleries = $governmentCalculator->getUnprotectedArtilleryDominions($selectedDominion->realm))->isNotEmpty())
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-bomb"></i> Artillery</h3>
            </div>
            <div class="box-body">
                <p>The following Artillery dominions do not yet have a protector:</p>
                <div class="form-group">
                    <table class="table table-condensed">
                            <tr>
                                <th>Ruler</th>
                                <th>Dominion</th>
                                <th>Protectorship</th>
                        @foreach($unprotectedArtilleries as $unprotectedArtillery)
                            @php
                                $protectorshipOffer = null;
                                if($selectedDominion->protectorshipOffered->contains('protected_id', $unprotectedArtillery->id))
                                {
                                    $protectorshipOffer = OpenDominion\Models\ProtectorshipOffer::where('protected_id', $unprotectedArtillery->id)->where('protector_id', $selectedDominion->id)->first();
                                }
                            @endphp
                            <tr>
                                <td><em>{{ $unprotectedArtillery->title->name }}</em> {{ $unprotectedArtillery->ruler_name }}</td>
                                <td>{{ $unprotectedArtillery->name }}</td>
                                <td>
                                    @if($protectorshipOffer)
                                        <form action="{{ route('dominion.government.rescind-protectorship-offer') }}" method="post" role="form">
                                            @csrf
                                            <input type="hidden" name="protectorship_offer_id" value="{{ $protectorshipOffer->id }}">
                                            <button type="submit" class="btn btn-warning" {{ !$governmentCalculator->canRescindProtectorshipOffer($selectedDominion, $protectorshipOffer) ? 'disabled' : ''}}>
                                                Rescind Protectorship Offer
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('dominion.government.offer-protectorship') }}" method="post" role="form">
                                            @csrf
                                            <input type="hidden" name="unprotected_dominion" value="{{ $unprotectedArtillery->id }}">
                                            <button type="submit" class="btn btn-primary" {{ !$governmentCalculator->canOfferProtectorship($selectedDominion) ? 'disabled' : ''}}>
                                                Offer Protectorship
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            <div class="box-footer">
                @if($selectedDominion->isProtector())
                    <p><span class="label label-warning">Protector</span> You are already protecting a dominion and cannot protect another one.</p>
                @elseif(!$governmentCalculator->canOfferProtectorship($selectedDominion) and !$selectedDominion->protectorshipOffered->count())
                    <p><span class="label label-warning">Cannot Protect</span> {{ $selectedDominion->race->name }} dominions cannot be protectors.</p>
                @elseif($governmentCalculator->canOfferProtectorship($selectedDominion) and $selectedDominion->protectorshipOffered->count() > 0)
                    <p><span class="label label-warning">Pending Offer</span> You have already submitted a protectorship offer.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

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

                            <div class="col-xs-offset-6 col-xs-6 col-sm-offset-8 col-sm-4 col-lg-offset-10 col-lg-2">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        Change
                                    </button>
                                </div>
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
            </div>
        </div>
    </div>
</div>
@endif

@if(in_array($selectedDominion->round->mode, ['factions', 'factions-duration']) and $selectedDominion->isMonarch())
<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-handshake"></i> Alliance</h3>
            </div>
            <div class="box-body">
                {{-- //Columns must be a factor of 12 (1,2,3,4,6,12) --}}
                @php
                    $numOfCols = 3;
                    $rowCount = 0;
                    $bootstrapColWidth = 12 / $numOfCols;
                @endphp

                <div class="row">
                    <form action="{{ route('dominion.government.offer-alliance') }}" method="post" role="form">
                        @csrf

                        @foreach($allianceableRealms as $realm)
                            <div class="col-md-{{ $bootstrapColWidth }}">
                                <label class="btn btn-block">
                                    <div class="box">
                                        <div class="box-header with-border">
                                            @if($selectedDominion->realm->isAlly($realm))
                                                <span class="label label-success">Ally</span> <h4 class="box-title">{{ $realm->name }} (# {{ $realm->number }})</h4>
                                            @else
                                                <input type="radio" id="realm" name="realm" value="{{ $realm->id }}" {{ $allianceCalculator->canFormAllianceWithRealm($selectedDominion->realm, $realm, $selectedDominion) ? 'required' : 'disabled' }}>&nbsp;<h4 class="box-title">{{ $realm->name }} (# {{ $realm->number }})</h4>
                                            @endif
                                        </div>
                                        <div class="box-body">
                                            <p><strong>Governor:</strong> {!! $realm->hasMonarch() ? $realm->monarch->name : '<em class="text-muted">None, cannot form alliance</em>' !!}</p>
                                            <p><strong>Faction:</strong> {{ ucfirst($realm->alignment) }}</p>
                                            <p><strong>Dominions:</strong> {{ $realm->dominions->count() }}</p>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            @php
                                $rowCount++;
                            @endphp

                            @if($rowCount % $numOfCols == 0)
                                </div><div class="row">
                            @endif

                        @endforeach

                        <div class="col-sm-offset-9 col-lg-3">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    Offer Alliance
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                @if($allianceCalculator->getPendingReceivedAllianceOffers($selectedDominion->realm)->count())
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box-header with-border">
                                <h3 class="box-title">Received Alliance Offers</h3>
                            </div>
                            <div class="box-body">
                                <div class="form-group">
                                    <table class="table table-condensed">
                                        <thead>
                                            <th>Realm</th>
                                            <th>Faction</th>
                                            <th>Governor</th>
                                            <th>Action</th>
                                        </thead>
                            
                                        <tbody>
                                            @foreach($allianceCalculator->getPendingReceivedAllianceOffers($selectedDominion->realm) as $allianceOfferReceived)
                                                <tr>
                                                    <td>{{ $allianceOfferReceived->inviter->name }} (# {{ $allianceOfferReceived->inviter->number }})</td>
                                                    <td>{{ ucfirst($allianceOfferReceived->inviter->alignment) }}</td>
                                                    <td>{{ $allianceOfferReceived->inviter->monarch->name }}</td>
                                                    <td>
                                                        <div class="btn-toolbar">
                                                            <form action="{{ route('dominion.government.answer-alliance-offer') }}" method="post" role="form">
                                                                @csrf
                                                                <input type="hidden" name="alliance_offer_id" value="{{ $allianceOfferReceived->id }}">
                                                                <input type="hidden" name="answer" value="accept">
                                                                <button type="submit" class="btn btn-success" {{ !$allianceCalculator->canFormAllianceWithRealm($allianceOfferReceived->inviter, $allianceOfferReceived->invited, $selectedDominion) ? 'disabled' : ''}}>
                                                                    Accept
                                                                </button>
                                                            </form>
                                                            <form action="{{ route('dominion.government.answer-alliance-offer') }}" method="post" role="form">
                                                                @csrf
                                                                <input type="hidden" name="alliance_offer_id" value="{{ $allianceOfferReceived->id }}">
                                                                <input type="hidden" name="answer" value="decline">
                                                                <button type="submit" class="btn btn-danger">
                                                                    Decline
                                                                </button>
                                                            </form> 
                                                        </div> 
                                                    </td>
                                                </tr>
                                        @endforeach
                                        <tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($allianceCalculator->getPendingSentAllianceOffers($selectedDominion->realm)->count())
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box-header with-border">
                                <h3 class="box-title">Sent Alliance Offers</h3>
                            </div>
                            <div class="box-body">
                                <div class="form-group">
                                    <table class="table table-condensed">
                                        <thead>
                                            <th>Realm</th>
                                            <th>Faction</th>
                                            <th>Governor</th>
                                            <th>Action</th>
                                        </thead>
                                
                                        <tbody>
                                            @foreach($allianceCalculator->getPendingSentAllianceOffers($selectedDominion->realm) as $allianceOfferReceived)
                                                <tr>
                                                    <td>{{ $allianceOfferReceived->invited->name }} (# {{ $allianceOfferReceived->invited->number }})</td>
                                                    <td>{{ ucfirst($allianceOfferReceived->invited->alignment) }}</td>
                                                    <td>{{ $allianceOfferReceived->invited->monarch->name }}</td>
                                                    <td>
                                                        <div class="btn-toolbar">
                                                            <form action="{{ route('dominion.government.rescind-alliance-offer') }}" method="post" role="form">
                                                                @csrf
                                                                <input type="hidden" name="alliance_offer_id" value="{{ $allianceOfferReceived->id }}">
                                                                <button type="submit" class="btn btn-danger">
                                                                    Rescind
                                                                </button>
                                                            </form> 
                                                        </div> 
                                                    </td>
                                                </tr>
                                            @endforeach
                                        <tbody>

                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($selectedDominion->realm->getAllies()->count())
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box-header with-border">
                                <h3 class="box-title">Active Alliances</h3>
                            </div>
                            <div class="box-body">
                                <div class="form-group">
                                    <table class="table table-condensed">
                                        <thead>
                                            <th>Realm</th>
                                            <th>Faction</th>
                                            <th>Governor</th>
                                            <th>Duration</th>
                                            <th>Break Alliance</th>
                                        </thead>
                                
                                        <tbody>
                                            @foreach($selectedDominion->realm->getAllies() as $alliedRealm)
                                                @php
                                                    $realmAlliance = OpenDominion\Models\RealmAlliance::where(['realm_id' => $selectedDominion->realm->id, 'allied_realm_id' => $alliedRealm->id])->orWhere(['realm_id' => $alliedRealm->id, 'allied_realm_id' => $selectedDominion->realm->id])->first();
                                                @endphp

                                                <tr>
                                                    <td>{{ $alliedRealm->name }} (# {{ $alliedRealm->number }})</td>
                                                    <td>{{ ucfirst($alliedRealm->alignment) }}</td>
                                                    <td>{{ $alliedRealm->monarch->name }}</td>
                                                    <td>{{ number_format($selectedDominion->round->ticks - $realmAlliance->established_tick) . ' ' . str_plural('tick', ($selectedDominion->round->ticks - $realmAlliance->established_tick))}}</td>
                                                    <td>
                                                        <div class="form-group">
                                                            <form action="{{ route('dominion.government.break-alliance') }}" method="post" role="form">
                                                                @csrf
                                                                <input type="hidden" name="realm_alliance_id" value="{{ $realmAlliance->id }}">
                                                                <label>
                                                                    <input type="checkbox" name="confirm"  {{ !$allianceCalculator->canBreakAlliance($realmAlliance, $selectedDominion) ? 'disabled' : ''}} required> Confirm breaking alliance<br>
                                                                </label><br>
                                                                <button type="submit" class="btn btn-danger" {{ !$allianceCalculator->canBreakAlliance($realmAlliance, $selectedDominion) ? 'disabled' : ''}}>
                                                                    Break
                                                                </button>
                                                            </form> 
                                                        </div> 
                                                    </td>
                                                </tr>
                                            @endforeach
                                        <tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>In this round, you are able to form alliances with other realms. Allied realms cannot take hostile actions against each other but can cast friendly spells on each other.</p>
                <p>You can form alliances with realms that have a governor and if there have been no invasions between the two realms in the past 48 ticks.</p>
                <p>An alliance can be broken after 192 ticks.</p>
                <p>Breaking an alliance incurs a prestige penalty: <code>25% * 1 - min([Tick Duration of Alliance]/1000, 1)</code></p>
                <p>After breaking an alliance, there is a prestige penalty if invading former ally: <code>50% * 1 - min([Ticks Since Break of Alliance]/192, 1)</code></p>
            </div>
        </div>
    </div>


</div>

@endif

@if(!$selectedDominion->race->getPerkValue('cannot_vote') and !($selectedDominion->round->mode == 'deathmatch' or $selectedDominion->round->mode =='deathmatch-duration'))
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
                                                            data-land="{{ number_format($dominion->land) }}"
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
                                        <button type="submit" class="btn btn-primary btn-block" {{ ($selectedDominion->isLocked() or !$governmentCalculator->canVote($selectedDominion)) ? 'disabled' : null }}>
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
                                                        <span data-toggle="tooltip" data-placement="top" title="Governor of The Realm">
                                                        <i class="fa fa-star fa-lg text-orange"></i>
                                                        </span>
                                                    @endif
                                                    {{ $dominion->name }}
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
                <p>Here you can vote for the governor of your realm. You can only change your vote every 192 ticks.</p>
                @if(isset($selectedDominion->tick_voted))

                    @if($governmentCalculator->canVote($selectedDominion))
                        <p>You are currently able to vote.</p>
                    @else
                        <p>You can vote again in <strong>{{ number_format($governmentCalculator->getTicksUntilCanVote($selectedDominion)) . ' ' . str_plural('tick', $governmentCalculator->getTicksUntilCanVote($selectedDominion)) }}</strong>.</p>
                    @endif

                @elseif($governmentCalculator->canVote($selectedDominion))
                    <p>You have not cast a vote yet.</p>
                @else
                    <p>You cannot vote.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

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
@push('page-scripts')
    <script type="text/javascript">
    $("form").submit(function () {
        // prevent duplicate form submissions
        $(this).find(":submit").attr('disabled', 'disabled');
    });
    </script>
@endpush
