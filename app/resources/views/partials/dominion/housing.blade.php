<tr>
    <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by barren land:">Barren housing:</span></td>
    <td>{{ number_format($populationCalculator->getBarrenHousing($selectedDominion)) }}</td>
</tr>

<tr>
    <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by buildings under construction:">Construction housing:</span></td>
    <td>{{ number_format($populationCalculator->getConstructionHousing($selectedDominion)) }}</td>
</tr>

@if($populationCalculator->getAvailableHousingFromWizardHousing($selectedDominion) > 0)
<tr>
    <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by Wizard Guilds or other buildings that house wizard units:<br>Filled / Available">Wizard housing:</span></td>
    <td>{{ number_format($populationCalculator->getUnitsHousedInWizardHousing($selectedDominion)) }} / {{ number_format($populationCalculator->getAvailableHousingFromWizardHousing($selectedDominion)) }}</td>
</tr>
@endif

@if($populationCalculator->getAvailableHousingFromSpyHousing($selectedDominion) > 0)
<tr>
    <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by Forest Havens or other buildings that house spy units:<br>Filled / Available">Spy housing:</span></td>
    <td>{{ number_format($populationCalculator->getUnitsHousedInSpyHousing($selectedDominion)) }} / {{ number_format($populationCalculator->getAvailableHousingFromSpyHousing($selectedDominion)) }}</td>
</tr>
@endif

@if($populationCalculator->getAvailableHousingFromUnitSpecificBuildings($selectedDominion))
    @foreach($populationCalculator->getAvailableHousingFromUnitSpecificBuildings($selectedDominion, null, true) as $slot => $amount)
        @php
            $unit = $selectedDominion->race->units->firstWhere('slot', $slot);
        @endphp
        <tr>
            <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by buildings for specific unit:<br>Filled / Available">{{ $unit->name }} housing:</span></td>
            <td>{{ number_format($populationCalculator->getUnitsHousedInUnitSpecificBuildings($selectedDominion, $unit->slot)) }} / {{ number_format($amount) }}</td>
        </tr>
    @endforeach
@endif


@if($populationCalculator->getAvailableHousingFromUnitAttributeSpecificBuildings($selectedDominion) > 0)
<tr>
    <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by buildings for specific types of units (attributes):<br>Filled / Available">Unit type specific housing:</span></td>
    <td>{{ number_format($populationCalculator->getUnitsHousedInUnitAttributeSpecificBuildings($selectedDominion)) }} / {{ number_format($populationCalculator->getAvailableHousingFromUnitAttributeSpecificBuildings($selectedDominion)) }}</td>
</tr>
@endif

@if($populationCalculator->getAvailableHousingFromDrafteeSpecificBuildings($selectedDominion) > 0)
<tr>
    <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by buildings for {{ str_plural($raceHelper->getDrafteesTerm($selectedDominion->race)) }}:<br>Filled / Available">{{ $raceHelper->getDrafteesTerm($selectedDominion->race) }} housing:</span></td>
    <td>{{ number_format($populationCalculator->getDrafteesHousedInDrafteeSpecificBuildings($selectedDominion)) }} / {{ number_format($populationCalculator->getAvailableHousingFromDrafteeSpecificBuildings($selectedDominion)) }}</td>
</tr>
@endif


@if($populationCalculator->getAvailableHousingFromMilitaryHousing($selectedDominion) > 0)
<tr>
    <td><span data-toggle="tooltip" data-placement="top" title="Housing provided by barracks or other buildings and units that military units:<br>Filled / Available">Military housing:</span></td>
    <td>{{ number_format($populationCalculator->getUnitsHousedInMilitaryHousing($selectedDominion)) }} / {{ number_format($populationCalculator->getAvailableHousingFromMilitaryHousing($selectedDominion)) }}</td>
</tr>
@endif
