@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Legal</h3>
        </div>
        <div class="box-body">
            <ul>
              <li><a href="{{ route('legal.terms-and-conditions') }}">Terms and Conditions</a></li>
              <li><a href="{{ route('legal.privacy-policy') }}">Privacy Policy</a></li>
              <li>Credits</li>
        </div>
    </div>
@endsection
