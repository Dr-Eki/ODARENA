
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="ra ra-sword"></i> Units incoming and at home</h3>
    </div>
    <div class="box-body table-responsive no-padding">
        <table class="table">
            <colgroup>
                <col>
                @for ($i = 1; $i <= 12; $i++)
                    <col width="100">
                @endfor
                <col width="100">
            </colgroup>
            <thead>
                <tr>
                    <th>Unit</th>
                    @for ($i = 1; $i <= 12; $i++)
                        <th class="text-center">{{ $i }}</th>
                    @endfor
                    <th class="text-center">Home<br>(Incoming)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($unitHelper->getUnitTypes($selectedDominion->race) as $unitType)
                    <tr>
                        <td>

                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'offense'), $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'defense'), ]) }}">
                                {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                            </span>
                        </td>
                        @for ($i = 1; $i <= 12; $i++)
                            <td class="text-center">
                                @php
                                    $incomingAmount = $unitCalculator->getQueuedIncomingUnitTypeAtTick($selectedDominion, $unitType, $i);
                                @endphp
                                @if ($incomingAmount)
                                    {{ number_format($incomingAmount) }}
                                @else
                                    -
                                @endif
                            </td>
                        @endfor
                        <td class="text-center">
                            {{ number_format($selectedDominion->{'military_' . $unitType}) }}
                            ({{ number_format($unitCalculator->getUnitTypeTotalIncoming($selectedDominion, $unitType)) }})
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
