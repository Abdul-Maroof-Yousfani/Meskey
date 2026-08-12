<!DOCTYPE html>
<html class="loading" lang="en">
<!-- BEGIN : Head-->

<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description"
        content="Innovative Network ERP is a robust, user-friendly, and modern ERP solution designed to streamline business operations with unmatched flexibility and efficiency.">
    <meta name="keywords"
        content="Innovative Network ERP, business management software, ERP solution, powerful ERP system, enterprise resource planning, efficient operations">
    <meta name="author" content="Innovative Network">

    <title>@yield('title') - {{ config('app.name') }}</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('management') }}/app-assets/img/ico/favicon.ico">
    <link rel="shortcut icon" type="image/png" href="{{ asset('management') }}/app-assets/img/ico/favicon-32x32.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-touch-fullscreen" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link
        href="https://fonts.googleapis.com/css?family=Rubik:300,400,500,700,900%7CMontserrat:300,400,500,600,700,800,900"
        rel="stylesheet">
    <!-- BEGIN VENDOR CSS-->
    <!-- font icons-->
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/fonts/feather/style.min.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('management/app-assets/fonts/simple-line-icons/style.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('management/app-assets/fonts/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('management/app-assets/vendors/css/perfect-scrollbar.min.css') }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/vendors/css/prism.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/vendors/css/switchery.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/vendors/css/chartist.min.css') }}">
    <!-- END VENDOR CSS-->
    <!-- BEGIN APEX CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/css/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/css/bootstrap-extended.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/css/colors.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/css/components.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/css/themes/layout-dark.css') }}">
    <link rel="stylesheet" href="{{ asset('management/app-assets/css/plugins/switchery.css') }}">
    <link rel="stylesheet" href="{{ asset('management/app-assets/vendors/css/select2.min.css') }}">

    <link rel="stylesheet" type="text/css"
        href="{{ asset('management/app-assets/css/core/menu/horizontal-menu.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/css/pages/dashboard1.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('management/assets/css/style.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css') }}">
    <script src="{{ asset('assets/js/custom.js') }}"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

    <script>
        const SUBMISSION_ON_ENTER = @json(env('SUBMISSION_ON_ENTER', false));
        const IS_LOCAL = @json(env('IS_LOCAL', false));
        let purchaseRequestRowIndex = 1;
    </script>
    <style>
        .custom-switch .custom-control-label::before {
            background-color: #e9ecef; /* Default bootstrap grey */
        }
        .custom-switch .custom-control-input:checked ~ .custom-control-label::before {
            background-color: #007bff; /* Primary blue */
            border-color: #007bff;
        }
    </style>
</head>

<body
    class="horizontal-layout horizontal-menu horizontal-menu-padding 2-columns  navbar-sticky {{ Cookie::get('layout') === 'dark' ? 'layout-dark' : '' }}"
    data-open="hover" data-menu="horizontal-menu" data-col="2-columns">
    <div id="preloader" class="preloader-overlay" style="display: none;">
        <div class="preloader-spinner">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-light header-navbar navbar-fixed">
        <div class="container-fluid navbar-wrapper">
            <div class="navbar-header d-flex">
                <div class="navbar-toggle menu-toggle d-xl-none d-block float-left align-items-center justify-content-center"
                    data-toggle="collapse"><i class="ft-menu font-medium-3"></i></div>
                <ul class="navbar-nav">
                    <li class="nav-item mr-2 d-none d-lg-block"><a class="nav-link apptogglefullscreen"
                            id="navbar-fullscreen" href="javascript:;"><i class="ft-maximize font-medium-3"></i></a>
                    </li>
                    @if (getCurrentCompany())
                        <!-- <li class="nav-item nav-search"><a class="nav-link nav-link-search" href="javascript:"><i
                                    class="ft-search font-medium-3"></i></a>
                            <div class="search-input">
                                <div class="search-input-icon"><i class="ft-search font-medium-3"></i></div>
                                <input class="input" type="text" placeholder="Explore Apex..." tabindex="0"
                                    data-search="template-search">
                                <div class="search-input-close"><i class="ft-x font-medium-3"></i></div>
                                <ul class="search-list"></ul>
                            </div>
                        </li> -->
                    @endif
                </ul>
                <div class="navbar-brand-center">
                    <div class="navbar-header">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                @if (getCurrentCompany())
                                    <div class="logo">
                                        <a class="logo-text" href="{{ url('/') }}">
                                            <div class="logo-img">
                                                <img class="logo-img" alt="Apex logo"
                                                    src="{{ image_path(getCurrentCompany()->logo) }}">
                                            </div>
                                            {{-- <span class="text">{{ getCurrentCompany()->prefix }}</span> --}}
                                        </a>
                                    </div>
                                @else
                                    <div class="logo">
                                        <a class="logo-text" href="{{ url('/') }}">
                                            <div class="logo-img"><img class="logo-img" alt="Apex logo"
                                                    src="{{ image_path('management/app-assets/img/meskay-logo.png') }}">
                                            </div>
                                        </a>
                                    </div>
                                @endif
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="navbar-container">
                <div class="collapse navbar-collapse d-block" id="navbarSupportedContent">
                    <ul class="navbar-nav">

                        @if (getCurrentCompany())
                            @if (count(auth()->user()->companies) > 1)

                                <li class="nav-item position-relative mr-3">
                                    <button class="btn btn-primary  dropdown-toggle " href="javascript:;"
                                        data-toggle="dropdown">
                                        <i class="ft-refresh-cw"></i>
                                        {{ getCurrentCompany()->name }}
                                    </button>
                                    <ul
                                        class="notification-dropdown dropdown-menu dropdown-menu-media dropdown-menu-right m-0 overflow-hidden">
                                        <li class="dropdown-menu-header">
                                            <div
                                                class="dropdown-header d-flex justify-content-between m-0 px-3 py-2 white bg-primary">
                                                <div class="d-flex"><span class="noti-title">Switct Company</span>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="scrollable-container">
                                            @foreach (getUserAllCompanies(auth()->user()->id) as $v)
                                                <a class="d-flex justify-content-between"
                                                    {{ auth()->user()->current_company_id == $v->id ? 'disabled' : '' }}
                                                    href="{{ route('select.company', $v->app_key) }}">
                                                    <div class="media d-flex align-items-center">
                                                        <div class="media-left">
                                                            <div class="mr-3"><img class="avatar"
                                                                    src="{{ image_path($v->logo) }}" alt="avatar"
                                                                    height="45" width="45"></div>
                                                        </div>
                                                        <div class="media-body">
                                                            <h6 class="m-0"><span>{{ $v->name }}</span>
                                                                <small
                                                                    class="success lighten-1 font-italic float-right">
                                                                    <i class="{{ auth()->user()->current_company_id == $v->id ? 'ft-check active' : 'ft-arrow-up-right primary' }}"
                                                                        style="font-size: 30px;"></i>
                                                                </small>
                                                            </h6>
                                                            <small class="noti-text">Commented on your photo</small>
                                                        </div>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </li>

                                    </ul>
                                </li>
                            @endif
                        @endif


                        <li>
                            <div class="custom-switch custom-switch-primary custom-control-inline mb-1 mb-xl-0">
                                <input type="checkbox" class="custom-control-input" id="color-switch-1"
                                    {{ Cookie::get('layout') === 'dark' ? 'checked' : '' }}>
                                <label class="custom-control-label mr-1 color-mode-switch" for="color-switch-1">
                                    <span>Dark</span>
                                </label>
                            </div>
                        </li>

                        @if (getCurrentCompany())
                            <li class="nav-item mr-3 position-relative"><a
                                    class="nav-link dropdown-toggle dropdown-notification p-0 mt-2"
                                    id="dropdownBasic1" href="javascript:;"><i
                                        class="ft-bell font-medium-3"></i><span
                                        class="notification badge badge-pill badge-danger" id="notification-badge-count" style="display: none;">0</span></a>
                                <ul
                                    class="notification-dropdown dropdown-menu dropdown-menu-media dropdown-menu-right m-0 overflow-hidden" id="notification-dropdown-menu">
                                    <li class="dropdown-menu-header">
                                        <div class="dropdown-header d-flex justify-content-center m-0 px-3 py-2 white bg-primary">
                                            <span>Loading notifications...</span>
                                        </div>
                                    </li>
                                </ul>
                            </li>
                        @endif
                        <li class="dropdown nav-item mr-1"><a
                                class="nav-link dropdown-toggle user-dropdown d-flex align-items-end"
                                id="dropdownBasic2" href="javascript:;" data-toggle="dropdown">
                                <div class="user d-md-flex d-none mr-2">
                                    <span class="text-right">{{ auth()->user()->name }}</span><span
                                        class="text-right text-muted font-small-1">{{ auth()->user()->email }}</span>
                                </div>
                                <img class="avatar" src="{{ image_path(auth()->user()->profile_image) }}"
                                    alt="avatar" height="35" width="35">
                            </a>
                            <div class="dropdown-menu text-left dropdown-menu-right m-0 pb-0"
                                aria-labelledby="dropdownBasic2">

                                <a class="dropdown-item" href="{{ url('profile-settings') }}">
                                    <div class="d-flex align-items-center"><i class="ft-edit mr-2"></i><span>Edit
                                            Profile</span></div>
                                </a>

                                <div class="dropdown-divider"></div>

                                <form class="m-0" method="POST" action="{{ route('logouts') }}">
                                    @csrf
                                    <a href="#"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                        <div class="dropdown-item"><i
                                                class="ft-power mr-2"></i><span>{{ __('Logout') }}</span></div>

                                    </a>
                                </form>


                            </div>
                        </li>
                        {{-- <li class="nav-item d-none d-lg-block mr-2 mt-1"><a
                                class="nav-link notification-sidebar-toggle" href="javascript:;"><i
                                    class="ft-align-right font-medium-3"></i></a></li> --}}
                    </ul>
                </div>
            </div>
        </div>
    </nav>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Request desktop notification permission on page load
        if ("Notification" in window) {
            if (Notification.permission !== "granted" && Notification.permission !== "denied") {
                Notification.requestPermission();
            }
        }

        let previousLatestId = localStorage.getItem('previousLatestId');

        // 1. Fetch unread count every 1 minute
        function fetchUnreadCount() {
            $.ajax({
                url: "{{ route('notifications.count') }}",
                type: "GET",
                success: function(res) {
                    $('#notification-badge-count').text(res.count);
                    
                    if (res.count > 0) {
                        $('#notification-badge-count').show();
                        
                        // Check if we have a new notification to show on Desktop
                        if (res.latest && res.latest.id !== previousLatestId) {
                            if ("Notification" in window && Notification.permission === "granted") {
                                let desktopNoti = new Notification("Meskey", {
                                    body: res.latest.message
                                });
                                
                                desktopNoti.onclick = function() {
                                    window.focus();
                                    this.close();
                                };
                            }
                            previousLatestId = res.latest.id;
                            localStorage.setItem('previousLatestId', previousLatestId);
                        }
                    } else {
                        $('#notification-badge-count').hide();
                    }
                }
            });
        }

        
        // Use setTimeout so that it runs after jQuery is loaded
        setTimeout(function() {
            fetchUnreadCount();
            setInterval(fetchUnreadCount, 6000);

            // 2. Fetch dropdown items on click
            $('#dropdownBasic1').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                let menu = $('#notification-dropdown-menu');
                
                // Toggle visibility manually
                if (menu.hasClass('show')) {
                    menu.removeClass('show');
                    return;
                } else {
                    menu.addClass('show');
                }
                
                menu.html('<li class="dropdown-menu-header"><div class="dropdown-header d-flex justify-content-center m-0 px-3 py-2 white bg-primary"><span>Loading notifications...</span></div></li>');
                
                $.ajax({
                    url: "{{ route('notifications.dropdown') }}",
                    type: "GET",
                    success: function(res) {
                        menu.html(res.html);
                    }
                });
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#dropdownBasic1, #notification-dropdown-menu').length) {
                    $('#notification-dropdown-menu').removeClass('show');
                }
            });

            // 3. Mark all as read
            $(document).on('click', '.mark-all-read-btn', function() {
                $.ajax({
                    url: "{{ route('notifications.markAllRead') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        if (res.success) {
                            fetchUnreadCount();
                            // Keep dropdown open but reload its contents
                            $.ajax({
                                url: "{{ route('notifications.dropdown') }}",
                                type: "GET",
                                success: function(res2) {
                                    $('#notification-dropdown-menu').html(res2.html);
                                }
                            });
                        }
                    }
                });
            });

            /*
            // ============================================
            // PUSHER REAL-TIME BROADCAST SETUP
            // Uncomment the code below when Pusher is configured
            // ============================================
            if (typeof window.Echo !== 'undefined') {
                window.Echo.private('App.Models.User.' + "{{ auth()->id() }}")
                    .notification((notification) => {
                        let countElement = $('#notification-badge-count');
                        let currentCount = parseInt(countElement.text()) || 0;
                        countElement.text(currentCount + 1).show();
                    });
            }
            */
        }, 1000);
    });
</script>
