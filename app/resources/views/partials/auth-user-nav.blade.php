@auth
    <!-- User Account Menu -->
    <li class="dropdown user user-menu">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <img src="{{ Auth::user()->getAvatarUrl() }}" class="user-image" alt="{{ Auth::user()->display_name }}">
            <span class="hidden-xs">{{ Auth::user()->display_name }}</span>
        </a>
        <ul class="dropdown-menu">
            <li class="user-header">
                <img src="{{ Auth::user()->getAvatarUrl() }}" class="img-circle" alt="{{ Auth::user()->display_name }}">
                <p>
                    {{ Auth::user()->display_name }}
                </p>
            </li>
            <li class="user-footer">
                <div class="row">
                    <div class="col-md-12">
                        <a href="{{ route('dashboard') }}" class="btn btn-info btn-flat btn-block"><i class="fas fa-columns fa-fw"></i> Dashboard</a>
                    </div>
                    <div class="col-md-12">
                          <a href="{{ route('settings') }}" class="btn btn-primary btn-block"><i class="fas fa-sliders-h fa-fw"></i> Settings</a>
                    </div>
                    <div class="col-md-12">
                          <a href="{{ route('dashboard.quickstarts') }}" class="btn btn-warning btn-block"><i class="fas fa-solid fa-forward-fast"></i> Quickstarts</a>
                    </div>
                    <div class="col-md-12">
                        <form action="{{ route('auth.logout') }}" method="post">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-block">
                                <i class="fas fa-sign-out-alt fa-fw"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>

            </li>
        </ul>
    </li>
@else
    <li class="{{ Route::is('auth.register') ? 'active' : null }}"><a href="{{ route('auth.register') }}">Register</a></li>
    <li class="{{ Route::is('auth.login') ? 'active' : null }}"><a href="{{ route('auth.login') }}">Login</a></li>
@endauth
