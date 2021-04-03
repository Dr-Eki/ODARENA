@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Spy Operations</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <h4>Info</h4>
                    <ul>
                        <li><b>Aura</b>: the spell lingers for a specific duration.</li>
                        <li><b>Impact</b>: the effect of the spell is immediate and then dissipates. No lingering effect.</li>
                        <li><b>Info</b>: the spell is used to gather information about the target.</li>
                        <li><b>Invasion</b>: the spell is triggered automatically during an invasion.</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h4>Hostile</h4>
                    <ul>
                        <li><b>Friendly</b>: cast on dominions in your realm.</li>
                        <li><b>Hostile</b>: cast on dominions not in your realm.</li>
                        <li><b>Self</b>: cast on yourself.</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h4>Theft</h4>
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
            <h3 class="box-title">Operations</h3>
        </div>

        <div class="box-header">
            <h4 class="box-title">Info</h4>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                  <table class="table table-striped">
                      <colgroup>
                          <col width="200">
                      </colgroup>
                      <thead>
                          <tr>
                              <th>Operation</th>
                              <th>Effect</th>
                          </tr>
                      </thead>
                      @foreach ($spyops as $spyop)
                          @if($spyop->scope == 'info')
                          <tr>
                              <td>
                                  {{ $spyop->name }}
                                  {!! $espionageHelper->getExclusivityString($spyop) !!}
                              </td>
                              <td>
                                  <ul>
                                      @foreach($espionageHelper->getSpyopEffectsString($spyop) as $effect)
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
            <h4 class="box-title">Theft</h4>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                  <table class="table table-striped">
                      <colgroup>
                          <col width="200">
                      </colgroup>
                      <thead>
                          <tr>
                              <th>Operation</th>
                              <th>Effect</th>
                          </tr>
                      </thead>
                      @foreach ($spyops as $spyop)
                          @if($spyop->scope == 'theft')
                          <tr>
                              <td>
                                  {{ $spyop->name }}
                                  {!! $espionageHelper->getExclusivityString($spyop) !!}
                              </td>
                              <td>
                                  <ul>
                                      @foreach($espionageHelper->getSpyopEffectsString($spyop) as $effect)
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
            <h4 class="box-title">Hostile</h4>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                  <table class="table table-striped">
                      <colgroup>
                          <col width="200">
                      </colgroup>
                      <thead>
                          <tr>
                              <th>Operation</th>
                              <th>Effect</th>
                          </tr>
                      </thead>
                      @foreach ($spyops as $spyop)
                          @if($spyop->scope == 'hostile')
                          <tr>
                              <td>
                                  {{ $spyop->name }}
                                  {!! $espionageHelper->getExclusivityString($spyop) !!}
                              </td>
                              <td>
                                  <ul>
                                      @foreach($espionageHelper->getSpyopEffectsString($spyop) as $effect)
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


    </div>
@endsection
