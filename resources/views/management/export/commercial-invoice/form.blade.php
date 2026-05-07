@php
    $isEdit = $mode === 'edit';
    $formAction = $isEdit
        ? route('commercial-invoice.update', ['commercial_invoice' => $commercialInvoice->id])
        : route('commercial-invoice.store');
    $selectedBillIds = old('bill_of_lading_ids', $commercialInvoice?->resolved_bill_of_lading_ids ?? []);
@endphp

<form action="{{ $formAction }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <input type="hidden" id="listRefresh" value="{{ route('get.commercial-invoice') }}" />

    <div class="row form-mar">
        {{-- ===== LEFT: FORM FIELDS ===== --}}
        <div class="col-md-4">
            <h6 class="header-heading-sepration">Basic Information</h6>

            <div class="form-group">
                <label>Commercial Invoice No</label>
                <input type="text" name="commercial_invoice_no" id="commercial_invoice_no"
                    class="form-control"
                    value="{{ old('commercial_invoice_no', $commercialInvoice?->commercial_invoice_no ?? '') }}"
                    readonly>
            </div>

            <div class="form-group">
                <label>Date</label>
                <input type="date" name="invoice_date" id="invoice_date" class="form-control"
                    value="{{ old('invoice_date', optional($commercialInvoice?->invoice_date)->format('Y-m-d') ?? date('Y-m-d')) }}">
            </div>

            <div class="form-group">
                <label>Export Order</label>
                @if ($isEdit)
                    <input type="hidden" name="export_order_id" value="{{ $commercialInvoice->export_order_id }}">
                @endif
                <select name="export_order_id" id="export_order_id"
                    class="form-control select2"
                    {{ $isEdit ? 'disabled' : '' }}>
                    <option value="">Select Export Order</option>
                    @foreach ($exportOrders as $exportOrder)
                        <option value="{{ $exportOrder->id }}"
                            {{ old('export_order_id', $commercialInvoice?->export_order_id) == $exportOrder->id ? 'selected' : '' }}>
                            {{ $exportOrder->voucher_no }} — {{ $exportOrder->buyer?->name ?? 'N/A' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Bill Of Lading</label>
                @if ($isEdit && !empty($selectedBillIds))
                    @foreach($selectedBillIds as $bId)
                        <input type="hidden" name="bill_of_lading_ids[]" value="{{ $bId }}">
                    @endforeach
                @endif
                <select name="bill_of_lading_ids[]" id="bill_of_lading_ids"
                    class="form-control select2" multiple
                    {{ $isEdit ? 'disabled' : '' }}>
                </select>
            </div>

            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" id="remarks" rows="5" class="form-control text-editor"
                    placeholder="Enter remarks...">{!! old('remarks', $commercialInvoice->remarks ?? '') !!}</textarea>
            </div>

        </div>

        {{-- ===== RIGHT: PREVIEW ===== --}}
        <div class="col-md-8">
            <h6 class="header-heading-sepration">Commercial Invoice Preview</h6>
            <div id="commercialInvoicePreviewContainer">
                @include('management.export.commercial-invoice.preview', [
                    'preview'      => $preview ?? [],
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
    var initialBillIds   = @json(array_values(array_unique(array_map('intval', (array) $selectedBillIds))));
    var currentInvoiceId = {{ $isEdit ? (int) $commercialInvoice->id : 'null' }};

    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        // Summernote initialization
        if (!document.querySelector('link[data-ci-summernote]')) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css';
            link.setAttribute('data-ci-summernote', '1');
            document.head.appendChild(link);
        }
        if (!document.querySelector('script[data-ci-summernote]')) {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js';
            script.defer = true;
            script.setAttribute('data-ci-summernote', '1');
            document.head.appendChild(script);
        }
        
        var initSummernote = function() {
            if ($.fn.summernote) {
                $('#remarks').summernote({
                    height: 140,
                    callbacks: {
                        onChange: function() { fetchCommercialInvoicePreview(); }
                    }
                });
            } else {
                setTimeout(initSummernote, 300);
            }
        };
        initSummernote();

        if (!$('#commercial_invoice_no').val()) {
            getCommercialInvoiceNumber();
        }

        loadBillsByExportOrder(true);

        $('#invoice_date').on('change', function() {
            getCommercialInvoiceNumber();
            fetchCommercialInvoicePreview();
        });

        $('#export_order_id').on('change', function() {
            getCommercialInvoiceNumber();
            loadBillsByExportOrder(false);
        });

        $('#bill_of_lading_ids').on('change', function() {
            fetchCommercialInvoicePreview();
        });
    });

    function getCommercialInvoiceNumber() {
        if ({{ $isEdit ? 'true' : 'false' }}) return; // don't change number in edit mode
        $.get("{{ route('get.commercial-invoice.getNumber') }}", {
            invoice_date: $('#invoice_date').val(),
            export_order_id: $('#export_order_id').val()
        }, function(res) {
            if (res.commercial_invoice_no) {
                $('#commercial_invoice_no').val(res.commercial_invoice_no);
            }
        });
    }

    function loadBillsByExportOrder(isInitial) {
        var exportOrderId = $('#export_order_id').val();
        var $bills        = $('#bill_of_lading_ids');

        $bills.empty().trigger('change');

        if (!exportOrderId) {
            showCIHint('Please select the Export Order first, then choose Bill of Ladings.');
            return;
        }

        $.get("{{ route('get.commercial-invoice.bills') }}", {
            export_order_id:   exportOrderId,
            current_invoice_id: currentInvoiceId
        }, function(res) {
            if (!res.success) return;

            (res.data || []).forEach(function(item) {
                if (!$bills.find("option[value='" + item.id + "']").length) {
                    $bills.append(new Option(item.text, item.id, false, false));
                }
            });

            $bills.val(isInitial ? initialBillIds : []).trigger('change');
            fetchCommercialInvoicePreview();
        });
    }

    function fetchCommercialInvoicePreview() {
        var exportOrderId  = $('#export_order_id').val();
        var billOfLadingIds = $('#bill_of_lading_ids').val() || [];
        var remarks = $.fn.summernote ? $('#remarks').summernote('code') : $('#remarks').val();

        if (!exportOrderId || billOfLadingIds.length === 0) {
            showCIHint('Select both Export Order and Bill of Ladings to generate the preview.');
            return;
        }

        $.ajax({
            url:      "{{ route('get.commercial-invoice.related.data') }}",
            method:   'GET',
            data: {
                export_order_id:       exportOrderId,
                'bill_of_lading_ids[]': billOfLadingIds,
                commercial_invoice_no: $('#commercial_invoice_no').val(),
                invoice_date:          $('#invoice_date').val(),
                remarks:               remarks,
                current_invoice_id:    currentInvoiceId,
            },
            dataType: 'json',
            success: function(res) {
                if (!res.success) return;

                $('#commercialInvoicePreviewContainer').html(res.preview_html);

            },
            error: function(xhr) {
                var msg = 'Preview load nahi ho saka.';
                if (xhr.responseJSON) {
                    msg = xhr.responseJSON.message || xhr.responseJSON.error || msg;
                }
                showCIHint(msg, true);
            }
        });
    }

    function showCIHint(message, isDanger) {
        isDanger = isDanger || false;
        var cls  = isDanger ? 'bg-light-danger alert-light-danger' : 'bg-light-warning alert-light-warning';
        $('#commercialInvoicePreviewContainer').html(
            '<div class="alert ' + cls + ' mb-2" role="alert"><strong>' + message + '</strong></div>'
        );
    }
</script>
