<style>
    /* Packing Details layout (Job Order Request / Job Order) */
    #packingItems { counter-reset: packingItem; }
    #packingItems .packing-item {
        counter-increment: packingItem;
        border: 1px solid #dfe3e8;
        border-radius: 6px;
        background: #fff;
        padding: 0 6px 12px;
        margin-bottom: 16px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    #packingItems .packing-item .form-group { margin-bottom: 10px; }
    #packingItems .packing-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f5f7fa;
        border-bottom: 1px solid #dfe3e8;
        border-radius: 6px 6px 0 0;
        padding: 6px 12px;
        margin-bottom: 12px;
    }
    #packingItems .packing-item-title::before {
        content: "Packing Item #" counter(packingItem);
        font-weight: 600;
        color: #333;
    }
    #packingItems .packing-item-actions .btn + .btn { margin-left: 6px; }
    /* First packing item can not be removed */
    #packingItems .packing-item:first-child .remove-packing-item { display: none; }
</style>
<script>
    /*
     * Copies a packing row (with its master packing sub items) and inserts the copy right below it.
     * Without a row (header Duplicate button) the last packing item is copied.
     * Used by Job Order Request and Job Order (create / edit).
     * The page then calls its own reindexPackingItems() to fix the packing_items[n] indexes.
     */
    window.duplicatePackingItem = function ($row) {
        if (!$row || !$row.length) {
            $row = $('#packingItems .packing-item').last();
        }
        var $sourceFields = $row.find('input, select, textarea');

        // jQuery clone() does not carry select / textarea values, so read them first
        var values = [];
        var hadSelect2 = [];
        $sourceFields.each(function () {
            var $f = $(this);
            values.push($f.is(':checkbox, :radio') ? $f.prop('checked') : $f.val());
            hadSelect2.push($f.is('select') && $f.hasClass('select2-hidden-accessible'));
        });

        var $clone = $row.clone(false);

        // Drop the cloned Select2 UI; it is created again after insert
        $clone.find('.select2-container').remove();
        $clone.find('select')
            .removeClass('select2-hidden-accessible')
            .removeAttr('data-select2-id aria-hidden tabindex')
            .find('option').removeAttr('data-select2-id');

        $clone.find('input, select, textarea').each(function (i) {
            var $f = $(this);

            // Temporary index so reindexPackingItems() renames this row
            var name = $f.attr('name');
            if (name) {
                $f.attr('name', name.replace(/^packing_items\[\d+\]/, 'packing_items[99999]'));
            }

            if ($f.is(':file')) {
                $f.val('');
            } else if ($f.is(':checkbox, :radio')) {
                $f.prop('checked', values[i]);
            } else {
                $f.val(values[i]);
            }
        });

        // The copy is a new row, not the saved one
        $clone.find('input.packing-item-ref').val('');

        $row.after($clone);

        $clone.find('select').each(function (i) {
            var $s = $(this);
            var index = $clone.find('input, select, textarea').index($s);
            if (hadSelect2[index]) {
                $s.select2({ width: '100%' });
            }
        });

        // Fumigation lock (if the page uses it) is worked out again for the copy
        $clone.find('select.fumigation-locked').trigger('change.fumigationLock');

        return $clone;
    };
</script>
