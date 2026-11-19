/* =====================================================================
   Mobile list cards: ⋯ (more) actions -> neeche se khulne wali sheet (GLOBAL)
   CSS: public/management/assets/css/mobile-ui.css (section 6: .tkc-sheet, .tkc-backdrop, .tkc-sheet-item)

   Card markup (kisi bhi list ka partial):
     <div class="tkc">
       ...
       <button type="button" class="tkc-more" data-title="#123" aria-label="More actions">
           <i class="ft-more-horizontal"></i>
       </button>
       <template class="tkc-extra">
           <a href="..." class="tkc-sheet-item"><i class="ft-edit"></i><span>Edit</span></a>
           <a href="javascript:;" class="tkc-sheet-item is-danger" onclick="..."><i class="ft-x"></i><span>Delete</span></a>
       </template>
     </div>

   Lists AJAX se baar baar aati hain -> sheet + listeners sirf EK baar (document par delegation).
   ===================================================================== */
(function () {
    'use strict';

    if (window.tkcSheetReady) return;
    window.tkcSheetReady = true;

    function init() {
        var backdrop = document.createElement('div');
        backdrop.className = 'tkc-backdrop';
        var sheet = document.createElement('div');
        sheet.className = 'tkc-sheet';
        sheet.setAttribute('role', 'dialog');
        sheet.setAttribute('aria-modal', 'true');
        sheet.innerHTML =
            '<span class="tkc-sheet-grip"></span>' +
            '<div class="tkc-sheet-title"></div>' +
            '<div class="tkc-sheet-body"></div>' +
            '<button type="button" class="tkc-sheet-cancel">Cancel</button>';
        document.body.appendChild(backdrop);
        document.body.appendChild(sheet);

        function closeSheet() {
            sheet.classList.remove('open');
            backdrop.classList.remove('show');
            document.body.classList.remove('tkc-sheet-open');
        }

        function openSheet(btn) {
            var card = btn.closest('.tkc');
            var tpl = card ? card.querySelector('template.tkc-extra') : null;
            if (!tpl) return;
            sheet.querySelector('.tkc-sheet-title').textContent = btn.getAttribute('data-title') || 'Actions';
            var body = sheet.querySelector('.tkc-sheet-body');
            body.innerHTML = '';
            body.appendChild(tpl.content.cloneNode(true));
            backdrop.classList.add('show');
            sheet.classList.add('open');
            document.body.classList.add('tkc-sheet-open');
        }

        // ⋯ button card ke "tap = View" wale hisse ke bahar hota hai, is liye wo View trigger nahi karta
        document.addEventListener('click', function (e) {
            var more = e.target.closest ? e.target.closest('.tkc-more') : null;
            if (more) {
                e.preventDefault();
                openSheet(more);
            }
        });

        backdrop.addEventListener('click', closeSheet);
        sheet.querySelector('.tkc-sheet-cancel').addEventListener('click', closeSheet);
        // sheet ka koi action dabaya -> uska onclick/href chale, phir sheet band
        sheet.querySelector('.tkc-sheet-body').addEventListener('click', function (e) {
            if (e.target.closest('a, button')) setTimeout(closeSheet, 0);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSheet();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
