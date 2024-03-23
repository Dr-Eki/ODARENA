<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <title>ODARENA | @yield('title')</title>

    <link rel="author" href="{{ asset('humans.txt') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="apple-mobile-web-app-title" content="ODARENA">
    <meta name="application-name" content="ODARENA">
    <meta name="theme-color" content="#ffffff">

    @if(!in_array(request()->getHost(), ['sim.odarena.com', 'odarena.local', 'odarena.virtual']))
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-174957772-1"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', 'UA-174957772-1');
        </script>
    @endif

    @include('partials.styles')


    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    @if(Auth::user())
        <script type="text/javascript">

            function urlBase64ToUint8Array(base64String) {
                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                const base64 = (base64String + padding)
                    .replace(/\-/g, '+')
                    .replace(/_/g, '/');

                const rawData = window.atob(base64);
                const outputArray = new Uint8Array(rawData.length);

                for (let i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }
                return outputArray;
            }

            // Check if the browser supports service workers and push notifications
            if ('serviceWorker' in navigator && 'PushManager' in window) {
                navigator.serviceWorker.register('/serviceworker.js')
                    .then(function(swReg) {
                        console.log('Service Worker is registered', swReg);

                        swReg.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: '{{ config('services.webpush.vapid.public_key') }}'
                        })
                        .then(function(subscription) {
                            // Send the subscription object to the server
                            fetch('{{ url('/api/v1/push-subscription') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify(subscription)
                            });
                        })
                        .catch(function(error) {
                            console.error('Error subscribing to push notifications', error);
                        });
                    })
                    .catch(function(error) {
                        console.error('Service Worker Error', error);
                    });
            }
            else
            {
                console.warn('Service workers are not supported in this browser');
            }
        </script>
    @endif

</head>
<body class="hold-transition {{ Auth::user() && Auth::user()->skin ? Auth::user()->skin : 'skin-red' }}  sidebar-mini">
{{-- Analytics::render() --}}

<div class="wrapper">

    @include('partials.main-header')

    @include('partials.main-sidebar')

    <div class="content-wrapper">
        @include('partials.tick-indicator')

        @hasSection('page-header')
            <div class="content-header">
                <h1>
                    @yield('page-header')

                    @hasSection('page-subheader')
                        <small>
                            @yield('page-subheader')
                        </small>
                    @endif

                </h1>
                {{--<ol class="breadcrumb">
                    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active">Foo</li>
                </ol>--}}
            </div>
        @endif

        <section class="content">

            @include('partials.alerts')

            @include('partials.resources-overview')

            @yield('content')

        </section>

    </div>

    @include('partials.main-footer')

    @include('partials.control-sidebar')

</div>

@include('partials.scripts')


</body>
</html>
