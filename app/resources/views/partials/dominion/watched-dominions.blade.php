@if($selectedDominion->isWatchingAnyDominion())
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fas fa-eye"></i> Watched Dominions</h3>
        </div>
            <div class="box-body table-responsive no-padding">
                <table class="table">
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Dominion</th>
                            <th>Land</th>
                            <th>DP</th>
                            <th>Units<br>Returning</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach($selectedDominion->watchedDominions()->get() as $watchedDominion)
                            @php
                            $dpMultiplierReduction = $militaryCalculator->getDefensiveMultiplierReduction($selectedDominion);
                            $landRatio = $watchedDominion->land / $selectedDominion->land;

                            $dominionDP = $militaryCalculator->getDefensivePower(
                                $watchedDominion,
                                $selectedDominion,
                                $landRatio,
                                null,
                                $dpMultiplierReduction,
                                false, # Ignore ambush
                                false,
                                [], # No $invadingUnits  
                            );

                            $dominionFogged = ($watchedDominion->getSpellPerkValue('fog_of_war') == 1);

                        @endphp
                            <tr>
                                <td><a href="{{ route('dominion.insight.show', $watchedDominion) }}">{{ $watchedDominion->name }}</a> <a href="{{ route('dominion.realm', [$watchedDominion->realm->number]) }}">(# {{ $watchedDominion->realm->number }})</a></td>
                                <td>{{ number_format($watchedDominion->land) }} <small class="text-muted">({{ number_format($landRatio * 100, 2) }}%)</small></td>
                                <td>{!! $dominionFogged ? '<span class="label label-default">Fog</span>' : number_format($dominionDP) . ' *' !!}</td>
                                <td>
                                    @if ($militaryCalculator->hasReturningUnits($watchedDominion))
                                        <span class="label label-success">Yes</span>
                                    @else
                                        <span class="text-gray">No</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4">
                                <a href="{{ route('dominion.insight.watched-dominions') }}">View all watched dominions</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="box-footer">
                    <small class="text-muted">* DP is calculated with your target defensive modifiers reductions ({{ number_format($dpMultiplierReduction/100,2) }}%). The defensive power shown is with the basic, static perks and do not take into account circumstantial perks such as perks vs. specific types of targets or perks based on specific unit compositions.</small>
                </div>
            </div>
    </div>
@endif
