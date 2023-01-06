<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="ra ra-anvil"></i> Resource Training Cost Modifiers</h3>
    </div>
        <div class="box-body table-responsive no-padding">
            <table class="table">
                <colgroup>
                    <col width="33%">
                    <col width="67%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Resource</th>
                        <th>Modifier</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach($selectedDominion->race->resources as $resourceKey)
                        @php
                            $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                        @endphp
                        <tr>
                            <td>{{ $resource->name }}:</td>
                            <td>{{ number_format($trainingCalculator->getSpecialistEliteCostMultiplier($selectedDominion, $resourceKey) * 100, 2) }}%</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="2"><p class="text-muted"><small><em>Cost reductions can only exceed 50% with spells, deity, or research perks. Max total reduction is 90%.</em></small></p></td>
                    </tr>

                </tbody>
            </table>
        </div>
</div>
