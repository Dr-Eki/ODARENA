@extends('layouts.topnav')
@section('title', "Scribes | {$race->name}")

@section('content')
    @include('partials.scribes.nav')
<div class="row">

    <div class="col-sm-12 col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="col-sm-2 text-center">
                    <h2 class="box-title">{{ $race->name }}</h2>

                    @if($race->experimental)
                        <span class="label label-danger">Experimental</span>
                    @endif
                </div>

                <div class="col-sm-8 text-center">
                    <a href="#units">Units</a> |
                    <a href="#resources">Resources</a> |
                    <a href="#buildings">Buildings</a> |
                    <a href="#improvements">Improvements</a> |
                    @if($raceHelper->hasLandImprovements($race))
                        <a href="#land_improvements">Land Perks</a> |
                    @endif
                    <a href="#spells">Spells</a> |
                    <a href="#sabotage">Sabotage</a>
                </div>

                <div class="col-sm-2 text-center">
                    Difficulty:
                    @if($race->skill_level === 1)
                        <span class="label label-success">Comfortable</span>
                    @elseif($race->skill_level === 2)
                        <span class="label label-warning">Challenging</span>
                    @elseif($race->skill_level === 3)
                        <span class="label label-danger">Advanced</span>
                    @endif
                </div>
            </div>
            <div>


            </div>
            @if($race->description)
                <div class="box-body">
                {!! $race->description !!}
                </div>
            @endif
        </div>
    </div>
</div>
<div class="row">

    <a id="units"></a>
    <div class="col-sm-12 col-md-9">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Units</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table">
                    <colgroup>
                        <col width="100">
                        <col width="100">
                        <col>
                        <col width="100">
                        <col width="100">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th class="text-center">OP / DP</th>
                            <th>Special Abilities</th>
                            <th>Attributes</th>
                            <th>Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                      @foreach ($race->units as $unit)
                          @if(in_array($unit->slot, ['wizards','spies','archmages']))
                              @php
                                  $unitType = $unit->slot;
                              @endphp
                          @else
                              @php
                                  $unitType = 'unit' . $unit->slot;
                              @endphp
                          @endif
                          <tr>
                              <td>
                                  <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $race) }}">
                                      {{ $unitHelper->getUnitName($unitType, $race) }}
                                  </span>
                              </td>
                                @if (in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4', 'unit5', 'unit6', 'unit7', 'unit8', 'unit9', 'unit10']))
                                    <td class="text-center">  <!-- OP / DP -->
                                        {{ display_number_format($unit->power_offense) }}
                                        /
                                        {{ display_number_format($unit->power_defense) }}
                                    </td>
                                @else
                                    <td class="text-center">&mdash;</td>
                                    <td class="text-center">  <!-- If Spy/Wiz/AM --></td>
                                @endif

                              <td>
                                  {!! $unitHelper->getUnitHelpString("unit{$unit->slot}", $race) !!}
                              </td>
                              <td>
                                  {!! $unitHelper->getUnitAttributesList("unit{$unit->slot}", $race) !!}
                              </td>

                              <td>  <!-- Cost -->
                                    @if($race->getUnitPerkValueForUnitSlot($unit->slot,'cannot_be_trained'))
                                        &mdash;
                                    @else
                                        {!! $unitHelper->getUnitCostString($race, $unit->cost) !!}
                                    @endif
                              </td>
                          </tr>
                      @endforeach

                    </tbody>
                </table>
            </div>
        </div>
      </div>

      <a id="perks"></a>
      <div class="col-sm-12 col-md-3 no-padding">
          <div class="col-sm-12 col-md-12">
              <div class="box">
                  <div class="box-header with-border">
                      <h3 class="box-title">Traits</h3>
                  </div>
                  <div class="box-body table-responsive no-padding">
                      <table class="table table-striped">
                          <colgroup>
                              <col>
                              <col>
                          </colgroup>
                          <tbody>
                              @if(!$race->getPerkValue('no_population'))
                                  <tr>
                                      <td>
                                          <span data-toggle="tooltip" data-placement="top" title="Term used for workers in this faction (<em>peasants</em> by default)">Workers:</span>
                                      </td>
                                      <td>{{ $raceHelper->getPeasantsTerm($race) }} {!! $race->getPerkValue('peasant_dp') ? '<small class="text-muted">(DP:&nbsp;' . $race->getPerkValue('peasant_dp') . ')</small>' : '' !!} </td>
                                  </tr>
                                  @if(!$race->getPerkValue('no_drafting'))
                                      <tr>
                                          <td>
                                              <span data-toggle="tooltip" data-placement="top" title="Term used for draftees in this faction (<em>draftees</em> by default)">Draftees:</span>
                                          </td>
                                          <td>{{ $raceHelper->getDrafteesTerm($race) }} <small class="text-muted">(DP:&nbsp;{{$race->getPerkValue('draftee_dp') ?: 1}})</small></td>
                                      </tr>
                                  @endif
                              @endif
                              @if($race->max_per_round)
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="Maximum amount of dominions of this faction per round">Max per round:</span>
                                    </td>
                                    <td><span class="text-red">{{ $race->max_per_round }}</a></td>
                                </tr>
                              @endif
                              @if($race->minimum_rounds)
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="Minimum rounds you must have played in order to select this faction">Minimum rounds played:</span>
                                    </td>
                                    <td><span class="text-red">{{ $race->minimum_rounds }}</a></td>
                                </tr>
                              @endif
                              @if($race->psionic_strength !== 1)
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="Standard is 1">Psionic base strength:</span>
                                    </td>
                                    <td><span class="text-info">{{ number_format($race->psionic_strength,6) }}</a></td>
                                </tr>
                              @endif
                              @if(!$race->getPerkValue('no_population'))
                              <tr>
                                  <td>
                                      <span data-toggle="tooltip" data-placement="top" title="What each worker produces">{{ $raceHelper->getPeasantsTerm($race) }} production:</span>
                                  </td>
                                  <td>
                                      @php
                                          $x = 0;
                                          $peasantProductions = count($race->peasants_production);
                                      @endphp
                                      @foreach ($race->peasants_production as $resourceKey => $amount)
                                          @php
                                              $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                              $x++;
                                          @endphp


                                          <span class="text-green">
                                              @if($x < $peasantProductions)
                                                  {{ number_format($amount,2) }}&nbsp;{{ $resource->name }},
                                              @else
                                                  {{ number_format($amount,2) }}&nbsp;{{ $resource->name }}
                                              @endif
                                          </span>
                                      @endforeach
                                  </td>
                              </tr>
                              @endif
                              @foreach ($race->perks as $perk)
                                  @php
                                      $perkDescription = $raceHelper->getPerkDescriptionHtmlWithValue($perk);
                                  @endphp
                                  <tr>
                                      <td>
                                          {!! $perkDescription['description'] !!}
                                      </td>
                                      <td>
                                          {!! $perkDescription['value'] !!}
                                      </td>
                                  </tr>
                              @endforeach
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>

      </div>
</div>

<a id="resources"></a>
<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Resources</h3>
            </div>

            <div class="box-body table-responsive">
                <div class="row">
                    <div class="col-md-12">
                      <table class="table table-striped">
                          <colgroup>
                              <col width="20%">
                              <col width="20%">
                              <col width="20%">
                              <col width="20%">
                              <col width="20%">
                          </colgroup>
                          <thead>
                              <tr>
                                  <th>Resource</th>
                                  <th>Construction</th>
                                  <th>Buy</th>
                                  <th>Sell</th>
                                  <th>Improvement Points</th>
                              </tr>
                          </thead>
                          @foreach ($race->resources as $resourceKey)
                              @php
                                  $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                              @endphp
                              <tr>
                                  <td>{{ $resource->name }}</td>
                                  <td>{{ in_array($resourceKey, $race->construction_materials) ? 'Yes' : '' }}</td>
                                  <td>{{ $resource->buy ? number_format($resource->buy, 2) : 'N/A' }}</td>
                                  <td>{{ $resource->sell ? number_format($resource->sell, 2) : 'N/A' }}</td>
                                  <td>{{ isset($race->improvement_resources[$resourceKey]) ? number_format($race->improvement_resources[$resourceKey],2) : 'N/A' }}</td>
                              </tr>
                          @endforeach
                      </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<a id="buildings"></a>
<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Buildings</h3>
            </div>

            <div class="box-body table-responsive">
                <div class="row">
                    <div class="col-md-12">
                      <table class="table table-striped">
                          <colgroup>
                              <col width="200">
                              <col width="100">
                          </colgroup>
                          <thead>
                              <tr>
                                  <th>Building</th>
                                  <th>Land Type</th>
                                  <th>Perks</th>
                              </tr>
                          </thead>
                          @foreach ($buildings as $building)
                              <tr>
                                  <td>{{ $building->name }}</td>
                                  <td>{{ ucwords($building->land_type) }}</td>
                                  <td>{!! $buildingHelper->getBuildingDescription($building) !!}</td>
                              </tr>
                          @endforeach
                      </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@if($raceHelper->hasLandImprovements($race))
<a id="land_improvements"></a>
<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Land Perks</h3>
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
                                  <th>Land Type</th>
                                  <th>Perks</th>
                              </tr>
                          </thead>
                          @foreach ($landHelper->getLandTypes() as $landType)
                              <tr>
                                  <td>
                                      {!! $landHelper->getLandTypeIconHtml($landType) !!}&nbsp;{{ ucwords($landType) }}
                                  </td>
                                  <td>
                                      <ul>
                                      @if(isset($race->land_improvements[$landType]))
                                          @foreach($race->land_improvements[$landType] as $perk => $value)
                                              <li>
                                              {!! $LandImprovementHelper->getPerkDescription($perk, $value) !!}
                                              </li>
                                          @endforeach
                                      @else
                                          &mdash;
                                      @endif
                                      </ul>
                                  </td>
                              </tr>
                          @endforeach
                      </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<a id="improvements"></a>
<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Improvements</h3>
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
                                  <th>Building</th>
                                  <th>Perks</th>
                              </tr>
                          </thead>
                          @foreach ($improvements as $improvement)
                              <tr>
                                  <td>
                                      {{ $improvement->name }}
                                  </td>
                                  <td>
                                      <table>
                                          <colgroup>
                                              <col width="180">
                                              <col width="80">
                                              <col width="100">
                                          </colgroup>
                                          <thead>
                                              <tr>
                                                  <td><u>Perk</u></td>
                                                  <td><u>Max</u></td>
                                                  <td><u>Coefficient</u></td>
                                              </tr>
                                      @foreach($improvement->perks as $perk)
                                          @php
                                              $improvementPerkMax = number_format($improvementHelper->extractImprovementPerkValuesForScribes($perk->pivot->value)[0]);
                                              $improvementPerkCoefficient = number_format($improvementHelper->extractImprovementPerkValuesForScribes($perk->pivot->value)[1]);
                                              if($improvementPerkMax > 0)
                                              {
                                                  $improvementPerkMax = '+' . $improvementPerkMax;
                                              }
                                          @endphp
                                          <tr>
                                              <td>{{ ucwords($improvementHelper->getImprovementPerkDescription($perk->key)) }}</td>
                                              <td>{{ $improvementPerkMax }}%</td>
                                              <td>{{ $improvementPerkCoefficient }}</td>
                                          <tr>
                                      @endforeach
                                      </table>
                                  </td>
                              </tr>
                          @endforeach
                      </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <a id="spells"></a>
    <div class="col-sm-12 col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Spells</h3>
            </div>
            <div class="box-body">
                <h4 class="box-title">Friendly Auras</h4>
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                        <col width="100">
                        <col width="50">
                        <col width="50">
                        <col width="50">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Spell</th>
                            <th>Deity</th>
                            <th>Cost</th>
                            <th>Duration</th>
                            <th>Cooldown</th>
                            <th>Effect</th>
                        </tr>
                    <tbody>
                    @foreach ($spells as $spell)
                        @if($spell->class == 'passive' and $spell->scope == 'friendly' and $spellHelper->isSpellAvailableToRace($race, $spell))
                        <tr>
                            <td>{{ $spell->name }}</td>
                            <td>{{ $spell->deity ? $spell->deity->name : 'Any' }}</td>
                            <td>{{ $spell->cost }}x</td>
                            <td>{{ $spell->duration }} ticks</td>
                            <td>
                                @if($spell->cooldown > 0)
                                    {{ $spell->cooldown }} ticks
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
                    </tbody>
                </table>

                <h4 class="box-title">Hostile Auras</h4>
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                        <col width="100">
                        <col width="50">
                        <col width="50">
                        <col width="50">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Spell</th>
                            <th>Deity</th>
                            <th>Cost</th>
                            <th>Duration</th>
                            <th>Cooldown</th>
                            <th>Effect</th>
                        </tr>
                    <tbody>
                    @foreach ($spells as $spell)
                        @if($spell->class == 'passive' and $spell->scope == 'hostile' and $spellHelper->isSpellAvailableToRace($race, $spell))
                        <tr>
                            <td>{{ $spell->name }}</td>
                            <td>{!! $spell->deity ? $spell->deity->name : '<span class="text-muted">Any</span>' !!}</td>
                            <td>{{ $spell->cost }}x</td>
                            <td>{{ $spell->duration }} ticks</td>
                            <td>
                                @if($spell->cooldown > 0)
                                    {{ $spell->cooldown }} ticks
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
                    </tbody>
                </table>

                <h4 class="box-title">Self Auras</h4>
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                        <col width="100">
                        <col width="50">
                        <col width="50">
                        <col width="50">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Spell</th>
                            <th>Deity</th>
                            <th>Cost</th>
                            <th>Duration</th>
                            <th>Cooldown</th>
                            <th>Effect</th>
                        </tr>
                    <tbody>
                    @foreach ($spells as $spell)
                        @if($spell->class == 'passive' and $spell->scope == 'self' and $spellHelper->isSpellAvailableToRace($race, $spell))
                        <tr>
                            <td>{{ $spell->name }}</td>
                            <td>{!! $spell->deity ? $spell->deity->name : '<span class="text-muted">Any</span>' !!}</td>
                            <td>{{ $spell->cost }}x</td>
                            <td>{{ $spell->duration }} ticks</td>
                            <td>
                                @if($spell->cooldown > 0)
                                    {{ $spell->cooldown }} ticks
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
                    </tbody>
                </table>


                <h4 class="box-title">Friendly Impact Spells</h4>
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                        <col width="100">
                        <col width="50">
                        <col width="50">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Spell</th>
                            <th>Deity</th>
                            <th>Cost</th>
                            <th>Cooldown</th>
                            <th>Effect</th>
                        </tr>
                    <tbody>
                    @foreach ($spells as $spell)
                        @if($spell->class == 'active' and $spell->scope == 'friendly' and $spellHelper->isSpellAvailableToRace($race, $spell))
                        <tr>
                            <td>{{ $spell->name }}</td>
                            <td>{!! $spell->deity ? $spell->deity->name : '<span class="text-muted">Any</span>' !!}</td>
                            <td>{{ $spell->cost }}x</td>
                            <td>
                                @if($spell->cooldown > 0)
                                    {{ $spell->cooldown }} ticks
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
                    </tbody>
                </table>

                <h4 class="box-title">Hostile Impact Spells</h4>
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                        <col width="100">
                        <col width="50">
                        <col width="50">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Spell</th>
                            <th>Deity</th>
                            <th>Cost</th>
                            <th>Cooldown</th>
                            <th>Effect</th>
                        </tr>
                    <tbody>
                    @foreach ($spells as $spell)
                        @if($spell->class == 'active' and $spell->scope == 'hostile' and $spellHelper->isSpellAvailableToRace($race, $spell))
                        <tr>
                            <td>{{ $spell->name }}</td>
                            <td>{!! $spell->deity ? $spell->deity->name : '<span class="text-muted">Any</span>' !!}</td>
                            <td>{{ $spell->cost }}x</td>
                            <td>
                                @if($spell->cooldown > 0)
                                    {{ $spell->cooldown }} ticks
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
                    </tbody>
                </table>

                <h4 class="box-title">Self Impact Spells</h4>
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                        <col width="100">
                        <col width="50">
                        <col width="50">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Spell</th>
                            <th>Deity</th>
                            <th>Cost</th>
                            <th>Cooldown</th>
                            <th>Effect</th>
                        </tr>
                    <tbody>
                    @foreach ($spells as $spell)
                        @if($spell->class == 'active' and $spell->scope == 'self' and $spellHelper->isSpellAvailableToRace($race, $spell))
                        <tr>
                            <td>{{ $spell->name }}</td>
                            <td>{!! $spell->deity ? $spell->deity->name : '<span class="text-muted">Any</span>' !!}</td>
                            <td>{{ $spell->cost }}x</td>
                            <td>
                                @if($spell->cooldown > 0)
                                    {{ $spell->cooldown }} ticks
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
                    </tbody>
                </table>

                <h4 class="box-title">Invasion Spells</h4>
                <table class="table table-striped">
                    <colgroup>
                        <col width="200">
                        <col width="100">
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Spell</th>
                            <th>Deity</th>
                            <th>Effect</th>
                        </tr>
                    <tbody>
                    @foreach ($spells as $spell)
                        @if($spell->class == 'hostile' and $spell->scope == 'invasion' and $spellHelper->isSpellAvailableToRace($race, $spell))
                        <tr>
                            <td>{{ $spell->name }}</td>
                            <td>{!! $spell->deity ? $spell->deity->name : '<span class="text-muted">Any</span>' !!}</td>
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
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<div class="row">
    <a id="sabotage"></a>
    <div class="col-sm-12 col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Sabotage</h3>
            </div>
            <div class="box-body">
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
                    <tbody>
                    @foreach ($spyops as $spyop)
                        @if($spyop->scope == 'hostile' and $espionageCalculator->isSpyopAvailableToRace($race, $spyop))
                        <tr>
                            <td>
                                {{ $spyop->name }}
                            </td>
                            <td>
                                <ul>
                                    @foreach($espionageHelper->getSpyopEffectsString($spyop) as $effect)
                                        <li>{{ ucfirst($effect) }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <a id="chronicles"></a>
    <div class="col-sm-12 col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Chronicles</h3>
            </div>
            <div class="box-body">
              @php
                  $factionUrlName = str_replace(' ','-',strtolower($race->name));
                  $alignments = ['good' => 'commonwealth', 'evil' => 'empire', 'independent' => 'independent', 'npc' => 'barbarian-horde'];
                  $alignment = $alignments[$race->alignment];
              @endphp
              <p><a href="https://lounge.odarena.com/chronicles/factions/{{ $alignment }}/{{ $factionUrlName }}/" target="_blank"><i class="fa fa-book"></i> Read the history of {{ $race->name }} in the Chronicles.</a></p>

            </div>
        </div>
    </div>
</div>


</div>
@endsection
