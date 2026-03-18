<form method="POST" action="{{ isset($productionInput) ? route('production-voucher.input.update', [$productionVoucher->id, $productionInput->id]) : route('production-voucher.input.store', $productionVoucher->id) }}" id="ajaxSubmit" autocomplete="off">
    @csrf
    @if(isset($productionInput))
        @method('PUT')
    @endif
    <input type="hidden" name="production_voucher_id" id="production_voucher_id" value="{{ $productionVoucher->id ?? '' }}">
    <input type="hidden" name="input_id" id="input_id" value="{{ $productionInput->id ?? '' }}">
    <input type="hidden" id="listRefresh" value="{{ route('get.production-voucher-inputs', $productionVoucher->id) }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Production Input</h6>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Commodity:</label>
                        <select name="product_id" id="input_product_id" class="form-control select2" required>
                            <option value="">Select Commodity</option>
                            @if(isset($products))
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ isset($productionInput) && $productionInput->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Location:</label>
                        <select name="location_id" id="input_location_id" class="form-control select2" required>
                            <option value="">Select Location</option>
                            @if(isset($sublocations))
                                @foreach($sublocations as $sublocation)
                                    <option value="{{ $sublocation->id }}" {{ isset($productionInput) && $productionInput->location_id == $sublocation->id ? 'selected' : '' }}>{{ $sublocation->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Qty (kg):</label>
                        <input type="number" name="qty" id="input_qty" class="form-control" step="0.01" min="0.01" value="{{ $productionInput->qty ?? '' }}" required>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" id="input_remarks" class="form-control" rows="3">{{ $productionInput->remarks ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Listings Table (for refresh functionality) -->
            <div class="row mt-3">
                <div class="col-md-12">
                    <h6 class="header-heading-sepration">Available Listings</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="inputListingsTable">
                            <thead>
                                <tr>
                                    <th>Commodity</th>
                                    <th>Location</th>
                                    <th>Available Qty (kg)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <button type="button" class="btn btn-sm btn-info" onclick="refreshInputListings()">
                                            <i class="ft-refresh-cw"></i> Refresh Listings
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">{{ isset($productionInput) ? 'Update' : 'Save' }} Production Input</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('.select2').select2();
    });

    function refreshInputListings() {
        const voucherId = $('#production_voucher_id').val();
        const productId = $('#input_product_id').val();
        const locationId = $('#input_location_id').val();

        if (!voucherId) {
            alert('Please select a production voucher first');
            return;
        }

        // AJAX call to refresh listings
        $.ajax({
            url: '{{ route("get.production-voucher") }}',
            method: 'POST',
            data: {
                voucher_id: voucherId,
                product_id: productId,
                location_id: locationId,
                action: 'refresh_input_listings'
            },
            success: function(response) {
                // Update listings table
                $('#inputListingsTable tbody').html(response.html);
            }
        });
    }

    // Note: listRefresh is handled automatically by scripts.js for refresh-inputs-outputs route
</script>

