@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Spells</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <h4>Class</h4>
                    <ul>
                        <li><b>Aura</b>: the spell lingers for a specific duration.</li>
                        <li><b>Impact</b>: the effect of the spell is immediate and then dissipates. No lingering effect.</li>
                        <li><b>Info</b>: the spell is used to gather information about the target.</li>
                        <li><b>Invasion</b>: the spell is triggered automatically during an invasion.</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h4>Scope</h4>
                    <ul>
                        <li><b>Friendly</b>: cast on dominions in your realm.</li>
                        <li><b>Hostile</b>: cast on dominions not in your realm.</li>
                        <li><b>Self</b>: cast on yourself.</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h4>General</h4>
                    <ul>
                        <li><b>Cost</b>: mana cost multiplied by your land size.</li>
                        <li><b>Duration</b>: how long the spell lasts.</li>
                        <li><b>Cooldown</b>: time before spell can be cast again.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- BEGIN AURA -->
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Auras</h3>
        </div>


        <div class="box-header">
            <h4 class="box-title">Friendly Auras</h4>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                  <table class="table table-striped">
                      <colgroup>
                          <col width="200">
                          <col width="50">
                      </colgroup>
                      <thead>
                          <tr>
                              <th>Spell</th>
                              <th>Cost</th>
                              <th>Duration</th>
                              <th>Cooldown</th>
                              <th>Effect</th>
                          </tr>
                      </thead>
                      @foreach ($spells as $spell)
                          @if($spell->class == 'passive' and $spell->scope == 'friendly')
                          <tr>
                              <td>
                                  {{ $spell->name }}
                                  {!! $spellHelper->getExclusivityString($spell) !!}
                              </td>
                              <td>{{ $spell->cost }}x</td>
                              <td>{{ $spell->duration }} ticks</td>
                              <td>
                                  @if($spell->cooldown > 0)
                                      {{ $spell->cooldown }} hours
                                  @else
                                      None
                                  @endif
                              </td>
                              <td>
                                  <ul>
                                      @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                          <li>{{ ucfirst($effect) }}</li>
                                      @endforeach
                                  <ul>
                              </td>
                          </tr>
                          @endif
                      @endforeach
                  </table>
                </div>
            </div>
        </div>

        <div class="box-header">
            <h4 class="box-title">Hostile Auras</h4>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                  <table class="table table-striped">
                      <colgroup>
                          <col width="200">
                          <col width="50">
                      </colgroup>
                      <thead>
                          <tr>
                              <th>Spell</th>
                              <th>Cost</th>
                              <th>Duration</th>
                              <th>Cooldown</th>
                              <th>Effect</th>
                          </tr>
                      </thead>
                      @foreach ($spells as $spell)
                          @if($spell->class == 'passive' and $spell->scope == 'hostile')
                          <tr>
                              <td>
                                  {{ $spell->name }}
                                  {!! $spellHelper->getExclusivityString($spell) !!}
                              </td>
                              <td>{{ $spell->cost }}x</td>
                              <td>{{ $spell->duration }} ticks</td>
                              <td>
                                  @if($spell->cooldown > 0)
                                      {{ $spell->cooldown }} hours
                                  @else
                                      None
                                  @endif
                              </td>
                              <td>
                                  <ul>
                                      @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                          <li>{{ ucfirst($effect) }}</li>
                                      @endforeach
                                  <ul>
                              </td>
                          </tr>
                          @endif
                      @endforeach
                  </table>
                </div>
            </div>
        </div>

        <div class="box-header">
            <h4 class="box-title">Self Auras</h4>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                  <table class="table table-striped">
                      <colgroup>
                          <col width="200">
                          <col width="50">
                      </colgroup>
                      <thead>
                          <tr>
                              <th>Spell</th>
                              <th>Cost</th>
                              <th>Duration</th>
                              <th>Cooldown</th>
                              <th>Effect</th>
                          </tr>
                      </thead>
                      @foreach ($spells as $spell)
                          @php
                              $exclusives = count($spell->exclusive_races);
                              $excludes = count($spell->excluded_races);
                          @endphp
                          @if($spell->class == 'passive' and $spell->scope == 'self')
                          <tr>
                              <td>
                                  {{ $spell->name }}
                                  {!! $spellHelper->getExclusivityString($spell) !!}
                              </td>
                              <td>{{ $spell->cost }}x</td>
                              <td>{{ $spell->duration }} ticks</td>
                              <td>
                                  @if($spell->cooldown > 0)
                                      {{ $spell->cooldown }} hours
                                  @else
                                      None
                                  @endif
                              </td>
                              <td>
                                  <ul>
                                      @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                          <li>{{ ucfirst($effect) }}</li>
                                      @endforeach
                                  <ul>
                              </td>
                          </tr>
                          @endif
                      @endforeach
                  </table>
                </div>
            </div>
        </div>
        <!-- END AURA -->

        <!-- BEGIN IMPACT -->
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Impact Spells</h3>
            </div>

            <div class="box-header">
                <h4 class="box-title">Friendly Impact Spells</h4>
            </div>
            <div class="box-body table-responsive">
                <div class="row">
                    <div class="col-md-12">
                      <table class="table table-striped">
                          <colgroup>
                              <col width="200">
                              <col width="50">
                          </colgroup>
                          <thead>
                              <tr>
                                  <th>Spell</th>
                                  <th>Cost</th>
                                  <th>Cooldown</th>
                                  <th>Effect</th>
                              </tr>
                          </thead>
                          @foreach ($spells as $spell)
                              @if($spell->class == 'active' and $spell->scope == 'friendly')
                              <tr>
                                  <td>
                                      {{ $spell->name }}
                                      {!! $spellHelper->getExclusivityString($spell) !!}
                                  </td>
                                  <td>{{ $spell->cost }}x</td>
                                  <td>
                                      @if($spell->cooldown > 0)
                                          {{ $spell->cooldown }} hours
                                      @else
                                          None
                                      @endif
                                  </td>
                                  <td>
                                      <ul>
                                          @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                              <li>{{ ucfirst($effect) }}</li>
                                          @endforeach
                                      <ul>
                                  </td>
                              </tr>
                              @endif
                          @endforeach
                      </table>
                    </div>
                </div>
            </div>

            <div class="box-header">
                <h4 class="box-title">Hostile Impact Spells</h4>
            </div>
            <div class="box-body table-responsive">
                <div class="row">
                    <div class="col-md-12">
                      <table class="table table-striped">
                          <colgroup>
                              <col width="200">
                              <col width="50">
                          </colgroup>
                          <thead>
                              <tr>
                                  <th>Spell</th>
                                  <th>Cost</th>
                                  <th>Cooldown</th>
                                  <th>Effect</th>
                              </tr>
                          </thead>
                          @foreach ($spells as $spell)
                              @if($spell->class == 'active' and $spell->scope == 'hostile')
                              <tr>
                                  <td>
                                      {{ $spell->name }}
                                      {!! $spellHelper->getExclusivityString($spell) !!}
                                  </td>
                                  <td>{{ $spell->cost }}x</td>
                                  <td>
                                      @if($spell->cooldown > 0)
                                          {{ $spell->cooldown }} hours
                                      @else
                                          None
                                      @endif
                                  </td>
                                  <td>
                                      <ul>
                                          @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                              <li>{{ ucfirst($effect) }}</li>
                                          @endforeach
                                      <ul>
                                  </td>
                              </tr>
                              @endif
                          @endforeach
                      </table>
                    </div>
                </div>
            </div>


            <div class="box-header">
                <h4 class="box-title">Self Impact Spells</h4>
            </div>
            <div class="box-body table-responsive">
                <div class="row">
                    <div class="col-md-12">
                      <table class="table table-striped">
                          <colgroup>
                              <col width="200">
                              <col width="50">
                          </colgroup>
                          <thead>
                              <tr>
                                  <th>Spell</th>
                                  <th>Cost</th>
                                  <th>Cooldown</th>
                                  <th>Effect</th>
                              </tr>
                          </thead>
                          @foreach ($spells as $spell)
                              @if($spell->class == 'active' and $spell->scope == 'self')
                              <tr>
                                  <td>
                                      {{ $spell->name }}
                                      {!! $spellHelper->getExclusivityString($spell) !!}
                                  </td>
                                  <td>{{ $spell->cost }}x</td>
                                  <td>
                                      @if($spell->cooldown > 0)
                                          {{ $spell->cooldown }} hours
                                      @else
                                          None
                                      @endif
                                  </td>
                                  <td>
                                      <ul>
                                          @foreach($spellHelper->getSpellEffectsString($spell) as $effect)
                                              <li>{{ ucfirst($effect) }}</li>
                                          @endforeach
                                      <ul>
                                  </td>
                              </tr>
                              @endif
                          @endforeach
                      </table>
                    </div>
                </div>
            </div>
            <!-- END IMPACT -->

    </div>
@endsection
