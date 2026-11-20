@extends('management/layouts/master')
@section('title')
    Profile Settings
@endsection
@section('content')

    @php
        use App\Models\User;
        use Carbon\Carbon;
        $users_data = App\Models\User::loginHistory();
        $me = auth()->user();
        $historyCount = isset($users_data->loginHistories) ? count($users_data->loginHistories) : 0;
    @endphp

    <div class="content-wrapper ps-page">

        <!-- ================= Hero ================= -->
        <div class="ps-hero">
            <span class="ps-hero-deco d1"></span>
            <span class="ps-hero-deco d2"></span>

            <div class="ps-hero-inner">
                <div class="ps-avatar-wrap">
                    {{-- #imagePreview: scripts.js isi par naya photo preview lagata hai --}}
                    <div class="ps-avatar" id="imagePreview"
                        style="background-image: url('{{ image_path($me->profile_image) }}');"></div>
                    {{-- label "for" se file input (jo General form ke andar hai) khulta hai --}}
                    <label for="imageUpload" class="ps-avatar-btn" title="Change photo">
                        <i class="ft-camera"></i>
                    </label>
                </div>

                <div class="ps-hero-info">
                    <h2 class="ps-name">{{ $me->name }}</h2>
                    <div class="ps-chips">
                        @if ($me->username)
                            <span class="ps-chip"><i class="ft-at-sign"></i>{{ $me->username }}</span>
                        @endif
                        @if ($me->email)
                            <span class="ps-chip"><i class="ft-mail"></i>{{ $me->email }}</span>
                        @endif
                        @if ($me->created_at)
                            <span class="ps-chip"><i class="ft-calendar"></i>Member since
                                {{ $me->created_at->format('M Y') }}</span>
                        @endif
                    </div>
                    <div class="ps-photo-note" id="photoNote">
                        <i class="ft-info"></i> New photo selected. Click <b>Save Changes</b> to apply it.
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= Tabs ================= -->
        <ul class="nav ps-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab"
                    aria-controls="general" aria-selected="true">
                    <i class="ft-user"></i><span>General</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="change-password-tab" data-toggle="tab" href="#change-password" role="tab"
                    aria-controls="change-password" aria-selected="false">
                    <i class="ft-shield"></i><span>Security</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="connections-tab" data-toggle="tab" href="#connections" role="tab"
                    aria-controls="connections" aria-selected="false">
                    <i class="ft-clock"></i><span>Login History</span>
                    @if ($historyCount)
                        <span class="ps-tab-count">{{ $historyCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="cache-tab" data-toggle="tab" href="#cache" role="tab"
                    aria-controls="cache" aria-selected="false">
                    <i class="ft-refresh-ccw"></i><span>Cache</span>
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ================= General ================= -->
            <div class="tab-pane active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <div class="ps-card">
                    <div class="ps-card-head">
                        <span class="ps-card-ic"><i class="ft-user"></i></span>
                        <div>
                            <h4>Personal information</h4>
                            <p>Update your name, email and profile photo.</p>
                        </div>
                    </div>

                    <form class="form-l" id="ajaxSubmit" method="post"
                        action="{{ route('profile-settings', $me->id) }}">
                        @csrf
                        @method('PUT')

                        {{-- photo upar hero me dikhti hai; input yahin form ke andar rehna zaroori hai --}}
                        <input type="file" id="imageUpload" name="profile_image" accept=".png, .jpg, .jpeg"
                            class="ps-file-input" />

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group ps-field">
                                    <label for="ps-name">Full name</label>
                                    <div class="ps-input">
                                        <i class="ft-user"></i>
                                        <input type="text" id="ps-name" value="{{ $me->name ?: '' }}" name="name"
                                            class="form-control" placeholder="Your full name">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group ps-field">
                                    <label for="ps-email">Email address</label>
                                    <div class="ps-input">
                                        <i class="ft-mail"></i>
                                        <input type="email" id="ps-email" value="{{ $me->email ?: '' }}"
                                            name="email" class="form-control" placeholder="you@example.com">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group ps-field">
                                    <label>Username</label>
                                    <div class="ps-input is-readonly">
                                        <i class="ft-at-sign"></i>
                                        <input type="text" class="form-control" value="{{ $me->username }}" readonly
                                            disabled>
                                    </div>
                                    <small class="ps-hint">Username cannot be changed.</small>
                                </div>
                            </div>
                        </div>

                        <div class="ps-actions">
                            <button type="submit" class="ps-btn position-relative">
                                <i class="ft-check"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ================= Security ================= -->
            <div class="tab-pane" id="change-password" role="tabpanel" aria-labelledby="change-password-tab">
                <div class="ps-card">
                    <div class="ps-card-head">
                        <span class="ps-card-ic"><i class="ft-lock"></i></span>
                        <div>
                            <h4>Change password</h4>
                            <p>Use a strong password you don't use anywhere else.</p>
                        </div>
                    </div>

                    <form class="form-l" id="ajaxSubmit" data-reset="true" method="post"
                        action="{{ route('updatePassword', $me->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group ps-field errorappend">
                                    <label for="ps-old">Current password</label>
                                    <div class="ps-input">
                                        <i class="ft-lock"></i>
                                        <input type="password" id="ps-old" name="old_password" class="form-control"
                                            placeholder="Enter current password" autocomplete="current-password">
                                        <button type="button" class="ps-eye" aria-label="Show password"><i
                                                class="ft-eye"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group ps-field">
                                    <label for="ps-new">New password</label>
                                    <div class="ps-input">
                                        <i class="ft-unlock"></i>
                                        <input type="password" id="ps-new" name="new_password" class="form-control"
                                            placeholder="Enter new password" autocomplete="new-password">
                                        <button type="button" class="ps-eye" aria-label="Show password"><i
                                                class="ft-eye"></i></button>
                                    </div>
                                    <div class="ps-strength" id="psStrength">
                                        <span></span><span></span><span></span><span></span>
                                        <em></em>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group ps-field">
                                    <label for="ps-confirm">Confirm new password</label>
                                    <div class="ps-input">
                                        <i class="ft-check-circle"></i>
                                        <input type="password" id="ps-confirm" name="confirm_password"
                                            class="form-control" placeholder="Re-enter new password"
                                            autocomplete="new-password">
                                        <button type="button" class="ps-eye" aria-label="Show password"><i
                                                class="ft-eye"></i></button>
                                    </div>
                                    <small class="ps-hint ps-match" id="psMatch"></small>
                                </div>
                            </div>
                        </div>

                        <div class="ps-tip">
                            <i class="ft-info"></i>
                            <span>Tip: use at least 8 characters with a mix of letters, numbers and symbols.</span>
                        </div>

                        <div class="ps-actions">
                            <button type="submit" class="ps-btn position-relative">
                                <i class="ft-shield"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ================= Login History ================= -->
            <div class="tab-pane" id="connections" role="tabpanel" aria-labelledby="connections-tab">
                <div class="ps-card">
                    <div class="ps-card-head">
                        <span class="ps-card-ic"><i class="ft-activity"></i></span>
                        <div>
                            <h4>Recent activity</h4>
                            <p>Devices and locations that signed in to your account.</p>
                        </div>
                    </div>

                    @if ($historyCount)
                        <ul class="ps-history">
                            @foreach ($users_data->loginHistories as $item)
                                @php
                                    $ua = (string) $item->header;
                                    $uaL = strtolower($ua);
                                    $isMobile = str_contains($uaL, 'mobile') || str_contains($uaL, 'android') || str_contains($uaL, 'iphone');

                                    $browser = 'Browser';
                                    if (str_contains($ua, 'Edg')) $browser = 'Edge';
                                    elseif (str_contains($ua, 'OPR') || str_contains($ua, 'Opera')) $browser = 'Opera';
                                    elseif (str_contains($ua, 'Chrome')) $browser = 'Chrome';
                                    elseif (str_contains($ua, 'Firefox')) $browser = 'Firefox';
                                    elseif (str_contains($ua, 'Safari')) $browser = 'Safari';

                                    $os = '';
                                    if (str_contains($ua, 'Windows')) $os = 'Windows';
                                    elseif (str_contains($ua, 'Android')) $os = 'Android';
                                    elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) $os = 'iOS';
                                    elseif (str_contains($ua, 'Mac OS')) $os = 'macOS';
                                    elseif (str_contains($ua, 'Linux')) $os = 'Linux';

                                    $createdAt = Carbon::parse($item->created_at);
                                @endphp
                                <li class="ps-history-item {{ $loop->first ? 'is-latest' : '' }}">
                                    <span class="ps-device"><i class="{{ $isMobile ? 'ft-smartphone' : 'ft-monitor' }}"></i></span>
                                    <div class="ps-history-main">
                                        <div class="ps-history-title" title="{{ $ua }}">
                                            {{ $browser }}{{ $os ? ' on ' . $os : '' }}
                                            @if ($loop->first)
                                                <span class="ps-latest">Latest</span>
                                            @endif
                                        </div>
                                        <div class="ps-history-meta">
                                            <span><i class="ft-map-pin"></i>{{ $item->location ?: 'Unknown location' }}</span>
                                        </div>
                                    </div>
                                    <div class="ps-history-time">
                                        <strong>{{ $createdAt->format('h:i A') }}</strong>
                                        <small>{{ $createdAt->format('d M Y') }} &middot; {{ $createdAt->diffForHumans() }}</small>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="ps-empty">
                            <span><i class="ft-clock"></i></span>
                            <p>No login activity yet.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- ================= Cache ================= -->
            {{-- clearAppCache() is defined in management/layouts/header.blade.php --}}
            <div class="tab-pane" id="cache" role="tabpanel" aria-labelledby="cache-tab">
                <div class="ps-card">
                    <div class="ps-card-head">
                        <span class="ps-card-ic"><i class="ft-refresh-ccw"></i></span>
                        <div>
                            <h4>Clear cache</h4>
                            <p>Screen looks old or a change is not showing? Clear the saved files in this browser.</p>
                        </div>
                    </div>

                    <div class="ps-tip">
                        <i class="ft-info"></i>
                        <span>You stay logged in. The page reloads with fresh files.</span>
                    </div>

                    <div class="ps-actions">
                        <button type="button" class="ps-btn position-relative"
                            onclick="clearAppCache(this, @js(route('clear.cache')))">
                            <i class="ft-refresh-ccw"></i> Clear Cache
                        </button>
                    </div>
                </div>

                @if ($me->user_type === 'super-admin')
                    <div class="ps-card">
                        <div class="ps-card-head">
                            <span class="ps-card-ic"><i class="ft-users"></i></span>
                            <div>
                                <h4>Reset cache for all users</h4>
                                <p>Clears the server cache, and every user's browser gets fresh files on their next page load.</p>
                            </div>
                        </div>

                        <div class="ps-tip">
                            <i class="ft-alert-triangle"></i>
                            <span>Use after a deploy if users still see old screens. The first page load for everyone will be a little slower.</span>
                        </div>

                        <div class="ps-actions">
                            <button type="button" class="ps-btn position-relative"
                                onclick="clearAppCache(this, @js(route('clear.cache.all')), 'Reset cache for ALL users? Every user will get fresh files on their next page load.')">
                                <i class="ft-users"></i> Reset for All Users
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        /* ================== Profile Settings ================== */
        .ps-page {
            --ps-brand: #27489a;
            --ps-brand-2: #4a6fd1;
            --ps-card: #fff;
            --ps-text: #1f2333;
            --ps-muted: #7a7f93;
            --ps-border: #eceef4;
            --ps-input: #f7f8fc;
            max-width: 1040px;
            margin: 0 auto;
            padding-bottom: 20px;
        }

        body.layout-dark .ps-page {
            --ps-card: #1c1c20;
            --ps-text: #eceef5;
            --ps-muted: #9a9fb2;
            --ps-border: #2a2a30;
            --ps-input: #23252c;
        }

        /* ---------- Hero ---------- */
        .ps-hero {
            position: relative;
            overflow: hidden;
            margin-top: 6px;
            padding: 30px 32px;
            border-radius: 24px;
            color: #fff;
            background: linear-gradient(135deg, #4a6fd1 0%, #27489a 55%, #1a3270 100%);
            box-shadow: 0 16px 40px rgba(39, 72, 154, .25);
        }

        .ps-hero-deco {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .ps-hero-deco.d1 {
            width: 320px;
            height: 320px;
            right: -90px;
            top: -140px;
            background: radial-gradient(circle, rgba(255, 255, 255, .18), rgba(255, 255, 255, 0) 70%);
        }

        .ps-hero-deco.d2 {
            width: 220px;
            height: 220px;
            right: 140px;
            bottom: -150px;
            border: 36px solid rgba(255, 255, 255, .06);
        }

        .ps-hero-inner {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .ps-avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }

        .ps-avatar {
            width: 112px;
            height: 112px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            background-color: rgba(255, 255, 255, .2);
            border: 4px solid rgba(255, 255, 255, .9);
            box-shadow: 0 10px 28px rgba(0, 0, 0, .25);
        }

        .ps-avatar-btn {
            position: absolute;
            right: 2px;
            bottom: 4px;
            width: 36px;
            height: 36px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 15px;
            color: var(--ps-brand);
            background: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .2);
            cursor: pointer;
            transition: transform .15s;
        }

        .ps-avatar-btn:hover {
            transform: scale(1.08);
        }

        .ps-hero-info {
            min-width: 0;
        }

        .ps-name {
            margin: 0 0 10px;
            font-size: 1.7rem;
            font-weight: 700;
            letter-spacing: -.3px;
            color: #fff;
            word-break: break-word;
        }

        .ps-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .ps-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            max-width: 100%;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 500;
            color: #fff;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .18);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ps-photo-note {
            display: none;
            margin-top: 12px;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 12.5px;
            background: rgba(255, 255, 255, .16);
        }

        .ps-photo-note.show {
            display: inline-block;
        }

        /* ---------- Tabs ---------- */
        .ps-tabs {
            display: flex;
            flex-wrap: nowrap;
            gap: 6px;
            margin: 18px 0 16px;
            padding: 6px;
            border: 1px solid var(--ps-border);
            border-radius: 16px;
            background: var(--ps-card);
            box-shadow: 0 2px 10px rgba(20, 30, 70, .04);
            overflow-x: auto;
            scrollbar-width: none;
        }

        .ps-tabs::-webkit-scrollbar {
            display: none;
        }

        .ps-tabs .nav-item {
            flex: 1 0 auto;
        }

        .ps-tabs .nav-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 12px;
            border: 1px solid transparent;
            font-size: 14px;
            font-weight: 600;
            color: var(--ps-muted);
            white-space: nowrap;
            transition: background .2s, color .2s;
        }

        .ps-tabs .nav-link:hover {
            color: var(--ps-brand);
            background: rgba(39, 72, 154, .05);
        }

        .ps-tabs .nav-link.active {
            color: var(--ps-brand);
            background: linear-gradient(180deg, rgba(39, 72, 154, .14), rgba(39, 72, 154, .06));
            border-color: rgba(39, 72, 154, .22);
        }

        .ps-tab-count {
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 10px;
            font-size: 11px;
            line-height: 20px;
            text-align: center;
            color: #fff;
            background: var(--ps-brand);
        }

        body.layout-dark .ps-tabs .nav-link.active,
        body.layout-dark .ps-tabs .nav-link:hover {
            color: #9db4f0;
            background: rgba(90, 130, 230, .14);
            border-color: rgba(90, 130, 230, .3);
        }

        /* ---------- Card ---------- */
        .ps-card {
            padding: 26px 28px;
            border: 1px solid var(--ps-border);
            border-radius: 22px;
            background: var(--ps-card);
            box-shadow: 0 4px 20px rgba(20, 30, 70, .05);
            animation: psIn .3s ease-out;
        }

        @keyframes psIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .ps-card-head {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--ps-border);
        }

        .ps-card-head h4 {
            margin: 0 0 2px;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--ps-text);
        }

        .ps-card-head p {
            margin: 0;
            font-size: 13px;
            color: var(--ps-muted);
        }

        .ps-card-ic {
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-size: 19px;
            color: #fff;
            background: linear-gradient(135deg, #4a6fd1, #27489a);
            box-shadow: 0 6px 14px rgba(39, 72, 154, .28);
        }

        /* ---------- Fields ---------- */
        .ps-file-input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .ps-field label {
            margin-bottom: 7px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ps-text);
        }

        .ps-input {
            position: relative;
        }

        .ps-input > i {
            position: absolute;
            left: 15px;
            top: 25px;
            transform: translateY(-50%);
            z-index: 2;
            font-size: 16px;
            color: var(--ps-muted);
            pointer-events: none;
            transition: color .2s;
        }

        .ps-input .form-control {
            height: 50px;
            padding: 0 46px 0 44px;
            border-radius: 14px;
            border: 1.5px solid var(--ps-border);
            background: var(--ps-input);
            color: var(--ps-text);
            font-size: 14.5px;
            box-shadow: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .ps-input .form-control:focus {
            border-color: var(--ps-brand-2);
            background: var(--ps-card);
            box-shadow: 0 0 0 4px rgba(39, 72, 154, .12);
        }

        .ps-input:focus-within > i {
            color: var(--ps-brand);
        }

        .ps-input.is-readonly .form-control {
            opacity: .75;
            cursor: not-allowed;
        }

        .ps-input .form-control.is-invalid {
            border-color: #e5484d;
            background-image: none;
        }

        .ps-eye {
            position: absolute;
            right: 7px;
            top: 25px;
            transform: translateY(-50%);
            z-index: 2;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--ps-muted);
            cursor: pointer;
        }

        .ps-eye:hover {
            color: var(--ps-brand);
            background: rgba(39, 72, 154, .08);
        }

        .ps-hint {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: var(--ps-muted);
        }

        .ps-match.ok { color: #1f9d55; }
        .ps-match.bad { color: #e5484d; }

        /* password strength */
        .ps-strength {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 8px;
        }

        .ps-strength span {
            flex: 1;
            height: 5px;
            border-radius: 5px;
            background: var(--ps-border);
            transition: background .25s;
        }

        .ps-strength em {
            min-width: 52px;
            font-style: normal;
            font-size: 11.5px;
            font-weight: 600;
            text-align: right;
            color: var(--ps-muted);
        }

        .ps-strength[data-level="1"] span:nth-child(-n+1) { background: #e5484d; }
        .ps-strength[data-level="2"] span:nth-child(-n+2) { background: #f59e0b; }
        .ps-strength[data-level="3"] span:nth-child(-n+3) { background: #4a6fd1; }
        .ps-strength[data-level="4"] span:nth-child(-n+4) { background: #1f9d55; }

        .ps-tip {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 14px;
            font-size: 13px;
            color: var(--ps-brand);
            background: rgba(39, 72, 154, .06);
            border: 1px solid rgba(39, 72, 154, .14);
        }

        .ps-tip i {
            margin-top: 2px;
        }

        body.layout-dark .ps-tip {
            color: #9db4f0;
            background: rgba(90, 130, 230, .10);
            border-color: rgba(90, 130, 230, .25);
        }

        /* ---------- Buttons ---------- */
        .ps-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 22px;
        }

        .ps-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 180px;
            height: 48px;
            padding: 0 24px;
            border: 0;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #4a6fd1, #27489a);
            box-shadow: 0 10px 22px rgba(39, 72, 154, .28);
            cursor: pointer;
            transition: transform .15s, box-shadow .2s, filter .2s;
        }

        .ps-btn:hover {
            filter: brightness(1.06);
            box-shadow: 0 14px 28px rgba(39, 72, 154, .36);
        }

        .ps-btn:active {
            transform: scale(.98);
        }

        /* ---------- Login history ---------- */
        .ps-history {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .ps-history-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px;
            margin-bottom: 10px;
            border: 1px solid var(--ps-border);
            border-radius: 16px;
            background: var(--ps-input);
        }

        .ps-history-item.is-latest {
            border-color: rgba(39, 72, 154, .25);
            background: rgba(39, 72, 154, .05);
        }

        .ps-device {
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            font-size: 19px;
            color: var(--ps-brand);
            background: rgba(39, 72, 154, .09);
        }

        .ps-history-main {
            flex: 1;
            min-width: 0;
        }

        .ps-history-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--ps-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ps-latest {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10.5px;
            font-weight: 700;
            color: #1f9d55;
            background: rgba(31, 157, 85, .12);
        }

        .ps-history-meta {
            margin-top: 3px;
            font-size: 12.5px;
            color: var(--ps-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ps-history-meta i {
            margin-right: 4px;
        }

        .ps-history-time {
            flex-shrink: 0;
            text-align: right;
        }

        .ps-history-time strong {
            display: block;
            font-size: 13.5px;
            color: var(--ps-text);
        }

        .ps-history-time small {
            font-size: 11.5px;
            color: var(--ps-muted);
        }

        body.layout-dark .ps-device {
            color: #9db4f0;
            background: rgba(90, 130, 230, .14);
        }

        body.layout-dark .ps-history-item.is-latest {
            border-color: rgba(90, 130, 230, .3);
            background: rgba(90, 130, 230, .08);
        }

        .ps-empty {
            padding: 40px 10px;
            text-align: center;
            color: var(--ps-muted);
        }

        .ps-empty span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            margin-bottom: 10px;
            border-radius: 50%;
            font-size: 28px;
            color: var(--ps-brand-2);
            background: rgba(39, 72, 154, .08);
        }

        /* ---------- Mobile ---------- */
        @media (max-width: 767.98px) {
            .ps-hero {
                padding: 26px 18px 22px;
                border-radius: 22px;
            }

            .ps-hero-inner {
                flex-direction: column;
                text-align: center;
                gap: 14px;
            }

            .ps-avatar {
                width: 96px;
                height: 96px;
            }

            .ps-name {
                font-size: 1.35rem;
                margin-bottom: 10px;
            }

            .ps-chips {
                justify-content: center;
            }

            .ps-chip {
                font-size: 11.5px;
            }

            .ps-tabs {
                margin: 14px 0 12px;
            }

            .ps-tabs .nav-link {
                padding: 9px 12px;
                font-size: 13px;
            }

            .ps-card {
                padding: 18px 16px;
                border-radius: 18px;
            }

            .ps-card-head {
                margin-bottom: 18px;
                padding-bottom: 14px;
            }

            .ps-actions {
                justify-content: stretch;
            }

            .ps-btn {
                width: 100%;
            }

            .ps-history-item {
                flex-wrap: wrap;
                padding: 12px;
            }

            .ps-history-time {
                width: 100%;
                padding-left: 58px;
                text-align: left;
            }

            .ps-history-time strong {
                display: inline;
                margin-right: 6px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password show / hide
            document.querySelectorAll('.ps-eye').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var input = btn.parentElement.querySelector('input');
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.querySelector('i').className = show ? 'ft-eye-off' : 'ft-eye';
                    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                });
            });

            // Password strength meter
            var newPass = document.getElementById('ps-new');
            var meter = document.getElementById('psStrength');
            var confirm = document.getElementById('ps-confirm');
            var match = document.getElementById('psMatch');
            var labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];

            function checkStrength() {
                var v = newPass.value;
                var score = 0;
                if (v.length >= 8) score++;
                if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
                if (/\d/.test(v)) score++;
                if (/[^A-Za-z0-9]/.test(v)) score++;
                if (v.length && score === 0) score = 1;
                meter.setAttribute('data-level', v.length ? score : 0);
                meter.querySelector('em').textContent = v.length ? labels[score] : '';
            }

            function checkMatch() {
                if (!confirm.value) {
                    match.textContent = '';
                    match.className = 'ps-hint ps-match';
                    return;
                }
                var ok = confirm.value === newPass.value;
                match.textContent = ok ? 'Passwords match' : 'Passwords do not match';
                match.className = 'ps-hint ps-match ' + (ok ? 'ok' : 'bad');
            }

            if (newPass && meter) {
                newPass.addEventListener('input', function() {
                    checkStrength();
                    checkMatch();
                });
            }
            if (confirm && match) {
                confirm.addEventListener('input', checkMatch);
            }

            // Form reset (password save ke baad) par meter bhi saaf karo
            var pwForm = newPass ? newPass.closest('form') : null;
            if (pwForm) {
                pwForm.addEventListener('reset', function() {
                    setTimeout(function() {
                        checkStrength();
                        checkMatch();
                    }, 0);
                });
            }

            // Nayi photo select hone par note dikhao aur General tab khol do (Save wahin hai)
            var upload = document.getElementById('imageUpload');
            var note = document.getElementById('photoNote');
            if (upload) {
                upload.addEventListener('change', function() {
                    if (upload.files && upload.files.length) {
                        if (note) note.classList.add('show');
                        var gen = document.getElementById('general-tab');
                        if (gen && !gen.classList.contains('active') && window.jQuery) {
                            window.jQuery(gen).tab('show');
                        }
                    }
                });
            }
        });
    </script>

@endsection
