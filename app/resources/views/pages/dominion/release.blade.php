@extends('layouts.master')
@section('title', 'Release')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-cycle"></i> Release Units</h3>
                </div>
                <form action="{{ route('dominion.military.release') }}" method="post" role="form">
                    @csrf
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="100">
                                <col width="100">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th class="text-center">Owned</th>
                                    <th class="text-center">Release</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getDrafteeHelpString($selectedDominion->race, $selectedDominion) }}">
                                            {{ $raceHelper->getDrafteesTerm($selectedDominion->race) }}:
                                        </span>
                                    </td>
                                    <td class="text-center">{{ number_format($selectedDominion->military_draftees) }}</td>
                                    <td class="text-center">
                                        <input type="number" name="release[draftees]" class="form-control text-center" placeholder="0" min="0" max="{{ $selectedDominion->military_draftees }}" value="{{ old('release.draftees') }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </td>
                                </tr>
                                @foreach ($unitHelper->getUnitTypes($selectedDominion->race) as $unitType)
                                    @if(
                                          ($unitType == 'spies' and $selectedDominion->race->getPerkValue('cannot_train_spies')) or
                                          ($unitType == 'wizards' and $selectedDominion->race->getPerkValue('cannot_train_wizards')) or
                                          ($unitType == 'archmages' and $selectedDominion->race->getPerkValue('cannot_train_archmages'))
                                      )
                                    @else
                                          <tr>
                                              <td>

                                                  <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'offense'), $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'defense'), ]) }}">
                                                      {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                                                  </span>
                                              </td>
                                              <td class="text-center">{{ number_format($selectedDominion->{'military_' . $unitType}) }}</td>
                                              <td class="text-center">
                                                  <input type="number" name="release[{{ $unitType }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $selectedDominion->{'military_' . $unitType} }}" value="{{ old('release.' . $unitType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                              </td>
                                          </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-danger" {{ $selectedDominion->isLocked() ? 'disabled' : null }} id="submit">Release</button>

                        <span class="pull-right">
                            <a href="{{ route('dominion.military') }}" class="btn btn-primary">Cancel</a>
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p><span class="label label-danger">Warning</span> You are about to <b>instantly and irrevokably</b> release your troops.</p>
                    <p>No resources spent on training units will be returned when you release units.</p>
                    <p>Released draftees return as peasants.</p>
                    <p>Units which cost draftees to train are returned as draftees.</p>
                    <p>Units which cost peasants to train are returned as peasants.</p>
                    <p><em>Units which do not count as population or which do not require neither draftees nor peasants to train are simply released with nothing in return.</em></p>
                </div>
            </div>
        </div>

    </div>
@endsection
@push('page-scripts')
    <script type="text/javascript">
    $("form").submit(function () {
        // prevent duplicate form submissions
        $(this).find(":submit").attr('disabled', 'disabled');
    });
    </script>
@endpush
