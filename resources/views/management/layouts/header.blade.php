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
    <!-- END APEX CSS-->
    <!-- BEGIN Page Level CSS-->
    <link rel="stylesheet" type="text/css"
        href="{{ asset('management/app-assets/css/core/menu/horizontal-menu.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/css/pages/dashboard1.css') }}">
    <!-- END Page Level CSS-->
    <!-- BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('management/assets/css/style.css') }}">
    <!-- END: Custom CSS-->
</head>
<!-- END : Head-->

<!-- BEGIN : Body-->



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
                        <li class="nav-item nav-search"><a class="nav-link nav-link-search" href="javascript:"><i
                                    class="ft-search font-medium-3"></i></a>
                            <div class="search-input">
                                <div class="search-input-icon"><i class="ft-search font-medium-3"></i></div>
                                <input class="input" type="text" placeholder="Explore Apex..." tabindex="0"
                                    data-search="template-search">
                                <div class="search-input-close"><i class="ft-x font-medium-3"></i></div>
                                <ul class="search-list"></ul>
                            </div>
                        </li>
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

            <!-- BEGIN : Activities Bar-->
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




                                                            </h6><small class="noti-text">Commented on your
                                                                photo</small>

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
                                <label class="custom-control-label mr-1" for="color-switch-1">
                                    <span>Dark</span>
                                </label>
                            </div>
                        </li>

                        @if (getCurrentCompany())
                            <li class="dropdown nav-item"><a
                                    class="nav-link dropdown-toggle dropdown-notification p-0 mt-2"
                                    id="dropdownBasic1" href="javascript:;" data-toggle="dropdown"><i
                                        class="ft-bell font-medium-3"></i><span
                                        class="notification badge badge-pill badge-danger">4</span></a>
                                <ul
                                    class="notification-dropdown dropdown-menu dropdown-menu-media dropdown-menu-right m-0 overflow-hidden">



                                    <li class="dropdown-menu-header">
                                        <div
                                            class="dropdown-header d-flex justify-content-between m-0 px-3 py-2 white bg-primary">
                                            <div class="d-flex"><i
                                                    class="ft-bell font-medium-3 d-flex align-items-center mr-2"></i><span
                                                    class="noti-title">7 New Notification</span></div><span
                                                class="text-bold-400 cursor-pointer">Mark all as read</span>
                                        </div>
                                    </li>
                                    <li class="scrollable-container"><a class="d-flex justify-content-between"
                                            href="javascript:void(0)">
                                            <div class="media d-flex align-items-center">
                                                <div class="media-left">
                                                    <div class="mr-3"><img class="avatar"
                                                            src="../../../app-assets/img/portrait/small/avatar-s-20.png"
                                                            alt="avatar" height="45" width="45"></div>
                                                </div>
                                                <div class="media-body">
                                                    <h6 class="m-0"><span>Kate Young</span><small
                                                            class="grey lighten-1 font-italic float-right">5 mins
                                                            ago</small></h6><small class="noti-text">Commented on your
                                                        photo</small>
                                                    <h6 class="noti-text font-small-3 m-0">Great Shot John! Really
                                                        enjoying
                                                        the composition on this piece.</h6>
                                                </div>
                                            </div>
                                        </a><a class="d-flex justify-content-between" href="javascript:void(0)">
                                            <div class="media d-flex align-items-center">
                                                <div class="media-left">
                                                    <div class="mr-3"><img class="avatar"
                                                            src="../../../app-assets/img/portrait/small/avatar-s-11.png"
                                                            alt="avatar" height="45" width="45"></div>
                                                </div>
                                                <div class="media-body">
                                                    <h6 class="m-0"><span>Andrew Watts</span><small
                                                            class="grey lighten-1 font-italic float-right">49 mins
                                                            ago</small></h6><small class="noti-text">Liked your album:
                                                        UI/UX Inspo</small>
                                                </div>
                                            </div>
                                        </a><a class="d-flex justify-content-between read-notification"
                                            href="javascript:void(0)">
                                            <div class="media d-flex align-items-center py-0 pr-0">
                                                <div class="media-left">
                                                    <div class="mr-3"><img
                                                            src="../../../app-assets/img/icons/sketch-mac-icon.png"
                                                            alt="avatar" height="45" width="45"></div>
                                                </div>
                                                <div class="media-body">
                                                    <h6 class="m-0">Update</h6><small class="noti-text">MyBook
                                                        v2.0.7</small>
                                                </div>
                                                <div class="media-right">
                                                    <div class="border-left">
                                                        <div class="px-4 py-2 border-bottom">
                                                            <h6 class="m-0 text-bold-600">Update</h6>
                                                        </div>
                                                        <div class="px-4 py-2 text-center">
                                                            <h6 class="m-0">Close</h6>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a><a class="d-flex justify-content-between read-notification"
                                            href="javascript:void(0)">
                                            <div class="media d-flex align-items-center">
                                                <div class="media-left">
                                                    <div class="avatar bg-primary bg-lighten-3 mr-3 p-1"><span
                                                            class="avatar-content font-medium-2">LD</span></div>
                                                </div>
                                                <div class="media-body">
                                                    <h6 class="m-0"><span>Registration done</span><small
                                                            class="grey lighten-1 font-italic float-right">6 hrs
                                                            ago</small></h6>
                                                </div>
                                            </div>
                                        </a>
                                        <div class="cursor-pointer">
                                            <div class="media d-flex align-items-center justify-content-between">
                                                <div class="media-left">
                                                    <div class="media-body">
                                                        <h6 class="m-0">New Offers</h6>
                                                    </div>
                                                </div>
                                                <div class="media-right">
                                                    <div class="custom-control custom-switch">
                                                        <input class="switchery" type="checkbox" checked
                                                            id="notificationSwtich" data-size="sm">
                                                        <label for="notificationSwtich"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between cursor-pointer read-notification">
                                            <div class="media d-flex align-items-center">
                                                <div class="media-left">
                                                    <div class="avatar bg-danger bg-lighten-4 mr-3 p-1"><span
                                                            class="avatar-content font-medium-2"><i
                                                                class="ft-heart text-danger"></i></span></div>
                                                </div>
                                                <div class="media-body">
                                                    <h6 class="m-0"><span>Application approved</span><small
                                                            class="grey lighten-1 font-italic float-right">18 hrs
                                                            ago</small></h6>
                                                </div>
                                            </div>
                                        </div><a class="d-flex justify-content-between read-notification"
                                            href="javascript:void(0)">
                                            <div class="media d-flex align-items-center">
                                                <div class="media-left">
                                                    <div class="mr-3"><img class="avatar"
                                                            src="{{ asset('management/app-assets/img/portrait/small/avatar-s-6.png') }}"
                                                            alt="avatar" height="45" width="45"></div>
                                                </div>
                                                <div class="media-body">
                                                    <h6 class="m-0"><span>Anna Lee</span><small
                                                            class="grey lighten-1 font-italic float-right">27 hrs
                                                            ago</small></h6><small class="noti-text">Commented on your
                                                        photo</small>
                                                    <h6 class="noti-text font-small-3 text-bold-500 m-0">Woah!Loving
                                                        these
                                                        colors! Keep it up</h6>
                                                </div>
                                            </div>
                                        </a>
                                        <div class="d-flex justify-content-between cursor-pointer read-notification">
                                            <div class="media d-flex align-items-center">
                                                <div class="media-left">
                                                    <div class="avatar bg-info bg-lighten-4 mr-3 p-1">
                                                        <div class="avatar-content font-medium-2"><i
                                                                class="ft-align-left text-info"></i></div>
                                                    </div>
                                                </div>
                                                <div class="media-body">
                                                    <h6 class="m-0"><span>Report generated</span><small
                                                            class="grey lighten-1 font-italic float-right">35 hrs
                                                            ago</small></h6>
                                                </div>
                                            </div>
                                        </div><a class="d-flex justify-content-between read-notification"
                                            href="javascript:void(0)">
                                            <div class="media d-flex align-items-center">
                                                <div class="media-left">
                                                    <div class="mr-3"><img class="avatar"
                                                            src="../../../app-assets/img/portrait/small/avatar-s-7.png"
                                                            alt="avatar" height="45" width="45"></div>
                                                </div>
                                                <div class="media-body">
                                                    <h6 class="m-0"><span>Oliver Wright</span><small
                                                            class="grey lighten-1 font-italic float-right">2 days
                                                            ago</small></h6><small class="noti-text">Liked your album:
                                                        UI/UX Inspo</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li class="dropdown-menu-footer">
                                        <div
                                            class="noti-footer text-center cursor-pointer primary border-top text-bold-400 py-1">
                                            Read All Notifications</div>
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
