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
    var entry = null;      // { form, placeholder, origin, sheet, body, stickers, stickersWrap, appliedStripe, appliedTags, fab, _activeFilters, _appliedFilters }
    var backdrop = null;
    var isOpen = false;

    // ---------- helpers ----------
    function isCountable(el) {
        if (!el.name) return false;
        if (el.type === 'hidden' || el.type === 'submit' || el.type === 'button') return false;
        if (el.name === '_token' || el.name === 'daterange' || el.name === 'daterange2' || el.name === 'page' || el.name === 'per_page') return false;
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

    function cleanLabel(txt) {
        if (!txt) return '';
        return txt.replace(/[:*]/g, '').trim();
    }

    function getFieldLabel(el) {
        var isSearch = (el.name === 'search' || el.id === 'search');
        if (el.id) {
            var lbl = document.querySelector('label[for="' + el.id + '"]');
            if (lbl && lbl.textContent.trim()) return cleanLabel(lbl.textContent);
        }
        var group = el.closest('.form-group') || el.closest('[class*="col"]') || el.parentElement;
        if (group) {
            var lbl2 = group.querySelector('label');
            if (lbl2 && lbl2.textContent.trim()) return cleanLabel(lbl2.textContent);
        }
        if (isSearch) return 'Search';
        if (el.placeholder && el.placeholder.trim()) {
            var ph = el.placeholder.trim();
            if (!/^search\b/i.test(ph)) return cleanLabel(ph);
        }
        var n = (el.name || '').replace(/\[\]$/, '').replace(/_id$/i, '').replace(/_/g, ' ');
        return n ? cleanLabel(n.charAt(0).toUpperCase() + n.slice(1)) : 'Filter';
    }

    function getFieldIcon(el) {
        var name = (el.name || '').toLowerCase();
        var id = (el.id || '').toLowerCase();
        var label = (getFieldLabel(el) || '').toLowerCase();
        var combined = name + ' ' + id + ' ' + label;

        if (combined.indexOf('search') !== -1) return 'ft-search';
        if (combined.indexOf('locat') !== -1 || combined.indexOf('port') !== -1 || combined.indexOf('destination') !== -1 || combined.indexOf('origin') !== -1) return 'ft-map-pin';
        if (combined.indexOf('truck') !== -1 || combined.indexOf('vehicle') !== -1 || combined.indexOf('bilty') !== -1) return 'ft-truck';
        if (combined.indexOf('product') !== -1 || combined.indexOf('item') !== -1 || combined.indexOf('commodity') !== -1) return 'ft-box';
        if (combined.indexOf('supplier') !== -1 || combined.indexOf('customer') !== -1 || combined.indexOf('broker') !== -1 || combined.indexOf('party') !== -1 || combined.indexOf('account') !== -1 || combined.indexOf('user') !== -1) return 'ft-user';
        if (combined.indexOf('date') !== -1 || combined.indexOf('time') !== -1 || combined.indexOf('day') !== -1) return 'ft-calendar';
        if (combined.indexOf('status') !== -1 || combined.indexOf('approval') !== -1) return 'ft-check-circle';
        if (combined.indexOf('type') !== -1 || combined.indexOf('category') !== -1) return 'ft-layers';
        if (combined.indexOf('amount') !== -1 || combined.indexOf('price') !== -1 || combined.indexOf('weight') !== -1) return 'ft-hash';
        return 'ft-tag';
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function getActiveFilters(form) {
        var list = [];
        var elements = form.querySelectorAll('input[name], select[name], textarea[name]');
        elements.forEach(function (el) {
            if (!isCountable(el)) return;
            var val = '';
            var valText = '';

            if (el.type === 'checkbox' || el.type === 'radio') {
                if (el.checked) {
                    val = el.value || '1';
                    valText = 'Yes';
                }
            } else if (el.multiple) {
                var sel = Array.prototype.filter.call(el.options, function (o) {
                    return o.selected && o.value && !/^(all|select\b.*)$/i.test(o.text.trim());
                });
                if (sel.length > 0) {
                    val = '1';
                    valText = sel.map(function (o) { return o.text.trim(); }).join(', ');
                }
            } else if (el.tagName === 'SELECT') {
                if (el.value !== null && String(el.value).trim() !== '' && String(el.value).toLowerCase() !== 'all') {
                    var opt = el.options[el.selectedIndex];
                    valText = opt ? opt.text.trim() : String(el.value).trim();
                    if (!/^(all|select\b.*)$/i.test(valText)) {
                        val = el.value;
                    }
                }
            } else {
                if (el.value !== null && String(el.value).trim() !== '') {
                    val = String(el.value).trim();
                    valText = val;
                }
            }

            if (val !== '') {
                list.push({
                    el: el,
                    name: el.name,
                    label: getFieldLabel(el),
                    icon: getFieldIcon(el),
                    valueText: valText
                });
            }
        });
        return list;
    }

    function clearSingleField(el) {
        var $ = window.jQuery;
        if (!el) return;
        if (el.type === 'checkbox' || el.type === 'radio') {
            if ($) $(el).prop('checked', false).trigger('change');
            else {
                el.checked = false;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        } else if (el.tagName === 'SELECT') {
            if ($) $(el).val(el.multiple ? [] : '').trigger('change');
            else {
                el.value = '';
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        } else {
            if ($) $(el).val('').trigger('keyup').trigger('change');
            else {
                el.value = '';
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
        updateCount();
    }

    function triggerFormSubmit(form) {
        if (!form) return;
        if (window.jQuery) {
            window.jQuery(form).trigger('submit');
            // jQuery ka trigger('submit') native addEventListener('submit') nahi chalata
            // (aur page ka handler preventDefault karta hai) -> applied stripe yahin update karo
            updateAppliedStripe();
        } else {
            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        }
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
            '<div class="m-sheet-stickers" id="mSheetStickers" hidden>' +
                '<div class="m-stickers-wrap"></div>' +
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

        // Header ke neeche applied filters stripe (sirf mobile)
        var appliedStripe = document.createElement('div');
        appliedStripe.className = 'm-applied-filters-stripe';
        appliedStripe.id = 'mAppliedFiltersStripe';
        appliedStripe.hidden = true;
        appliedStripe.innerHTML =
            '<div class="m-applied-filters-inner">' +
                '<span class="m-applied-label" title="Applied Filters"><i class="ft-filter"></i></span>' +
                '<div class="m-applied-tags"></div>' +
                '<button type="button" class="m-applied-clear-all" data-clear-applied-all title="Clear all filters"><i class="ft-trash-2"></i></button>' +
            '</div>';

        var headerTarget = document.querySelector('.mph-head') || document.querySelector('.row > [class*="col"] > .page-title');
        if (headerTarget) {
            var insertAfter = headerTarget.classList.contains('mph-head') ? headerTarget : headerTarget.closest('.row');
            if (insertAfter && insertAfter.parentNode) {
                insertAfter.parentNode.insertBefore(appliedStripe, insertAfter.nextSibling);
            } else {
                origin.insertBefore(appliedStripe, placeholder);
            }
        } else {
            origin.insertBefore(appliedStripe, placeholder);
        }

        entry = {
            form: form,
            placeholder: placeholder,
            origin: origin,
            sheet: sheet,
            body: sheet.querySelector('.m-sheet-body'),
            stickers: sheet.querySelector('#mSheetStickers'),
            stickersWrap: sheet.querySelector('.m-stickers-wrap'),
            appliedStripe: appliedStripe,
            appliedTags: appliedStripe.querySelector('.m-applied-tags'),
            fab: fab,
            _activeFilters: [],
            _appliedFilters: []
        };

        fab.addEventListener('click', openSheet);
        backdrop.addEventListener('click', closeSheet);
        sheet.addEventListener('click', function (e) {
            var delBtn = e.target.closest('[data-del-idx]');
            if (delBtn) {
                e.preventDefault();
                e.stopPropagation();
                var idx = parseInt(delBtn.getAttribute('data-del-idx'), 10);
                if (entry && entry._activeFilters && entry._activeFilters[idx]) {
                    var targetEl = entry._activeFilters[idx].el;
                    clearSingleField(targetEl);
                }
                return;
            }
            if (e.target.closest('[data-m-sheet-close]')) {
                e.preventDefault();
                var apply = !!e.target.closest('[data-m-sheet-apply]');
                closeSheet();
                // button wala filter (form[data-filter-apply]): "Show results" = filter lagao.
                // Page ka submit handler (jQuery) filter chalata hai; auto-filter wale forms par kuch nahi.
                if (apply && form.hasAttribute('data-filter-apply')) {
                    triggerFormSubmit(form);
                }
            } else if (e.target.closest('[data-m-sheet-clear]')) {
                e.preventDefault();
                clearFilters();
            }
        });

        // Applied stripe par click listener (individual tag delete ya Clear all)
        appliedStripe.addEventListener('click', function (e) {
            var delBtn = e.target.closest('[data-del-applied]');
            if (delBtn) {
                e.preventDefault();
                e.stopPropagation();
                var idx = parseInt(delBtn.getAttribute('data-del-applied'), 10);
                if (entry && entry._appliedFilters && entry._appliedFilters[idx]) {
                    var targetEl = entry._appliedFilters[idx].el;
                    clearSingleField(targetEl);
                    triggerFormSubmit(entry.form);
                }
                return;
            }
            if (e.target.closest('[data-clear-applied-all]')) {
                e.preventDefault();
                clearFilters();
                triggerFormSubmit(entry.form);
            }
        });

        // Form submit listener: filter apply hone par stripe update karo
        form.addEventListener('submit', function () {
            updateAppliedStripe();
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

    // ---------- count badge + modal stickers ----------
    function updateCount() {
        if (!entry) return;
        var active = getActiveFilters(entry.form);
        var n = active.length;

        var badge = entry.fab.querySelector('.m-filter-count');
        badge.textContent = n;
        badge.hidden = n === 0;

        updateStickers(active);
    }

    function updateStickers(active) {
        if (!entry || !entry.stickers || !entry.stickersWrap) return;
        active = active || getActiveFilters(entry.form);
        entry._activeFilters = active;

        if (active.length === 0) {
            entry.stickers.hidden = true;
            entry.stickersWrap.innerHTML = '';
            return;
        }

        entry.stickers.hidden = false;
        var html = '';
        active.forEach(function (item, idx) {
            var safeLabel = escapeHtml(item.label || 'Filter');
            var safeVal = escapeHtml(item.valueText);
            var iconClass = item.icon || 'ft-tag';
            html +=
                '<span class="m-filter-sticker" title="' + safeLabel + ': ' + safeVal + '">' +
                    '<i class="' + iconClass + '"></i>' +
                    '<span class="m-sticker-val">' + safeVal + '</span>' +
                    '<button type="button" class="m-sticker-del" data-del-idx="' + idx + '" aria-label="Remove ' + safeLabel + '">&times;</button>' +
                '</span>';
        });
        entry.stickersWrap.innerHTML = html;
    }

    // ---------- applied filters stripe (header ke neeche: apply hone par) ----------
    function updateAppliedStripe() {
        if (!entry || !entry.appliedStripe) return;
        var active = getActiveFilters(entry.form);
        entry._appliedFilters = active;

        if (active.length === 0) {
            entry.appliedStripe.hidden = true;
            if (entry.appliedTags) entry.appliedTags.innerHTML = '';
            return;
        }

        entry.appliedStripe.hidden = false;
        var html = '';
        active.forEach(function (item, idx) {
            var safeLabel = escapeHtml(item.label || 'Filter');
            var safeVal = escapeHtml(item.valueText);
            var iconClass = item.icon || 'ft-tag';
            html +=
                '<span class="m-filter-sticker" title="' + safeLabel + ': ' + safeVal + '">' +
                    '<i class="' + iconClass + '"></i>' +
                    '<span class="m-sticker-val">' + safeVal + '</span>' +
                    '<button type="button" class="m-sticker-del" data-del-applied="' + idx + '" aria-label="Remove ' + safeLabel + '">&times;</button>' +
                '</span>';
        });
        if (entry.appliedTags) entry.appliedTags.innerHTML = html;

        var clearAllBtn = entry.appliedStripe.querySelector('[data-clear-applied-all]');
        if (clearAllBtn) {
            clearAllBtn.hidden = (active.length <= 1);
        }
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
                $(el).val('').trigger('keyup').trigger('change');
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
        updateAppliedStripe();
        if (window.jQuery && entry) {
            window.jQuery(entry.form).on('change keyup', '[name]', updateCount);
            window.jQuery(entry.form).on('submit', function () {
                updateAppliedStripe();
            });
        }
    });
})();
