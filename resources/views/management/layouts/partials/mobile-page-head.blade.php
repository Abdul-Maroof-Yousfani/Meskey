{{--
    Mobile page header - desktop par kuch nahi badalta.

    AB GLOBAL: master.blade.php isay har page par (content ke baad) include karta hai.
    - Jis page me neeche wala markup (mph-head classes) pehle se hai -> wahi use hota hai.
    - Baaki pages: script khud "h2.page-title" wali row dhoondh kar mph-* classes laga deta hai
      (autoHead neeche). Kisi page ko is se bachana ho to us header row par: data-no-mph
    - Kuch pages abhi bhi isay khud include karte hain: koi masla nahi, script sirf EK dafa chalta hai.

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
        // page khud bhi include kare aur master bhi -> sirf pehli dafa chalo
        if (window.mphReady) return;
        window.mphReady = true;

        var MOBILE = window.matchMedia('(max-width: 767.98px)');

        // ---- Auto header: jis page par mph-head markup nahi, us ka "page-title" header khud mph bana do ----
        // Sirf classes / icon jodta hai (desktop par .mph-sub / .mph-create-icon CSS se chhupe rehte hain).
        function moduleName() {
            // URL ka pehla hissa: /production/... -> "Production", /export/... -> "Export"
            var seg = (window.location.pathname.split('/').filter(Boolean)[0] || '');
            return seg.replace(/[-_]+/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
        }

        function autoHead() {
            if (document.querySelector('.mph-head')) return; // page ka apna mph header hai
            var title = document.querySelector('.content-wrapper h2.page-title, .main-content h2.page-title');
            if (!title || title.closest('.modal, #modal-sidebar, form, table')) return;
            var row = title.closest('.row');
            if (!row || row.hasAttribute('data-no-mph') || row.closest('[data-no-mph]')) return;

            // title wala column (row ka seedha child)
            var titleCol = title;
            while (titleCol.parentElement && titleCol.parentElement !== row) titleCol = titleCol.parentElement;
            if (titleCol === title && title.parentElement !== row) return;

            row.classList.add('mph-head');
            if (titleCol !== title) titleCol.classList.add('mph-title-col');

            if (!titleCol.querySelector('.mph-sub') && moduleName()) {
                var sub = document.createElement('div');
                sub.className = 'mph-sub';
                sub.textContent = moduleName();
                titleCol.insertBefore(sub, titleCol.firstChild);
            }

            // baaki columns = actions. Add / Create / New wala button -> gol ➕, doosre icon wale button -> gol icon
            Array.prototype.forEach.call(row.children, function (col) {
                if (col === titleCol || col.classList.contains('mph-back')) return; // back button jaisa hai waisa rahe
                col.classList.add('mph-actions');
                var createDone = false;
                col.querySelectorAll('a.btn, button.btn').forEach(function (btn) {
                    var text = (btn.textContent || '').trim();
                    var wrap = function () {
                        if (btn.querySelector('.mph-create-text')) return;
                        var span = document.createElement('span');
                        span.className = 'mph-create-text';
                        while (btn.firstChild) span.appendChild(btn.firstChild);
                        btn.appendChild(span);
                        if (!btn.getAttribute('aria-label') && text) btn.setAttribute('aria-label', text);
                        if (!btn.getAttribute('title') && text) btn.setAttribute('title', text);
                    };
                    if (!createDone && /\b(add|create|new)\b/i.test(text)) {
                        createDone = true;
                        wrap();
                        btn.classList.add('mph-create');
                        var icon = document.createElement('i');
                        icon.className = 'ft-plus mph-create-icon';
                        btn.insertBefore(icon, btn.firstChild);
                    } else {
                        var own = btn.querySelector('i[class*="ft-"], i[class*="fa-"], i.fa');
                        if (own && text) {
                            // apna icon bahar, text chhupa (mobile par sirf icon)
                            var keep = own.cloneNode(true);
                            own.parentNode.removeChild(own);
                            wrap();
                            btn.insertBefore(keep, btn.firstChild);
                            btn.classList.add('mph-icon-btn');
                        }
                    }
                });
            });
        }

        autoHead();
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', autoHead);
        }

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

        // Foran chalao (header aur page header dono is waqt DOM me hain): loading ke dauran
        // (baaki content chhupa, sirf app-bar dikhta hai) navbar aur header ke beech gap na rahe.
        // DOMContentLoaded / load par dobara naapo (layout tab badalta hai).
        fitHead();
        if (document.readyState === 'loading') {
            // Kai pages (jaise arrival/ticket) ye partial apni mph-head row se PEHLE include karte hain:
            // abhi row bani hi nahi. Row DOM me aate hi (parse ke dauran) foran chipkao, load ka intezar nahi.
            if (!document.querySelector('.mph-head') && window.MutationObserver) {
                var waitRow = new MutationObserver(function () {
                    if (document.querySelector('.mph-head')) {
                        waitRow.disconnect();
                        fitHead();
                    }
                });
                waitRow.observe(document.documentElement, { childList: true, subtree: true });
                document.addEventListener('DOMContentLoaded', function () { waitRow.disconnect(); });
            }
            document.addEventListener('DOMContentLoaded', fitHead);
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
