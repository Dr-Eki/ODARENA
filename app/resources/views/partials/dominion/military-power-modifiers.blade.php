<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="ra ra-axe"></i> Military Power Modifiers</h3>
    </div>
        <div class="box-body table-responsive no-padding">
            <table class="table">
                <colgroup>
                    <col width="33%">
                    <col width="33%">
                    <col width="33%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Modifier</th>
                        <th>Offensive</th>
                        <th>Defensive</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Total:</strong></td>
                        <td><strong>{{ number_format(($militaryCalculator->getOffensivePowerMultiplier($selectedDominion) - 1) * 100, 2) }}%</strong></td>
                        <td><strong>{{ number_format(($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) - 1) * 100, 2) }}%</strong></td>
                    </tr>
                    <tr>
                        <td>Prestige:</td>
                        <td>{{ number_format($prestigeCalculator->getPrestigeMultiplier($selectedDominion) * 100, 2) }}%</td>
                        <td>&mdash;</td>
                    </tr>
                    <tr>
                        <td>Improvements:</td>
                        <td>
                            @if($selectedDominion->race->getPerkValue('land_improvements'))
                                {{ number_format($landImprovementCalculator->getOffensivePowerBonus($selectedDominion) * 100, 2) }}%
                            @else
                                {{ number_format($improvementCalculator->getImprovementMultiplierBonus($selectedDominion, 'forges') * 100, 2) }}%
                            @endif
                        </td>
                        <td>
                            @if($selectedDominion->race->getPerkValue('land_improvements'))
                                {{ number_format($landImprovementCalculator->getDefensivePowerBonus($selectedDominion) * 100, 2) }}%
                            @else
                                {{ number_format($improvementCalculator->getImprovementMultiplierBonus($selectedDominion, 'walls') * 100, 2) }}%
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Advancements:</td>
                        <td>{{ number_format($selectedDominion->getTechPerkMultiplier('offense') * 100, 2) }}%</td>
                        <td>{{ number_format($selectedDominion->getTechPerkMultiplier('defense') * 100, 2) }}%</td>
                    </tr>
                    <tr>
                        <td>Spell:</td>
                        <td>{{ number_format($militaryCalculator->getSpellMultiplier($selectedDominion, null, 'offense') * 100, 2) }}%</td>
                        <td>{{ number_format($militaryCalculator->getSpellMultiplier($selectedDominion, null, 'defense') * 100, 2) }}%</td>
                    </tr>
                    <tr>
                        <td>Buildings:</td>
                        <td>{{ number_format($selectedDominion->getBuildingPerkMultiplier('offensive_power') * 100, 2) }}%</td>
                        <td>{{ number_format($selectedDominion->getBuildingPerkMultiplier('defensive_power') * 100, 2) }}%</td>
                    </tr>
                    <tr>
                        <td>Faction bonus:</td>
                        <td>{{ number_format($selectedDominion->race->getPerkMultiplier('offense') * 100, 2) }}%</td>
                        <td>{{ number_format($selectedDominion->race->getPerkMultiplier('defense') * 100, 2) }}%</td>
                    </tr>
                    <tr>
                        <td>League:</td>
                        <td>{{ number_format($militaryCalculator->getLeagueMultiplier($selectedDominion, null, 'offense') * 100, 2) }}%</td>
                        <td>{{ number_format($militaryCalculator->getLeagueMultiplier($selectedDominion, null, 'defense') * 100, 2) }}%</td>
                    </tr>
                    @if($selectedDominion->getSpellPerkValue('reduces_target_raw_defense_from_land'))
                    <tr>
                        <td>Ambush:</td>
                        <td colspan="2">-{{ number_format($militaryCalculator->getRawDefenseAmbushReductionRatio($selectedDominion) * 100, 2) }}% target raw DP</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
</div>
