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
    <link rel="stylesheet" type="text/css" href="{{ asset('management/app-assets/vendors/css/toastr.css') }}">
    <script src="{{ asset('management/app-assets/vendors/js/toastr.min.js') }}"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

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

        /* ===== Side nav overlay =====
           .main-panel (components.css) par "z-index: 2" hai jo apna stacking context banata hai.
           Sidebar (z-index 998) .main-panel ke andar hai, overlay bahar - is liye overlay ya to sab ke
           peeche jata tha ya sidebar ke upar bhi aa jata tha. Overlay-menu mode (mobile/tablet) me
           .main-panel ka stacking context hata do, taake order ho: sidebar 998 > overlay 997 > content. */
        body.vertical-overlay-menu .main-panel {
            z-index: auto;
        }
        body.vertical-overlay-menu .sidenav-overlay {
            z-index: 997;
        }

        /* =====================================================================
           Side nav (mobile/tablet overlay mode) - naya clean design, theme blue
           ===================================================================== */
        /* Width: theme me 250px fix tha (width, left aur open translate teeno me) - teeno ek saath badlo.
           Chhoti screen pe 85% se zyada nahi, taake overlay ka kuch hissa dikhe aur tap se band ho sake. */
        body.vertical-overlay-menu {
            --sidenav-w: min(300px, 85vw);
        }
        body.vertical-overlay-menu .main-menu.app-sidebar {
            width: var(--sidenav-w) !important;
            left: calc(-1 * var(--sidenav-w)) !important;
        }
        body.vertical-overlay-menu.menu-open .main-menu.app-sidebar {
            transform: translate3d(var(--sidenav-w), 0, 0) !important;
        }

        body.vertical-overlay-menu .main-menu.app-sidebar {
            background: #fff !important;
            border-radius: 0;
            border-right: 1px solid #ececf2;
            box-shadow: 10px 0 30px rgba(20, 30, 60, .18);
            overflow: hidden;
        }
        body.vertical-overlay-menu.menu-hide .main-menu.app-sidebar { box-shadow: none; }

        /* Upar chhota "MENU" label */
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation::before {
            content: "MENU";
            display: block;
            padding: 18px 22px 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.4px;
            color: #9a98a6;
        }
        /* neeche floating bottom nav ke liye jagah */
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation {
            padding-bottom: 110px;
        }

        /* Theme ki laal patti / slide wala effect hatao */
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li > a::before { display: none !important; }
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li.sidebar-group-active:not(.open) {
            border-right: 0 !important;
            background: transparent !important;
        }

        /* --- Top level items --- */
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation > li > a {
            display: flex !important;
            align-items: center;
            margin: 3px 12px;
            padding: 8px 34px 8px 8px;
            line-height: 1.4;
            border-radius: 14px;
            border: 1px solid transparent;
            font-size: 14px;
            font-weight: 500;
            color: #3d3c4a !important;
            background: transparent;
            transition: background .2s, color .2s, border-color .2s;
        }
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation > li > a > i {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            margin-right: 12px;
            border-radius: 11px;
            font-size: 1rem;
            color: #27489a !important;
            background: rgba(39, 72, 154, .08);
            transition: background .2s, color .2s;
        }
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation > li > a > span {
            overflow: hidden;
            text-overflow: ellipsis;
        }
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation > li:hover > a {
            background: rgba(39, 72, 154, .05);
        }

        /* Open (submenu khula) */
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation > li.open > a {
            color: #27489a !important;
            font-weight: 600;
            background: rgba(39, 72, 154, .07);
        }

        /* Active: bottom nav jaisa tinted pill + bhara hua icon */
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation > li.active > a,
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation > li.sidebar-group-active > a {
            color: #27489a !important;
            font-weight: 600;
            background: linear-gradient(180deg, rgba(39, 72, 154, .14), rgba(39, 72, 154, .06));
            border-color: rgba(39, 72, 154, .22);
        }
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation > li.active > a > i,
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation > li.sidebar-group-active > a > i {
            color: #fff !important;
            background: linear-gradient(135deg, #4a6fd1, #27489a);
            box-shadow: 0 4px 10px rgba(39, 72, 154, .30);
        }

        /* Arrow (chevron) - beech me, khulne pe ghoomta hai.
           <a> ko relative do, warna arrow <li> ke hisaab se position hota hai aur
           submenu khulne par (li lamba hone se) neeche khisak jata hai. */
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li.has-sub > a {
            position: relative;
        }
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li.has-sub > a:not(.mm-next)::after {
            content: "\e844" !important;
            font-family: "feather" !important;
            font-size: .95rem;
            position: absolute;
            right: 14px;
            top: 50% !important;
            transform: translateY(-50%) rotate(0deg);
            color: #a5a3b0;
            transition: transform .2s ease;
        }
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li.open > a:not(.mm-next)::after {
            transform: translateY(-50%) rotate(90deg);
            color: #27489a;
        }

        /* --- Submenu --- */
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul {
            margin: 2px 12px 8px 29px;
            padding: 2px 0 2px 10px;
            border-left: 2px solid rgba(39, 72, 154, .12);
            background: transparent !important;
        }
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li a {
            display: flex !important;
            align-items: center;
            margin: 2px 0;
            padding: 8px 30px 8px 10px !important;
            line-height: 1.35;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            color: #5d5b6a !important;
            background: transparent;
            white-space: normal;
        }
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li a:hover {
            background: rgba(39, 72, 154, .05);
            color: #27489a !important;
        }
        /* submenu ka teer wala icon -> chhota dot */
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li a > i.submenu-icon {
            flex-shrink: 0;
            width: 6px;
            height: 6px;
            margin-right: 10px;
            border-radius: 50%;
            font-size: 0 !important;
            background: #c3c9dc;
        }
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li a > i.submenu-icon::before { content: none; }
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li.active > a,
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li.open > a {
            color: #27489a !important;
            font-weight: 600;
            background: rgba(39, 72, 154, .08);
        }
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li.active > a > i.submenu-icon,
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li.open > a > i.submenu-icon {
            background: #27489a;
            box-shadow: 0 0 0 3px rgba(39, 72, 154, .15);
        }
        /* 3rd level */
        body.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li ul {
            margin: 2px 0 6px 13px;
        }

        /* --- Dark mode --- */
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar {
            background: #18181b !important;
            border-right-color: #2a2a30;
            box-shadow: 10px 0 30px rgba(0, 0, 0, .5);
        }
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation::before { color: #77757f; }
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation > li > a { color: #d6d6dc !important; }
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation > li > a > i {
            color: #9db4f0 !important;
            background: rgba(90, 130, 230, .14);
        }
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation > li:hover > a,
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation > li.open > a {
            background: rgba(90, 130, 230, .10);
            color: #c9d6f7 !important;
        }
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation > li.active > a,
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation > li.sidebar-group-active > a {
            color: #c9d6f7 !important;
            background: linear-gradient(180deg, rgba(90, 130, 230, .24), rgba(90, 130, 230, .10));
            border-color: rgba(90, 130, 230, .35);
        }
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation > li.active > a > i,
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation > li.sidebar-group-active > a > i {
            color: #fff !important;
            background: linear-gradient(135deg, #5a82e6, #27489a);
        }
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation li.open > a:not(.mm-next)::after { color: #9db4f0; }
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul {
            border-left-color: rgba(90, 130, 230, .20);
        }
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li a { color: #a9a8b3 !important; }
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li a:hover,
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li.active > a,
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li.open > a {
            color: #c9d6f7 !important;
            background: rgba(90, 130, 230, .12);
        }
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li a > i.submenu-icon { background: #4a4a55; }
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li.active > a > i.submenu-icon,
        body.layout-dark.vertical-overlay-menu .main-menu.app-sidebar .navigation li ul li.open > a > i.submenu-icon {
            background: #9db4f0;
        }

        /* ===== Header ko thori saans do (upar/neeche padding) - sirf mobile ===== */
        @media (max-width: 767.98px) {
            nav.header-navbar .container-fluid.navbar-wrapper {
                padding-top: 6px;
                padding-bottom: 6px;
            }
        }

        /* ===== Round header icon buttons (theme toggle + notification) ===== */
        .theme-toggle-btn,
        .header-navbar .header-icon-btn {
            position: relative;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            margin: 0 !important;
            padding: 0 !important;
            border-radius: 50%;
            /* bottom nav ke active pill jaisa halka theme-blue shade */
            border: 1px solid rgba(39, 72, 154, .25);
            background: linear-gradient(180deg, rgba(39, 72, 154, .12), rgba(39, 72, 154, .05));
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
            color: #27489a;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .6), 0 2px 6px rgba(39, 72, 154, .10);
            transition: background .2s, border-color .2s, color .2s, box-shadow .2s;
        }
        /* hover pe thora sa dark */
        .theme-toggle-btn:hover,
        .header-navbar .header-icon-btn:hover {
            color: #1d3878;
            border-color: rgba(39, 72, 154, .45);
            background: linear-gradient(180deg, rgba(39, 72, 154, .22), rgba(39, 72, 154, .12));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5), 0 3px 10px rgba(39, 72, 154, .20);
        }
        /* theme (bootstrap-extended.css) .dropdown-notification par margin-top: 1.2rem !important lagata hai - usy override karo */
        .header-navbar .navbar-container ul.navbar-nav li .dropdown-notification.header-icon-btn {
            margin: 0 !important;
        }
        .header-navbar .header-icon-btn::after { display: none !important; } /* dropdown caret hatao */
        .header-navbar .header-icon-btn i { font-size: 1rem; margin: 0; }

        /* notification badge chhota sa, button ke corner par */
        .header-navbar .header-icon-btn .notification {
            position: absolute;
            top: -5px;
            right: -6px;
            min-width: 17px;
            height: 17px;
            padding: 0 4px;
            font-size: 10px;
            line-height: 17px;
            border-radius: 9px;
            border: 2px solid #fff;
            box-sizing: content-box;
        }

        .theme-toggle-btn .icon-sun { display: none; color: #f6b100; }
        #color-switch-1:checked + .theme-toggle-btn .icon-moon { display: none; }
        #color-switch-1:checked + .theme-toggle-btn .icon-sun { display: inline-block; }

        body.layout-dark .theme-toggle-btn,
        body.layout-dark .header-navbar .header-icon-btn {
            background: linear-gradient(180deg, rgba(90, 130, 230, .20), rgba(90, 130, 230, .10));
            border-color: rgba(90, 130, 230, .35);
            color: #9db4f0;
            box-shadow: none;
        }
        body.layout-dark .theme-toggle-btn:hover,
        body.layout-dark .header-navbar .header-icon-btn:hover {
            color: #c9d6f7;
            border-color: rgba(90, 130, 230, .55);
            background: linear-gradient(180deg, rgba(90, 130, 230, .32), rgba(90, 130, 230, .18));
        }
        body.layout-dark .header-navbar .header-icon-btn .notification { border-color: #2a2a2e; }

        /* ===== Mobile header + bottom nav ===== */
        .mobile-bottom-nav { display: none; }

        @media (max-width: 767.98px) {
            .mobile-user-dropdown .avatar {
                border-radius: 50%;
                object-fit: cover;
            }
            .mobile-user-dropdown > a { text-decoration: none; }
            .mobile-user-greet {
                display: flex;
                flex-direction: column;
                justify-content: center;
                margin-left: 8px;
                line-height: 1.2;
                min-width: 0;
                max-width: 140px;
            }
            .mobile-user-greet small {
                font-size: 11px;
                color: #8a8a8a;
            }
            .mobile-user-greet strong {
                font-size: 13px;
                font-weight: 600;
                color: #342e49;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            body.layout-dark .mobile-user-greet small { color: #9a9a9a; }
            body.layout-dark .mobile-user-greet strong { color: #e6e6e6; }

            .mobile-user-dropdown .dropdown-menu {
                left: 0 !important;
                right: auto !important;
                min-width: 200px;
            }

            /* Floating, frosted-glass bottom bar */
            .mobile-bottom-nav {
                display: flex;
                position: fixed;
                left: 12px;
                right: 12px;
                bottom: calc(10px + env(safe-area-inset-bottom));
                height: 66px;
                padding: 6px;
                gap: 6px;
                z-index: 1020;
                align-items: stretch;
                border-radius: 22px;
                background: rgba(255, 255, 255, .82);
                -webkit-backdrop-filter: blur(14px) saturate(160%);
                backdrop-filter: blur(14px) saturate(160%);
                border: 1px solid rgba(255, 255, 255, .7);
                box-shadow: 0 8px 28px rgba(30, 20, 60, .14), 0 2px 6px rgba(30, 20, 60, .06);
            }
            .mobile-bottom-nav .mbn-item {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 4px;
                border-radius: 16px;
                border: 1px solid transparent;
                color: #7b7b86;
                font-size: 12px;
                font-weight: 500;
                text-decoration: none;
                transition: background-color .2s, color .2s, border-color .2s, transform .15s;
                -webkit-tap-highlight-color: transparent;
            }
            .mobile-bottom-nav .mbn-item i { font-size: 1.3rem; line-height: 1; }
            .mobile-bottom-nav .mbn-item:active { transform: scale(.95); }

            /* Active item: tinted pill (reference jaisa) */
            .mobile-bottom-nav .mbn-item.active,
            body.menu-open .mobile-bottom-nav .mbn-item.mbn-menu {
                color: #27489a;
                font-weight: 700;
                background: linear-gradient(180deg, rgba(39, 72, 154, .16), rgba(39, 72, 154, .08));
                border-color: rgba(39, 72, 154, .30);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, .6), 0 2px 8px rgba(39, 72, 154, .15);
            }
            /* menu khula ho to baaki items ka active highlight hata do */
            body.menu-open .mobile-bottom-nav .mbn-item.active:not(.mbn-menu) {
                background: transparent;
                border-color: transparent;
                box-shadow: none;
                color: #7b7b86;
                font-weight: 500;
            }

            body.layout-dark .mobile-bottom-nav {
                background: rgba(32, 32, 36, .85);
                border-color: rgba(255, 255, 255, .08);
                box-shadow: 0 8px 28px rgba(0, 0, 0, .55);
            }
            body.layout-dark .mobile-bottom-nav .mbn-item { color: #a9a9b3; }
            body.layout-dark .mobile-bottom-nav .mbn-item.active,
            body.layout-dark.menu-open .mobile-bottom-nav .mbn-item.mbn-menu {
                color: #9db4f0;
                background: linear-gradient(180deg, rgba(90, 130, 230, .26), rgba(90, 130, 230, .12));
                border-color: rgba(90, 130, 230, .40);
                box-shadow: none;
            }
            body.layout-dark.menu-open .mobile-bottom-nav .mbn-item.active:not(.mbn-menu) {
                color: #a9a9b3;
            }

            /* content ko bottom nav ke peeche chhupne se bachane ke liye */
            body { padding-bottom: calc(96px + env(safe-area-inset-bottom)); }

            /* ===== Notification popup: mobile par full screen ===== */
            body.noti-open { overflow: hidden; }
            body.noti-open nav.header-navbar { z-index: 1065 !important; }
            body.noti-open .mobile-bottom-nav { display: none; }

            #notification-dropdown-menu.show {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                width: 100vw !important;
                height: 100vh;
                height: 100dvh;
                max-height: none !important;
                min-width: 0 !important;
                margin: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                display: flex !important;
                flex-direction: column;
                z-index: 1066;
                background: #f4f5fa;
                animation: notiSlideUp .25s ease-out;
            }
            @keyframes notiSlideUp {
                from { transform: translateY(40px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }

            /* ---------- Top app bar (white, clean) ---------- */
            #notification-dropdown-menu.show > .dropdown-menu-header {
                flex-shrink: 0;
                position: relative;
                z-index: 2;
            }
            #notification-dropdown-menu.show .dropdown-header {
                background: #fff !important;
                color: #2b2a35 !important;
                padding: 14px 16px !important;
                padding-top: calc(14px + env(safe-area-inset-top)) !important;
                border-bottom: 1px solid #ececf2;
                box-shadow: 0 4px 14px rgba(30, 20, 60, .05);
                justify-content: flex-start !important;
            }
            #notification-dropdown-menu.show .dropdown-header > .d-flex {
                flex: 1;
                min-width: 0;
                flex-direction: column;
                align-items: flex-start !important;
            }
            /* bara "Notifications" heading, purana count subtitle ban jata hai */
            #notification-dropdown-menu.show .dropdown-header > .d-flex::before {
                content: "Notifications";
                font-size: 1.25rem;
                font-weight: 700;
                letter-spacing: -.2px;
                line-height: 1.2;
                color: #2b2a35;
            }
            #notification-dropdown-menu.show .dropdown-header > .d-flex > i { display: none; }
            #notification-dropdown-menu.show .noti-title {
                font-size: .78rem;
                font-weight: 500 !important;
                color: #8a8797;
                margin-top: 2px;
            }
            #notification-dropdown-menu.show .dropdown-header .mark-all-read-btn {
                flex-shrink: 0;
                color: #27489a !important;
                text-decoration: none !important;
                opacity: 1 !important;
                font-size: .75rem !important;
                font-weight: 600;
                padding: 7px 12px;
                border-radius: 20px;
                background: rgba(39, 72, 154, .10);
                white-space: nowrap;
            }
            #notification-dropdown-menu.show .noti-mobile-back {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                width: 38px;
                height: 38px;
                margin-right: 12px;
                border-radius: 12px;
                background: #f3f2f8;
                color: #2b2a35;
                font-size: 1.15rem;
                text-decoration: none;
            }

            /* ---------- List ---------- */
            #notification-dropdown-menu.show > .scrollable-container {
                flex: 1 1 auto;
                max-height: none !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch;
                background: transparent !important;
                padding: 14px 14px 8px;
            }
            #notification-dropdown-menu.show .notification-row {
                position: relative;
                margin-bottom: 10px;
                border: 1px solid #efeff4 !important;
                border-radius: 16px;
                background-color: #fff !important;
                box-shadow: 0 2px 8px rgba(30, 20, 60, .04);
                overflow: hidden;
                transition: transform .15s;
            }
            #notification-dropdown-menu.show .notification-row:active { transform: scale(.985); }
            #notification-dropdown-menu.show .notification-row .media {
                padding: 14px 14px 12px !important;
                align-items: flex-start !important;
            }
            /* left side gol bell icon */
            #notification-dropdown-menu.show .notification-row .media::before {
                content: "\e836";
                font-family: "feather";
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                margin-right: 12px;
                border-radius: 12px;
                font-size: 1.05rem;
                color: #8a8797;
                background: #f3f2f8;
            }
            #notification-dropdown-menu.show .notification-row .media-body { min-width: 0; }
            #notification-dropdown-menu.show .notification-row .noti-text {
                font-size: .88rem !important;
                color: #4a4858 !important;
                line-height: 1.5 !important;
                padding-right: 14px;
                word-break: break-word;
            }
            /* time: left side, clock icon ke saath */
            #notification-dropdown-menu.show .notification-row .text-right {
                text-align: left !important;
                margin-top: 6px !important;
            }
            #notification-dropdown-menu.show .notification-row .text-right small {
                font-style: normal !important;
                font-size: .72rem !important;
                color: #9a98a6 !important;
            }
            #notification-dropdown-menu.show .notification-row .text-right small::before {
                content: "\e84d";
                font-family: "feather";
                margin-right: 4px;
                vertical-align: -1px;
            }

            /* unread: purple icon, halka tint aur right corner pe dot */
            #notification-dropdown-menu.show .notification-row[style*="rgba(39, 72, 154"] {
                background-color: #f4f7fd !important;
                border-color: rgba(39, 72, 154, .22) !important;
            }
            #notification-dropdown-menu.show .notification-row[style*="rgba(39, 72, 154"] .media::before {
                color: #fff;
                background: linear-gradient(135deg, #4a6fd1, #27489a);
                box-shadow: 0 4px 10px rgba(39, 72, 154, .30);
            }
            #notification-dropdown-menu.show .notification-row[style*="rgba(39, 72, 154"] .noti-text {
                color: #2b2a35 !important;
            }
            #notification-dropdown-menu.show .notification-row[style*="rgba(39, 72, 154"]::after {
                content: "";
                position: absolute;
                top: 16px;
                right: 14px;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #27489a;
                box-shadow: 0 0 0 3px rgba(39, 72, 154, .15);
            }

            /* Empty state */
            #notification-dropdown-menu.show > .scrollable-container > div.d-flex {
                flex-direction: column;
                height: 100%;
                padding: 40px 20px !important;
            }
            #notification-dropdown-menu.show > .scrollable-container > div.d-flex::before {
                content: "\e833";
                font-family: "feather";
                display: flex;
                align-items: center;
                justify-content: center;
                width: 76px;
                height: 76px;
                margin-bottom: 14px;
                border-radius: 50%;
                font-size: 2rem;
                color: #4a6fd1;
                background: rgba(39, 72, 154, .10);
            }
            #notification-dropdown-menu.show > .scrollable-container > div.d-flex span {
                font-size: .95rem !important;
                font-weight: 500;
            }

            /* ---------- Footer: "Show All" bara button ---------- */
            #notification-dropdown-menu.show > .dropdown-menu-footer {
                flex-shrink: 0;
                background: #fff !important;
                border-top: 1px solid #ececf2 !important;
                padding-bottom: env(safe-area-inset-bottom);
            }
            #notification-dropdown-menu.show > .dropdown-menu-footer > div {
                padding: 12px 14px !important;
            }
            /* "Read All" upar header me pehle se hai, is liye yahan chhupa do */
            #notification-dropdown-menu.show > .dropdown-menu-footer .mark-all-read-btn { display: none; }
            #notification-dropdown-menu.show > .dropdown-menu-footer a:not(.mark-all-read-btn) {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 46px;
                border-radius: 14px;
                font-size: .92rem !important;
                color: #fff !important;
                background: linear-gradient(135deg, #4a6fd1, #27489a);
                box-shadow: 0 6px 16px rgba(39, 72, 154, .28);
            }

            /* ---------- Dark mode ---------- */
            body.layout-dark #notification-dropdown-menu.show { background: #121214; }
            body.layout-dark #notification-dropdown-menu.show .dropdown-header {
                background: #1b1b1f !important;
                color: #eee !important;
                border-bottom-color: #2a2a30;
                box-shadow: none;
            }
            body.layout-dark #notification-dropdown-menu.show .dropdown-header > .d-flex::before { color: #f1f1f4; }
            body.layout-dark #notification-dropdown-menu.show .noti-mobile-back {
                background: #26262c;
                color: #eee;
            }
            body.layout-dark #notification-dropdown-menu.show .dropdown-header .mark-all-read-btn {
                color: #9db4f0 !important;
                background: rgba(90, 130, 230, .18);
            }
            body.layout-dark #notification-dropdown-menu.show .notification-row {
                background-color: #1c1c20 !important;
                border-color: #2a2a30 !important;
                box-shadow: none;
            }
            body.layout-dark #notification-dropdown-menu.show .notification-row .media::before {
                background: #26262c;
                color: #9a98a6;
            }
            body.layout-dark #notification-dropdown-menu.show .notification-row .noti-text { color: #c9c8d1 !important; }
            body.layout-dark #notification-dropdown-menu.show .notification-row[style*="rgba(39, 72, 154"] {
                background-color: #1b2133 !important;
                border-color: rgba(90, 130, 230, .30) !important;
            }
            body.layout-dark #notification-dropdown-menu.show .notification-row[style*="rgba(39, 72, 154"] .noti-text {
                color: #f1f1f4 !important;
            }
            body.layout-dark #notification-dropdown-menu.show > .dropdown-menu-footer {
                background: #1b1b1f !important;
                border-top-color: #2a2a30 !important;
            }
        }
        /* back button sirf mobile full-screen me dikhe */
        .noti-mobile-back { display: none; }
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
                {{-- Mobile: profile avatar sabse left --}}
                <div class="dropdown d-flex d-md-none align-items-center mobile-user-dropdown mr-2">
                    <a href="javascript:;" class="d-flex align-items-center" id="dropdownBasicMobile"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img class="avatar" src="{{ image_path(auth()->user()->profile_image) }}" alt="avatar"
                            height="35" width="35">
                        <span class="mobile-user-greet">
                            <small>Welcome back</small>
                            <strong>{{ auth()->user()->name }}</strong>
                        </span>
                    </a>
                    <div class="dropdown-menu text-left m-0 pb-0" aria-labelledby="dropdownBasicMobile">
                        <div class="px-3 py-2">
                            <div class="font-weight-bold">{{ auth()->user()->name }}</div>
                            <small class="text-muted">{{ auth()->user()->email }}</small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ url('profile-settings') }}">
                            <div class="d-flex align-items-center"><i class="ft-edit mr-2"></i><span>Edit
                                    Profile</span></div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <form class="m-0" method="POST" action="{{ route('logouts') }}">
                            @csrf
                            <a href="#" onclick="event.preventDefault(); this.closest('form').submit();">
                                <div class="dropdown-item"><i class="ft-power mr-2"></i><span>{{ __('Logout') }}</span>
                                </div>
                            </a>
                        </form>
                    </div>
                </div>

                {{-- Hamburger: tablet par yahin, mobile par bottom nav me --}}
                <div class="navbar-toggle menu-toggle d-xl-none d-none d-md-block float-left align-items-center justify-content-center"
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
                <div class="navbar-brand-center d-none d-md-block">
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


                        <li class="nav-item d-flex align-items-center mr-2">
                            <input type="checkbox" class="d-none" id="color-switch-1"
                                {{ Cookie::get('layout') === 'dark' ? 'checked' : '' }}>
                            <label class="theme-toggle-btn color-mode-switch" for="color-switch-1"
                                title="Dark / Light mode">
                                <i class="ft-moon icon-moon"></i>
                                <i class="ft-sun icon-sun"></i>
                            </label>
                        </li>

                        @if (getCurrentCompany())
                            <li class="nav-item mr-2 position-relative d-flex align-items-center"><a
                                    class="nav-link dropdown-toggle dropdown-notification header-icon-btn"
                                    id="dropdownBasic1" href="javascript:;" title="Notifications"><i
                                        class="ft-bell"></i><span
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
                        <li class="dropdown nav-item mr-1 d-none d-md-block"><a
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

    {{-- Mobile: fixed bottom navbar --}}
    <div class="mobile-bottom-nav">
        <a href="{{ url('dashboard') }}"
            class="mbn-item {{ request()->is('dashboard') || request()->is('/') ? 'active' : '' }}">
            <i class="ft-grid"></i><span>Dashboard</span>
        </a>
        <a href="javascript:;" class="mbn-item mbn-menu menu-toggle">
            <i class="ft-menu"></i><span>Menu</span>
        </a>
        <a href="{{ url('profile-settings') }}"
            class="mbn-item {{ request()->is('profile-settings*') ? 'active' : '' }}">
            <i class="ft-user"></i><span>Profile</span>
        </a>
    </div>
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

            // =========================================================================
            // AJAX Notification Polling (Commented out as requested - Replaced with Pusher)
            // Pehle AJAX se har minute notification check hoti thi:
            // setInterval(fetchUnreadCount, 60000);
            // =========================================================================

            // Mobile full-screen popup helpers
            function isMobileView() {
                return window.matchMedia('(max-width: 767.98px)').matches;
            }

            function closeNotiMenu() {
                $('#notification-dropdown-menu').removeClass('show');
                $('body').removeClass('noti-open');
            }

            // Header me back (close) button lagao - CSS isy sirf mobile par dikhati hai
            function decorateNotiMenu() {
                let header = $('#notification-dropdown-menu .dropdown-header').first();
                if (header.length && !header.find('.noti-mobile-back').length) {
                    header.prepend('<a href="javascript:;" class="noti-mobile-back" aria-label="Close"><i class="ft-arrow-left"></i></a>');
                }
            }

            $(document).on('click', '.noti-mobile-back', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeNotiMenu();
            });

            // 2. Fetch dropdown items on click
            $('#dropdownBasic1').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let menu = $('#notification-dropdown-menu');

                // Toggle visibility manually
                if (menu.hasClass('show')) {
                    closeNotiMenu();
                    return;
                } else {
                    menu.addClass('show');
                    if (isMobileView()) {
                        $('body').addClass('noti-open');
                    }
                }

                menu.html('<li class="dropdown-menu-header"><div class="dropdown-header d-flex justify-content-center m-0 px-3 py-2 white bg-primary"><span>Loading notifications...</span></div></li>');
                decorateNotiMenu();

                $.ajax({
                    url: "{{ route('notifications.dropdown') }}",
                    type: "GET",
                    success: function(res) {
                        menu.html(res.html);
                        decorateNotiMenu();
                    }
                });
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#dropdownBasic1, #notification-dropdown-menu').length) {
                    closeNotiMenu();
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
                                    decorateNotiMenu();
                                }
                            });
                        }
                    }
                });
            });

            // ============================================
            // PUSHER REAL-TIME NOTIFICATIONS
            // ============================================
            @php
                $pusherCfg = function_exists('getPusherConfig') ? getPusherConfig() : null;
            @endphp
            @if(!empty($pusherCfg['enabled']) && !empty($pusherCfg['key']))
            var pusher = new Pusher("{{ $pusherCfg['key'] }}", {
                cluster: "{{ $pusherCfg['cluster'] ?? 'ap2' }}"
            });
            var channel = pusher.subscribe('notify-channel');
            channel.bind('notify-event', function(data) {
                var currentUserId = Number({{ auth()->user()->id ?? 0 }});
                var currentCompanyId = Number({{ getCurrentCompany()?->id ?? 0 }});

                // Ignore notification if event is explicitly targeted to another company
                if (data.company_id && currentCompanyId && Number(data.company_id) !== currentCompanyId) {
                    return;
                }

                var isRecipient = !data.user_ids || data.user_ids.length === 0 || data.user_ids.map(Number).includes(currentUserId);

                if (isRecipient) {
                    var count = parseInt($('#notification-badge-count').text()) || 0;
                    $('#notification-badge-count').text(count + 1).show();

                    // 1. Windows Desktop Notification (Exactly same as AJAX implementation)
                    var notifBody = data.clean_text || (data.message ? $('<div>').html(data.message).text().trim() : 'You have a new notification.');
                    if ("Notification" in window) {
                        if (Notification.permission === "granted") {
                            let desktopNoti = new Notification("Meskey", {
                                body: notifBody
                            });
                            desktopNoti.onclick = function() {
                                window.focus();
                                if (data.url) {
                                    window.location.href = data.url;
                                }
                                this.close();
                            };
                        } else if (Notification.permission !== "denied") {
                            Notification.requestPermission().then(function(perm) {
                                if (perm === "granted") {
                                    let desktopNoti = new Notification("Meskey", {
                                        body: notifBody
                                    });
                                    desktopNoti.onclick = function() {
                                        window.focus();
                                        if (data.url) {
                                            window.location.href = data.url;
                                        }
                                        this.close();
                                    };
                                }
                            });
                        }
                    }

                    if (data.id) {
                        previousLatestId = data.id;
                        localStorage.setItem('previousLatestId', previousLatestId);
                    }

                    // 2. In-app Toastr Notification
                    if (typeof toastr !== 'undefined') {
                        toastr.info(data.message, data.title || 'Meskey Notification', {
                            timeOut: 8000,
                            closeButton: true,
                            progressBar: true,
                            escapeHtml: false
                        });
                    }

                    // 3. Refresh dropdown content if open
                    if ($('#notification-dropdown-menu').hasClass('show')) {
                        $.ajax({
                            url: "{{ route('notifications.dropdown') }}",
                            type: "GET",
                            success: function(res) {
                                $('#notification-dropdown-menu').html(res.html);
                                decorateNotiMenu();
                            }
                        });
                    }
                }
            });
            @endif
        }, 1000);
    });
</script>
