@extends('layouts.master')
@section('title', 'Buffer')

@section('content')
@include('partials.dominion.advisor-selector')

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="ra ra-book ra-fw"></i> Buffer</h3>
            </div>
            <div class="box-body">
                <table class="table table-condensed table-striped no-border">
                    <colgroup>
                        <col width="100">
                        <col width="200">
                        <col width="200">
                        <col width="200">
                        <col width="200">
                        <col width="100">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Tick</th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Amount</th>
                            <th>Source</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $dominion = $selectedDominion;

                            $tickChanges = \OpenDominion\Models\TickChange::where([
                                            'target_type' => \OpenDominion\Models\Dominion::class,
                                            'target_id' => $dominion->id,
                                            'status' => 0
                                        ])->get();

                            dump($tickChanges);
                        @endphp

                    @foreach ($bufferedItems as $buffer)
                        <tr>
                            <td>{{ $buffer->tick }}</td>
                            <td>{{ str_replace("OpenDominion\Models\\",'',$buffer->source_type) }}</td>
                            <td>{{ $buffer->source->name }}</td>
                            <td>{{ number_format($buffer->amount) }}</td>
                            <td>{{ $buffer->type }}</td>
                            <td class="text-center">{!! ($buffer->status == 1) ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-spinner fa-spin"></i>' !!}</td>
                        </tr>
                    @endforeach
                    <tbody>
                </table>
            </div>
            <div class="box-footer">
                <div class="pull-right">
                    {{ $bufferedItems->links() }}
                </div>
            </div>

          </div>
      </div>
</div>
@endsection
