@extends('layouts.master')
@section('title', 'Trade Routes')

@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa-solid fa-book"></i> Trade Ledger</h3>
            </div>
            <div class="box-body table-responsive box-border">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Hold</th>
                            <th>Tick</th>
                            <th>Resource Sold</th>
                            <th>Resource Bought</th>
                            <th>Amount Sold</th>
                            <th>Amount Bought</th>
                            <th>Return Tick</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tradeLedgerEntries as $tradeLedgerEntry)
                            <tr>
                                <td><a href="{{ route('dominion.trade.hold', $tradeLedgerEntry->hold->key) }}"><strong>{{ $tradeLedgerEntry->hold->name }}</strong></a></td>
                                <td>{{ number_format($tradeLedgerEntry->tick) }}</td>
                                <td>{{ $tradeLedgerEntry->soldResource->name }}</td>
                                <td>{{ $tradeLedgerEntry->boughtResource->name }}</td>
                                <td>{{ number_format($tradeLedgerEntry->source_amount) }}</td>
                                <td>{{ number_format($tradeLedgerEntry->target_amount) }}</td>
                                <td>{{ number_format($tradeLedgerEntry->return_tick) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="box-footer">
                {{ $tradeLedgerEntries->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
