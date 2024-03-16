@if (isset($selectedDominion) && !Route::is('dominion.status'))
    <div class="box">
        <div class="box-body">

            <div class="row">
                <div class="col-xs-2">
                    <div class="row">
                        <div class="col-lg-6"><b>Land:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->land) }}</div>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="row" data-toggle="tooltip" data-placement="top" title='<small class="text-muted">Employment:</small> {{ number_format($populationCalculator->getEmploymentPercentage($selectedDominion), 2) }}%''>
                        <div class="col-lg-6"><b>{{ Str::plural($raceHelper->getPeasantsTerm($selectedDominion->race)) }}:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->peasants) }}</div>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="row">
                      <div class="col-lg-6"><b>Networth:</b></div>
                      <div class="col-lg-6">{{ number_format($networthCalculator->getDominionNetworth($selectedDominion)) }}</div>
                  </div>
                </div>
                <div class="col-xs-2">
                    @php
                        $dpFromUnitsWithoutSufficientResources = $militaryCalculator->dpFromUnitWithoutSufficientResources($selectedDominion);
                    @endphp
                    <div class="row" data-toggle="tooltip" data-placement="top" title='<small class="text-muted">Raw:</small> {{ number_format($militaryCalculator->getDefensivePowerRaw($selectedDominion)) }}<br><small class="text-muted">Mods:</small> {{ number_format(($militaryCalculator->getDefensivePowerMultiplier($selectedDominion)-1)*100,2) }}%<br> @if($dpFromUnitsWithoutSufficientResources)<span class='text-red'><b>{{ number_format($dpFromUnitsWithoutSufficientResources) }} raw DP</b> unavailable due to insufficient resources!</span> @endif'>
                        <div class="col-lg-6"><b>DP:</b></div>
                        <div class="col-lg-6">
                            @if($dpFromUnitsWithoutSufficientResources)
                                <b class="text-red">{{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }}</b>
                            @else
                                {{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xs-2">
                    <div class="row" data-toggle="tooltip" data-placement="top" title='<small class="text-muted">XP:</small> {{ number_format($productionCalculator->getXpGeneration($selectedDominion)) }}/tick'>
                        <div class="col-lg-6"><b>XP:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->xp) }}</div>
                    </div>
                </div>

                <div class="col-xs-2">
                    <div class="row">
                        <div class="col-lg-6"><b>Morale:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->morale) }}</div>
                    </div>
                </div>
            </div>

            <div class="row">
                @foreach ($selectedDominion->race->resources as $resourceKey)
                    @php
                        $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                    @endphp
                    <div class="col-xs-2" data-toggle="tooltip" data-placement="top" title='<small class="text-muted">{{ $resource->name }}:</small> {{ number_format($resourceCalculator->getProduction($selectedDominion, $resourceKey) - $resourceCalculator->getConsumption($selectedDominion, $resourceKey)) }}/tick'>
                        <div class="row">
                            <div class="col-lg-6">
                                <b>{{ $resource->name }}:</b>
                            </div>
                            <div class="col-lg-6">
                                {{ number_format($resourceCalculator->getAmount($selectedDominion, $resourceKey)) }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($dominionProtectionService->canTick($selectedDominion))
                <div class="row">
                    <div class="col-xs-12">
                        <form action="{{ route('dominion.status') }}" method="post" role="form" id="tick_form">
                        @csrf
                        <input type="hidden" name="returnTo" value="{{ Route::currentRouteName() }}">

                        <select class="btn btn-warning" name="ticks">
                            @for ($i = 1; $i <= min(24, $selectedDominion->protection_ticks); $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>

                        <button type="submit"
                                class="btn btn-primary"
                                {{ $selectedDominion->isLocked() ? 'disabled' : null }}
                                id="tick-button">
                            <i class="ra ra-shield"></i>
                            Proceed tick(s) ({{ $selectedDominion->protection_ticks }} {{ Str::plural('tick', $selectedDominion->protection_ticks) }} left)
                        </button>
                    </form>
                    </div>
                </div>
            @endif

        </div>
    </div>

@endif
