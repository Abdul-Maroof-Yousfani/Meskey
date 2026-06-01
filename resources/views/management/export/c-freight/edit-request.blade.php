<form action="{{ route('c-freight.update', $cFreight->id) }}" method="POST" id="cFreightForm" class="form">
    @csrf
    @method('PUT')
    <input type="hidden" name="update_type" value="request">
    <div class="row">
        <!-- Row 1 -->
        <div class="col-md-3">
            <div class="form-group">
                <label for="export_order_id">Export Order <span class="text-danger">*</span></label>
                <select name="export_order_id" id="export_order_id" class="form-control select2" required>
                    <option value="">Select Export Order</option>
                    @foreach($exportOrders as $eo)
                        <option value="{{ $eo->id }}" {{ $cFreight->export_order_id == $eo->id ? 'selected' : '' }}>{{ $eo->voucher_no }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                <label>Buyer</label>
                <input type="text" class="form-control" id="buyer" readonly>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                <label>Contract No</label>
                <input type="text" class="form-control" id="contract_no" readonly>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label>Commodity</label>
                <input type="text" class="form-control" id="commodity" readonly>
            </div>
        </div>

        <!-- Row 2 -->
        <div class="col-md-3">
            <div class="form-group">
                <label>No of Containers</label>
                <input type="text" class="form-control" id="no_of_containers" readonly>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label>Requested Containers <span class="text-danger">*</span></label>
                <input type="number" name="requested_containers" class="form-control" required placeholder="e.g. 120" value="{{ $cFreight->requested_containers }}">
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label>Port of Discharge</label>
                <input type="text" class="form-control" id="port" readonly>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label>Free Days <span class="text-danger">*</span></label>
                <input type="text" name="free_days" class="form-control" required placeholder="e.g. 14 Days" value="{{ $cFreight->free_days }}">
            </div>
        </div>

        <!-- Row 3 -->
        <div class="col-md-3">
            <div class="form-group">
                <label>Shipment Period</label>
                <input type="text" class="form-control" id="shipment_period" readonly>
            </div>
        </div>

        <div class="col-md-9">
            <div class="form-group">
                <label>ETR / Comments <span class="text-danger">*</span></label>
                <input type="text" name="etr" class="form-control" required placeholder="Estimated Time of Readiness / Comments" value="{{ $cFreight->etr }}">
            </div>
        </div>

    </div>

    <div class="row mt-2">
        <div class="col-12 text-right">
            <button type="button" class="btn btn-secondary modal-sidebar-close">Close</button>
            <button type="submit" class="btn btn-primary">Save Request</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });

        $('#export_order_id').change(function() {
            var eoId = $(this).val();
            if (eoId) {
                var url = "{{ route('c-freight.get-export-order-details', ':id') }}";
                url = url.replace(':id', eoId);
                
                $.ajax({
                    url: url,
                    type: "GET",
                    success: function(res) {
                        $('#buyer').val(res.buyer);
                        $('#contract_no').val(res.contract_no);
                        $('#no_of_containers').val(res.no_of_containers);
                        $('#commodity').val(res.commodity);
                        $('#port').val(res.port);
                        $('#shipment_period').val(res.shipment_period);
                    },
                    error: function(err) {
                        console.error('Error fetching EO details:', err);
                    }
                });
            } else {
                $('#buyer, #contract_no, #no_of_containers, #commodity, #port, #shipment_period').val('');
            }
        });

        // Trigger change to populate initial data
        if ($('#export_order_id').val()) {
            $('#export_order_id').trigger('change');
        }
    });
</script>
