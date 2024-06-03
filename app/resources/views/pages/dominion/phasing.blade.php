@extends ('layouts.master')
@section('title', 'Phasing')

@section('content')
@push('styles')
<style>
    .unit-row {
        margin-bottom: 1em;
        margin-left: 0;
        margin-right: 0;
        padding-top: 1em;
        padding-bottom: 1em;
    }
    .unit-row:hover {
        background: #f2faff;
    }
</style>
@endpush

<div class="row">
    <div class="col-sm-12 col-md-9">

        <!-- TARGET -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-player-dodge ra-fw"></i> Phasing</h3>
                    </div>
                    <div class="box-body">
                        <div class="row unit-row">
                            <div class="col-md-2">
                                <strong>Source Unit</strong>
                            </div>

                            <div class="col-md-2">
                                <strong>Amount</strong>
                            </div>

                            <div class="col-md-2">
                                <strong>Target Unit</strong>
                            </div>

                            <div class="col-md-2">
                                <strong>Unit Cost</strong>
                            </div>

                            <div class="col-md-2">
                                <strong>Total Cost</strong>
                            </div>

                            <div class="col-md-2">
                                <p>&nbsp;<br></p>
                            </div>
                        </div>

                        @foreach($phasingConfig as $sourceUnitKey => $phasingUnitData)
                            @php
                                $targetUnitData = current($phasingUnitData);
                                $sourceUnit = \OpenDominion\Models\Unit::fromKey($sourceUnitKey);
                                $targetUnit = \OpenDominion\Models\Unit::fromKey($targetUnitData['target_unit_key']);
                                $resource = \OpenDominion\Models\Resource::fromKey($targetUnitData['resource']);
                                $unitResourceCost = $targetUnitData['resource_amount'];
                                $dominionResourceAmount = $selectedDominion->{"resource_{$resource->key}"};
                            @endphp

                            <form action="{{ route('dominion.phasing')}}" method="post" role="form">
                                @csrf
                                <div class="row unit-row">
                                    <div class="col-md-2">
                                        {{ $sourceUnit->name }}
                                        <br><small class="text-muted">Total:</small> {{ number_format($unitCalculator->getUnitTypeTotal($selectedDominion, $sourceUnit->slot)) }}
                                        <br><small class="text-muted">Available:</small> {{ number_format($selectedDominion->{"military_unit{$sourceUnit->slot}"}) }}
                                        <input type="hidden" name="source_unit_key" value="{{ $sourceUnit->key }}">
                                    </div>

                                    <div class="col-md-2">
                                        <input type="number" class="form-control rangeInput" data-source-unit-key="{{ $sourceUnit->key }}" data-target-unit-key="{{ $targetUnit->key }}" min="0" value="0" placeholder="{{ $selectedDominion->{"military_unit{$sourceUnit->slot}"} }}" max="{{ $selectedDominion->{"military_unit{$sourceUnit->slot}"} }}"  name="source_unit_amount">
                                    </div>

                                    <div class="col-md-2">
                                        {{ $targetUnit->name }}
                                        <br><small class="text-muted">Total:</small> {{ number_format($unitCalculator->getUnitTypeTotal($selectedDominion, $targetUnit->slot)) }}
                                        <br><small class="text-muted">Available:</small> {{ number_format($selectedDominion->{"military_unit{$targetUnit->slot}"}) }}
                                        <input type="hidden" name="target_unit_key" value="{{ $targetUnit->key }}">
                                    </div>

                                    <div class="col-md-2">
                                        {{ number_format($unitResourceCost) }}
                                        {{ $resource->name }}
                                    </div>

                                    <div class="col-md-2">
                                        <span class="resourceAmount" data-id="{{ $sourceUnitKey }}">0</span>
                                        {{ $resource->name }}
                                    </div>

                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary btn-block">Phase</button>
                                    </div>
                                </div>
                            </form>

                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>Here you can phase (convert) units from one aspect to another.</p>
                <p>Phasing is instant.</p>
            </div>
        </div>
    </div>

</div>
@endsection

@push('page-scripts')
    <script type="text/javascript">
    $("form").submit(function () {
        // prevent duplicate form submissions
        $(this).find(":submit").attr('disabled', 'disabled');
    });
    </script>
@endpush

@push('page-scripts')
    <script type="text/javascript">
    document.querySelectorAll('.rangeInput').forEach(function(rangeInput) {
        rangeInput.addEventListener('input', function(e) {
            var sourceUnitKey = e.target.getAttribute('data-source-unit-key');
            var targetUnitKey = e.target.getAttribute('data-target-unit-key');
            var sourceUnitAmount = e.target.value;

            fetch('/dominion/phasing/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    source_unit: sourceUnitKey,
                    source_unit_amount: sourceUnitAmount,
                    target_unit: targetUnitKey
                })
            })
            .then(response => response.json())
            .then(data => {
                var resourceKey = Object.keys(data)[0];
                var cost = data[resourceKey];

                // Format the cost with commas
                var formattedCost = Number(cost).toLocaleString();

                document.querySelector('.resourceAmount[data-id="' + sourceUnitKey + '"]').textContent = formattedCost;
                document.querySelector('.resourceName[data-id="' + sourceUnitKey + '"]').textContent = resourceKey;
            });
        });
    });
    </script>
@endpush
