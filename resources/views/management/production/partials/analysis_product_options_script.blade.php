<script>
    // Refill a product select with [{id, name}]; the current value is kept when it is still in the list
    window.fillAnalysisProductOptions = function ($select, products) {
        var current = String($select.val() || '');
        var html = '<option value="">Select Product</option>';
        var found = false;
        (products || []).forEach(function (p) {
            if (String(p.id) === current) found = true;
            html += '<option value="' + p.id + '">' + $('<div>').text(p.name).html() + '</option>';
        });
        $select.html(html).val(found ? current : '').trigger('change.select2');
        return found;
    };

    // Make sure a (fixed) product is in the select even when the list does not have it
    window.ensureAnalysisProductOption = function ($select, id, name) {
        if (id && !$select.find('option[value="' + id + '"]').length) {
            $select.append('<option value="' + id + '">' + $('<div>').text(name || ('#' + id)).html() + '</option>');
        }
    };
</script>
