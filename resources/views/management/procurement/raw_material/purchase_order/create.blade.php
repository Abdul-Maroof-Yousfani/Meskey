<form action="{{ route('raw-material.purchase-request.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.raw-material.purchase-request') }}" />
    <div class="row form-mar">
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
                <label>Sample Money:</label>
                <input type="text" name="sample_money" placeholder="Sample Money" class="form-control"  />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Weighbridge Amount:</label>
                <input type="text" name="weighbridge_amount" placeholder="Weighbridge Amount" class="form-control"  />
            </div>
        </div>

        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description (Optional):</label>
                <textarea name="description" placeholder="Description" class="form-control"></textarea>
            </div>
        </div>
        <!-- Status -->
        {{-- <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Status:</label>
                <select class="form-control" name="status" >
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div> --}}
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
        initializeDynamicSelect2('#product_id', 'products', 'name', 'id', false, false);
        initializeDynamicSelect2('#company_location_id', 'company_locations', 'name', 'id', true, false);


    });
    </script>