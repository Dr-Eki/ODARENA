@php($user = Auth::user())

<ul class="nav nav-stacked">

    <li class="header">Staff</li>
    <li class="{{ Route::is('staff.index') ? 'active' : null }}"><a href="{{ route('staff.index') }}">Dashboard</a></li>

</ul>
