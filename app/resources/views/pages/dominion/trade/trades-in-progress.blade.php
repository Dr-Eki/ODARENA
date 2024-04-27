@extends('layouts.master')
@section('title', 'Trades In Progress')

@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa-solid fa-arrow-right-arrow-left fa-fw"></i> Trades In Progress</h3>
            </div>
            <div class="box-body table-responsive">


                @foreach($tradeRoutesTickData as $holdKey => $holdTradeData)
                    @php
                        $hold = \OpenDominion\Models\Hold::where('key', $holdKey)->firstOrFail();

                    @endphp

                    <a href="{{ route('dominion.trade.hold', $hold->key) }}"><strong>{{ $hold->name }}</strong></a></td>
                    <table class="table">
                        <colgroup>
                            <col>
                            <col width="100">
                            @for ($tick = 1; $tick <= 12; $tick++)
                                <col width="100">
                            @endfor
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Resource</th>
                                @for ($tick = 1; $tick <= 12; $tick++)
                                    <th class="text-center">{{ $tick }}</th>
                                @endfor
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tradeCalculator->getResourcesTradedBetweenDominionAndHold($selectedDominion, $hold) as $resourceKey)
                                @php
                                    $resource = \OpenDominion\Models\Resource::where('key', $resourceKey)->firstOrFail();
                                    $importTotal = 0;
                                    $exportTotal = 0;
                                @endphp

                                <tr>
                                    <td><i class="fa-solid fa-arrow-left fa-fw"></i>{{ $resource->name }}</td>

                                    @for ($tick = 1; $tick <= 12; $tick++)
                                        <td class="text-center">
                                            @if(isset($holdTradeData[$tick][$resource->key]['import']))
                                                {{ number_format($holdTradeData[$tick][$resource->key]['import']) }}

                                                @php
                                                    $importTotal += $holdTradeData[$tick][$resource->key]['import'];
                                                @endphp
                                            @else
                                                0
                                            @endif
                                        </td>
                                    @endfor
                                    <td>{{ number_format($importTotal) }}</td>
                                </tr>

                                <tr>
                                    <td><i class="fa-solid fa-arrow-right fa-fw"></i>{{ $resource->name }}</td>

                                    @for ($tick = 1; $tick <= 12; $tick++)
                                        <td class="text-center">
                                            @if(isset($holdTradeData[$tick][$resource->key]['export']))
                                                {{ number_format($holdTradeData[$tick][$resource->key]['export']) }}
                                                
                                                @php
                                                    $exportTotal += $holdTradeData[$tick][$resource->key]['export'];
                                                @endphp
                                            @else
                                                0
                                            @endif
                                        </td>
                                    @endfor
                                    <td>{{ number_format($exportTotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach

                
            </div>
        </div>
    </div>
</div>

@endsection
