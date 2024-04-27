@extends('layouts.master')
@section('title', 'Confirm Trade')

@section('content')
    @php
        $hold = $tradeRoute->hold;
        $soldResourceAmount = $tradeRoute->source_amount;

        $soldResource = $tradeRoute->soldResource;

        $boughtResource = $tradeRoute->boughtResource;

        $boughtResourceAmount = $tradeCalculator->getBoughtResourceAmount($selectedDominion, $hold, $soldResource, $soldResourceAmount, $boughtResource);
    @endphp
    <div class="row">
        <div class="col-md-4 col-md-offset-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa-solid fa-arrow-right-arrow-left fa-fw"></i> Edit {{ $soldResource->name }} for {{ $boughtResource->name }} trade route with {{ $hold->name }}
                    </h3>
                </div>
                <div class="box-body">
                    <form action="{{ route('dominion.trade.routes.edit', [$tradeRoute->hold->key, $tradeRoute->soldResource->key]) }}" method="post">
                        @csrf
                        <input type="hidden" name="trade_route" value="{{ $tradeRoute->id }}">
                        <input type="hidden" name="hold" value="{{ $hold->id }}">
                        <div class="row">
                            <div class="col-xs-6 col-sm-6">
                                <h5>
                                    <i class="fa-solid fa-arrow-right fa-fw"></i> You Sell
                                </h5>
                                <div class="input-group">
                                    <small class="text-muted">Resource:</small>
                                    <select name="sold_resource" class="input-group form-control" style="min-width:10em; width:100%;" disabled>
                                        <option value="{{ $soldResource->key }}">{{ $soldResource->name }}</option>
                                    </select>
                                    <br>
                                    <small class="text-muted">Current Amount:</small>
                                    <span class="input-group-input">
                                        <input type="number" name="current_sold_resource_amount" class="form-control text-center" placeholder="{{ $soldResourceAmount }}" min="1" size="5" style="min-width:5em; width:100%;" disabled>
                                    </span>
                                    <br>
                                    <small class="text-muted">New Amount: *</small><br>
                                    <span class="input-group-input">
                                        <input type="number" name="sold_resource_amount" class="form-control text-center" placeholder="{{ $soldResourceAmount }}" min="1" size="5" style="min-width:5em; width:100%;" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </span>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-6">
                                <h5>
                                    <i class="fa-solid fa-arrow-left fa-fw"></i> You Buy
                                </h5>

                                <div class="input-group">
                                    <small class="text-muted">Resource:</small>
                                    <select name="bought_resource" class="input-group form-control" style="min-width:10em; width:100%;" disabled>
                                        <option value="{{ $boughtResource->key }}">{{ $boughtResource->name }}</option>
                                    </select>
                                    <br>
                                    <small class="text-muted">Current Amount:</small>
                                    <span class="input-group-input">
                                        <input type="number" name="current_bought_resource_amount" class="form-control text-center" placeholder="{{ $boughtResourceAmount }}" min="1" size="5" style="min-width:5em; width:100%;" disabled>
                                    </span>
                                    <br>
                                    <small class="text-muted">New Amount: **</small><br>
                                    <span class="input-group-input">
                                        <input type="number" name="bought_resource_amount" class="form-control text-center" placeholder="{{ $boughtResourceAmount }}" min="1" size="5" style="min-width:5em; width:100%;" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </span>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-12">
                                <p>&nbsp;</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-sm-12">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fa-solid fa-check fa-fw"></i> Edit Trade
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-sm-12">
                                <a href="{{ route('dominion.trade.routes.clear-trade-route') }}" class="btn btn-default btn-block">
                                    <i class="fa-solid fa-xmark"></i> Do Nothing
                                </a>
                            </div>
                        </div>
                    </form>
                    <div class="col-xs-6 col-sm-6">
                        <small class="text-muted">
                            * If you do not have enough to trade, the trade route will be cancelled and the hold's sentiment about you may go down.
                        </small>
                    </div>
                    <div class="col-xs-6 col-sm-6">
                        <small class="text-muted">
                            ** Amount received may fluctuate with market movements. You agree to receive the amount determined by the hold.
                        </small>
                    </div>
                    <div class="col-xs-12 col-sm-12">
                        <hr>
                    </div>
                    <div class="col-xs-12 col-sm-12">
                        <h5>Cancel Trade</h5>
                        <small class="text-muted">You can immediately*** cancel this trade route. No further trades will take place. Resources currently enroute will be delivered.</small>
                        <form method="post" action="{{ route('dominion.trade.routes.delete-trade-route') }}">
                            @csrf
                            <input type="hidden" name="trade_route" value="{{ $tradeRoute->id }}">
                            <button type="submit" class="btn btn-block btn-warning">Cancel Trade Route</button>
                        </form>
                        <small class="text-muted">*** There is no confirmation. This action is instant and irreversible.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('page-scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('.box-primary form');

        forms.forEach(form => {
            const soldResourceSelect = form.querySelector('select[name="sold_resource"]');
            const boughtResourceSelect = form.querySelector('select[name="bought_resource"]');
            const soldResourceAmountInput = form.querySelector('input[name="sold_resource_amount"]');
            const boughtResourceAmountInput = form.querySelector('input[name="bought_resource_amount"]');

            // Check if elements exist before adding event listeners
            if (soldResourceSelect && boughtResourceSelect && soldResourceAmountInput && boughtResourceAmountInput) {
                [soldResourceSelect, boughtResourceSelect, soldResourceAmountInput].forEach(input => {
                    input.addEventListener('change', function() {
                        const data = {
                            hold: form.querySelector('input[name="hold"]').value,
                            sold_resource: soldResourceSelect.value,
                            sold_resource_amount: soldResourceAmountInput.value,
                            bought_resource: boughtResourceSelect.value,
                        };

                        fetch('/dominion/trade/routes/calculate-trade-route', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(data)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.original && data.original.bought_resource_amount) {
                                boughtResourceAmountInput.value = data.original.bought_resource_amount;
                            } else {
                                console.error('Invalid response structure:', data);
                                boughtResourceAmountInput.value = 'Error';
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    });
                });
            }
        });
    });
    </script>
@endpush

@push('page-scripts')
    <script type="text/javascript">
    $("form").submit(function () {
        // prevent duplicate form submissions
        $(this).find(":submit").attr('disabled', 'disabled');
    });
    </script>
@endpush
