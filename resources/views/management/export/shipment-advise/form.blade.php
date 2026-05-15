@php
    $isEdit = $mode === 'edit';
    $formAction = $isEdit
        ? route('shipment-advise.update', ['shipment_advise' => $shipmentAdvise->id])
        : route('shipment-advise.store');
    $selectedExportOrderId = old('export_order_id', $shipmentAdvise?->packingList?->commercialInvoice?->export_order_id);
    $selectedPackingListId = old('packing_list_id', $shipmentAdvise?->packing_list_id);
@endphp

<form action="{{ $formAction }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <input type="hidden" id="listRefresh" value="{{ route('get.shipment-advise') }}" />

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
                <label>Packing List</label>
                @if ($isEdit)
                    <input type="hidden" name="packing_list_id" value="{{ $selectedPackingListId }}">
                @endif
                <select name="packing_list_id" id="packing_list_id" class="form-control select2"
                    {{ $isEdit ? 'disabled' : '' }}>
                    <option value="">Select Packing List</option>
                </select>
            </div>
        </div>

        <div class="col-md-8">
            <h6 class="header-heading-sepration">Shipment Advice Preview</h6>
            <div id="ShipmentAdvisePreviewContainer">
                @include('management.export.shipment-advise.preview', [
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
    var initialPackingListId = @json($selectedPackingListId ? (int) $selectedPackingListId : null);
    var currentShipmentAdviceId = {{ $isEdit ? (int) $shipmentAdvise->id : 'null' }};

    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });

        $('#export_order_id').on('change', function() {
            loadPackingListsByExportOrder(false);
        });

        $('#packing_list_id').on('change', function() {
            fetchShipmentAdvicePreview();
        });

        loadPackingListsByExportOrder(true);
    });

    function loadPackingListsByExportOrder(isInitial) {
        var exportOrderId = $('#export_order_id').val();
        var $packingLists = $('#packing_list_id');

        $packingLists.empty().append(new Option('Select Packing List', '', false, false)).trigger('change');

        if (!exportOrderId) {
            showShipmentAdviceHint('Please select Export Order first, then choose a Packing List.');
            return;
        }

        $.ajax({
            url: "{{ route('get.shipment-advise.packing-lists') }}",
            method: 'GET',
            data: {
                export_order_id: exportOrderId,
                current_shipment_advise_id: currentShipmentAdviceId
            },
            dataType: 'json',
            success: function(res) {
                if (!res.success) return;

                (res.data || []).forEach(function(item) {
                    $packingLists.append(new Option(item.text, item.id, false, false));
                });

                if ((res.data || []).length === 0) {
                    showShipmentAdviceHint('No available Packing List found against this Export Order.');
                    return;
                }

                if (isInitial && initialPackingListId) {
                    $packingLists.val(String(initialPackingListId)).trigger('change');
                } else {
                    $packingLists.val('').trigger('change');
                    showShipmentAdviceHint('Now select a Packing List to generate the preview.');
                }
            },
            error: function(xhr) {
                var msg = 'Unable to load Packing List list.';
                if (xhr.responseJSON) {
                    msg = xhr.responseJSON.message || xhr.responseJSON.error || msg;
                }
                showShipmentAdviceHint(msg, true);
            }
        });
    }

    function fetchShipmentAdvicePreview() {
        var packingListId = $('#packing_list_id').val();

        if (!packingListId) {
            showShipmentAdviceHint('Please select a Packing List to generate the preview.');
            return;
        }

        $.ajax({
            url: "{{ route('get.shipment-advise.related.data') }}",
            method: 'GET',
            data: {
                packing_list_id: packingListId,
            },
            dataType: 'json',
            success: function(res) {
                if (!res.success) return;
                $('#ShipmentAdvisePreviewContainer').html(res.preview_html);
            },
            error: function(xhr) {
                var msg = 'Unable to load preview.';
                if (xhr.responseJSON) {
                    msg = xhr.responseJSON.message || xhr.responseJSON.error || msg;
                }
                showShipmentAdviceHint(msg, true);
            }
        });
    }

    function showShipmentAdviceHint(message, isDanger) {
        isDanger = isDanger || false;
        var cls = isDanger ? 'bg-light-danger alert-light-danger' : 'bg-light-warning alert-light-warning';
        $('#ShipmentAdvisePreviewContainer').html(
            '<div class="alert ' + cls + ' mb-2" role="alert"><strong>' + message + '</strong></div>'
        );
    }
</script>
