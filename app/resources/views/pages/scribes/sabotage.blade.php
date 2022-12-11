@extends('layouts.topnav')
@section('title', "Scribes | Sabotage")

@section('content')
@include('partials.scribes.nav')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Sabotage</h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-12">
                <p>Units are assigned a Sabotage Power, which is calculated as 1 for spies and for other units as <code>[Offensive Power] * ([Counts As Spy] + [Counts As Spy On Offense] + [Counts As Spy On Sabotage])</code>, meaning that a unit 6 offensive power and which counts as 0.5 spy has 3.0 sabotage power.</p>
                <p>The formula used to calculate sabotage damage is:</p>
                <code>
                    ([Base Damage] * [Spy Strength]) * (([Sabotage Point Sent] / [Target Land Size]) / [Target Defensive SPA])) * [Saboteur Damage Boosts] * [Target Damage Reductions]
                </code>
                <p>Definitions:</p>
                <ul>
                    <li><code>[Base Damage]</code> is the base damage stated for that particular sabotage operation/perk.</li>
                    <li><code>[Spy Strength]</code> is the amount of spy strength the saboteur is spending on this operation.</li>
                    <li><code>[Sabotage Point Sent]</code> is the amount of sabotage points the saboteur is sending on this operation.</li>
                    <li><code>[Target Land Size]</code> is the size of the target's land.</li>
                    <li><code>[Target Defensive SPA]</code> is the target's defensive SPA.</li>
                    <li><code>[Saboteur Damage Boosts]</code> is the sum of all the damage boosts the saboteur has.</li>
                    <li><code>[Target Damage Reductions]</code> is the sum of all the damage reductions the target has.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Operations</h3>
    </div>

    <div class="box-body table-responsive">
        <div class="row">
            <div class="col-md-12">
              <table class="table table-striped">
                  <colgroup>
                      <col width="200">
                      <col>
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
