<div class="col-sm-12 col-md-12" id="units_returning">
    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="ra ra-boot-stomp"></i> Units returning</h3>
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
                        <th class="text-center"><br>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (range(1, $selectedDominion->race->units->count()) as $slot)
                        @php
                            $unitType = ('unit' . $slot)
                        @endphp
                        <tr>
                            <td>
                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race, [$militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'offense'), $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unitHelper->getUnitFromRaceUnitType($selectedDominion->race, $unitType), 'defense'), ]) }}">
                                    {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                                </span>
                            </td>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $unitTickAmount = $unitCalculator->getQueuedReturningUnitTypeAtTick($selectedDominion, $unitType, $i);
                                @endphp
                                <td class="text-center">
                                    {{ ($unitTickAmount > 0) ? number_format($unitTickAmount) : '-' }}
                                </td>
                            @endfor
                            <td class="text-center">
                                {{ number_format($unitCalculator->getUnitTypeTotalReturning($selectedDominion, $unitType)) }}
                            </td>
                        </tr>
                    @endforeach
                    @if(!$selectedDominion->race->getPerkValue('cannot_train_spies'))
                        <tr>
                            <td>Spies</td>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $unitTickAmount = $unitCalculator->getQueuedReturningUnitTypeAtTick($selectedDominion, 'spies', $i);
                                @endphp
                                <td class="text-center">
                                    {{ ($unitTickAmount > 0) ? number_format($unitTickAmount) : '-' }}
                                </td>
                            @endfor
                            <td class="text-center">
                                {{ number_format($unitCalculator->getUnitTypeTotalReturning($selectedDominion, 'spies')) }}
                            </td>
                        </tr>
                    @endif
                    @if(!$selectedDominion->race->getPerkValue('cannot_train_wizards'))
                        <tr>
                            <td>Wizards</td>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $unitTickAmount = $unitCalculator->getQueuedReturningUnitTypeAtTick($selectedDominion, 'wizards', $i);
                                @endphp
                                <td class="text-center">
                                    {{ ($unitTickAmount > 0) ? number_format($unitTickAmount) : '-' }}
                                </td>
                            @endfor
                            <td class="text-center">
                                {{ number_format($unitCalculator->getUnitTypeTotalReturning($selectedDominion, 'wizards')) }}
                            </td>
                        </tr>
                    @endif
                    @if(!$selectedDominion->race->getPerkValue('cannot_train_archmages'))
                        <tr>
                            <td>Archmages</td>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $unitTickAmount = $unitCalculator->getQueuedReturningUnitTypeAtTick($selectedDominion, 'archmages', $i);
                                @endphp
                                <td class="text-center">
                                    {{ ($unitTickAmount > 0) ? number_format($unitTickAmount) : '-' }}
                                </td>
                            @endfor
                            <td class="text-center">
                                {{ number_format($unitCalculator->getUnitTypeTotalReturning($selectedDominion, 'archmages')) }}
                            </td>
                        </tr>
                    @endif
                    @if(array_sum($returningResources) > 0)
                        <tr>
                            <th colspan="14">Resources and Other</th>
                        </tr>

                        @foreach($returningResources as $key => $totalAmount)
                            @if($key == 'artefacts' and is_array($totalAmount))
                                @foreach($totalAmount as $artefactKey => $tick)
                                    @php
                                        $artefact = OpenDominion\Models\Artefact::where('key', $artefactKey)->first();
                                    @endphp
                                    <tr>
                                        <td><span data-toggle="tooltip" data-placement="top" title="{{ $artefactHelper->getArtefactTooltip($artefact) }}">{{ $artefact->name }}</span></td>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <td class="text-center">
                                                {!! ($tick == $i) ? '<i class="ra ra-alien-fire"></i>' : '-' !!}
                                            </td>
                                        @endfor
                                        <td class="text-center">
                                            &nbsp;
                                        </td>
                                    </tr>
                                @endforeach
                            @elseif($totalAmount !== 0)
                                @php

                                    $name = 'undefined:'.$key;

                                    if(in_array($key, $selectedDominion->race->resources))
                                    {
                                        $name = OpenDominion\Models\Resource::where('key', $key)->first()->name;
                                        $key = 'resource_' . $key;
                                    }
                                    elseif($key == 'xp')
                                    {
                                        $name = 'XP';
                                    }
                                    elseif($key == 'prestige')
                                    {
                                        $name = 'Prestige';
                                    }

                                @endphp
                                <tr>
                                    <td>{{ $name }}</td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $resourceTickAmount = $queueService->getInvasionQueueAmount($selectedDominion, $key, $i);
                                            $resourceTickAmount += $queueService->getExpeditionQueueAmount($selectedDominion, $key, $i);
                                            $resourceTickAmount += $queueService->getTheftQueueAmount($selectedDominion, $key, $i);
                                            $resourceTickAmount += $queueService->getSabotageQueueAmount($selectedDominion, $key, $i);
                                            $resourceTickAmount += $queueService->getDesecrationQueueAmount($selectedDominion, $key, $i);
                                            $resourceTickAmount += $queueService->getStunQueueAmount($selectedDominion, $key, $i);
                                        @endphp
                                        <td class="text-center">
                                            {{ ($resourceTickAmount > 0) ? number_format($resourceTickAmount) : '-' }}
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        {{ number_format($totalAmount) }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>