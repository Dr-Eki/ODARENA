@extends('layouts.master')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Thread: {{ $thread->title }}</h3>
        </div>
        <div class="box-body">
            {!! Illuminate\Mail\Markdown::parse($thread->body) !!}

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
            @if ($selectedDominion->isMonarch())
                <a href="{{ route('dominion.council.delete.thread', $thread) }}"><i class="fa fa-trash text-red"></i></a>
            @endif
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
                            <em>{{ $post->dominion->title->name }}</em>
                            {{ $post->dominion->ruler_name }} of
                            <b>{{ $post->dominion->name }}</b>
                        </i>
                    </small>
                    @if ($selectedDominion->isMonarch())
                        <a href="{{ route('dominion.council.delete.post', $post) }}"><i class="fa fa-trash text-red"></i></a>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Post Reply</h3>
        </div>
        <form action="{{ route('dominion.council.reply', $thread) }}" method="post" class="form-horizontal" role="form">
            @csrf
            <div class="box-body">

                {{-- Body --}}
                <div class="form-group">
                    <label for="body" class="col-sm-3 control-label">Body</label>
                    <div class="col-sm-9">
                        <textarea name="body" id="body" rows="3" class="form-control" placeholder="Body" required {{ $selectedDominion->isLocked() ? 'disabled' : null }}>{{ old('body') }}</textarea>
                        <p class="help-block">Markdown is supported with <a href="http://commonmark.org/help/" target="_blank">CommonMark syntax <i class="fa fa-external-link"></i></a>.</p>
                    </div>
                </div>

            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Post Reply</button>
            </div>
        </form>
    </div>
@endsection
