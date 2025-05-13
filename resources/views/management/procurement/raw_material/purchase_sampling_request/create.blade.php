<form action="{{ route('raw-material.purchase-sampling-request.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.purchase-sampling-request') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Contract:</label>
                <select class="form-control select2" name="purchase_contract_id">
                    <option value="">Select Contract</option>
                    @foreach ($purchaseOrders as $purchaseOrder)
                        <option value="{{ $purchaseOrder->id }}">
                            Ticket No: {{ $purchaseOrder->contract_no }}
                          
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remark" placeholder="Remarks" class="form-control" rows="5"></textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>


<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
