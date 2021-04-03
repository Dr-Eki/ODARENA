@extends('layouts.master')

@section('page-header', "Register to Round {$round->number} of ODARENA")

@section('content')

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Round #{{ $round->number }} &mdash; <strong>{{ $round->name }}</strong></h3>
        </div>
        <form action="{{ route('round.register', $round) }}" method="post" class="form-horizontal" role="form">
            @csrf

            <div class="box-body">

                <!-- Dominion Name -->
                <div class="form-group">
                    <label for="dominion_name" class="col-sm-3 control-label">Dominion Name</label>
                    <div class="col-sm-9">
                        <input type="text" name="dominion_name" id="dominion_name" class="form-control" placeholder="Dominion Name" value="{{ old('dominion_name') }}" maxlength="50" required autofocus>
                        <p class="help-block">Your dominion name is shown when viewing and interacting with other players.</p>
                    </div>
                </div>

                <!-- Title -->
                <div class="form-group">
                    <label for="ruler_name" class="col-sm-3 control-label">Ruler Title</label>
                    <div class="col-sm-9">
                        <select name="title" id="title" class="form-control select2" required>
                        <option disabled selected>-- Select Ruler Title --</option>
                        @foreach ($titles as $title)

                        <option value="{{ $title->id }}">
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
                    <div class="col-sm-9">
                        <input type="text" name="ruler_name" id="ruler_name" class="form-control" placeholder="{{ Auth::user()->display_name }}" value="{{ old('ruler_name') }}">
                        <p class="help-block">If you leave it as default ({{ Auth::user()->display_name }}), you get 100 extra prestige for playing under your real display name.</p>
                    </div>
                </div>

                <!-- Race -->
                <div class="form-group">
                    <label for="race" class="col-sm-3 control-label">Faction</label>
                    <div class="col-sm-9">
                        <div class="row">

                            <!-- Commonwealth -->
                            <div class="col-xs-12">
                              <div class="row">
                                  <div class="col-xs-1">
                                    <img src="{{ asset('assets/app/images/commonwealth.svg') }}" class="img-responsive" alt="The Commonwealth">
                                  </div>
                                  <div class="col-xs-11">
                                    <h2>The Commonwealth</h2>
                                    <p>The Commonwealth is a union of factions and races which have come together and joined forces in response to the Empire.</p>

                                    <p>So far,
                                    @if(isset($countAlignment['good']))
                                      {{ number_format($countAlignment['good']) }}
                                    @else
                                      no
                                    @endif
                                     dominions have joined the Commonwealth this round.</p>
                                  </div>

                                    @foreach ($races->filter(function ($race) { return $race->playable && $race->alignment === 'good'; }) as $race)
                                        @include('partials.register-faction')
                                    @endforeach
                                </div>
                            </div>

                            <!-- Empire -->
                            <div class="col-xs-12">
                                <div class="row">
                                      <div class="col-xs-1">
                                        <img src="{{ asset('assets/app/images/empire.svg') }}" class="img-responsive" alt="The Empire">
                                      </div>
                                      <div class="col-xs-11">
                                        <h2>The Empire</h2>
                                        <p>Seizing the opportunity, the Orcish Empress formed the fledgling Empire only recently but sits unquestioned at the thrones and rules with a firm grip.</p>
                                        <p>So far,
                                        @if(isset($countAlignment['evil']))
                                          {{ number_format($countAlignment['evil']) }}
                                        @else
                                          no
                                        @endif
                                         dominions have joined the Empire this round.</p>
                                      </div>
                                </div>

                                <div class="row">

                                    @foreach ($races->filter(function ($race) { return $race->playable && $race->alignment === 'evil'; }) as $race)
                                        @include('partials.register-faction')
                                    @endforeach
                                </div>
                            </div>

                            <!-- Independent -->
                            <div class="col-xs-12">
                                  <div class="row">
                                    <div class="col-xs-1">
                                      <img src="{{ asset('assets/app/images/independent.svg') }}" class="img-responsive" alt="Independent">
                                    </div>
                                    <div class="col-xs-11">
                                      <h2>Independent</h2>
                                      <p>Unaffected or unaffiliated, or even unaware, these are the factions which do not align with Empire or the Commonwealth. Preferring to be left alone, they have been forced to band together as a tattered band of forces dedicated to maintaining their independence.</p>
                                      <p>So far,
                                      @if(isset($countAlignment['independent']))
                                        {{ number_format($countAlignment['independent']) }}
                                      @else
                                        no
                                      @endif
                                       dominions have joined the Independents this round.</p>
                                    </div>
                                  </div>
                                <div class="row">
                                    @foreach ($races->filter(function ($race) { return $race->playable && $race->alignment === 'independent'; }) as $race)
                                        @include('partials.register-faction')
                                    @endforeach
                                </div>
                            </div>

                            @if(stristr(Auth::user()->email, '@lykanthropos.com') or stristr(Auth::user()->email, '@odarena.local') or stristr(Auth::user()->email, '@odarena.com') or stristr(Auth::user()->email, '@odarena.com'))
                            <div class="col-xs-12">
                                  <div class="row">
                                      <div class="col-xs-1">
                                        <img src="{{ asset('assets/app/images/barbarian.svg') }}" class="img-responsive" alt="Barbarian Horde">
                                      </div>
                                      <div class="col-xs-11">
                                        <h2>Barbarian Horde</h2>
                                        <p></p>
                                      </div>
                                  </div>

                                <div class="row">
                                    @foreach ($races->filter(function ($race) { return $race->alignment === 'npc'; }) as $race)
                                    <div class="col-xs-12">
                                        <label class="btn btn-block" style="border: 1px solid #d2d6de; margin: 5px 0px; white-space: normal;">
                                            <div class="row text-left">
                                                <div class="col-lg-4">
                                                    <p>
                                                        <input type="radio" name="race" value="{{ $race->id }}" autocomplete="off" {{ (old('race') == $race->id) ? 'checked' : null }} required>
                                                        <strong>{{ $race->name }}</strong>
                                                        &nbsp;&mdash;&nbsp;
                                                    <a href="{{ route('scribes.faction', $race->name) }}">Scribes</a>
                                                    </p>
                                                    <p>
                                                    Currently:&nbsp;
                                                    @if(isset($countRaces[$race->name]))
                                                      {{ number_format($countRaces[$race->name]) }}
                                                    @else
                                                    0
                                                    @endif
                                                  </p>
                                                  </p>
                                                </div>

                                                <div class="col-sm-4">
                                                  <ul>
                                                    <li>Attacking: {{ str_replace('0','Unplayable',str_replace(1,'Difficult',str_replace(2,'Challenging', str_replace(3,'Apt',$race->attacking)))) }}</li>
                                                    <li>Converting: {{ str_replace('0','Unplayable',str_replace(1,'Difficult',str_replace(2,'Challenging', str_replace(3,'Apt',$race->converting)))) }}</li>
                                                    <li>Exploring: {{ str_replace('0','Unplayable',str_replace(1,'Difficult',str_replace(2,'Challenging', str_replace(3,'Apt',$race->exploring)))) }}</li>
                                                  </ul>
                                                </div>

                                            </div>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif




                        </div>
                    </div>
                </div>

                {{-- Terms and Conditions --}}
                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-9">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="terms" required> I agree to the <a href="{{ route('legal.termsandconditions') }}">Terms and Conditions</a>
                            </label>
                        </div>
                    </div>
                </div>


                {{-- Notice --}}
                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-9">

                      @if($round->hasStarted())
                          @if($round->hasCountdown())
                              <p>This round has already started and ends {{ $round->end_date->format('l, jS \o\f F Y \a\t H:i') }}.</p>
                          @else
                              <p>This round has already started!</p>
                          @endif
                          <p>When you register, you start with 96 protection ticks. Make the most of them. Once you have used them all, you leave protection immediately.</p>
                          <p>Regularly scheduled ticks do not count towards your dominion while you are in protection.</p>
                          <p>To help you get going, you will get 1% extra starting resources for every hour since the round started (max +100%).</p>

                          @if ($discordInviteLink = config('app.discord_invite_link'))
                          <p>If you need any help or just want to chat, come join us on <a href="{{ $discordInviteLink }}" target="_blank">Discord</a>.</p>
                          @endif

                      @else
                          <p>The round starts on {{ $round->start_date->format('l, jS \o\f F Y \a\t H:i') }}.</p>
                          <p>When you register, you start with 84 protection ticks. Make the most of them. Once you have used them all, you leave protection immediately.</p>
                          <p>Regularly scheduled ticks do not count towards your dominion while you are in protection.</p>

                            @if ($discordInviteLink = config('app.discord_invite_link'))
                                <p>In the meantime, come join us on <a href="{{ $discordInviteLink }}" target="_blank">Discord</a>.</p>
                            @endif

                      @endif

                      <p>Head over to the <a href="https://sim.odarena.com/" target="_blank">ODARENA Simulator</a> if you want to sim protection. Click <a href="https://lounge.odarena.com/2020/02/24/odarena-sim/" target="_blank">here</a> to read about how the sim works.</p>

                    </div>
                </div>

            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>

        </form>
    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var realmTypeEl = $('#realm_type');
            var createPackOnlyEls = $('.create-pack-only');
            var joinPackOnlyEls = $('.join-pack-only');

            function updatePackInputs() {
                var realmTypeOption = realmTypeEl.find(':selected');

                if (realmTypeOption.val() === 'join_pack') {
                    createPackOnlyEls.hide();
                    joinPackOnlyEls.show();

                } else if (realmTypeOption.val() === 'create_pack') {
                    joinPackOnlyEls.hide();
                    createPackOnlyEls.show();

                } else {
                    createPackOnlyEls.hide();
                    joinPackOnlyEls.hide();
                }
            }

            realmTypeEl.on('change', updatePackInputs);

            updatePackInputs();
        })(jQuery);
    </script>
@endpush
