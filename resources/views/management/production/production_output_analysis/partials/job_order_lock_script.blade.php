{{--
    Output Analysis: when a Job Order is selected, the product is fixed to the job order's product
    (cannot be changed) and the QC Checklist is built from the job order's specifications,
    with each spec shown read-only next to the value field.
    Needs on the page: #job_order_id, #product_id, #hidden_product_id, #product_fixed_badge, #qcChecklistContainer.
--}}
<style>
    /* Plant heading + location line under the header fields */
    .request-details-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 14px;
    }
    .request-details-card .request-plant-heading {
        font-weight: 700;
        margin: 0 0 4px;
    }
    .request-details-card .request-details-line {
        font-size: 0.9rem;
        color: #475569;
    }
    .layout-dark .request-details-card {
        background: #1a1a1a;
        border-color: #404040;
    }
    .layout-dark .request-details-card .request-details-line {
        color: #cbd5e1;
    }
    .request-alert-remarks {
        border-top: 1px dashed #e2e8f0;
        padding-top: 8px;
    }
    .request-alert-remarks .request-alert-remarks-text {
        white-space: pre-line;
        margin: 2px 0;
    }
    .layout-dark .request-alert-remarks {
        border-top-color: #404040;
    }

    /* QC Checklist: compact rows, header row, white result fields */
    .qc-checklist-container .form-group.qc-spec-heading {
        background: transparent;
        padding: 0 15px 6px;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 1px solid #dcdce0;
    }
    .qc-checklist-container .form-group.qc-slab-row {
        padding-top: 6px;
        padding-bottom: 6px;
    }
    .qc-checklist-container .qc-slab-row label.label-control {
        padding-top: 0;
        padding-bottom: 0;
    }
    .qc-checklist-container .qc-spec-result:not(.qc-result-ok):not(.qc-result-warn) {
        background-color: #ffffff;
    }
    .qc-checklist-container .job-order-spec {
        background-color: #eef2f7;
        font-weight: 600;
    }
    .layout-dark .qc-checklist-container .qc-spec-result:not(.qc-result-ok):not(.qc-result-warn) {
        background-color: #262626;
    }
    .layout-dark .qc-checklist-container .job-order-spec {
        background-color: #1f2937;
    }
    .qc-checklist-container input.qc-result-ok {
        background-color: #d4edda;
        border-color: #28a745;
        color: #155724;
    }
    .qc-checklist-container input.qc-result-warn {
        background-color: #fff3cd;
        border-color: #ffc107;
        color: #856404;
    }
    .layout-dark .qc-checklist-container .qc-spec-heading {
        color: #adb5bd;
        border-bottom-color: #404040;
    }
</style>

<script>
    $(function () {
        const $jobOrder = $('#job_order_id');
        const $product = $('#product_id');
        let loadedJobOrderId = null;
        let pendingRequest = null;

        function escapeHtml(text) {
            return $('<div>').text(text == null ? '' : text).html();
        }

        function specText(spec) {
            if (spec.spec_value === null || spec.spec_value === '') return 'No spec';
            const type = spec.value_type ? spec.value_type.charAt(0).toUpperCase() + spec.value_type.slice(1) + ' ' : '';
            return type + spec.spec_value + (spec.uom ? ' ' + spec.uom : '');
        }

        function lockProduct(productId, productName) {
            window.ensureAnalysisProductOption($product, productId, productName);
            $product.val(productId).trigger('change.select2').prop('disabled', true);
            $('#hidden_product_id').val(productId).prop('disabled', false);
            $('#product_fixed_badge').removeClass('d-none');
        }

        function unlockProduct() {
            $product.prop('disabled', false);
            $('#hidden_product_id').val('').prop('disabled', true);
            $('#product_fixed_badge').addClass('d-none');
        }

        function renderSpecChecklist(specs) {
            const $container = $('#qcChecklistContainer');

            // Keep values already typed (or saved, on edit) for the same parameter
            const typed = {};
            $container.find('input[name^="items[0][params]"]').each(function () {
                const match = this.name.match(/items\[0\]\[params\]\[(\d+)\]/);
                if (match) typed[match[1]] = $(this).val();
            });

            let html = `
                <div class="form-group row qc-spec-heading">
                    <div class="col-md-3">Slabs</div>
                    <div class="col-md-3">Job Order</div>
                    <div class="col-md-6">QC Result</div>
                </div>`;
            specs.forEach(function (spec) {
                const symbol = spec.qc_symbol || '%';
                const value = typed[spec.id] !== undefined ? typed[spec.id] : '';
                html += `
                    <div class="form-group row qc-slab-row">
                        <label class="col-md-3 label-control">${escapeHtml(spec.name)}</label>
                        <div class="col-md-3">
                            <input type="text" class="form-control job-order-spec" value="${escapeHtml(specText(spec))}"
                                   readonly tabindex="-1" title="Job Order specification">
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="number" step="any" min="0" required
                                       name="items[0][params][${spec.id}]"
                                       value="${escapeHtml(value)}"
                                       class="form-control qc-spec-result"
                                       data-spec-value="${escapeHtml(spec.spec_value)}"
                                       data-value-type="${escapeHtml(spec.value_type || '')}"
                                       placeholder="${escapeHtml(symbol)}">
                                <div class="input-group-append">
                                    <span class="input-group-text">${escapeHtml(symbol)}</span>
                                </div>
                            </div>
                        </div>
                    </div>`;
            });
            $container.html(html);
            $container.find('.qc-spec-result').each(function () {
                colorResult($(this));
            });
        }

        /*
         * Colors a QC Result against its Job Order spec:
         *  Max (or no type): result <= spec -> green, result > spec -> yellow
         *  Min: result >= spec -> green, result < spec -> yellow
         * Empty result or no spec value -> no color.
         */
        function colorResult($input) {
            $input.removeClass('qc-result-ok qc-result-warn');

            const raw = $input.val();
            const specValue = parseFloat($input.data('spec-value'));
            const type = String($input.data('value-type') || '').toLowerCase();
            if (raw === '' || isNaN(parseFloat(raw)) || isNaN(specValue)) return;

            const result = parseFloat(raw);
            const ok = type === 'min' ? result >= specValue : result <= specValue;
            $input.addClass(ok ? 'qc-result-ok' : 'qc-result-warn');
        }

        $('#qcChecklistContainer')
            .off('input.qcSpec')
            .on('input.qcSpec', '.qc-spec-result', function () {
                colorResult($(this));
            });

        function loadJobOrder(jobOrderId) {
            jobOrderId = jobOrderId ? String(jobOrderId) : '';
            if (jobOrderId === loadedJobOrderId) return;
            loadedJobOrderId = jobOrderId;

            if (pendingRequest) pendingRequest.abort();

            if (!jobOrderId) {
                unlockProduct();
                return;
            }

            pendingRequest = $.get("{{ route('production-output-analysis.job-order-details') }}", { job_order_id: jobOrderId })
                .done(function (res) {
                    if (res.product_id) {
                        lockProduct(res.product_id, res.product_name);
                    } else {
                        unlockProduct();
                    }

                    if (res.specs && res.specs.length) {
                        renderSpecChecklist(res.specs);
                    } else if (res.product_id) {
                        // Job order has no specs: use the product's QC parameters
                        $product.trigger('change');
                    }
                })
                .fail(function (xhr, status) {
                    if (status === 'abort') return;
                    loadedJobOrderId = null;
                    if (window.toastr) toastr.error('Job Order ki details load nahi ho sakin.');
                });
        }

        $jobOrder.on('change', function () {
            loadJobOrder($(this).val());
        });

        if ($jobOrder.val()) {
            loadJobOrder($jobOrder.val());
        }
    });
</script>
