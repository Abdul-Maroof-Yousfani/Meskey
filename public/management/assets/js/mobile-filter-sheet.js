/* =====================================================================
   Mobile filter bottom sheet (global)
   CSS: public/management/assets/css/mobile-ui.css (section 2)

   Kaise kaam karta hai:
   - Har page par "form#filterForm" (ya koi bhi form[data-filter-sheet]) dhoondta hai.
   - Agar us me kam se kam 2 visible filters hon, to:
       * mobile (< 768px): form ko neeche se khulne wali sheet me le jata hai,
         aur form ki asal jagah (table ke upar) ek chhota filter button (icon only) dikhata hai.
       * desktop: form apni asal jagah rehta hai, kuch nahi badalta.
   - Form ko copy nahi kiya jata, wahi element move hota hai, is liye
     select2 / daterangepicker / filterationCommon sab pehle jaise chalte hain.

   Opt-out:  <form id="filterForm" data-no-filter-sheet>
   Opt-in:   <form data-filter-sheet>  (1 field wale form par bhi lagana ho)
   Button filter: <form data-filter-apply> -> "Show results" form ka submit chalata hai
             (page: filterationCommon_withbtn + $('#filterForm').on('submit', ...runFilter))
   ===================================================================== */
(function () {
    'use strict';

    var MOBILE = window.matchMedia('(max-width: 767.98px)');
    var entry = null;      // { form, placeholder, origin, sheet, body, fab }
    var backdrop = null;
    var isOpen = false;

    // ---------- helpers ----------
    function isCountable(el) {
        if (!el.name) return false;
        if (el.type === 'hidden' || el.type === 'submit' || el.type === 'button') return false;
        if (el.name === '_token' || el.name === 'daterange' || el.name === 'daterange2') return false;
        return true;
    }

    function visibleFieldCount(form) {
        return form.querySelectorAll(
            'input:not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea'
        ).length;
    }

    function findForm() {
        var forms = document.querySelectorAll('form#filterForm, form[data-filter-sheet]');
        for (var i = 0; i < forms.length; i++) {
            var f = forms[i];
            if (f.hasAttribute('data-no-filter-sheet')) continue;
            if (f.hasAttribute('data-filter-sheet') || visibleFieldCount(f) >= 2) return f;
        }
        return null;
    }

    // ---------- filter button ki jagah ----------
    // page title row (.row > .col > .page-title) ke actions column me, sabse pehle (➕ ke left me)
    function placeFab(fab, origin, placeholder) {
        var title = document.querySelector('.row > [class*="col"] > .page-title');
        if (title) {
            var titleCol = title.parentElement;
            var row = titleCol.parentElement;
            var actions = null;
            for (var i = 0; i < row.children.length; i++) {
                var c = row.children[i];
                if (c !== titleCol && /(^|\s)col/.test(c.className)) { actions = c; break; }
            }
            if (!actions) {
                actions = document.createElement('div');
                actions.className = 'col-auto';
                row.appendChild(actions);
            }
            fab.classList.add('in-title');
            actions.insertBefore(fab, actions.firstChild);
            return;
        }
        origin.insertBefore(fab, placeholder);
    }

    // ---------- build ----------
    function build(form) {
        var placeholder = document.createComment(' m-filter-sheet: form ki asal jagah ');
        var origin = form.parentNode;
        origin.insertBefore(placeholder, form);

        var sheet = document.createElement('div');
        sheet.className = 'm-filter-sheet';
        sheet.id = 'mFilterSheet';
        sheet.setAttribute('role', 'dialog');
        sheet.setAttribute('aria-modal', 'true');
        sheet.setAttribute('aria-label', 'Filters');
        sheet.innerHTML =
            '<div class="m-sheet-head">' +
                '<span class="m-sheet-grip"></span>' +
                '<div class="m-sheet-title"><i class="ft-sliders"></i> Filters</div>' +
                '<button type="button" class="m-sheet-clear" data-m-sheet-clear>Clear all</button>' +
                '<button type="button" class="m-sheet-close" data-m-sheet-close aria-label="Close"><i class="ft-x"></i></button>' +
            '</div>' +
            '<div class="m-sheet-body"></div>' +
            '<div class="m-sheet-foot">' +
                '<button type="button" class="m-sheet-apply" data-m-sheet-close data-m-sheet-apply><i class="ft-check"></i> Show results</button>' +
            '</div>';

        var fab = document.createElement('button');
        fab.type = 'button';
        fab.className = 'm-filter-fab';
        fab.setAttribute('aria-label', 'Filters');
        fab.title = 'Filters';
        fab.innerHTML = '<i class="ft-sliders"></i><span class="m-filter-count" hidden></span>';

        backdrop = document.createElement('div');
        backdrop.className = 'm-sheet-backdrop';

        document.body.appendChild(backdrop);
        document.body.appendChild(sheet);
        // button: page title bar me ➕ (create) ke saath; title bar na ho to filters ki asal jagah.
        // desktop par CSS isay chhupa deti hai.
        placeFab(fab, origin, placeholder);

        entry = {
            form: form,
            placeholder: placeholder,
            origin: origin,
            sheet: sheet,
            body: sheet.querySelector('.m-sheet-body'),
            fab: fab
        };

        fab.addEventListener('click', openSheet);
        backdrop.addEventListener('click', closeSheet);
        sheet.addEventListener('click', function (e) {
            if (e.target.closest('[data-m-sheet-close]')) {
                e.preventDefault();
                var apply = !!e.target.closest('[data-m-sheet-apply]');
                closeSheet();
                // button wala filter (form[data-filter-apply]): "Show results" = filter lagao.
                // Page ka submit handler (jQuery) filter chalata hai; auto-filter wale forms par kuch nahi.
                if (apply && form.hasAttribute('data-filter-apply') && window.jQuery) {
                    window.jQuery(form).trigger('submit');
                }
            } else if (e.target.closest('[data-m-sheet-clear]')) {
                e.preventDefault();
                clearFilters();
            }
        });
    }

    // ---------- mobile <-> desktop ----------
    function place() {
        if (!entry) return;
        var form = entry.form;

        if (MOBILE.matches) {
            if (form.parentNode !== entry.body) entry.body.appendChild(form);
            // form ke jane se asal container (e.g. card-header) khaali reh gaya to mobile par chhupa do
            entry.origin.classList.toggle('m-filter-origin-empty', entry.origin.children.length === 0);
        } else {
            closeSheet();
            if (form.parentNode === entry.body && entry.placeholder.parentNode) {
                entry.placeholder.parentNode.insertBefore(form, entry.placeholder.nextSibling);
            }
            entry.origin.classList.remove('m-filter-origin-empty');
        }
        updateCount();
    }

    // ---------- open / close ----------
    function openSheet() {
        if (!entry || !MOBILE.matches) return;
        updateCount();
        isOpen = true;
        backdrop.classList.add('show');
        entry.sheet.classList.add('open');
        document.body.classList.add('m-sheet-open');
    }

    function closeSheet() {
        if (!entry) return;
        // khula hua select2 dropdown bhi band karo
        var $ = window.jQuery;
        if ($ && $.fn && $.fn.select2) {
            $(entry.form).find('select').each(function () {
                var $s = $(this);
                if ($s.data('select2')) {
                    try { $s.select2('close'); } catch (err) { /* ignore */ }
                }
            });
        }
        isOpen = false;
        if (backdrop) backdrop.classList.remove('show');
        entry.sheet.classList.remove('open');
        document.body.classList.remove('m-sheet-open');
    }

    // ---------- count badge ----------
    function updateCount() {
        if (!entry) return;
        var n = 0;
        entry.form.querySelectorAll('input[name], select[name], textarea[name]').forEach(function (el) {
            if (!isCountable(el)) return;
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (el.checked) n++;
                return;
            }
            if (el.multiple) {
                if (Array.prototype.some.call(el.options, function (o) { return o.selected && o.value; })) n++;
                return;
            }
            if (el.value !== null && String(el.value).trim() !== '') n++;
        });
        var badge = entry.fab.querySelector('.m-filter-count');
        badge.textContent = n;
        badge.hidden = n === 0;
    }

    // ---------- clear all ----------
    function clearFilters() {
        var $ = window.jQuery;
        if (!entry || !$) return;
        $(entry.form).find('input[name], select[name], textarea[name]').each(function () {
            var el = this;
            if (!isCountable(el)) return;
            if (el.type === 'checkbox' || el.type === 'radio') {
                $(el).prop('checked', false).trigger('change');
            } else if (el.tagName === 'SELECT') {
                $(el).val(el.multiple ? [] : '').trigger('change');
            } else {
                // filterationCommon text inputs par "keyup" sunta hai
                $(el).val('').trigger('keyup');
            }
        });
        updateCount();
    }

    // ---------- init ----------
    function init() {
        var form = findForm();
        if (!form) return;
        build(form);
        place();

        if (MOBILE.addEventListener) MOBILE.addEventListener('change', place);
        else if (MOBILE.addListener) MOBILE.addListener(place); // purane Safari

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen) closeSheet();
        });

        // native input events
        form.addEventListener('input', updateCount);
        form.addEventListener('change', updateCount);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // select2 / daterangepicker jQuery se "change" bhejte hain jo native listener tak nahi pahunchta.
    // Page poora load hone ke baad aakhri window.jQuery par suno (vendors.min.js jQuery dobara load karta hai).
    window.addEventListener('load', function () {
        updateCount();
        if (window.jQuery && entry) {
            window.jQuery(entry.form).on('change keyup', '[name]', updateCount);
        }
    });
})();
