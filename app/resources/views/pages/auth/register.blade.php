@extends('layouts.topnav')
@section('title', 'ODARENA | Register Account')

@section('content')
    <div class="row">
        <div class="col-sm-6 col-sm-offset-3">

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Register</h3>
                </div>
                <form action="{{ route('auth.register') }}" method="post" class="form-horizontal" role="form">
                    @csrf
                    @honeypot

                    <div class="box-body">

                        {{-- Display Name --}}
                        <div class="form-group">
                            <label for="display_name" class="col-sm-3 control-label">Display Name</label>
                            <div class="col-sm-9">
                                <input type="text" name="display_name" id="display_name" class="form-control" placeholder="Display Name" value="{{ old('display_name') }}" required autofocus>
                                <span class="help-block">
                                    Your display name will be shown on your public profile and in Valhalla (leaderboards).
                                </span>
                            </div>
                        </div>

                        {{-- Email --}}
                        <div class="form-group">
                            <label for="email" class="col-sm-3 control-label">Email</label>
                            <div class="col-sm-9">
                                <input type="email" name="email" id="email" class="form-control" placeholder="Email" value="{{ old('email') }}" required>
                                <span class="help-block">
                                    Please use a valid email address, since you need to validate your account before you can start playing.
                                </span>
                            </div>
                        </div>

                        {{-- Password --}}
                        <div class="form-group">
                            <label for="password" class="col-sm-3 control-label">Password</label>
                            <div class="col-sm-9">
                                <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                            </div>
                        </div>

                        {{-- Password (confirm) --}}
                        <div class="form-group">
                            <label for="password_confirmation" class="col-sm-3 control-label">Password (confirm)</label>
                            <div class="col-sm-9">
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Password (confirm)" required>
                            </div>
                        </div>

                        {{-- Terms and Conditions --}}
                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-9">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="terms" required> I agree to the <a href="{{ route('legal.terms-and-conditions') }}">Terms and Conditions</a>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <p>Once you activate your user account, you can sign up for an active round and start playing. Your user account will be persistent across rounds and dominions.</p>

                    </div>

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Register</button>
                        <div class="pull-right">
                            Already have an account? <a href="{{ route('auth.login') }}">Login</a>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>
@endsection
