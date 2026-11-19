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
{{-- CSS: public/management/assets/css/mobile-ui.css (section 5) --}}

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

            // header ke bilkul neeche chipkao:
            //  gap > 0 -> beech ka khaali hissa khatam (row upar khincho)
            //  gap < 0 -> row fixed header ke neeche dabi hui hai; sticky use visually neeche
            //             dhakel kar content ke upar le aata tha -> row (aur us ke saath content) neeche karo
            var rowTop = row.getBoundingClientRect().top + window.pageYOffset;
            var gap = rowTop - top;
            if (gap !== 0 && gap < 140) {
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
        // load par theme (app.js) header ko "fixed-top" karti hai -> layout badalta hai.
        // Ye listener theme se pehle chalta hai, is liye theme ke baad dobara naapo.
        window.addEventListener('load', function () {
            fitHead();
            setTimeout(fitHead, 60);
            setTimeout(fitHead, 700); // menu transit (500ms) ke baad
        });
        var t;
        window.addEventListener('resize', function () {
            clearTimeout(t);
            t = setTimeout(fitHead, 150);
        });
    })();
</script>
