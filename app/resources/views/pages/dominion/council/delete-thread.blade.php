@extends('layouts.master')

@section('content')
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Delete Thread</h3>
        </div>
        <form action="{{ route('dominion.council.delete.thread', $thread) }}" method="post" class="form-horizontal" role="form">
            @csrf
            <div class="box-body">
                Are you sure you want to delete this thread and all of its contents?
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-danger" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Delete Thread</button>
            </div>
        </form>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Thread: {{ $thread->title }}</h3>
        </div>
        <div class="box-body">
            {!! Illuminate\Mail\Markdown::convertToHtml($thread->body) !!}
        </div>
        <div class="box-footer">
            <small>
                <i>
                    Posted {{ $thread->created_at }} by
                    @if ($thread->dominion->isMonarch())
                        <i class="ra ra-queen-crown text-red"></i>
                    @endif
                    <em>{{ $thread->dominion->title->name }}</em>
                    {{ $thread->dominion->ruler_name }} of
                    <b>{{ $thread->dominion->name }}</b>
                </i>
            </small>
        </div>
    </div>

    @if (!$thread->posts->isEmpty())
        @foreach ($thread->posts as $post)
            <div class="box">
                <div class="box-body">
                    {!! Illuminate\Mail\Markdown::convertToHtml($post->body) !!}
                </div>
                <div class="box-footer">
                    <small>
                        <i>
                            Posted {{ $post->created_at }} by
                            @if ($post->dominion->isMonarch())
                                <i class="ra ra-queen-crown text-red"></i>
                            @endif
                            <em>{{ $thread->dominion->title->name }}</em>
                            {{ $thread->dominion->ruler_name }} of
                            <b>{{ $thread->dominion->name }}</b>
                        </i>
                    </small>
                </div>
            </div>
        @endforeach
    @endif
@endsection
