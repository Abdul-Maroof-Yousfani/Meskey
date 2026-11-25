{{-- Loading slip line items (one per packaging): table + totals + balance checks. Used by create and edit. --}}
<script>
    function escapeHtml(text) {
        return $('<div>').text(text == null ? '' : String(text)).html();
    }

    // One row per packaging (DO packing row) of the ticket's Delivery Orders.
    // savedItems: { do_packing_item_id: {no_of_bags, empty_bags, extra_bags} } (edit)
    function packagingItemsTableHtml(packagings, savedItems) {
        if (!packagings.length) {
            return '<div class="text-muted">No packaging found in the Delivery Order.</div>';
        }

        var rows = packagings.map(function(p, i) {
            var saved = savedItems[p.delivery_order_packing_item_id] || {};
            return `
                <tr class="slip-item-row"
                    data-bag-size="${p.bag_size}"
                    data-remaining-bags="${p.remaining_bags}"
                    data-pool="${escapeHtml(p.empty_extra_pool)}"
                    data-available-empty="${p.available_empty_bags}"
                    data-available-extra="${p.available_extra_bags}"
                    data-label="${escapeHtml(p.label)}">
                    <td>${escapeHtml(p.do_reference)}</td>
                    <td>
                        <strong>${escapeHtml(p.bag_type_name)}</strong>${p.brand_name ? '<br><small class="text-muted">' + escapeHtml(p.brand_name) + '</small>' : ''}
                        <input type="hidden" name="items[${i}][delivery_order_packing_item_id]" value="${p.delivery_order_packing_item_id}">
                    </td>
                    <td>${p.bag_size} KG</td>
                    <td>${p.remaining_bags} <small class="text-muted d-block">of ${p.total_bags}</small></td>
                    <td><input type="number" name="items[${i}][no_of_bags]" class="form-control form-control-sm slip-no-of-bags" min="0" max="${p.remaining_bags}" step="1" value="${saved.no_of_bags || ''}" placeholder="0"></td>
                    <td>
                        <input type="number" name="items[${i}][empty_bags]" class="form-control form-control-sm slip-empty-bags" min="0" step="1" value="${saved.empty_bags || ''}" placeholder="0">
                        <small class="text-muted">Available: ${p.available_empty_bags}</small>
                    </td>
                    <td>
                        <input type="number" name="items[${i}][extra_bags]" class="form-control form-control-sm slip-extra-bags" min="0" step="1" value="${saved.extra_bags || ''}" placeholder="0">
                        <small class="text-muted">Available: ${p.available_extra_bags}</small>
                    </td>
                    <td class="slip-item-kg text-right">0.00</td>
                </tr>`;
        }).join('');

        return `
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="slip_items_table">
                    <thead class="thead-light">
                        <tr>
                            <th>DO</th>
                            <th>Packaging</th>
                            <th>Bag Size</th>
                            <th>Balance Bags</th>
                            <th>No. of Bags</th>
                            <th>Empty Bags</th>
                            <th>Extra Bags</th>
                            <th class="text-right">KG</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <td colspan="4" class="text-right">Total</td>
                            <td id="slip_total_bags">0</td>
                            <td id="slip_total_empty">0</td>
                            <td id="slip_total_extra">0</td>
                            <td id="slip_total_kg" class="text-right">0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>`;
    }

    // Row KG, totals and each packaging's balance check
    function recalcSlipItems() {
        var totalBags = 0, totalEmpty = 0, totalExtra = 0, totalKg = 0;
        var pools = {};
        var errors = [];

        $('#slip_items_table .slip-item-row').each(function() {
            var $row = $(this);
            var bags = parseInt($row.find('.slip-no-of-bags').val()) || 0;
            var empty = parseInt($row.find('.slip-empty-bags').val()) || 0;
            var extra = parseInt($row.find('.slip-extra-bags').val()) || 0;
            var kg = bags * (parseFloat($row.data('bag-size')) || 0);
            var label = $row.data('label');

            $row.find('.slip-item-kg').text(kg.toFixed(2));
            totalBags += bags; totalEmpty += empty; totalExtra += extra; totalKg += kg;

            var remaining = parseInt($row.data('remaining-bags')) || 0;
            $row.find('.slip-no-of-bags').toggleClass('is-invalid', bags > remaining);
            if (bags > remaining) errors.push(`${label}: No. of Bags (${bags}) is more than its balance (${remaining}).`);

            var pool = $row.data('pool');
            pools[pool] = pools[pool] || { label: label, empty: 0, extra: 0, availableEmpty: parseInt($row.data('available-empty')) || 0, availableExtra: parseInt($row.data('available-extra')) || 0, rows: [] };
            pools[pool].empty += empty;
            pools[pool].extra += extra;
            pools[pool].rows.push($row);
        });

        Object.keys(pools).forEach(function(key) {
            var p = pools[key];
            var emptyOver = p.empty > p.availableEmpty;
            var extraOver = p.extra > p.availableExtra;
            p.rows.forEach(function($row) {
                $row.find('.slip-empty-bags').toggleClass('is-invalid', emptyOver);
                $row.find('.slip-extra-bags').toggleClass('is-invalid', extraOver);
            });
            if (emptyOver) errors.push(`${p.label}: Empty Bags (${p.empty}) are more than available (${p.availableEmpty}).`);
            if (extraOver) errors.push(`${p.label}: Extra Bags (${p.extra}) are more than available (${p.availableExtra}).`);
        });

        $('#slip_total_bags').text(totalBags);
        $('#slip_total_empty').text(totalEmpty);
        $('#slip_total_extra').text(totalExtra);
        $('#slip_total_kg').text(totalKg.toFixed(2));
        $('#kilogram').val(totalKg.toFixed(2));
        $('#metric_tons_display').val((totalKg / 1000).toFixed(3));

        if (errors.length) {
            $('#slip_items_error').html(errors.map(e => `<div>${escapeHtml(e)}</div>`).join('')).show();
            $('.submitbutton').prop('disabled', true);
        } else {
            $('#slip_items_error').hide().html('');
            $('.submitbutton').prop('disabled', false);
        }
    }

    $(document).off('input.loadingSlipItems').on('input.loadingSlipItems', '.slip-no-of-bags, .slip-empty-bags, .slip-extra-bags', recalcSlipItems);
</script>
