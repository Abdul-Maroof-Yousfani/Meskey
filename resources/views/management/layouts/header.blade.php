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
    {{-- Saari custom UI styling: public/management/assets/css/mobile-ui.css --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('management/assets/css/mobile-ui.css') }}?v={{ @filemtime(public_path('management/assets/css/mobile-ui.css')) }}">
</head>

<body
    class="horizontal-layout horizontal-menu horizontal-menu-padding 2-columns  navbar-sticky {{ Cookie::get('layout') === 'dark' ? 'layout-dark' : '' }}"
    data-open="hover" data-menu="horizontal-menu" data-col="2-columns">
    {{-- Full-screen lock loader (theme switch, notifications) --}}
    {{-- "show" se shuru: page load hone tak loader dikhe, window load par JS isy hata deta hai --}}
    <div id="appLockLoader" class="app-lock-loader show" role="status" aria-live="polite" aria-hidden="false">
        <div class="app-lock-box">
            <span class="app-lock-spinner"></span>
            <span class="app-lock-text">Loading...</span>
        </div>
    </div>
    <script>
        // Page loader abhi SIRF mobile par (desktop par band - wapas chahiye to PAGE_LOADER_DESKTOP = true)
        window.PAGE_LOADER_DESKTOP = false;
        window.pageLoaderEnabled = function() {
            return window.PAGE_LOADER_DESKTOP || window.matchMedia('(max-width: 767.98px)').matches;
        };
        if (window.pageLoaderEnabled()) {
            // page load hone tak scroll lock + solid loader (peeche ka adhoora page na dikhe)
            document.body.classList.add('app-locked', 'page-booting');
        } else {
            var bootLoader = document.getElementById('appLockLoader');
            bootLoader.classList.remove('show');
            bootLoader.setAttribute('aria-hidden', 'true');
        }
    </script>

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
                    <div class="dropdown-menu profile-menu text-left m-0 p-0" aria-labelledby="dropdownBasicMobile">
                        <div class="pm-head">
                            <button type="button" class="pm-back" aria-label="Close"
                                onclick="document.getElementById('dropdownBasicMobile').click()">
                                <i class="ft-arrow-left"></i>
                            </button>
                            <img class="pm-avatar" src="{{ image_path(auth()->user()->profile_image) }}"
                                alt="avatar">
                            <div class="pm-info">
                                <div class="pm-name">{{ auth()->user()->name }}</div>
                                <div class="pm-email">{{ auth()->user()->email }}</div>
                            </div>
                        </div>

                        <div class="pm-body">
                            <a class="pm-item" href="{{ url('profile-settings') }}">
                                <span class="pm-ic"><i class="ft-user"></i></span>
                                <span class="pm-label">
                                    Edit Profile
                                    <small>Name, photo &amp; password</small>
                                </span>
                                <i class="ft-chevron-right pm-arrow"></i>
                            </a>
                        </div>

                        <div class="pm-foot">
                            <form class="m-0" id="mobileLogoutForm" method="POST" action="{{ route('logouts') }}">
                                @csrf
                                {{-- iPhone jaisa "slide to logout" --}}
                                <div class="slide-logout" id="slideLogout">
                                    <div class="sl-fill"></div>
                                    <span class="sl-text">Slide to logout</span>
                                    <div class="sl-knob" role="button" tabindex="0"
                                        aria-label="Slide to logout (ya Enter dabayein)">
                                        <i class="ft-log-out"></i>
                                    </div>
                                </div>
                            </form>
                        </div>
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
                        {{-- "dropdown" class jaan boojh kar nahi lagayi: theme ka app-menu.js (xl screen) har
                             .dropdown ko hover par kholta hai aur .dropdown a ka click rok deta hai.
                             Profile dropdown sirf CLICK par khule (Bootstrap data-toggle). --}}
                        <li class="nav-item mr-1 d-none d-md-block position-relative"><a
                                class="nav-link dropdown-toggle user-dropdown d-flex align-items-end"
                                id="dropdownBasic2" href="javascript:;" data-toggle="dropdown">
                                <div class="user d-md-flex d-none mr-2">
                                    <span class="text-right">{{ auth()->user()->name }}</span><span
                                        class="text-right text-muted font-small-1">{{ auth()->user()->email }}</span>
                                </div>
                                <img class="avatar" src="{{ image_path(auth()->user()->profile_image) }}"
                                    alt="avatar" height="35" width="35">
                            </a>
                            <div class="dropdown-menu profile-menu dropdown-menu-right text-left m-0 p-0"
                                aria-labelledby="dropdownBasic2">
                                <div class="pm-head">
                                    <img class="pm-avatar" src="{{ image_path(auth()->user()->profile_image) }}"
                                        alt="avatar">
                                    <div class="pm-info">
                                        <div class="pm-name">{{ auth()->user()->name }}</div>
                                        <div class="pm-email">{{ auth()->user()->email }}</div>
                                    </div>
                                </div>

                                <div class="pm-body">
                                    <a class="pm-item" href="{{ url('profile-settings') }}">
                                        <span class="pm-ic"><i class="ft-user"></i></span>
                                        <span class="pm-label">
                                            Edit Profile
                                            <small>Name, photo &amp; password</small>
                                        </span>
                                        <i class="ft-chevron-right pm-arrow"></i>
                                    </a>
                                </div>

                                <div class="pm-foot">
                                    <form class="m-0" method="POST" action="{{ route('logouts') }}">
                                        @csrf
                                        <button type="submit" class="pm-logout">
                                            <i class="ft-log-out"></i><span>{{ __('Logout') }}</span>
                                        </button>
                                    </form>
                                </div>
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
            class="mbn-item {{ request()->is('dashboard') || request()->is('/') ? 'active' : '' }}" aria-label="Dashboard" title="Dashboard">
            <i class="ft-grid"></i>
        </a>
        <a href="javascript:;" class="mbn-item mbn-menu menu-toggle" aria-label="Menu" title="Menu">
            <i class="ft-menu"></i>
        </a>
        {{-- Profile: page par jane ki jagah upar wala full-screen profile popup kholo.
             stopPropagation zaroori hai, warna Bootstrap ka document click handler popup ko fauran band kar deta hai.
             href fallback hai agar JS na chale. --}}
        <a href="{{ url('profile-settings') }}"
            class="mbn-item {{ request()->is('profile-settings*') ? 'active' : '' }}" aria-label="Profile" title="Profile"
            onclick="var t = document.getElementById('dropdownBasicMobile'); if (t) { event.preventDefault(); event.stopPropagation(); t.click(); }">
            <i class="ft-user"></i>
        </a>
    </div>
<script>
    // ===== Full-screen lock loader (global) =====
    var appLoaderTimer = null;

    function showAppLoader(text, maxMs) {
        var el = document.getElementById('appLockLoader');
        if (!el) return;
        el.querySelector('.app-lock-text').textContent = text || 'Please wait...';
        el.classList.add('show');
        el.setAttribute('aria-hidden', 'false');
        document.body.classList.add('app-locked');

        // safety: request atak jaye to bhi kuch der baad screen unlock ho jaye
        clearTimeout(appLoaderTimer);
        appLoaderTimer = setTimeout(hideAppLoader, maxMs || 15000);
    }

    function hideAppLoader() {
        var el = document.getElementById('appLockLoader');
        if (!el) return;
        clearTimeout(appLoaderTimer);
        el.classList.remove('show');
        el.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('app-locked', 'page-booting');
    }

    // =====================================================================
    // GLOBAL PAGE LOADER - poori application ke liye, sirf yahin se
    // =====================================================================
    // 1) Naya page: loader HTML me "show" se shuru hota hai -> page poora load hone par hatao.
    //    Agar us waqt page ki pehli AJAX (e.g. filterationCommon wali list) chal rahi ho
    //    to uske khatam hone tak ruko, taake khaali table -> cards ka jhatka na dikhe.
    //    Koi request atak jaye to bhi DOM ready ke 10 sec baad hata do.
    window.addEventListener('load', function() {
        var jq = window.jQuery;
        if (jq && jq.active > 0) {
            jq(document).one('ajaxStop', function() {
                // naya HTML paint hone do, phir hatao
                requestAnimationFrame(function() { setTimeout(hideAppLoader, 30); });
            });
        } else {
            hideAppLoader();
        }
    });
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            if (document.body.classList.contains('page-booting')) hideAppLoader();
        }, 10000);
    });

    // 2) Page chhorte waqt (link click, form submit, window.location wagaira) loader dikhao.
    //    Jin clicks se page nahi badalta (download, naya tab, file) un par skip karo,
    //    warna loader atak jata.
    var appLoaderSkipNav = false;

    function shouldSkipLoaderForLink(a, e) {
        if (!a) return true;
        if (e && (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button === 1)) return true; // naya tab/window
        if (a.hasAttribute('download') || a.hasAttribute('data-no-loader')) return true;
        if ((a.getAttribute('target') || '').toLowerCase() === '_blank') return true;
        var href = (a.getAttribute('href') || '').trim().toLowerCase();
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0 ||
            href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) return true;
        if (/\.(pdf|xlsx?|csv|zip|docx?|png|jpe?g)(\?|#|$)/.test(href) || /[\/?&]download/.test(href)) return true;
        return false;
    }

    document.addEventListener('click', function(e) {
        var a = e.target.closest ? e.target.closest('a') : null;
        if (a && shouldSkipLoaderForLink(a, e)) {
            // is click se aane wala beforeunload ignore karo (download wagaira)
            appLoaderSkipNav = true;
            setTimeout(function() { appLoaderSkipNav = false; }, 1500);
        }
    }, true);

    document.addEventListener('submit', function(e) {
        var f = e.target;
        if (f && ((f.getAttribute('target') || '').toLowerCase() === '_blank' || f.hasAttribute('data-no-loader'))) {
            appLoaderSkipNav = true;
            setTimeout(function() { appLoaderSkipNav = false; }, 1500);
        }
    }, true);

    window.addEventListener('beforeunload', function() {
        if (appLoaderSkipNav) return;
        if (!window.pageLoaderEnabled()) return; // desktop par page loader band
        // page na badla (e.g. server ne file bhej di) to 8 sec baad khud hat jaye
        showAppLoader('Loading...', 8000);
    });

    // 3) Browser back/forward cache se page wapas aaye to loader band
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) hideAppLoader();
    });

    // ===== Mobile profile popup (full screen): khaali jagah tap karne se band na ho =====
    // Bootstrap dropdown menu ke andar ke click par bhi band ho jata hai. Sirf link/button ko
    // aage jane do (navigate, logout, back), baaki click yahin rok do.
    document.addEventListener('DOMContentLoaded', function() {
        var pm = document.querySelector('.mobile-user-dropdown .profile-menu');
        if (pm) {
            pm.addEventListener('click', function(e) {
                if (!e.target.closest('a, button')) {
                    e.stopPropagation();
                }
            });
        }
    });

    // (Mobile filter bottom sheet: public/management/assets/js/mobile-filter-sheet.js)

    // ===== Slide to logout (mobile profile popup) =====
    document.addEventListener('DOMContentLoaded', function() {
        var track = document.getElementById('slideLogout');
        var form = document.getElementById('mobileLogoutForm');
        if (!track || !form) return;

        var knob = track.querySelector('.sl-knob');
        var fill = track.querySelector('.sl-fill');
        var text = track.querySelector('.sl-text');
        var PAD = 5;
        var startX = 0, dx = 0, max = 0, dragging = false, done = false;

        function setPos(x) {
            knob.style.transform = 'translateX(' + x + 'px)';
            fill.style.width = (x + knob.offsetWidth + PAD * 2) + 'px';
            // text slide ke saath halka hota jaye
            text.style.opacity = String(Math.max(0, 1 - (x / (max || 1)) * 1.4));
        }

        function finish() {
            done = true;
            track.classList.add('done');
            setPos(max);
            text.style.opacity = '1';
            text.textContent = 'Logging out...';
            if (typeof showAppLoader === 'function') showAppLoader('Logging out...');
            form.submit();
        }

        function reset() {
            track.classList.add('returning');
            setPos(0);
            setTimeout(function() { track.classList.remove('returning'); }, 320);
        }

        knob.addEventListener('pointerdown', function(e) {
            if (done) return;
            dragging = true;
            startX = e.clientX;
            dx = 0;
            max = track.clientWidth - knob.offsetWidth - PAD * 2;
            track.classList.remove('returning');
            track.classList.add('dragging');
            knob.setPointerCapture(e.pointerId);
            e.preventDefault();
        });

        knob.addEventListener('pointermove', function(e) {
            if (!dragging) return;
            dx = Math.min(Math.max(0, e.clientX - startX), max);
            setPos(dx);
        });

        function release() {
            if (!dragging) return;
            dragging = false;
            track.classList.remove('dragging');
            // 90% se zyada khincha = logout, warna wapas
            if (dx >= max * 0.9) {
                finish();
            } else {
                reset();
            }
        }
        knob.addEventListener('pointerup', release);
        knob.addEventListener('pointercancel', release);

        // keyboard se bhi chale (accessibility)
        knob.addEventListener('keydown', function(e) {
            if (!done && (e.key === 'Enter' || e.key === ' ')) {
                e.preventDefault();
                max = track.clientWidth - knob.offsetWidth - PAD * 2;
                finish();
            }
        });

        // browser "back" se wapas aaye to slider reset
        window.addEventListener('pageshow', function() {
            if (done && typeof hideAppLoader === 'function') hideAppLoader();
            done = false;
            track.classList.remove('done');
            text.textContent = 'Slide to logout';
            text.style.opacity = '';
            knob.style.transform = '';
            fill.style.width = '';
        });
    });

    // ===== Dark / light mode: cookie save hone tak screen lock =====
    // scripts.js (#color-switch-1 change) /set-layout-cookie par AJAX bhejta hai. Agar user
    // us ke khatam hone se pehle doosre page par chala jaye to cookie save nahi hoti aur
    // theme purani state me wapas aa jati hai. Is liye request poori hone tak loader dikhao.
    $(document).on('change', '#color-switch-1', function() {
        showAppLoader('Applying theme...');
    });
    // NOTE: footer me vendors.min.js jQuery dobara (3.4.1) load karta hai aur window.$ badal deta hai;
    // scripts.js ka AJAX usi doosri jQuery se jata hai. Is liye ajaxComplete page load hone ke baad
    // aakhri window.jQuery par lagao, warna event kabhi nahi milta aur loader atka rehta hai.
    window.addEventListener('load', function() {
        window.jQuery(document).ajaxComplete(function(event, xhr, settings) {
            if (settings && settings.url && settings.url.indexOf('set-layout-cookie') !== -1) {
                hideAppLoader();
            }
        });
    });

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

            // ===== Notifications: 10 10 karke (infinite scroll) =====
            // List (.scrollable-container) ke neeche pahuncho -> spinner -> agli 10 jud jayen.
            // data-next-page / data-has-more: dropdown_items.blade.php (controller: fetchDropdown?page=N)
            // "scroll" bubble nahi hota -> capture phase; dropdown ka HTML har dafa naya banta hai.
            var notiLoadingMore = false;

            function loadMoreNotifications(list) {
                var $list = $(list);
                if (notiLoadingMore || String($list.attr('data-has-more')) !== '1') return;

                notiLoadingMore = true;
                var page = parseInt($list.attr('data-next-page'), 10) || 2;
                var $spinner = $list.find('.noti-loadmore');
                $spinner.show();

                $.ajax({
                    url: "{{ route('notifications.dropdown') }}",
                    type: "GET",
                    data: { page: page },
                    success: function(res) {
                        // agar is dauran dropdown dobara bana (mark all read / naya notification) to purani list chhor do
                        if (!document.body.contains(list)) return;
                        $spinner.before(res.html);
                        $list.attr('data-next-page', res.next_page);
                        $list.attr('data-has-more', res.has_more ? 1 : 0);
                        if (!res.has_more && !$list.find('.noti-end').length) {
                            $spinner.after('<div class="noti-end">No more notifications</div>');
                        }
                    },
                    error: function() {
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Mazeed notifications load nahi ho sakin.');
                        }
                    },
                    complete: function() {
                        $spinner.hide();
                        notiLoadingMore = false;
                        // list itni chhoti ho ke scroll hi na bane, to agla page khud mangwa lo
                        if (document.body.contains(list) && list.scrollHeight <= list.clientHeight + 4) {
                            loadMoreNotifications(list);
                        }
                    }
                });
            }

            document.addEventListener('scroll', function(e) {
                var list = e.target;
                if (!list || !list.matches || !list.matches('#notification-dropdown-menu .scrollable-container')) return;
                // neeche se 60px pehle hi agla page mangwa lo
                if (list.scrollTop + list.clientHeight >= list.scrollHeight - 60) {
                    loadMoreNotifications(list);
                }
            }, true);

            // 2. Fetch dropdown items on click
            // Pehle screen lock + loader, data aane ke baad hi popup bhara hua khule
            $('#dropdownBasic1').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let menu = $('#notification-dropdown-menu');

                if (menu.hasClass('show')) {
                    closeNotiMenu();
                    return;
                }

                showAppLoader('Loading notifications...');

                $.ajax({
                    url: "{{ route('notifications.dropdown') }}",
                    type: "GET",
                    success: function(res) {
                        menu.html(res.html);
                        decorateNotiMenu();
                        menu.addClass('show');
                        if (isMobileView()) {
                            $('body').addClass('noti-open');
                        }
                    },
                    error: function() {
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Notifications load nahi ho sakin. Dobara try karein.');
                        }
                    },
                    complete: function() {
                        hideAppLoader();
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
                showAppLoader('Marking as read...');
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
                                },
                                complete: function() {
                                    hideAppLoader();
                                }
                            });
                        } else {
                            hideAppLoader();
                        }
                    },
                    error: function() {
                        hideAppLoader();
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Mark as read nahi ho saka. Dobara try karein.');
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
