{{--@extends('layouts.app')--}}

{{--@section('content')--}}
{{--<div class="container">--}}
{{-- <div class="row justify-content-center">--}}
{{-- <div class="col-md-8">--}}
{{-- <div class="card">--}}
{{-- <div class="card-header">{{ __('Register') }}</div>--}}

{{-- <div class="card-body">--}}
{{-- <form method="POST" action="{{ route('register') }}">--}}
{{-- @csrf--}}

{{-- <div class="row mb-3">--}}
{{-- <label for="name" class="col-md-4 col-form-label text-md-end">{{ __('Name') }}</label>--}}

{{-- <div class="col-md-6">--}}
{{-- <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>--}}

{{-- @error('name')--}}
{{-- <span class="invalid-feedback" role="alert">--}}
{{-- <strong>{{ $message }}</strong>--}}
{{-- </span>--}}
{{-- @enderror--}}
{{-- </div>--}}
{{-- </div>--}}

{{-- <div class="row mb-3">--}}
{{-- <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>--}}

{{-- <div class="col-md-6">--}}
{{-- <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">--}}

{{-- @error('email')--}}
{{-- <span class="invalid-feedback" role="alert">--}}
{{-- <strong>{{ $message }}</strong>--}}
{{-- </span>--}}
{{-- @enderror--}}
{{-- </div>--}}
{{-- </div>--}}

{{-- <div class="row mb-3">--}}
{{-- <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>--}}

{{-- <div class="col-md-6">--}}
{{-- <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">--}}

{{-- @error('password')--}}
{{-- <span class="invalid-feedback" role="alert">--}}
{{-- <strong>{{ $message }}</strong>--}}
{{-- </span>--}}
{{-- @enderror--}}
{{-- </div>--}}
{{-- </div>--}}

{{-- <div class="row mb-3">--}}
{{-- <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label>--}}

{{-- <div class="col-md-6">--}}
{{-- <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">--}}
{{-- </div>--}}
{{-- </div>--}}

{{-- <div class="row mb-0">--}}
{{-- <div class="col-md-6 offset-md-4">--}}
{{-- <button type="submit" class="btn btn-primary">--}}
{{-- {{ __('Register') }}--}}
{{-- </button>--}}
{{-- </div>--}}
{{-- </div>--}}
{{-- </form>--}}
{{-- </div>--}}
{{-- </div>--}}
{{-- </div>--}}
{{-- </div>--}}
{{--</div>--}}
{{--@endsection--}}
















<!doctype html>
<html lang="en">

<!-- Mirrored from block.codescandy.com/signin-v2.html by HTTrack Website Copier/3.x [XR&CO'2014], Thu, 21 Mar 2024 18:53:56 GMT -->

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <!-- Favicon icon-->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/favicon/apple-touch-icon.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon/favicon-16x16.png" />
    <link rel="manifest" href="assets/images/favicon/site.html" />
    <link rel="mask-icon" href="assets/images/favicon/block-safari-pinned-tab.svg" color="#8b3dff" />
    <link rel="shortcut icon" href="assets/images/favicon/favicon.ico" />
    <meta name="msapplication-TileColor" content="#8b3dff" />
    <meta name="msapplication-config" content="assets/images/favicon/tile.xml" />

    <!-- Color modes -->
    <script src="{{asset('assets/js/vendors/color-modes.js')}}"></script>

    <!-- Libs CSS -->
    <link href="{{asset('frontend/assets/libs/simplebar/dist/simplebar.min.css')}}" rel="stylesheet" />
    <link href="{{asset('frontend/assets/libs/bootstrap-icons/font/bootstrap-icons.min.css')}}" rel="stylesheet" />


    <!-- Box icons -->
    <link rel="stylesheet" href="{{asset('frontend/assets/fonts/css/boxicons.min.css')}}" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="{{asset('frontend/assets/css/theme.min.css')}}" />
    <!-- Analytics Code -->


    <title>Sign In v2 - </title>
</head>

<body>
    <main>
        <!--Pageheader start-->
        <div class="position-relative h-100">
            <div class="container d-flex flex-wrap justify-content-center vh-100 align-items-center w-lg-50 position-lg-absolute">
                <div class="row justify-content-center">
                    <div class="align-self-end col-md-9 col-11">
                        <div class=" mb-7">
                            <a href="{{url('/')}}"><img src="{{asset('management/images/ajp-logo.png')}}" alt="brand" class="mb-5 w-50" /></a>
                            <h2 class="mb-1 text-center">Register To Your Account</h2>
                            <p class="mb-0 text-center">
                                Your Own Digital Campaign
                                {{-- <a href="{{url('/register')}}" class="text-primary">Register here</a>--}}
                            </p>
                        </div>

                        <form method="POST" class="needs-validation mb-6" novalidate action="{{ route('register') }}">
                            @csrf
                            @if(isset($profile))
                            <input type="hidden" name="profile_id" value="{{ $profile->id }}">
                            @endif
                            <div class="row mb-3">

                                <div class="col-md-12">
                                    <input id="name" type="text" placeholder="Name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                                    @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <input id="email" type="email" placeholder="Email Address" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

                                    @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <input id="password" placeholder="Password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                    @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <input id="password-confirm" placeholder="Confirm Password" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                                </div>
                            </div>

                            <div class="row mb-0">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-dark w-100">
                                        {{ __('Register') }}
                                    </button>
                                </div>
                            </div>
                        </form>




                    </div>
                </div>
            </div>
            <div
                class="position-fixed top-0 end-0 w-50 h-100 d-none d-xl-block vh-100"
                style="background-image: url({{asset('management/images/authentication.png')}}); background-position: center; background-repeat: no-repeat; background-size: cover"></div>
        </div>

    </main>

    <!-- Libs JS -->
    <script src="{{asset('frontend/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('frontend/assets/libs/simplebar/dist/simplebar.min.js')}}"></script>
    <script src="{{asset('frontend/assets/libs/headhesive/dist/headhesive.min.js')}}"></script>

    <!-- Theme JS -->
    <script src="{{asset('frontend/assets/js/theme.min.js')}}"></script>

    <script src="{{asset('frontend/assets/js/vendors/password.js')}}"></script>
</body>


</html>