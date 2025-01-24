<!DOCTYPE html>
<html lang="en">
<head>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
        <meta name="description" content="">
        <title>Login - {{config('app.name')}}</title>
        <link rel="stylesheet" href="{{asset('assets/css/layout.css')}}">
        <link rel="stylesheet" href="{{asset('assets/css/style.css')}}">
        <link rel="stylesheet" href="{{asset('assets/css/icons.css')}}">
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
        <link rel="icon" href="favicon.ico" type="image/x-icon">
    </head>
</head>
<body>
<style>
    .invalid-feedback {
         display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 80%;
        color: #dc3545;
    }
</style>
<section class="LoginBanner justify-content-center" style="background-image:url(assets/images/banner/login-screen.png); ">

<!-- BEGIN: Content-->
<div class="app-content content ">
    <div>
        <div class="content-body">
            <div class="auth-wrapper auth-v1 px-2">
                <div class="auth-inner py-2">
                    <!-- Login v1 -->
                    <div class="mb-0">
                        <div class="card-body cus-st">
                            <div class="text-center ">
                                <h1 class="">Verify Your Account</h1>
                                <p class="card-text mb-2">Kindly Enter the 6-digit verification code sent to your email.</p>
                            </div>
                            <form method="POST" action="{{ route('two-factor.verify') }}">
                                @csrf

                                <div class="form-group row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="login-email" class="form-label ">Verification Code</label>
                                            <input id="code" type="text" class="form-control @error('code') is-invalid @enderror" name="code" required autocomplete="off" autofocus>
                                        </div>


                                        @error('code')
                                        <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row mb-0">
                                    <div class="col-md-6">
                                        <button type="submit" class="btn btn-theme btn-block ">
                                            {{ __('Verify') }}
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <a class="btn btn-theme btn-block" href="{{ route('verification.resend') }}">Resend</a>
                                    </div>

                                </div>
                            </form>


                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- END: Content-->
</section>
<script src="{{asset('assets/js/jquery.js')}}"></script>
<script src="{{asset('assets/js/custom.js')}}"></script>
</body>
</html>
