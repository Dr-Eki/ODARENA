@extends('layouts.master')
@section('title', 'Deity')

@section('content')

<div class="row">
    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-pray"></i> Deity</h3>
            </div>
            <div class="box-body">
                @if(!$selectedDominion->hasDeity())
                <form action="{{ route('dominion.government.deity') }}" method="post" role="form">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <colgroup>
                                    <col width="50">
                                    <col width="200">
                                    <col width="100">
                                    <col>
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Deity</th>
                                        <th>Range Multiplier</th>
                                        <th>Perks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($deityHelper->getDeitiesByRace($selectedDominion->race) as $deity)
                                    <tr>
                                        <td>
                                            @if($selectedDominion->hasPendingDeitySubmission() and $selectedDominion->getPendingDeitySubmission()->key == $deity->key)
                                                <span class="text-muted"><i class="fas fa-pray"></i></span>
                                            @else
                                                <input type="radio" name="key" id="{{ $deity->key }}" value="{{ $deity->key }}" {{ ($selectedDominion->isLocked() || $selectedDominion->hasPendingDeitySubmission()) ? 'disabled' : null }}>
                                            @endif
                                        </td>
                                        <td>
                                            <label for="{{ $deity->key }}">{{ $deity->name }}</label>
                                            @if($selectedDominion->hasPendingDeitySubmission() and $selectedDominion->getPendingDeitySubmission()->key == $deity->key)
                                            <br><span class="small text-muted"><strong>{{ $selectedDominion->getPendingDeitySubmissionTicksLeft() }}</strong> {{ str_plural('tick', $selectedDominion->getPendingDeitySubmissionTicksLeft()) }} left until devotion is in effect</span>
                                            @endif
                                        </td>
                                        <td>{{ $deity->range_multiplier }}x</td>
                                        <td>
                                            <ul>
                                                @foreach($deityHelper->getDeityPerksString($deity) as $effect)
                                                    <li>{{ ucfirst($effect) }}</li>
                                                @endforeach
                                            </ul>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-6">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block" {{ ($selectedDominion->isLocked() || $selectedDominion->hasPendingDeitySubmission()) ? 'disabled' : null }}>
                                Submit To This Deity
                            </button>
                        </div>
                    </div>
                </form>
                @elseif($selectedDominion->deity)
                <form id="renounce-deity" action="{{ route('dominion.government.renounce') }}" method="post" role="form">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <form action="{{ route('dominion.government.deity') }}" method="post" role="form">
                            <p>You have been devoted to <strong>{{ $selectedDominion->deity->name }}</strong> for {{ $selectedDominion->devotion->duration }} ticks, granting you the following perks:</p>
                            <ul>
                                @foreach($deityHelper->getDeityPerksString($selectedDominion->deity, $selectedDominion->getDominionDeity()) as $effect)
                                    <li>{{ ucfirst($effect) }}</li>
                                @endforeach
                                    <li>Range multiplier: {{ $selectedDominion->deity->range_multiplier }}x</li>
                            </ul>
                            @if(!$selectedDominion->race->getPerkValue('cannot_renounce_deity'))
                                <p>If you wish to devote your dominion to another deity, you may renounce your devotion to {{ $selectedDominion->deity->name }} below.</p>
                            @endif
                        </div>
                    </div>

                    @if(!$selectedDominion->race->getPerkValue('cannot_renounce_deity'))
                        <div class="col-sm-6 col-lg-6">
                            <div class="form-group">
                                <select id="renounce-deity"  class="form-control">
                                    <option value="0">Renounce devotion?</option>
                                    <option value="1">Confirm renounce</option>
                                </select>
                                <button id="renounce-deity" type="submit" class="btn btn-danger btn-block" disabled {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    Renounce This Deity
                                </button>
                            </div>
                        </div>
                    @endif
                </form>
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
                <p>You can devote your dominion to a deity in exchange for some perks (good and bad). For every tick that you remain devoted to a deity, the perks are increased by 0.10% per tick to a maximum of +100%, when the perk values are doubled.</p>
                <p>It takes 48 ticks for a devotion to take effect. Your dominion can only be submitted to one deity at a time. However, you can renounce your deity to select a new one (which resets the ticks counter).</p>
                <p>The range multiplier is the maximum land size range the deity permits you to interact with, unless recently invaded, and takes effect immediately once you submit to a deity. A dominion with a wider range cannot take actions against a dominion with a more narrow range, unless the two ranges overlap.</p>
                <p>For more information, see the <a href="{{ route('scribes.deities') }}" target="_blank"><i class="ra ra-scroll-unfurled"></i> Scribes</a>.<p>
            </div>
        </div>
    </div>
</div>
@endsection

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
