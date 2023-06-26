@extends('layouts.master')
@section('title', 'Notes')

@section('content')
    @php
        Cache::put('test-key', 'test-value', 60);
        $value = Cache::get('test-key');

        if($value == 'test-value')
            dump('Cache is working!');
        else
            dump('Cache is NOT working!');
    @endphp

    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                  <h3 class="box-title"><i class="ra ra-quill-ink"><label for="notes"></i> Notes</label></h3>
                </div>
                <form action="{{ route('dominion.notes') }}" method="post" {{--class="form-inline" --}}role="form">
                    @csrf
                    <div class="box-body">
                        <div class="row">
                            <div class="col-lg-12">
                              <textarea id="notes" name="notes" rows=20 style="width:100%; font-family:monospace;" name="body" id="body" class="form-control" required {{ $selectedDominion->isLocked() ? 'disabled' : null }}>{{ $notes }}</textarea>
                          </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                            Update Notes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>These notes are only visible to you.</p>
                </div>
            </div>

            @include('partials.dominion.watched-dominions')
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
    $(document).delegate('#notes', 'keydown', function(e) {
      var keyCode = e.keyCode || e.which;

      if (keyCode == 9) {
        e.preventDefault();
        var start = this.selectionStart;
        var end = this.selectionEnd;

        // set textarea value to: text before caret + tab + text after caret
        $(this).val($(this).val().substring(0, start)
                    + "\t"
                    + $(this).val().substring(end));

        // put caret at right position again
        this.selectionStart =
        this.selectionEnd = start + 1;
      }
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
