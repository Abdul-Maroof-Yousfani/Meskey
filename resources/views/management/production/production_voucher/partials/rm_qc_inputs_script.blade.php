{{--
    Production Voucher (create + edit) - Production Inputs:
    1. RM QC: when job orders are selected, the products of their RM QC fill the input rows
       (product and location only). Rows the user filled or saved are never replaced.
    2. Duplicate: every input row gets a button that copies the whole row (product, location, qty, remarks).
    Uses the page's #inputRowTemplate, populateInputLocationsSelect(), allInputSublocations and getSelectedJobOrderIds().
--}}
<style>
    #productionInputsTable .duplicatethis { margin: 0 2px; }
</style>
<script>
    (function () {
        var rmQcUrl = "{{ route('production-voucher.rm-qc-inputs') }}";
        var lastJobOrderKey = null;

        function dataRows() {
            return $('#productionInputsTable tbody tr').not('#inputTotalRow');
        }
        function rowIsFilled($row) {
            return !!($row.find('select[name="input_product_id[]"]').val() || $row.find('input[name="input_qty[]"]').val());
        }
        function selectedJobOrderIds() {
            if (typeof getSelectedJobOrderIds === 'function') return getSelectedJobOrderIds();
            var ids = [];
            $('select.job-order-select').each(function () { var v = $(this).val(); if (v && ids.indexOf(v) === -1) ids.push(v); });
            return ids;
        }

        function initSelect2($row) {
            $row.find('select.select2').each(function () {
                var $s = $(this);
                if ($s.data('select2')) $s.select2('destroy');
                $s.select2({ dropdownParent: $s.closest('.modal-body, .card-body, body') });
            });
        }

        // Recalculate totals / yield / costing through the page's own input handlers
        function recalc($row) {
            $row.find('input[name="input_qty[]"]').trigger('input').trigger('change');
        }

        // A new, empty input row from the page template, inserted after $after (or before the total row)
        function newRow($after) {
            var $row = $($('#inputRowTemplate').html());
            $row.addClass('input-data-row');
            if ($after && $after.length) {
                $after.after($row);
            } else {
                var $total = $('#productionInputsTable tbody #inputTotalRow');
                $total.length ? $total.before($row) : $('#productionInputsTable tbody').append($row);
            }
            return $row;
        }

        function fillRow($row, values) {
            var $product = $row.find('select[name="input_product_id[]"]');
            if (values.product_id && !$product.find('option[value="' + values.product_id + '"]').length) {
                $product.append($('<option>', { value: values.product_id, text: values.product_name || ('#' + values.product_id) }));
            }
            $product.val(values.product_id || '');

            var $loc = $row.find('select[name="input_location_id[]"]');
            if (typeof populateInputLocationsSelect === 'function') {
                populateInputLocationsSelect($loc, values.location_id || '');
            }
            if (values.location_id && !$loc.find('option[value="' + values.location_id + '"]').length) {
                // RM QC location from another company location: still offer it
                var sub = (window.allInputSublocations || []).find(function (s) { return String(s.id) === String(values.location_id); });
                $loc.append($('<option>', { value: values.location_id, text: sub ? sub.name : ('#' + values.location_id) }));
            }
            $loc.val(values.location_id || '');

            $row.find('input[name="input_qty[]"]').val(values.qty || '');
            $row.find('textarea[name="input_remarks[]"]').val(values.remarks || '');
            initSelect2($row);
            addDuplicateButton($row);
        }

        // ---- RM QC -> Production Inputs ----
        function loadRmQcInputs(force) {
            var ids = selectedJobOrderIds();
            var key = ids.slice().sort().join(',');
            if (!force && key === lastJobOrderKey) return;
            lastJobOrderKey = key;

            // Rows this script added earlier (and the user did not touch) are refreshed;
            // anything the user filled or that was saved stays as it is
            dataRows().filter('.rmqc-row').remove();
            var manualFilled = dataRows().filter(function () { return rowIsFilled($(this)); }).length;

            var done = function () {
                if (!dataRows().length) newRow(); // always keep one row
                recalc(dataRows().first());
            };

            if (!ids.length || manualFilled) { done(); return; }

            $.post(rmQcUrl, { job_order_ids: ids, _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val() })
                .done(function (res) {
                    var items = res.items || [];
                    // Inputs may have been filled while the request was running
                    if (!items.length || dataRows().filter(function () { return rowIsFilled($(this)); }).length) { done(); return; }

                    dataRows().remove();
                    var $last = null;
                    items.forEach(function (it) {
                        var $row = newRow($last);
                        // Only the product and location come from RM QC; qty and remarks are entered by the user
                        fillRow($row, {
                            product_id: it.product_id,
                            product_name: it.product_name,
                            location_id: it.location_id,
                        });
                        $row.addClass('rmqc-row');
                        $last = $row;
                    });
                    done();
                })
                .fail(function () { done(); });
        }

        // Editing an RM QC row makes it the user's own: it is no longer replaced
        $(document).off('.pvRmQcTouch')
            .on('input.pvRmQcTouch', '#productionInputsTable tr.rmqc-row input, #productionInputsTable tr.rmqc-row textarea', function (e) {
                if (e.originalEvent) $(this).closest('tr').removeClass('rmqc-row');
            })
            .on('select2:select.pvRmQcTouch', '#productionInputsTable tr.rmqc-row select', function () {
                $(this).closest('tr').removeClass('rmqc-row');
            });

        var jobOrderTimer = null;
        function scheduleRmQc() {
            clearTimeout(jobOrderTimer);
            jobOrderTimer = setTimeout(function () { loadRmQcInputs(false); }, 400);
        }
        // A job order picked, changed or its select removed
        $(document).off('.pvRmQc')
            .on('change.pvRmQc', 'select.job-order-select', scheduleRmQc)
            .on('click.pvRmQc', '.remove-job-order-select', scheduleRmQc);

        // ---- Duplicate row ----
        function addDuplicateButton($row) {
            if ($row.is('#inputTotalRow') || $row.find('.duplicatethis').length) return;
            var $btn = $('<button type="button" class="btn btn-sm btn-info duplicatethis" title="Duplicate this row"><i class="fa fa-copy"></i></button>');
            var $remove = $row.find('.removethis');
            $remove.length ? $remove.before($btn) : $row.find('td:last').append($btn);
        }

        $(document).off('click.pvDup').on('click.pvDup', '#productionInputsTable .duplicatethis', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var $src = $(this).closest('tr');
            var $product = $src.find('select[name="input_product_id[]"]');
            var $copy = newRow($src);
            fillRow($copy, {
                product_id: $product.val(),
                product_name: $product.find('option:selected').text(),
                location_id: $src.find('select[name="input_location_id[]"]').val(),
                qty: $src.find('input[name="input_qty[]"]').val(),
                remarks: $src.find('textarea[name="input_remarks[]"]').val(),
            });
            recalc($copy);
        });

        // Rows are added by several handlers (+, Add Input Row, RM QC): give each new one the button
        var tbody = document.querySelector('#productionInputsTable tbody');
        if (tbody && window.MutationObserver) {
            new MutationObserver(function () {
                dataRows().each(function () { addDuplicateButton($(this)); });
            }).observe(tbody, { childList: true });
        }

        $(function () {
            dataRows().each(function () { addDuplicateButton($(this)); });

            // Edit / reopened form: fill from RM QC only when no input is entered yet
            setTimeout(function () {
                var ids = selectedJobOrderIds();
                lastJobOrderKey = null;
                if (ids.length && !dataRows().filter(function () { return rowIsFilled($(this)); }).length) {
                    loadRmQcInputs(true);
                } else {
                    lastJobOrderKey = ids.slice().sort().join(',');
                }
            }, 1200);
        });
    })();
</script>
