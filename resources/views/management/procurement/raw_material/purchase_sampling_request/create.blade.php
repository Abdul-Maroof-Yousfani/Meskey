<form action="{{ route('raw-material.purchase-sampling-request.store') }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.purchase-sampling-request') }}" />
    <input type="hidden" name="is_ind" value="{{ isset($ind) ? 1 : 0 }}" />
    @if (isset($purchaseOrder))
        <input type="hidden" name="purchase_contract_id" value="{{ $purchaseOrder->id }}" />
    @endif
    <div class="row form-mar">
        @if (!isset($ind))
            <div class="col-xs-12 col-sm-12 col-md-12 d-none">
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" checked disabled id="customQC"
                            name="is_custom_qc">
                        <input type="hidden" name="is_custom_qc" value="on" />
                        <label class="custom-control-label" for="customQC">Custom QC Request (Without Contract)</label>
                    </div>
                </div>
            </div>

            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group ">
                    <label>Product:</label>
                    <select name="product_id" id="product_id" class="form-control select2">
                        <option value="">Product Name</option>
                    </select>
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <label>Supplier Name:</label>
                    <input name="supplier_name" placeholder="Supplier Name" class="form-control">
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <label>Address:</label>
                    <input name="address" placeholder="Address" class="form-control">
                </div>
            </div>
        @endif

        @if (isset($ind, $purchaseOrder))
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <label>Contract:</label>
                    <input name="contract_id" disabled value="{{ $purchaseOrder->contract_no }}" class="form-control">
                </div>
            </div>
        @endif

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
        $('#ajaxSubmit .select2').select2();

        initializeDynamicSelect2('#supplier_id', 'suppliers', 'name', 'id', true, false);
        initializeDynamicSelect2('#product_id', 'products', 'name', 'id', false, false);

        $('#customQC').change(function() {
            if ($(this).is(':checked')) {
                $('#contractSelect').prop('disabled', true);
                $('#contractSelect').val('').trigger('change');
            } else {
                $('#contractSelect').prop('disabled', false);
            }
        });
    });
</script>
