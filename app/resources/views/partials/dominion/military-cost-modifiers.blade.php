<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="ra ra-anvil"></i> Training Cost Modifiers</h3>
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
                        <th>Resource</th>
                        <th>Modifier</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Gold:</td>
                        <td>{{ number_format(($trainingCalculator->getSpecialistEliteCostMultiplier($selectedDominion, 'gold')-1) * 100, 2) }}%</td>
                    </tr>
                    <tr>
                        <td>Ore:</td>
                        <td>{{ number_format(($trainingCalculator->getSpecialistEliteCostMultiplier($selectedDominion, 'ore')-1) * 100, 2) }}%</td>
                    </tr>
                    <tr>
                        <td>Lumber:</td>
                        <td>{{ number_format(($trainingCalculator->getSpecialistEliteCostMultiplier($selectedDominion, 'lumber')-1) * 100, 2) }}%</td>
                    </tr>
                    <tr>
                        <td>Mana:</td>
                        <td>{{ number_format(($trainingCalculator->getSpecialistEliteCostMultiplier($selectedDominion, 'mana')-1) * 100, 2) }}%</td>
                    </tr>
                    <tr>
                        <td>Food:</td>
                        <td>{{ number_format(($trainingCalculator->getSpecialistEliteCostMultiplier($selectedDominion, 'food')-1) * 100, 2) }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
</div>
