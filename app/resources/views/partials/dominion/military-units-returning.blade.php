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
                                    $unitTickAmount = $queueService->getInvasionQueueAmount($selectedDominion, "military_{$unitType}", $i);
                                    $unitTickAmount += $queueService->getExpeditionQueueAmount($selectedDominion, "military_{$unitType}", $i);
                                    $unitTickAmount += $queueService->getTheftQueueAmount($selectedDominion, "military_{$unitType}", $i);
                                    $unitTickAmount += $queueService->getSabotageQueueAmount($selectedDominion, "military_{$unitType}", $i);
                                    $unitTickAmount += $queueService->getDesecrationQueueAmount($selectedDominion, "military_{$unitType}", $i);
                                    $unitTickAmount += $queueService->getStunQueueAmount($selectedDominion, "military_{$unitType}", $i);
                                    $unitTickAmount += $queueService->getArtefactQueueAmount($selectedDominion, "military_{$unitType}", $i);
                                @endphp
                                <td class="text-center">
                                    {{ ($unitTickAmount > 0) ? number_format($unitTickAmount) : '-' }}
                                </td>
                            @endfor
                            <td class="text-center">
                                {{ number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, "military_{$unitType}") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "military_{$unitType}") + $queueService->getTheftQueueAmount($selectedDominion, "military_{$unitType}", $i) + $queueService->getSabotageQueueAmount($selectedDominion, "military_{$unitType}", $i) + $queueService->getDesecrationQueueAmount($selectedDominion, "military_{$unitType}", $i) + $queueService->getStunQueueAmount($selectedDominion, "military_{$unitType}", $i) + $queueService->getArtefactQueueAmount($selectedDominion, "military_{$unitType}", $i)) }}
                            </td>
                        </tr>
                    @endforeach
                    @if(!$selectedDominion->race->getPerkValue('cannot_train_spies'))
                        <tr>
                            <td>Spies</td>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $spiesTickAmount = $queueService->getInvasionQueueAmount($selectedDominion, "military_spies", $i);
                                    $spiesTickAmount += $queueService->getExpeditionQueueAmount($selectedDominion, "military_spies", $i);
                                    $spiesTickAmount += $queueService->getTheftQueueAmount($selectedDominion, "military_spies", $i);
                                    $spiesTickAmount += $queueService->getSabotageQueueAmount($selectedDominion, "military_spies", $i);
                                    $spiesTickAmount += $queueService->getDesecrationQueueAmount($selectedDominion, "military_spies", $i);
                                    $spiesTickAmount += $queueService->getStunQueueAmount($selectedDominion, "military_spies", $i);
                                    $spiesTickAmount += $queueService->getArtefactQueueAmount($selectedDominion, "military_spies", $i);
                                @endphp
                                <td class="text-center">
                                    {{ ($spiesTickAmount > 0) ? number_format($spiesTickAmount) : '-' }}
                                </td>
                            @endfor
                            <td class="text-center">
                                {{ number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, "military_spies") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "military_spies") + $queueService->getTheftQueueAmount($selectedDominion, "military_spies", $i) + $queueService->getSabotageQueueAmount($selectedDominion, "military_spies", $i) + $queueService->getDesecrationQueueAmount($selectedDominion, "military_spies", $i) + $queueService->getStunQueueAmount($selectedDominion, "military_spies", $i) + $queueService->getArtefactQueueAmount($selectedDominion, "military_spies", $i)) }}
                            </td>
                        </tr>
                    @endif
                    @if(!$selectedDominion->race->getPerkValue('cannot_train_wizards'))
                        <tr>
                            <td>Wizards</td>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $wizardsTickAmount = $queueService->getInvasionQueueAmount($selectedDominion, "military_wizards", $i);
                                    $wizardsTickAmount += $queueService->getExpeditionQueueAmount($selectedDominion, "military_wizards", $i);
                                    $wizardsTickAmount += $queueService->getTheftQueueAmount($selectedDominion, "military_wizards", $i);
                                    $wizardsTickAmount += $queueService->getSabotageQueueAmount($selectedDominion, "military_wizards", $i);
                                    $wizardsTickAmount += $queueService->getDesecrationQueueAmount($selectedDominion, "military_wizards", $i);
                                    $wizardsTickAmount += $queueService->getStunQueueAmount($selectedDominion, "military_wizards", $i);
                                    $wizardsTickAmount += $queueService->getArtefactQueueAmount($selectedDominion, "military_wizards", $i);
                                @endphp
                                <td class="text-center">
                                    {{ ($wizardsTickAmount > 0) ? number_format($wizardsTickAmount) : '-' }}
                                </td>
                            @endfor
                            <td class="text-center">
                                {{ number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, "military_wizards") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "military_wizards") + $queueService->getTheftQueueAmount($selectedDominion, "military_wizards", $i) + $queueService->getSabotageQueueAmount($selectedDominion, "military_wizards", $i) + $queueService->getDesecrationQueueAmount($selectedDominion, "military_wizards", $i) + $queueService->getStunQueueAmount($selectedDominion, "military_wizards", $i) + $queueService->getArtefactQueueAmount($selectedDominion, "military_wizards", $i)) }}
                            </td>
                        </tr>
                    @endif
                    @if(!$selectedDominion->race->getPerkValue('cannot_train_archmages'))
                        <tr>
                            <td>Archmages</td>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $archmagesTickAmount = $queueService->getInvasionQueueAmount($selectedDominion, "military_archmages", $i);
                                    $archmagesTickAmount += $queueService->getExpeditionQueueAmount($selectedDominion, "military_archmages", $i);
                                    $archmagesTickAmount += $queueService->getTheftQueueAmount($selectedDominion, "military_archmages", $i);
                                    $archmagesTickAmount += $queueService->getSabotageQueueAmount($selectedDominion, "military_archmages", $i);
                                    $archmagesTickAmount += $queueService->getDesecrationQueueAmount($selectedDominion, "military_archmages", $i);
                                    $archmagesTickAmount += $queueService->getStunQueueAmount($selectedDominion, "military_archmages", $i);
                                    $archmagesTickAmount += $queueService->getArtefactQueueAmount($selectedDominion, "military_archmages", $i);
                                @endphp
                                <td class="text-center">
                                    {{ ($archmagesTickAmount > 0) ? number_format($archmagesTickAmount) : '-' }}
                                </td>
                            @endfor
                            <td class="text-center">
                                {{ number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, "military_archmages") + $queueService->getExpeditionQueueTotalByResource($selectedDominion, "military_archmages") + $queueService->getTheftQueueAmount($selectedDominion, "military_archmages", $i) + $queueService->getSabotageQueueAmount($selectedDominion, "military_archmages", $i) + $queueService->getDesecrationQueueAmount($selectedDominion, "military_archmages", $i) + $queueService->getStunQueueAmount($selectedDominion, "military_archmages", $i) + $queueService->getArtefactQueueAmount($selectedDominion, "military_archmages", $i)) }}
                            </td>
                        </tr>
                    @endif
                    @if(array_sum($returningResources) > 0)
                        <tr>
                            <th colspan="14">Resources and Other</th>
                        </tr>

                        @foreach($returningResources as $key => $totalAmount)
                            @if($totalAmount !== 0)
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