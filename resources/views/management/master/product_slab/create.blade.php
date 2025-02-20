<form action="{{ route('product-slab.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.product-slab') }}" />

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Products:</label>
                <select class="form-control" name="product_id" id="product_id">
                    <option value="">Select Product</option>
                    @foreach ($slab_types as $slab_type)
                        <option value="{{ $slab_type->id }}">{{ $slab_type->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Slab Types:</label>
                <select class="form-control" name="product_slab_type_id" id="product_slab_type_id">
                    <option value="">Select Slab Types</option>
                    @foreach ($slab_types as $slab_type)
                        <option value="{{ $slab_type->id }}">{{ $slab_type->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

       
        <div class="col-xs-3 col-sm-3 col-md-3">
            <div class="form-group">
                <label>From:</label>
                <input type="text" name="from" placeholder="From" class="form-control"  />
            </div>
        </div>
        <div class="col-xs-3 col-sm-3 col-md-3">
            <div class="form-group">
                <label>To:</label>
                <input type="text" name="to" placeholder="To" class="form-control"  />
            </div>
        </div>

        <!-- Status -->
        <div class="col-xs-3 col-sm-3 col-md-3">
            <div class="form-group">
                <label>Deduction Type:</label>
                <select class="form-control" name="deduction_type" >
                    <option value="kg">Kg</option>
                    <option value="amount">Amount</option>
                </select>
            </div>
        </div>
        <div class="col-xs-3 col-sm-3 col-md-3">
            <div class="form-group">
                <label>Deduction value:</label>
                <input type="text" name="deduction_value" placeholder="Deduction Value" class="form-control" />
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
    $(document).ready(function () {
        initializeDynamicSelect2('#product_id', 'products', 'name', 'id', false, false);
        initializeDynamicSelect2('#product_slab_type_id', 'product_slab_types', 'name', 'id', false, false);
        //  function initializeDynamicSelect2(selector, tableName, columnName, idColumn = 'id', enableTags = false, isMultiple = true) {
    });
</script>