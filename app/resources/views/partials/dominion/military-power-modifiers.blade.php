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
                        <td>{{ number_format($selectedDominion->getImprovementPerkValue('offensive_power'), 2) }}%</td>
                        <td>{{ number_format($selectedDominion->getImprovementPerkValue('defensive_power'), 2) }}%</td>
                    </tr>
                    @if(isset($selectedDominion->race->land_improvements))
                    <tr>
                        <td>Land:</td>
                        <td>{{ number_format($selectedDominion->getLandImprovementsPerkMultiplier('offensive_power_mod') * 100, 2) }}%</td>
                        <td>{{ number_format($selectedDominion->getLandImprovementsPerkMultiplier('defensive_power_mod') * 100, 2) }}%</td>
                    </tr>
                    @endif
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
                        <td>Deity:</td>
                        <td>{{ number_format($selectedDominion->getDeityPerkMultiplier('offensive_power') * 100, 2) }}%</td>
                        <td>{{ number_format($selectedDominion->getDeityPerkMultiplier('defensive_power') * 100, 2) }}%</td>
                    </tr>
                    @if($militaryCalculator->getRawDefenseAmbushReductionRatio($selectedDominion))
                    <tr>
                        <td>Ambush:</td>
                        <td colspan="2">-{{ number_format($militaryCalculator->getRawDefenseAmbushReductionRatio($selectedDominion) * 100, 2) }}% target raw DP</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
</div>
