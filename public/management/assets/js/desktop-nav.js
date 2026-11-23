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
            left = Math.max(EDGE, window.innerWidth - width - EDGE);
        }
        menu.style.top = (r.bottom + GAP) + 'px';
        menu.style.left = left + 'px';
    }

    function placeOpen() {
        if (!DESKTOP.matches) return;
        var open = document.querySelectorAll(ITEM + '.show');
        for (var i = 0; i < open.length; i++) place(open[i]);
    }

    function onPointer(e) {
        if (!DESKTOP.matches || !e.target.closest) return;
        var li = e.target.closest(ITEM);
        if (li) {
            place(li);
            // dropdown khulne ke baad us ki asal chaurai se dobara (screen ke kinare wala hisaab)
            window.requestAnimationFrame(function () { place(li); });
        }
    }

    document.addEventListener('mouseover', onPointer);
    document.addEventListener('click', onPointer, true);
    window.addEventListener('resize', placeOpen);
    window.addEventListener('scroll', placeOpen, { passive: true });

    // menu bar side me scroll ho to khula dropdown saath chale + ‹ › buttons update
    // ("scroll" bubble nahi hota -> capture)
    document.addEventListener('scroll', function (e) {
        if (e.target && e.target.matches && e.target.matches('.navbar-horizontal .main-menu-content > ul')) {
            placeOpen();
            updateArrows();
        }
    }, true);

    // ---------- ‹ › buttons: menu bahar nikal raha ho to aage / peeche ----------
    function getList() {
        return document.querySelector('body.horizontal-menu .navbar-horizontal .main-menu-content > ul');
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
                    var step = Math.max(160, Math.round(list.clientWidth * 0.6));
                    list.scrollBy({ left: dir === 'prev' ? -step : step, behavior: 'smooth' });
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
        var atStart = ul.scrollLeft <= 2;
        var atEnd = ul.scrollLeft + ul.clientWidth >= ul.scrollWidth - 2;
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
    // Theme (app-menu.js) hover par .show lagata / mouseleave par hatata hai. Pin wale li par
    // hum .show wapas laga dete hain. Band: dobara click, bahar click, Esc, ya doosra menu hover.
    var PIN = 'nav-pinned';

    function topLi(el) {
        return el && el.closest ? el.closest(ITEM) : null;
    }

    function unpinAll(except) {
        var list = document.querySelectorAll(ITEM + '.' + PIN);
        for (var i = 0; i < list.length; i++) {
            if (list[i] === except) continue;
            list[i].classList.remove(PIN, 'show');
        }
    }

    document.addEventListener('click', function (e) {
        if (!DESKTOP.matches || !e.target.closest) return;
        if (e.target.closest('.nav-scroll-btn')) return;

        var toggle = e.target.closest(ITEM + ' > .nav-link');
        if (toggle) {
            var li = toggle.parentElement;
            if (li.classList.contains(PIN)) {
                li.classList.remove(PIN, 'show');
            } else {
                unpinAll(li);
                li.classList.add(PIN, 'show');
                place(li);
            }
            return;
        }

        // dropdown ke andar ka page link -> pin khatam (submenu kholne wale click par nahi)
        var link = e.target.closest(ITEM + ' .dropdown-item');
        if (link && !link.parentElement.classList.contains('dropdown-submenu')) {
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
            .on('mouseenter', ITEM, function () {
                if (!DESKTOP.matches) return;
                if (this.classList.contains(PIN)) {
                    this.classList.add('show'); // theme khule par hover ko toggle (band) kar deta hai
                } else {
                    unpinAll(); // doosra menu hover kiya -> purana pin band
                }
            })
            .on('mouseleave', ITEM, function () {
                if (this.classList.contains(PIN)) this.classList.add('show');
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
