@extends('layouts.master')
@section('title', ' Hold ' . $hold->name . ' | Trade Ledger')

@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-solid fa-book"></i> Trade Ledger</h3>
            </div>
            <div class="box-body table-responsive box-border">
                <p>Only trades with dominions in your realm are shown.</p>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Dominion</th>
                            <th>Tick</th>
                            <th>Resource Sold</th>
                            <th>Resource Bought</th>
                            <th>Amount Sold</th>
                            <th>Amount Bought</th>
                            <th>Return Tick</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($holdTradeLedgerEntries as $tradeLedgerEntry)
                            @if($selectedDominion->realm->id == $tradeLedgerEntry->dominion->realm->id)
                                <tr>
                                    <td><a href="{{ route('dominion.insight.show', $tradeLedgerEntry->dominion) }}">{{ $tradeLedgerEntry->dominion->name }} (# {{ $tradeLedgerEntry->dominion->realm->number }})</a></td>
                                    <td>{{ number_format($tradeLedgerEntry->tick) }}</td>
                                    <td>{{ $tradeLedgerEntry->soldResource->name }}</td>
                                    <td>{{ $tradeLedgerEntry->boughtResource->name }}</td>
                                    <td>{{ number_format($tradeLedgerEntry->source_amount) }}</td>
                                    <td>{{ number_format($tradeLedgerEntry->target_amount) }}</td>
                                    <td>{{ number_format($tradeLedgerEntry->return_tick) }}</td>
                                </tr>
                            @else
                                <tr>
                                    <td colspan="6"><em>Not disclosed</em></td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="box-footer">
                {{ $holdTradeLedgerEntries->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
