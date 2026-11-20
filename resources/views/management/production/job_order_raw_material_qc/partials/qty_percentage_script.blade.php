<script>
    /*
     * Sugg. QTY share in RM QC.
     * Every row % and every product % is worked out on the total Sugg. QTY of all products together.
     * e.g. Product A rows 100 + 100 and Product B row 100 -> each row 33.33%, Product A 66.67%, Product B 33.33%.
     */
    window.updateRmQcQtyPercentages = function () {
        var $tables = $('.commodity-table');
        var grandTotal = 0;
        $tables.find('.suggested-quantity').each(function () {
            grandTotal += parseFloat($(this).val()) || 0;
        });

        var pct = function (qty) {
            return grandTotal > 0 ? (qty / grandTotal) * 100 : 0;
        };

        $tables.each(function () {
            var $table = $(this);
            var productTotal = 0;
            $table.find('tbody tr').each(function () {
                var qty = parseFloat($(this).find('.suggested-quantity').val()) || 0;
                productTotal += qty;
                $(this).find('.row-qty-percent').text(pct(qty).toFixed(2) + '%');
            });
            var productPct = pct(productTotal);
            $table.find('.total-qty-percent').html('<strong>' + productPct.toFixed(2) + '%</strong>');
            // Badge after the product name, e.g. 85% / 15%
            $table.find('.product-qty-percent').text(parseFloat(productPct.toFixed(2)) + '%');
        });
    };

    $(document).off('input.rmQcPct', '.suggested-quantity').on('input.rmQcPct', '.suggested-quantity', function () {
        updateRmQcQtyPercentages();
    });
    // Rows added / removed: work it out again after the page's own handler ran
    $(document).off('click.rmQcPct', '.add-location-row, .remove-row').on('click.rmQcPct', '.add-location-row, .remove-row', function () {
        setTimeout(updateRmQcQtyPercentages, 0);
    });

    updateRmQcQtyPercentages();
</script>
