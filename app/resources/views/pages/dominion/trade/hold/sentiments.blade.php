@extends('layouts.master')
@section('title', "Hold | {$hold->name} | Sentiments")

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-solid fa-book"></i> Hold Sentiments</h3>
            </div>
            <div class="box-body table-responsive box-border">
                <p>Only sentiments with dominions in your realm are shown.</p>
                <form action="{{ route('dominion.trade.hold.sentiments', $hold->id) }}" method="GET">
                    <input type="text" name="search" placeholder="Search by name or event..." value="{{ $search ?? '' }}">
                    <button type="submit" class="btn btn-default">Search</button>
                </form>
                <table class="table table-hover">
                    <colgroup>
                        <col style="width: 20%;">
                        <col style="width: 20%;">
                        <col style="width: 40%;">
                        <col style="width: 20%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Dominion</th>
                            <th>Sentiment</th>
                            <th>Event</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($holdSentimentEvents as $holdSentimentEvent)
                            <tr>
                                <td>
                                    <a href="{{ route('dominion.insight.show', $holdSentimentEvent->target) }}">
                                        {{ $holdSentimentEvent->target->name }}
                                        (# {{$holdSentimentEvent->target->realm->number}})
                                    </a>
                                </td>
                                <td>
                                    <span class="{{ $holdSentimentEvent->sentiment >= 0 ? 'text-green' : 'text-red'; }}">
                                        {{ sprintf('%+g',$holdSentimentEvent->sentiment) }}
                                    </span>
                                </td>
                                <td>{{ $holdHelper->getSentimentEventDescriptionString($holdSentimentEvent->description) }}</td>
                                <td>{{ $holdSentimentEvent->created_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="box-footer">
                {{ $holdSentimentEvents->appends(['search' => request()->search])->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
