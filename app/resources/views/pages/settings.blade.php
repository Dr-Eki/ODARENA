@extends('layouts.master')

@section('page-header', 'Settings')
@section('title', 'Settings')

@section('content')
    @php
        $user = Auth::user();
    @endphp

<div class="row">
    <div class="col-sm-12 col-md-12">
        <form action="{{ route('settings') }}" method="post" enctype="multipart/form-data" role="form">
            @csrf

            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#account" data-toggle="tab">Account</a></li>
                    <li><a href="#notifications" data-toggle="tab">Notifications</a></li>
                </ul>
                <div class="tab-content">

                    <div class="tab-pane active" id="account">
                        <div class="row form-horizontal">
                            <div class="col-md-6">

                                <h2 class="page-header">Settings</h2>

                                {{-- Email --}}
                                <div class="form-group">
                                    <label for="email" class="col-sm-3 control-label">Email</label>
                                    <div class="col-sm-9">
                                        <input type="email" name="account_email" id="email" class="form-control" value="{{ $user->email }}" readonly>
                                        <p class="help-block">To change your email address, please contact admin.</p>
                                    </div>
                                </div>

                                {{-- Skins --}}
                                <div class="form-group">
                                    <label for="skin" class="col-sm-3 control-label">Skin</label>
                                    <div class="col-sm-9">
                                        <select name="skin" id="skin" class="form-control">
                                            <option value="skin-red">Default</option>
                                            <option value="skin-dark-red" {{ Auth::user()->skin == 'skin-dark-red' ? 'selected' : null }}>Dark</option>
                                        </select>
                                        <p class="help-block">Select a new color scheme for the website. <em>The game is optimised for default.</em></p>
                                    </div>
                                </div>

                                {{-- World News --}}
                                <div class="form-group">
                                    <label for="skin" class="col-sm-3 control-label">Skin</label>
                                    <div class="col-sm-9">
                                        <select name="skin" id="skin" class="form-control">
                                            <option value="skin-red">Default</option>
                                            <option value="skin-dark-red" {{ Auth::user()->skin == 'skin-dark-red' ? 'selected' : null }}>Dark</option>
                                        </select>
                                        <p class="help-block">Select a new color scheme for the website. <em>The game is optimised for default.</em></p>
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-6">

                                <h2 class="page-header">Avatar</h2>

                                {{-- Avatar --}}
                                <div class="form-group">
                                    <div class="col-xs-12">
                                        <div style="margin-bottom: 10px;">
                                            <img src="{{ $user->getAvatarUrl() }}" class="img-responsive" alt="Avatar of {{ $user->display_name }}">
                                        </div>
                                        @if ($user->avatar === null)
                                            <p class="help-block">Your are currently using your <a href="https://en.gravatar.com/" target="_blank">Gravatar <i class="fa fa-external-link"></i></a>.</p>
                                        {{--@else--}}
                                            {{--<p class="help-block">You are using a custom uploaded avatar. <a href="#">Reset to Gravatar</a>.</p>--}}
                                        @endif

                                        <label class="btn btn-default btn-file">
                                            Upload new avatar <input type="file" name="account_avatar" accept="image/*">
                                        </label>

                                        <span class="new-avatar-filename" style="padding-left: 8px;"></span>

                                        <p class="help-block">Uploaded avatars will be cropped/resized to 200x200 pixels and converted to PNG. Upload a square image for best results.</p>
                                        <p class="help-block">Supported formats are JPG, PNG, WebP and non-animated GIF.</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="notifications">
                        <div class="row">
                            <div class="col-sm-6">

                                <h2 class="page-header">Notifications</h2>

                                @foreach ($notificationHelper->getNotificationCategories() as $category => $notifications)
                                    <table class="table table-striped table-hover">
                                        <colgroup>
                                            <col>
                                            <col width="100">
                                            <col width="100">
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <th>{{ $notificationHelper->getNotificationTypeLabel($category) }}</th>
                                                <th class="text-center">Email</th>
                                                <th class="text-center">Ingame</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><em>All {{ $notificationHelper->getNotificationTypeLabel($category) }}</em></td>
                                                <td class="text-center">
                                                    <input type="checkbox" data-check-all data-check-all-type="email" {{ collect($notificationSettings[$category] ?? [])->map(function ($notification) { return $notification['email'] ?? false; })->reduce(function ($carry, $item) { return (($carry || ($carry === null)) && $item); }) ? 'checked' : null }}>
                                                </td>
                                                <td class="text-center">
                                                    <input type="checkbox" data-check-all data-check-all-type="ingame" {{ collect($notificationSettings[$category] ?? [])->map(function ($notification) { return $notification['ingame'] ?? false; })->reduce(function ($carry, $item) { return (($carry || ($carry === null)) && $item); }) ? 'checked' : null }}>
                                                </td>
                                            </tr>
                                            @foreach ($notifications as $type => $notification)
                                                <tr>
                                                    <td>{{ $notification['label'] }}</td>
                                                    <td class="text-center">
                                                        <input type="checkbox" name="notifications[{{ $category }}][{{ $type }}][email]" {{ array_get($notificationSettings, "{$category}.{$type}.email", $notification['defaults']['email']) ? 'checked' : null }} data-check-all-type="email">
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($notification['onlyemail'] ?? false)
                                                            &nbsp;
                                                        @else
                                                            <input type="checkbox" name="notifications[{{ $category }}][{{ $type }}][ingame]" {{ array_get($notificationSettings, "{$category}.{$type}.ingame", $notification['defaults']['ingame']) ? 'checked' : null }} data-check-all-type="ingame">
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endforeach

                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Settings</button>
                </div>
            </div>

        </form>
      </div>
</div>

@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {

            // Display filename and filesize on avatar upload
            $(document).on('change', ':file', function () {
                var input = $(this);
                var file = input.get(0).files[0];
                var fileName = input.val().replace(/\\/g, '/').replace(/.*\//, '');

                $('.new-avatar-filename').text(fileName + ' (' + formatBytes(file.size) + ')');
            });

            $('#notifications input[type=checkbox]').change(function (e) {
                var input = $(this);
                var inputType = input.data('check-all-type') ? input.data('check-all-type') : 'email'
                var inputIsAllCheckbox = input.is('[data-check-all]');

                var allCheckbox = input.parents('tbody').find('input[type="checkbox"][data-check-all][data-check-all-type="' + inputType + '"]');
                var allCheckboxes = input.parents('tbody').find('input[type="checkbox"][data-check-all-type="' + inputType + '"]').not('[data-check-all]');

                if (inputIsAllCheckbox) {
                    allCheckboxes.each(function () {
                        $(this).prop('checked', allCheckbox.prop('checked'));
                    });
                } else {
                    allCheckbox.prop('checked', (allCheckboxes.filter(':checked').length === allCheckboxes.length) ? 'checked' : '');
                }
            });

        })(jQuery);
    </script>
@endpush
