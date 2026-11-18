@extends('management.layouts.master')
@section('title')
    All Notifications
@endsection
@section('content')
    @php
        $unreadCount = auth()->user()->unreadNotifications()->count();
    @endphp

    <div class="content-wrapper nt-page">
        <section id="extended">

            <!-- ================= Header ================= -->
            <div class="nt-hero">
                <span class="nt-hero-deco"></span>
                <div class="nt-hero-left">
                    <span class="nt-hero-ic"><i class="ft-bell"></i></span>
                    <div>
                        <h2 class="nt-title">Notifications</h2>
                        <p class="nt-sub">
                            <span id="ntUnreadText">
                                @if ($unreadCount)
                                    You have <b>{{ $unreadCount }}</b> unread notification{{ $unreadCount > 1 ? 's' : '' }}
                                @else
                                    You're all caught up
                                @endif
                            </span>
                        </p>
                    </div>
                </div>
                {{-- mark-all-read-btn: header.blade.php ka global handler isi class se chalta hai --}}
                <button type="button" class="nt-mark-btn mark-all-read-btn position-relative">
                    <i class="ft-check-circle"></i><span>Mark all as read</span>
                </button>
            </div>

            <!-- ================= Search ================= -->
            <div class="nt-toolbar">
                <form id="filterForm" class="form nt-search-form" onsubmit="return false;">
                    <input type="hidden" name="page" value="{{ request('page', 1) }}">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                    <div class="nt-search">
                        <i class="ft-search"></i>
                        <input type="text" class="form-control" id="search" placeholder="Search notifications..."
                            name="search" value="{{ request('search', '') }}" autocomplete="off">
                    </div>
                </form>
            </div>

            <!-- ================= List ================= -->
            <div class="nt-list-wrap" id="filteredData">
                <table class="nt-table">
                    <thead class="nt-sr-only">
                        <tr>
                            <th>Notification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <style>
        /* ================== All Notifications page ================== */
        .nt-page {
            --nt-brand: #27489a;
            --nt-brand-2: #4a6fd1;
            --nt-card: #fff;
            --nt-text: #2b2a35;
            --nt-muted: #8a8797;
            --nt-border: #eceef4;
            --nt-soft: #f3f2f8;
            max-width: 980px;
            margin: 0 auto;
            padding-bottom: 20px;
        }

        body.layout-dark .nt-page {
            --nt-card: #1c1c20;
            --nt-text: #e4e4e8;
            --nt-muted: #9a98a6;
            --nt-border: #2a2a30;
            --nt-soft: #26262c;
        }

        .nt-sr-only {
            position: absolute !important;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
        }

        /* ---------- Hero ---------- */
        .nt-hero {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 6px;
            padding: 24px 26px;
            border-radius: 22px;
            color: #fff;
            background: linear-gradient(135deg, #4a6fd1 0%, #27489a 60%, #1a3270 100%);
            box-shadow: 0 14px 34px rgba(39, 72, 154, .24);
        }

        .nt-hero-deco {
            position: absolute;
            right: -70px;
            top: -110px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            pointer-events: none;
            background: radial-gradient(circle, rgba(255, 255, 255, .18), rgba(255, 255, 255, 0) 70%);
        }

        .nt-hero-left {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .nt-hero-ic {
            flex-shrink: 0;
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-size: 22px;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .22);
        }

        .nt-title {
            margin: 0 0 3px;
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: -.3px;
            color: #fff;
        }

        .nt-sub {
            margin: 0;
            font-size: 13.5px;
            color: rgba(255, 255, 255, .82);
        }

        .nt-mark-btn {
            position: relative;
            z-index: 1;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 42px;
            padding: 0 18px;
            border: 1px solid rgba(255, 255, 255, .35);
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--nt-brand);
            background: #fff;
            box-shadow: 0 6px 16px rgba(0, 0, 0, .12);
            cursor: pointer;
            transition: transform .15s, box-shadow .2s;
        }

        .nt-mark-btn:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, .18);
        }

        .nt-mark-btn:active {
            transform: scale(.97);
        }

        /* ---------- Search ---------- */
        .nt-toolbar {
            margin: 16px 0 14px;
        }

        .nt-search {
            position: relative;
        }

        .nt-search i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            color: var(--nt-muted);
            pointer-events: none;
        }

        .nt-search .form-control {
            height: 50px;
            padding-left: 46px;
            border-radius: 14px;
            border: 1.5px solid var(--nt-border);
            background: var(--nt-card);
            color: var(--nt-text);
            font-size: 14.5px;
            box-shadow: 0 2px 10px rgba(20, 30, 70, .04);
        }

        .nt-search .form-control:focus {
            border-color: var(--nt-brand-2);
            box-shadow: 0 0 0 4px rgba(39, 72, 154, .12);
        }

        /* ---------- List (table rows ko cards banao) ---------- */
        .nt-table,
        .nt-table tbody {
            display: block;
            width: 100%;
            margin: 0;
            border: 0;
        }

        .nt-table tr {
            display: block;
        }

        .nt-table td {
            display: block;
            padding: 0;
            border: 0 !important;
        }

        /* date group heading */
        .nt-table tr.nt-group td {
            padding: 12px 4px 8px;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: var(--nt-muted);
        }

        .nt-table tr.nt-group:first-child td {
            padding-top: 0;
        }

        .nt-card {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 10px;
            padding: 16px 16px 14px;
            border: 1px solid var(--nt-border);
            border-radius: 18px;
            background: var(--nt-card);
            color: var(--nt-text);
            text-decoration: none !important;
            box-shadow: 0 2px 8px rgba(20, 30, 70, .04);
            transition: transform .15s, box-shadow .2s, border-color .2s;
        }

        .nt-card:hover {
            color: var(--nt-text);
            border-color: rgba(39, 72, 154, .25);
            box-shadow: 0 8px 22px rgba(20, 30, 70, .08);
            transform: translateY(-1px);
        }

        .nt-card:active {
            transform: scale(.99);
        }

        .nt-ic {
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-size: 18px;
            color: var(--nt-muted);
            background: var(--nt-soft);
        }

        .nt-body {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .nt-text {
            font-size: 14px;
            line-height: 1.55;
            color: #4a4858;
            word-break: break-word;
        }

        .nt-time {
            margin-top: 7px;
            font-size: 12px;
            color: var(--nt-muted);
        }

        .nt-time i {
            margin-right: 5px;
            vertical-align: -1px;
        }

        .nt-dot-sep {
            margin: 0 6px;
        }

        .nt-arrow {
            align-self: center;
            flex-shrink: 0;
            font-size: 16px;
            color: #c3c1cc;
            transition: transform .15s, color .15s;
        }

        .nt-card:hover .nt-arrow {
            color: var(--nt-brand);
            transform: translateX(2px);
        }

        .nt-new {
            align-self: flex-start;
            flex-shrink: 0;
            padding: 3px 9px;
            border-radius: 10px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .3px;
            color: #fff;
            background: linear-gradient(135deg, #4a6fd1, #27489a);
        }

        /* unread */
        .nt-row.is-unread .nt-card {
            background: #f6f8fe;
            border-color: rgba(39, 72, 154, .22);
        }

        .nt-row.is-unread .nt-card::before {
            content: "";
            position: absolute;
            left: 0;
            top: 16px;
            bottom: 16px;
            width: 4px;
            border-radius: 0 4px 4px 0;
            background: var(--nt-brand);
        }

        .nt-row.is-unread .nt-ic {
            color: #fff;
            background: linear-gradient(135deg, #4a6fd1, #27489a);
            box-shadow: 0 6px 14px rgba(39, 72, 154, .28);
        }

        .nt-row.is-unread .nt-text {
            font-weight: 600;
            color: var(--nt-text);
        }

        /* empty */
        .nt-empty {
            padding: 50px 20px;
            text-align: center;
            border: 1px dashed var(--nt-border);
            border-radius: 20px;
            background: var(--nt-card);
        }

        .nt-empty span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 76px;
            height: 76px;
            margin-bottom: 14px;
            border-radius: 50%;
            font-size: 30px;
            color: var(--nt-brand-2);
            background: rgba(39, 72, 154, .08);
        }

        .nt-empty h5 {
            margin: 0 0 6px;
            font-weight: 700;
            color: var(--nt-text);
        }

        .nt-empty p {
            margin: 0;
            font-size: 13.5px;
            color: var(--nt-muted);
        }

        /* loading shimmer rows (scripts.js renderLoadingTable) - card jaisa */
        .nt-table tbody.shimmer-table tr td {
            height: 76px;
            margin-bottom: 10px;
            padding: 16px !important;
            border: 1px solid var(--nt-border) !important;
            border-radius: 18px;
            background: var(--nt-card);
            color: transparent;
            overflow: hidden;
        }

        /* ---------- Pagination ---------- */
        #paginationLinks {
            margin: 14px 0 0;
        }

        .nt-pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid var(--nt-border);
            border-radius: 16px;
            background: var(--nt-card);
            box-shadow: 0 2px 10px rgba(20, 30, 70, .04);
            text-align: left;
        }

        .nt-pager-info {
            font-size: 13px;
            color: var(--nt-muted);
        }

        .nt-pager-info b {
            color: var(--nt-text);
        }

        .nt-pager-info {
            flex: 0 0 auto;
        }

        /* right side bachi hui jagah le, pages us me shrink/wrap hon -> JS wrap dekh kar kam numbers dikhata hai */
        .nt-pager-right {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: nowrap;
        }

        .nt-perpage {
            flex: 0 0 auto;
        }

        #paginationLinks .pagination {
            flex: 0 1 auto;
            min-width: 0;
            display: flex;
            align-items: center;
            margin: 0;
            gap: 6px;
            flex-wrap: wrap;
        }

        /* bahut tang screen: "5 / 10" */
        #paginationLinks .nt-compact .page-link {
            min-width: 64px;
            font-weight: 700;
            color: var(--nt-text);
            background: var(--nt-soft);
            border-color: transparent;
            opacity: 1;
        }

        #paginationLinks .page-item.nt-gap .page-link {
            min-width: 24px;
            padding: 0 2px;
            border-color: transparent;
            background: transparent;
            opacity: 1;
            color: var(--nt-muted);
        }

        /* per page dropdown - pagination ke saath ek line me, same height */
        .nt-perpage {
            position: relative;
        }

        .nt-perpage select.form-control {
            height: 38px;
            min-width: 112px;
            padding: 0 32px 0 12px;
            border-radius: 11px;
            border: 1px solid var(--nt-border);
            background: var(--nt-card);
            color: var(--nt-text);
            font-size: 13px;
            font-weight: 600;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            cursor: pointer;
            box-shadow: none;
        }

        .nt-perpage select.form-control:focus {
            border-color: var(--nt-brand-2);
            box-shadow: 0 0 0 3px rgba(39, 72, 154, .12);
        }

        .nt-perpage i {
            position: absolute;
            right: 11px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            color: var(--nt-muted);
            pointer-events: none;
        }

        #paginationLinks .page-link {
            min-width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
            border-radius: 11px !important;
            border: 1px solid var(--nt-border);
            background: var(--nt-card);
            color: var(--nt-text);
            font-size: 13px;
            font-weight: 600;
            box-shadow: none;
        }

        #paginationLinks .page-link:hover {
            color: var(--nt-brand);
            border-color: rgba(39, 72, 154, .3);
            background: rgba(39, 72, 154, .05);
        }

        #paginationLinks .page-item.active .page-link {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(135deg, #4a6fd1, #27489a);
            box-shadow: 0 6px 14px rgba(39, 72, 154, .28);
        }

        #paginationLinks .page-item.disabled .page-link {
            opacity: .5;
        }

        /* ---------- Dark mode ---------- */
        body.layout-dark .nt-text {
            color: #c9c8d1;
        }

        body.layout-dark .nt-row.is-unread .nt-card {
            background: #1f2333;
            border-color: rgba(90, 130, 230, .30);
        }

        body.layout-dark .nt-row.is-unread .nt-card::before {
            background: #9db4f0;
        }

        body.layout-dark .nt-row.is-unread .nt-text {
            color: #f1f1f4;
        }

        body.layout-dark .nt-card:hover .nt-arrow {
            color: #9db4f0;
        }

        body.layout-dark .nt-mark-btn {
            color: #1d3878;
        }

        /* ---------- Mobile ---------- */
        @media (max-width: 767.98px) {
            .nt-hero {
                flex-direction: column;
                align-items: stretch;
                padding: 20px 18px;
                border-radius: 20px;
            }

            .nt-hero-ic {
                width: 46px;
                height: 46px;
                font-size: 20px;
            }

            .nt-title {
                font-size: 1.3rem;
            }

            .nt-mark-btn {
                justify-content: center;
                width: 100%;
            }

            .nt-card {
                gap: 12px;
                padding: 14px 12px 12px;
                border-radius: 16px;
            }

            .nt-ic {
                width: 40px;
                height: 40px;
                font-size: 16px;
                border-radius: 12px;
            }

            .nt-text {
                font-size: 13.5px;
            }

            .nt-arrow {
                display: none;
            }

            .nt-pager {
                flex-direction: column;
                align-items: stretch;
                padding: 12px;
            }

            .nt-pager-info {
                text-align: center;
            }

            .nt-pager-right {
                flex-direction: column;
                align-items: center;
            }

            #paginationLinks .pagination {
                justify-content: center;
                gap: 4px;
                max-width: 100%;
            }

            #paginationLinks .page-link {
                min-width: 34px;
                height: 34px;
                font-size: 12.5px;
            }

            .nt-perpage {
                width: 100%;
            }

            .nt-perpage select.form-control {
                width: 100%;
            }
        }
    </style>
@endsection
@section('script')
    <script>
        // =====================================================================
        // Responsive pagination: jitni jagah ho utne page numbers
        //   level 0: 1 2 3 4 5 … 10   |  1 … 3 4 5 6 7 … 10
        //   level 1: 1 2 3 4 … 10     |  1 … 4 5 6 … 10
        //   level 2: 1 2 3 … 10       |  1 … 5 … 10
        //   level 3: 5 / 10  (bahut tang screen)
        // Buttons doosri line me chale jayen (wrap) to agla chhota level try hota hai.
        // =====================================================================
        (function () {
            var LEVELS = [
                { block: 5, sib: 2 },
                { block: 4, sib: 1 },
                { block: 3, sib: 0 },
            ];

            function pageList(c, last, lv) {
                var block = lv.block, sib = lv.sib, p = [], i;
                if (last <= block + 2) {
                    for (i = 1; i <= last; i++) p.push(i);
                } else if (c <= block - 1) {
                    for (i = 1; i <= block; i++) p.push(i);
                    p.push(last);
                } else if (c >= last - block + 2) {
                    p.push(1);
                    for (i = last - block + 1; i <= last; i++) p.push(i);
                } else {
                    p.push(1);
                    for (i = c - sib; i <= c + sib; i++) p.push(i);
                    p.push(last);
                }
                // gaps: ek page chhoot raha ho to wahi number, warna "…"
                var out = [], prev = 0;
                p.forEach(function (n) {
                    if (prev && n - prev === 2) out.push(prev + 1);
                    else if (prev && n - prev > 2) out.push('gap');
                    out.push(n);
                    prev = n;
                });
                return out;
            }

            function li(html, cls) {
                var el = document.createElement('li');
                el.className = 'page-item nt-num' + (cls ? ' ' + cls : '');
                el.innerHTML = html;
                return el;
            }

            function render(ul, levelIndex) {
                var c = parseInt(ul.dataset.current, 10);
                var last = parseInt(ul.dataset.last, 10);
                var tpl = ul.dataset.url;
                var nextLi = ul.lastElementChild; // prev/next li waise hi rehte hain

                ul.querySelectorAll('.nt-num').forEach(function (el) { el.remove(); });

                if (levelIndex >= LEVELS.length) {
                    ul.insertBefore(li('<span class="page-link">' + c + ' / ' + last + '</span>', 'disabled nt-compact'), nextLi);
                    return;
                }

                pageList(c, last, LEVELS[levelIndex]).forEach(function (n) {
                    var el;
                    if (n === 'gap') {
                        el = li('<span class="page-link">&hellip;</span>', 'disabled nt-gap');
                    } else if (n === c) {
                        el = li('<span class="page-link">' + n + '</span>', 'active');
                        el.setAttribute('aria-current', 'page');
                    } else {
                        el = li('<a class="page-link" href="' + tpl.replace('987654321', n) + '">' + n + '</a>');
                    }
                    ul.insertBefore(el, nextLi);
                });
            }

            function wraps(ul) {
                var first = ul.firstElementChild, last = ul.lastElementChild;
                return first && last && last.offsetTop > first.offsetTop + 2;
            }

            function fitPager() {
                var ul = document.querySelector('#paginationLinks ul.nt-pages');
                if (!ul || !ul.dataset.last) return;
                for (var lv = 0; lv <= LEVELS.length; lv++) {
                    render(ul, lv);
                    if (!wraps(ul)) return;
                }
            }

            // har AJAX list load ke baad (filterationCommon #filteredData ka html badalta hai)
            document.addEventListener('DOMContentLoaded', function () {
                var box = document.getElementById('filteredData');
                if (box && window.MutationObserver) {
                    new MutationObserver(fitPager).observe(box, { childList: true });
                }
                fitPager();
            });

            // screen resize / rotate
            var t;
            window.addEventListener('resize', function () {
                clearTimeout(t);
                t = setTimeout(fitPager, 120);
            });
        })();

        $(document).ready(function () {
            filterationCommon(`{{ route('notifications.getList') }}`);

            // The mark-all-read-btn logic is already handled in header.blade.php globally,
            // but we can refresh the list if needed
            $(document).on('click', '.mark-all-read-btn', function() {
                setTimeout(function() {
                    filterationCommon(`{{ route('notifications.getList') }}`);
                    $('#ntUnreadText').text("You're all caught up");
                }, 500); // give time for the header ajax to finish
            });
        });
    </script>
@endsection
