<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <title>@yield('title', 'ODARENA')</title>

    @include('partials.styles')

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body class="hold-transition skin-red layout-top-nav">
{!! Analytics::render() !!}

<div class="wrapper">

    <!-- Header -->
    <header class="main-header">
        <nav class="navbar navbar-static-top">
            <div class="container">

                <!-- Navbar Header -->
                <div class="navbar-header">
                    <a href="{{ url('') }}" class="navbar-brand"><b>ODARENA</b></a>
                    <button class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
                        <i class="fas fa-compress"></i>
                    </button>
                </div>

                <!-- Navbar Left Menu -->
                <div class="collapse navbar-collapse pull-left" id="navbar-collapse">
                    <ul class="nav navbar-nav">
                        <li class="active"><a href="{{ route('home') }}">Error 419 <span class="sr-only">(current)</span></a></li>
                    </ul>
                </div>

            </div>
        </nav>
    </header>

    <!-- Content -->
    <div class="content-wrapper">
        <div class="container">

            <div class="content">

                <div class="row">
                    <div class="col-sm-8 col-sm-offset-2">

                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Status 419: Document Expired</h3>
                            </div>
                            <div class="box-body">
                                <p>
                                      ODARENA encountered an authentication time out error. The page you were on expired. Go back, refresh, and try again.
                                </p>
                                <p>
                                    <dl>
                                        <dt>Message:</dt>
                                        <dd>{{ $exception->getMessage() }}</dd>
                                    </dl>
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>

    @include('partials.main-footer')

</div>

@include('partials.scripts')

</body>
</html>
