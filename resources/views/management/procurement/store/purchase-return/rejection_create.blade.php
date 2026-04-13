<style>
    html, body {
        overflow-x: hidden;
    }
    #rejectionReturnTable .select2-container {
        width: 100% !important;
    }
    input:disabled {
        background-color: white !important;
        color: #495057 !important;
        cursor: not-allowed;
    }
</style>

<form style="overflow-x: hidden;" action="{{ route('store.rejection-return.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('store.rejection-return.getList') }}" />
    
    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Select GRN:<span class="text-danger">*</span></label>
                <select name="grn_id" id="grn_id" class="form-control select2" required onchange="populateGrnDetails(this)">
                    <option value="">Select GRN</option>
                    @foreach($approvedGRNs ?? [] as $grn)
                        @php
                            $itemsData = $grn->purchaseOrderReceivingData->filter(function($data) {
                                return ($data->qc->rejected_quantity ?? 0) > 0 && ($data->qc->deduction_per_bag ?? 0) == 0;
                            })->map(function($data) {
                                return [
                                    'id' => $data->item_id,
                                    'name' => $data->item->name ?? '',
                                    'qty' => $data->qc->rejected_quantity ?? 0,
                                    'uom' => $data->item->unitOfMeasure->name ?? '',
                                    'rate' => $data->purchase_order_data->rate ?? 0
                                ];
                            })->values();
                        @endphp
                        <option value="{{ $grn->id }}" 
                                data-truck="{{ $grn->truck_no }}"
                                data-items="{{ json_encode($itemsData) }}">
                            {{ $grn->purchase_order_receiving_no }} - {{ $grn->supplier->name ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Return Date:<span class="text-danger">*</span></label>
                <input type="date" name="purchase_date" class="form-control" id="purchase_date" value="{{ date('Y-m-d') }}">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Reference No:</label>
                <input type="text" name="reference_no" id="reference_no" class="form-control" placeholder="Automatically generated" readonly>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Truck No:</label>
                <input type="text" name="truck_no" id="truck_no" class="form-control" placeholder="Enter Truck No">
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
            <div class="form-group">
                <label class="form-label">Remarks (Optional):</label>
                <textarea name="remarks" placeholder="Overall remarks for this rejection return" class="form-control" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="row form-mar" id="itemSection">
        <div class="col-md-12">
            <div style="overflow-x: auto; width: 100%;">
                <table class="table table-bordered text-left" id="rejectionReturnTable" style="width:100%;">
                    <thead>
                        <tr>
                            <th style="min-width: 300px;" class="text-left">Item</th>
                            <th style="min-width: 150px;" class="text-left">Rate</th>
                            <th style="min-width: 150px;" class="text-left">Rejected Qty</th>
                            <th style="min-width: 200px;" class="text-left">Weight (grams)</th>
                        </tr>
                    </thead>
                    <tbody id="rejectionReturnBody">
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">Please select a GRN to load rejected items.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('.select2').select2({ width: '100%' });
        
        $('#grn_id, #purchase_date').on('change', function() {
            // Mock reference number generation
            let grn = $('#grn_id').find(':selected').text().split(' - ')[0];
            if(grn && grn !== 'Select GRN' && grn !== '') {
                $('#reference_no').val('RET-' + grn.trim());
            } else {
                $('#reference_no').val('');
            }
        });
    });

    function populateGrnDetails(select) {
        let option = $(select).find(':selected');
        let truckNo = option.data('truck');
        let items = option.data('items');

        if (!option.val()) {
            $('#truck_no').val('');
            $('#rejectionReturnBody').html('<tr><td colspan="4" class="text-center py-4 text-muted">Please select a GRN to load rejected items.</td></tr>');
            return;
        }

        // Populate Truck No
        $('#truck_no').val(truckNo || '');

        // Populate Items
        if (items && items.length > 0) {
            let html = '';
            items.forEach((item) => {
                html += `
                    <tr>
                        <td class="text-left">
                            <select class="form-control select2" disabled>
                                <option value="${item.id}" selected>${item.name}</option>
                            </select>
                            <input type="hidden" name="item_id[]" value="${item.id}">
                        </td>
                        <td class="text-left">
                            <input type="text" class="form-control text-left" value="${item.rate}" disabled>
                            <input type="hidden" name="rate[]" value="${item.rate}">
                        </td>
                        <td class="text-left">
                            <input type="text" class="form-control text-left" value="${item.qty}" disabled>
                            <input type="hidden" name="qty[]" value="${item.qty}">
                        </td>
                        <td class="text-left">
                            <input type="number" name="weight[]" step="0.01" class="form-control text-left" placeholder="Weight (grams)">
                        </td>
                    </tr>
                `;
            });
            $('#rejectionReturnBody').html(html);
            $('.select2').select2({ width: '100%' });
        } else {
            $('#rejectionReturnBody').html('<tr><td colspan="4" class="text-center py-4 text-warning">No rejected items found for this GRN.</td></tr>');
        }
    }
</script>
