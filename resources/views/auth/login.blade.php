<!DOCTYPE html>
<html class="loading" lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description"
        content="Innovative Network ERP is a robust, user-friendly, and modern ERP solution designed to streamline business operations with unmatched flexibility and efficiency.">
    <meta name="keywords"
        content="Innovative Network ERP, business management software, ERP solution, powerful ERP system, enterprise resource planning, efficient operations">
    <meta name="author" content="Innovative Network">
    <meta name="theme-color" content="#27489a">
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

    <style>
        :root {
            --brand: #27489a;
            --brand-2: #4a6fd1;
            --brand-dark: #1d3878;
            --bg: #f4f6fb;
            --card: #ffffff;
            --text: #1f2333;
            --muted: #7a7f93;
            --border: #e3e6ef;
            --input-bg: #f7f8fc;
        }

        body.layout-dark {
            --bg: #111216;
            --card: #1b1c21;
            --text: #eceef5;
            --muted: #9a9fb2;
            --border: #2c2e36;
            --input-bg: #23252c;
        }

        * { box-sizing: border-box; }

        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            font-family: "Montserrat", "Segoe UI", Roboto, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        /* ================= Layout ================= */
        .auth-shell {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
        }

        /* ---- Brand side (left on desktop, top on mobile) ---- */
        .auth-brand {
            position: relative;
            flex: 1 1 50%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 56px;
            color: #fff;
            overflow: hidden;
            background: linear-gradient(145deg, #4a6fd1 0%, #27489a 55%, #1a3270 100%);
        }

        /* decorative shapes */
        .auth-brand::before,
        .auth-brand::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .auth-brand::before {
            width: 520px;
            height: 520px;
            right: -180px;
            top: -160px;
            background: radial-gradient(circle, rgba(255, 255, 255, .16), rgba(255, 255, 255, 0) 70%);
        }

        .auth-brand::after {
            width: 420px;
            height: 420px;
            left: -140px;
            bottom: -170px;
            border: 60px solid rgba(255, 255, 255, .06);
        }

        /* Logo, beech me, glowing halo ke saath */
        .brand-center {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-halo {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 34px;
            border-radius: 40px;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .16);
            -webkit-backdrop-filter: blur(6px);
            backdrop-filter: blur(6px);
            box-shadow: 0 30px 60px rgba(8, 18, 55, .30);
            animation: floatY 6s ease-in-out infinite;
        }

        .logo-halo::before {
            /* halki si roshni peeche */
            content: "";
            position: absolute;
            inset: -60px;
            z-index: -1;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, .22), rgba(255, 255, 255, 0) 65%);
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 22px 30px;
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 12px 30px rgba(10, 20, 60, .25);
        }

        .brand-logo img {
            display: block;
            height: 90px;
            width: auto;
            max-width: 260px;
            object-fit: contain;
        }

        @keyframes floatY {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Decorative shapes */
        .deco {
            position: absolute;
            pointer-events: none;
            z-index: 0;
        }

        .deco-ring {
            border-radius: 50%;
            border: 1.5px solid rgba(255, 255, 255, .14);
        }

        .deco-ring.r1 {
            width: 300px;
            height: 300px;
            left: 50%;
            top: 50%;
            margin: -150px 0 0 -150px;
            animation: pulseRing 5s ease-in-out infinite;
        }

        .deco-ring.r2 {
            width: 440px;
            height: 440px;
            left: 50%;
            top: 50%;
            margin: -220px 0 0 -220px;
            border-style: dashed;
            border-color: rgba(255, 255, 255, .10);
            animation: spinSlow 60s linear infinite;
        }

        .deco-dot {
            border-radius: 50%;
            background: rgba(255, 255, 255, .5);
            animation: floatY 5s ease-in-out infinite;
        }

        .deco-dot.d1 { width: 10px; height: 10px; top: 18%; left: 22%; }
        .deco-dot.d2 { width: 6px; height: 6px; top: 72%; left: 78%; animation-delay: 1.2s; }
        .deco-dot.d3 { width: 14px; height: 14px; top: 26%; right: 16%; background: rgba(255, 255, 255, .25); animation-delay: 2.4s; }

        .deco-grid {
            width: 120px;
            height: 120px;
            left: 48px;
            bottom: 70px;
            background-image: radial-gradient(rgba(255, 255, 255, .28) 1.5px, transparent 1.5px);
            background-size: 16px 16px;
        }

        @keyframes pulseRing {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: .6; }
        }

        @keyframes spinSlow {
            to { transform: rotate(360deg); }
        }

        @media (prefers-reduced-motion: reduce) {
            .logo-halo, .deco-ring, .deco-dot { animation: none; }
        }

        .brand-foot {
            position: relative;
            z-index: 1;
            text-align: center;
            font-size: .8rem;
            color: rgba(255, 255, 255, .6);
        }

        /* ---- Form side ---- */
        .auth-form-side {
            flex: 1 1 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 40px 36px;
            box-shadow: 0 20px 50px rgba(20, 30, 70, .08);
            animation: cardIn .45s ease-out;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-card .welcome-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            margin-bottom: 16px;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 600;
            color: var(--brand);
            background: rgba(39, 72, 154, .08);
        }

        body.layout-dark .auth-card .welcome-chip {
            color: #9db4f0;
            background: rgba(90, 130, 230, .14);
        }

        .auth-card h2 {
            font-size: 1.7rem;
            font-weight: 700;
            letter-spacing: -.3px;
            margin: 0 0 6px;
            color: var(--text);
        }

        .auth-card .sub {
            font-size: .92rem;
            color: var(--muted);
            margin: 0 0 26px;
        }

        /* ---- Alert ---- */
        .auth-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            margin-bottom: 18px;
            border-radius: 14px;
            font-size: .85rem;
            color: #8a5a00;
            background: #fff6e0;
            border: 1px solid #ffe2a3;
        }

        body.layout-dark .auth-alert {
            color: #ffd27a;
            background: rgba(255, 190, 60, .1);
            border-color: rgba(255, 190, 60, .25);
        }

        .auth-alert .close-alert {
            margin-left: auto;
            background: none;
            border: 0;
            padding: 0 2px;
            color: inherit;
            opacity: .7;
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
        }

        /* ---- Fields ---- */
        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            margin-bottom: 7px;
            color: var(--text);
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .lead-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 1rem;
            pointer-events: none;
            transition: color .2s;
        }

        .input-wrap .form-control {
            height: 52px;
            padding: 0 48px 0 46px;
            border-radius: 14px;
            border: 1.5px solid var(--border);
            background: var(--input-bg);
            color: var(--text);
            font-size: .95rem;
            box-shadow: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .input-wrap .form-control::placeholder {
            color: var(--muted);
            opacity: .8;
        }

        .input-wrap .form-control:focus {
            border-color: var(--brand-2);
            background: var(--card);
            box-shadow: 0 0 0 4px rgba(39, 72, 154, .12);
        }

        .input-wrap:focus-within .lead-icon {
            color: var(--brand);
        }

        body.layout-dark .input-wrap:focus-within .lead-icon {
            color: #9db4f0;
        }

        .input-wrap .form-control.is-invalid {
            border-color: #e5484d;
            background-image: none;
        }

        .input-wrap .form-control.is-invalid:focus {
            box-shadow: 0 0 0 4px rgba(229, 72, 77, .12);
        }

        .toggle-pass {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            transition: background .2s, color .2s;
        }

        .toggle-pass:hover {
            background: rgba(39, 72, 154, .08);
            color: var(--brand);
        }

        .field-error {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 7px;
            font-size: .8rem;
            color: #e5484d;
        }

        /* ---- Remember / forgot row ---- */
        .row-between {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin: 6px 0 24px;
            font-size: .85rem;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            cursor: pointer;
            color: var(--muted);
            user-select: none;
        }

        .remember input {
            width: 18px;
            height: 18px;
            margin: 0;
            accent-color: var(--brand);
            cursor: pointer;
        }

        .forgot {
            font-weight: 600;
            color: var(--brand);
            text-decoration: none;
        }

        .forgot:hover {
            color: var(--brand-dark);
            text-decoration: underline;
        }

        body.layout-dark .forgot {
            color: #9db4f0;
        }

        /* ---- Button ---- */
        .btn-login {
            position: relative;
            width: 100%;
            height: 52px;
            border: 0;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: .2px;
            color: #fff;
            background: linear-gradient(135deg, var(--brand-2), var(--brand));
            box-shadow: 0 10px 24px rgba(39, 72, 154, .30);
            cursor: pointer;
            transition: transform .15s, box-shadow .2s, filter .2s;
        }

        .btn-login:hover {
            filter: brightness(1.06);
            box-shadow: 0 14px 30px rgba(39, 72, 154, .38);
        }

        .btn-login:active {
            transform: scale(.985);
        }

        .btn-login:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(39, 72, 154, .22), 0 10px 24px rgba(39, 72, 154, .30);
        }

        .btn-login .spinner {
            display: none;
            width: 18px;
            height: 18px;
            margin-right: 10px;
            border: 2px solid rgba(255, 255, 255, .45);
            border-top-color: #fff;
            border-radius: 50%;
            vertical-align: -3px;
            animation: spin .7s linear infinite;
        }

        .btn-login.loading {
            pointer-events: none;
            filter: saturate(.85);
        }

        .btn-login.loading .spinner {
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .card-foot {
            margin-top: 26px;
            text-align: center;
            font-size: .78rem;
            color: var(--muted);
        }

        .card-foot i {
            color: #2fb36d;
            margin-right: 4px;
        }

        /* ================= Tablet ================= */
        @media (max-width: 1199.98px) {
            .auth-brand { padding: 40px; }
            .brand-logo img { height: 76px; }
        }

        /* ================= Mobile ================= */
        @media (max-width: 991.98px) {
            .auth-shell {
                flex-direction: column;
            }

            /* brand panel becomes a gradient header */
            .auth-brand {
                flex: 0 0 auto;
                justify-content: flex-start;
                align-items: center;
                text-align: center;
                padding: calc(34px + env(safe-area-inset-top)) 24px 90px;
                border-radius: 0 0 32px 32px;
            }

            .auth-brand::before {
                width: 340px;
                height: 340px;
                right: -140px;
                top: -140px;
            }

            .auth-brand::after {
                width: 260px;
                height: 260px;
                left: -110px;
                bottom: -150px;
                border-width: 40px;
            }

            .logo-halo {
                padding: 16px;
                border-radius: 28px;
            }

            .logo-halo::before { inset: -40px; }

            .brand-logo {
                padding: 14px 20px;
                border-radius: 18px;
            }

            .brand-logo img { height: 56px; max-width: 200px; }

            .deco-ring.r1 { width: 200px; height: 200px; margin: -100px 0 0 -100px; }
            .deco-ring.r2 { width: 300px; height: 300px; margin: -150px 0 0 -150px; }
            .deco-grid { width: 80px; height: 80px; left: 18px; bottom: 30px; }

            .brand-foot {
                display: none;
            }

            /* form card overlaps the header */
            .auth-form-side {
                flex: 1 0 auto;
                align-items: flex-start;
                padding: 0 16px calc(28px + env(safe-area-inset-bottom));
                margin-top: -64px;
                position: relative;
                z-index: 2;
            }

            .auth-card {
                max-width: 480px;
                margin: 0 auto;
                padding: 28px 22px;
                border-radius: 22px;
                box-shadow: 0 16px 40px rgba(20, 30, 70, .12);
            }

            .auth-card h2 { font-size: 1.4rem; }
            .auth-card .sub { margin-bottom: 22px; }
        }

        @media (max-width: 359.98px) {
            .auth-card { padding: 24px 16px; }
            .row-between { font-size: .8rem; }
        }
    </style>
</head>

<body class="{{ Cookie::get('layout') === 'dark' ? 'layout-dark' : '' }}">
    <main class="auth-shell">

        <!-- ===== Brand panel ===== -->
        <section class="auth-brand">
            <!-- decorative shapes -->
            <span class="deco deco-ring r1"></span>
            <span class="deco deco-ring r2"></span>
            <span class="deco deco-dot d1"></span>
            <span class="deco deco-dot d2"></span>
            <span class="deco deco-dot d3"></span>
            <span class="deco deco-grid"></span>

            <div class="brand-center">
                <div class="logo-halo">
                    <div class="brand-logo">
                        <img src="{{ asset('management/app-assets/img/meskay-logo.png') }}"
                            alt="{{ config('app.name') }}">
                    </div>
                </div>
            </div>

            {{-- <div class="brand-foot">&copy; {{ date('Y') }} Innovative Network. All rights reserved.</div> --}}
        </section>

        <!-- ===== Form ===== -->
        <section class="auth-form-side">
            <div class="auth-card">
                <span class="welcome-chip"><i class="fa fa-hand-paper-o"></i> Welcome back</span>
                <h2>Sign in to your account</h2>
                <p class="sub">Please enter your details to continue.</p>

                @if (session('message'))
                    <div class="auth-alert" role="alert">
                        <i class="fa fa-exclamation-circle mt-1"></i>
                        <span>{{ session('message') }}</span>
                        <button type="button" class="close-alert" aria-label="Close"
                            onclick="this.closest('.auth-alert').remove()">&times;</button>
                    </div>
                @endif

                <form method="POST" id="loginForm" class="needs-validation" novalidate action="{{ route('login') }}">
                    @csrf
                    <input type="hidden" name="skip-error-format">

                    <div class="field">
                        <label for="signinEmailInput">Username</label>
                        <div class="input-wrap">
                            <i class="fa fa-user-o lead-icon"></i>
                            <input id="signinEmailInput" placeholder="Enter your username" type="text"
                                class="form-control @error('username') is-invalid @enderror" name="username"
                                value="{{ old('username') }}" required autofocus autocomplete="username">
                        </div>
                        @error('username')
                            <div class="field-error" role="alert"><i class="fa fa-info-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="formSignUpPassword">Password</label>
                        <div class="input-wrap">
                            <i class="fa fa-lock lead-icon"></i>
                            <input id="formSignUpPassword" type="password" placeholder="Enter your password"
                                class="form-control fakePassword @error('password') is-invalid @enderror"
                                name="password" required autocomplete="current-password">
                            <button type="button" class="toggle-pass" id="togglePassword"
                                aria-label="Show password" title="Show / hide password">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="field-error" role="alert"><i class="fa fa-info-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row-between">
                        <label class="remember" for="rememberMeCheckbox">
                            <input name="remember" id="rememberMeCheckbox" type="checkbox"
                                {{ old('remember') ? 'checked' : '' }}>
                            <span>Remember me</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="forgot" href="{{ route('password.request') }}">{{ __('Forgot Password?') }}</a>
                        @endif
                    </div>

                    <button type="submit" class="btn-login" id="loginBtn">
                        <span class="spinner"></span><span class="btn-text">Login</span>
                    </button>
                </form>

                <div class="card-foot"><i class="fa fa-lock"></i> Your connection is secure</div>
            </div>
        </section>
    </main>

    <script>
        (function() {
            // Password show / hide
            var toggle = document.getElementById('togglePassword');
            var pass = document.getElementById('formSignUpPassword');
            if (toggle && pass) {
                toggle.addEventListener('click', function() {
                    var show = pass.type === 'password';
                    pass.type = show ? 'text' : 'password';
                    toggle.querySelector('i').className = show ? 'fa fa-eye-slash' : 'fa fa-eye';
                    toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                    pass.focus();
                });
            }

            // Submit par loading state (double submit se bachao)
            var form = document.getElementById('loginForm');
            var btn = document.getElementById('loginBtn');
            if (form && btn) {
                form.addEventListener('submit', function() {
                    btn.classList.add('loading');
                    btn.querySelector('.btn-text').textContent = 'Signing in...';
                });
                // browser "back" se wapas aaye to button reset karo
                window.addEventListener('pageshow', function() {
                    btn.classList.remove('loading');
                    btn.querySelector('.btn-text').textContent = 'Login';
                });
            }
        })();
    </script>
</body>

</html>
