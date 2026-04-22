@php
    $isEdit = $mode === 'edit';
    $formAction = $isEdit
        ? route('packing-list.update', ['packing_list' => $packingList->id])
        : route('packing-list.store');
    $selectedExportOrderId = old('export_order_id', $packingList?->commercialInvoice?->export_order_id);
    $selectedCommercialInvoiceId = old('commercial_invoice_id', $packingList?->commercial_invoice_id);
@endphp

<form action="{{ $formAction }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <input type="hidden" id="listRefresh" value="{{ route('get.packing-list') }}" />

    <div class="row form-mar">
        <div class="col-md-4">
            <h6 class="header-heading-sepration">Basic Information</h6>

            <div class="form-group">
                <label>Export Order</label>
                @if ($isEdit)
                    <input type="hidden" name="export_order_id" value="{{ $selectedExportOrderId }}">
                @endif
                <select name="export_order_id" id="export_order_id" class="form-control select2"
                    {{ $isEdit ? 'disabled' : '' }}>
                    <option value="">Select Export Order</option>
                    @foreach ($exportOrders as $exportOrder)
                        <option value="{{ $exportOrder->id }}"
                            {{ (string) $selectedExportOrderId === (string) $exportOrder->id ? 'selected' : '' }}>
                            {{ $exportOrder->voucher_no }} - {{ $exportOrder->buyer?->name ?? 'N/A' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Commercial Invoice</label>
                @if ($isEdit)
                    <input type="hidden" name="commercial_invoice_id" value="{{ $selectedCommercialInvoiceId }}">
                @endif
                <select name="commercial_invoice_id" id="commercial_invoice_id" class="form-control select2"
                    {{ $isEdit ? 'disabled' : '' }}>
                    <option value="">Select Commercial Invoice</option>
                </select>
            </div>
        </div>

        <div class="col-md-8">
            <h6 class="header-heading-sepration">Packing List Preview</h6>
            <div id="packingListPreviewContainer">
                @include('management.export.packing-list.preview', [
                    'preview' => $preview ?? [],
                    'goodsSummary' => $goodsSummary ?? [],
                ])
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">{{ $isEdit ? 'Update' : 'Save' }}</button>
        </div>
    </div>
</form>

<script>
    var initialExportOrderId = @json($selectedExportOrderId ? (int) $selectedExportOrderId : null);
    var initialCommercialInvoiceId = @json($selectedCommercialInvoiceId ? (int) $selectedCommercialInvoiceId : null);
    var currentPackingListId = {{ $isEdit ? (int) $packingList->id : 'null' }};

    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });

        $('#export_order_id').on('change', function() {
            loadCommercialInvoicesByExportOrder(false);
        });

        $('#commercial_invoice_id').on('change', function() {
            fetchPackingListPreview();
        });

        loadCommercialInvoicesByExportOrder(true);
    });

    function loadCommercialInvoicesByExportOrder(isInitial) {
        var exportOrderId = $('#export_order_id').val();
        var $commercialInvoices = $('#commercial_invoice_id');

        $commercialInvoices.empty().append(new Option('Select Commercial Invoice', '', false, false)).trigger('change');

        if (!exportOrderId) {
            showPackingListHint('Please select Export Order first, then choose a Commercial Invoice.');
            return;
        }

        $.ajax({
            url: "{{ route('get.packing-list.commercial-invoices') }}",
            method: 'GET',
            data: {
                export_order_id: exportOrderId,
                current_packing_list_id: currentPackingListId
            },
            dataType: 'json',
            success: function(res) {
                if (!res.success) return;

                (res.data || []).forEach(function(item) {
                    $commercialInvoices.append(new Option(item.text, item.id, false, false));
                });

                if ((res.data || []).length === 0) {
                    showPackingListHint('No available Commercial Invoice found against this Export Order.');
                    return;
                }

                if (isInitial && initialCommercialInvoiceId) {
                    $commercialInvoices.val(String(initialCommercialInvoiceId)).trigger('change');
                } else {
                    $commercialInvoices.val('').trigger('change');
                    showPackingListHint('Now select a Commercial Invoice to generate the preview.');
                }
            },
            error: function(xhr) {
                var msg = 'Unable to load Commercial Invoice list.';
                if (xhr.responseJSON) {
                    msg = xhr.responseJSON.message || xhr.responseJSON.error || msg;
                }
                showPackingListHint(msg, true);
            }
        });
    }

    function fetchPackingListPreview() {
        var commercialInvoiceId = $('#commercial_invoice_id').val();

        if (!commercialInvoiceId) {
            showPackingListHint('Please select a Commercial Invoice to generate the preview.');
            return;
        }

        $.ajax({
            url: "{{ route('get.packing-list.related.data') }}",
            method: 'GET',
            data: {
                commercial_invoice_id: commercialInvoiceId,
            },
            dataType: 'json',
            success: function(res) {
                if (!res.success) return;
                $('#packingListPreviewContainer').html(res.preview_html);
            },
            error: function(xhr) {
                var msg = 'Unable to load preview.';
                if (xhr.responseJSON) {
                    msg = xhr.responseJSON.message || xhr.responseJSON.error || msg;
                }
                showPackingListHint(msg, true);
            }
        });
    }

    function showPackingListHint(message, isDanger) {
        isDanger = isDanger || false;
        var cls = isDanger ? 'bg-light-danger alert-light-danger' : 'bg-light-warning alert-light-warning';
        $('#packingListPreviewContainer').html(
            '<div class="alert ' + cls + ' mb-2" role="alert"><strong>' + message + '</strong></div>'
        );
    }
</script>
