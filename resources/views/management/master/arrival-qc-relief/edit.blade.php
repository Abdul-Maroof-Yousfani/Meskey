<form action="{{ route('arrival-qc-relief.update', $id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.arrival-qc-relief') }}" />

    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-sm btn-outline-info" onclick="openModal(this,'{{ route('arrival-qc-relief.audit-logs', $id) }}','Audit Logs: {{ $first->product->name ?? 'Arrival QC Relief' }}', false, '55%')">
            <i class="fa fa-history mr-1"></i> Audit Logs
        </button>
    </div>

    <!-- Info Alert Box (Matching Reference Image Style) -->
    <div class="alert alert-info mb-3 border-0 shadow-xs">
        <div class="d-flex align-items-center mb-1">
            <i class="ft-info font-medium-3 mr-2"></i>
            <h6 class="mb-0 text-white font-weight-bold">Editing Arrival QC Relief Parameters</h6>
        </div>
        <ul class="mb-0 pl-3">
            <li><strong>Range (From - To):</strong> Define measured QC range (e.g. <code>36.00</code> to <code>39.00</code>) that triggers this relief rule.</li>
            <li><strong>Type:</strong> Choose between <strong>Fixed</strong> (override reading to a target number like <code>35.00</code>) or <strong>Minus Amount</strong> (deduct an amount from reading/rate).</li>
            <li><strong>Value:</strong> The fixed target value or the amount to minus based on selected Type.</li>
            <li><strong>Multi-Range:</strong> Configure multiple ranges per QC slab type using the "+ Add Range" button.</li>
        </ul>
    </div>

    <!-- Header Fields: Product, Location, Date Range -->
    <div class="card p-3 mb-3 border bg-light-neutral">
        <div class="row">
            <div class="col-md-6 col-12">
                <div class="form-group mb-2">
                    <label class="font-weight-bold">Product: <span class="text-danger">*</span></label>
                    <select class="form-control select2" name="product_id" id="arrival_qc_product_id_edit" required>
                        <option value="">Select Product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" {{ $product->id == $selectedProductId ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6 col-12">
                <div class="form-group mb-2">
                    <label class="font-weight-bold">Location: <span class="text-danger">*</span></label>
                    <select class="form-control select2" name="location_id" id="arrival_qc_location_id_edit" required>
                        <option value="all" {{ empty($selectedLocationId) ? 'selected' : '' }}>All Locations</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $loc->id == $selectedLocationId ? 'selected' : '' }}>
                                {{ $loc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6 col-12">
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Start Date: <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="start_date" id="start_date_edit"
                        value="{{ $startDate }}" required>
                </div>
            </div>

            <div class="col-md-6 col-12">
                <div class="form-group mb-0">
                    <label class="font-weight-bold">End Date: <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="end_date" id="end_date_edit"
                        value="{{ $endDate }}" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Heading -->
    <div class="row mb-2">
        <div class="col-12">
            <h6 class="header-heading-sepration py-2 px-3 font-weight-bold text-uppercase d-flex align-items-center justify-content-between">
                <span><i class="ft-sliders mr-2"></i> QC Slab Types Configuration</span>
                <small class="text-muted text-lowercase font-weight-normal">(Toggle switch to activate parameters)</small>
            </h6>
        </div>
    </div>

    <!-- Slabs Container -->
    <div id="slabs-container">
        @include('management.master.arrival-qc-relief.partials.slabs_container', [
            'slab_types' => $slab_types,
            'configuredData' => $configuredData
        ])
    </div>

    <!-- Action Buttons -->
    <div class="row bottom-button-bar mt-4 pt-3 border-top">
        <div class="col-12 text-right">
            <button type="button" class="btn btn-secondary modal-sidebar-close position-relative mr-2">
                <i class="ft-x mr-1"></i> Close
            </button>
            <button type="submit" class="btn btn-primary submitbutton">
                <i class="ft-check mr-1"></i> Update Arrival QC Relief
            </button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        if ($.fn.select2) {
            $('.select2').select2({
                dropdownParent: $('#modal-sidebar')
            });
        }

        function toggleSlabRanges($switch) {
            const slabTypeId = $switch.attr('id').replace('enable_', '');
            const $group = $switch.closest('.slab-type-group');
            const $container = $group.find(`.slab-ranges-container[data-slab-type="${slabTypeId}"]`);
            const $badge = $group.find('.status-pill');

            if ($switch.is(':checked')) {
                $container.find('input, select, button').not('.slab-range-template input, .slab-range-template select, .slab-range-template button').prop('disabled', false);
                $container.slideDown(200);
                $badge.removeClass('badge-secondary').addClass('badge-success').text('Active Relief');
            } else {
                $container.find('input, select, button').prop('disabled', true);
                $container.slideUp(200);
                $badge.removeClass('badge-success').addClass('badge-secondary').text('Inactive');
            }
        }

        $('.slab-enable-switch').each(function() {
            toggleSlabRanges($(this));
        });

        // Unbind previous delegated events to prevent duplicate event triggers on modal reopen
        $(document).off('change', '.slab-enable-switch').on('change', '.slab-enable-switch', function() {
            toggleSlabRanges($(this));
        });

        // Dynamic change on Relief Type Select
        $(document).off('change', '.relief-type-select').on('change', '.relief-type-select', function() {
            const val = $(this).val();
            const $row = $(this).closest('.slab-range-row');
            const $input = $row.find('.relief-val');
            const $label = $row.find('.relief-val-label');

            if (val === 'minus_amount') {
                $input.attr('placeholder', 'Minus Amount (e.g. 2.00)');
                $label.text('Minus Amount:');
            } else {
                $input.attr('placeholder', 'Fixed To (e.g. 35.00)');
                $label.text('Value (Fixed To):');
            }
        });

        // Add Range dynamically - unbind first to prevent double row addition!
        $(document).off('click', '.add-range-btn').on('click', '.add-range-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const slabTypeId = $(this).data('slab-type');
            const $group = $(this).closest('.slab-type-group');
            const $container = $group.find(`.slab-ranges-container[data-slab-type="${slabTypeId}"]`);
            const $list = $container.find('.slab-ranges-list');
            const $template = $container.find('.slab-range-template');

            const nextIndex = $list.find('.slab-range-row').length + Math.floor(Math.random() * 1000);
            const newRowHtml = $template.html().replace(/INDEX_HOLDER/g, nextIndex);
            const $newRow = $(newRowHtml);

            $newRow.find('input, select, button').prop('disabled', false);
            $list.append($newRow);
            $newRow.hide().fadeIn(250);
        });

        // Remove Range
        $(document).off('click', '.remove-range-btn').on('click', '.remove-range-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const $row = $(this).closest('.slab-range-row');
            const $list = $row.closest('.slab-ranges-list');

            if ($list.find('.slab-range-row').length > 1) {
                $row.fadeOut(200, function() {
                    $(this).remove();
                });
            } else {
                $row.find('input').val('');
                $row.find('select').val('fixed');
                if (typeof toastr !== 'undefined') {
                    toastr.info('Cleared range values. At least one range row is kept.');
                }
            }
        });
    });
</script>
