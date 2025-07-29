<!DOCTYPE html>
<html class="loading" lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description"
        content="Innovative Network ERP is a robust, user-friendly, and modern ERP solution designed to streamline business operations with unmatched flexibility and efficiency.">
    <meta name="keywords"
        content="Innovative Network ERP, business management software, ERP solution, powerful ERP system, enterprise resource planning, efficient operations">
    <meta name="author" content="Innovative Network">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('management') }}/app-assets/img/ico/favicon.ico">
    <link rel="shortcut icon" type="image/png" href="{{ asset('management') }}/app-assets/img/ico/favicon-32.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-touch-fullscreen" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link
        href="https://fonts.googleapis.com/css?family=Rubik:300,400,500,700,900%7CMontserrat:300,400,500,600,700,800,900"
        rel="stylesheet">

    <link rel="stylesheet" type="text/css"
        href="{{ asset('management/app-assets/fonts/font-awesome/css/font-awesome.min.css') }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/css/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/css/bootstrap-extended.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/css/components.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/css/pages/authentication.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/css/themes/layout-dark.css') }}">


</head>

<body
    class="horizontal-layout horizontal-menu horizontal-menu-padding 1-column auth-page navbar-sticky blank-page {{ Cookie::get('layout') === 'dark' ? 'layout-dark' : '' }}"
    data-open="hover" data-menu="horizontal-menu" data-col="1-column">
    <div class="wrapper p-0">
        <div class="main-panel">
            <!-- BEGIN : Main Content-->
            <div class="main-content">
                <div class="content-overlay"></div>
                <div class="content-wrapper">
                    <!--Login Page Starts-->
                    <section id="login" class="auth-height">
                        <div class="row full-height-vh m-0">
                            <div class="col-12 d-flex align-items-center justify-content-center">
                                <div class="card overflow-hidden">
                                    <div class="card-content">
                                        <div class="card-body auth-img">
                                            <div class="row m-0">
                                                <div
                                                    class="col-lg-6 d-none d-lg-flex justify-content-center align-items-center auth-img-bg p-3">
                                                    <img src="{{ asset('management/app-assets/img/meskay-logo.png') }}"
                                                        alt="" class="img-fluid" width="300" height="230">
                                                </div>
                                                <div class="col-lg-6 col-12 px-4 py-4">
                                                    <form method="POST" class="needs-validation mb-6" novalidate
                                                        action="{{ route('login') }}">
                                                        @csrf
                                                        <input type="hidden" name="skip-error-format">
                                                        <h4 class="mb-2 card-title">Login</h4>
                                                        <p>Welcome back, please login to your account.</p>
                                                        @if (session('message'))
                                                            <div class="alert alert-warning alert-dismissible fade show"
                                                                role="alert">
                                                                {{ session('message') }}
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="alert" aria-label="Close"></button>
                                                            </div>
                                                        @endif
                                                        <div class="my-2">
                                                            <input id="signinEmailInput" placeholder="Username"
                                                                type="text"
                                                                class="form-control @error('username') is-invalid @enderror"
                                                                name="username" value="{{ old('username') }}" required
                                                                autofocus>
                                                            @error('username')
                                                                <span class="invalid-feedback" role="alert">
                                                                    {{ $message }}
                                                                </span>
                                                            @enderror
                                                        </div>
                                                        <div class="my-2">
                                                            <input id="formSignUpPassword" type="password"
                                                                placeholder="Password"
                                                                class="form-control fakePassword @error('password') is-invalid @enderror"
                                                                name="password" required>
                                                            @error('password')
                                                                <span class="invalid-feedback" role="alert">
                                                                    {{ $message }}
                                                                </span>
                                                            @enderror
                                                        </div>
                                                        <div
                                                            class="d-sm-flex justify-content-between mb-3 font-small-2">
                                                            <div class="remember-me mb-2 mb-sm-0">
                                                                <div class="checkbox auth-checkbox">
                                                                    <input class="form-check-input" name="remember"
                                                                        id="rememberMeCheckbox"
                                                                        {{ old('remember') ? 'checked' : '' }}
                                                                        type="checkbox" />

                                                                    <label for="rememberMeCheckbox"><span>Remember
                                                                            Me</span></label>
                                                                </div>
                                                            </div>

                                                            @if (Route::has('password.request'))
                                                                <a class="text-primary"
                                                                    href="{{ route('password.request') }}">
                                                                    {{ __('Forgot Password?') }}
                                                                </a>
                                                            @endif
                                                        </div>
                                                        <div
                                                            class="d-flex justify-content-end flex-sm-row flex-column">
                                                            {{-- <a href="auth-register.html"
                                                                    class="btn bg-light-primary mb-2 mb-sm-0">Register</a> --}}
                                                            <button type="submit"
                                                                class="btn btn-primary">Login</button>
                                                        </div>


                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <!--Login Page Ends-->
                </div>
            </div>
            <!-- END : End Main Content-->
        </div>
    </div>

</body>
<script src="{{ asset('frontend/assets/js/vendors/password.js') }}"></script>

</html>
