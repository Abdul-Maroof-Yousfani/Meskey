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
<section class="LoginBanner" style="background-image:url(assets/images/banner/login-screen.png); ">
    <div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div>
        <div class="content-header row">
        </div>
        <div class="content-body">
            <div class="auth-wrapper auth-v1 px-2">
                <div class="auth-inner py-2">
                    <div class="row">
                        <div class="col-md-12 mt-4">

                            <div class="card-body" style="text-align: center;">
                                <h4 class="card-heading text-center">{{ __('Verify From Google Authenticator') }} </h4>
                                <p> Please enter the 6-digit code from the Google Authenticator app for the verification of the QR code you scanned. You will be unable to login otherwise</p>

                                <div>
                                    <form id="googleAuthForm" method="POST" action="{{ url('/setup-google-authenticator-code') }}">

                                        @csrf

                                        <div class="form-group row">
                                            <label for="2fa_code" class="col-md-4 col-form-label text-md-right">{{ __('6-digit Code') }}</label>

                                            <div class="col-md-6">
                                                <input id="2fa_code" type="text" class="form-control @error('2fa_code') is-invalid @enderror" name="2fa_code" required autofocus>
                                                <span style="color:red;">
                                                    <strong id="msg"></strong>
                                                </span>
                                                <span style="color:green;">
                                                    <strong id="suucess_msg"></strong>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-group row mb-0">
                                            <div class="col-md-6 offset-md-4">
                                                <button  id="verifyBtn" type="button" class="btn btn-primary">
                                                    {{ __('Verify') }}
                                                </button>
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
    </div>
</div>
    <script src="{{asset('assets/js/jquery.js')}}"></script>
    <script src="{{asset('assets/js/custom.js')}}"></script>
    <script>
        $(document).ready(function () {
            $('#verifyBtn').on('click', function () {
                $('#msg').html('');
                $('#suucess_msg').html('');
                // Serialize the form data
                var formData = $('#googleAuthForm').serialize();

                // Send an AJAX request
                $.ajax({
                    url: '{{ url('/setup-google-authenticator-code') }}',
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function (response) {
                        if(response.error != ''){
                            $('#suucess_msg').html('');
                            $('#msg').html(response.error);
                        }
                        if(response.success != ''){
                            $('#msg').html('');
                            $('#suucess_msg').html(response.success);

                            window.location.href = '{{ url('home') }}';
                        }

                    },
                    error: function (xhr) {
                        // Handle error response
                        $('#msg').html(xhr.responseJSON.error);
                        // alert(xhr.responseJSON.error); // or update the UI accordingly
                    }
                });
            });
        });
    </script>

</body>
</html>
