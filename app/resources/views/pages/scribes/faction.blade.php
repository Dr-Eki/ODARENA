@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
<div class="row">

    <div class="col-sm-12 col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">{{ $race->name }}</h2>

                <span class="pull-right">
                    Skill level:
                    @if($race->skill_level === 1)
                        <span class="label label-success">Beginner</span>
                    @elseif($race->skill_level === 2)
                        <span class="label label-warning">Intermediate</span>
                    @elseif($race->skill_level === 3)
                        <span class="label label-danger">Advanced</span>
                    @endif

                    |

                    Attacking:
                    @if($race->attacking === 0)
                        <i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i>
                    @elseif($race->attacking === 1)
                        <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i>
                    @elseif($race->attacking === 2)
                        <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i><i class="fa fa-star text-gray"></i>
                    @elseif($race->attacking === 3)
                        <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i>
                    @endif

                    |

                    Converting:
                    @if($race->converting === 0)
                        <i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i>
                    @elseif($race->converting === 1)
                        <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i>
                    @elseif($race->converting === 2)
                        <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i><i class="fa fa-star text-gray"></i>
                    @elseif($race->converting === 3)
                        <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i>
                    @endif

                    |

                    Exploring:
                    @if($race->exploring === 0)
                        <i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i>
                    @elseif($race->exploring === 1)
                        <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i>
                    @elseif($race->exploring === 2)
                        <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i><i class="fa fa-star text-gray"></i>
                    @elseif($race->exploring === 3)
                        <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i>
                    @endif

                </span>
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
                        <col>
                        <col width="150">
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
                              {{ dd($unit) }}
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
                                @if (in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4']))
                                    <td class="text-center">  <!-- OP / DP -->
                                        {{ $unit->power_offense }} / {{ $unit->power_defense }}
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
                                    @php
                                        $unitCostString = (number_format($unit->cost_gold) . ' gold');

                                        if ($unit->cost_ore > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_ore) . ' ore');
                                        }

                                        if ($unit->cost_lumber > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_lumber) . ' lumber');
                                        }

                                        if ($unit->cost_food > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_food) . ' food');
                                        }

                                        if ($unit->cost_mana > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_mana) . ' mana');
                                        }

                                        if ($unit->cost_gem > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_gem) . ' gem');
                                        }

                                        if ($unit->cost_prestige > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_prestige) . ' Prestige');
                                        }

                                        if ($unit->cost_boat > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_boat) . ' boat');
                                        }

                                        if ($unit->cost_champion > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_champion) . ' Champion');
                                        }

                                        if ($unit->cost_soul > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_soul) . ' Soul');
                                        }

                                        if ($unit->cost_blood > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_blood) . ' blood');
                                        }

                                        if ($unit->cost_unit1 > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_unit1) . ' ' . $unitHelper->getUnitName('unit1', $race));
                                        }

                                        if ($unit->cost_unit2 > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_unit2) . ' ' . $unitHelper->getUnitName('unit2', $race));
                                        }

                                        if ($unit->cost_unit3 > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_unit3) . ' ' . $unitHelper->getUnitName('unit3', $race));
                                        }

                                        if ($unit->cost_unit4 > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_unit4) . ' ' . $unitHelper->getUnitName('unit4', $race));
                                        }

                                        if ($unit->cost_morale !== 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_morale) . '% morale');
                                        }

                                        if ($unit->cost_peasant > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_peasant) . ' peasant');
                                        }

                                        if ($unit->cost_wild_yeti > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_wild_yeti) . '  wild yeti');
                                        }

                                        if ($unit->cost_spy > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_spy) . '  Spy');
                                        }

                                        if ($unit->cost_wizard > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_wizard) . '  Wizard');
                                        }

                                        if ($unit->cost_archmage > 0) {
                                            $unitCostString .= (', ' . number_format($unit->cost_archmage) . '  ArchMage');
                                        }

                                    @endphp
                                    {{ $unitCostString }}
                              </td>
                          </tr>
                      @endforeach

                    </tbody>
                </table>
            </div>
        </div>
      </div>

      <div class="col-sm-12 col-md-3 no-padding">
          <div class="col-sm-12 col-md-12">
              <div class="box">
                  <div class="box-header with-border">
                      <h3 class="box-title">Perks</h3>
                  </div>
                  <div class="box-body table-responsive no-padding">
                      <table class="table table-striped">
                          <colgroup>
                              <col>
                              <col>
                          </colgroup>
                          <tbody>
                              @foreach ($race->perks->sort() as $perk)
                                  @php
                                      $perkDescription = $raceHelper->getPerkDescriptionHtmlWithValue($perk);
                                  @endphp
                                  <tr>
                                      <td>
                                          {!! $perkDescription['description'] !!}
                                      </td>
                                      <td>
                                          {!! $perkDescription['value']  !!}
                                      </td>
                                  </tr>
                              @endforeach
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>
          <div class="col-sm-12 col-md-12">
              <div class="box">
                  <div class="box-header with-border">
                      <h3 class="box-title">Lands</h3>
                  </div>
                  <div class="box-body table-responsive no-padding">
                      <table class="table table-striped">
                          <colgroup>
                              <col>
                              <col>
                          </colgroup>
                          <tbody>
                              <tr>
                                  <td>Home land type:</td>
                                  <td>{!! $landHelper->getLandTypeIconHtml($race->home_land_type) !!} {{ ucwords($race->home_land_type) }}<td>
                              </tr>
                              @php
                                  $constructionMaterials = $raceHelper->getConstructionMaterials($race);
                                  $materials = count($constructionMaterials);
                              @endphp

                                  <tr>
                                      <td colspan="3" class="text-center"><b>Construction Materials</b></th>
                                  </tr>

                              @if($race->getPerkValue('cannot_construct'))
                                  <tr>
                                      <td colspan="3" class="text-center">Cannot construct buildings.</td>
                                  </tr>
                              @elseif($materials === 1)
                                  <tr>
                                      <td>Resource:</td>
                                      <td>{{ ucwords($constructionMaterials[0]) }}<td>
                                  </tr>
                              @else
                                  <tr>
                                      <td>Primary:</td>
                                      <td>{{ ucwords($constructionMaterials[0]) }}<td>
                                  </tr>
                                  <tr>
                                      <td>Secondary:</td>
                                      <td>{{ ucwords($constructionMaterials[1]) }}<td>
                                  </tr>
                              @endif
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>
      </div>
</div>

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

<div class="row">
    <div class="col-sm-12 col-md-5">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Spells</h3>
            </div>
            <div class="box-body">
                <p>See <a href="{{ route('scribes.spells', str_slug($race['name'])) }}">Spells</a>.</p>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-4">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Spy Ops</h3>
            </div>
            <div class="box-body">
                <p>See <a href="{{ route('scribes.spy-ops', str_slug($race['name'])) }}">Spy Ops</a>.</p>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
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
