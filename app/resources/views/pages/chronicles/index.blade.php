@extends('layouts.topnav')

@section('content')
    <div class="box">
        <div class="box-body">
            <p>These are the Chronicles of Odarena, where history becomes legends.</p>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fas fa-book"></i> Chronicles</h3>
        </div>
        <div class="box-body table-responsive no-padding">
            <table class="table table-striped">
                <colgroup>
                    <col width="50">
                    <col >
                    <col width="150">
                    <col width="150">
                    <col width="150">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Chapter</th>
                        <th class="text-center">Era</th>
                        <th class="text-center">Start</th>
                        <th class="text-center">End</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rounds as $round)
                        <tr>
                            <td class="text-center">{{ number_format($round->number) }}</td>
                            <td>
                                @if ($round->isActive())
                                    {{ $round->name }}
                                    <span class="label label-info">Active</span>
                                @elseif (!$round->hasStarted())
                                    {{ $round->name }}
                                    <span class="label label-warning">Not yet started</span>
                                @else
                                    <a href="{{ route('chronicles.round', $round) }}">{{ $round->name }}</a>
                                @endif
                            </td>
                            <td class="text-center">{{ $round->league->description }}</td>
                            <td class="text-center">{{ $round->start_date->toFormattedDateString() }}</td>
                            <td class="text-center">
                                @if($round->hasEnded())
                                    {{ $round->end_date->toFormattedDateString() }}
                                @else
                                    &mdash;
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
