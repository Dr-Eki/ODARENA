@php
    $wizardPointsOffense = $magicCalculator->getWizardPoints($selectedDominion, 'offense');
    $wizardPointsDefense = $magicCalculator->getWizardPoints($selectedDominion, 'defense');
    $wizardPointsRequiredByAllUnits = $magicCalculator->getWizardPointsRequiredByAllUnits($selectedDominion);
    $wizardPointsUsed = $magicCalculator->getWizardPointsUsed($selectedDominion, 2);
    $wizardPointsRemaining = $magicCalculator->getWizardPointsRemaining($selectedDominion, 2);

    $hasEnoughWizardPoints = $wizardPointsOffense >= $wizardPointsRequiredByAllUnits;

@endphp

<div class="box box-info">
    <div class="box-body no-padding">
        <div class="row">
            <div class="col-md-6">
                <table class="table">
                    <tbody>
                        <tr>
                            <td>Wizard Points (Offense):</td>
                            <td>{{ number_format($wizardPointsOffense,2) }}</td>
                        </tr>
                        <tr>
                            <td>Wizard Points (Defense):</td>
                            <td>{{ number_format($wizardPointsDefense,2) }}</td>
                        </tr>
                        <tr>
                            <td>Wizard Points Required:</td>
                            <td>{{ number_format($wizardPointsRequiredByAllUnits,2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>Wizard Points Used:</td>
                            <td>{{ number_format($wizardPointsUsed,2) }}</td>
                        </tr>
                        <tr>
                            <td>Wizard Points Remaining:</td>
                            <td>
                                <span class="{{ $hasEnoughWizardPoints ? 'text-green' : 'text-red' }}">
                                    {{ number_format($wizardPointsRemaining,2) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>


            <div class="col-md-6">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Wizard Points Required</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($selectedDominion->race->units as $unit)
                            @if(!$unit->getPerkValue('wizard_points_required'))
                                @continue
                            @endif
        
                            <tr>
                                <td>{{ $unit->name }}</td>
                                <td>{{ number_format($magicCalculator->getWizardPointsRequiredByUnitTotal($selectedDominion, $unit->slot),2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>