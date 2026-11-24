/* =====================================================================
   Desktop horizontal menu: saare menu ek line + side scroll (CSS: mobile-ui.css section 17)
   Scroll wala dabba (ul) apne andar ke absolute dropdown ko kaat deta hai, is liye top-level
   dropdown CSS me "position: fixed" hai. Yeh script us dropdown ko us menu item ke theek
   neeche rakhta hai (hover / click / scroll / resize par). Sirf >= 1200px (theme yahin horizontal rakhta hai).
   ===================================================================== */
(function () {
    'use strict';

    var DESKTOP = window.matchMedia('(min-width: 1200px)');
    var ITEM = 'body.horizontal-menu .navbar-horizontal .main-menu-content > ul > li.dropdown';
    var GAP = 6;      // menu item aur dropdown ke beech
    var EDGE = 8;     // screen ke kinare se kam se kam faasla

    function place(li) {
        if (!li) return;
        var menu = null;
        for (var i = 0; i < li.children.length; i++) {
            if (li.children[i].classList.contains('dropdown-menu')) { menu = li.children[i]; break; }
        }
        if (!menu) return;

        var r = li.getBoundingClientRect();
        var width = menu.offsetWidth || 240;
        var left = r.left;
        if (left + width > window.innerWidth - EDGE) {
            left = window.innerWidth - width - EDGE;
        }
        // item scroll ho kar aadha baayen chhupa ho to dropdown screen se bahar na jaye
        left = Math.max(EDGE, left);
        var top = r.bottom + GAP;
        menu.style.top = top + 'px';
        menu.style.left = left + 'px';
        // lamba dropdown (jaise Export, 21 items) screen se neeche na jaye -> andar scroll
        menu.style.maxHeight = Math.max(160, window.innerHeight - top - EDGE) + 'px';
    }

    function childMenu(li) {
        for (var i = 0; i < li.children.length; i++) {
            if (li.children[i].classList.contains('dropdown-menu')) return li.children[i];
        }
        return null;
    }

    // submenu (jaise Setup): item ke daayen, jagah na ho to baayen; neeche jagah kam ho to upar khiskao
    function placeSub(li) {
        var menu = childMenu(li);
        if (!menu) return;
        var r = li.getBoundingClientRect();
        var w = menu.offsetWidth || 240;
        var left = r.right + 4;
        var openLeft = left + w > window.innerWidth - EDGE;
        if (openLeft) left = Math.max(EDGE, r.left - w - 4);
        li.classList.toggle('openLeft', openLeft);

        var avail = window.innerHeight - EDGE * 2;
        var h = Math.min(menu.scrollHeight || 0, avail);
        var top = r.top - 6;
        if (top + h > window.innerHeight - EDGE) top = window.innerHeight - EDGE - h;
        top = Math.max(EDGE, top);
        menu.style.setProperty('top', top + 'px', 'important');
        menu.style.setProperty('left', left + 'px', 'important');
        menu.style.maxHeight = avail + 'px';
    }

    function placeOpen() {
        if (!DESKTOP.matches) return;
        var open = document.querySelectorAll(ITEM + '.show');
        for (var i = 0; i < open.length; i++) place(open[i]);
        var subs = document.querySelectorAll(ITEM + ' li.dropdown-submenu.show');
        for (var j = 0; j < subs.length; j++) placeSub(subs[j]);
    }

    function onPointer(e) {
        if (!DESKTOP.matches || !e.target.closest) return;
        var li = e.target.closest(ITEM);
        if (li) {
            place(li);
            // dropdown khulne ke baad us ki asal chaurai se dobara (screen ke kinare wala hisaab)
            window.requestAnimationFrame(function () { place(li); });
        }
        var sub = e.target.closest(ITEM + ' li.dropdown-submenu');
        if (sub) {
            placeSub(sub);
            window.requestAnimationFrame(function () { placeSub(sub); });
            setTimeout(function () { placeSub(sub); }, 0); // theme click ke .show ke baad
        }
    }

    document.addEventListener('mouseover', onPointer);
    document.addEventListener('click', onPointer, true);
    window.addEventListener('resize', placeOpen);
    window.addEventListener('scroll', placeOpen, { passive: true });

    // menu bar side me scroll ho to khula dropdown saath chale + ‹ › buttons update
    // ("scroll" bubble nahi hota -> capture)
    document.addEventListener('scroll', function (e) {
        if (!e.target || !e.target.matches) return;
        if (e.target.matches('.navbar-horizontal .main-menu-content > ul')) {
            placeOpen();
            updateArrows();
        } else if (e.target.matches('.navbar-horizontal .main-menu-content .dropdown-menu')) {
            placeOpen(); // lamba dropdown andar scroll hua -> khula submenu saath chale
        }
    }, true);

    // ---------- ‹ › buttons: menu bahar nikal raha ho to aage / peeche ----------
    function getList() {
        return document.querySelector('body.horizontal-menu .navbar-horizontal .main-menu-content > ul');
    }

    // apni animation: kuch browsers / settings me scrollBy({behavior:'smooth'}) chalta hi nahi
    // Ek click = agla (ya pichla) ek menu item poora nazar aaye. Pehle 60% chaurai ka jump tha:
    // chhoti screen par menu thora hi bahar hota hai -> ek click me seedha aakhir / shuru, aur ‹ fauran ghaib.
    var EDGE_PAD = 48; // ‹ › button + fade ki jagah
    function stepTarget(ul, dir) {
        var sl = anim ? anim.to : ul.scrollLeft; // jaldi jaldi click -> agle target se aage
        var cw = ul.clientWidth;
        var base = ul.getBoundingClientRect().left - ul.scrollLeft; // scroll 0 par ul ka left
        var items = ul.children;
        var i, r, left, right;
        if (dir === 'next') {
            for (i = 0; i < items.length; i++) {
                r = items[i].getBoundingClientRect();
                right = r.right - base;
                if (right > sl + cw - EDGE_PAD + 1) return right - cw + EDGE_PAD;
            }
            return ul.scrollWidth;
        }
        for (i = items.length - 1; i >= 0; i--) {
            r = items[i].getBoundingClientRect();
            left = r.left - base;
            if (left < sl + EDGE_PAD - 1) return left - EDGE_PAD;
        }
        return 0;
    }

    var anim = null;
    function animateScrollTo(el, target) {
        if (anim) cancelAnimationFrame(anim.id);
        var from = el.scrollLeft;
        var max = el.scrollWidth - el.clientWidth;
        var to = Math.max(0, Math.min(max, Math.round(target)));
        var start = null, dur = 260;
        anim = { to: to, id: 0 };
        function frame(ts) {
            if (start === null) start = ts;
            var p = Math.min(1, (ts - start) / dur);
            var ease = 1 - Math.pow(1 - p, 3);
            el.scrollLeft = from + (to - from) * ease;
            if (p < 1) { anim.id = requestAnimationFrame(frame); }
            else { anim = null; updateArrows(); }
        }
        anim.id = requestAnimationFrame(frame);
    }

    function ensureArrows() {
        var ul = getList();
        if (!ul) return null;
        var box = ul.parentElement; // .main-menu-content
        // theme breakpoint badalne par menu ka HTML dobara banata hai -> buttons dobara lagao
        if (!box.querySelector(':scope > .nav-scroll-btn')) {
            box.classList.add('has-nav-scroll');
            ['prev', 'next'].forEach(function (dir) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'nav-scroll-btn is-' + dir;
                b.setAttribute('aria-label', dir === 'prev' ? 'Scroll menu left' : 'Scroll menu right');
                b.innerHTML = '<i class="' + (dir === 'prev' ? 'ft-chevron-left' : 'ft-chevron-right') + '"></i>';
                b.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var list = getList();
                    if (!list) return;
                    animateScrollTo(list, stepTarget(list, dir));
                });
                box.appendChild(b);
            });
        }
        return box;
    }

    function updateArrows() {
        var box = ensureArrows();
        var ul = getList();
        if (!box || !ul) return;
        var prev = box.querySelector(':scope > .nav-scroll-btn.is-prev');
        var next = box.querySelector(':scope > .nav-scroll-btn.is-next');
        // ul khud bahar phail jaye (dabba kaat de) to bhi overflow maano
        var overflow = DESKTOP.matches &&
            (ul.scrollWidth > ul.clientWidth + 2 || ul.scrollWidth > box.clientWidth + 2);
        // sirf scrollLeft par bharosa nahi: pehla / aakhri item sach me poora nazar aa raha ho tab hi chhupao
        var ur = ul.getBoundingClientRect();
        var first = ul.firstElementChild, last = ul.lastElementChild;
        var firstSeen = !first || first.getBoundingClientRect().left >= ur.left - 2;
        var lastSeen = !last || last.getBoundingClientRect().right <= ur.right + 2;
        var atStart = ul.scrollLeft <= 2 && firstSeen;
        var atEnd = ul.scrollLeft + ul.clientWidth >= ul.scrollWidth - 2 && lastSeen;
        prev.hidden = !overflow || atStart;
        next.hidden = !overflow || atEnd;
        box.classList.toggle('can-prev', overflow && !atStart);
        box.classList.toggle('can-next', overflow && !atEnd);
    }

    var arrowTimer;
    function scheduleArrows() {
        clearTimeout(arrowTimer);
        arrowTimer = setTimeout(updateArrows, 120);
    }

    // ---------- click = dropdown khula ruk jaye (hover chhodne par bhi band na ho) ----------
    // Theme (app-menu.js) hover par .show lagata / mouseleave par fauran hatata hai.
    // - Pin: top menu ya submenu (jaise Setup) par click -> wo aur us ka parent dono khule rukte hain.
    //   Band: dobara click, bahar click, Esc, ya doosra top menu hover.
    // - Hover: mouse bahar jaye to thori der (GRACE) khula rehta hai, taa ke neeche / side me
    //   dropdown tak aaram se pahunch sako (CSS ::after bhi beech ka gap bharta hai).
    var PIN = 'nav-pinned';
    var GRACE = 300;  // ms
    var ANY = 'body.horizontal-menu .navbar-horizontal .main-menu-content li.dropdown'; // top + submenu
    var SUB = ITEM + ' li.dropdown-submenu';

    function topLi(el) {
        return el && el.closest ? el.closest(ITEM) : null;
    }

    function clearGrace(li) {
        if (li && li._navGrace) { clearTimeout(li._navGrace); li._navGrace = null; }
    }

    function closeLi(li) {
        clearGrace(li);
        li.classList.remove(PIN, 'show');
        // andar ke khule submenu bhi band
        var inner = li.querySelectorAll('li.dropdown.show, li.dropdown.' + PIN);
        for (var i = 0; i < inner.length; i++) { clearGrace(inner[i]); inner[i].classList.remove(PIN, 'show'); }
    }

    // sub aur top menu ke beech ke saare submenu li (andar se bahar)
    function parentSubs(sub, top) {
        var list = [];
        var li = sub.parentElement ? sub.parentElement.closest('li.dropdown-submenu') : null;
        while (li && li !== top && (!top || top.contains(li))) {
            list.push(li);
            li = li.parentElement ? li.parentElement.closest('li.dropdown-submenu') : null;
        }
        return list;
    }

    function pinAll(list) {
        for (var i = 0; i < list.length; i++) {
            clearGrace(list[i]);
            list[i].classList.add(PIN, 'show');
        }
    }

    function unpinAll(except) {
        var list = document.querySelectorAll(ANY + '.' + PIN + ', ' + ANY + '.show');
        for (var i = 0; i < list.length; i++) {
            if (except && (list[i] === except || list[i].contains(except) || except.contains(list[i]))) continue;
            closeLi(list[i]);
        }
    }

    document.addEventListener('click', function (e) {
        if (!DESKTOP.matches || !e.target.closest) return;
        if (e.target.closest('.nav-scroll-btn')) return;

        // top menu par click
        var toggle = e.target.closest(ITEM + ' > .nav-link');
        if (toggle) {
            var li = toggle.parentElement;
            if (li.classList.contains(PIN)) {
                closeLi(li);
            } else {
                unpinAll(li);
                clearGrace(li);
                li.classList.add(PIN, 'show');
                place(li);
            }
            return;
        }

        // submenu (jaise Setup) par click -> submenu + us ke upar ke saare menu pin
        // (teesra level, jaise Production > Milling > Production Analysis: beech wala Milling bhi
        //  pin hona zaroori hai, warna mouse hatte hi Milling band ho kar andar wala bhi band kar deta)
        var subToggle = e.target.closest(SUB + ' > .dropdown-item');
        if (subToggle) {
            var sub = subToggle.parentElement;
            var willPin = !sub.classList.contains(PIN);
            var top = topLi(sub);
            var parents = parentSubs(sub, top);
            if (willPin) {
                unpinAll(sub);
                // bahan submenu band
                var sibs = sub.parentElement.children;
                for (var s = 0; s < sibs.length; s++) if (sibs[s] !== sub && sibs[s].classList.contains('dropdown')) closeLi(sibs[s]);
                sub.classList.add(PIN);
                pinAll(parents);
                if (top) { clearGrace(top); top.classList.add(PIN, 'show'); }
            } else {
                closeLi(sub); // khud + andar ke khule / pinned submenu band
            }
            // theme ka click handler .show toggle karta hai -> us ke baad sahi state
            setTimeout(function () {
                clearGrace(sub);
                sub.classList.toggle('show', willPin);
                if (willPin) {
                    pinAll(parents);
                    if (top) top.classList.add('show');
                    placeSub(sub);
                }
            }, 0);
            return;
        }

        // dropdown ke andar ka page link -> sab band (page khul raha hai)
        if (e.target.closest(ITEM + ' .dropdown-item')) {
            unpinAll();
            return;
        }

        if (!topLi(e.target)) unpinAll();
    }, true);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') unpinAll();
    });

    // theme ke hover handlers ke BAAD chalna zaroori hai -> jQuery delegated (document par)
    if (window.jQuery) {
        window.jQuery(document)
            .on('mouseenter', ANY, function () {
                if (!DESKTOP.matches) return;
                var li = this;
                clearGrace(li);
                li.classList.add('show'); // theme khule par hover ko toggle (band) kar deta hai

                // isi level ke doosre khule (pin nahi) fauran band - do dropdown ek saath na dikhen
                var sibs = li.parentElement.children;
                for (var i = 0; i < sibs.length; i++) {
                    var o = sibs[i];
                    if (o === li || !o.classList.contains('dropdown')) continue;
                    if (o.matches(ITEM)) {
                        // doosra top menu hover -> us ka pin bhi khatam
                        if (li.matches(ITEM)) closeLi(o);
                    } else if (!o.classList.contains(PIN)) {
                        closeLi(o);
                    }
                }
                if (li.matches(ITEM)) place(li);
            })
            .on('mouseleave', ANY, function () {
                if (!DESKTOP.matches) return;
                var li = this;
                if (li.classList.contains(PIN)) { li.classList.add('show'); return; }
                // theme ne .show hata diya -> thori der wapas rakho
                li.classList.add('show');
                clearGrace(li);
                li._navGrace = setTimeout(function () {
                    li._navGrace = null;
                    if (!li.classList.contains(PIN) && !li.matches(':hover')) closeLi(li);
                }, GRACE);
            });
    }

    // ---------- jis page par ho, dropdown ke andar us ka item bhi active ----------
    function cleanPath(p) {
        return (p || '/').replace(/\/+$/, '') || '/';
    }

    // desktop (horizontal) aur mobile (side nav) dono: theme mobile par class badal deta hai
    // (dropdown-menu / dropdown-item hat jate hain) is liye sirf #main-menu-navigation ka dhancha use karo
    var NAV = '#main-menu-navigation';

    function markActive() {
        var nav = document.querySelector(NAV);
        if (!nav) return;
        var here = cleanPath(location.pathname);
        var links = nav.querySelectorAll(':scope > li li > a[href]');
        var best = null, bestLen = -1;

        for (var i = 0; i < links.length; i++) {
            var a = links[i];
            var raw = a.getAttribute('href') || '';
            if (!raw || raw.charAt(0) === '#' || raw.indexOf('javascript') === 0) continue;
            // host nahi milate (APP_URL / IP / port alag ho sakta hai) - sirf path
            var p = cleanPath(a.pathname);
            if (p === '/') continue;
            // pura match ya aage "/..." (jaise /export/order/12/edit -> /export/order)
            if ((here === p || here.indexOf(p + '/') === 0) && p.length > bestLen) {
                best = a;
                bestLen = p.length;
            }
        }
        if (!best) return;

        var old = nav.querySelectorAll(':scope > li li.active');
        for (var j = 0; j < old.length; j++) old[j].classList.remove('active');

        var mobile = !DESKTOP.matches;

        // item + us ke upar ke saare submenu li + top menu
        var top = null;
        for (var t = 0; t < nav.children.length; t++) {
            if (nav.children[t].contains(best)) { top = nav.children[t]; break; }
        }
        var li = best.parentElement;
        while (li && li.tagName === 'LI') {
            li.classList.add('active');
            // mobile side nav: group pehle se khula ho taa ke active item seedha nazar aaye
            if (mobile && li !== best.parentElement) li.classList.add('open');
            if (li === top) break;
            li = li.parentElement ? li.parentElement.closest('li') : null;
        }

        // sahi top menu mil gaya -> baaki top menus se active hatao (warna do panel active dikhte)
        if (top) {
            var tops = nav.children;
            for (var k = 0; k < tops.length; k++) {
                if (tops[k] !== top) tops[k].classList.remove('active', 'sidebar-group-active');
            }
            // active menu scroll me chhupa ho to nazar me le aao (sirf desktop bar)
            var ul = nav;
            if (!mobile && ul.scrollWidth > ul.clientWidth) {
                var off = top.offsetLeft - (ul.clientWidth - top.offsetWidth) / 2;
                ul.scrollLeft = Math.max(0, off);
            }
        }
    }

    function start() {
        markActive();
        updateArrows();
        setTimeout(updateArrows, 300);
        setTimeout(updateArrows, 800);  // theme menu init (app.js load) ke baad
        setTimeout(updateArrows, 2000); // fonts / icons late load
        var ul = getList();
        if (ul && window.ResizeObserver) {
            var ro = new ResizeObserver(scheduleArrows);
            ro.observe(ul);
            ro.observe(ul.parentElement);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
    window.addEventListener('load', function () {
        markActive();
        updateArrows();
    });
    window.addEventListener('resize', scheduleArrows);
    // desktop <-> mobile: theme menu ki classes badal deta hai -> active dobara lagao
    function onLayoutChange() {
        scheduleArrows();
        setTimeout(markActive, 300);
    }
    if (DESKTOP.addEventListener) DESKTOP.addEventListener('change', onLayoutChange);
    else if (DESKTOP.addListener) DESKTOP.addListener(onLayoutChange);
    // theme mobile side nav load par banata hai -> us ke baad bhi
    setTimeout(markActive, 900);
})();
