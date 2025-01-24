<!doctype html>
<html lang="en">
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

    <!-- Libs CSS -->
    <link href="{{ asset('frontend/assets/libs/simplebar/dist/simplebar.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('frontend/assets/libs/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet" />

    <!-- Scroll Cue -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/libs/scrollcue/scrollCue.css') }}" />

    <!-- Box icons -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/fonts/css/boxicons.min.css') }}" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/theme.min.css') }}" />
    <!-- Analytics Code -->
    <title>404 error - {{config('app.name')}}</title>
</head>
<body>
<main class="w-100">
    <!--404 error start-->
    <div class="container overflow-hidden">
        <div class="row align-items-center justify-content-center min-vh-100 text-center">
            <div class="col-lg-6 col-12">
                <div class="position-relative mb-7">
                    <div class="scene d-none d-lg-block" data-relative-input="true">
                        <div class="position-absolute top-0" data-depth="0.5">
                            <img src="{{asset('frontend/assets/images/error/stars.svg')}}" alt />
                        </div>
                    </div>
                    <div class="scene d-none d-lg-block" data-relative-input="true">
                        <div class="position-absolute" data-depth="0.1">
                            <img src="{{asset('frontend/assets/images/error/rocket.svg')}}" alt />
                        </div>
                    </div>
                    <div class="scene d-none d-lg-block" data-relative-input="true">
                        <div class="position-absolute top-0 start-50 translate-middle" style="margin-top: -80px; margin-left: -80px" data-depth="0.1">
                            <img src="{{asset('frontend/assets/images/error/globe.svg')}}" alt />
                        </div>
                    </div>
                    <div class="scene d-none d-lg-block" data-relative-input="true">
                        <div class="position-absolute start-50" data-depth="0.1">
                            <img src="{{asset('frontend/assets/images/error/astronut.svg')}}" alt style="top: -110px; position: absolute; bottom: 0" />
                        </div>
                    </div>
                    <div class="position-relative z-n1">
                        <img src="{{asset('frontend/assets/images/error/404-number.svg')}}" alt class="img-fluid" />
                    </div>
                    <div class="scene d-none d-lg-block" data-relative-input="true">
                        <div class="position-absolute start-100 bottom-0" style data-depth="0.1">
                            <img src="{{asset('frontend/assets/images/error/planet.svg')}}" alt />
                        </div>
                    </div>
                </div>

                <h2>Oops page not found</h2>
                <p>The page you are looking for is not available.</p>

                <a href="{{url('/')}}" class="btn btn-primary">Go back to home</a>
            </div>
        </div>
    </div>
    <!--404 error end-->
</main>
<!-- Libs JS -->
<script src="{{asset('frontend/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('frontend/assets/libs/simplebar/dist/simplebar.min.js')}}"></script>
<script src="{{asset('frontend/assets/libs/headhesive/dist/headhesive.min.js')}}"></script>

<!-- Theme JS -->
<script src="{{asset('frontend/assets/js/theme.min.js')}}"></script>
</body>
</html>
