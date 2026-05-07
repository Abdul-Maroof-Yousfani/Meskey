@php
    $isEdit = $mode === 'edit';
    $formAction = $isEdit ? route('bill-of-lading.update', ['bill_of_lading' => $billOfLading->id]) : route('bill-of-lading.store');
    $selectedFormEIds = old('export_form_e_ids', $billOfLading?->selected_form_e_ids ?? []);
    $selectedDcIds = old('export_delivery_challan_ids', $billOfLading?->selected_delivery_challan_ids ?? []);
@endphp

<form action="{{ $formAction }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <input type="hidden" id="listRefresh" value="{{ route('get.bill-of-lading') }}" />

    <div class="row form-mar">
        <div class="col-md-4">
            <h6 class="header-heading-sepration">Basic Information</h6>

            <div class="form-group">
                <label>Bill No</label>
                <input type="text" name="bill_no" id="bill_no" class="form-control"
                    value="{{ old('bill_no', $billOfLading?->bill_no ?? '') }}" readonly>
            </div>

            <div class="form-group">
                <label>Bill Date</label>
                <input type="date" name="bill_date" id="bill_date" class="form-control"
                    value="{{ old('bill_date', optional($billOfLading?->bill_date)->format('Y-m-d') ?? date('Y-m-d')) }}">
            </div>

            <div class="form-group">
                <label>Export Order</label>
                @if($isEdit)
                    <input type="hidden" name="export_order_id" value="{{ $billOfLading->export_order_id }}">
                @endif
                <select name="export_order_id" id="export_order_id" class="form-control select2" {{ $isEdit ? 'disabled' : '' }}>
                    <option value="">Select Export Order</option>
                    @foreach ($exportOrders as $exportOrder)
                        <option value="{{ $exportOrder->id }}"
                            {{ old('export_order_id', $billOfLading?->export_order_id) == $exportOrder->id ? 'selected' : '' }}>
                            {{ $exportOrder->voucher_no }} - {{ $exportOrder->buyer?->name ?? 'N/A' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Form-E</label>
                @if($isEdit)
                    @foreach($selectedFormEIds as $feId)
                        <input type="hidden" name="export_form_e_ids[]" value="{{ $feId }}">
                    @endforeach
                @endif
                <select name="export_form_e_ids[]" id="export_form_e_ids" class="form-control select2" multiple {{ $isEdit ? 'disabled' : '' }}>
                </select>
            </div>

            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="mb-0">Delivery Challan</label>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0" id="select_all_dc" {{ $isEdit ? 'disabled' : '' }}>Select All</button>
                </div>
                @if($isEdit)
                    @foreach($selectedDcIds as $dcId)
                        <input type="hidden" name="export_delivery_challan_ids[]" value="{{ $dcId }}">
                    @endforeach
                @endif
                <select name="export_delivery_challan_ids[]" id="export_delivery_challan_ids" class="form-control select2" multiple {{ $isEdit ? 'disabled' : '' }}>
                </select>
            </div>

            <div class="form-group">
                <label>Carrier Name</label>
                <input type="text" name="carrier_name" id="carrier_name" class="form-control"
                    value="{{ old('carrier_name', $billOfLading?->carrier_name ?? '') }}">
            </div>

            <div class="form-group">
                <label>Shipped On Board Date</label>
                <input type="date" name="shipped_on_board_date" id="shipped_on_board_date" class="form-control"
                    value="{{ old('shipped_on_board_date', optional($billOfLading?->shipped_on_board_date)->format('Y-m-d')) }}">
            </div>

            <div class="form-group">
                <label>Charter Party Dated</label>
                <input type="date" name="charter_party_dated" id="charter_party_dated" class="form-control"
                    value="{{ old('charter_party_dated', $preview['charter_party_dated'] ?? '') }}">
            </div>

            <div class="form-group">
                <label>Cautions</label>
                <textarea name="cautions_text" id="cautions_text" rows="5" class="form-control text-editor"
                    placeholder="Enter cautions text...">{!! old('cautions_text', $billOfLading->cautions_text ?? '') !!}</textarea>
            </div>

            <div class="form-group">
                <label>Place Of Issue</label>
                <input type="text" id="place_of_issue_display" class="form-control"
                    value="{{ $preview['place_of_issue'] ?? '' }}" readonly>
            </div>
        </div>

        <div class="col-md-8">
            <h6 class="header-heading-sepration">Bill Of Lading Preview</h6>
            <div id="bolPreviewContainer">
                @include('management.export.bill-of-lading.preview', [
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
    var initialFormEIds = @json(array_values(array_unique(array_map('intval', (array) $selectedFormEIds))));
    var initialDcIds = @json(array_values(array_unique(array_map('intval', (array) $selectedDcIds))));
    var currentBillId = {{ $isEdit ? (int) $billOfLading->id : 'null' }};

    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });
        // Ensure summernote assets exist for this modal
        if (!document.querySelector('link[data-bol-summernote]')) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css';
            link.setAttribute('data-bol-summernote', '1');
            document.head.appendChild(link);
        }
        if (!document.querySelector('script[data-bol-summernote]')) {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js';
            script.defer = true;
            script.setAttribute('data-bol-summernote', '1');
            document.head.appendChild(script);
        }
        if ($.fn.summernote) {
            $('#cautions_text').summernote({
                height: 140,
            });
        } else {
            // Retry init once CDN script finishes loading
            var tryInit = setInterval(function() {
                if ($.fn.summernote) {
                    clearInterval(tryInit);
                    $('#cautions_text').summernote({ height: 140 });
                }
            }, 300);
            setTimeout(function() { clearInterval(tryInit); }, 7000);
        }

        if (!$('#bill_no').val()) {
            getBillNumber();
        }

        loadFormEsByExportOrder(true);

        $('#bill_date').on('change', function() {
            getBillNumber();
            fetchBillPreview();
        });

        $('#export_order_id').on('change', function() {
            loadFormEsByExportOrder(false);
        });

        $('#export_form_e_ids').on('change', function() {
            loadDeliveryChallansByFormEs(false);
        });

        $('#export_delivery_challan_ids, #carrier_name, #shipped_on_board_date, #charter_party_dated').on('change keyup', function() {
            fetchBillPreview();
        });

        $('#select_all_dc').on('click', function() {
            var $challans = $('#export_delivery_challan_ids');
            var allValues = $challans.find('option').map(function() {
                return $(this).val();
            }).get();
            $challans.val(allValues).trigger('change');
        });

        $(document).off('summernote.change', '#cautions_text').on('summernote.change', '#cautions_text', function() {
            fetchBillPreview();
        });
    });

    function getBillNumber() {
        $.ajax({
            url: "{{ route('get.bill-of-lading.getNumber') }}",
            method: "GET",
            data: {
                bill_date: $('#bill_date').val()
            },
            dataType: "json",
            success: function(res) {
                if (!{{ $isEdit ? 'true' : 'false' }}) {
                    $('#bill_no').val(res.bill_no || '');
                }
            }
        });
    }

    function loadFormEsByExportOrder(isInitial) {
        var exportOrderId = $('#export_order_id').val();
        var $formEs = $('#export_form_e_ids');
        var $challans = $('#export_delivery_challan_ids');

        $formEs.empty().trigger('change');
        $challans.empty().trigger('change');

        if (!exportOrderId) {
            showSelectionHint('Please select Export Order, then Form-E and Delivery Challans.');
            return;
        }

        $.ajax({
            url: "{{ route('get.bill-of-lading.form-es') }}",
            method: "GET",
            data: {
                export_order_id: exportOrderId,
                current_bill_id: currentBillId
            },
            dataType: "json",
            success: function(res) {
                if (!res.success) {
                    return;
                }

                (res.data || []).forEach(function(item) {
                    if ($formEs.find("option[value='" + item.id + "']").length === 0) {
                        var option = new Option(item.text, item.id, false, false);
                        $formEs.append(option);
                    }
                });

                var values = isInitial ? initialFormEIds : [];
                $formEs.val(values).trigger('change');
                loadDeliveryChallansByFormEs(isInitial);
            }
        });
    }

    function loadDeliveryChallansByFormEs(isInitial) {
        var exportOrderId = $('#export_order_id').val();
        var formEIds = $('#export_form_e_ids').val() || [];
        var $challans = $('#export_delivery_challan_ids');

        $challans.empty().trigger('change');

        if (!exportOrderId || formEIds.length === 0) {
            showSelectionHint('Please select at least one Form-E to load approved Delivery Challans.');
            return;
        }

        $.ajax({
            url: "{{ route('get.bill-of-lading.delivery-challans') }}",
            method: "GET",
            data: {
                export_order_id: exportOrderId,
                'export_form_e_ids[]': formEIds,
                current_bill_id: currentBillId
            },
            traditional: true,
            dataType: "json",
            success: function(res) {
                if (!res.success) {
                    return;
                }

                (res.data || []).forEach(function(item) {
                    if ($challans.find("option[value='" + item.id + "']").length === 0) {
                        var option = new Option(item.text, item.id, false, false);
                        $challans.append(option);
                    }
                });

                $challans.val(isInitial ? initialDcIds : []).trigger('change');
                fetchBillPreview();
            }
        });
    }

    function fetchBillPreview() {
        var exportOrderId = $('#export_order_id').val();
        var formEIds = $('#export_form_e_ids').val() || [];
        var challanIds = $('#export_delivery_challan_ids').val() || [];
        var cautionsText = $.fn.summernote ? $('#cautions_text').summernote('code') : $('#cautions_text').val();

        if (!exportOrderId || formEIds.length === 0 || challanIds.length === 0) {
            showSelectionHint('Select Export Order + Form-E + Delivery Challans to auto-build merged Bill of Lading.');
            return;
        }

        $.ajax({
            url: "{{ route('get.bill-of-lading.related.data') }}",
            method: "GET",
            data: {
                export_order_id: exportOrderId,
                'export_form_e_ids[]': formEIds,
                'export_delivery_challan_ids[]': challanIds,
                bill_no: $('#bill_no').val(),
                bill_date: $('#bill_date').val(),
                carrier_name: $('#carrier_name').val(),
                shipped_on_board_date: $('#shipped_on_board_date').val(),
                charter_party_dated: $('#charter_party_dated').val(),
                cautions_text: cautionsText,
                current_bill_id: currentBillId,
            },
            traditional: true,
            dataType: "json",
            success: function(res) {
                if (res.success) {
                    $('#bolPreviewContainer').html(res.preview_html);
                    $('#place_of_issue_display').val((res.preview && res.preview.place_of_issue) ? res.preview.place_of_issue : '');
                }
            },
            error: function(xhr) {
                let message = 'Unable to load Bill Of Lading details.';
                if (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                    message = xhr.responseJSON.message || xhr.responseJSON.error;
                }
                showSelectionHint(message, true);
            }
        });
    }

    function showSelectionHint(message, isDanger = false) {
        $('#bolPreviewContainer').html(`
            <div class="alert ${isDanger ? 'bg-light-danger alert-light-danger' : 'bg-light-warning alert-light-warning'} mb-2" role="alert">
                <strong>${message}</strong>
            </div>
        `);
    }
</script>
