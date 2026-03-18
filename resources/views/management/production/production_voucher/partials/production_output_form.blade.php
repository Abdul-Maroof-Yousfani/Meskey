<form method="POST" action="{{ isset($productionOutput) ? route('production-voucher.output.update', [$productionVoucher->id, $productionOutput->id]) : route('production-voucher.output.store', $productionVoucher->id) }}" id="ajaxSubmit" autocomplete="off">
    @csrf
    @if(isset($productionOutput))
        @method('PUT')
    @endif
    <input type="hidden" name="production_voucher_id" id="output_production_voucher_id" value="{{ $productionVoucher->id ?? '' }}">
    <input type="hidden" name="output_id" id="output_id" value="{{ $productionOutput->id ?? '' }}">
    <input type="hidden" id="listRefresh" value="{{ route('get.production-voucher-outputs', $productionVoucher->id) }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Production Output</h6>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Commodity:</label>
                        <select name="product_id" id="output_product_id" class="form-control select2" required>
                            <option value="">Select Commodity</option>
                            @if(isset($products))
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ isset($productionOutput) && $productionOutput->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Qty (kg):</label>
                        <input type="number" name="qty" id="output_qty" class="form-control" step="0.01" min="0.01" value="{{ $productionOutput->qty ?? '' }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Storage Location:</label>
                        <select name="storage_location_id" id="output_storage_location_id" class="form-control select2" required>
                            <option value="">Select Storage Location</option>
                            @if(isset($companyLocations))
                                @foreach($companyLocations as $location)
                                    <option value="{{ $location->id }}" {{ isset($productionOutput) && $productionOutput->storage_location_id == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Brand:</label>
                        <select name="brand_id" id="output_brand_id" class="form-control select2">
                            <option value="">Select Brand</option>
                            @if(isset($brands))
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ isset($productionOutput) && $productionOutput->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" id="output_remarks" class="form-control" rows="3">{{ $productionOutput->remarks ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">{{ isset($productionOutput) ? 'Update' : 'Save' }} Production Output</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('.select2').select2();
    });

    // Note: listRefresh is handled automatically by scripts.js for refresh-inputs-outputs route
</script>

