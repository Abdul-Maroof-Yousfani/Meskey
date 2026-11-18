{{--
    Mobile page header (opt-in, sirf jis page me include ho) - desktop par kuch nahi badalta.

    Use (page ke @section('content') ke andar kahin bhi):
        @include('management.layouts.partials.mobile-page-head')

    Markup:
        <div class="row w-100 mx-auto align-items-center mph-head">
            <div class="col-6 mph-title-col">
                <div class="mph-sub">Arrival</div>                      (sirf mobile)
                <h2 class="page-title mb-0">Ticket List</h2>
            </div>
            <div class="col-6 text-right mph-actions">
                <a href="..." class="btn btn-primary mph-create" aria-label="Create ..." title="Create ...">
                    <i class="ft-plus mph-create-icon"></i><span class="mph-create-text">Create ...</span>
                </a>
            </div>
        </div>

    Mobile par: poori width app-bar, top header ke neeche chipka hua aur sticky,
    header chhupe (scroll) to screen ke top par; ➕ soft gol button (sirf icon).
--}}
<style>
    /* desktop: sirf text (pehle jaisa) */
    .mph-sub,
    .mph-create-icon {
        display: none;
    }

    @media (max-width: 767.98px) {

        /* theme ke .wrapper ka overflow:hidden sticky tod deta hai; clip sticky nahi rokta */
        @supports (overflow: clip) {
            .wrapper {
                overflow: clip;
            }
        }

        /* poori width app-bar, top header ke neeche chipka hua (JS margin-top set karta hai) */
        .mph-head {
            display: flex;
            flex-wrap: nowrap !important;
            align-items: center !important;
            gap: 12px;
            width: 100vw !important;
            max-width: 100vw !important;
            margin-left: calc(50% - 50vw) !important;
            margin-right: calc(50% - 50vw) !important;
            margin-bottom: 14px !important;
            padding: 10px 16px;
            border-bottom: 1px solid #ececf2;
            background: #fff;
            box-shadow: 0 4px 14px rgba(30, 20, 60, .05);
        }

        /* sticky: header dikhe to us ke neeche, header chhupe (m-bars-hidden) to bilkul top par */
        .mph-head.mph-sticky {
            position: sticky !important;
            top: var(--mph-sticky-top, 60px);
            z-index: 990;
            transition: top .28s cubic-bezier(.2, .8, .2, 1);
        }

        body.m-bars-hidden .mph-head.mph-sticky {
            top: 0;
        }

        /* header ke neeche wala content (table card) thora neeche se shuru ho,
           sticky header ke peeche na chhupe */
        .mph-head ~ .row {
            margin-top: 12px;
        }

        /* scroll par theme top header ko shadow deti hai (body.page-scrolled) jo neeche chipke bar par girti hai */
        body.page-scrolled nav.header-navbar {
            box-shadow: none !important;
        }

        .mph-head > [class*="col"] {
            flex: 0 0 auto !important;
            width: auto !important;
            max-width: none !important;
            padding: 0 !important;
        }

        /* "Welcome back / naam" jaisa: module (chhota grey) / title (semibold) */
        .mph-head .mph-title-col {
            flex: 1 1 auto !important;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1.2;
            text-align: left !important;
        }

        .mph-sub {
            display: block;
            font-size: 11px;
            font-weight: 400;
            line-height: 1.2;
            color: #8a8a8a;
        }

        .mph-head .page-title {
            margin: 0 !important;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.2;
            color: #342e49;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* actions: [filter] [+] */
        .mph-head .mph-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            flex-shrink: 0 !important;
        }

        /* ➕ soft gol button, sirf icon */
        .mph-create {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            min-width: 0;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 50% !important;
            color: #4a6fd1 !important;
            background: rgba(39, 72, 154, .06) !important;
            box-shadow: none !important;
        }

        .mph-create:active {
            background: rgba(39, 72, 154, .12) !important;
        }

        .mph-create-text {
            display: none;
        }

        .mph-create-icon {
            display: inline-block;
            font-size: 16px;
            line-height: 1;
        }

        /* dark mode */
        body.layout-dark .mph-head {
            border-bottom-color: #2a2a30;
            background: #1b1b1f;
            box-shadow: none;
        }

        body.layout-dark .mph-sub {
            color: #9a9a9a;
        }

        body.layout-dark .mph-head .page-title {
            color: #e6e6e6;
        }

        body.layout-dark .mph-create {
            color: #9db4f0 !important;
            background: rgba(90, 130, 230, .10) !important;
        }
    }
</style>

<script>
    // ===== Mobile page header: top header se chipkao + sticky (desktop par kuch nahi) =====
    (function () {
        var MOBILE = window.matchMedia('(max-width: 767.98px)');

        function fitHead() {
            var row = document.querySelector('.mph-head');
            var header = document.querySelector('nav.header-navbar');
            if (!row) return;

            // reset (sticky hata kar asal jagah naapo)
            row.classList.remove('mph-sticky');
            row.style.removeProperty('margin-top');
            if (!MOBILE.matches || !header) return;

            // header ki asal height (offsetHeight transform se affect nahi hoti - header chhupa ho tab bhi sahi)
            var top = header.offsetTop + header.offsetHeight;
            document.documentElement.style.setProperty('--mph-sticky-top', top + 'px');

            // header ke bilkul neeche chipkao (beech ka gap khatam)
            var rowTop = row.getBoundingClientRect().top + window.pageYOffset;
            var gap = rowTop - top;
            if (gap > 0 && gap < 140) {
                var current = parseFloat(window.getComputedStyle(row).marginTop) || 0;
                row.style.setProperty('margin-top', (current - gap) + 'px', 'important');
            }
            row.classList.add('mph-sticky');
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fitHead);
        } else {
            fitHead();
        }
        window.addEventListener('load', fitHead);
        var t;
        window.addEventListener('resize', function () {
            clearTimeout(t);
            t = setTimeout(fitHead, 150);
        });
    })();
</script>
