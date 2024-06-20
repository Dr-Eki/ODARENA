@extends('layouts.master')
@section('title', "Hold | {$hold->name} | Give Units")

@section('content')

<div class="row">
    <div class="col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-solid fa-book"></i> Give Units</h3>
            </div>
            <div class="box-body table-responsive box-border">
                <form action="{{ route('dominion.trade.hold.give-units', $hold) }}" method="post">
                    @csrf
                    <input type="hidden" name="hold" value="{{ $hold->id }}">
                    <input type="hidden" name="dominion" value="{{ $selectedDominion->id }}">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Amount</th>
                                <th>Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($selectedDominion->race->units as $unit)
                                <tr>
                                    <td> {{ $unit->name }} </td>
                                    <td>
                                        <input type="number" name="units[{{ $unit->key }}]" class="form-control" placeholder="0" min="0" max="{{ $selectedDominion->{'military_unit' . $unit->slot} }}">
                                    </td>
                                    <td>
                                        {{ number_format($selectedDominion->{'military_unit' . $unit->slot}) }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td><strong>Estimated sentiment gain</strong></td>
                                <td colspan="2">
                                    <span id="sentiment-gain">0</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-primary">Give Units</button>
                </form>
            </div>
        </div>
    </div>



    <div class="col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>You can improve your relations with <a href="{{ route('dominion.trade.hold', $hold) }}">{{ $hold->name }}</a> by giving them units. The more units you give, the more your relations will improve.</p>
                <p>Actual sentiment gained is calculated when the units arrive at the hold. It takes 12 ticks for the units to arrive.</p>
            </div>
        </div>
    </div>
</div>
@endsection
@push('page-scripts')
    <script>
        $(document).ready(function() {
            $('form').on('input', function() {
                var units = {};
                $('input[type="number"]').each(function() {
                    var unitKey = $(this).attr('name').replace('units[', '').replace(']', '');
                    units[unitKey] = $(this).val();
                });

                $.ajax({
                    url: '{{ route("dominion.trade.hold.calculate-units-gift") }}',
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: JSON.stringify({
                        hold: {{ $hold->id }},
                        dominion: {{ $selectedDominion->id }},
                        units: units
                    }),
                    success: function(response) {
                        console.log('Response:', response); // Debugging line
                        if (response && response.sentiment) {
                            $('#sentiment-gain').text(response.sentiment);
                        } else {
                            console.error('Sentiment value is missing in the response');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', status, error);
                    }
                });
            });
        });
    </script>
@endpush
