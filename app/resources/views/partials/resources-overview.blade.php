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
                        <div class="col-lg-6"><b>Gold:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->resource_gold) }}</div>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="row">
                        <div class="col-lg-6"><b>Food:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->resource_food) }}</div>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="row">
                        <div class="col-lg-6"><b>Ore:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->resource_ore) }}</div>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="row">
                        <div class="col-lg-6"><b>XP:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->resource_tech) }}</div>
                    </div>
                </div>
            </div>

            <div class="row">
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
                        <div class="col-lg-6"><b>Lumber:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->resource_lumber) }}</div>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="row">
                        <div class="col-lg-6"><b>Mana:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->resource_mana) }}</div>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="row">
                        <div class="col-lg-6"><b>Gems:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->resource_gems) }}</div>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="row">
                        <div class="col-lg-6"></div>
                        <div class="col-lg-6"></div>
                    </div>
                </div>
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
