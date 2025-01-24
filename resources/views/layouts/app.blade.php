<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    <!-- Scripts -->
    {{--    <script src="{{ asset('js/app.js') }}" defer></script> --}}

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">


    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        .text-signature {
            color: #E8BC50;
        }

        .bg-signature {
            background: #E8BC50;
        }
    </style>
    <!-- Styles -->
    {{--    <link href="{{ asset('css/app.css') }}" rel="stylesheet"> --}}
</head>

<body>
    {{--        <nav class="navbar navbar-expand-md navbar-light bg-white border-bottom-1"> --}}
    {{--            <div class="container"> --}}
    {{--                <a class="navbar-brand text-uppercase" href="{{ url('/') }}"> --}}
    {{--                    {{ config('app.name') }} --}}
    {{--                    <img width="80px" src="{{asset('management/images/logo.webp')}}" alt=""> --}}
    {{--                </a> --}}
    {{--                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}"> --}}
    {{--                    <span class="navbar-toggler-icon"></span> --}}
    {{--                </button> --}}

    {{--                <div class="collapse navbar-collapse" id="navbarSupportedContent"> --}}
    {{--                    <!-- Left Side Of Navbar --> --}}
    {{--                    <ul class="navbar-nav me-auto"> --}}

    {{--                    </ul> --}}

    {{--                    <!-- Right Side Of Navbar --> --}}
    {{--                    <ul class="navbar-nav ms-auto"> --}}
    {{--                        <!-- Authentication Links --> --}}
    {{--                        @guest --}}
    {{--                            @if (Route::has('login')) --}}
    {{--                            @else --}}
    {{--                                <li class="nav-item"> --}}
    {{--                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a> --}}
    {{--                                </li> --}}
    {{--                            @endif --}}

    {{--                            @if (Route::has('register')) --}}
    {{--                                <li class="nav-item"> --}}
    {{--                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a> --}}
    {{--                                </li> --}}
    {{--                            @endif --}}
    {{--                        @endguest --}}
    {{--                        @auth --}}
    {{--                            <li class="nav-item dropdown"> --}}
    {{--                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre> --}}
    {{--                                    {{ Auth::user()->name }} --}}
    {{--                                </a> --}}

    {{--                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown"> --}}
    {{--                                    <a class="dropdown-item" href="{{ route('logout') }}" --}}
    {{--                                       onclick="event.preventDefault(); --}}
    {{--                                                     document.getElementById('logout-form').submit();"> --}}
    {{--                                        {{ __('Logout') }} --}}
    {{--                                    </a> --}}

    {{--                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none"> --}}
    {{--                                        @csrf --}}
    {{--                                    </form> --}}
    {{--                                </div> --}}
    {{--                            </li> --}}
    {{--                        @endauth --}}
    {{--                    </ul> --}}
    {{--                </div> --}}
    {{--            </div> --}}
    {{--        </nav> --}}


    @yield('content')
</body>

</html>
