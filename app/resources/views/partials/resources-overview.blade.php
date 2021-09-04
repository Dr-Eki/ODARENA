@if (isset($selectedDominion) && !Route::is('dominion.status'))
    <div class="box">
        <div class="box-body">

            <div class="row">
                <div class="col-xs-2">
                    <div class="row">
                        <div class="col-lg-6"><b>Land:</b></div>
                        <div class="col-lg-6">{{ number_format($landCalculator->getTotalLand($selectedDominion)) }}</div>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="row">
                      <div class="col-lg-6"><b>
                        {{ str_plural($raceHelper->getPeasantsTerm($selectedDominion->race)) }}:</td>
                        </div>
                        </b>
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
                    <div class="row">
                        <div class="col-lg-6"><b>DP:</b></div>
                        <div class="col-lg-6">{{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }}</div>
                    </div>
                </div>

                <div class="col-xs-2">
                    <div class="row">
                        <div class="col-lg-6"><b>XP:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->xp) }}</div>
                    </div>
                </div>
            </div>

            <div class="row">
                @foreach ($selectedDominion->race->resources as $resourceKey)
                    @php
                        $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                    @endphp
                    <div class="col-xs-2">
                        <div class="row">
                            <div class="col-lg-6">
                                <span data-toggle="tooltip" data-placement="top" title="{{ number_format($resourceCalculator->getProduction($selectedDominion, $resourceKey) - $resourceCalculator->getConsumption($selectedDominion, $resourceKey)) . ' ' . $resource->name }}/tick">
                                    <b>{{ $resource->name }}:</b>
                                </span>
                            </div>
                            <div class="col-lg-6">
                                <span data-toggle="tooltip" data-placement="top" title="{{ number_format($resourceCalculator->getProduction($selectedDominion, $resourceKey) - $resourceCalculator->getConsumption($selectedDominion, $resourceKey)) . ' ' . $resource->name }}/tick">
                                    {{ number_format($resourceCalculator->getAmount($selectedDominion, $resourceKey)) }}
                                </span>
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
                        @for ($i = 1; $i <= $selectedDominion->protection_ticks; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>

                    <button type="submit"
                            class="btn btn-info"
                            {{ $selectedDominion->isLocked() ? 'disabled' : null }}
                            id="tick-button">
                        <i class="ra ra-shield"></i>
                        Proceed tick(s) ({{ $selectedDominion->protection_ticks }} {{ str_plural('tick', $selectedDominion->protection_ticks) }} left)
                    </button>
                  </form>
                </div>
            </div>
            @endif

        </div>
    </div>

@endif
