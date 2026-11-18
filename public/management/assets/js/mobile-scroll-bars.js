/* =====================================================================
   Mobile: scroll down -> top header + bottom nav chhup jayen,
           scroll up   -> wapas aa jayen
   CSS: public/management/assets/css/mobile-scroll-bars.css
   ===================================================================== */
(function () {
    'use strict';

    var MOBILE = window.matchMedia('(max-width: 767.98px)');
    var TOP_ZONE = 60;   // page ke itne upar tak bars hamesha dikhen
    var THRESHOLD = 8;   // itni chhoti hil-dul ignore (ungli ka jhatka)

    var body = document.body;
    var lastY = window.pageYOffset || 0;
    var ticking = false;

    // in halaat me bars hamesha dikhne chahiye
    function overlayOpen() {
        return body.classList.contains('menu-open') ||          // side nav
            body.classList.contains('noti-open') ||             // notifications full screen
            body.classList.contains('m-sheet-open') ||          // filter sheet
            body.classList.contains('app-locked') ||            // loader
            !!document.querySelector('.header-navbar .dropdown-menu.show'); // profile / koi header dropdown
    }

    // class sirf tab chhuo jab sach me badalni ho - warna neeche wala MutationObserver
    // (body class par) baar baar chalta aur loop ban jata
    function show() {
        if (body.classList.contains('m-bars-hidden')) body.classList.remove('m-bars-hidden');
    }

    function hide() {
        if (!body.classList.contains('m-bars-hidden')) body.classList.add('m-bars-hidden');
    }

    function update() {
        ticking = false;
        var y = window.pageYOffset || document.documentElement.scrollTop || 0;

        if (!MOBILE.matches || overlayOpen() || y <= TOP_ZONE) {
            show();
            lastY = y;
            return;
        }

        var delta = y - lastY;
        if (Math.abs(delta) < THRESHOLD) return; // lastY mat badlo, chhoti movements jama hon

        if (delta > 0) hide();   // neeche scroll
        else show();             // upar scroll

        lastY = y;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            ticking = true;
            window.requestAnimationFrame(update);
        }
    }, { passive: true });

    // desktop par jate hi saaf
    function onMediaChange() {
        if (!MOBILE.matches) show();
    }
    if (MOBILE.addEventListener) MOBILE.addEventListener('change', onMediaChange);
    else if (MOBILE.addListener) MOBILE.addListener(onMediaChange);

    // koi popup / sheet khulte hi bars wapas (class badalne par)
    if (window.MutationObserver) {
        new MutationObserver(function () {
            if (overlayOpen()) show();
        }).observe(body, { attributes: true, attributeFilter: ['class'] });
    }
})();
