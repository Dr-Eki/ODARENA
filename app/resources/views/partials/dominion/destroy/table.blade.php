<table class="table">
    <colgroup>
        <col>
        <col width="100">
        <col width="100">
    </colgroup>

    @foreach ($landTypesBuildingTypes as $landType => $buildingTypes)

        @if (empty($buildingTypes))
            @continue
        @endif

        <thead>
            <tr>
                <th colspan="3">
                    <span class="pull-right barren-land">Barren: <strong>{{ number_format($landCalculator->getTotalBarrenLandByLandType($selectedDominion, $landType)) }}</strong></span>
                    <h4>{{ ucfirst($landType) }}</h4>
                </th>
            </tr>
            <tr>
                <th>Building</th>
                <th class="text-center">Owned</th>
                <th class="text-center">Destroy</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($buildingTypes as $buildingType)
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $buildingHelper->getBuildingHelpString($buildingType) }}">
                            {{ ucwords(str_replace('_', ' ', $buildingType)) }}
                        </span>
                    </td>
                    <td class="text-center">
                        {{ $selectedDominion->{'building_' . $buildingType} }}
                        <small>
                            ({{ number_format((($selectedDominion->{'building_' . $buildingType} / $landCalculator->getTotalLand($selectedDominion)) * 100), 2) }}%)
                        </small>
                    </td>
                    <td class="text-center">
                        <input type="number" name="destroy[{{ $buildingType }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $selectedDominion->{'building_' . $buildingType} }}" value="{{ old('destroy.' . $buildingType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                    </td>
                </tr>
            @endforeach
        </tbody>

    @endforeach

</table>
